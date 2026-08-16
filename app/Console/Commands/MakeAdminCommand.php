<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates or promotes the master admin account.
 *
 * Until this existed there was no way into the console on a fresh install:
 * DatabaseSeeder creates listings and help articles and no users at all, so
 * the admin portal shipped complete and unreachable.
 *
 * Deliberately a command and not a seeder with a default password. A seeded
 * credential has to be written down somewhere to be useful, which means it
 * ends up in a README, a chat message, or a .env committed by accident — and
 * it is identical on every install that ever ran the seeder. Prompting keeps
 * the password out of the repository, out of shell history, and out of the
 * deploy config, and makes it different everywhere.
 *
 *   php artisan listora:make-admin
 *   php artisan listora:make-admin --email=ops@listora1.com --name="Ops"
 *
 * Flags are for CI and unattended provisioning. `--password` is accepted but
 * warned about, because an argument is visible in `ps` and lands in shell
 * history; prefer the prompt or LISTORA_ADMIN_PASSWORD in the environment.
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'listora:make-admin
        {--email= : Email address for the account}
        {--name= : Display name}
        {--password= : Password (discouraged — visible in ps and shell history)}
        {--admin : Create a plain Admin rather than a Super Admin}
        {--force : Promote an existing account without confirming}';

    protected $description = 'Create or promote the master admin account for the console';

    public function handle(): int
    {
        $role = $this->option('admin') ? UserRole::Admin : UserRole::SuperAdmin;

        $email = $this->resolveEmail();
        if ($email === null) {
            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            return $this->promote($existing, $role);
        }

        return $this->create($email, $role);
    }

    private function resolveEmail(): ?string
    {
        $email = $this->option('email') ?: text(
            label: 'Email address',
            required: true,
            validate: fn (string $v) => Validator::make(['email' => $v], ['email' => ['email', 'max:190']])
                ->errors()->first('email') ?: null,
        );

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:190']]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return null;
        }

        return strtolower(trim($email));
    }

    private function promote(User $user, UserRole $role): int
    {
        if ($user->role === $role) {
            $this->info("{$user->email} is already {$role->label()}.");
        } else {
            $confirmed = $this->option('force') || $this->option('no-interaction') || confirm(
                label: "{$user->email} already exists as {$user->role->label()}. Promote to {$role->label()}?",
                default: false,
            );

            if (! $confirmed) {
                $this->comment('Left unchanged.');

                return self::SUCCESS;
            }

            $user->role = $role;
        }

        // An existing account may predate email verification being required.
        // An admin who cannot get past the verification gate cannot reach the
        // console, which would make the promotion useless.
        $user->email_verified_at ??= now();
        $user->save();

        $this->attachRole($user, $role);

        $this->line('');
        $this->info("Promoted {$user->email} to {$role->label()}.");
        $this->reportRbacState();

        return self::SUCCESS;
    }

    private function create(string $email, UserRole $role): int
    {
        $name = $this->option('name') ?: text(label: 'Display name', required: true);

        $secret = $this->resolvePassword();
        if ($secret === null) {
            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
            'role' => $role,
        ]);

        // Assigned as a property, NOT through create(): `email_verified_at` is
        // absent from User::$fillable, so passing it to a mass-assignment call
        // drops it silently — and /dashboard sits behind the `verified`
        // middleware, so the provisioned admin would be locked out of the
        // console it was created to reach.
        //
        // Verified on creation is correct here regardless: the account is being
        // made by whoever holds the server, and on a fresh install there is no
        // configured mailer and no inbox to click a link in.
        $user->email_verified_at = now();
        $user->save();

        $this->attachRole($user, $role);

        $this->line('');
        $this->info("Created {$role->label()}: {$user->email}");
        $this->line('Sign in at '.rtrim(config('app.url'), '/').'/login');
        $this->reportRbacState();

        return self::SUCCESS;
    }

    private function resolvePassword(): ?string
    {
        if ($fromOption = $this->option('password')) {
            $this->warn('--password is visible in ps output and shell history. Rotate it if this was a shared machine.');
            $secret = $fromOption;
        } elseif ($fromEnv = env('LISTORA_ADMIN_PASSWORD')) {
            $this->line('Using LISTORA_ADMIN_PASSWORD from the environment.');
            $secret = $fromEnv;
        } else {
            $secret = password(label: 'Password', required: true);

            if (password(label: 'Confirm password', required: true) !== $secret) {
                $this->error('Passwords did not match.');

                return null;
            }
        }

        // Same floor the application enforces elsewhere, read from settings so
        // provisioning cannot quietly create the one account on the system
        // that is exempt from the policy it publishes.
        $min = (int) setting('users.min_password_length', 12);

        $validator = Validator::make(
            ['password' => $secret],
            ['password' => ['required', Password::min($min)->letters()->numbers()]],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return null;
        }

        return $secret;
    }

    /**
     * Attach the matching RBAC role row when RBAC has been seeded.
     *
     * The primary `role` column already resolves to a permission set through
     * User::effectiveRoles(), so this is belt and braces — but it makes the
     * account visible in the roles console as holding the role, rather than
     * appearing to hold nothing while silently having everything.
     */
    private function attachRole(User $user, UserRole $role): void
    {
        if (! Role::configured()) {
            return;
        }

        $roleRow = Role::query()->where('key', $role->value)->first();

        if ($roleRow) {
            $user->roles()->syncWithoutDetaching([
                $roleRow->id => ['assigned_at' => now()],
            ]);
            $user->forgetEffectiveRoles();
        }
    }

    private function reportRbacState(): void
    {
        if (Role::configured()) {
            return;
        }

        $this->line('');
        $this->warn('RBAC is not seeded, so granular permissions are inactive and every');
        $this->warn('admin currently passes on the legacy "is admin" check. Run:');
        $this->line('    php artisan db:seed --class=RbacSeeder');
    }
}
