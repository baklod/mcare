# MCARE Security Hardening Notes

Branch: `comments-checking-in-code`

This file explains the security changes added during the code-review branch. It is written as a study guide so future maintainers can understand **why** each control exists.

---

## 1. Global rate limiting

All web routes now run through:

```php
Route::middleware('throttle:global-web')->group(function () {
    // application routes
});
```

The named limiter is defined in `AppServiceProvider`:

```php
RateLimiter::for('global-web', function (Request $request) use ($actorKey) {
    return Limit::perMinute(120)
        ->by('global-web:'.$actorKey($request));
});
```

### Why 120 per minute?

This is a **coarse flood-control ceiling**, not the main security defense. It is intentionally generous so normal browsing still works.

### Important limitation

Rate limiting does **not** prevent SQL injection, XSS, CSRF, or broken authorization. A single malicious request can still be dangerous if code is vulnerable. Those threats need their own controls.

---

## 2. Strict admin-login limiter

Admin login receives a separate limiter:

- 5 attempts per minute for normalized email + IP
- 20 attempts per minute for the IP overall

The email + IP key is hashed before use.

### Why combine email and IP?

- Email-only limiting could let an attacker lock out another person too easily.
- IP-only limiting can be weak behind shared networks.
- Combining both gives a stronger first barrier, while the IP-wide ceiling catches rotation across many emails.

### Deployment limitation

IP-based limits depend on correct proxy configuration. When deploying behind Cloudflare, Nginx, a load balancer, or another reverse proxy, verify Laravel's trusted-proxy setup so `request()->ip()` represents the real client and not only the proxy server.

---

## 3. Endpoint-specific limiters

Named limiters were added for:

- OAuth redirects/callbacks
- admin searches
- sensitive mutations
- protected document downloads

Examples of sensitive mutations:

- enrollment review decisions
- batch create/update/delete
- logout actions

### Why separate limiters?

A user can legitimately load many pages but should not need to perform dozens of high-impact state changes or bulk-download private files every minute.

---

## 4. Global browser-security headers

`SecurityHeaders` is appended globally in `bootstrap/app.php`.

It adds:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`
- `X-Permitted-Cross-Domain-Policies: none`

In production it also adds a Content Security Policy (CSP). HSTS is added only when the current production request is actually HTTPS.

### Why is CSP not fully strict yet?

The current enrollment page contains inline JavaScript for features such as the signature pad. Therefore the production CSP temporarily permits inline scripts/styles.

Future improvement:

1. move inline JavaScript into Vite-managed files;
2. remove `'unsafe-inline'`;
3. use nonces or hashes when necessary.

---

## 5. Private response headers

`PrivateResponseHeaders` is applied to:

- enrollment pages
- payment pages
- admin pages

It adds:

- `Cache-Control: no-store...`
- `Pragma: no-cache`
- `Expires: 0`
- `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`
- `Referrer-Policy: no-referrer`

### Why?

Private pages can contain:

- applicant names
- email addresses
- birth dates
- addresses
- payment references
- uploaded-document links

The goal is to reduce accidental storage in browser/shared caches and reduce search-engine indexing or URL leakage.

### Important limitation

`noindex` and `robots.txt` are not security controls. Attackers can ignore them. Authentication and authorization remain mandatory.

---

## 6. Search input limits

Admin enrollment and activity-log searches now validate:

```php
'search' => ['nullable', 'string', 'max:100']
```

### Why?

Eloquent parameter binding already helps protect query values from SQL injection. The length limit mainly prevents oversized payloads and reduces abuse of wildcard `LIKE` searches.

---

## 7. OAuth state protection

The Google callback was changed from:

```php
Socialite::driver('google')->stateless()->user();
```

to:

```php
Socialite::driver('google')->user();
```

### Why?

This is a normal browser/session application. Stateful Socialite keeps and verifies the OAuth `state` value, which helps defend against login CSRF and authorization-response mixups.

The session is also regenerated after successful Google authentication.

---

## 8. Safer global error behavior

The universal error handler now includes a readable `429 Too Many Requests` response.

It also only allows detailed local 500 errors when:

```php
app()->isLocal() && config('app.debug')
```

### Why?

If `APP_DEBUG=true` is accidentally left enabled on a non-local deployment, the custom error handler still avoids intentionally returning detailed 500 diagnostics through this branch.

Production must still use:

```env
APP_ENV=production
APP_DEBUG=false
```

---

## 9. Session configuration notes

`.env.example` now documents:

```env
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=false
```

For HTTPS production:

```env
SESSION_SECURE_COOKIE=true
```

### Why is secure cookie false in the example?

Local development currently uses plain HTTP. Setting it true locally would prevent the browser from sending the session cookie over HTTP.

---

## 10. Search-engine crawler rules

`public/robots.txt` now discourages crawling of:

- `/admin/`
- `/payment`
- `/auth/`
- `/enrollment`

Again: this is privacy hygiene, not access control.

---

## 11. Automated tests added

`tests/Feature/SecurityHardeningTest.php` covers:

- global security headers
- private noindex/no-store behavior
- admin-login throttling
- rejection of oversized admin searches

---

## 12. Commands to run before merging

```bash
composer install
npm install
php artisan config:clear
php artisan test
npm run build
```

For a clean database validation:

```bash
php artisan migrate:fresh --seed
php artisan test
```

Do not run `migrate:fresh` against a database containing important real data.

---

## 13. Remaining security work

This hardening pass does **not** make the system perfectly secure. Important future work includes:

- install and configure formal RBAC (for example Spatie Permission) when ready
- add authorization policies for future staff/trainer/assessor roles
- implement real PayMongo webhook signature verification
- add malware scanning for uploaded documents if operationally feasible
- add file-content validation beyond extensions/MIME declarations
- move inline scripts to Vite and tighten CSP
- add password-reset and account-recovery controls
- consider MFA for admins
- configure trusted proxies correctly in production
- add monitoring/alerts for repeated failed logins and bulk document access
- perform black-box and penetration testing before live deployment

---

## Final reminder

Security is layered:

- **Validation** controls accepted input.
- **Escaping** reduces XSS risk.
- **Eloquent/parameter binding** reduces SQL injection risk.
- **CSRF tokens** protect state-changing browser requests.
- **Authentication** establishes identity.
- **Authorization** decides what that identity may access.
- **Rate limiting** slows abuse.
- **Security headers** reduce browser-side attack surface.
- **Logging** helps detect and investigate incidents.

No single control replaces the others.
