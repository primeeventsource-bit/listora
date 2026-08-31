# Listora — Laravel Cloud deployment

How Listora is built, configured, and shipped to Laravel Cloud, and why the
non-obvious settings are what they are.

**Repository:** `primeeventsource-bit/listora`

---

## Stack

| | |
|---|---|
| Framework | Laravel **12** |
| PHP | **8.2+** (`composer.json` requires `^8.2`; CI tests 8.2, 8.3 and 8.4) |
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

### If a push to `main` deploys production

Then an environment is tracking the wrong branch. Nothing in this repository
decides that — **the branch is set per environment in the Laravel Cloud
dashboard**, so it cannot be fixed by a commit. Check it directly:

1. Laravel Cloud → the **production** environment → **Settings → Source
   Control**. The branch must read `production`. If it reads `main`, every
   push to dev has been deploying production, and the two-environment split
   has been decorative.
2. Laravel Cloud → the **dev** environment → same screen. Branch must read
   `main`.
3. If only one environment exists, that is the problem: create the second and
   give it its **own** database cluster and Redis instance. Sharing them is
   worse than having one environment, because it looks separated and is not.

Confirm from the CLI which commit each branch is on before releasing — if they
are identical, dev is not being used as a gate:

```bash
git fetch origin
git log --oneline origin/production..origin/main   # what dev has that prod does not
```

A healthy repository has dev **ahead** of production between releases. Two
branches sitting on the same commit means work is reaching production without
being verified anywhere first.

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

### Mapbox — optional

Powers the basemap behind the visitor map on **Admin → Reports**.

```bash
MAPBOX_ACCESS_TOKEN=pk.xxxxxxxx
# Optional. Defaults to mapbox://styles/mapbox/light-v11
MAPBOX_STYLE=
```

`MAPBOX_API_KEY` and `MAPBOX_TOKEN` are also accepted, so an existing
deployment does not render a blank map because of a name. The canonical one is
`MAPBOX_ACCESS_TOKEN`.

Use a **public** (`pk.*`) token — it is sent to the browser by design. Restrict
it by URL in the Mapbox dashboard rather than treating it as a secret. Never
put a secret (`sk.*`) token here.

Unset is a supported state: the reports page falls back to a plotted grid with
the same pins and the same numbers, and makes no third-party call at all.

### GeoIP — required for a useful advertising map

```env
MAXMIND_LICENSE_KEY=          # MaxMind account → Manage Licence Keys
MAXMIND_MMDB_PATH=            # only to override where the database is installed
MAXMIND_ANONYMOUS_MMDB_PATH=
```

And add to the **build** command, after `composer install`:

```bash
php artisan listora:geoip-update
```

> **Unset is not a neutral state here.** `GeoIpService` falls back to
> Cloudflare's request headers, and `cf-ipcity`, `cf-region`, `cf-iplatitude`
> and `cf-iplongitude` are **Cloudflare Pro and above** — Laravel Cloud's
> bundled Cloudflare passes only `cf-ipcountry`. Every visitor then resolves
> to their country's geographic centroid, so the advertising map shows one pin
> per country (every US visitor stacked at 39.8283, -98.5795, a field in
> Kansas) and `geo_city` and `geo_region` are never populated at all.

GeoLite2 is free, but MaxMind's licence forbids redistributing the database,
so it is downloaded at build time rather than committed — which also keeps a
60MB binary that goes stale weekly out of the repository.

**Build, not deploy.** Containers are rebuilt per release, so the file has to
be baked into the image; downloading at deploy time would refetch it on every
container start and lose it on the next. The command fails soft, so a missing
key or a MaxMind outage degrades geolocation rather than failing the build.
Pass `--strict` if you would rather the build stop.

### Ops alerts — optional

```env
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
step on the critical path, and no **routed** page renders `@vite`.

`resources/views/welcome.blade.php` does render it, but nothing routes to it —
`/` goes to `HomeController`. There is no `public/build` manifest, so the
moment anything routes to that view it will 500 with "Vite manifest not
found". Delete it or build assets before wiring it up.

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

**"The [composer.lock] and [composer.json] files could not be found"**
The environment has a **base directory** set — almost certainly `listora-app`,
from before `fd7e42d` moved the app to the repository root. Nothing in this
repository can fix it: Laravel Cloud → the environment → **Settings**, in the
build or source-control section, clear the **Base Directory** field so it is
empty (the repository root). Confirm the files really are committed at the top
level first:

```bash
git ls-tree --name-only origin/production | grep composer
```

This failure is quiet in the worst way. The build fails, the *last successful*
build keeps serving, and the site carries on running code from before the
problem started — so the symptom is "our changes aren't appearing", not "the
deploy is broken". Check **Deploys** before assuming a caching problem.

**Deploy fails on `composer install`**
Check the PHP version is 8.2 or higher. `^8.2` is the constraint in `composer.json`.

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
