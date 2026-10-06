# Deploying to Railway

This app builds its PHP dependencies and Vue assets into a PHP 8.4 Docker image. Railway
also needs a PostgreSQL database and a persistent volume for uploaded scan photos.

## 1. Create the Railway services

1. Push this repository to GitHub.
2. In Railway, create a project and deploy the GitHub repository as a service.
   Railway will use the repository's `Dockerfile`.
3. Add a PostgreSQL service to the project.
4. Add a volume to the app service, mounted at:
   `/var/www/html/storage/app/public`

Keep the app at one replica when using the local volume for uploads. Railway
volumes are not shared between replicas.

## 2. Configure app variables

Set these variables on the app service:

| Variable | Value |
| --- | --- |
| `APP_NAME` | `A-RAICE` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate locally with `php artisan key:generate --show`, then paste the output into Railway. |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Reference the Postgres service's `DATABASE_URL`, for example `${{Postgres.DATABASE_URL}}`. Replace `Postgres` with the actual service name if different. |
| `FILESYSTEM_DISK` | `public` |
| `VIT_API_URL` | Base URL of the leaf-disease classifier API. |
| `GRAIN_API_URL` | Base URL of the grain classifier API. |

After Railway generates a public domain for the app, set `APP_URL` to that full
HTTPS URL (for example, `https://your-app.up.railway.app`) and redeploy.
Laravel trusts Railway's forwarded HTTPS headers so Vite assets are generated
with HTTPS URLs behind Railway's TLS proxy.

## 3. Configure verification and password-reset email

Laravel's registration, email verification, resend-verification, and password
reset flows already send mail. The local `.env.example` uses the `log` mailer,
so production needs a real SMTP provider. The Docker image selects SMTP so
production emails are sent rather than silently written to Laravel logs.

One option is [Resend SMTP](https://resend.com/docs/send-with-smtp):

1. Create a Resend account, add a domain you control, and finish its DNS
   verification. The Railway `up.railway.app` domain is not a sender domain.
2. Create a Resend API key.
3. Add these variables to the Railway app service:

   | Variable | Value |
   | --- | --- |
   | `MAIL_MAILER` | `smtp` |
   | `MAIL_SCHEME` | `smtps` |
   | `MAIL_HOST` | `smtp.resend.com` |
   | `MAIL_PORT` | `465` |
   | `MAIL_USERNAME` | `resend` |
   | `MAIL_PASSWORD` | The Resend API key (keep it secret). |
   | `MAIL_FROM_ADDRESS` | A sender address on the verified domain, e.g. `noreply@mail.example.com`. |
   | `MAIL_FROM_NAME` | `A-RAICE` |

Save the variables and redeploy the app. Test by registering with an inbox you
can access; use **Resend Verification Email** if needed. Also test **Forgot
Password**. Check the Resend Emails dashboard and Railway deploy logs if a
message does not arrive; check spam/junk too. Never use `MAIL_MAILER=log` in
Railway.

The two classifier URLs are needed for scan predictions. The rest of the app can
start without them, but scan submissions will be marked failed until the
corresponding APIs are configured and reachable.

## 4. Deploy and check

Deploy the app service. Container startup runs database migrations, seeds the
initial disease and rice-variety catalog, and then starts Laravel on Railway's
assigned port. The `/up` endpoint is configured as the deployment health check.

Production seeding does not create the development `test@example.com` account.
It also leaves an existing disease catalog untouched on later restarts and
deployments.

Open the generated public domain and check the service logs in Railway if the
health check or startup fails. Make sure the app service can reach the Postgres
service and that `DB_URL` points to its connection URL.
