# Deploying the Barangay Profiling System for free

**Want the exact clicks, names, and copy-paste commands? Start with
[the deployment checklist](deployment-step-by-step.md).** This document covers the
architecture, constraints, and background.

Written 2026-09-08. Every limit quoted was read from the provider's own docs on
that date. Every claim about *this repo* was verified by running it — the
commands and their output are included so you can re-check them.

Modelled on `save_furry_friend/ai_docs/deployment.md`, which targets the same
account set. Two of its prerequisite findings apply here verbatim and are
credited where they come up.

---

## Contents

- [You need two of your six services](#you-need-two-of-your-six-services)
- [What this costs](#what-this-costs)
- [Five repo changes were required](#five-repo-changes-were-required)
- [How the pieces wire together](#how-the-pieces-wire-together)
- [Step by step](#step-by-step)
- [Cold start](#cold-start)
- [When it doesn't work](#when-it-doesnt-work)
- [What we did not use, and when you would add it](#what-we-did-not-use-and-when-you-would-add-it)

---

## You need two of your six services

| Service | Needed | Why |
|---|---|---|
| **Render** web service | **Yes** | Runs the container. One service, Docker runtime, free plan. |
| **Neon** Postgres | **Yes** | The database. Neon is Postgres-only — there is no MySQL option — so the app had to be made Postgres-clean. It now is. |
| MongoDB Atlas | No | Nothing in the app touches Mongo. `composer.json` requires exactly three packages: `laravel/framework`, `laravel/tinker`, `predis/predis`. No `ext-mongodb`, no `mongodb/mongodb`. |
| Cloudflare R2 | No | No file storage of any kind. `grep -r 'Storage::\|->store(\|UploadedFile\|->file('` over `app/`, `routes/`, `resources/views/`, `database/` returns **zero matches**. Nothing is uploaded, so there is nothing to put in a bucket. |
| Cloudflare Pages | No | Pages hosts static sites. This app is server-rendered Blade — nginx *inside* the container serves the Vite build from `public/build`. Verified in the built image: `/build/assets/app-Bv-UW6LG.css` → `200, 29,321 B, text/css` and `/build/assets/app-De1LAS1t.js` → `200, 257,213 B`. There is no separate front end to host. |
| Brevo | No | The app never sends mail. No `Mail::`, no Mailable, no notification is dispatched, and there are no password-reset routes in `routes/web.php` — the `password_reset_tokens` table exists from Laravel's default migration but nothing writes to it. `User` has the `Notifiable` trait and never uses it. |

So: **Render + Neon.** Keep the other four accounts for the next project.

The reason the answer is this short is the architecture. See
`ai_docs/server-rendering-vs-spa.md` — a server-rendered app is one deployable
that emits finished HTML, so the SPA's split of "static host + API host + CDN +
object storage" collapses into one container and one database.

---

## What this costs

**$0/month, permanently, with no card on file.** Neither provider asks for one.

Measured against the real limits:

| Limit | Free allowance | This app | Headroom |
|---|---|---|---|
| Neon storage | 0.5 GB per project | **12 MB** fully seeded | 2.4% used |
| Neon compute | 100 CU-hours/month, scales to zero after 5 min idle | idle between requests | ~400 h/month at 0.25 CU |
| Neon egress | 5 GB/month per project | HTML only, no media | negligible |
| Render RAM | **512 MB** for the whole container | **81 MiB** peak under 30 concurrent requests | 16% used |
| Render CPU | **0.1 CPU** | see the latency figures below | the real constraint |
| Render instance hours | 750/month per workspace; spun-down services consume none | one service | ample for one demo service |
| Render bandwidth | your workspace's monthly included outbound amount | ~60 KB per page view | not the binding limit |

Two Render facts worth internalising before you start, both from
<https://render.com/docs/free>:

- **No shell access.** Free web services support neither SSH, dashboard shell,
  nor one-off jobs. Every `artisan` command you need to run against production —
  migrations, creating the first user, seeding — has to be run **from your
  machine against Neon directly.** That is why steps 3 and 5 look the way they
  do; it is not a stylistic preference.
- **Render's own free Postgres expires 30 days after creation** (then a 14-day
  grace period, then deletion). That is the reason this setup uses Neon instead,
  whose free plan has no expiry.

The 12 MB is measured, not estimated — full seed (3,200 constituents, 11,337 tax
records, 953 criminal records) loaded into Postgres 18:

```
$ psql -tAc "select pg_size_pretty(pg_database_size('brgy'));"
  12 MB
```

Neon's 0.5 GB is the limit to watch, and you are two orders of magnitude away.

---

## Six repo changes were required

The image in this repo could not be deployed anywhere until these were fixed.
All six are already applied — this section is so you know why the files look
the way they do, and so you can recognise the failure if you undo one.

> Since these were written, the project has moved to PostgreSQL wholesale: the
> `postgres:18-alpine` compose service, `DB_CONNECTION=pgsql` in `.env.example`
> and `phpunit.xml`, and `config/database.php` reduced to `pgsql` + `sqlite`.
> Development and production now run the same engine, which is what makes the
> rest of this document boring in the good way.

### 1. No PostgreSQL driver in the image

`Dockerfile` installed `pdo_mysql` and nothing else. Neon speaks Postgres, so
`pdo_pgsql` was added (`libpq-dev` at build time, `libpq` at runtime) — and
`pdo_mysql` has since been dropped, since nothing uses it.

### 2. A MySQL-only query broke the entire dashboard

`app/Queries/DashboardMetrics.php` ranked captains with
`->having('constituents_count', '>', 0)`. A `HAVING` clause with no `GROUP BY`
is a MySQL extension. On SQLite it fails outright, and on Postgres it forces the
query to be treated as an aggregate:

```
SQLSTATE[HY000]: General error: 1 HAVING clause on a non-aggregate query
```

Eleven tests failed — every dashboard test. Replaced with `->has('constituents')`,
which emits an `EXISTS` subquery: portable, and able to use the
`barangay_captain_id` index instead of filtering a computed alias.

### 3. Search was silently case-sensitive on Postgres

The one that would have shipped. `HasPersonName::search()` used
`orWhere($column, 'like', "%$term%")`. Plain `LIKE` is case-insensitive on MySQL
**only because of the column collation**; on Postgres it is case-sensitive.
Proven against Postgres 18 before the fix, with one resident named "Cruz":

```
exact  Cruz -> 1
lower  cruz -> 0        ← "No constituents match your search."
upper  CRUZ -> 0
```

The existing tests all passed, because every one of them happened to search
using the stored casing (`'Amihan'`, `'Mabini'`, `'Caloocan'`).

Fixed with `orWhereLike($column, "%$term%", caseSensitive: false)`, which the
framework compiles to `ILIKE` on Postgres and `LIKE` everywhere else. After:

```
Cruz -> 1    cruz -> 1    CRUZ -> 1    cRuZ -> 1
sql -> ... where ("first_name"::text ilike ? or ...)
```

`tests/Feature/ConstituentCrudTest.php` gained
`it('matches the search term regardless of case')` so this cannot regress
unnoticed.

### 4. The image spoke FastCGI, not HTTP

*Same finding as the reference doc's §2, and it applies unchanged here.*

The `app` stage ends `EXPOSE 9000` / `CMD ["php-fpm"]`, with nginx as a separate
compose service. A free PaaS gives you **one** container and routes HTTP to
**one** injected port. Render pointed at that image would connect and receive
FastCGI bytes it cannot parse.

Added a final `render` stage: nginx + php-fpm under supervisord, answering HTTP
on `$PORT`. Three Alpine-specific details cost real debugging time:

- `conf.d/*.conf` is included in nginx's **main** context on Alpine and
  `http.d/*.conf` inside `http`. A `server` block in `conf.d` is a syntax error,
  so the rendered vhost goes to `http.d`.
- Alpine's `nginx.conf` has **no `pid` directive** to rewrite, and the compiled
  default is `/run/nginx/nginx.pid`, which the unprivileged `app` user cannot
  create. One is inserted pointing at `/tmp`.
- The build-time `nginx -t` runs as root and *writes that pid file*, which then
  got committed into the layer owned by root. `/tmp` is sticky, so at runtime
  `app` could neither overwrite nor unlink it:
  `open() "/tmp/nginx.pid" failed (13: Permission denied)`, nginx into
  `FATAL`, container up but answering nothing. The build now removes it.

`compose.yaml` pins `target: app`, so the dev workflow is untouched. A plain
`docker build .` — which is what Render runs — builds `render`, because it is the
last stage. **Keep it last.**

### 5. Dev package manifests were baked into the production image

Found by running the image, not by reading it. `COPY . .` copied the host's
`bootstrap/cache/packages.php`, written by `composer install` **with** dev
dependencies. The image's `vendor/` is `--no-dev`, so the container died before
serving a request:

```
production.ERROR: Class "Laravel\Pail\PailServiceProvider" not found
```

`bootstrap/cache/*.php` is now in `.dockerignore`. Laravel regenerates it at
runtime.

### 6. php-fpm was sized for a laptop, not a 512 MB instance

`docker/php/www.conf` allows `pm.max_children = 20` with `memory_limit = 512M`
per worker, and `docker/php/opcache.ini` reserves 192 MB of shared memory. On a
dev machine that is sensible. On Render's Free plan — **0.1 CPU and 512 MB for
nginx, supervisord and php-fpm combined** — opcache alone claims 37% of the
container before a single request arrives, and a handful of concurrent requests
gets the whole thing OOM-killed. The symptom is intermittent 502s that read like
an application bug.

Rather than shrink the dev settings, the `render` stage layers two override
files on top (`docker/php/render-php.ini`, `docker/php/render-fpm.conf`), named
so PHP loads them last:

| Setting | Dev | `render` stage |
|---|---|---|
| `pm` | `dynamic` | `ondemand` — no benefit to resident workers at 0.1 CPU |
| `pm.max_children` | 20 | 4 |
| `memory_limit` | 512M | 128M |
| `opcache.memory_consumption` | 192 | 64 |
| `opcache.validate_timestamps` | 1 | 0 — the image is immutable |

Verified by constraining the container to exactly Render's plan
(`docker run --memory 512m --cpus 0.1`) and firing 30 concurrent authenticated
requests:

```
30/30 -> 200
peak mem: 80.74MiB / 512MiB (15.77%)
OOMKilled=false  Restarts=0  bad log lines: 0
```

### Also adjusted, less dramatically

- **`config:cache` stays out of the build.** *Reference doc §1.* It freezes every
  `env()` call at the moment it runs, and the build has no platform environment,
  so a baked cache would ignore everything Render injects. `docker/php/entrypoint.sh`
  already caches at container start, which is correct — no change needed, but do
  not "optimise" it into the Dockerfile.
- **The database wait understood only MySQL.** It defaulted to host `mysql`,
  port `3306`. With Neon there is no sibling container and `DB_URL` overrides the
  host anyway, so the entrypoint would have waited 60 s on a nonexistent host and
  then `exit 1` before php-fpm ever started. It now skips the wait entirely when
  `DB_URL` is set, and otherwise defaults to `postgres:5432` with a per-driver
  port table.
- **`.env` is no longer seeded when `APP_KEY` is in the environment.** On Render,
  copying `.env.example` is actively harmful: it carries the compose service
  names (`DB_HOST=postgres`, `REDIS_HOST=redis`, `MAIL_HOST=mailpit`), and any
  variable the platform does not set would silently fall back to those.

### The result

The suite passes on both engines the project now supports, 59 tests including
the new regression test:

| Engine | Result |
|---|---|
| PostgreSQL 18 (`postgres` service, and `brgy_testing` for the suite) | **59 passed** (253 assertions) |
| SQLite (`:memory:`) | **59 passed** (253 assertions) |

SQLite is kept green deliberately: it is the cheapest possible check that no
engine-specific SQL has crept back in. MySQL was verified green at the time of
the cutover and has since been removed from the project entirely.

Pint: PASS. PHPStan level 6: no errors.

---

## How the pieces wire together

One value per row. Get these right and nothing else is hard; get one wrong and
the failure looks like a different problem entirely.

| Variable | Value | Consequence of getting it wrong |
|---|---|---|
| `APP_KEY` | `php artisan key:generate --show`, set once, never rotated | Unset → the entrypoint generates a **fresh key per container**, so every session and signed URL breaks on each deploy and cold start. Users appear randomly logged out. |
| `APP_URL` | the service's own `https://…onrender.com` | Wrong → redirects and asset URLs point elsewhere. |
| `DB_URL` | Neon **pooled** string, `?sslmode=require` | Laravel's URL parser overrides host, database, credentials **and** the `sslmode` that `config/database.php` hardcodes to `prefer`. |
| `DB_CONNECTION` | `pgsql` | Also the project default now, so this is belt-and-braces rather than load-bearing. |
| `SESSION_DRIVER` | `database` | `file` → Render's free plan has no persistent disk and recycles the instance after 15 idle minutes, logging everyone out on every cold start. |
| `CACHE_STORE` | `file` | `database` → every cache read is a Postgres round-trip that wakes Neon's compute. |
| `QUEUE_CONNECTION` | `sync` | `database` → a `queue:work` daemon polls the `jobs` table continuously, holds Neon awake permanently and burns the 100 CU-hour allowance in about two weeks. *Reference doc's finding; this app queues nothing at all, so `sync` costs nothing.* |
| `LOG_CHANNEL` | `stderr` | Otherwise the log goes to a file nobody will read in an ephemeral container. |
| `PORT` | injected by Render (10000) | The entrypoint renders it into the nginx vhost with `envsubst '${PORT}'`. Only `${PORT}` is named — substituting everything would eat nginx's own `$uri` and `$document_root`. |

### Neon needs two connection strings

*Straight from the reference doc, and the reason `RUN_MIGRATIONS=false`.*

Runtime uses the **pooled** endpoint (`-pooler` in the hostname). Migrations use
the **direct** endpoint: the pooler runs PgBouncer in transaction mode, which
drops the prepared statements and session state `php artisan migrate` relies on.

### The seeder cannot run inside the production image

Found by trying it. `php artisan db:seed` in the deployed container fails:

```
Call to undefined function Database\Factories\fake()
```

`fakerphp/faker` is a **dev** dependency and the image installs `--no-dev`. This
is correct — production images should not carry a data generator — but it means
demo data has to be loaded from your laptop, and the first real user has to be
created with `tinker`. Both are covered in step 5.

---

## Step by step

### 0. Choose names

```bash
APP_URL=https://brgy-profiling.onrender.com   # Render service name: brgy-profiling
```

`render.yaml` at the repo root already uses `brgy-profiling` and region
`singapore`. Change both together if you want a different name.

### 1. Postgres — Neon

1. Sign up at <https://neon.com>. No card.
2. **Create project.** Postgres **18** (the default, and what the suite is
   verified against). Region **AWS ap-southeast-1 (Singapore)** — nearest to you
   and to the Render region. **Region is permanent.**
3. Copy **both** connection strings:
   - pooled — hostname contains `-pooler`
   - direct — the same hostname with `-pooler` removed
4. Append `?sslmode=require` to both. Neon rejects non-TLS connections; no CA
   file is needed, its chain is in every system trust store.

No IP allowlist to configure. Compute auto-resumes in a few hundred milliseconds
on the first query after idling.

### 2. Generate the app key

```bash
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
# base64:....................................=
```

Save it. This is the single most common deployment mistake — see the table above
for what happens if you skip it.

### 3. Migrate, against the DIRECT endpoint

Before the first deploy, because `SESSION_DRIVER=database` needs the `sessions`
table to exist:

```bash
export DB_DIRECT='postgresql://USER:PASS@ep-xxx.ap-southeast-1.aws.neon.tech/neondb?sslmode=require'

docker compose run --rm --no-deps -T -u app \
  -e DB_CONNECTION=pgsql -e DB_URL="$DB_DIRECT" -e SKIP_DB_WAIT=true \
  --entrypoint sh app -c 'php artisan migrate --force'
```

Verified locally against Postgres 18 — all seven migrations apply cleanly and the
foreign keys with `ON DELETE CASCADE` / `ON DELETE SET NULL` behave as the suite expects.

### 4. Deploy — Render

1. Push this branch to GitHub.
2. Render dashboard → **New → Blueprint** → pick the repo. It reads
   `render.yaml`.
3. It prompts for the three `sync: false` values:
   - `APP_KEY` — from step 2
   - `APP_URL` — `https://brgy-profiling.onrender.com`
   - `DB_URL` — the **pooled** string from step 1
4. Apply. First build takes a few minutes: it compiles the PHP extensions, runs
   `composer install --no-dev`, and runs `npm ci && npm run build` for the Tailwind
   and Vite output.

Everything non-secret is already in `render.yaml` — do not set it twice in the
dashboard, the blueprint will fight you on the next sync.

If you would rather click through the UI than use the blueprint: **New → Web
Service**, connect the repo, runtime **Docker**, plan **Free**, region
**Singapore**, health check path `/up`, then add every variable from
`render.yaml` by hand.

### 5. Create the first user, and optionally seed demo data

The admin account, with no Faker involved — run it against the **direct**
endpoint:

```bash
docker compose run --rm --no-deps -T -u app \
  -e DB_CONNECTION=pgsql -e DB_URL="$DB_DIRECT" -e SKIP_DB_WAIT=true \
  --entrypoint php app artisan tinker --execute='
    \App\Models\User::create([
        "first_name" => "Admin",
        "middle_name" => null,
        "last_name"  => "Staff",
        "email"      => "you@example.com",
        "password"   => bcrypt("choose-a-real-password"),
    ]);
    echo "users=", \App\Models\User::count(), PHP_EOL;
'
```

Registration is also self-serve at `/register`, so you can create the first
account through the browser instead. Note that **every authenticated user is a
barangay administrator** — `routes/web.php` has `auth` as its only authorisation
boundary — so leaving open registration on a public URL gives anyone who signs up
full access to every resident record. Decide that deliberately.

To load the full demo data set (3,200 constituents — the 12 MB measured above),
run the seeder from the dev image, which has Faker:

```bash
docker compose run --rm --no-deps -T -u app \
  -e DB_CONNECTION=pgsql -e DB_URL="$DB_DIRECT" -e SKIP_DB_WAIT=true \
  --entrypoint sh app -c 'php artisan db:seed --force'
```

Verified against Postgres 18: 3,200 constituents, 11,337 tax records, 953
criminal records, 2.7 s. It also creates `admin@brgy.local` / `password` — **delete
that account** before the URL is public.

### 6. Verify

```bash
curl -s -o /dev/null -w '%{http_code}\n' $APP_URL/up            # 200
curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' $APP_URL/   # 302 -> /login
curl -s $APP_URL/login | grep -c 'name="_token"'                # 1
```

Then in a browser: log in, open the dashboard, and search a resident's name **in
lowercase**. That last one is the Postgres-specific behaviour from fix 3, and it
is the check most likely to catch a regression.

For reference, the same sequence against the built image locally, on Postgres:

```
POST /login        -> 302 -> /
GET  /             -> 200   14,373 B
GET  /constituents -> 200   13,456 B
GET  /profile      -> 200    9,095 B
search 'Dela Cruz' / 'dela cruz' / 'DELA CRUZ' / 'dElA cRuZ'  -> all match
sessions rows in Postgres: 5
error lines in container log: 0
```

**Expect it to feel slow, and do not chase that as a bug.** The same pages,
measured unconstrained and then again with the container held to Render's actual
Free plan:

| Page | Unconstrained | `--cpus 0.1 --memory 512m` |
|---|---|---|
| `/` (dashboard, 5 aggregate queries) | 81 ms | **1.54 s** |
| `/constituents` (15 rows + aggregates) | 44 ms | **0.54 s** |
| `/barangay-captains` | 29 ms | **0.45 s** |
| `/profile` | 23 ms | **0.45 s** |

A tenth of a CPU is the binding constraint on this plan — not memory, not the
database. Half a second per page is what Free costs; $7/month of Render Starter
is what fixes it.

### 7. Keepalive — the step people skip

Render free spins down after 15 idle minutes. Neon's compute scales to zero after
5. Neither *deletes* anything, so unlike Atlas's 30-day pause there is nothing to
manually resume — the cost of idling is purely the cold start.

If you are showing this to someone at a scheduled time, hit `/up` a few minutes
beforehand. A free cron-ping service pointed at `$APP_URL/up` every 10 minutes
keeps it warm, but it also keeps Neon's compute awake, which spends the CU-hour
allowance. Warm demo or full month of allowance — pick one. Do not point a
keepalive at `/`: that is a redirect to `/login`, which renders Blade for nothing.

---

## Cold start

First request after idle: **roughly a minute.** Render has to pull and start the
container, then the entrypoint runs `config:cache`, `route:cache` and
`view:cache` before php-fpm accepts anything. Neon adds a few hundred
milliseconds on top for its own resume.

Nothing to be done about it on a free plan; it is the cost of scale-to-zero.
Warm requests settle at the 0.45–1.5 s in step 6 — the 20–80 ms column there is
what the same code does when it is not limited to a tenth of a CPU.

---

## When it doesn't work

| Symptom | Cause |
|---|---|
| Deploy succeeds, every request 500s | Migrations were never run. `SESSION_DRIVER=database` needs the `sessions` table. Step 3. |
| `Class "Laravel\Pail\PailServiceProvider" not found` | `bootstrap/cache/*.php` reached the image. Confirm it is still in `.dockerignore`. |
| Users randomly logged out, or "signature has expired" | `APP_KEY` is not set, so each container invents one. Step 2. |
| Container healthy, connections refused | nginx is dead while php-fpm lives. Check `render logs` for `emerg`. If it is `/tmp/nginx.pid` permission denied, the build-time `rm -f /tmp/nginx.pid` was removed. |
| Health check fails but the app works in a browser | `healthCheckPath` is not `/up`, or the `location = /up` block was dropped from the vhost template. |
| `could not find driver` | `pdo_pgsql` missing — you are deploying the `app` stage, not `render`. |
| Search finds nothing for lowercase input | `orWhereLike(..., caseSensitive: false)` was reverted to plain `like`. Fix 3. |
| `migrate` hangs or errors oddly | You are migrating through the **pooled** endpoint. Use the direct one. |
| Boot hangs ~60 s then exits | `DB_URL` unset, so the entrypoint waits for the local `postgres` host, which does not exist on Render. |
| Assets 404, page unstyled | `public/build` missing — the `assets` stage failed. Check the build log for the `npm run build` step. |
| Neon writes start failing | 0.5 GB storage limit. You are at 12 MB, so suspect a runaway loop, not the data. |
| Intermittent 502s under light traffic | Container OOM-killed. The `render` stage's php-fpm overrides were dropped, so it is running the dev pool's 20 workers on 512 MB. Fix 6. |
| No way to run `artisan` on the deployed service | Correct — Free web services have no shell and no one-off jobs. Run it from your machine against Neon's direct endpoint, as in steps 3 and 5. |
| Mail silently fails if you ever add it | Free services cannot make outbound connections on ports 25, 465 or 587. Use an HTTP mail API, not SMTP. |

---

## What we did not use, and when you would add it

| Service | Add it when |
|---|---|
| **Cloudflare R2** | The app gains file uploads — resident photos, scanned IDs, document attachments. That is the moment `FILESYSTEM_DISK=s3` and the four `AWS_*` variables start mattering. The reference doc's §3 already documents the R2 wiring, including that `AWS_URL` must **not** repeat the bucket name. |
| **Brevo** | You enable password reset, or email a receipt for a tax payment. The `password_reset_tokens` table is already there. Two warnings: Brevo transactional sending is **not self-serve** (the reference doc's §4 — they have to switch it on for your account, so start days early), and Render Free blocks outbound ports 25/465/587, so you must use Brevo's **HTTP API**, not its SMTP relay. |
| **Cloudflare Pages** | Only if this app is ever split into an API plus a JS front end. Read `ai_docs/server-rendering-vs-spa.md` first; the ten-item cost list there is why the answer is currently no. |
| **MongoDB Atlas** | No plausible trigger for this data model. Constituents, taxes and criminal records are relational, with foreign keys doing real work. |
| **Cloudflare DNS** | You buy a domain. Point it at Render, proxy through Cloudflare for TLS and caching. Pages is still not involved. |

### If the free tier stops being enough

In order of what breaks first:

1. **Cold starts become unacceptable** — the barangay staff notice a minute of
   waiting. Render Starter is $7/month and never spins down. This is the first
   thing worth paying for.
2. **Neon's 100 CU-hours run out** — only if you add a keepalive or a polling
   queue worker. Fix the cause before upgrading.
3. **0.5 GB of storage** — at 12 MB for 3,200 residents, that is roughly 130,000
   residents. A barangay has a few thousand. You will never hit this.
