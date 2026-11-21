---
name: verify
description: Run this project's checks (Pint style check, Larastan level 6, the Pest suite on PostgreSQL and on SQLite, the Vite build) and report what passed and failed. Use after code changes, before committing, or when asked to run the tests.
---

# Verify

The stack must be running (`make up`). Run from the repository root.

| Check | Command | Notes |
| --- | --- | --- |
| Style | `docker compose run --rm --no-deps --entrypoint sh app -c './vendor/bin/pint --test'` | `make lint` fixes instead of checking |
| Static analysis | `make analyse` | Larastan level 6 over `app`, `database`, `routes` |
| Tests (PostgreSQL) | `make test` | Pest against `brgy_testing`; the only safe way to run the suite in Docker |
| Tests (SQLite) | see below | Proves no engine-specific SQL crept in |
| Front-end build | `make assets` | Only when `resources/css` or `resources/js` changed |

SQLite portability run (the command the README documents):

```bash
docker compose run --rm --no-deps -T -u app -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_STORE=array \
  -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync -e BCRYPT_ROUNDS=4 \
  --entrypoint sh app -c 'php artisan test'
```

To run one test while iterating, pass the same variables `make test` sets:

```bash
docker compose exec -u app -e APP_ENV=testing -e DB_DATABASE=brgy_testing -e CACHE_STORE=array \
  -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array \
  -e BROADCAST_CONNECTION=null -e BCRYPT_ROUNDS=4 app php artisan test --filter=<name>
```

If `make test` cannot connect, `brgy_testing` may be missing (it is created on Postgres's first
initialisation only):
`docker compose exec postgres psql -U brgy -d brgy -c 'CREATE DATABASE brgy_testing OWNER brgy;'`

## Report

One line per check: command, pass or fail, and for a failure the first failing test or error
verbatim. Say which checks were skipped and why. Never report a check as passing without running it.
