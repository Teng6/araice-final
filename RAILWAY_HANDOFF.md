# A-RAICE Railway handoff

This note summarizes the Railway hosting work and decisions from the hosting
conversation. It is intended as context for the next coding assistant.

## Repository and deployment

- Repository: `Teng6/araice-final`
- Working branch at handoff: `PHASE-4`
- The local branch was clean and matched `origin/PHASE-4` at handoff
  (`64d6ac0`).
- Live Railway URL reported by the user: <https://araice.up.railway.app>
- The user confirmed the site was working after the blank-page fix. Railway's
  exact currently active commit was not independently inspected.
- Railway project shown in screenshots: `agile-charisma`, production
  environment; app service `araice-final`; Postgres service `Postgres`.
- Do not open browser tabs or modify Railway account settings unless the user
  explicitly asks. The user preferred to configure Railway manually.

## Hosting changes in the repository

The Laravel/Vue application has Railway Docker deployment support:

- `Dockerfile` uses PHP 8.4 (needed by the locked Symfony 8 dependencies),
  installs `pdo_pgsql`, Node.js 22, Composer dependencies and frontend assets,
  creates the Laravel storage link, and starts the app on Railway's `PORT`.
- The Docker build uses `npm install --no-audit --no-fund`, not `npm ci`.
  Railway's Linux `npm ci` failed because the Windows-generated lockfile omitted
  platform/optional dependency metadata. This was verified in Railway's build
  output; an npm install and frontend build passed locally.
- The container invokes `docker/start.sh` explicitly through `/bin/sh`, avoiding
  dependence on executable file permissions being preserved on Windows.
- `docker/start.sh` prepares writable Laravel storage/cache directories, runs
  `php artisan migrate --force`, seeds initial catalog data, and starts Laravel.
- `railway.json` specifies Dockerfile deployment, `/up` health checking, and
  one replica. One replica is important because local uploaded files use a
  Railway volume and Railway volumes are not shared across replicas.
- Railway should mount the app's persistent volume at
  `/var/www/html/storage/app/public`. The separate Postgres volume is only for
  database files.
- `bootstrap/app.php` trusts forwarded proxy headers. This fixed the deployed
  blank page: the HTML had HTTP Vite asset URLs while the page was HTTPS, so
  Chrome blocked the CSS and JavaScript as mixed content.
- `tests/Feature/RailwayHttpsAssetsTest.php` covers HTTPS asset URL generation
  when Railway forwards `X-Forwarded-Proto` and `X-Forwarded-Host`.
- Production seeding does not create the development `test@example.com` user
  and returns without overwriting an existing disease catalog.
- `RAILWAY.md` contains setup, environment-variable, volume, and deployment
  instructions.

## Email verification behavior

The live deployment had no configured mail sender; local mail defaults to
Laravel's `log` mailer, which writes verification messages to logs rather than
delivering them. The user explicitly chose to disable the verification gate
instead of setting up an email provider.

- `AUTH_REQUIRE_EMAIL_VERIFICATION` defaults to `false` in `config/auth.php`
  and `.env.example`.
- New registrations are marked verified and do not dispatch a verification
  email while the setting is false.
- The dashboard requires authentication but not email verification by default.
- The profile does not prompt for verification or clear the verified timestamp
  after email edits while verification is disabled.
- Setting `AUTH_REQUIRE_EMAIL_VERIFICATION=true` re-enables the verification
  requirement and registration notification. A real mail provider must then be
  configured; no SMTP provider or credentials were added.
- A Resend/Gmail SMTP configuration was explored, then explicitly reverted at
  the user's request. Do not re-add or configure email sending unless asked.

## User roles

There is no in-app user role editor. Existing valid role values are `farmer`,
`lgu_staff`, and `admin`. The user was shown how to change
`lgu@example.com` through Railway's Postgres console. Railway's Postgres Console
opens a Linux shell, so enter `psql "$DATABASE_URL"` first; at the `railway=#`
prompt, run:

```sql
UPDATE users
SET role = 'lgu_staff'
WHERE email = 'lgu@example.com'
RETURNING id, name, email, role;
```

The conversation did not confirm the result of this update. `admin` is
full-privilege and should only be assigned to a trusted account.

## Test and validation history

- Full Laravel suite passed after disabling verification:
  **67 tests, 290 assertions**.
- The HTTPS asset regression test passed separately:
  **1 test, 3 assertions**.
- Pint passed for the verification and proxy changes; `git diff --check` passed.
- Earlier Railway troubleshooting was based on screenshots/logs supplied by the
  user. Docker was not installed locally, so no local Docker image build was
  performed.

## Other user-requested values

- Approximate Orion, Bataan coordinates: latitude `14.62157`, longitude
  `120.57723`.
- The user asked for a handoff summary file specifically so another coding
  assistant could understand the deployment changes and decisions.
