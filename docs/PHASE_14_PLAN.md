# Phase 14 Plan: Production Hardening & cPanel Deployment Verification

This document details the technical specifications, architectural safeguards, and automated verification suites for hardening Krushi Baandhava for Linux cPanel shared hosting (PHP 8.2+, MySQL 8.0, Apache).

## Proposed Changes

### 1. HTTP Security Headers Middleware
- File: `app/Http/Middleware/SecurityHeadersMiddleware.php`
- Applies critical defensive headers to all HTTP responses:
  - `X-Frame-Options: SAMEORIGIN` (prevents clickjacking)
  - `X-Content-Type-Options: nosniff` (prevents MIME-confusion attacks)
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=(self)`
  - `Content-Security-Policy`: Secure baseline allowing self, inline scripts for Alpine.js/Livewire, and YouTube embeds for curated agricultural videos.
- Registered globally in `bootstrap/app.php`.

### 2. Rate Limiting Configuration
- Files: `bootstrap/app.php` & `app/Providers/AppServiceProvider.php`
- Define custom rate limiters:
  - `api`: 60 requests per minute per IP
  - `decision`: 30 simulations per minute per IP
  - `admin-login`: 5 attempts per minute per IP with backoff
- Guard public API routes with throttles.

### 3. Apache `.htaccess` Production Hardening
- File: `public/.htaccess`
- Add `mod_deflate` / Gzip compression rules for text, html, css, js, svg, json.
- Add `mod_expires` / browser caching directives (1 year for Vite compiled hashed assets, 1 month for icons/svgs).
- Deny direct access to `.env`, `.git`, `.bak`, `.sql`, `.yaml`, and dotfiles.
- Enforce `Options -Indexes` preventing directory traversal.

### 4. Production Health Check Command
- File: `app/Console/Commands/ProductionHealthCheckCommand.php`
- Signature: `app:health-check` (with `--json` and `--fail-fast` options).
- Performs automated pre-flight checks:
  1. PHP version >= 8.2 and required extensions (`intl`, `pdo_mysql`, `curl`, `mbstring`, `gd`, `bcmath`).
  2. Database connectivity & pending migrations check.
  3. Master data integrity: Active Karnataka districts, mandis, crops count > 0.
  4. Write permissions for `storage/` and `bootstrap/cache/`.
  5. Cache & Session drivers operational.
  6. Environment checks: Warns if `APP_DEBUG=true` in production or default `APP_KEY` missing.

### 5. cPanel Deployment Scripts & Checklist
- File: `scripts/cpanel-deploy.sh`
- File: `docs/DEPLOYMENT_CPANEL_CHECKLIST.md`
- Automated bash script for cPanel Terminal/SSH executing permissions, symlinks, optimizations, migrations, and health check.
- Complete, step-by-step operations manual for shared hosting administrators.

### 6. Automated Feature Tests
- File: `tests/Feature/ProductionHardeningTest.php`
- Verifies security headers on farmer and admin routes.
- Verifies rate limiter enforcement.
- Verifies `app:health-check` artisan command passes with exit code 0.
- Verifies `/up` Laravel health probe returns 200.
- Verifies `.htaccess` rules and file protection.

---

## Verification Plan

### Automated Tests
- Run `& "C:\Program Files\php-8.2.30\php.exe" artisan test tests/Feature/ProductionHardeningTest.php`
- Run full test suite: `& "C:\Program Files\php-8.2.30\php.exe" artisan test` (must pass 100% with zero regressions)
- Run `npm run build`
- Run `& "C:\Program Files\php-8.2.30\php.exe" artisan app:health-check`
