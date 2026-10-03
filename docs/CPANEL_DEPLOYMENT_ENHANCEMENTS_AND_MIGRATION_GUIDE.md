# Krushi Baandhava — cPanel Production Deployment Guide (Post-Enhancements)

**Document:** `docs/CPANEL_DEPLOYMENT_ENHANCEMENTS_AND_MIGRATION_GUIDE.md`  
**Updated:** October 2026  
**Scope:** Covers deploying the enhanced application with 100% KRAMA alignment, 170+ standardized APMCs, hyperlocal weather detection, async variety management, and automated daily data auditing.

---

## 1. Summary of New Enhancements Included in this Deployment

1. **100% KRAMA Alignment & Zero Ghost Mandis:**
   - 18 new APMC markets registered (Afzalpur, Aland, Bilagi, Devadurga, Indi, Humnabad, KGF, Kudligi, Kunigal, Madhugiri, Mahalingpur, Mudhol, Pavagada, Rampura, Sandur, Shahapur, Shorapur, Arsikere APMC).
   - Purged all ghost records from Chikkaballapura; reconciled historical Sona Masuri Paddy records.
2. **Hardened Ingestion Engine:**
   - Levenshtein string distance gatekeeper (`isPlausibleMarketMatch`) to prevent cross-mandi misattributions.
   - Zero-guess variety fallback (`getGenericVarietyForCrop` instead of blind cultivar guessing).
3. **Automated Production Data Integrity Auditor:**
   - New console command: `php artisan data:audit-integrity`.
   - Pre-configured in `routes/console.php` to run nightly at 20:30 IST with `--fix` auto-reconciliation.
4. **Hyperlocal Weather & GPS Geocoding:**
   - Header location picker with automatic reverse geocoding via Open-Meteo & Nominatim with 15-minute coordinate TTL caching.
5. **Async Variety Management in Admin Panel:**
   - AJAX-powered instant variety toggling and sorting without page reloads.

---

## 2. Pre-Deployment Preparation Checklist

### A. Database Strategy (Critical)
> [!IMPORTANT]
> **Do NOT deploy an empty database and run fresh migrations/seeders from scratch!**  
> Your local database contains **229,198+ authentic KRAMA historical price records**, training models, and reconciled variety mappings.
> 
> **Recommended Database Migration Path:**
> 1. Export your local MySQL database via phpMyAdmin or command line:
>    ```bash
>    mysqldump -u root -p krushi_baandhava > krushi_baandhava_production.sql
>    ```
> 2. Compress into a `.zip` archive.
> 3. In cPanel, create a new MySQL Database (e.g. `cpaneluser_krushi`) and User with full permissions.
> 4. In cPanel phpMyAdmin, import `krushi_baandhava_production.sql`.

---

## 3. Step-by-Step cPanel Deployment Procedure

### Step 1: Configure PHP Version & Extensions
1. In cPanel, go to **Select PHP Version** (or **MultiPHP Manager**).
2. Set version to **PHP 8.2** or **PHP 8.3**.
3. Under **PHP Extensions / Options**, ensure the following are enabled:
   - `pdo_mysql`
   - `curl`
   - `mbstring`
   - `fileinfo`
   - `openssl`
   - `xml`
   - `bcmath`
   - `intl`
   - `zip`
4. Recommended PHP settings:
   - `memory_limit`: `256M` or `512M`
   - `max_execution_time`: `120`
   - `upload_max_filesize`: `32M`

---

### Step 2: Upload Application Files
1. Zip the project root. You may exclude:
   - `/node_modules`
   - `/tests`
   - `.git`
   - Temporary scratch files
2. In cPanel **File Manager**, upload the archive to your desired folder (e.g., `/home/username/public_html` or `/home/username/krushi`).
3. Extract the archive.
4. **Document Root Setup:**
   - In cPanel **Domains** (or **Subdomains**), ensure the Document Root points to the `public/` folder:
     `public_html/public` (or `/home/username/krushi/public`).
   - If your main domain Document Root cannot be changed, ensure the root `.htaccess` redirects requests into `/public`.

---

### Step 3: Configure `.env` on cPanel
Create or update `.env` in the project root:
```env
APP_NAME="Krushi Baandhava"
APP_ENV=production
APP_KEY=base64:... (keep your existing key or generate one)
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yourcpaneluser_krushidb
DB_USERNAME=yourcpaneluser_dbuser
DB_PASSWORD="your_strong_password"

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

DATA_GOV_IN_API_KEY=your_ogd_india_api_key_here
```

---

### Step 4: Storage Permissions & Symlink
In cPanel **Terminal** (SSH) or File Manager:
1. Ensure write permissions for storage and cache:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
2. Create storage symlink for uploaded media/avatars:
   ```bash
   php artisan storage:link
   ```

---

### Step 5: Run Production Caching & Verification
In cPanel Terminal:
```bash
# 1. Clear any stale caches
php artisan optimize:clear

# 2. Cache configuration, routes, and views for maximum speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Verify database integrity
php artisan data:audit-integrity --days=3
```
Expected output:
```
=== AUDIT SUMMARY ===
Records Audited:    1361+
Market Discrepancies: 0 (Clean)
Variety Discrepancies: 0 (Clean)
Price Discrepancies:  0 (Clean)
✅ SUCCESS: 100% 1-to-1 data fidelity verified. Zero discrepancies found!
```

---

### Step 6: Configure the Single Master cPanel Cron Job
In cPanel, open **Cron Jobs**. Under **Add New Cron Job**:
- **Timing:** Every minute (`* * * * *`)
- **Command:**
  ```bash
  /usr/local/bin/php /home/yourcpaneluser/public_html/artisan schedule:run >> /dev/null 2>&1
  ```
  *(Replace `/home/yourcpaneluser/public_html` with your actual cPanel home path, and verify PHP path via `which php`).*

#### What this Cron Job handles automatically:
| Task | Frequency / Time | Action |
| :--- | :--- | :--- |
| **Scheduler Heartbeat** | Every minute | Updates live heartbeat in Admin Panel health monitor |
| **KRAMA Ingestion (Morning)** | Daily at 06:00 | Syncs early morning arrival data |
| **KRAMA Ingestion (Afternoon)** | Daily at 12:30 | Syncs mid-day auction updates |
| **KRAMA Ingestion (Evening)** | Daily at 19:30 | Captures final daily auction summaries |
| **Data Integrity Auditor** | Daily at 20:30 | Runs `data:audit-integrity --days=3 --fix` to verify 100% 1:1 match and auto-reconcile |
| **Historical Analytics** | Daily at 01:00 | Calculates monthly seasonal price trends |
| **Forecast Engine** | Daily at 02:00 | Computes 1D, 7D, 15D, 30D Holt's Linear projections |
| **Weather Cache Pruner** | Daily at 03:30 | Prunes expired forecast records |
| **Rolling Retention** | Daily at 23:00 | Retains 365-day rolling window to keep DB lean (~35MB) |

---

### Step 7: Firewall & Outbound Connectivity Verification
Ensure cPanel's outgoing firewall (CSF/ConfigServer) allows outbound HTTP/HTTPS port 443 requests to:
1. `krama.karnataka.gov.in` (KRAMA APMC price scraper)
2. `api.agmarknet.gov.in` (Agmarknet national data)
3. `api.open-meteo.com` & `geocoding-api.open-meteo.com` (Hyperlocal weather & GPS reverse geocoding)
4. `nominatim.openstreetmap.org` (Fallback reverse geocoding)

---

## 4. Post-Deployment Verification Smoke Tests

1. **Farmer Home Page:** Visit `https://yourdomain.com/` — check crops, mandi prices, and weather widget.
2. **Hyperlocal Location Modal:** Click the location pin in header $\rightarrow$ Click "Detect My Location" or select taluk $\rightarrow$ verify weather updates for the selected location.
3. **Paddy Crop Page:** Visit `https://yourdomain.com/crops/paddy` $\rightarrow$ Confirm that Chikkaballapura **does not** appear falsely for Paddy on 25 & 26 Sep.
4. **Admin Panel:** Visit `https://yourdomain.com/admin/login` $\rightarrow$ Check **Data Sources** and **Deployment Hub**.
5. **Terminal Sanity Check:** Run `php artisan data:audit-integrity --days=3` $\rightarrow$ Verify `0 Discrepancies`.
