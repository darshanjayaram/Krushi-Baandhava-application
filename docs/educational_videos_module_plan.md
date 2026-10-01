# Educational Videos Module: Implementation Plan & Admin Management Guide

## 1. Overview & Objectives

Transform the **Curated Educational Videos** module into a streamlined, high-value agricultural learning hub. The focus is on:
1. **Zero-Effort Video Curation**: YouTube oEmbed auto-fetcher (instant title, channel, duration, and thumbnail extraction).
2. **Contextual Discovery**: Multi-crop and growth-stage taxonomy linking videos to farmer workflows.
3. **Smart Placement**: Featured spotlight flags and contextual integration on the Crop Details page.
4. **Fast Admin Management**: In-page preview player modal, AJAX status/featured toggles, and multi-filter toolbar.

---

## 2. Feature Scope & Architecture

| Feature Area | Key Capabilities |
| :--- | :--- |
| **Automated YouTube Metadata Fetcher** | Auto-extracts Video ID, Title, Channel Name, Duration, and HD Thumbnail on URL paste via YouTube oEmbed API (no API key required). |
| **Growth-Stage & Crop Tagging** | Tags videos with growth stages (`Nursery`, `Vegetative`, `Pest & Disease Control`, `Irrigation`, `Harvest`, `Post-Harvest`, `General`). Links videos to specific crops or universal categories. |
| **Smart Placement & Cross-Module** | `is_featured` toggle for spotlighting videos on homepage / hub header. Automatic integration of top 2 crop-specific videos on `farmer/crops/show.blade.php`. |
| **Fast Admin UI/UX** | AJAX instant toggles for Active/Hidden and Featured status. In-page video playback modal. Bulk activate, deactivate, and delete actions. |

---

## 3. Implementation Phases

### Phase 1: Database Schema & Model Enhancement
* Create migration `add_enhanced_fields_to_curated_videos_table`:
  * `is_featured` (`boolean`, default: `false`, indexed)
  * `growth_stage` (`string`, length: 50, nullable, default: `'general'`)
  * `language` (`string`, length: 20, default: `'kn'`)
  * `views_count` (`unsignedInteger`, default: `0`)
* Update `App\Models\CuratedVideo`:
  * Add fillables & casts.
  * Define growth stages taxonomy dictionary with English and Kannada labels.
  * Query scopes: `scopeFeatured()`, `scopeByCrop()`, `scopeByStage()`.

### Phase 2: Backend Controller & Zero-Effort Auto-Fetcher
* Add route & method `fetchMetadata(Request $request)`:
  * Accepts `youtube_url`.
  * Calls YouTube oEmbed API (`https://www.youtube.com/oembed?url=...&format=json`).
  * Returns JSON with title, author_name, thumbnail_url, and youtube_video_id.
* Add AJAX toggle routes:
  * `POST /admin/videos/{video}/toggle` (active status).
  * `POST /admin/videos/{video}/toggle-featured` (featured status).
* Add bulk action route:
  * `POST /admin/videos/bulk` (activate, deactivate, delete selected).

### Phase 3: Admin UI/UX Revamp
* **`resources/views/admin/videos/index.blade.php`**:
  * Metrics stats overview (Total Videos, Active, Featured, Crops Covered).
  * In-page video preview modal (plays video without leaving dashboard).
  * Filter toolbar: Search, Crop, Growth Stage, Status.
  * Quick-action toggles directly on video cards/table.
* **`resources/views/admin/videos/form.blade.php`**:
  * 2-column layout: Form inputs on left, live YouTube preview card on right.
  * URL input triggers automatic metadata fetch on paste/input.
  * Growth stage dropdown/pills with Kannada labels.

### Phase 4: Farmer Frontend Integration
* **`resources/views/farmer/crops/show.blade.php`**:
  * Add contextual "Expert Video Guides" section below mandi prices, querying top 2 curated videos for the current crop.
* **`resources/views/farmer/videos/index.blade.php`**:
  * Featured spotlight hero banner at the top of the video hub.
  * Growth stage filter pills alongside crop filters.

---

## 4. Admin Management Guide

### 1. Adding a New Video
1. Navigate to **Admin Panel → Educational Videos → Add Video**.
2. Paste any YouTube URL (`https://youtu.be/...` or `youtube.com/watch?v=...`).
3. The system will automatically fetch and display:
   - Video Title (English)
   - Channel Name
   - HD Thumbnail and Video Preview Player
4. Enter or refine the Kannada Title (`title_kn`).
5. Select the **Associated Crop** (or leave empty for General) and select the **Growth Stage** (e.g. *ಕೀಟ & ರೋಗ ನಿರ್ವಹಣೆ / Pest & Disease Control*).
6. Optionally check **"Featured Spotlight"** to pin to top.
7. Click **Save Video**.

### 2. Managing Video Library
* **In-Page Playback**: Click the thumbnail or "▶ Preview" button to watch the video directly in the admin modal.
* **Quick Status Toggle**: Click the Active/Hidden badge to immediately toggle visibility without page reload.
* **Featured Toggle**: Click the Star icon to spotlight/un-spotlight videos.
* **Bulk Operations**: Select multiple items using checkboxes to activate, deactivate, or delete in bulk.

---

## 5. Dynamic Taxonomies: Categories & Growth Stages Management

Admins can dynamically create, edit, deactivate, or delete video categories and crop growth stages without modifying code.

### Navigation
Navigate to **Admin Panel → Educational Videos → Categories & Stages** (`/admin/videos/taxonomies`).

### Capabilities
1. **Thematic Categories Tab (`?type=category`)**:
   - Manage categories like *Cultivation Techniques, Pest & Disease Control, Organic Farming, Farm Machinery & Drones, Farmer Success Stories, etc.*
   - Add new categories with English title, Kannada title (`name_kn`), unique slug, emoji/icon (`🌾`, `🐛`, `🚜`, etc.), and display order.
2. **Crop Growth Stages Tab (`?type=growth_stage`)**:
   - Manage agronomic stages like *Nursery & Sowing, Growth & Nutrition, Pest & Disease Control, Irrigation & Water Mgmt, Harvesting & Picking, Storage & Value Addition, etc.*
3. **In-Page Editing & Cascade Safety**:
   - Inline edit modal to update names, icons, or display order.
   - If an admin edits a slug, existing curated videos referencing the old slug are automatically cascaded.
   - Delete protection: Categories or Growth Stages with videos attached cannot be accidentally deleted; they must be reassigned or set to Inactive.
4. **Seamless Integration**:
   - Video entry form (`/admin/videos/create`) dynamically populates dropdowns from database taxonomies with "+ Manage ↗" direct links.
   - Farmer video hub (`/farmer/videos`) filter pills dynamically reflect newly created active categories and stages.

---

## 6. Configurable Pagination & Professional Farmer Hub UI

### Admin Hub Settings (Max Videos Per Page)
- Admins can configure the number of videos displayed per page on the public Farmer Hub (`/farmer/videos`) directly from the Educational Videos module.
- Setting: `farmer_video_per_page` managed via `SystemSetting` model.
- Click **"⚙️ Per Page: X"** in the top header of **Admin Panel → Educational Videos** to change between `6, 8, 9, 12, 15, 18, 24, 30` videos per page.

### Classic & Professional Farmer UI Features
1. **Hero Header**: Rich dark emerald and gold gradient banner with Kannada & English headers and badges.
2. **Dual-Tier Horizontal Filter Pills**:
   - Tier 1: Thematic Categories with active emerald pill styling.
   - Tier 2: Agronomic Growth Stages with gold/amber pill accents.
   - Active filter indicator bar with one-click "✕ Reset All Filters" option.
3. **Featured Video Spotlight Banner**: Ambient glowing card with 16:9 thumbnail, pulsing play button, duration tag, crop badge, and language tag.
4. **Card Grid**: Responsive 1/2/3/4 column layout with tactile hover elevation, bottom duration tag, growth stage badge, crop badge, and verified channel indicator.
5. **Mobile-First Responsive Pagination**:
   - **Mobile**: Clean, touch-friendly 3-part navigation (`[ ← ಹಿಂದಿನದು / Prev ]`, `[ ಪುಟ X / Y ]`, `[ ಮುಂದಿನದು / Next → ]`).
   - **Desktop**: Full numbered page navigation with sliding window and intelligent ellipses (`1 … 4 5 6 … 12`) plus result counters.
6. **Cinema Video Modal**: Fullscreen-ready dark backdrop with autoplay iframe, title display in Kannada & English, and direct "YouTube ↗" open link.
