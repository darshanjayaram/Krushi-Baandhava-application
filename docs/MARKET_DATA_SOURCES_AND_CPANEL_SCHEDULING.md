# Market Data Sources, Sirsi/Tumakuru Integration & cPanel Cron Scheduling

This document details the multi-source agricultural data architecture, the integration of **Sirsi APMC (TSS)** and **Tumakuru APMC** for Arecanut, the **Admin Configurable Auto Price Sync Scheduling**, and the **cPanel Cron Job Setup & Live Heartbeat Monitoring** subsystem.

---

## 1. Multi-Source Agricultural Data Architecture

Krushi Baandhava employs a **Hybrid Data Ingestion Model** combining official government open data portals, state agricultural marketing boards, commodity boards, and cooperative auction tender systems:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              KRUSHI BAANDHAVA INGESTION PIPELINE                      │
├────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                        │
│  [data.gov.in / OGD Platform] ──► APMC Daily Mandi Spot Rates (Modal, Min, Max, Qty)   │
│  [CEDA Agmarknet (Ashoka)]    ──► Authenticated REST API (Daily, Time-Series & Trends) │
│  [TSS Sirsi (tssindia.in)]    ──► Cooperative Tender Auction Grades (Sirsi Arecanut)   │
│  [Coffee Board of India]      ──► Daily Spot Arabica & Robusta Raw Prices Scraper     │
│  [Coconut Development Board]  ──► Ball Copra, Milling Copra & Tender Coconut Scraper   │
│                                                                                        │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### Clean, Active Data Sources (5 Providers)

| Data Source | Type | Organization | Official Portal URL | Use Case in Project |
| :--- | :--- | :--- | :--- | :--- |
| **`data_gov_mandi`** | Government REST API | Ministry of Agriculture, GoI | [data.gov.in](https://data.gov.in/) | Primary daily arrival and modal spot prices for general APMCs. |
| **`ceda_agmarknet`** | Authenticated REST API | Centre for Economic Data & Analysis (Ashoka Univ) | [api.ceda.ashoka.edu.in](https://api.ceda.ashoka.edu.in/) | National Agmarknet verified REST API for Karnataka mandis & time-series. |
| **`tss_sirsi`** | Cooperative Society Scraper | The Totgars' Co-operative Sale Society Ltd. | [tssindia.in](https://tssindia.in/) | Real daily tender auction results for Sirsi Arecanut 5 sub-grades. |
| **`coffee_board`** | Statutory Commodity Board Scraper | Ministry of Commerce & Industry | [coffeeboard.gov.in](https://coffeeboard.gov.in/) | Daily Arabica / Robusta spot prices (APMCs don't trade coffee). |
| **`coconut_board`** | Statutory Board Scraper | Ministry of Agriculture, GoI | [coconutboard.gov.in](https://coconutboard.gov.in/) | Daily Copra and Coconut reference rates across Karnataka. |

*(Note: Legacy dummy entries `krama` [state access placeholder with no API] and `agmarknet` [unauthenticated dummy endpoint superseded by CEDA Agmarknet REST API] were cleaned up).*

---

## 2. Sirsi & Tumakuru Arecanut Integration

### Why Sirsi Uses TSS Cooperative Auction Data
In Sirsi (Uttara Kannada district), standard open APMC auctions handle very little volume. Over 60% of all arecanut is auctioned daily through **The Totgars' Co-operative Sale Society (TSS Sirsi)** in the New Market Yard, Sirsi. TSS Sirsi:
- Was established in 1923 and is one of Asia's largest farmer cooperatives (49,000+ members, ₹1,650+ Cr turnover).
- Is the registered proprietor of the official **Geographical Indication (GI Tag)** for **"Sirsi Supari"**.
- Conducts daily computerized tender auctions across 5 authentic trade grades:
  1. **Rashi (ರಾಶಿ):** Top red arecanut.
  2. **Chali (ಚಾಲಿ):** White sundried arecanut.
  3. **Bette (ಬೆಟ್ಟೆ):** Semi-ripe sorted betelnut.
  4. **Bilegotu (ಬಿಳೆಗೋಟು):** Unhusked white gotu.
  5. **Kempugotu (ಕೆಂಪುಗೋಟು):** Unhusked red gotu.

### 4-Market Display on the Frontend (`/crop/1`)
When viewing Arecanut on the farmer interface (`/crop/1` or `/crop/arecanut`):
- The **"VIEW DIFFERENT MARKET"** section presents all 4 major mandis with real spot rates directly on the pills:
  1. **SAGAR:** ₹47,669 (Rashi)
  2. **CHANNAGIRI:** ₹45,585 (Rashi)
  3. **SIRSI:** ₹46,024 (Rashi)
  4. **★ TUMAKURU:** ₹47,500 (Rashi • Nearest Market highlighted based on user GPS/district)
- Selecting **SIRSI** (`?market=SIRSI`) displays the **"PICK YOUR GRADE"** strip with all 5 TSS sub-grades (*Rashi ₹46,024, Chali ₹44,599, Bette ₹37,691, Bilegotu ₹27,373, Kempugotu ₹25,892*), matching the exact design and precision of Negilu Krishi.

---

## 3. Admin Configurable Auto Price Sync Scheduling

In the Admin Panel (`/admin/datasources/{id}/edit`), administrators have granular scheduling controls:

### Supported Frequency Options
- **Twice Daily (Morning & Evening - Recommended for Mandis):**
  - Slot 1: `06:00 IST` (Early arrivals and opening bids).
  - Slot 2: `18:00 IST` (Closing modal rates and daily cooperative auction summaries).
- **Once Daily (Pick Time):** Configurable to any specific 24-hour time (e.g. `17:30 IST`).
- **Interval-Based:** `Every 2 Hours`, `Every 6 Hours`, `Every 12 Hours`, or `Hourly`.
- **Operating Days Selector:**
  - `Mon – Sat (Skip Sunday Mandi Holiday)`: Automatically pauses on Sundays because Karnataka APMCs are closed.
  - `All 7 Days`: For weather and 24/7 feeds.
- **Custom Cron Override:** Allows advanced users to enter custom standard cron expressions (e.g., `0 6,18 * * 1-6`).

### Dynamic Scheduler Evaluation (`isDue()`)
Laravel’s scheduled sync command (`krushi:sync-market-prices`) runs every hour / on scheduled ticks. It executes `DataSource::isDue()` for each active provider:
- Verifies day-of-week against `sync_days`.
- Evaluates configured `sync_time` windows (within a ±45-minute window of the target time).
- Prevents re-fetching if a successful sync has already run within that window.
- Updates `$source->last_heartbeat_at = now()` and sets the global scheduler cache key.

---

## 4. cPanel Cron Job Setup & Live Health Monitor

On `/admin/datasources`, administrators see the **"Automated Scheduling & cPanel Cron Assistant"** widget:

### Ready-to-Copy cPanel Cron Command
```bash
* * * * * /usr/local/bin/php /home/username/public_html/artisan schedule:run >/dev/null 2>&1
```

### Automatic System Path Detection
- **Detected PHP Binary:** Automatically populated from PHP runtime (`PHP_BINARY` or `/usr/local/bin/php` on cPanel/CloudLinux).
- **Project Base Path:** Automatically populated from Laravel's `base_path()`.
- **One-Click Clipboard Copy:** Instant copy with visual checkmark feedback.

### Live Heartbeat Monitor
- **🟢 Cron Active:** Displayed when the scheduler has ticked within the last 15 minutes (with relative time, e.g. *"Last tick: 42 seconds ago"*).
- **🔴 Cron Inactive / Not Set:** Warning shown if no scheduler heartbeat has been recorded in the past 15 minutes, reminding the administrator to add the cron job in cPanel.

### Step-by-Step cPanel Configuration
1. Log into your hosting account's **cPanel** dashboard.
2. Scroll to the **Advanced** section and click **Cron Jobs**.
3. Under **Add New Cron Job**:
   - In **Common Settings**, select **Once Per Minute (* * * * *)**.
   - In the **Command** field, paste the copied command from Krushi Baandhava Admin.
   - Click **Add New Cron Job**.
4. Once added, return to `/admin/datasources` — within 60 seconds, the heartbeat badge will turn **🟢 Cron Active**. You never need to touch cPanel again; any frequency changes made in the Admin Panel will be picked up automatically!
