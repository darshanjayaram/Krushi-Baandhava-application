# Dynamic Footer CMS & Accordion Engine — Implementation Plan

## 1. Overview & Objectives
This plan documents the **Content Management System (CMS)** for **Column 2 (Platform Hubs)** and **Column 3 (Community & Help)** of the Krushi Baandhava footer.

### Core Objectives:
1. **Dynamic Column Titles**: Rename Column 2 and Column 3 in both English and ಕನ್ನಡ (*ಕನ್ನಡ*).
2. **Top-to-Bottom 2-Column Grid (6-Item Threshold Rule)**:
   - **Column 1 (Left)** fills strictly from **Item #1 to Item #6** (top to bottom).
   - If there are 1 to 6 items, they stay purely in Column 1 (Left).
   - **Only after Column 1 is filled with 6 items**, subsequent items from **Item #7 onwards** overflow into **Column 2 (Right)** from top to bottom.
   - 12 comprehensive default agricultural links seeded to fill both columns (6 on Left, 6 on Right).
3. **Hide / Show Visibility Toggle**:
   - Each link item card in Admin has an interactive `👁️ Shown` / `🚫 Hidden` toggle button (`is_visible: true/false`).
   - Hidden items are immediately excluded from both the public footer (desktop & mobile accordion) and the live preview without having to delete them.
   - Individual card collapse/expand and global `Collapse All / Expand All` controls for compact CMS management.
4. **Button Style Selector (Iconic Green WhatsApp Button & Customs)**:
   - Ability to format any Column 3 action:
     - `🟢 Green CTA Button`: Forest green (`#1C5A2C`) button with custom icon (e.g. `💬`), bilingual text, optional subtitle (e.g. *Karnataka Rytha Channel*), and directional arrow `→`.
     - `🔗 Standard Link`: Clean agricultural text link with hover effect.
     - `🟡 Alert Highlight Pill`: Amber notice pill (e.g. "Report Mandi Rate Issue").
     - `✉️ Contact Chip`: White-bordered chip with subtle background (e.g. email or phone number).
5. **Preserve Mobile PWA Accordions**:
   - Mobile view (`< md`) maintains the compact touch accordion buttons.
   - Accordion triggers dynamically adopt the renamed column titles.
   - Drawers smoothly expand, looping over visible links.
6. **Real-Time Sticky Preview**: Instant reactive updates on keystroke and toggle in the admin dashboard (Desktop & Mobile modes).

---

## 2. Phase-by-Phase Roadmap & Status

### Phase 1: Database SystemSettings Schema & Default Seeds — [COMPLETED]
- Defined keys in `SystemSetting`:
  - `footer_col2_title_en` (Default: "Platform Hubs")
  - `footer_col2_title_kn` (Default: "ವೇದಿಕೆ ಕೇಂದ್ರಗಳು")
  - `footer_col2_links` (JSON array of 12 default platform links with `is_visible: true`)
  - `footer_col3_title_en` (Default: "Community & Help")
  - `footer_col3_title_kn` (Default: "ಸಂಪರ್ಕ & ಸಹಾಯ")
  - `footer_col3_links` (JSON array with WhatsApp button, email chip, and discrepancy alert)
- Seeded 12 comprehensive Karnataka agricultural links.

### Phase 2: Controller Engine Updates (`FooterController.php`) — [COMPLETED]
- **`index()`**:
  - Loads column titles and decoded link arrays (with default fallbacks).
  - Passes structured collections to `admin.footer.index`.
- **`update()`**:
  - Validates titles and JSON string payloads.
  - Sanitizes items (`icon`, `label_en`, `label_kn`, `url`, `style`, `new_tab`, `is_visible`).
  - Persists to `SystemSetting` with `group = 'footer'`.
  - Clears Laravel view cache automatically.

### Phase 3: Admin CMS Link & Button Builder UI (`admin/footer/index.blade.php`) — [COMPLETED]
- **Column 2 Link Manager Card**:
  - English & Kannada Column Title inputs.
  - Interactive cards with `is_visible` toggle (`👁️ Shown` / `🚫 Hidden`).
  - Sub-column indicator: items 1–6 labeled `Col 1 (Left)`, items 7+ labeled `Col 2 (Right)`.
  - Reorder up/down (`▲`/`▼`) and delete (`✕`).
  - Card collapse/expand and header `Collapse All / Expand All` button.
  - Hidden inputs serialize `col2Links` and `col3Links` to JSON on submit.
- **Column 3 Action & CTA Manager Card**:
  - Style selector per item: `🟢 Button`, `✉️ Chip`, `🟡 Alert`, `🔗 Link`.
  - `is_visible` toggle on each action card.
  - Quick-add presets: `+ WhatsApp CTA`, `+ Email Chip`, `+ Alert Pill`, `+ Link`.
- **Sticky Real-Time Live Preview**:
  - Desktop Mode: Renders Column 1 with 6 items on the Left and items 7+ on the Right.
  - Mobile Mode: Interactive accordion simulation with dynamic titles and drawer expansion.

### Phase 4: Dynamic Frontend Engine (`components/farmer-footer.blade.php`) — [COMPLETED]
- **Desktop Rendering (`md+`)**:
  - Slices visible links: `col2Left = slice(0, 6)`, `col2Right = slice(6)`.
  - Column 1 (Left) renders up to 6 items top-to-bottom.
  - If more than 6 items exist, Column 2 (Right) renders items 7+ top-to-bottom.
  - Column 3 renders configured action styles (Green button, Contact chip, Alert pill, Standard link).
- **Mobile Accordion Rendering (`< md`)**:
  - Drawer 1 (Platform Hubs): loops through `$visibleCol2Links`.
  - Drawer 2 (Community & Help): loops through `$visibleCol3Links`.
- Bottom attribution bar maintained: Developed by (left), Made with ❤️ (center), Copyright (right).

### Phase 5: Build, Test & Browser Verification — [COMPLETED]
- Built with `npm run build` (Vite / Tailwind v4).
- View cache cleared (`php artisan view:clear`).
- Tested visibility toggles (hiding/restoring links).
- Tested rendering with single column (<= 6 items) and 2-column layout (12 items).

---

## 3. Data Structure Specification

```json
// footer_col2_links (12 Default Items)
[
  // Col 1 (Left: Items 1 to 6)
  { "icon": "📊", "label_en": "Daily Mandi Rates", "label_kn": "ದೈನಂದಿನ ಮಂಡಿ ದರಗಳು", "url": "/crops", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "📈", "label_en": "Price Forecasts", "label_kn": "ದರ ಮುನ್ಸೂಚನೆ & ಟ್ರೆಂಡ್ಸ್", "url": "/crops", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "🏛️", "label_en": "Govt Schemes", "label_kn": "ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು", "url": "/schemes", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "🌤️", "label_en": "Weather Radar", "label_kn": "ಹವಾಮಾನ & ಮಳೆ ವರದಿ", "url": "/weather", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "▶️", "label_en": "Agri Videos", "label_kn": "ಕೃಷಿ ವೀಡಿಯೊಗಳು", "url": "/videos", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "📰", "label_en": "Agri News & Alerts", "label_kn": "ಸುದ್ದಿ & ಮಾರುಕಟ್ಟೆ ಎಚ್ಚರಿಕೆ", "url": "/news", "style": "link", "new_tab": false, "is_visible": true },

  // Col 2 (Right: Items 7 to 12)
  { "icon": "📖", "label_en": "Farming Guides", "label_kn": "ಕೃಷಿ ಕೈಪಿಡಿಗಳು (Guides)", "url": "/articles", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "🏢", "label_en": "APMC Directory", "label_kn": "ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ವಿವರ", "url": "/crops", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "🌾", "label_en": "Crop Advisory", "label_kn": "ಬೆಳೆ ಸಲಹೆ & ರಕ್ಷಣೆ", "url": "/articles", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "💰", "label_en": "MSP Support Rates", "label_kn": "ಬೆಂಬಲ ಬೆಲೆ (MSP) ವಿವರ", "url": "/schemes", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "🧪", "label_en": "Soil & Nutrients", "label_kn": "ಮಣ್ಣು & ಪೋಷಕಾಂಶ ನಿರ್ವಹಣೆ", "url": "/articles", "style": "link", "new_tab": false, "is_visible": true },
  { "icon": "🌱", "label_en": "Organic Farming", "label_kn": "ಸಾವಯವ ಕೃಷಿ ಪದ್ಧತಿ", "url": "/articles", "style": "link", "new_tab": false, "is_visible": true }
]

// footer_col3_links
[
  {
    "icon": "💬",
    "label_en": "Join WhatsApp Community",
    "label_kn": "ವಾಟ್ಸಾಪ್ ಸಮುದಾಯಕ್ಕೆ ಸೇರಿ",
    "subtitle_en": "Karnataka Rytha Channel",
    "subtitle_kn": "ಕರ್ನಾಟಕ ರೈತರ ಸಮುದಾಯ",
    "url": "https://whatsapp.com/channel/krushi-baandhava",
    "style": "button",
    "new_tab": true,
    "is_visible": true
  },
  {
    "icon": "✉️",
    "label_en": "support@krushibaandhava.org",
    "label_kn": "support@krushibaandhava.org",
    "subtitle_en": "Email Support",
    "subtitle_kn": "ಇಮೇಲ್ ಬೆಂಬಲ",
    "url": "mailto:support@krushibaandhava.org",
    "style": "chip",
    "new_tab": false,
    "is_visible": true
  },
  {
    "icon": "⚠️",
    "label_en": "Report Mandi Rate Issue",
    "label_kn": "ದರ ವ್ಯತ್ಯಾಸ ವರದಿ ಮಾಡಿ",
    "url": "#",
    "style": "alert",
    "new_tab": true,
    "is_visible": true
  }
]
```
