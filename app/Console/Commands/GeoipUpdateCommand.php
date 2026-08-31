<?php

namespace App\Console\Commands;

use GeoIp2\Database\Reader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use Throwable;

/**
 * Install or refresh the MaxMind GeoLite2 City database.
 *
 * Without it, GeoIpService falls back to Cloudflare's headers - and Laravel
 * Cloud's bundled Cloudflare sends only cf-ipcountry, so every visitor
 * resolves to their country's geographic centroid. The advertising map then
 * shows one pin per country stacked in a field in Kansas, and the approximate
 * city and region the brief asks for cannot be populated at all.
 *
 * Meant for the BUILD command, not the deploy command. Containers are rebuilt
 * per release and the file has to be baked into the image; downloading it at
 * deploy time would fetch ~60MB on every container start and lose it on the
 * next.
 *
 * Fails soft on purpose. A missing licence key, a MaxMind outage, or a corrupt
 * archive must not fail the build - the site works without geolocation, it
 * just reports less. Exits non-zero only with --strict, for a pipeline that
 * would rather stop.
 */
class GeoipUpdateCommand extends Command
{
    protected $signature = 'listora:geoip-update
        {--strict : Exit non-zero if the database could not be installed}
        {--force : Download even if a database is already present}';

    protected $description = 'Download the MaxMind GeoLite2 City database used for approximate visitor geolocation';

    public function handle(): int
    {
        $target = config('services.maxmind.mmdb_path');

        if (! $target) {
            return $this->giveUp('No MAXMIND_MMDB_PATH configured.');
        }

        if (! $this->option('force') && is_readable($target)) {
            $this->info('GeoLite2 database already present at '.$target);
            $this->line('  Age: '.$this->ageOf($target).'. Use --force to refresh.');

            return self::SUCCESS;
        }

        $key = config('services.maxmind.license_key');

        if (! $key) {
            return $this->giveUp(
                'No MAXMIND_LICENSE_KEY set. Geolocation will fall back to Cloudflare headers, '.
                'which give country only - the advertising map will plot every visitor at their '.
                "country's centroid."
            );
        }

        $this->info('Downloading GeoLite2 City from MaxMind...');

        try {
            $archive = $this->download($key);
            $mmdb = $this->extract($archive);
            $this->install($mmdb, $target);
        } catch (Throwable $e) {
            return $this->giveUp('Could not install the database: '.$e->getMessage());
        } finally {
            $this->cleanUp();
        }

        $this->info('Installed '.$target.' ('.$this->humanSize(filesize($target)).')');

        return self::SUCCESS;
    }

    private function download(string $key): string
    {
        $response = Http::timeout(120)
            ->withOptions(['sink' => $path = $this->workDir().'/GeoLite2-City.tar.gz'])
            ->get('https://download.maxmind.com/app/geoip_download', [
                'edition_id' => 'GeoLite2-City',
                'license_key' => $key,
                'suffix' => 'tar.gz',
            ]);

        if (! $response->successful()) {
            // MaxMind answers an invalid key with 401 and a plain-text body,
            // which is worth repeating verbatim - "unauthorized" alone sends
            // people looking at the wrong thing.
            throw new \RuntimeException(
                'MaxMind returned HTTP '.$response->status().'. '.trim(substr($response->body(), 0, 200))
            );
        }

        return $path;
    }

    /** @return string Path to the extracted .mmdb */
    private function extract(string $archive): string
    {
        $dir = $this->workDir().'/extracted';
        File::ensureDirectoryExists($dir);

        // The tarball contains GeoLite2-City_YYYYMMDD/GeoLite2-City.mmdb, and
        // the date changes with every release, so the file is found rather
        // than assumed.
        (new PharData($archive))->decompress();
        (new PharData(str_replace('.tar.gz', '.tar', $archive)))->extractTo($dir, null, true);

        $found = collect(File::allFiles($dir))
            ->first(fn ($f) => $f->getExtension() === 'mmdb');

        if (! $found) {
            throw new \RuntimeException('No .mmdb file inside the archive.');
        }

        return $found->getPathname();
    }

    private function install(string $mmdb, string $target): void
    {
        // Opened before it is installed: a truncated download is still a file,
        // and a corrupt database that replaces a working one would break
        // geolocation until the next release rather than failing here.
        (new Reader($mmdb))->close();

        File::ensureDirectoryExists(dirname($target));

        // Move into place in one step so a reader never sees a partial file.
        File::move($mmdb, $target.'.new');
        File::move($target.'.new', $target);
    }

    private function giveUp(string $reason): int
    {
        $this->warn($reason);

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    private function workDir(): string
    {
        $dir = storage_path('app/geoip-tmp');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    private function cleanUp(): void
    {
        File::deleteDirectory(storage_path('app/geoip-tmp'));
    }

    private function ageOf(string $path): string
    {
        $days = (int) floor((time() - filemtime($path)) / 86400);

        return $days === 0 ? 'installed today' : $days.' '.str('day')->plural($days).' old';
    }

    private function humanSize(int|false $bytes): string
    {
        return $bytes === false ? 'unknown size' : round($bytes / 1_048_576, 1).' MB';
    }
}
