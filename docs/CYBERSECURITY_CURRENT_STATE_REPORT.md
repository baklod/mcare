# MCARE Cybersecurity Current-State Report

**Assessment date:** July 14, 2026
**System:** MCARE Laravel enrollment, learning, payment-workflow, and administration portal
**Assessment type:** Source-code, route, configuration, dependency, and automated-test review

## Executive Summary

MCARE has a useful development-stage security baseline, but it should **not yet be described as production-hardened**. Authentication, CSRF protection, private file storage, response privacy headers, rate limiting, server-side validation, password hashing, session rotation, and activity logging are present. This review also activated the installed Spatie Permission package, remediated the dependency advisories found in the lockfile, strengthened upload checks, and improved browser security headers.

The largest remaining risks are operational and feature-level: stronger administrator MFA and password recovery are not implemented, PayMongo is still a UI/workflow integration without a verified webhook, uploads are not malware-scanned, authorization policies are not yet defined for every individual record, and certificate authenticity/revocation is not yet implemented. A real deployment also needs TLS, backup/restore testing, secret management, monitoring, and an independent penetration test.

This document is an engineering assessment, not a penetration-test certificate or a guarantee that the system has no vulnerabilities.

## Latest implementation update

The current working tree also includes a session-bound payment continuation for
new enrollment submissions (the form no longer silently authenticates the
applicant), private in-page document previews, validation-safe upload drafts,
and a shared navigation guard that suppresses repeated sidebar requests. The
navigation guard may send an allow-listed `navigation_spam` telemetry event to
the existing audit log. This client signal is only an operational hint: a
modified browser or disabled JavaScript can omit it, so it must never be used
as proof of misconduct or as a replacement for server-side authorization,
rate limiting, or incident review.

Administrator sign-in now uses a password plus a short-lived, six-digit email
verification code for configured roles. The code is hashed in the encrypted
session, expires after the configured TTL, has a maximum attempt count, and
does not create the privileged session until verification succeeds. The generic
account login and Google callback paths do not bypass this staff challenge.
The admin activity log records sent, failed, expired, locked, and verified
events without recording the code; the local \`log\` mailer still renders the
code into the Laravel mail log and is for development only.

## Scope and Method

The review covered Laravel routes, middleware, controllers, models, migrations, authentication flows, upload handling, protected document responses, audit logging, Composer and npm lockfiles, and the automated feature tests. The following checks were run:

- `composer audit --locked --no-dev`
- `npm audit --omit=dev`
- Laravel feature tests and route inspection
- Production asset build
- Manual source review of authentication, authorization, file delivery, security headers, and payment workflow

Out of scope: live infrastructure scanning, external attack simulation, social engineering, mobile binary analysis, database-server configuration, and third-party cloud configuration.

## Current Controls

| Area | Current implementation | Status |
| --- | --- | --- |
| Authentication | Laravel session authentication, Socialite OAuth state validation, session ID regeneration, logout invalidation, and admin email 2FA before privileged session creation | Implemented baseline |
| Role access | Spatie roles/permissions synchronized from the existing `users.role` field; role and named-permission middleware protect role portals and sensitive route groups | Implemented baseline |
| Request integrity | Laravel CSRF protection and server-side validation | Implemented |
| Abuse controls | Global and endpoint-specific rate limiters for login, OAuth, search, mutations, and document responses | Implemented |
| Password storage | Laravel hashed cast; password rules require at least 10 characters, mixed case, letters, and numbers | Implemented |
| Private records | Enrollment files and learning modules are stored outside the public web root and delivered through authorized controllers | Implemented |
| Browser headers | `nosniff`, frame restrictions, referrer policy, permissions policy, cross-origin isolation, private no-store/noindex headers, production CSP, and HTTPS-only HSTS | Implemented baseline |
| Audit records | Login, admin, module, learning, and export events use the custom admin activity log; print and CSV export are available | Implemented baseline |
| Content deterrence | Viewer controls, watermark identity, private delivery, and restricted-action telemetry | Deterrence only |
| Payment security | Payment selection/reference workflow exists; server-to-server PayMongo checkout and signed webhook verification do not | Incomplete |
| Certificate security | Eligibility views exist; signed serial/QR verification, revocation, and Browsershot generation do not | Planned |

## Findings and Priorities

### High Priority

1. **Stronger administrator MFA and account recovery are still needed.** Email OTP now protects the admin password flow, but a stolen mailbox or mail-delivery failure can still affect access. Add password reset, verified staff email, TOTP/WebAuthn MFA, recovery codes, and session revocation.
2. **PayMongo is not yet a trusted payment integration.** The current application can prepare payment state, but it does not create and verify the complete server-side payment lifecycle. Payment approval must be based on PayMongo API results and a signature-verified, replay-safe webhook—not browser-submitted status.
3. **Uploaded files are not malware scanned or content-disarmed.** MIME and extension allow-lists reduce accidental misuse but do not prove a PDF or media file is safe. Add antivirus scanning, quarantine, and PDF content disarm/reconstruction before staff or learners open uploads.
4. **Production security is not verified.** Before real applicant data is used, verify `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure cookies, trusted proxies, least-privilege database credentials, encrypted backups, restore drills, and secret rotation.

### Medium Priority

1. **Per-record authorization is partly controller-based.** Route permissions now provide a strong role boundary, but Laravel policies should explicitly decide who may view each enrollment, module, receipt, certificate, report, and audit record. Policies should deny access by default.
2. **The CSP still allows inline styles and scripts.** Existing Blade pages require this compatibility setting. Move inline JavaScript and styles into Vite assets, then replace `'unsafe-inline'` with nonces or hashes.
3. **Audit monitoring is passive.** Logs can be viewed and exported, but suspicious patterns are not alerted, retained under a documented schedule, protected by append-only storage, or forwarded to monitoring.
4. **Long-lived sessions need user controls.** Add a “sign out other devices” screen, staff idle timeout, session inventory, and forced re-authentication for security-sensitive changes.
5. **Data lifecycle rules are not formalized.** Define consent evidence, record retention, archival, deletion, and breach-response procedures for personal and TESDA-related data.

### Resolved in This Security Pass

- Updated the affected Guzzle dependency family. The initial Composer audit found three medium-severity advisories; the post-update audit reports no known advisories in the locked production packages.
- Confirmed the npm production audit reports no known vulnerabilities.
- Activated Spatie Permission with role and permission backfill while retaining the current `users.role` field as a compatibility source of truth.
- Added named permission checks to sensitive admin, trainer, trainee, payment, document, module, report, and account routes.
- Added matching extension checks alongside content-MIME validation for uploaded documents and learning files.
- Removed the unnecessary password character blacklist and applied a stronger, usable password rule.
- Expanded browser security headers and made the production CSP compatible with same-origin protected previews.
- Added admin email OTP 2FA on the dedicated and generic account login paths, with hashed codes, expiry, attempt limits, pre-auth sessions, audit events, and a defense-in-depth middleware check.

## Content Protection: Accurate Security Statement

MCARE can **control server access, deter casual copying, watermark content, and record actions visible to the application**. It cannot guarantee that a learner will never copy or capture content after the browser or mobile device has rendered it. JavaScript cannot reliably detect developer tools, operating-system screenshots, another camera, modified clients, or every print path. Disabling JavaScript may be inferred later if expected telemetry disappears, but it cannot be treated as proof of a specific action.

Paper-safe wording:

> The system applies authenticated authorization, private content delivery, identity-based watermarking, cache restrictions, and activity logging to deter and trace unauthorized redistribution. Client-side restrictions are defense-in-depth controls and do not claim to prevent all screen capture, printing, or copying on user-controlled devices.

## Implementation Roadmap

### Phase 1 — Baseline (completed in this pass)

- Dependency advisory remediation
- Spatie role/permission bridge and existing-account backfill
- Named permissions on sensitive route groups
- Upload MIME-plus-extension validation
- Stronger password validation
- Browser and private-response header improvements
- Automated regression tests for roles, permissions, and headers

### Phase 2 — Identity and Record Authorization (next)

- Add Laravel policies for enrollments, documents, modules, receipts, reports, certificates, and audit logs
- Add password reset, staff email verification, stronger TOTP/WebAuthn MFA, recovery codes, and session revocation; retain email OTP as the baseline factor
- Log denied sensitive actions with a request correlation ID, without logging passwords, tokens, or full document contents
- Add user/session inventory and staff idle timeout

### Phase 3 — Trusted Payments and Digital Artifacts

- Create PayMongo checkout server-side and verify webhook signatures, event IDs, amounts, currency, and final status
- Make webhook processing idempotent and preserve the provider event ID for audit
- Implement Browsershot only after Node, Chrome/Puppeteer, sandboxing, timeouts, and deployment capacity are verified
- Give certificates a unique serial, signed verification URL/QR code, immutable issuance record, document hash, and revocation status
- Keep receipts, tickets, and certificates authorization-protected even if a PDF copy is downloadable

### Phase 4 — File and Operations Hardening

- Quarantine uploads; scan with antivirus and use content disarm/reconstruction for PDFs where feasible
- Move inline scripts/styles out of Blade and enforce a nonce/hash CSP
- Configure centralized alerts, protected log retention, backup encryption, restore drills, and incident response
- Run a staging vulnerability scan and an independent penetration test before handling production personal data

## Verification Checklist Before Production

- [ ] All migrations run successfully on a production-like backup copy
- [ ] `composer audit --locked --no-dev` and `npm audit --omit=dev` are clean or formally risk-accepted
- [ ] Full automated test suite and production asset build pass
- [ ] Admin, trainer, trainee, applicant, and alumni permission matrix is signed off by the project owner
- [ ] Every personal-data and downloadable-artifact route has a policy test for allowed and denied users
- [ ] HTTPS, secure cookies, debug-off behavior, CSP, backups, and restore are verified in staging
- [ ] PayMongo signed webhooks are tested with success, retry, replay, wrong amount, and invalid-signature cases
- [ ] Upload quarantine and malware handling are tested
- [ ] Admin email OTP, stronger MFA/recovery, and session revocation are tested
- [ ] Incident contact, retention schedule, privacy notice, and breach-response procedure are documented

## Authoritative References

- OWASP Authorization Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html
- OWASP File Upload Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- OWASP HTTP Headers Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html
- OWASP Content Security Policy Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html
- Laravel Authorization: https://laravel.com/docs/12.x/authorization
- Laravel Validation: https://laravel.com/docs/12.x/validation
- Spatie Laravel Permission: https://spatie.be/docs/laravel-permission/v6/introduction
