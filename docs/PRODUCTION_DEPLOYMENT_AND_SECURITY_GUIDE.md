# Krushi Baandhava — Production Deployment & Security Lockdown Guide

This guide provides instructions for deploying Krushi Baandhava into production (cPanel, VPS, AWS, Cloud VM, Docker) with an existing database, ensuring the setup wizard is permanently locked and all security features are operational.

---

## 1. Setup Wizard: Disabling & Locking Manually

If you are deploying Krushi Baandhava directly with an existing database dump (e.g. imported via phpMyAdmin or MySQL CLI), you must ensure that the `/setup` wizard cannot be accessed by anyone.

Krushi Baandhava now has **4-tier automatic & manual protection** against setup wizard tampering:

### Tier 1: Environment Variable Lock (Primary Method)
In your production `.env` file, set:
```dotenv
APP_INSTALLED=true
ENABLE_SETUP_WIZARD=false
```
When these are set, any request to `/setup`, `/setup/test-db`, `/setup/test-api`, or `POST /setup` is instantly blocked with **HTTP 403 Forbidden** and the encrypted security shield screen.

### Tier 2: Storage Lockfile (File-Based Lock)
The system checks for the presence of the `storage/installed` file.
- **Using cPanel File Manager (No SSH required):**
  1. Open cPanel -> **File Manager**.
  2. Navigate into your project folder -> `storage/`.
  3. Click **+ File** (New File) in the top toolbar.
  4. Name the file `installed` (exactly `installed`, no file extension).
  5. Click **Create New File**. You can leave it empty or right-click -> **Edit** and paste `{"status":"locked"}`.
- **Using Linux / macOS Terminal (if available):**
  ```bash
  touch storage/installed
  ```

### Tier 3: Database Auto-Detection (Automatic Fail-Safe)
Even if you **forget** to set `.env` and **forget** to create `storage/installed`:
- When any request hits `/setup`, the application queries your database.
- If it detects existing users (`role != farmer`) OR existing agricultural data (`districts > 0` and `crops > 0`), it automatically marks the system as an **active deployment**.
- It **automatically creates** `storage/installed` on disk for you, writes `APP_INSTALLED=true` and `ENABLE_SETUP_WIZARD=false` to `.env`, and returns **HTTP 403 Forbidden**.
- An attacker can **never** trigger re-installation, run seeders, or overwrite Super Admin credentials.

### Tier 4: Production Environment Guard
In `APP_ENV=production`, the setup wizard is strictly disabled by default unless explicitly overridden by `ENABLE_SETUP_WIZARD=true`.

---

## 2. Using Existing Database in Deployment (cPanel GUI / No SSH Access)

Here is how to manage everything via cPanel web tools and the Krushi Baandhava Admin UI without needing SSH, bash, or PowerShell:

### Step 1: Upload Application Files
- In cPanel -> **File Manager**, upload your project zip file to `public_html` (or your subdomain directory).
- Right-click the uploaded `.zip` and click **Extract**.

### Step 2: Configure `.env` in cPanel File Manager
1. In cPanel File Manager, click **Settings** (gear icon in the top right corner) and check **Show Hidden Files (dotfiles)**, then click Save.
2. Find the `.env` file (or copy `.env.example` to `.env`).
3. Right-click `.env` and select **Edit**.
4. Fill in your production MySQL database name, database user, and password:
   ```dotenv
   APP_NAME="Krushi Baandhava"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cpaneluser_krushibaandhava
   DB_USERNAME=cpaneluser_dbuser
   DB_PASSWORD="your_database_password"

   # Setup Wizard Permanent Lockdown
   APP_INSTALLED=true
   ENABLE_SETUP_WIZARD=false

   # HTTPS Session Security
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   SESSION_HTTP_ONLY=true
   SESSION_SAME_SITE=lax
   ```
5. Click **Save Changes**.

### Step 3: Import Your Database via phpMyAdmin
1. In cPanel, navigate to **Databases** -> **MySQL® Databases**:
   - Create a database (e.g. `cpaneluser_krushibaandhava`).
   - Create a user (e.g. `cpaneluser_dbuser`) and strong password.
   - Under **Add User to Database**, select both, click **Add**, check **ALL PRIVILEGES**, and click **Make Changes**.
2. Go back to cPanel home and open **phpMyAdmin**:
   - Click your newly created database in the left sidebar.
   - Click the **Import** tab at the top.
   - Click **Choose File** and select your `.sql` or compressed `.sql.gz` dump from your computer.
     *(Tip: Gzipping your SQL file reduces size by 85%, preventing upload timeouts on large price datasets).*
   - Scroll to the bottom and click **Import** (or **Go**).
   - phpMyAdmin will display a green banner confirming all tables and data were imported.

### Step 4: Run Migrations via One-Click Admin Panel UI
You do **not** need `php artisan migrate` in terminal!
1. Log in to your Admin Panel at `https://your-domain.com/admin/login` using your Super Admin email and password.
2. Go to **Settings** (or `/admin/settings`).
3. Right at the top, look at the **Database Schema** card.
4. Click the yellow **Update Database** button.
   - This executes pending migrations (`php artisan migrate --force`) and updates master seeds in the background.

### Step 5: Cache & Optimize for Production Speed via One-Click UI
You do **not** need `php artisan optimize` in terminal!
1. In **Admin Panel -> Settings** (`/admin/settings`), look at the top action cards:
   - **Production Speed** card: Click the green **Optimize for Production** button.
     - This automatically pre-compiles routes, configuration files, and views into cached files (`php artisan optimize`), making page loads lightning fast for farmers.
   - **Clear Caches** card: Click the **Clear All Caches** button whenever you change `.env` or branding settings to flush the compiled files.

---

## 3. Security Audit & Protection Features Implemented

The application includes enterprise-grade security controls:

| Security Feature | Implementation Detail | Status |
| :--- | :--- | :--- |
| **No Hardcoded Login Credentials** | Stripped demo emails and default passwords from `resources/views/admin/auth/login.blade.php`. Autocomplete and proper input types configured. | ✅ Active |
| **Brute-Force & Rate Limiting** | Dual-tier throttling: Route-level (`throttle:admin-login`) and Controller-level (`RateLimiter` per email+IP). Locks out after 5 consecutive failures for 60 seconds with live countdown timer. | ✅ Active |
| **Security Audit Logging** | All login events (`admin.login`), failed attempts (`admin.login_failed`), lockouts (`admin.login_throttled`), deactivated user attempts (`admin.deactivated_attempt`), and logouts (`admin.logout`) are logged to database `audit_logs` table with IP address and timestamps. | ✅ Active |
| **Session Fixation Defense** | Calls `$request->session()->regenerate()` on every successful login, and `$request->session()->invalidate()` + `$request->session()->regenerateToken()` on logout or failed privilege validation. | ✅ Active |
| **CSRF Protection** | Laravel CSRF token verification middleware enforced across all web forms (`@csrf`), preventing cross-site request forgery. | ✅ Active |
| **HTTP Security Headers** | Injected via `SecurityHeadersMiddleware`: <br>• `X-Frame-Options: SAMEORIGIN` (prevents Clickjacking) <br>• `X-Content-Type-Options: nosniff` (prevents MIME confusion) <br>• `X-XSS-Protection: 1; mode=block` <br>• `Referrer-Policy: strict-origin-when-cross-origin` <br>• `Permissions-Policy: camera=(), microphone=(self), payment=(), usb=(), display-capture=(), geolocation=(self)` <br>• `Content-Security-Policy` with white-listed script, font, image, and API domains. | ✅ Active |
| **Role-Based Access Control (RBAC)** | `EnsureUserHasRole` middleware enforces permissions. Sub-admins (Data, Content, Support, Forecast) are blocked from User Management and System Settings, which are restricted exclusively to `super_admin`. | ✅ Active |
| **Staff Deactivation Guard** | Inactive accounts (`is_active = false`) are immediately rejected at login even if their password is correct. | ✅ Active |
| **SQL Injection Defense** | All database queries utilize Eloquent ORM and PDO prepared statements with parameter binding. | ✅ Active |

---

## 4. Production Security Checklist Before Going Live

- [ ] `APP_DEBUG=false` in `.env` (Never leave `true` in production).
- [ ] `APP_ENV=production` in `.env`.
- [ ] `APP_INSTALLED=true` and `ENABLE_SETUP_WIZARD=false` in `.env`.
- [ ] `SESSION_SECURE_COOKIE=true` enabled (when SSL/HTTPS is active).
- [ ] `storage/installed` file exists.
- [ ] Super Admin password is changed from any default/development password.
- [ ] Storage directory permissions: `storage/` and `bootstrap/cache/` are writable (`chmod 775` on Linux).
- [ ] `.env` file permissions: Read-only for webserver (`chmod 600` or `chmod 640`).
