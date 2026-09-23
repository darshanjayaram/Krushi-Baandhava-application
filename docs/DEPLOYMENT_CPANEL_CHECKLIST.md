# Krushi Baandhava — cPanel Production Deployment Checklist & Operations Manual

This document provides a verified, production-grade deployment checklist for hosting **Krushi Baandhava** on Linux cPanel shared hosting or VPS environments.

---

## 1. Hosting Environment Prerequisites

- **cPanel**: Version 110+ (CentOS / CloudLinux / AlmaLinux / Ubuntu)
- **PHP**: 8.2 or 8.3 via **MultiPHP Manager**
- **Web Server**: Apache 2.4+ with `mod_rewrite`, `mod_deflate`, `mod_expires`, `mod_headers`
- **Database**: MySQL 8.0+ or MariaDB 10.5+ with `utf8mb4_unicode_ci`
- **SSL**: Valid HTTPS certificate (cPanel AutoSSL, Sectigo, or Let's Encrypt)

---

## 2. PHP Extension Checklist

In cPanel **Select PHP Version** (or **MultiPHP INI Editor**), ensure these extensions are enabled for PHP 8.2/8.3:

| Extension | Required / Recommended | Purpose |
| :--- | :--- | :--- |
| `pdo_mysql` | **Required** | Eloquent ORM & MySQL database connection |
| `mbstring` | **Required** | Kannada UTF-8 unicode multi-byte string processing |
| `curl` | **Required** | data.gov.in & Open-Meteo HTTP API integration |
| `fileinfo` | **Required** | CMS image upload MIME validation |
| `openssl` | **Required** | Secure password hashing & HTTPS cryptography |
| `xml` | **Required** | Dynamic XML sitemap generation & DOM parsing |
| `intl` | **Recommended** | NumberFormatter (INR Currency ₹ formatting) |
| `gd` | **Recommended** | Image thumbnail generation for news and articles |
| `bcmath` | **Recommended** | High-precision forecasting coordinate math |
| `zip` | **Recommended** | Backup archives & Composer operations |

---

## 3. Directory Layout & Web Root Isolation

For security, the Laravel core **must never** be exposed inside `public_html`.

```text
/home/USERNAME/
├── agrimarket/                  <-- Core Laravel Application (Strictly Private)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   └── .env
│
└── public_html/                 <-- Public Web Root (Served to Internet)
    ├── build/                   <-- Compiled assets from Vite
    ├── icons/                   <-- PWA icons (192, 512)
    ├── storage/                 <-- Symlink to /home/USERNAME/agrimarket/storage/app/public
    ├── index.php                <-- Front controller pointing to ../agrimarket/
    ├── manifest.json            <-- PWA Manifest
    ├── sw.js                    <-- Service Worker
    ├── robots.txt               <-- Crawler directives
    ├── favicon.ico
    └── .htaccess                <-- Apache rules, Gzip compression & headers
```

### Front Controller Configuration (`public_html/index.php`)
```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/../agrimarket/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader
require __DIR__.'/../agrimarket/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../agrimarket/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

---

## 4. Production `.env` Configuration

Configure `/home/USERNAME/agrimarket/.env`:

```ini
APP_NAME="Krushi Baandhava"
APP_ENV=production
APP_KEY=base64:...                      # Generate with: php artisan key:generate
APP_DEBUG=false                         # NEVER set to true in production!
APP_URL=https://krushibaandhava.org

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpaneluser_krushibaandhava
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD="your-strong-random-password"

BROADCAST_CONNECTION=log
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# data.gov.in API Configuration
DATAGOV_API_KEY="your-production-datagov-api-key"
DATAGOV_RESOURCE_ID="9ef84268-d588-465a-a308-a864a43d0070"

# Open-Meteo Weather
OPEN_METEO_BASE_URL="https://api.open-meteo.com/v1"
```

---

## 5. Storage Symlink & Permissions

In cPanel Terminal or SSH:
```bash
cd /home/USERNAME/agrimarket

# Set write permissions for web server
chmod -R 775 storage bootstrap/cache

# Create public storage symlink
ln -sfn /home/USERNAME/agrimarket/storage/app/public /home/USERNAME/public_html/storage
```

---

## 6. Pre-Deployment Asset Compilation

Shared cPanel hosts do not run Node.js. Always compile assets locally before deployment:
```bash
npm run build
```
Ensure the contents of `public/build/` are uploaded to `public_html/build/`.

---

## 7. Automated cPanel Deployment Execution

Execute the automated deployment script from Terminal:
```bash
bash /home/USERNAME/agrimarket/scripts/cpanel-deploy.sh
```

Or run manual optimization commands:
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan app:health-check
```

---

## 8. cPanel Cron Jobs Configuration

Navigate to cPanel **Cron Jobs** and configure the following 2 tasks:

### 1. Master Laravel Scheduler (Runs Every Minute)
- **Schedule**: `* * * * *` (Every minute)
- **Command**:
  ```bash
  /usr/local/bin/ea-php82 /home/USERNAME/agrimarket/artisan schedule:run >> /dev/null 2>&1
  ```
  *Handles daily mandi price synchronization, Open-Meteo weather updates, and nightly price forecasting automatically.*

### 2. Database Queue Worker (Runs Every 5 Minutes)
- **Schedule**: `*/5 * * * *` (Every 5 minutes)
- **Command**:
  ```bash
  /usr/local/bin/ea-php82 /home/USERNAME/agrimarket/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
  ```
  *Using `--stop-when-empty` ensures jobs are processed without running long persistent daemons that exceed cPanel process limits.*

---

## 9. Verification & Smoke Test Checklist

- [ ] `GET /up` returns HTTP 200 OK (Laravel health probe)
- [ ] `GET /` loads within 1.2s without JavaScript console errors
- [ ] Response headers include `X-Frame-Options: SAMEORIGIN` and `X-Content-Type-Options: nosniff`
- [ ] `GET /manifest.json` returns valid JSON with `display: standalone`
- [ ] `GET /sitemap.xml` returns valid XML with active commodity and mandi URLs
- [ ] `GET /offline` returns the bilingual offline fallback screen
- [ ] `php artisan app:health-check` displays 100% `PASS` / `WARN` without any `FAIL`
- [ ] Admin login at `/admin/login` works with rate limiting (5 attempts/min)
