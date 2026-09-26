# Krushi Baandhava — cPanel Fresh Deployment & Master Setup Guide

This guide outlines the end-to-end procedure for deploying **Krushi Baandhava** to a fresh cPanel hosting account with an empty MySQL database, zero manual entry of mandis or crops, and automatic security lockdown.

---

## 1. Prerequisites on cPanel
Ensure your cPanel hosting account provides:
- **PHP 8.2 or 8.3** (Select via `cPanel > Select PHP Version`).
- Required PHP Extensions enabled:
  - `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `fileinfo`.
- **MySQL Database & User** created via `cPanel > MySQL® Databases`.

---

## 2. Step 1: Upload Application Code

1. Compress your project into a `.zip` archive (excluding `/vendor` if you run `composer install` via SSH, or including `/vendor` for shared hosting without composer).
2. In cPanel, navigate to **File Manager** $\rightarrow$ `public_html` (or your subdomain directory).
3. Upload and extract the archive.
4. **Document Root Configuration:**
   - Ensure your domain or subdomain's Document Root points to the `public_html/public` directory (or configure `.htaccess` in root if pointing directly to `public_html`).

---

## 3. Step 2: Configure Environment (`.env`)

1. In cPanel File Manager, find `.env.example` in the project root and copy/rename it to `.env`.
2. Edit `.env` to input your MySQL credentials created in cPanel:
   ```env
   APP_NAME="Krushi Baandhava"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=yourcpaneluser_krushidb
   DB_USERNAME=yourcpaneluser_dbuser
   DB_PASSWORD=your_strong_database_password

   DATA_GOV_IN_API_KEY=your_ogd_india_api_key_here
   ```

---

## 4. Step 3: Run the 4-Step Web Setup Wizard (Zero-Terminal)

Open your web browser and navigate to:
```
https://yourdomain.com/setup
```

The interactive 4-step wizard will guide you through:
1. **Step 1: Database & Server Diagnostics**:
   - Verify PHP $\ge 8.2$ and required extensions.
   - Click **"Test MySQL Database Connection"** to verify connection to your cPanel database in real-time.
2. **Step 2: Create Super Administrator Account**:
   - Enter your personal Name, Admin Email, and choose a strong password (minimum 8 characters).
3. **Step 3: Master Data Selection (Zero Manual Entry)**:
   - Keep **"Karnataka State APMC Directory"** checked (provisions all 31 districts and 68 mandis). Click *Preview Verified Mandis* to inspect.
   - Keep **"Karnataka Core Agricultural Commodities"** checked (provisions 19 core crops with varieties and data.gov.in alias mappings). Click *Preview Commodities* to inspect.
   - Enter your `data.gov.in` API key (optional) and click *Test API Key*.
   - Keep *"Fetch today's live APMC prices right away"* checked.
4. **Step 4: Review & Initialize**:
   - Click **"Complete Installation & Lock Setup Wizard"**.
   - The installer runs migrations, seeds all master entities, hashes your admin password, ingests today's prices, and permanently writes `storage/installed`.

---

## 5. Security Lockdown

* Once installation finishes, visiting `https://yourdomain.com/setup` will **never display the installation form again**.
* Visitors will see a secure locked screen: **"Setup Wizard is Locked — Krushi Baandhava has already been installed and secured."**
* Any automated or malicious `POST` requests to `/setup` are immediately rejected with **`403 Forbidden`**.

---

## 6. Step 4: Configure Daily cPanel Cron Job

To keep APMC mandi prices automatically updated every day:
1. In cPanel, navigate to **Cron Jobs**.
2. Under **Add New Cron Job**, set the interval:
   - E.g. **Once per day at 6:30 AM** (`30 6 * * *`) or **7:00 PM** (`0 19 * * *`).
3. Set the command:
   ```bash
   /usr/local/bin/php /home/yourcpanelusername/public_html/artisan schedule:run >> /dev/null 2>&1
   ```
   *(Replace `/home/yourcpanelusername/public_html` with your actual cPanel home directory path).*

---

## 7. Ongoing Management: Admin Deployment & APMC Hub

Whenever you want to scan for new mandis or newly introduced crops:
1. Log in to the Admin Panel: `https://yourdomain.com/admin/login`.
2. In the left navigation, click **Deployment & APMC Hub** (`/admin/deployment-hub`).
3. You have 4 one-click actions:
   - **Scan for Mandis:** Queries `data.gov.in` for newly reporting Karnataka APMCs and registers them automatically.
   - **Scan for Crops:** Queries live feeds for newly reported commodities and generates aliases.
   - **Sync Directory:** Re-synchronizes standard Karnataka master directories cleanly with zero duplicate collisions.
   - **Sync Rates Now:** Manually triggers immediate live price ingestion from `data.gov.in` or `CEDA Agmarknet`.
