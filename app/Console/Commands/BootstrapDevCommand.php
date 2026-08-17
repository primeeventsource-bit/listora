<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoTrafficSeeder;
use Database\Seeders\HelpArticleSeeder;
use Database\Seeders\ListoraSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\TestAccountsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;

/**
 * Brings a freshly created dev environment up in one command.
 *
 * The steps are individually documented but easy to get out of order, and two
 * of them (test accounts, demo traffic) are actively unsafe against the wrong
 * database — which is not hypothetical: a dev environment pointed at the
 * production cluster is exactly the state this project was in when this
 * command was written.
 *
 * So the ordering and the guards live here rather than in a runbook that has
 * to be followed correctly at the end of a long day.
 *
 *   php artisan listora:bootstrap-dev
 *
 * Refuses outright in production, and stops before writing anything if the
 * database already holds real accounts — the signal that it is not the empty
 * dev database it was pointed at.
 */
class BootstrapDevCommand extends Command
{
    protected $signature = 'listora:bootstrap-dev
        {--no-demo-traffic : Skip the 90 days of synthetic analytics data}
        {--no-test-accounts : Skip the per-role sign-in fixtures}
        {--force : Proceed even though the database already holds accounts}';

    protected $description = 'Migrate and seed a fresh dev environment: RBAC, site data, test logins, demo traffic';

    public function handle(): int
    {
        if (App::environment('production')) {
            $this->error('Refusing: APP_ENV is production.');
            $this->line('This seeds accounts with a shared known password and synthetic traffic');
            $this->line('into an append-only table. Neither belongs on a production database.');

            return self::FAILURE;
        }

        if (! $this->databaseLooksEmpty()) {
            return self::FAILURE;
        }

        $this->line('');
        $this->components->info('Migrating');
        $this->call('migrate', ['--force' => true]);

        // RBAC first: everything downstream authorises against it, and the
        // seeders below attach role rows that do not exist until it has run.
        $this->components->info('Seeding roles and permissions');
        $this->call('db:seed', ['--class' => RbacSeeder::class, '--force' => true]);

        $this->components->info('Seeding listings and help articles');
        $this->call('db:seed', ['--class' => ListoraSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => HelpArticleSeeder::class, '--force' => true]);

        if (! $this->option('no-test-accounts')) {
            $this->components->info('Creating one sign-in per role');
            $this->call('db:seed', ['--class' => TestAccountsSeeder::class, '--force' => true]);
        }

        if (! $this->option('no-demo-traffic')) {
            $this->components->info('Generating 90 days of demo traffic');
            $this->call('db:seed', ['--class' => DemoTrafficSeeder::class, '--force' => true]);
        }

        $this->summarise();

        return self::SUCCESS;
    }

    /**
     * The guard that matters.
     *
     * An environment freshly attached to its own cluster has no users. Finding
     * real ones means this is pointed somewhere else — most likely the
     * production cluster, which is the mistake this command exists to survive.
     * Test fixtures are ignored so re-running on a dev box stays frictionless.
     */
    private function databaseLooksEmpty(): bool
    {
        try {
            $real = User::query()->where('email', 'not like', '%@listora1.test')->count();
        } catch (\Throwable) {
            // No users table yet — an unmigrated database, which is precisely
            // what this command expects to find.
            return true;
        }

        if ($real === 0) {
            return true;
        }

        $this->line('');
        $this->warn("This database already holds {$real} account(s) that are not test fixtures.");
        $this->line('  Connection: <options=bold>'.config('database.default').'</> → '
            .config('database.connections.'.config('database.default').'.database'));
        $this->line('');
        $this->line('  A dev environment attached to its own cluster starts empty. Real accounts');
        $this->line('  mean this is pointed at a database that is already in use — check that dev');
        $this->line('  is not still sharing the production cluster before continuing.');
        $this->line('');

        if ($this->option('force')) {
            $this->warn('  --force given; continuing anyway.');

            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('  Stopping. Re-run with --force if this really is the right database.');

            return false;
        }

        return confirm(label: 'Seed this database anyway?', default: false);
    }

    private function summarise(): void
    {
        $this->line('');
        $this->components->info('Dev environment ready');

        $rows = User::query()
            ->where('email', 'like', '%@listora1.test')
            ->orderBy('id')
            ->get(['name', 'email', 'role']);

        if ($rows->isNotEmpty()) {
            $this->table(
                ['Role', 'Email', 'Password'],
                $rows->map(fn (User $u) => [$u->role->label(), $u->email, 'ListoraTest2026'])->all(),
            );
            $this->warn('These share a known password. Dev only — never seed them anywhere public.');
        }

        $this->line('');
        $this->line('  Sign in at <options=bold>'.rtrim(config('app.url'), '/').'/login</>');
        $this->line('');
        $this->line('  Still to do by hand:');
        $this->line('    <info>php artisan listora:make-admin</info>   your own admin, prompted password');
        $this->line('    <info>php artisan listora:map-check</info>    confirm the Reports map will render');

        // Counted rather than assumed: a migration that silently did nothing
        // is the failure this summary is meant to make visible.
        $this->line('');
        $this->line('  Rows: '
            .DB::table('listings')->count().' listings, '
            .DB::table('help_articles')->count().' help articles, '
            .DB::table('permissions')->count().' permissions, '
            .DB::table('tracking_events')->count().' tracking events.');
    }
}
