# Report Issues & Farmer Feedback System - Implementation Plan
**Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)**  
**Target:** Elevated, Farmer-Centric, Classic Heritage Feedback & Helpdesk CRM  
**Status:** Approved & Ready for Execution  

---

## 1. Executive Summary & Vision

Negilu (`negilukrishi.in/feedback/`) demonstrates the power of a dual-tab feedback/issue form with voice recording in Karnataka's agricultural context. However, it lacks visual richness, photo/receipt upload, mandi/crop context tagging, structured ticket tracking, and an administrative resolution CRM.

Krushi Baandhava's **Report Issues & Feedback** module elevates this into an industry-standard, classic heritage agricultural experience:
- **Heritage Visual Styling:** Built with warm limestone/sand palette (`#EFEAE0`, `#FAF8F5`, `#1C5A2C` deep forest green, `#DDD3BE` soft border tones), brass accents, and rounded pill elements matching the rest of the application.
- **Dual-Purpose Experience:**
  - `🛠️ ಸಮಸ್ಯೆ ವರದಿ (Report an Issue)`: For mandi rate discrepancies, fake weights/fraud, market listing requests, technical app bugs, or crop disease help.
  - `💡 ಸಲಹೆ & ಅಭಿಪ್ರಾಯ (Suggestions & Feedback)`: For rating user experience, proposing new features, sharing market stories, and farmer appreciations.
- **Voice Note & Photo Uploads:**
  - One-tap HTML5 audio voice note recording with live timer, waveform preview, playback, and retake for illiterate/voice-first farmers.
  - Photo attachment for mandi weighing receipts, payment bills, pest/crop photos, or error screenshots with live thumbnail preview.
- **Mandi & Crop Smart Pre-filling:**
  - Contextual parameters passed from mandi profiles or crop detail pages (`/feedback?mode=issue&crop=Tomato&market=Kolar`) automatically select the right category and pre-fill crop/market fields.
- **Farmer Assurance & Ticket Reference:**
  - Immediate generation of human-readable reference number (e.g. `KB-26-5246`).
  - Ticket confirmation screen with clean acknowledgment. (Note: The direct WhatsApp follow-up button is currently hidden and ready to be re-activated once official support desk WhatsApp operations commence).
- **Admin Helpdesk & Grievance CRM:**
  - Dedicated admin portal (`/admin/feedback`) to review, filter (New, In Review, Resolved, Rejected), listen to voice notes, preview photos/receipts in a lightbox, log internal resolution notes, and trigger direct WhatsApp/Phone replies.

---

## 2. Architecture & Data Model

### Database Schema: `farmer_feedbacks`
| Field | Type | Description |
|---|---|---|
| `id` | `bigint unsigned auto_increment` | Primary key |
| `ticket_no` | `varchar(30) unique` | Human-friendly ticket number, e.g., `KB-26-8941` |
| `type` | `enum('issue', 'feedback')` | Mode selected by user |
| `category` | `varchar(100)` | Category tag (e.g., `price_discrepancy`, `sell_produce`, `new_market`, `crop_disease`, `bug`, `feature_request`, `appreciation`) |
| `rating` | `tinyint unsigned nullable` | 1-5 star rating (for feedback mode) |
| `crop_name` | `varchar(120) nullable` | Selected or free-text crop name |
| `district` | `varchar(120) nullable` | District name |
| `market_name` | `varchar(150) nullable` | Market or APMC Mandi name |
| `message` | `text` | Detailed description or feedback text |
| `voice_path` | `varchar(255) nullable` | Audio file path in storage (`uploads/feedback/voice/...`) |
| `voice_duration` | `int unsigned nullable` | Duration in seconds |
| `photo_path` | `varchar(255) nullable` | Photo file path in storage (`uploads/feedback/photos/...`) |
| `farmer_name` | `varchar(120) nullable` | Farmer name |
| `farmer_phone` | `varchar(20)` | Farmer contact number (Required for follow-up) |
| `farmer_email` | `varchar(120) nullable` | Optional email |
| `status` | `enum('new', 'in_review', 'resolved', 'rejected')` | Current ticket status (default: `new`) |
| `admin_notes` | `text nullable` | Internal remarks from admin team |
| `resolved_at` | `timestamp nullable` | Timestamp of ticket resolution |
| `resolved_by` | `bigint unsigned nullable` | Foreign key to `users.id` (Admin user) |
| `ip_address` | `varchar(45) nullable` | Anti-spam telemetry |
| `user_agent` | `text nullable` | Device/browser telemetry |
| `created_at` / `updated_at` | `timestamps` | Standard Laravel timestamps |

---

## 3. UI/UX Design Specifications

### 3.1 Farmer View (`/feedback`)
1. **Header Banner:**
   - Classic Green `#1C5A2C` banner with gold emblem and bilingual title: "ರೈತರ ಸಹಾಯವಾಣಿ ಮತ್ತು ಪ್ರತಿಕ್ರಿಯೆ / Farmer Helpdesk & Feedback".
   - Subtitle: "ನಿಮ್ಮ ಧ್ವನಿ, ನಮ್ಮ ಶಕ್ತಿ. ಮಾರುಕಟ್ಟೆ ಸಮಸ್ಯೆಗಳು ಅಥವಾ ಸಲಹೆಗಳನ್ನು ಹಂಚಿಕೊಳ್ಳಿ."
2. **Dual-Tab Switcher:**
   - Segmented toggle styled with beige background and emerald active state:
     - 🛠️ **ಸಮಸ್ಯೆ ವರದಿ / Report Issue**
     - 💡 **ಸಲಹೆ & ಅಭಿಪ್ರಾಯ / Feedback & Suggestion**
3. **Category Chips:**
   - Interactive pills for instant single-click selection.
   - For Issues: *ದರ ವ್ಯತ್ಯಾಸ (Price Discrepancy)*, *ಬೆಳೆ ಮಾರಾಟ ಸಹಾಯ (Produce Sale Help)*, *ಹೊಸ ಮಾರುಕಟ್ಟೆ ಕೋರಿಕೆ (Request New Mandi)*, *ಬೆಳೆ ರೋಗ / ಕೀಟ (Crop Disease/Pest)*, *ಸರ್ಕಾರಿ ಯೋಜನೆ (Govt Scheme)*, *ಆ್ಯಪ್ ದೋಷ (Technical Bug)*, *ಇತರೆ (Other)*.
   - For Feedback: *ದರ ಮಾಹಿತಿ ಉಪಯುಕ್ತತೆ (Price Accuracy)*, *ಹೊಸ ವೈಶಿಷ್ಟ್ಯ ಕೋರಿಕೆ (Feature Request)*, *ಆ್ಯಪ್ ವಿನ್ಯಾಸ (App Design)*, *ಪ್ರಶಂಸೆ (Appreciation)*, *ಇತರೆ (Other)*.
4. **Context Inputs (Crops & Mandis):**
   - Quick searchable/selectable crop dropdown or datalist.
   - District & Mandi selection for localized market issues.
5. **Rich Media Upload Area:**
   - **Voice Note Recorder:** One-click Mic button with browser MediaRecorder API, visual pulse indicator, recording timer (up to 3 minutes), playback preview, and retake option.
   - **Photo / Mandi Slip Upload:** Drag-and-drop or camera shutter input with real-time thumbnail preview, file size check (up to 5MB), and clear button.
6. **Farmer Details & Anti-Spam:**
   - Name and 10-digit mobile number with Karnataka prefix (+91).
   - Hidden honeypot field (`website_url_check`) to block automated bot spam.
7. **Success Modal / State:**
   - Ticket badge with copy button (e.g., `KB-2601`).
   - One-tap WhatsApp button to forward details directly to Krushi Baandhava team.

---

### 3.2 Admin Helpdesk Module (`/admin/feedback`)
1. **Metric Overview Cards:**
   - Total Submissions, Pending New, In Review, Resolved, Issues vs Feedback breakdown.
2. **Comprehensive Data Table:**
   - Search by Ticket ID, Farmer Name, Phone Number, Crop, or Mandi.
   - Filter by Type (`issue`, `feedback`), Status (`new`, `in_review`, `resolved`, `rejected`), Category, and Date range.
   - Quick action badges with direct audio player inline preview and photo thumbnail popup.
3. **Grievance Detail & Action Modal / Drawer:**
   - Farmer profile details & submission timestamp.
   - Embedded audio waveform player for listening to the farmer's voice.
   - Full-resolution photo zoom (for verifying APMC receipt slips or crop disease).
   - Status update dropdown (`New` -> `In Review` -> `Resolved` / `Rejected`).
   - Admin internal notes textarea.
   - One-click **"Reply on WhatsApp"** button generating a pre-filled contextual message acknowledging ticket reference number.
   - One-click **"Call Farmer"** link (`tel:...`).

---

## 4. Phase-Wise Execution Steps

### Phase 1: Database Migration & Model Setup
- Create migration `create_farmer_feedbacks_table`.
- Create Eloquent model `App\Models\FarmerFeedback` with fillable attributes, status constants, search scopes, and formatted ticket generator.
- Run `php artisan migrate`.

### Phase 2: Farmer Public Controller & Route
- Create `App\Http\Controllers\Farmer\FeedbackController`.
- Register GET `/feedback` and POST `/feedback` routes in `routes/web.php`.
- Support pre-filling via query parameters (`?mode=issue&crop=...&market=...`).
- Implement file uploads (`storage/app/public/feedback/photos` and `storage/app/public/feedback/voice`).

### Phase 3: Farmer Feedback Blade View (`resources/views/farmer/feedback/index.blade.php`)
- Craft the Classic Heritage Agri UI matching `#EFEAE0` and `#1C5A2C`.
- Build Alpine.js reactive component managing dual tabs, category selection, HTML5 voice recording, photo preview, and validation.
- Success confirmation banner with WhatsApp follow-up link.

### Phase 4: Admin Helpdesk Module (`App\Http\Controllers\Admin\FeedbackController`)
- Create Admin controller with index, show, update status, and destroy actions.
- Build Admin Blade view `resources/views/admin/feedback/index.blade.php` with stats cards, filter pills, search bar, audio player, photo modal, and WhatsApp responder.
- Add navigation link in `resources/views/layouts/admin.blade.php` sidebar with unread count pill badge.

### Phase 5: Verification & Integration
- Connect Footer and navigation links to `/feedback`.
- Rebuild frontend assets with `npm run build`.
- Test voice recording and photo uploads, ticket generation, admin view, status updates, and WhatsApp messaging.
