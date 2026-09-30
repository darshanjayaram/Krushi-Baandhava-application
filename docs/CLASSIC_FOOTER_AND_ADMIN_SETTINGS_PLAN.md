# Classic Agricultural Footer & Dedicated Admin CMS System

## 1. Overview & Architectural Philosophy
The objective of this module is to deliver a **compact, classic, and mobile-friendly footer** for the Krushi Baandhava Farmer PWA and Web Application that stands out with its own distinct identity.

Unlike bloated footers or flat text lists, Krushi Baandhava’s footer is designed as an **architecturally elevated, compact classic field card** that anchors the application, conveys institutional credibility, fosters farmer community engagement, and introduces **mobile-first accordion drawers** to keep the interface compact on smartphone screens.

Furthermore, all footer content, developer attribution, telemetry, and community links are managed via a **Dedicated Footer CMS Page (`/admin/footer`)** with live interactive preview capabilities.

---

## 2. Desktop 3-Column Compact Classic Specification

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  KRUSHI BAANDHAVA COMPACT CLASSIC CANVAS (Solid 2px Border #DDD3BE • Warm Earthy Card #FAF8F5)         │
├─────────────────────────────────────┬────────────────────────────────────┬─────────────────────────────┤
│ COLUMN 1: Brand & Telemetry (5 cols)│ COLUMN 2: Platform Hubs (4 cols)   │ COLUMN 3: Farmer Help (3 cols│
│                                     │                                    │                             │
│ [🌱 Logo] Krushi Baandhava          │ 📂 PLATFORM HUBS                   │ 🤝 COMMUNITY & HELP         │
│           ಕೃಷಿ ಬಾಂಧವ                │                                    │                             │
│ Karnataka Farmer Market Network     │ 📊 Daily Rates   📈 Forecasts      │ ┌─────────────────────────┐ │
│ Real-time APMC trading prices &     │ 🏛️ Govt Schemes  🌤️ Weather Radar   │ │ 💬 Join WhatsApp        │ │
│ predictive crop guidance.           │ ▶️ Agri Videos   📰 Agri News      │ │    Community →          │ │
│                                     │ 📖 Farming Guides (Handbook)       │ └─────────────────────────┘ │
│ 🟢 31 Districts • 160+ APMCs        │                                    │ ✉️ support@krushibaandhava  │
│ ⚡ Sourced from KRAMA & Agmarknet   │                                    │ ⚠️ Report Rate Discrepancy  │
│                                     │                                    │                             │
│ 🛡️ Notice: Indicative mandi prices. │                                    │                             │
├─────────────────────────────────────┴────────────────────────────────────┴─────────────────────────────┤
│ COMPACT CRAFTSMANSHIP & ATTRIBUTION BAR                                                                │
│ © 2026 Krushi Baandhava • [ 🛠️ Developed by Bharath M S ]        🌾 Made for Karnataka Farmers 🌱 🔐 Admin│
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Column 1: Institutional Credibility & Telemetry
- **Bilingual Branding**: Logo icon, English primary title (`Krushi Baandhava`), and Kannada native title (`ಕೃಷಿ ಬಾಂಧವ`).
- **Tagline**: Purpose-driven subtitle (`Karnataka Farmer Market Intelligence Network` / `ಕರ್ನಾಟಕದ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ ವೇದಿಕೆ`).
- **Live Coverage Pill**: `🟢 31 Districts • 160+ APMCs • Sourced from KRAMA & Agmarknet Feeds` with pulsing green dot.
- **Compliance & Disclaimer Notice**: Independent farmer platform disclaimer to ensure compliance.

### Column 2: Compact Platform Hubs Grid
- Always visible on desktop in a clean 2-column grid:
  1. `📊 Daily Mandi Rates` (`farmer.crops.index`)
  2. `📈 Price Projections & Forecasts`
  3. `🏛️ Govt Schemes & Subsidies` (`farmer.schemes.index`)
  4. `🌤️ Weather Radar & Rain Forecasts` (`farmer.weather.index`)
  5. `▶️ Educational Agri Videos` (`farmer.videos.index`)
  6. `📰 Agri News & Alerts` (`farmer.news.index`)
  7. `📖 Farming Guides & Handbook` (`farmer.articles.index`)

### Column 3: Rytha Community & Direct Helpdesk
- **Interactive WhatsApp CTA**: Sleek emerald card connecting farmers to statewide WhatsApp channels.
- **Support Email**: Direct contact link.
- **Mandi Rate Correction CTA**: Dedicated button for farmers to report discrepancies.

---

## 3. Mobile PWA Accordion Drawer Mechanism

On mobile viewports (`< 768px`):
- Column 1 stays at the top as the compact brand introduction.
- Columns 2 & 3 transform into **smooth, collapsible Alpine.js Accordion Drawers**:
  - `📂 Explore Platform Hubs (7)`
  - `🤝 Community & Support`
- Tap targets adhere to modern mobile standards (min 44px height).
- Bottom clearance (`pb-24`) ensures the footer never collides with the fixed 4-tab mobile navigation bar (`#mobileBottomNav`).

---

## 4. Dedicated Footer CMS (`/admin/footer`)

A dedicated CMS dashboard within Admin Panel (`Content Management (CMS) → Footer CMS`):
- **Section 1: Developer Attribution & Copyright**:
  - `footer_developer_name`: Developer Name (`Bharath M S`)
  - `footer_developer_url`: Developer Portfolio URL (`https://...`)
  - `footer_copyright_text`: Copyright notice
  - `footer_show_admin_link`: Toggle discreet admin lock link
- **Section 2: Bilingual Mission & Legal Disclaimers**:
  - `footer_tagline_en` / `footer_tagline_kn`: Subtitles
  - `footer_description_en` / `footer_description_kn`: Mission statement
  - `footer_disclaimer_en` / `footer_disclaimer_kn`: Legal notice
  - `footer_show_disclaimer`: Toggle disclaimer box
- **Section 3: Live APMC Mandi Feeds Badge**:
  - `footer_telemetry_badge`: e.g. "31 Districts • 160+ APMCs"
  - `footer_telemetry_source`: e.g. "Sourced from KRAMA & Agmarknet Feeds"
  - `footer_show_telemetry`: Toggle telemetry pill
- **Section 4: Rytha Community & Support**:
  - `footer_whatsapp_url`: Official WhatsApp channel invite
  - `footer_whatsapp_text`: Button label
  - `footer_support_email`: Support contact email
  - `footer_feedback_url`: Price discrepancy form link
- **Interactive Live Preview**:
  - Real-time side-by-side preview with Desktop / Mobile viewport toggles.
  - Updates as the user types before saving.
