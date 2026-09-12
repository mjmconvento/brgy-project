# Deploy this project: click-by-click

**One path: Neon Free for PostgreSQL + Render Free for the website.** Use the names below; no extra database, Redis, static site, or domain purchase needed. The background and tradeoffs are in [deployment.md](deployment.md).

> **Demo only for now.** `/register` is public, and every registered user who clicks the verification link in their email can access all resident records. Use fake resident data. Creating your own account does not close registration; access must be restricted in the application before storing real personal, tax, or criminal-record data.

> **Email is required.** Registration sends a verification link and the site stays closed until it is clicked, so steps 6 and 7 need a Brevo API key and validated sender. You already have the Brevo account from `save_furry_friend`; the ten-minute key setup is in [email-verification.md](email-verification.md) steps 0–2. Do that before step 6.

## 1. Use these names and prepare your laptop

| Where | Enter / select |
|---|---|
| GitHub repository | `mjmconvento/brgy-project` |
| Git branch to deploy | `main` |
| Neon project name | `brgy-profiling` |
| Neon branch | Keep the default `production` (`main` if your existing project uses that name) |
| Neon database | Keep `neondb` |
| Neon database role | Keep `neondb_owner` |
| Render Blueprint name | `brgy-profiling-stack` — an organizational label, not the website address |
| Render web service name | `brgy-profiling` — already set in `render.yaml` |
| Region on both providers | **Singapore** |

The Neon project name, database name, Git branch, and Render service name **do not need to match**. Do not rename `neondb` to `brgy` just because your local database is named `brgy`.

**On your Mac:** open Docker Desktop and a terminal in this project's folder (the one containing `artisan`, `compose.yaml`, and `render.yaml`). All terminal commands below run here, **not in Neon or Render**.

If the existing local app already works, skip these two setup commands. Otherwise, prepare the PHP image and dependencies without migrating or seeding anything:

```bash
docker compose build app
docker compose run --rm --no-deps -T --entrypoint composer app install --no-interaction
```

**On GitHub:** open [this repository's main branch](https://github.com/mjmconvento/brgy-project/tree/main). Confirm the current application changes, including `render.yaml`, `Dockerfile`, and `docker/`, have been committed and pushed to `main`. Render cannot see uncommitted local files.

If they are still local, use your local `main` checkout and your editor's Git panel to review and commit the intended application changes with message `Prepare Render deployment`, then push:

```bash
git push origin main
```

Never commit `.env`, database URLs, passwords, or the app key. Do not upload your local `.env` to Render; it points at Docker-only services.

## 2. Create the Neon database

1. Open [Neon Console](https://console.neon.tech/) and sign in; create an account if needed.
2. From the projects page, click **New Project**. For a new account, use the initial project-creation form.
3. Fill in:

   | Field | Value |
   |---|---|
   | Project Name | `brgy-profiling` |
   | Postgres version | `18` |
   | Cloud service provider | `AWS` |
   | Region | **Singapore** / `ap-southeast-1` |
   | Plan, if asked | **Free** |

4. Click **Create Project**. You arrive at the project's dashboard.
5. Keep the generated `production` branch, `neondb` database, and `neondb_owner` role. No extra branch, database, or user is required. Do not enable Neon Auth; this app already handles login.

**Already created this project?** Open it instead of creating a second one. Keep its existing branch/database/role names and select those consistently in the next step. Check the region before creating a project; it cannot be changed in place.

## 3. Find and save BOTH database URLs

These are **two ways to connect to the same database**, not two databases you need to create.

1. In Neon, open **brgy-profiling → Project Dashboard → Connect**.
2. The **Connect to your database** popup opens. Select:
   - **Branch:** `production` (or the existing default branch from step 2).
   - **Compute:** the branch's primary/read-write compute, not a read replica.
   - **Database:** `neondb`.
   - **Role:** `neondb_owner`.
3. Find the **Connection pooling** toggle in this popup.
4. Switch it **ON**. Copy the connection string and save it in your password manager under **Brgy — Neon POOLED**.
5. Switch it **OFF**. Copy the connection string again and save it under **Brgy — Neon DIRECT**.

| Connection pooling toggle | Check the hostname | Where you will use it |
|---|---|---|
| **ON** → pooled URL | Contains `-pooler` | Render's `DB_URL` field |
| **OFF** → direct URL | Does **not** contain `-pooler` | Laptop's `DB_DIRECT` variable, for migrations |

The toggle only changes the URL displayed. Switching it off does **not** disable pooling for an app using the pooled URL.

**Recognize the shape — these are examples, not credentials to paste:**

```text
POOLED: postgresql://neondb_owner:PASSWORD@ep-EXAMPLE-pooler.ap-southeast-1.aws.neon.tech/neondb?sslmode=require
DIRECT: postgresql://neondb_owner:PASSWORD@ep-EXAMPLE.ap-southeast-1.aws.neon.tech/neondb?sslmode=require
```

Copy your **whole real URL**, beginning with `postgresql://` (or `postgres://`). If Neon shows `psql 'postgresql://...'`, copy only the URL inside the quotes — no `psql`, quotes, or `DATABASE_URL=` prefix. Use the version containing the actual password, not a masked display.

Keep the query parameters Neon supplies, including `sslmode=require` and any `&channel_binding=require`. **Do not append a second `?sslmode=require`.** Do not hand-edit the generated hostname or password. Both URLs are secrets; save them outside this repository.

## 4. Generate the Laravel app key

**On your laptop**, run:

```bash
docker compose run --rm --no-deps -T \
  -e APP_CONFIG_CACHE=/tmp/brgy-deploy-config.php \
  --entrypoint php app artisan key:generate --show
```

Copy only the output line starting with `base64:`. Save it in your password manager as **Brgy — APP_KEY**. Include the `base64:` prefix and any trailing `=`.

`--show` does not replace your local `.env` key. Generate the deployment key once; reuse it for later deploys. Never paste it into GitHub or `render.yaml`.

## 5. Create the tables in Neon BEFORE deploying

1. Go back to **Neon → brgy-profiling → Connect**.
2. Select the same branch/database/role as step 3; set **Connection pooling OFF**.
3. Copy the **direct URL only** to your clipboard again. This matters because you just copied an app key.
4. Back in your Mac terminal, run these blocks in the **same tab**:

```bash
DB_DIRECT="$(pbpaste)"
```

`pbpaste` reads your Mac clipboard, so the URL is not typed into shell history. `DB_DIRECT` exists only in this terminal session; **do not put it in `.env` or change your local database settings**.

```bash
docker compose run --rm --no-deps -T \
  -e APP_ENV=production \
  -e APP_CONFIG_CACHE=/tmp/brgy-deploy-config.php \
  -e DB_CONNECTION=pgsql \
  -e DB_URL="${DB_DIRECT:?Copy the direct Neon URL and run the pbpaste command first}" \
  --entrypoint php app artisan migrate --force --no-interaction
```

**Success:** migrations end in `DONE`, or `Nothing to migrate` if already applied. Do not continue after an error. The required-value check prevents an empty `DB_DIRECT` from silently targeting your local database; the temporary config-cache path bypasses any cached local settings without deleting them.

**Confirm in Neon:** left sidebar → **Postgres database → SQL Editor** (or **SQL Editor** in the older sidebar). Select the same branch and `neondb`. Replace the sample SQL with this and click **Run**:

```sql
SELECT tablename
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY tablename;
```

You should see `users`, `sessions`, `constituents`, `barangay_captains`, `taxes`, and `criminal_records`, along with Laravel's other tables. Do not manually create these tables in the Neon UI.

**Never run `make fresh`, `migrate:fresh`, or the test suite against Neon.** They can delete data. Your laptop's local database is never copied automatically: without the optional block below, the new site starts empty.

### Optional: load the demo data set

Recommended for a demo. In the **same terminal tab** where `DB_DIRECT` is set (rerun the `pbpaste` block from above if you opened a new one), run this **once**:

```bash
docker compose run --rm --no-deps -T \
  -e APP_ENV=production \
  -e APP_CONFIG_CACHE=/tmp/brgy-deploy-config.php \
  -e DB_CONNECTION=pgsql \
  -e DB_URL="${DB_DIRECT:?Copy the direct Neon URL and run the pbpaste command first}" \
  --entrypoint php app artisan db:seed --force --no-interaction
```

It runs from your laptop's development image because the production image deliberately has no data generator. **Success:** progress bars for captains, constituents, taxes, and criminal records, then a `Database\Seeders\BarangaySeeder ... DONE` line. Expect well under a minute.

What it writes, all fake: 60 barangay captains, 3,200 residents, roughly 11,000 tax records, roughly 950 criminal records (about 12 MB of Neon's 0.5 GB), and two demo logins:

| Email | Password |
|---|---|
| `admin@brgy.local` | `password` |
| `test1@user.com` | `password112233` |

Running it a second time stops at once with a duplicate-email error for `admin@brgy.local` and adds nothing — it cannot double the data, and it does not reset anything either. Those passwords are published in this repository, which is fine for a throwaway demo. When you want the demo logins gone, run this in the Neon **SQL Editor** from above:

```sql
DELETE FROM users WHERE email IN ('admin@brgy.local', 'test1@user.com');
```

## 6. Create the Render website using the Blueprint

1. Open [Render Dashboard](https://dashboard.render.com/) and sign in.
2. Click **New → Blueprint** — not Static Site, Postgres, or Key Value.
3. Connect your GitHub account if prompted. Allow Render access to **mjmconvento/brgy-project**, then click **Connect** beside that repository. If it is missing, grant the Render GitHub app access to this repo and return to the list.
4. In the Blueprint form, use:

   | Field | Value |
   |---|---|
   | Blueprint Name | `brgy-profiling-stack` |
   | Branch | `main` |
   | Blueprint Path | `render.yaml` |

5. Check the resource preview: **one web service**, named `brgy-profiling`, runtime **Docker**, plan **Free**, region **Singapore**. These come from the repository's `render.yaml`; no manual build or start command is needed.
6. Fill the five prompted environment variables:

   | Key | Exactly what to paste into its Value field |
   |---|---|
   | `APP_KEY` | The full `base64:...` value saved in step 4 |
   | `APP_URL` | `https://brgy-profiling.onrender.com` initially; verify the actual URL in step 7 |
   | `DB_URL` | The **POOLED** Neon URL saved in step 3 — hostname contains `-pooler` |
   | `BREVO_API_KEY` | The `xkeysib-...` **API** key from [email-verification.md](email-verification.md) step 1 — not the SMTP key |
   | `MAIL_FROM_ADDRESS` | Your Gmail address exactly as it appears under **Verified** at [app.brevo.com/senders](https://app.brevo.com/senders) |

   Paste values only, with **no surrounding quotes**. The key is **`DB_URL`**, not `DATABASE_URL`. Do not add `DB_DIRECT` or separate `DB_HOST`, `DB_USERNAME`, and `DB_PASSWORD` fields.
7. Click **Deploy Blueprint**. Open the created web service, then its **Events/Deploys** page to watch the build. Wait for the deployment to show **Live**. Use **Logs** for runtime errors.

The Blueprint already sets `DB_CONNECTION=pgsql`, `SESSION_DRIVER=database`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=brevo`, `MAIL_FROM_NAME`, and **`RUN_MIGRATIONS=false`**. Leave them alone. You ran migrations from your laptop because Render Free has no shell or one-off jobs; the direct connection is also the recommended path for schema changes.

## 7. Copy the ACTUAL website URL and create your account

Render assigns a unique `onrender.com` address. It may add a suffix, so **do not assume the address is exactly the example above**.

1. In Render, open the **web service** `brgy-profiling`, not the Blueprint overview.
2. Copy the public `https://...onrender.com` link shown on the service page.
3. Open **Environment → Environment Variables**. Find `APP_URL` and edit its value to that exact address, with **no trailing slash and no `/login`**.
4. Choose **Save and deploy**. Do not choose **Save only**; the running container needs the new value. If the value already matches, no change or redeploy is needed.
5. Once Live, open the actual address with `/register` appended. If you loaded the demo data, you can instead open `/login` and sign in as `admin@brgy.local` / `password` — the seeded accounts are already verified — then skip to item 9.
6. Enter your first and last name, optional middle name, your email in lowercase, and a unique password with its confirmation. Click **Register**.
7. You land on **Check your email**. Open the link in the email from `…@…t-sender-sib.com` (subject **Verify your email address**; check spam once if it takes more than a minute). No email? Work through [email-verification.md → Troubleshooting](email-verification.md#troubleshooting).
8. Clicking **Verify Email Address** signs you in and shows the dashboard with a green **Your email address has been verified.** banner.
9. With the demo data the dashboard shows 3,200 residents and populated charts; without it, zero residents/taxes/records are expected. Either way, your laptop's own local accounts do not exist in this database.

**Reminder:** other people can also register. This walkthrough does not secure public sign-up. Do not enter real resident information yet.

## 8. Verify the website, not just the deploy status

1. Open the actual website address with **`/up`** appended. It should show Laravel's healthy application page. This alone does **not** prove a database query works.
2. Log out and log back in. Open **Constituents → Add Constituent**.
3. Enter this fake record:

   | Field | Value |
   |---|---|
   | First name | `Demo` |
   | Last name | `Sample` |
   | Street | `Test Street` |
   | Barangay | `Test Barangay` |
   | City or municipality | `Test City` |
   | Country | `Philippines` |
   | Middle name / House number | Leave blank |
   | Voted barangay captain | Leave **None** |

4. Click **Save Constituent**. Expect the profile page and `Constituent added.`
5. Return to **Constituents**, search for **`sample`** in lowercase, and confirm **Sample, Demo** appears. The name is stored capitalised, so this also proves the case-insensitive search works on Neon.
6. Delete that fake record and confirm the deletion. This exercises a real database write, read/search, and delete.

You are done when login and this round trip work. Free Render services sleep after 15 idle minutes; the next visit can take about a minute. Slow wake-up is not a reason to recreate the database or generate another app key.

After setup, clear the temporary terminal variable:

```bash
unset DB_DIRECT
```

For future schema changes, repeat step 5 using the new code **before deploying code that needs those tables**. Use `migrate`, never `migrate:fresh`; keep the same Neon database and `APP_KEY`. The first such change is already here: `2026_09_12_000000_mark_existing_users_as_verified` marks every account that registered before email verification existed as verified, so run it before deploying that code or those accounts will be asked to verify — see [email-verification.md](email-verification.md) step 3.

## If you get stuck

| Symptom | Next action |
|---|---|
| Cannot connect to Docker daemon | Open Docker Desktop, wait until it is running, then retry the command. |
| `vendor/autoload.php` missing | Run the Composer command in step 1 from the project folder. |
| Migration says the URL is invalid, or shows a local Docker hostname | Recopy the **raw DIRECT URL**, not a `psql` command or app key. Rerun both terminal blocks in step 5. |
| Password authentication failed | Reopen Neon's Connect popup and copy the full URL for the selected role. If the password changed, replace both saved URLs and Render's `DB_URL`, then **Save and deploy**. |
| `relation "sessions" does not exist` | Run step 5. Ensure the migration URL and Render's URL use the **same Neon branch and database**. |
| Render is Live but the app returns 500 | Web service → **Logs**. Check `APP_KEY`, `DB_URL`, and the migration result; Live/`/up` alone does not verify the database. |
| Login sends you to the wrong hostname | Repeat step 7 with the address Render actually assigned. |
| Dashboard is empty | You skipped the optional demo data in step 5. Run that block once; the site never copies your laptop's local database. |
| Seeder stops with `duplicate key ... users_email_unique` | The demo data is already loaded; nothing was added. If you had registered `admin@brgy.local` yourself before seeding, the bulk data was never loaded — register with a different email, then run the seeder again. |
| App fails just after editing an environment variable | Confirm you used **Save and deploy**, not **Save only**, and wait for Live. |
| After registering, "Check your email" but nothing arrives | `BREVO_API_KEY` or `MAIL_FROM_ADDRESS` is wrong or missing — Render → **Logs** shows the transport error. Fix, **Save and deploy**, then press **Resend verification email**. Escape hatch: `php artisan email:verification-link`, per [email-verification.md](email-verification.md). |
| Signed in but every page returns to "Check your email" | Expected until the link is clicked. If this is an account that existed before email verification, run the `2026_09_12` migration (step 5) or press **Resend**. |

## Provider references

These back the dashboard locations and settings above; you do not need to read them to follow the steps.

- [Neon: create a project and its default resources](https://neon.com/docs/manage/projects)
- [Neon: Connect popup and connection strings](https://neon.com/docs/connect/connect-from-any-app)
- [Neon: pooling toggle and direct connections for migrations](https://neon.com/docs/connect/connection-pooling)
- [Neon: SQL Editor navigation](https://neon.com/docs/get-started/signing-up)
- [Render: Blueprint creation](https://render.com/docs/infrastructure-as-code)
- [Render: environment variables and Save and deploy](https://render.com/docs/configure-environment-variables)
- [Render: assigned website URLs](https://render.com/docs/web-services)
- [Render: Free plan limitations](https://render.com/docs/free)
