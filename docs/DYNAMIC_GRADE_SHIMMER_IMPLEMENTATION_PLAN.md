# Dynamic Multi-Row Grade Shimmer Implementation Plan

## 1. Problem Overview
In the crop detail page (`/crop/{id}` or `/crops/{slug}`), Box 1 contains the **Current Price** section, the **PICK YOUR GRADE** section, and the **VIEW DIFFERENT MARKET (All Mandis)** section.
When a user switches markets asynchronously (e.g. from **Sagara** to **Thirthahalli**):
1. **Sagara** has 6 grades across 2 rows (`Rashi`, `Bette`, `Gorabalu`, `Idi`, `Sippegotu`, `Api`) plus a toggle button (`Show fewer grades`).
2. The loading shimmer overlay previously only rendered **3 hardcoded static pills** in a single row.
3. This left the second row of grade cards (`Sippegotu`, `Api`) and the toggle button completely exposed and unshimmered underneath, creating a broken visual experience with old text bleeding through.

---

## 2. Architecture & Design Blueprint

### Phase 1: Dynamic 1:1 Grade Skeleton Generation
Bind the skeleton pills in the shimmer overlay directly to Alpine's reactive `gradesList`:
- When `gradesList.length === 1`:
  Render 1 matching prominent grade badge skeleton.
- When `gradesList.length > 1`:
  Render matching dynamic skeleton pills using Alpine's `<template x-for>` loop:
  `showAllGrades ? gradesList : gradesList.slice(0, Math.min(gradesList.length, 4))`
  Each skeleton card matches the height (`h-[54px]`), padding, border-radius (`rounded-2xl`), and flex column layout of the actual grade cards.
- When `gradesList.length > 4`:
  Render a matching toggle link skeleton (`h-3 w-28`) for the "Show more/fewer grades" button.
- When `gradesList.length === 0`:
  Hide the grade skeleton section completely via `x-show="gradesList && gradesList.length > 0"`.

### Phase 2: Shielding & Anti-Bleed Overlay
- Set overlay background to `bg-white/98` with `backdrop-blur-xs` to ensure zero bleed-through of stale data from the previous market during network transit.
- Maintain smooth Alpine enter/leave transitions (`duration-150` enter, `duration-200` leave) for natural cross-fading into the newly loaded market data.

### Phase 3: Quality Verification & Regression Testing
- Verify Sagara (multi-row 6 grades) switching to Thirthahalli.
- Verify single-grade crops/markets.
- Verify zero-grade crops/markets.
- Run view cache clear and automated test suite.
