---
name: deploy
description: Ship the Barangay Profiling System to production (Render web service, Neon Postgres, Brevo email).
disable-model-invocation: true
---

# Deploy

One Render web service built from the Dockerfile's final `render` stage (nginx + php-fpm under
supervisord on `$PORT`), Neon for Postgres, Brevo's HTTPS API for mail. Confirm with the user
before each production step.

## 0. Preconditions

- The `verify` skill passes and `main` is pushed.
- `render` is still the **last** stage of the Dockerfile (Render builds the final stage).

## 1. Migrate Neon first

`RUN_MIGRATIONS=false` on purpose: the service's `DB_URL` is Neon's pooled (PgBouncer, transaction
mode) endpoint, which breaks `artisan migrate`. Run pending migrations against the **direct**
endpoint before the new code goes live, e.g. from the local container:

```bash
docker compose exec -u app -e DB_URL='<neon direct connection string>?sslmode=require' app php artisan migrate --force
```

Skipping this on the first deploy makes every page 500: `SESSION_DRIVER=database` needs the
`sessions` table.

## 2. Deploy

Push to `main` (or trigger a manual deploy in Render). Secrets live in Render, never in
`render.yaml`: `APP_KEY` (must stay stable, or sessions and signed links break on every deploy),
`APP_URL`, `DB_URL`, `BREVO_API_KEY`, `MAIL_FROM_ADDRESS` (exactly the sender validated in Brevo).

## 3. Smoke test

- `curl -fsS https://<service>.onrender.com/up` (Laravel's health route through php-fpm).
- Sign in, open the dashboard and a constituent profile.
- Register a throwaway account and confirm the verification email arrives (Brevo), then delete it.

The free plan sleeps when idle; the first request can take about a minute.
