# MCARE 65% Prototype Setup Guide

This guide explains how to publish the latest MCARE prototype branch and install it on another Windows laptop.

## Current Version

- Repository: `https://github.com/delulArs/mcare_hub.git`
- Branch: `65%-prototype`
- Expected commit: `b70e4f5 feat: complete 65% prototype admin and alumni workflows`
- PHP requirement: PHP 8.2 or newer
- Node.js requirement: Node.js 20.19 or newer, or Node.js 22.12 or newer

## 1. Branch Owner: Publish the Latest Version

Run these commands on the laptop containing the latest MCARE work:

```powershell
cd D:\Mcare-hub\mcare-hub-dev

git switch 65%-prototype
git status
git log -1 --oneline
git push -u origin 65%-prototype
```

Confirm that commit `b70e4f5` appears under the `65%-prototype` branch on GitHub.

If the repository is private, add teammates through:

`GitHub repository -> Settings -> Collaborators -> Add people`

## 2. Install Required Software

Each teammate needs:

- Git
- PHP 8.2 or newer
- Composer 2
- Node.js 20.19 or newer, or Node.js 22.12 or newer
- XAMPP/MySQL only when using the optional MySQL setup

Verify the installed tools:

```powershell
git --version
php --version
composer --version
node --version
npm --version
```

## 3. Clone the MCARE Branch

```powershell
cd $HOME\Documents

git clone --branch "65%-prototype" --single-branch https://github.com/delulArs/mcare_hub.git

cd mcare_hub
git log -1 --oneline
```

The latest commit should be `b70e4f5`.

## 4. Install Project Dependencies

Run these commands inside the cloned `mcare_hub` folder:

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

Do not copy another developer's private `.env` file. Every teammate should create their own local configuration.

## 5. Recommended Local Database: SQLite

SQLite is the easiest option for reviewing the prototype because it does not require XAMPP or a MySQL server.

Create the local database file:

```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```

Open `.env` and use:

```env
APP_NAME=MCARE
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite

MAIL_MAILER=log

# Local prototype convenience only. Keep 2FA enabled in production.
TWO_FACTOR_ENABLED=false

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

PAYMONGO_PUBLIC_KEY=
PAYMONGO_SECRET_KEY=
PAYMONGO_WEBHOOK_SECRET=
PAYMONGO_LIVE_MODE=false
```

Create the tables, demo accounts, and sample Career Hub opportunities:

```powershell
php artisan migrate --seed
php artisan optimize:clear
npm run build
```

Optionally verify the installation:

```powershell
php artisan test
```

Expected result for this version:

```text
112 passed
762 assertions
```

## 6. Run MCARE

Start Laravel:

```powershell
php artisan serve
```

Open:

`http://127.0.0.1:8000`

If port 8000 is unavailable:

```powershell
php artisan serve --port=8011
```

Then open:

`http://127.0.0.1:8011`

When actively changing frontend files, use two terminals:

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

## 7. Demo Accounts

All local seeded accounts use the demo password `password123`.

| Role | Email |
| --- | --- |
| Administrator | `admin@mcare.com` |
| Trainer | `trainer@mcare.com` |
| Trainee | `trainee@mcare.com` |
| Applicant | `applicant@mcare.com` |
| Alumni | `alumni@mcare.com` |

Shared login page:

`http://127.0.0.1:8000/login`

MCARE redirects each account to its correct role portal.

The demo password and accounts are for local development only. Replace or remove them before production deployment.

## 8. Optional MySQL Setup

Use this section instead of SQLite when the teammate needs MySQL.

1. Start MySQL from XAMPP.
2. Open phpMyAdmin.
3. Create a database named `mcare_db`.
4. Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mcare_db
DB_USERNAME=root
DB_PASSWORD=
```

Create the tables and demo records:

```powershell
php artisan migrate --seed
```

Apache is not required when using `php artisan serve`.

## 9. Get Future Updates

After new commits are pushed to the branch:

```powershell
git switch 65%-prototype
git pull --ff-only origin 65%-prototype
composer install
npm install
php artisan migrate
npm run build
php artisan optimize:clear
```

Do not run `php artisan migrate:fresh` after storing important local data. That command deletes all database records.

## 10. Common Fixes

### Missing Vite manifest or unstyled pages

```powershell
npm install
npm run build
```

### Missing notifications or another database table

```powershell
php artisan migrate
```

### Missing application encryption key

```powershell
php artisan key:generate
```

### Old routes, configuration, or Blade views

```powershell
php artisan optimize:clear
```

### MySQL connection refused

Start MySQL in XAMPP and verify the `DB_HOST`, `DB_PORT`, and `DB_DATABASE` values in `.env`.

### Admin login asks for an email verification code

For local prototype testing, set:

```env
TWO_FACTOR_ENABLED=false
```

When `TWO_FACTOR_ENABLED=true` and `MAIL_MAILER=log`, the development email is written to `storage/logs/laravel.log`.

`INFO Nothing to migrate` is normal when the local database is already updated.

## 11. Ngrok Tunneling & Public Testing

To expose your local Laravel app for mobile or remote testing:

1. **Terminal 1 (Laravel Web Server)**:
   ```powershell
   php artisan serve
   ```
2. **Terminal 2 (Background Mail Queue)**:
   ```powershell
   php artisan queue:work --queue=mail,default --tries=3 --timeout=120
   ```
3. **Terminal 3 (Ngrok Tunnel)**:
   - For custom reserved domains (ngrok v3):
     ```powershell
     ngrok http 8000 --url https://freewill-pacemaker-outflank.ngrok-free.dev
     ```
   - For free dynamic domains:
     ```powershell
     ngrok http 8000
     ```

Make sure your `.env` has:
```env
APP_URL=https://freewill-pacemaker-outflank.ngrok-free.dev
TRUSTED_PROXIES=*
```

## Security Reminders

- Never commit the `.env` file.
- Never share PayMongo secret keys publicly.
- Never share Gmail app passwords.
- Keep PayMongo in test mode during prototype testing.
- Enable two-factor authentication and secure cookies before production deployment.
