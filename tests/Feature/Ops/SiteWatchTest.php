<?php

namespace Tests\Feature\Ops;

use App\Services\Notifications\SlackNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The site watch.
 *
 * Exists because the public domain failed five times in six days and the last
 * outage ran 58 hours - not because it was hard to fix, but because nobody
 * knew. The watch cannot repair the fault; what it must do is notice, say so
 * once, and say when it is over.
 *
 * The alert-once behaviour is the part worth testing. A message every five
 * minutes for two days gets the channel muted, and a muted channel reports
 * nothing at all.
 */
class SiteWatchTest extends TestCase
{
    use RefreshDatabase;

    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->sent = [];

        $this->app->bind(SlackNotifier::class, fn () => new class($this->sent) implements SlackNotifier
        {
            public function __construct(private array &$sent)
            {
            }

            public function send(array $payload): void
            {
                $this->sent[] = $payload['text'] ?? '';
            }
        });
    }

    /**
     * One fake, driven by state.
     *
     * Http::fake() called a second time does not replace the first stub, so
     * "fail, then recover" has to be expressed as a single handler that
     * changes its mind rather than two successive fakes.
     */
    private ?int $status = 200;

    private function siteReturns(int $status): void
    {
        $this->status = $status;
        $this->installFake();
    }

    private function siteFailsTls(): void
    {
        $this->status = null;
        $this->installFake();
    }

    private function installFake(): void
    {
        Http::fake(function () {
            if ($this->status === null) {
                throw new ConnectionException('cURL error 35: SSL connect error');
            }

            return Http::response('', $this->status);
        });
    }

    public function test_a_healthy_site_passes_quietly(): void
    {
        $this->siteReturns(200);

        $this->artisan('listora:site-watch')->assertSuccessful();

        $this->assertSame([], $this->sent, 'A healthy check should say nothing.');
    }

    public function test_an_unreachable_site_fails_and_alerts(): void
    {
        $this->siteFailsTls();

        $this->artisan('listora:site-watch')->assertFailed();

        $this->assertCount(1, $this->sent);
        $this->assertStringContainsString('unreachable', $this->sent[0]);
        // The alert carries the fix, because whoever reads it at 3am should not
        // have to reconstruct it.
        $this->assertStringContainsString('_cf-custom-hostname', $this->sent[0]);
    }

    /** A TLS failure is how every one of these outages has actually presented. */
    public function test_a_bad_status_code_counts_as_down(): void
    {
        $this->siteReturns(409);

        $this->artisan('listora:site-watch')->assertFailed();

        $this->assertCount(1, $this->sent);
    }

    public function test_an_ongoing_outage_alerts_only_once(): void
    {
        $this->siteFailsTls();

        $this->artisan('listora:site-watch')->assertFailed();
        $this->artisan('listora:site-watch')->assertFailed();
        $this->artisan('listora:site-watch')->assertFailed();

        $this->assertCount(1, $this->sent, 'Repeat checks must not repeat the alert.');
    }

    public function test_recovery_is_announced_and_then_stays_quiet(): void
    {
        $this->siteFailsTls();
        $this->artisan('listora:site-watch')->assertFailed();

        $this->siteReturns(200);
        $this->artisan('listora:site-watch')->assertSuccessful();

        $this->assertCount(2, $this->sent);
        $this->assertStringContainsString('reachable again', $this->sent[1]);

        // And a second healthy check does not repeat the all-clear.
        $this->artisan('listora:site-watch')->assertSuccessful();
        $this->assertCount(2, $this->sent);
    }

    /**
     * It has to watch the public host. The vanity URL stayed up through all
     * five outages, so a watch pointed there would have reported green for
     * 58 hours while nobody could reach the site.
     */
    public function test_it_checks_the_configured_public_url(): void
    {
        config(['app.url' => 'https://listora1.com']);
        $this->siteReturns(200);

        $this->artisan('listora:site-watch')->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://listora1.com/up');
    }
}
