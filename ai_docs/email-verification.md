# Email verification on registration

Written 2026-09-12. Registration now emails a verification link and the
application stays closed until it is clicked. Mail leaves the deployed app
through **Brevo's HTTPS API**, the same provider and the same wiring pattern as
`save_furry_friend/ai_docs/deployment.md` §4 — every Brevo claim below is
carried over from that document (verified there on 2026-08-13) and re-checked
against this repo by running it.

This document is in two halves. **What the code does** is already done and
tested. **What you have to do** is the Brevo and Render clicking that no code
can do for you — about fifteen minutes, because the hard part (activating
transactional sending) happened when you set up the previous project.

---

## Contents

- [What happens now](#what-happens-now)
- [What changed in the repo](#what-changed-in-the-repo)
- [What you have to do](#what-you-have-to-do)
  - [0. Reuse the Brevo account you already have](#0-reuse-the-brevo-account-you-already-have)
  - [1. Create a project-specific API key](#1-create-a-project-specific-api-key)
  - [2. Prove the key with curl](#2-prove-the-key-with-curl)
  - [3. Run the new migration against Neon, before deploying](#3-run-the-new-migration-against-neon-before-deploying)
  - [4. Set the two secrets on Render, before deploying](#4-set-the-two-secrets-on-render-before-deploying)
  - [5. Push and deploy](#5-push-and-deploy)
  - [6. Test on the live site](#6-test-on-the-live-site)
- [Local development](#local-development)
- [When the email does not arrive](#when-the-email-does-not-arrive)
- [Troubleshooting](#troubleshooting)
- [Limits worth knowing](#limits-worth-knowing)

---

## What happens now

```mermaid
sequenceDiagram
    participant B as Browser
    participant A as App
    participant M as Mailer (Brevo API / Mailpit)
    B->>A: POST /register
    A->>A: User::create, event(Registered)
    A->>M: VerifyEmail notification (signed URL, 60 min)
    A-->>B: 302 /verify-email  "Check your email"
    B->>A: GET / (or any gated page)
    A-->>B: 302 /verify-email  (verified middleware)
    Note over B: opens the email
    B->>A: GET /verify-email/{id}/{hash}?expires&signature
    A->>A: signed + hash checked, email_verified_at = now()
    A-->>B: 302 /  "Your email address has been verified."
```

What an account can and cannot do before the link is clicked:

| Reachable while unverified | Closed until verified |
|---|---|
| `/verify-email` — the "check your inbox" page, with a **Resend** button | Dashboard `/` |
| The link itself, `/verify-email/{id}/{hash}` | Constituents, taxes, criminal records |
| `/profile` — so a mistyped address can be corrected | Barangay captains |
| Log out | |

Three rules that fall out of this:

- **Changing your email on the profile page un-verifies you.** The flag belongs
  to the address it was earned for. A link goes to the new address at once and
  the gate closes until it is clicked.
- **Opening the link while signed out works.** The `auth` middleware sends you
  to the login form, and after signing in you land back on the link, which then
  verifies you. This is the common case — the email is usually opened on a
  phone, not in the tab that registered.
- **Existing accounts were grandfathered.** A migration marks every account with
  no `email_verified_at` as verified at the moment it runs. Nobody who
  registered before this change is locked out. Seeded demo accounts were already
  verified — `UserFactory` sets `email_verified_at => now()`.

---

## What changed in the repo

You do not need to write any code. Committed already:

| File | What it does |
|---|---|
| `composer.json` | `symfony/brevo-mailer` + `symfony/http-client` (both `^8.1`, matching the framework's `symfony/mailer` 8.1) |
| `config/services.php` | `brevo.key` reads `BREVO_API_KEY` |
| `config/mail.php` | a `brevo` mailer entry |
| `app/Providers/AppServiceProvider.php` | registers the transport via `Mail::extend('brevo', …)` — scheme `brevo+api`, a `Dsn` object, 10 s HTTP timeout |
| `app/Models/User.php` | `implements MustVerifyEmail` — this alone makes the framework send the link on `Registered` |
| `routes/web.php` | the three `verification.*` routes; dashboard and every resource moved behind `verified` |
| `app/Http/Controllers/Auth/EmailVerificationController.php` | notice page, the signed-link handler, resend |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | lands a new account on `/verify-email` instead of the dashboard |
| `app/Http/Controllers/ProfileController.php` | clears `email_verified_at` and re-sends when the address changes |
| `resources/views/auth/verify-email.blade.php` | the "Check your email" page — resend, fix-address link, log out |
| `database/migrations/2026_09_12_000000_mark_existing_users_as_verified.php` | grandfathers existing accounts |
| `app/Console/Commands/ShowVerificationLink.php` | `php artisan email:verification-link {email}` — prints the signed link when the email does not arrive |
| `render.yaml` | `MAIL_MAILER=brevo`, `MAIL_FROM_NAME`, and two new prompted secrets |
| `.env.example` | documents the mail block and `BREVO_API_KEY` |
| `tests/Feature/EmailVerificationTest.php`, `AuthenticationTest.php`, `ProfileTest.php` | the contract above, 71 tests green on Postgres-compatible SQLite |

Two details in the provider that are easy to get wrong if you ever touch it —
both inherited from the previous project, both real failures there:

- The DSN scheme is **`brevo+api`**. A bare `brevo` is accepted by the factory
  but routes to the **SMTP** transport on port 465 — blocked on Render — and
  then fails with `Password is not set`, an error that says nothing about the
  real mistake.
- It builds a `Dsn` object rather than a DSN string, because the string form
  goes through `parse_url()` and an API key containing `/`, `:` or `@` silently
  mis-parses.

---

## What you have to do

### 0. Reuse the Brevo account you already have

Everything that took days for `save_furry_friend` is already done on that
account and applies to every project sending through it:

| Done once, per account | Where to confirm |
|---|---|
| Transactional sending activated (the support-ticket step) | Any successful send from the previous project proves it |
| A validated Gmail sender | <https://app.brevo.com/senders> — your address shows **Verified**. The permanent orange DKIM/DMARC warning is expected and does not block sending |
| API-key IP blocking **deactivated** | <https://app.brevo.com/security/authorised_ips> — confirm it is still off. If it armed itself, every send 401s about a month after the last redeploy |

**Reuse the sender.** Adding a second sender means another 6-digit OTP to the
same inbox for no gain, and the recipient sees `you@<account>.t-sender-sib.com`
either way — Brevo rewrites the From header for unauthenticated senders. That
rewrite is what lets a Gmail sender deliver to strangers with no domain: it
produces a DMARC-aligned message where a literal Gmail From would not.

**Do not reuse the API key.** Keys are free and per-project keys can be
rotated or revoked independently — leak one and only one deploy has to change.

### 1. Create a project-specific API key

1. <https://app.brevo.com/settings/keys/api> → **Generate a new API key**.
2. Name: `brgy-profiling-render`.
3. Expiry: **no expiration**. A 1-year expiry on a hobby project is a silent
   time bomb.
4. **Do not enable "Create MCP server API key"** — Brevo's docs say doing so
   *deactivates the key you just made* and issues a different one.
5. Copy it now. *"Your API key is only visible during this step."* It starts
   with `xkeysib-`.

It is the **API key**, from the API keys tab. The SMTP key on the neighbouring
tab is a different secret for a different protocol and cannot work here:

| | API key | SMTP key |
|---|---|---|
| Used by | `api.brevo.com/v3/smtp/email` — what this app calls | `smtp-relay.brevo.com` — port 465, blocked on Render |
| Sent as | an `api-key:` request header | an SMTP password |
| Prefix | `xkeysib-` | — |

> The key grants **full access to your Brevo account**. Paste it into Render's
> environment, never into git, never into `.env.example`.

### 2. Prove the key with curl

Independent of Laravel, so a failure here is a Brevo problem and a success here
means any later failure is in the app or its environment:

```bash
export BREVO_API_KEY=xkeysib-...
export MAIL_FROM_ADDRESS=your.validated@gmail.com     # exactly the sender in step 0

curl -i -X POST https://api.brevo.com/v3/smtp/email \
  -H "api-key: $BREVO_API_KEY" \
  -H 'content-type: application/json' \
  -d "{\"sender\":{\"email\":\"$MAIL_FROM_ADDRESS\",\"name\":\"Barangay Profiling System\"},
       \"to\":[{\"email\":\"$MAIL_FROM_ADDRESS\"}],
       \"subject\":\"brgy probe\",\"htmlContent\":\"<p>probe</p>\"}"
```

| Response | Meaning |
|---|---|
| `201` with a `messageId` | Success. The probe lands in your inbox in under a minute |
| `401` | Wrong key — most often the SMTP key, or a key from a different account |
| `400 … sender not valid` | `MAIL_FROM_ADDRESS` is not exactly a validated sender |
| `402` / `account_under_validation` | Transactional sending is not activated on this account — you are on the wrong Brevo account |

### 3. Run the new migration against Neon, before deploying

Same procedure as every migration in
[deployment-step-by-step.md §5](deployment-step-by-step.md#5-create-the-tables-in-neon-before-deploying):
the **direct** endpoint, from your laptop, because Render Free has no shell and
the pooled endpoint drops what `migrate` relies on.

```bash
export DB_DIRECT='postgresql://USER:PASS@ep-xxx.ap-southeast-1.aws.neon.tech/neondb?sslmode=require'

docker compose run --rm --no-deps -T -u app \
  -e DB_CONNECTION=pgsql -e DB_URL="$DB_DIRECT" -e SKIP_DB_WAIT=true \
  --entrypoint sh app -c 'php artisan migrate --force'
```

Expected output ends with:

```
2026_09_12_000000_mark_existing_users_as_verified .. DONE
```

**Before** the deploy, not after: the moment the new code is live, every
account with `email_verified_at = NULL` is sent to the "check your email" page,
and the accounts you registered through the browser are exactly those rows.
Running the migration first means nobody notices the change. (If you run it
after, the only cost is that those accounts see the notice page and click
**Resend** once — nothing is lost.)

No Docker Desktop? The host toolchain works too:

```bash
DB_CONNECTION=pgsql DB_URL="$DB_DIRECT" php artisan migrate --force
```

Verified locally: one unverified row → after the migration, zero, with
`email_verified_at` set to the migration's wall-clock time. Re-running it is a
no-op — it only touches NULLs.

### 4. Set the two secrets on Render, before deploying

`render.yaml` now sets `MAIL_MAILER=brevo` and `MAIL_FROM_NAME` itself and
declares two more prompted (`sync: false`) values. Because the blueprint changed,
Render will ask for them on the next Blueprint sync — but do not wait for that.
Add them by hand first, so they exist before the code that needs them boots:

1. Render → web service **brgy-profiling** → **Environment**.
2. Add:

   | Key | Value |
   |---|---|
   | `BREVO_API_KEY` | the `xkeysib-…` key from step 1, no quotes |
   | `MAIL_FROM_ADDRESS` | your validated Gmail address, exactly as shown at <https://app.brevo.com/senders>, no quotes |

3. **Save, rebuild, and deploy** (or **Save and deploy** — whichever the button
   says; not **Save only**).

Order matters here, and it is worth understanding why. With `MAIL_MAILER=brevo`
and no key, the transport constructor fails loudly (`services.brevo.key` is not
a string) at the moment a registration tries to send. That request 500s **after
the account row was created and before the person was signed in** — the address
is now "taken" and its owner never saw a link. Loud is the right failure for a
missing secret, but set the secret first and it never happens.

`MAIL_FROM_ADDRESS` is not cosmetic. The transport derives Brevo's `sender`
field from the message's From header, which Laravel fills from this value. Not
exactly a validated sender → `400 sender not valid` on every send.

`MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` are
read only by the `smtp` mailer and are inert once `MAIL_MAILER=brevo`. Do not
add them on Render.

### 5. Push and deploy

```bash
git push origin main
```

Render builds on push. Watch **Events** until **Live**. The build installs the
two new Composer packages from the lock file; nothing else in the image changed.

If instead you re-sync the Blueprint, Render prompts for `BREVO_API_KEY` and
`MAIL_FROM_ADDRESS` — the same two values — and the manual entries from step 4
are kept.

### 6. Test on the live site

1. Open `$APP_URL/register` in a private window and register with an address
   you can read **that is not the sender address** — Gmail collapses
   self-addressed mail into the sent folder.
2. Expect the **Check your email** page. Open `$APP_URL/` in the same window —
   it bounces straight back. That is the gate.
3. The email arrives from `you@<account>.t-sender-sib.com`, subject **Verify
   your email address**, with a "Sent with Brevo" footer the free plan cannot
   remove. Give it a minute and check spam once.
4. Click **Verify Email Address**. Expect the dashboard with a green **Your
   email address has been verified.** banner.
5. Brevo → **Transactional → Logs** shows the send with its `messageId`. A
   `201` there means Brevo *accepted* the message, not that it landed —
   spam-foldering is invisible to the app.
6. Sign out, sign in as your original account. No notice page: it was
   grandfathered in step 3.

Then delete the test account from Neon's SQL Editor if you like:

```sql
DELETE FROM users WHERE email = 'the-test-address@example.com';
```

---

## Local development

Nothing to configure. `.env.example` keeps `MAIL_MAILER=smtp` pointed at the
**Mailpit** container, so every verification email shows up at
<http://localhost:8025> with a clickable link. Register at
<http://localhost:8000/register>, open Mailpit, click the button.

To exercise the real Brevo transport from your laptop — for example to confirm
a new key before touching Render — override the mailer for one command:

```bash
docker compose exec -T \
  -e MAIL_MAILER=brevo -e BREVO_API_KEY=xkeysib-... \
  -e MAIL_FROM_ADDRESS=your.validated@gmail.com \
  app php artisan tinker --execute='
    App\Models\User::where("email", "test1@user.com")->firstOrFail()
        ->sendEmailVerificationNotification();
    echo "sent", PHP_EOL;'
```

The link it sends points at your local `APP_URL`, so it only *verifies* anything
locally — but the send itself is the real thing and appears in Brevo's logs.

The test suite never sends mail: `phpunit.xml` and `make test` set
`MAIL_MAILER=array`, and the verification tests use `Notification::fake()`.

---

## When the email does not arrive

The escape hatch, in the repo since the previous project:

```bash
php artisan email:verification-link someone@example.com
```

It prints the same temporary signed URL the email carries, valid for the same
60 minutes (`auth.verification.expire`). Paste it into a browser signed in as
that account, or send it to the person by any other channel.

For an account on the **deployed** site, run it from your laptop with the
production key and URL — the signature depends on both — against Neon's direct
endpoint:

```bash
docker compose run --rm --no-deps -T -u app \
  -e APP_KEY='base64:...the production key...' \
  -e APP_URL=https://brgy-profiling.onrender.com \
  -e DB_CONNECTION=pgsql -e DB_URL="$DB_DIRECT" -e SKIP_DB_WAIT=true \
  --entrypoint php app artisan email:verification-link someone@example.com
```

It grants nothing that access to `APP_KEY` does not already grant. Treat the
printed link like the password reset it effectively is.

If mail is down for everyone — Brevo outage, key revoked, cap exceeded — the
same tool unblocks each affected account in turn, and Render's logs
(`LOG_CHANNEL=stderr`) carry the transport's error message.

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| Registration 500s; the address then says "already taken" | `MAIL_MAILER=brevo` with no `BREVO_API_KEY` (step 4), or the key is wrong. The row was created before the send failed. Set the key, then `email:verification-link` for the stranded account |
| `Unsupported mail transport [brevo]` | The `Mail::extend('brevo', …)` name and `config/mail.php`'s `transport` value disagree |
| `Unable to send an email: Key not found (code 401)` | Wrong key — most often the **SMTP** key instead of the API key |
| Worked for weeks, now 401 on every send | Brevo's IP allow-list armed itself after 30 quiet days. Step 0: deactivate it |
| `code 402` / `account_under_validation` | Wrong Brevo account — transactional sending was never activated on this one |
| `code 400` … `sender not valid` | `MAIL_FROM_ADDRESS` is not exactly a validated Brevo sender |
| `code 429` | Rate limited; back off using the `x-sib-ratelimit-reset` header |
| 401 right after rotating the key | Stale `config:cache`. The entrypoint rebuilds it on boot — redeploy rather than restart |
| Sends succeed, nothing arrives | Past the daily cap (Brevo queues ~1,000 then **silently drops**), or spam-foldered. A `201` is acceptance, not delivery |
| `Password is not set` | The DSN scheme is a bare `brevo`; it must be `brevo+api` |
| Link opens as `403 Invalid signature` | The link is older than 60 minutes, or `APP_KEY` changed since it was sent. Press **Resend** |
| Link opens as `403` immediately after registering | Signed in as a *different* account than the one the link is for. Log out, open the link, sign in as the right one |
| Everyone got sent to "Check your email" after the deploy | Step 3 was skipped. Run the migration now; they can also just press **Resend** |
| Locally, no mail in Mailpit | `MAIL_MAILER` is not `smtp` or `MAIL_HOST` is not `mailpit`. `php artisan about --only=mail` shows what is in effect |

---

## Limits worth knowing

- **300 emails/day** on the free plan, reset daily, no monthly cap published,
  no expiry, no card. Shared with `save_furry_friend` — both apps send through
  the same account.
- Past the daily cap, up to 1,000 more are queued and **anything beyond that is
  silently dropped**. Irrelevant at demo volume; a bot on the public
  `/register` form makes it relevant instantly — the resend route is throttled
  to 6/minute per account, registration is not.
- Free-plan mail carries an unremovable **"Sent with Brevo"** footer.
- A Brevo free account **unused for 4 months is deleted**, with warnings first.
  Two apps sending the occasional verification email counts as active.
