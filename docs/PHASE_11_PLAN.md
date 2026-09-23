# Phase 11 Implementation Plan: Agricultural CMS, Government Schemes, News & Videos

## 1. Overview & Objectives
Phase 11 builds the complete **Content & Knowledge Management System (CMS)** for **Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)**, giving Administrators full CRUD control to manage and publish:
1. **Government Schemes (`schemes`)**: Subsidies, machinery grants, irrigation programs (PMKSY, Krishi Bhagya, Raitha Siri, PM-KISAN, etc.) with eligibility rules, required documents, and official apply links.
2. **Agricultural News (`news_articles`)**: Market updates, APMC notices, MSP announcements, and government circulars.
3. **Educational Videos (`curated_videos`)**: YouTube farming video embeds (pest management, organic cultivation, harvest techniques) linked to crops.
4. **Agronomy Guides & Articles (`articles`)**: In-depth cultivation, disease prevention, and fertilizer guides.

On the Farmer frontend, it provides dedicated bilingual interfaces (`/schemes`, `/news`, `/videos`, `/articles/{slug}`) and cross-links relevant schemes/videos to specific crop detail pages.

---

## 2. User Review Required

> [!IMPORTANT]
> **Admin Control Guarantee**: All content is 100% manageable by Administrators from the Admin Panel (`/admin/schemes`, `/admin/news`, `/admin/videos`, `/admin/articles`) with full Create, Edit, Toggle Status, and Delete capabilities.
>
> **Bilingual English & Kannada**: Every entity supports both English and Kannada fields (`title`, `title_kn`, `content`, `content_kn`, etc.).

---

## 3. Proposed Changes & Architecture

### A. Database Migrations & Eloquent Models
1. **Migration: `create_agricultural_cms_tables.php`**:
   - **`schemes`**: `title`, `title_kn`, `slug`, `category` (subsidy, machinery, irrigation, insurance, organic), `sponsoring_agency`, `benefit_amount`, `benefit_amount_kn`, `eligibility_criteria`, `eligibility_criteria_kn`, `documents_required`, `official_url`, `apply_url`, `is_active`, `display_order`.
   - **`news_articles`**: `headline`, `headline_kn`, `slug`, `summary`, `summary_kn`, `content`, `content_kn`, `source_name`, `source_url`, `published_at`, `priority` (`breaking`, `important`, `standard`), `image_url`, `is_active`.
   - **`curated_videos`**: `title`, `title_kn`, `youtube_video_id`, `youtube_url`, `crop_id` (nullable), `category`, `channel_name`, `duration_text`, `is_active`, `display_order`.
   - **`articles`**: `title`, `title_kn`, `slug`, `crop_id` (nullable), `category` (cultivation, pest_control, soil_fertilizer, harvest_storage), `summary`, `summary_kn`, `body`, `body_kn`, `featured_image`, `is_published`, `published_at`.

2. **Eloquent Models**:
   - `app/Models/Scheme.php`: Scopes for active, category filter, Kannada accessors.
   - `app/Models/NewsArticle.php`: Scopes for active, priority, recent.
   - `app/Models/CuratedVideo.php`: Relationship to Crop, YouTube thumbnail accessor (`https://img.youtube.com/vi/{id}/hqdefault.jpg`).
   - `app/Models/Article.php`: Relationship to Crop, slug generation, published scope.

3. **Database Seeder**:
   - `database/seeders/AgriculturalCmsSeeder.php`: Seeds initial real Karnataka schemes (Krishi Bhagya, PMKSY Drip, PM-KISAN, Tractor Subsidy, Soil Health Card), agricultural news items, and curated YouTube videos.

---

### B. Admin Panel Management (`/admin/*`)
1. **Controllers**:
   - `app/Http/Controllers/Admin/SchemeController.php` (CRUD + toggle status)
   - `app/Http/Controllers/Admin/NewsController.php` (CRUD + toggle priority)
   - `app/Http/Controllers/Admin/VideoController.php` (CRUD + YouTube ID parser)
   - `app/Http/Controllers/Admin/ArticleController.php` (CRUD + publish toggle)
2. **Views**:
   - `resources/views/admin/schemes/index.blade.php`, `create.blade.php`, `edit.blade.php`
   - `resources/views/admin/news/index.blade.php`, `create.blade.php`, `edit.blade.php`
   - `resources/views/admin/videos/index.blade.php`, `create.blade.php`, `edit.blade.php`
   - `resources/views/admin/articles/index.blade.php`, `create.blade.php`, `edit.blade.php`
3. **Sidebar Updates**:
   - Update `resources/views/layouts/admin.blade.php` with a new group: **Content & Knowledge Management (CMS)**.

---

### C. Farmer PWA Frontend (`/*`)
1. **Controllers**:
   - `app/Http/Controllers/Farmer/SchemeController.php`: List with category filters, search, and detail modal.
   - `app/Http/Controllers/Farmer/NewsController.php`: Chronological news feed.
   - `app/Http/Controllers/Farmer/VideoController.php`: Video gallery with in-page player modal.
   - `app/Http/Controllers/Farmer/ArticleController.php`: Agronomy guides directory & article reading view.
2. **Views**:
   - `resources/views/farmer/schemes/index.blade.php`
   - `resources/views/farmer/news/index.blade.php`
   - `resources/views/farmer/videos/index.blade.php`
   - `resources/views/farmer/articles/index.blade.php`, `show.blade.php`
3. **Cross-Linking**:
   - Crop detail screen (`resources/views/farmer/crops/show.blade.php`): Dynamically query and display schemes and videos linked to the viewed crop!
   - Layout navbars: Add links to Schemes, Videos, and News in desktop and mobile menus.

---

### D. Public REST APIs (`/api/v1/*`)
- `app/Http/Controllers/Api/V1/CmsApiController.php`:
  - `GET /api/v1/schemes` (with category and search filters)
  - `GET /api/v1/news` (recent news feed)
  - `GET /api/v1/videos` (curated videos, optional `?crop_id=`)
  - `GET /api/v1/articles` (farming guides, optional `?crop_id=`)

---

## 4. Verification Plan

### Automated Tests:
1. `tests/Unit/CmsModelTest.php`:
   - Unit test relationships (Crop to Articles, Crop to Videos).
   - Unit test scopes (`active`, `published`, `priority`).
   - Unit test YouTube video ID parser and thumbnail URL accessor.
2. `tests/Feature/AdminCmsTest.php`:
   - Admin authentication enforcement (guests blocked).
   - CRUD tests for Schemes (create, list, update, delete).
   - CRUD tests for News, Videos, and Articles.
3. `tests/Feature/FarmerCmsTest.php`:
   - Farmer browsing screens: `/schemes`, `/news`, `/videos`, `/articles`.
   - Category filtering on `/schemes` and `/articles`.
   - Public API endpoints return 200 OK and valid JSON structures.
   - Dynamic schemes display on crop show page (`/crops/{slug}`).
4. Full application regression suite: `php artisan test` (must remain 100% green, $\ge 125$ tests).
5. Frontend asset compilation: `npm run build` with zero errors.
