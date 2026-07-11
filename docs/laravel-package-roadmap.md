# MCARE Laravel Package Roadmap

This note maps the capstone paper requirements to Laravel packages and framework features. It is intentionally staged so the app can keep working while bigger modules are added.

## Paper Requirements

The paper identifies these system modules as required for MCARE:

- Trainee Management Module
- Modular LMS and Content Security Module
- Application Compliance and Verification Module
- Scheduling Module
- PayMongo Payment Module
- Data Analytics and Reporting Module
- Alumni Tracker and Career Hub Module
- Automated Certification and Digital Record Module

It also describes role-based access for administrators, instructors/trainers, and trainees; protected learning materials with activity logging; progressive module unlocking; and secure system-generated PDF certificates/records.

## Recommended Packages

### Role and Permission Control

Use `spatie/laravel-permission` when we migrate from the current simple `users.role` column to full RBAC.

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan optimize:clear
php artisan migrate
```

Why: the paper explicitly references Spatie Laravel Permission for role-specific module boundaries. It will make admin, trainer, trainee, alumni, and staff permissions easier to manage than hard-coded role checks.

### Certificate PDF Generation

Use `spatie/browsershot` when we implement certificate and certificate-of-record generation.

```bash
composer require spatie/browsershot
npm install puppeteer
```

Why: certificates need polished browser-rendered layouts. Browsershot renders Blade/HTML through Chrome, which is better for certificate designs than simple text-only PDF generation.

Important: Browsershot requires Node 22 or newer and Puppeteer 23 or newer. Do not enable certificate download until Puppeteer/Chrome is installed and tested on the deployment machine.

### Optional Later Packages

- `spatie/laravel-activitylog`: replace or extend the current custom admin logs when audit requirements become heavier.
- `maatwebsite/excel`: export trainee lists, payment reports, analytics, and TESDA-style summaries.
- `intervention/image`: prepare trainee ID photos and certificate signatures if image processing becomes necessary.

## Current Implementation Choice

The trainer portal added in this phase uses the existing `users.role` field so it can work immediately. Spatie Permission should be introduced as a dedicated migration once admin, trainer, trainee, and alumni permissions are finalized.
