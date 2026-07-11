# MCARE Content Protection and Monitoring Boundaries

## Purpose

This document records what MCARE Hub can monitor, what it can only deter, and what it cannot reliably detect when users view protected learning materials.

The goal is layered protection and accountable access. The system must not claim that browser-based controls can guarantee prevention of copying, printing, screenshots, or external recording.

## Key Principle

The server can reliably log requests that reach Laravel. It cannot reliably observe actions that happen entirely inside the user's browser, operating system, extension, another application, or an external camera.

## What Laravel Can Reliably Log

Laravel can log an event when the server receives a related request, including:

- Successful and failed login attempts.
- Module-list and module-view requests.
- Authorized and denied file requests.
- Download endpoint requests.
- Certificate, receipt, ticket, or report generation.
- Expired or invalid signed-link usage.
- Authorization failures and direct URL attempts that reach Laravel.
- Excessive requests detected by rate limiting.
- Session, user, IP address, user agent, document identifier, and timestamp.
- Screenshot events reported by a supported native mobile application.

These events should be recorded through structured audit logs. Sensitive data, authentication secrets, full session tokens, and unnecessary document contents must not be stored in logs.

## What JavaScript Can Report as a Deterrence Signal

JavaScript may report interface events such as:

- Right-click or context-menu attempts.
- Copy, cut, paste, and text-selection attempts.
- Known keyboard shortcuts such as Ctrl+P, Ctrl+S, or Print Screen.
- Page visibility changes, focus loss, and tab switching.
- Clicking a controlled print or download button.

These are weak signals, not proof of misconduct. They can be bypassed by disabling JavaScript, changing browser behavior, using extensions, or performing the equivalent action through the operating system. They must not automatically suspend or accuse a user.

## What the Web Application Cannot Reliably Detect

A normal website cannot reliably determine that a user:

- Opened browser developer tools.
- Inspected the network panel.
- Read a response from browser cache.
- Disabled or modified JavaScript.
- Used a browser extension.
- Used an operating-system screenshot or recording tool.
- Printed through an operating-system or browser path not exposed to the page.
- Copied pixels using another application.
- Photographed the display with another device.

Developer-tools detection tricks based on window size, debugger timing, console behavior, or repeated `debugger` statements are unreliable. They create false positives, harm accessibility and performance, and are easy to bypass. MCARE should not treat them as a security control.

## Direct File Access Boundary

Calling a protected file URL can be logged only when the request reaches Laravel or another monitored delivery service. If the original file has already been delivered to the browser, the system cannot reliably observe a user reading it from local cache or copying it outside the application.

Therefore:

- Store protected originals outside the public web directory.
- Do not expose stable public storage URLs.
- Authorize every document request on the server.
- Use short-lived signed URLs where direct delivery is necessary.
- Avoid returning original module files when a personalized viewing copy can be used.
- Record allowed and denied access attempts.

## Recommended MCARE Protection Layers

1. **Authorization:** Confirm role, batch membership, ownership, and module entitlement on every request.
2. **Private storage:** Keep originals outside `public/` and deliver them only through authorized endpoints.
3. **Personalized watermarking:** Repeat the trainee name, trainee ID, document ID, and access time across rendered pages.
4. **Restricted viewer:** Prefer an authenticated viewer or personalized derivative instead of exposing the original file.
5. **Browser deterrence:** Keep copy, print, save, and context-menu controls as convenience barriers only.
6. **Audit logging:** Log server-side access, denial, generation, verification, and suspicious request patterns.
7. **Native mobile protection:** On Android, use secure-window protection for sensitive screens and supported screenshot callbacks for audit signals. On iOS, react to available capture notifications and obscure content during active screen recording where possible.
8. **Rate limiting and revocation:** Limit repeated access and allow administrators to revoke sessions, links, or issued documents.

## Artifact-Specific Policy

### Training Modules

- Restrict access by trainee and batch.
- Use personalized watermarks.
- Avoid exposing the original uploaded file.
- Log view and authorized delivery requests.
- Treat JavaScript restrictions as deterrence.

### Assessments

- Restrict access by schedule and eligibility.
- Randomize questions where appropriate.
- Log attempt start, submission, timeout, and authorization failures.
- Apply native secure-screen controls in a future mobile application.

### Certificates, Receipts, and Tickets

These artifacts are intended to be downloaded by authorized users. Protect authenticity instead of trying to make them impossible to save.

- Add a unique serial number and QR verification URL.
- Store an immutable issuance record.
- Record the recipient, issuer, template version, issue date, and file hash.
- Support revocation and replacement states.
- Log generation and download requests.

## Incident and Enforcement Rule

No single client-side signal is sufficient proof of unauthorized copying. Enforcement should use corroborating server evidence, repeated suspicious behavior, account history, and administrative review. Screenshot detection from a native application should be treated as an audit event unless policy explicitly states otherwise and users have been informed.

## Paper-Safe Wording

> The system implements layered content-protection controls, including authenticated access, private file storage, restricted viewing, browser-level copy and print deterrence, personalized watermarks, access logging, and supported mobile screen-capture protections. These controls reduce unauthorized redistribution while recognizing that no client-side technology can completely prevent external photography or capture on compromised devices.

## Implementation Checklist

- [ ] Define structured audit-event names and retention rules.
- [ ] Add policies for module, certificate, receipt, ticket, and report access.
- [ ] Move all protected originals to private storage.
- [ ] Remove stable public URLs for protected materials.
- [ ] Generate personalized watermarked viewing copies.
- [ ] Add rate limits to protected-document endpoints.
- [ ] Log allowed and denied server-side requests.
- [ ] Add QR verification and immutable issuance records for official artifacts.
- [ ] Add Android secure-window protection in the future mobile client.
- [ ] Add supported Android/iOS capture audit signals with user notice.
- [ ] Add automated authorization and direct-URL tests.
- [ ] Revise the paper and UAT claims to distinguish prevention, deterrence, and detection.
