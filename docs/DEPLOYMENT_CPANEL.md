# Krushi Baandhava — Linux cPanel Shared Hosting Deployment Guide

## 1. Directory Structure & Web Root Isolation

For security on shared hosting, the Laravel project core must **never** be placed inside `public_html`. The web root must point strictly to Laravel's `public/` directory to prevent `.env`, source code, and log disclosure.

### Recommended Filesystem Layout

```text
/home/USERNAME/
├── agrimarket/                  # Core Laravel Application (Private)
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
└── public_html/                 # Public Web Root (Directly exposed)
    ├── build/                   # Compiled CSS/JS from Vite
    ├── icons/                   # PWA Icons
    ├── index.php                # Front controller
    ├── manifest.json            # PWA Web App Manifest
    ├── sw.js                    # Service Worker
    ├── robots.txt
    ├── favicon.ico
    └── .htaccess                # Apache rewrite rules
```

### Front Controller (`public_html/index.php`) Setup
Update the vendor and bootstrap paths in `public_html/index.php`:
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

## 2. Environment & MultiPHP Configuration

1. **PHP Version**: In cPanel, navigate to **MultiPHP Manager** and select **PHP 8.2** or **PHP 8.3** for the domain.
2. **PHP Extensions**: In **Select PHP Version** (or MultiPHP INI), ensure the following are enabled:
   - `bcmath`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `xml`, `zip`.
3. **Database Configuration**:
   - Create a MySQL database and user in **MySQL Databases**.
   - Grant `ALL PRIVILEGES` to the user on the database.
   - Update `/home/USERNAME/agrimarket/.env`:
     ```env
     APP_NAME="Krushi Baandhava"
     APP_ENV=production
     APP_DEBUG=false
     APP_URL=https://yourdomain.com

     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=cpaneluser_krushibaandhava
     DB_USERNAME=cpaneluser_dbuser
     DB_PASSWORD="your-strong-password"

     QUEUE_CONNECTION=database
     CACHE_STORE=file
     SESSION_DRIVER=file
     ```

---

## 3. Storage Permissions & Symlink

From the cPanel Terminal or SSH:
```bash
cd /home/USERNAME/agrimarket
chmod -R 775 storage bootstrap/cache

# Link storage directory to public_html/storage
ln -s /home/USERNAME/agrimarket/storage/app/public /home/USERNAME/public_html/storage
```

---

## 4. cPanel Cron Jobs Configuration

Set up the following two cron jobs in cPanel **Cron Jobs**:

### 1. Laravel Scheduler (Runs Every Minute)
- **Schedule**: `* * * * *`
- **Command**:
  ```bash
  /usr/local/bin/php /home/USERNAME/agrimarket/artisan schedule:run >> /dev/null 2>&1
  ```
  *(Note: Verify exact PHP path in terminal using `which php` or `ea-php82` / `ea-php83` path).*

### 2. Database Queue Worker (Runs Every 5 Minutes)
- **Schedule**: `*/5 * * * *`
- **Command**:
  ```bash
  /usr/local/bin/php /home/USERNAME/agrimarket/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
  ```
  *Using `--stop-when-empty` ensures processes exit naturally after processing pending jobs, avoiding cPanel kill thresholds.*

---

## 5. Deployment Build Workflow

Because shared hosting typically lacks Node.js build tooling:
1. Run `npm run build` locally or in GitHub Actions CI.
2. Ensure the generated assets in `public/build/` are committed or uploaded to `public_html/build/`.
3. Run migrations and cache configuration:
   ```bash
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
