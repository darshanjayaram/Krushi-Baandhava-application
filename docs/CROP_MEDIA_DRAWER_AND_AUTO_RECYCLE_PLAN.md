# Crop Media Drawer & Auto-Recycle Architecture

## 1. Overview
The **In-Form Visual Photo Drawer & Auto-Recycle** system provides an intuitive, lightweight media management tool directly inside the Admin Crop Form (`resources/views/admin/master/crops/form.blade.php`).

It replaces blind dropdown menus with an interactive visual photo drawer, enables 1-click photo reuse across crops, and automatically cleans up unshared custom uploads to prevent disk bloat.

---

## 2. Core Capabilities

### A. Visual Photo Drawer & Thumbnail Tray
- **Live Preview Card**: Large 80x80px crop thumbnail with live status badge (`Custom Upload`, `Preset`, or `Auto-Matched`).
- **File Upload Button**: Clean, modern file button with instant client-side validation (formats: JPG, PNG, WEBP, SVG; max: 5MB).
- **Search & Filter Controls**:
  - Live search input: `🔍 Search photo (e.g. arecanut, tomato, coffee...)`
  - Filter tabs: `All`, `Presets`, `Uploads`
- **Visual Thumbnail Tray**:
  - Grid of square image cards showing all available preset photos and custom uploads.
  - Active checkmark and emerald ring on the currently assigned photo.
  - In-use indicator showing which crops are using each photo (e.g., `🟢 In Use: Arecanut`).
  - 1-click selection: immediately selects the photo, highlights it, and updates the preview card.
- **Action Controls**:
  - `✕ Reset / Remove Photo` button to restore default preset or clear icon.

### B. Auto-Recycle / Zero Storage Waste
- When an admin uploads a new photo or resets an image for a crop that already had a custom upload:
  - The controller checks if any other crop is sharing that exact file path.
  - If **unshared**, the previous file in `public/uploads/crops/` is permanently unlinked.
  - If another crop is sharing the file, it is preserved.
  - Bundled presets in `public/images/crops/` are permanently protected from deletion.

---

## 3. Implementation Phases

- **Phase 1: Backend Validation & Model Preparation**
  - Update `app/Http/Requests/Admin/CropRequest.php` to accept `selected_image_path` and `remove_image`.
- **Phase 2: Controller Asset Discovery & Auto-Recycle Logic**
  - Update `app/Http/Controllers/Admin/CropController.php`:
    - Add `getAvailableImages()` method that scans `images/crops/` and `uploads/crops/` and computes live crop usage.
    - Pass `$availableImages` and `$presetImages` to `create` and `edit` views.
    - In `update()`, implement safe auto-recycle of replaced unshared files.
- **Phase 3: Frontend Visual Drawer Implementation**
  - Update `resources/views/admin/master/crops/form.blade.php`:
    - Integrate Alpine.js reactive photo drawer state (`imageSearch`, `imageFilter`, `selectedImagePath`).
    - Build visual thumbnail tray with search, tabs, hover effects, and active state indicators.
- **Phase 4: Verification & Testing**
  - Verify preset selection, custom upload replacement, photo reuse across crops, and safe auto-recycle.
