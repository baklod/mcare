# MCARE email 2FA setup

The admin 2FA flow uses Laravel Mail, which is backed by the Symfony Mailer
already included in this application. A separate \`phpmailer/phpmailer\` package
is not required; adding a second mail stack would duplicate transport and
credential handling.

## Local development

The repository's local \`.env\` intentionally uses the \`log\` mailer:

\`\`\`dotenv
MAIL_MAILER=log
\`\`\`

With that setting, the six-digit code is written to the Laravel log instead of
being sent to an inbox. This is safe for local testing. Do not commit real mail
credentials.

## SMTP delivery

Set these values in the deployment environment (not in Git):

\`\`\`dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=staff@example.com
MAIL_PASSWORD=your-provider-app-password
MAIL_FROM_ADDRESS=staff@example.com
MAIL_FROM_NAME="MCARE"

TWO_FACTOR_ENABLED=true
TWO_FACTOR_ROLES=admin
TWO_FACTOR_TTL=10
TWO_FACTOR_MAX_ATTEMPTS=5
\`\`\`

For Gmail, use an app password, not the normal account password. Mailgun, SES,
Postmark, and another SMTP provider can be used by replacing the host,
credentials, and sender values.

With this Laravel/Symfony Mailer version, port 587 uses the `smtp` scheme and
automatically negotiates STARTTLS. The supported scheme names are `smtp` and
`smtps`; do not set `MAIL_SCHEME=tls`.

After changing environment variables on a cached deployment, run:

\`\`\`bash
php artisan config:clear
php artisan config:cache
\`\`\`

The admin flow validates the password without creating a privileged session,
sends a six-digit code, stores only a hash in the encrypted session, expires
the challenge after the configured TTL, limits attempts, and logs only
challenge events—not the code in the admin activity log. When \`MAIL_MAILER=log\`
is used for local testing, Laravel's mail log necessarily contains the rendered
message and code; treat that log as sensitive and do not use that mailer in
production. The code is required on both the
dedicated \`/admin/login\` form and the generic account login when an admin
account is detected.

For production, add a monitored mail provider and a recovery path. Email OTP
is a useful baseline, but TOTP/WebAuthn, recovery codes, staff email
verification, and session revocation remain recommended hardening.
