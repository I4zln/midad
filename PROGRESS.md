# PROGRESS

Resume rule: read this file, then continue from the first phase not marked done.

## Phase 0 — Audit · DONE
- `docs/AUDIT.md` written. Baseline commit `a7631af` (repo initialised; `.env`, `vendor`, `node_modules`, `*.sqlite` are git-ignored).
- Key gaps: test suite does not run (duplicate Pest `uses()`), English validation/date/pagination text, three views duplicate the quote card, index migration pending on the local DB, 9 long rows in `quotes`.
- Phase 6 decision (owner, 2026-10-28): **keep the 9 long rows as legacy**, no schema/data change; show a notice on their edit form.
- Next: Phase 1.

## Phase 1 — Quotes display · DONE
- Test suite fixed (duplicate Pest `uses()`); 26/26 pass.
- Arabic locale (`lang/ar/validation.php`, `lang/ar/pagination.php`, `APP_LOCALE=ar`): dates now "منذ 3 أيام".
- New Arabic pagination view `resources/views/pagination/midad.blade.php` set as default in `AppServiceProvider`.
- "قراءة المزيد" shown only on clamped cards (`markClampedQuotes()` in `app.js`); assets rebuilt.
- Verified on local server: home renders exactly 5 cards; `/quotes?page=2` shows "عرض 13–24 من 29".
- Next: Phase 2.

## Phase 2 — Functional requirements · DONE
- All 7 rules were already enforced server-side; gap was UX: password/login errors now render next to the field via `x-form-field`.
- Arabic length messages ("عدد أحرف الاسم يجب ألا يقل عن 2."); fixed CSS that turned `.field-error` grey outside the quote form.
- New `tests/Feature/FinalGuideTest.php` (8 tests); timed tests now use `forceCreate` (created_at is not fillable). 34/34 pass.
- Next: Phase 3.

## Phase 3 — Non-functional · DONE
- Quote rules moved to `app/Http/Requests/QuoteRequest.php` (shared by store/update, trims text, category constant).
- `tests/Feature/PerformanceTest.php`: query count is constant with 3 vs 23 quotes on home and /quotes (no N+1); indexes asserted.
- Local DB: backed up to `database/database.backup-2026-10-28.sqlite` (git-ignored), then applied pending `quotes_latest_index` migration; 29 rows intact.
- Measured (local `php artisan serve`, 5 runs): /quotes median 0.237s, page 2 0.245s, search+filter 0.209s, home 0.223s.
- 36/36 tests pass. Next: Phase 4.

## Phase 4 — Blade inheritance + components · DONE
- `Quote::scopeWithCardData()` holds the card's eager loading; used by home, /quotes, favorites, profile, author pages.
- Favorites, profile and author pages now render `<x-quote-card>` (was 3 hand-written copies).
- New `<x-quote-fields>` used by create, edit and the home composer dialog (was 3 copies); `<x-form-field>` gained a `name` prop.
- Legacy-length notice on the edit form for quotes > 155 chars (Phase 6 decision).
- Tests: shared card on all 5 listings, every page view `@extends('layouts.app')`, legacy notice. 39/39 pass.
- Next: Phase 5.

## Phase 5 — Extra feature · DONE
- Present: **Likes + Bookmarks (الإعجاب والحفظ)**, supported by search & category filter.
- Added `User::likes()/favorites()`, `QuoteLike::quote()/user()`, `QuoteFavorite::user()`.
- Bookmark toggle now uses the same locked transaction as likes (no 500 on double submit).
- Test: toggle, favorites page, DB unique constraint, cascade on quote delete. 40/40 pass.

## Phase 6 — Length conflict · DONE (owner chose "keep as legacy")
- Mu'allaqat (`config/muallaqat.php`) and classical poets (`config/featured_poets.php`) are separate from the DB.
- 9 long rows stay untouched in `quotes`; create/edit limited to 3–155 via `QuoteRequest`; edit form shows a legacy-length notice.
- Next: Phase 7 deliverables.

## Phase 7 — Deliverables · DONE
- `docs/SRS.md` rewritten for the final app → `deliverables/Abdulaziz_SRS.pdf` (rendered with headless Edge).
- `docs/ERD.mmd` annotated with unique keys/indexes → `deliverables/Abdulaziz_ERD.pdf` + `.png` + `.mmd`.
- `docs/DEMO-1-MIN.md` 60-second Arabic script → `deliverables/Abdulaziz_Demo_Script.md`.
- README run steps updated. `deliverables/Abdulaziz_SourceCode.zip` built with `git archive HEAD` (excludes vendor, node_modules, .env, *.sqlite, logs, public/build).
- Layout check (Edge headless): 10 pages × 390/1440px, no horizontal scroll; full load 0.31–0.60s.

---
# TASK 2 — UX polish, profile, ink welcome, auth, speed

## T2 Phase 1 — Speed · DONE
Root causes (measured with headless Edge, cache disabled, 1440px):
1. PHP OPcache disabled in XAMPP → every request recompiled Laravel. Enabled in `C:\xampp\php\php.ini` (backup `php.ini.backup-2026-10-28`; outside repo). TTFB 270–454ms → 17–44ms.
2. Hotlinked Wikimedia images (home 318KB, /muallaqat 2.6MB, 0.8–1.5s each) → self-hosted 720px WebP in `public/images/poets/` (425KB total); credits unchanged.
3. Unused axios in every bundle → removed `resources/js/bootstrap.js`. JS 66.5KB → 16.1KB.
4. Tailwind scanned Laravel's unused pagination views → removed `@source`. CSS 104.6KB → 92.1KB.
5. Saved dark theme applied only after JS → inline head script (no light flash).
Added: hover/touch/focus prefetch (10s TTL) in `loadPage`, copper progress line, POST double-submit guard.
Numbers: home load 819ms → 321ms; /quotes 446ms → 95ms; /muallaqat 464ms/3.35MB → 146ms/1.03MB.
In-site navigation (click → new page): 42–71ms; hover-then-click 11–16ms.
Fonts (470–630KB cold, cached after) not subset: Thmanyah licence for modified files not in repo.

## T2 Phase 2 — Opening message removed · DONE
- Welcome `<dialog>` (typed "السلام عليكم…" message) removed from `layouts/app.blade.php`, its JS (~70 lines) and ~40 CSS rules.
- Audio kept: navbar player folded into a compact «الأجواء» `<details>` menu (`site-header.blade.php`); closes on outside click/Escape; never autoplays (resume only after a user gesture).
- Test `UxPolishTest`: no welcome text on 3 pages, audio menu present, no `autoplay`. 41/41 pass.
