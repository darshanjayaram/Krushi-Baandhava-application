# Phase 11: Agricultural CMS, Government Schemes, News & Videos Walkthrough

## 1. Executive Summary

Phase 11 delivers a full-stack Agricultural Content Management System (CMS) within **Krushi Baandhava**. It equips administrators with total control over publishing, editing, and toggling content across four primary modules, while providing Karnataka farmers with bilingual, fast, mobile-first PWA browsing interfaces, dynamic cross-linking on commodity detail pages, and public REST APIs.

---

## 2. Key Architecture & Modules Delivered

### A. Database Schema & Models
1. **Government Schemes & Subsidies (`schemes`)**:
   - Stores Central and Karnataka agricultural welfare schemes (Tractor subsidies, Micro-irrigation, PM-KISAN, PMFBY, Soil Health Card, Krishi Bhagya).
   - Fields: `title`, `title_kn`, `slug`, `category`, `sponsoring_agency`, `benefit_amount`, `benefit_amount_kn`, `eligibility_criteria`, `eligibility_criteria_kn`, `documents_required`, `official_url`, `apply_url`, `display_order`, `is_active`.
2. **Agricultural News & Alerts (`news_articles`)**:
   - Breaking news ticker, circulars, price fluctuation advisories, and weather warnings.
   - Fields: `headline`, `headline_kn`, `slug`, `summary`, `summary_kn`, `content`, `content_kn`, `source_name`, `source_url`, `priority` (breaking, important, standard), `published_at`, `is_active`.
3. **Curated YouTube Educational Videos (`curated_videos`)**:
   - Curated agricultural tutorials, pest management techniques, and success stories.
   - Automatic YouTube video ID extraction from standard, short, or raw URL formats.
   - Fields: `title`, `title_kn`, `youtube_video_id`, `youtube_url`, `crop_id`, `category`, `channel_name`, `duration_text`, `display_order`, `is_active`.
4. **Agronomy Guides & Farming Practices (`articles`)**:
   - Scientific cultivation methods, pest/disease management protocols, and fertilizer schedules.
   - Fields: `crop_id`, `title`, `title_kn`, `slug`, `category`, `summary`, `summary_kn`, `body`, `body_kn`, `author_name`, `published_at`, `is_published`.

---

## 3. Admin CMS Management Portal

All 4 modules are accessible to authenticated administrators via the dedicated **Content Management (CMS)** section in `resources/views/layouts/admin.blade.php`:

| Module | Route Name | Endpoints | Actions Supported |
| :--- | :--- | :--- | :--- |
| **Govt Schemes** | `admin.schemes.*` | `/admin/schemes` | Index, Create, Edit, Update, Toggle status, Delete |
| **Agri News** | `admin.news.*` | `/admin/news` | Index, Create, Edit, Update, Toggle status, Delete |
| **Curated Videos** | `admin.videos.*` | `/admin/videos` | Index, Create (auto YouTube ID parsing), Edit, Update, Toggle, Delete |
| **Agri Guides** | `admin.articles.*` | `/admin/articles` | Index, Create (auto slug generation), Edit, Update, Toggle, Delete |

---

## 4. Farmer PWA User Experience

### 1. Schemes Hub (`/schemes`, `/schemes/{slug}`)
- Category filtering pills: Subsidies & Grants, Farm Machinery, Micro Irrigation, Crop Insurance, Organic & Soil.
- Real-time search by scheme name or sponsoring department.
- Deep detail view showcasing eligibility requirements, benefits breakdown, required documents checklist, and direct official application portal links.

### 2. Agri News & Alerts (`/news`, `/news/{slug}`)
- Pulsing real-time breaking news banner at the top for emergency alerts.
- Priority filters: All, Breaking (ತುರ್ತು), Important (ಪ್ರಮುಖ).
- Clean article view with source attribution and full bilingual content.

### 3. Video Hub (`/videos`)
- Filterable by crop and category.
- Inline modal YouTube player powered by Alpine.js allowing seamless video playback without navigating away from the PWA.

### 4. Agronomy Guides (`/articles`, `/articles/{slug}`)
- Expert guides filterable by crop and agronomic category (Cultivation, Pest Control, Soil Nutrition, Harvest & Storage).
- Cross-linked to related guides and crop profiles.

### 5. Crop Detail Integration (`/crops/{slug}`)
- Dynamic embedding of training videos for the specific crop.
- Relevant agronomy articles for the commodity.
- Direct cards linking to applicable government subsidies.

---

## 5. Public REST APIs (V1)

| Endpoint | Method | Query Parameters | Description |
| :--- | :--- | :--- | :--- |
| `/api/v1/schemes` | `GET` | `category`, `search`, `per_page` | Paginated listing of active welfare schemes |
| `/api/v1/news` | `GET` | `priority`, `per_page` | Paginated listing of active news and advisories |
| `/api/v1/videos` | `GET` | `crop_id`, `category`, `per_page` | Curated videos with thumbnails and embed links |
| `/api/v1/articles` | `GET` | `crop_id`, `category`, `per_page` | Published agronomy guides and cultivation protocols |

---

## 6. Verification & Quality Assurance

- **Unit Tests**: `tests/Unit/CmsModelTest.php` (4 passed).
- **Admin Feature Tests**: `tests/Feature/AdminCmsTest.php` (5 passed).
- **Farmer Feature Tests**: `tests/Feature/FarmerCmsTest.php` (6 passed).
- **Total Test Suite**: **129 passed (1,100 assertions), 100% green**.
- **Asset Compilation**: `npm run build` compiled clean in 1.44s.
