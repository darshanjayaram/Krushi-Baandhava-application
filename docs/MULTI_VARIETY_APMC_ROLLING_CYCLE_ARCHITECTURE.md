# Multi-Variety & Grade Architecture (Negilu Krishi Alignment)

## 1. Overview
Farmers and APMCs across Karnataka deal with commodities categorized into distinct varieties and commercial grades (e.g. `Rashi · Average`, `Saraku · Average`, `Bette · Average`, `Sippegotu · Average`, `Gorabalu`, `EDI` for Arecanut; `Hybrid · Average`, `Hybrid · Medium` for Tomato; `IR-64 · FAQ`, `IR-64 · Average` for Paddy).

To provide the cleanest and most authentic farmer experience—mirroring the proven UX of [Negilu Krishi](https://negilukrishi.in/)—Krushi Baandhava adheres strictly to the following principles:

1. **Zero Dates Inside Variety Buttons**:
   - Dates are strictly prohibited inside the variety/grade selection pills.
   - The trading date is displayed once and clearly in the hero header metadata (e.g., `Updated: 28 Sep 2026` / `ಪ್ರಕಾರ 28 ಸೆಪ್ಟೆಂಬರ್`).
2. **Standardized `{Variety} · {Grade}` Formatting**:
   - The dot separator (` · `, Unicode `&middot;`) cleanly separates the variety and the commercial auction grade.
   - Handled dynamically across English and Kannada through `MarketPrice::getDisplayVarietyGrade($locale)`:
     - English: `Rashi · Average`, `Saraku · Average`, `IR-64 · FAQ`
     - Kannada: `ರಾಶಿ · ಸರಾಸರಿ`, `ಸರಕು · ಸರಾಸರಿ`, `ಐಆರ್-64 · ಎಫ್‌ಎಕ್ಯೂ`
   - Self-contained varieties/grades (like `Gorabalu`, `EDI`, or when grade is empty/identical to variety) display cleanly without redundant dot suffixes.
3. **Session-Synchronized Availability Filtering**:
   - When viewing an APMC market, the system resolves that market's active trading date (`$marketLatestDate`).
   - Only varieties that were **actually traded on that specific date** are displayed.
   - If a variety/grade was not traded on that date, it is completely hidden. Stale records from prior days are never blended into the active auction session.
4. **Adaptive Single vs. Multi-Grade Layout**:
   - **Single Grade Traded**: Renders a compact, clean non-clickable box with variety/grade name and price. The `PICK YOUR GRADE` label is omitted to reduce cognitive clutter.
   - **Multiple Grades Traded**: Renders `PICK YOUR GRADE` / `ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ` with a wrapped row of button pills displaying name, grade, and authentic price (`₹{Price}`).

---

## 2. Database Schema

The `market_prices` table contains a dedicated `grade` column:

```sql
ALTER TABLE `market_prices` ADD `grade` VARCHAR(50) NULL DEFAULT 'Average' AFTER `unit`;
```

### Grade Normalization & Persistence
1. `MarketPriceIngestionService` extracts `grade` from the normalized provider payload (`source_grade` / `grade`).
2. Saved directly into `market_prices.grade` during canonical ingestion (`updateOrCreate`).
3. Historical and existing records backfilled from `market_price_raw` payload.

---

## 3. Localization Mapping

Grade translations are maintained in `App\Models\MarketPrice::getDisplayVarietyGrade()`:

| Raw Grade | English Label | Kannada Translation (`kn`) |
| :--- | :--- | :--- |
| `Average` | `Average` | `ಸರಾಸರಿ` |
| `FAQ` | `FAQ` | `ಎಫ್‌ಎಕ್ಯೂ` |
| `Non FAQ` | `Non FAQ` | `ನಾನ್-ಎಫ್‌ಎಕ್ಯೂ` |
| `Medium` | `Medium` | `ಮಧ್ಯಮ` |
| `Small` | `Small` | `ಸಣ್ಣ` |
| `Large` | `Large` | `ದೊಡ್ಡ` |
| `Ball` | `Ball` | `ಉಂಡೆ` |
| `Milling` | `Milling` | `ಮಿಲ್ಲಿಂಗ್` |
| `Desiccated` | `Desiccated` | `ಡೆಸಿಕೇಟೆಡ್` |
| `Dehusked` | `Dehusked` | `ಸಿಪ್ಪೆ ಸುಲಿದ` |

---

## 4. Verification & Testing

The implementation is verified with automated tests:
- `Tests\Feature\FarmerPriceDiscoveryTest`: 14 passing tests (13,254 assertions).
- `Tests\Feature\CommodityBoardPricesTest`: 4 passing tests (21 assertions).
- Multi-crop display script verified on Arecanut, Tomato, Onion, Paddy, Maize, and Coconut.
