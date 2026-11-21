# Barangay Profiling System

Records system for a Philippine barangay: staff sign in and maintain resident (constituent)
profiles, barangay captain candidates, monthly tax records and criminal records, with a
dashboard of charts. A 2015 Laravel 5.1 prototype rebuilt on Laravel 13 / PHP 8.5 /
PostgreSQL 18 / Tailwind 4 + Alpine 3 + Chart.js, all in Docker. `README.md` is thorough;
read the relevant section before larger changes.

## Running it

Docker only; nothing is needed on the host. `make` lists every target.

```bash
make setup     # first run: .env, build, install, up, key, migrate --seed, assets
make up        # start (includes Vite dev server and Mailpit)
make shell     # bash in the app container as the `app` user
```

- App http://localhost:8000 (`admin@brgy.local` / `password`), Mailpit http://localhost:8025.
- Artisan: `docker compose exec -u app app php artisan …`.
- Services: `app` (php-fpm), `nginx`, `postgres`, `redis`, `queue`, `scheduler`, plus `node` and `mailpit` in the `dev` profile.

## Tests: always `make test`

`make test` runs Pest against the `brgy_testing` database. **Never run `php artisan test`,
`pest` or `phpunit` directly in the container**: compose exports `DB_DATABASE=brgy` into
`$_SERVER`, which beats `phpunit.xml`'s `force="true"`, so `RefreshDatabase` would wipe the
development database. `Tests\TestCase` refuses to boot against anything but `brgy_testing`
or `:memory:`, and a PreToolUse hook blocks the bare command.

The suite also passes on SQLite in memory; keep it that way (no engine-specific SQL). The
`verify` skill has the command.

## Domain model

`barangay_captains` 1─* `constituents` (nullable FK, `ON DELETE SET NULL`);
`constituents` 1─* `taxes` and `criminal_records` (`ON DELETE CASCADE`).
`taxes_period_unique` makes one tax record per constituent per month.

- **Derived, never stored.** `has_unpaid_taxes`, `has_criminal_record` and
  `outstanding_tax_total` are accessors on `Constituent` that prefer a loaded relation, then an
  eager aggregate, and only then query. The listing uses `withListingAggregates()`.
- **Structured addresses.** `house_number` (optional), `street`, `barangay`, `city`, `country`
  via `App\Models\Concerns\HasAddress`; names via `HasPersonName` (`full_name`, search, ordering).
- **Money** is `decimal(12,2)` cast with `decimal:2`; never float arithmetic for amounts.
- **Status** is the `App\Enums\TaxStatus` enum (value, label, badge style); validate with `Rule::enum`.
- **Dashboard** reads from `App\Queries\DashboardMetrics`: grouped aggregates only.

## Performance budgets (tests enforce them)

- Constituent listing: exactly **2 queries** per page (`DerivedConstituentStateTest`).
- Dashboard: at most **12 queries**, independent of row count (`DashboardTest`).

## Conventions

- Routes: named `Route::resource`, nested children `->shallow()->except(['index', 'show'])`.
  Everything except login, registration and email verification is in the `['auth', 'verified']`
  group, the only authorization boundary (every verified user is an administrator).
- Controllers are thin and typed (`View` / `RedirectResponse`), use route-model binding, and pass
  only `$request->validated()` to `create`/`update`.
- Validation lives in FormRequests; shared address rules in `Http/Requests/Concerns/ValidatesAddress`.
  Database integrity rules get a matching validation message (see `TaxRequest`).
- Models: Laravel 13 `#[Fillable([...])]` attribute, `casts()` method, `#[Scope]` attribute
  (not `scopeX` methods), typed relations with generic PHPDoc.
- Views: Blade components `<x-layouts.app>`, `<x-form.*>`, `<x-ui.*>`; create and edit share a
  `_form.blade.php` partial; charts use `<x-ui.chart>` (no inline chart JS).
- Plural snake_case tables, `_at` timestamps, no `$table` overrides.
- Style: Pint (`pint.json`, Laravel preset). Static analysis: Larastan level 6 over `app`,
  `database`, `routes` (tests excluded on purpose).

## Production

Render (`render.yaml`, final Dockerfile stage `render`: nginx + php-fpm under supervisord) with
Neon Postgres and Brevo mail. **Migrations do not run on deploy** (`RUN_MIGRATIONS=false`,
because the app uses Neon's pooled endpoint): run them by hand against the direct endpoint
first. See the `deploy` skill.

## Claude Code setup in this repo

| Kind | Name | What it does |
| --- | --- | --- |
| Skill | `verify` | Pint check, Larastan, the Pest suite on Postgres and on SQLite; reports results. Claude runs it before commits |
| Skill | `add-crud-resource` | Builds a new resource end to end the way the existing four are built |
| Skill | `schema-change` | Adds or changes columns without breaking integrity, derived state or seed data |
| Skill | `deploy` | Manual only (`/deploy`): migrate Neon, then ship to Render |
| Agent | `laravel-reviewer` | Reviews a diff against this codebase's Laravel conventions and security rules |
| Agent | `query-performance-auditor` | Hunts N+1 queries and checks the query budgets and indexes |
| Agent | `dependency-upgrader` | Triages Dependabot branches and reports what is safe to merge |
| Hook | `SessionStart` | Reports the branch, Dependabot branches, stack status, missing `.env`/`vendor`, stale `public/hot` |
| Hook | `PreToolUse` | Blocks bare test runs, edits to generated files, lockfiles and committed migrations; asks before wiping the database |
| Hook | `PostToolUse` | Formats edited PHP with Pint; flags model, migration, routing and query-budget follow-ups |

Hooks are Python 3 scripts in `.claude/hooks/` (standard library only).
