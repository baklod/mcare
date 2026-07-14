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

### Role and Permission Control — Baseline Implemented

`spatie/laravel-permission` is installed and now acts as a compatibility-safe RBAC layer. A migration creates the permission matrix, backfills existing accounts, and keeps new or changed `users.role` values synchronized with Spatie roles. Sensitive route groups require named permissions as well as their role middleware.

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan optimize:clear
php artisan migrate
```

The legacy `users.role` value remains the temporary source of truth so existing screens and records continue to work. The next authorization phase is to add model policies for individual enrollments, modules, receipts, certificates, and reports.

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

Spatie provides route-level roles and permissions while the existing role column preserves compatibility. Browsershot remains intentionally uninstalled until certificate verification, deployment requirements, Chrome/Puppeteer sandboxing, and operational limits are designed and tested.

See `docs/CYBERSECURITY_CURRENT_STATE_REPORT.md` for the verified current state and phased security roadmap.
