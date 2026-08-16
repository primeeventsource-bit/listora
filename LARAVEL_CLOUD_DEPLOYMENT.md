# Listora — Laravel Cloud deployment

How Listora is built, configured, and shipped to Laravel Cloud, and why the
non-obvious settings are what they are.

**Repository:** `primeeventsource-bit/listora`

---

## Stack

| | |
|---|---|
| Framework | Laravel **12** |
| PHP | **8.3+** (`composer.json` requires `^8.3`) |
| Database | MySQL 8 |
| Cache / queue | Redis |
| Auth | Breeze (web sessions) + Sanctum (API tokens, `/api/v1`) |
| Frontend | Blade. Static CSS/JS in `public/` — see [Assets](#assets) |

> **Not Laravel 11.** Vaytoven, which this backend was ported from, runs 11.
> Every Laravel 11 release (v11.31.0–v11.55.1) is flagged by security
> advisories and Composer refuses to install one. Pinning to it would mean
> shipping a framework with known unpatched CVEs. The structure is otherwise
> identical between the two versions.

---

## The application lives at the repository root

Laravel Cloud detects an application by looking for these files **at the root
of the repository**:

```
composer.json
artisan
public/index.php
bootstrap/app.php
routes/web.php
```

The app used to sit in `listora-app/`, where Laravel Cloud found nothing at
all. It was moved to the root in `fd7e42d`. **Do not nest it again** — a
subdirectory layout is the single most likely cause of "Laravel Cloud can't
find an application in this repository".

---

## Environments

Two environments, each tracking its own branch, each with its **own** database
cluster and Redis instance.

| Branch | Environment | `APP_ENV` | `APP_DEBUG` |
|---|---|---|---|
| `main` | dev | `staging` | `true` |
| `production` | production | `production` | `false` |

Work lands on `main` and deploys to dev automatically. Verify it end to end
there, then merge to `production`:

```bash
git checkout production
git merge main
git push origin production
```

**Never push directly to `production`.** Verification is its own step, not
something to bundle into a release commit.

**Never share credentials between environments.** Separate clusters and
separate `APP_KEY`s are what stop a leaked dev secret from reaching production
data.

---

## Environment variables

Laravel Cloud **injects `DB_*` and `REDIS_*` automatically** when you attach a
MySQL cluster and a cache resource. Do not set them by hand — a hand-typed
host will drift from the real one the moment a cluster is resized.

### Required

```env
APP_NAME=Listora
APP_ENV=production            # dev environment: staging
APP_KEY=                      # generate per environment, never shared
APP_DEBUG=false               # dev environment: true
APP_URL=https://<your-environment-url>

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# Redis, not file. Each container has its own filesystem, so file-backed
# cache and queues diverge across replicas the moment you scale past one.
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

# Structured JSON to stderr, so Laravel Cloud's log search is queryable by
# field (level, channel, datetime, context.*) rather than by substring.
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
LOG_LEVEL=info                # dev environment: debug

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="help@listora1.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> `CACHE_STORE`, not `CACHE_DRIVER`. Laravel renamed this in 11. The old key
> is not an error — it is **silently ignored**, and you get file caching in
> production with nothing in the logs to say so.

### AI support chat

```env
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-sonnet-5
```

Unset is safe: the Help page chat degrades to "temporarily unavailable" and
points visitors at email. It does not error and does not take the page down.

### Arize AX tracing — optional

```env
ARIZE_TRACING_ENABLED=true
ARIZE_SPACE_ID=                                     # base64 Space ID, not the space name
ARIZE_API_KEY=                                      # scoped service key
ARIZE_PROJECT_NAME=listora-support-chat-production  # MUST differ per environment
ARIZE_COLLECTOR_ENDPOINT=https://otlp.arize.com/v1/traces
```

Give each environment a **different** `ARIZE_PROJECT_NAME` or dev and
production traces land in one pile and neither is readable.

The endpoint above is the **US** cluster. Region is not assumed — switch it if
your space lives elsewhere:

| Cluster | Endpoint |
|---|---|
| US | `https://otlp.arize.com/v1/traces` |
| EU | `https://otlp.eu-west-1a.arize.com/v1/traces` |
| Canada | `https://otlp.ca-central-1a.arize.com/v1/traces` |

With both credentials blank the tracer is never built and every span helper
becomes a no-op. Tracing cannot take down the thing it observes.

### GeoIP and ops alerts — optional

```env
MAXMIND_MMDB_PATH=            # unset falls back to Cloudflare headers, then no-op
MAXMIND_ANONYMOUS_MMDB_PATH=
SLACK_OPS_WEBHOOK_URL=        # unset = NoOpSlackNotifier
```

### There are no payment variables

Listora takes no payment on the website. It holds no card details, no bank
details, no merchant account, and no gateway credentials — advertising plans
are arranged with the owner directly, off the site.

There is therefore no `STRIPE_*`, no `NMI_*`, and no processor configuration
of any kind, and no webhook route to exempt from CSRF.

**If you find yourself adding a gateway key here, the product changed.** The
schema, the permission catalog, and the support-chat system prompt all assert
that no payment happens, and all three need to change with it.

---

## Deploy command

```bash
php artisan migrate --force
```

`--force` is required: migrations refuse to run unprompted in production
without it.

Do **not** add `php artisan config:cache` unless you also stop using `env()`
outside config files. Do not add `db:seed` to a recurring deploy — see below.

---

## First deploy, per environment

1. Create the environment and point it at its branch (`main` for dev,
   `production` for production).
2. Attach a **MySQL cluster** and a **Redis cache**. `DB_*` and `REDIS_*` are
   injected once attached.
3. Set the environment variables above. Generate `APP_KEY` per environment.
4. Deploy. Migrations run via the deploy command.
5. Seed once, by hand, from the environment's shell:

   ```bash
   php artisan db:seed --force
   ```

   This loads demo listings and the **13 help-centre articles**. The articles
   are load-bearing, not decoration: the support assistant is instructed to
   answer policy questions **only** from what help search returns, so an
   unseeded help centre leaves it with nothing to quote.

   Run it once. It is not idempotent for listings — repeated runs duplicate
   them.

   This also seeds **RBAC** — the 44 permissions and 5 system roles. That part
   *is* idempotent and can be re-run on its own after a release that adds
   permissions:

   ```bash
   php artisan db:seed --class=RbacSeeder --force
   ```

   Until it has run, `Role::configured()` is false and every granular
   `permission:` check on the console falls back to a binary "is this user an
   admin" test. Seeding flips that over permanently.

6. **Create the master admin.** Nothing seeds an account — the console has no
   users until you make one, by design:

   ```bash
   php artisan listora:make-admin
   ```

   Prompts for name, email and password. Nothing is written to the repository
   and no default credential exists, so it differs on every environment.

   For unattended provisioning, pass `--email` and `--name` and put the
   password in `LISTORA_ADMIN_PASSWORD`. Avoid `--password`: an argument is
   visible in `ps` and lands in shell history.

   Re-running against an existing address **promotes** that account rather than
   creating a second one. `--admin` makes a plain Admin instead of a Super
   Admin — an Admin runs the console day to day but cannot create, edit, or
   assign roles, which is what stops one minting itself a super admin.

   Sign in at `/login`; `/admin` and `/dashboard` both land on the console.

7. Check health:

   ```
   GET /up      Laravel's shallow check — 200 if the app boots
   GET /health  deeper — pings DB and Redis, 503 if either is down
   ```

   `/health` also reports mail as **advisory**: a mail outage shows as
   `degraded` but never returns 503, because a broken mailer must not pull the
   site out of rotation.

---

## Assets

Pages load static CSS and JS from `public/` via `asset()`. There is no build
step on the critical path, and no page currently renders `@vite`.

`package.json`, `vite.config.js`, and Tailwind are present but vestigial. If
Laravel Cloud runs `npm run build`, that is harmless.

This changes the moment a view uses `@vite`. At that point `npm run build`
becomes mandatory, and skipping it produces a **Vite manifest not found**
error at runtime rather than a build failure — a page that 500s in production
having passed every check locally.

---

## Troubleshooting

**"No application found in repository"**
The app must be at the repository root. Confirm `composer.json`, `artisan`,
and `public/index.php` are at the top level, not inside a subdirectory.

**Deploy fails on `composer install`**
Check the PHP version is 8.3 or higher. `^8.3` is a hard requirement.

**Site loads but sessions or logins do not persist**
`CACHE_STORE` (not `CACHE_DRIVER`) must be `redis`, and a cache resource must
be attached. File-backed cache across multiple containers loses state
unpredictably.

**`500` on every page, nothing useful in logs**
Usually a missing `APP_KEY`. Set it per environment.

**Help page renders but "Browse by topic" is empty**
The seeder has not run. See step 5 above. The AI assistant will also be unable
to answer policy questions until it has.

**AI chat says "temporarily unavailable"**
`ANTHROPIC_API_KEY` is unset or invalid. This is a graceful degradation, not a
crash — the page stays up.

**Traces never appear in Arize**
Check that both `ARIZE_SPACE_ID` and `ARIZE_API_KEY` are set — with only one
set, tracing disables itself and logs a warning. Confirm the collector
endpoint matches your region. Confirm the `ARIZE_PROJECT_NAME` you are
searching under matches what the environment exports.

**Migrations fail on a fresh database**
Run them in order on an empty schema; several migrations extend tables created
by earlier ones. `php artisan migrate:fresh --force` **destroys all data** —
never run it against production.

---

## Custom domain

1. Environment settings → **Custom Domains** → add `listora1.com`.
2. Add the CNAME or A records Laravel Cloud shows you at your registrar.
3. Wait for DNS propagation, typically 5–15 minutes.
4. TLS is provisioned automatically.
5. Update `APP_URL` to the custom domain — absolute URLs in emails and
   redirects are generated from it, so a stale value sends people to the old
   host.

---

## Related

- [`CONTENT.md`](CONTENT.md) — copy for every page and tab
- `config/tracing.php` — Arize instrumentation
- `config/listora.php` — brand, contact details, plans
- `.env.example` — every variable, annotated

**Last updated:** 2026-08-16
