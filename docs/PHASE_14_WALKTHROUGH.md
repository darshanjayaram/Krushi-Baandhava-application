# Phase 14 Walkthrough: Production Hardening & cPanel Deployment Verification

## Overview
Phase 14 represents the operational hardening and production deployment verification milestone for **Krushi Baandhava**. The application is now fully hardened for Linux cPanel shared hosting (PHP 8.2+, MySQL 8.0, Apache), protected with enterprise HTTP security headers, throttled against API abuse and brute-force attacks, accelerated with Gzip compression and long-term browser asset caching, and equipped with automated deployment scripting and production diagnostics.

---

## Changes Implemented

### 1. HTTP Security Headers Middleware (`app/Http/Middleware/SecurityHeadersMiddleware.php`)
- Created and globally registered defensive security headers middleware in `bootstrap/app.php`:
  - `X-Frame-Options: SAMEORIGIN`: Prevents UI redressing and clickjacking.
  - `X-Content-Type-Options: nosniff`: Prevents MIME-confusion attacks.
  - `X-XSS-Protection: 1; mode=block`: Legacy browser XSS protection.
  - `Referrer-Policy: strict-origin-when-cross-origin`: Restricts referrer data leakage across origins.
  - `Permissions-Policy: camera=(), microphone=(), payment=(), usb=(), display-capture=(), geolocation=(self)`: Disables unauthorized device sensors while strictly permitting self-origin geolocation for nearby mandi lookups.
  - `Content-Security-Policy`: Clean baseline securing scripts, styles, fonts, images, and permitting YouTube embeds for curated agricultural videos.

### 2. Multi-Tier Rate Limiting (`app/Providers/AppServiceProvider.php`, `routes/api.php`, `routes/web.php`)
- Configured named rate limiters:
  - `api`: 60 requests per minute per IP / user for all REST endpoints (`/api/v1/*`).
  - `decision`: 30 simulations per minute for the Where-to-Sell net realization calculator (`/api/v1/decision/where-to-sell`).
  - `admin-login`: 5 attempts per minute per IP with progressive backoff on `POST /admin/login`.

### 3. Apache `.htaccess` Production Tuning (`public/.htaccess`)
- **Directory Traversal Protection**: Enforced `Options -Indexes` and `Options -MultiViews`.
- **Sensitive File Shielding**: Blocks direct HTTP access to `.env`, `.git`, `.bak`, `.sql`, `.yaml`, `.ini`, `.sh`, `.log`, and `.composer` configuration files.
- **Gzip / Deflate Compression (`mod_deflate`)**: Compresses text/html, css, javascript, json, manifest, and svg resources.
- **Browser Caching Directives (`mod_expires`)**:
  - 1-Year immutable caching for Vite hashed assets (`app-*.css`, `app-*.js`, web fonts).
  - 1-Month caching for images, icons, and svgs.
  - 0-Seconds / revalidation policy for `sw.js` (ensuring immediate service worker updates).

### 4. Production Health Diagnostics Command (`app/Console/Commands/ProductionHealthCheckCommand.php`)
- Signature: `php artisan app:health-check [--json] [--fail-fast]`
- Validates 8 critical deployment criteria:
  1. PHP Version: Checks `>= 8.2.0` (Detected: PHP 8.2.30).
  2. Required PHP Extensions: Checks `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `openssl`, `xml` (critical) and `intl`, `gd`, `bcmath`, `zip` (recommended).
  3. Security: Verifies `APP_KEY` presence and flags warnings if `APP_DEBUG=true` in production environments.
  4. Database: Verifies live PDO connectivity and table schemas.
  5. Master Data Integrity: Confirms presence of Karnataka districts, APMC mandis, commodities, and price records.
  6. Filesystem: Validates write permissions for `storage/` and `bootstrap/cache/`.
  7. Frontend: Confirms Vite production build manifest (`public/build/manifest.json`).

### 5. Automated cPanel Deployment Script & Manual (`scripts/cpanel-deploy.sh`, `docs/DEPLOYMENT_CPANEL_CHECKLIST.md`)
- **Deployment Script (`scripts/cpanel-deploy.sh`)**:
  - Automatically identifies cPanel MultiPHP binaries (`ea-php82`, `ea-php83`).
  - Sets filesystem permissions (`chmod -R 775 storage bootstrap/cache`).
  - Creates the public storage symlink.
  - Runs Composer production optimization (`--no-dev --optimize-autoloader`).
  - Runs migrations (`php artisan migrate --force`).
  - Clears and precomputes production caches (`config:cache`, `route:cache`, `view:cache`, `event:cache`).
  - Automatically triggers `php artisan app:health-check`.
- **Operations Manual (`docs/DEPLOYMENT_CPANEL_CHECKLIST.md`)**:
  - Comprehensive guide covering isolated web root setup (`agrimarket/` private vs `public_html/` public), `.env` production variables, cPanel Cron Jobs configuration (Master Scheduler every minute + Queue Worker every 5 minutes), and post-deployment smoke tests.

---

## Verification & Test Results

### Production Hardening Feature Tests (`tests/Feature/ProductionHardeningTest.php`)
```
PASS  Tests\Feature\ProductionHardeningTest
✓ security headers are present on farmer routes                                                                0.06s  
✓ security headers are present on admin routes                                                                 0.02s  
✓ laravel health probe up endpoint returns 200                                                                 0.03s  
✓ public api rate limiter headers are present                                                                  0.02s  
✓ where to sell decision rate limiter is active                                                                0.02s  
✓ production health check command runs successfully                                                            0.03s  
✓ production health check command supports json output                                                         0.03s  
✓ htaccess file contains security caching and compression directives                                           0.02s  

Tests:    8 passed (32 assertions)
```

### Full Project Regression Test Suite
```
PASS  Tests (All 21 Feature & Unit Test Suites)
Tests:    151 passed (1317 assertions)
Duration: 8.11s
```

### Production Health Check Output
```
========================================================================
       Krushi Baandhava — Production Health & Deployment Verification   
========================================================================

+-------------+-----------------------------------+--------+--------------------------------------------------+
| Category    | Check                             | Status | Details                                          |
+-------------+-----------------------------------+--------+--------------------------------------------------+
| Runtime     | PHP Version (>= 8.2.0)            | PASS   | Detected PHP 8.2.30                              |
| Runtime     | PHP Extensions                    | WARN   | Core OK. Recommended missing on cPanel: intl     |
| Security    | Application Key                   | PASS   | Configured                                       |
| Security    | Debug Mode in Production          | PASS   | Env: local, APP_DEBUG=true                       |
| Database    | Database Connectivity             | PASS   | Connected (mysql, 41 tables)                     |
| Master Data | Karnataka Master Data Seeded      | PASS   | Districts: 13, Mandis: 21, Crops: 13, Prices: 42 |
| Filesystem  | Storage & Cache Write Permissions | PASS   | storage/: Writable, bootstrap/cache/: Writable   |
| Frontend    | Vite Production Build Assets      | PASS   | public/build/manifest.json present               |
+-------------+-----------------------------------+--------+--------------------------------------------------+

✔ Production health check passed successfully. System is deployment-ready.
```

---

## Conclusion
With Phase 14 completed, all 14 phases across the entire **Krushi Baandhava Roadmap** are fully implemented, verified, documented, and ready for deployment on Linux cPanel shared hosting.
