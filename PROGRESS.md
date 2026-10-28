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

## T2 Phase 3 — Ink welcome · DONE
- `components/ink-welcome.blade.php` in the hero: «أهلًا بك في مِـداد» (one kashida, kasra kept) / signed-in «أهلًا بك يا {name} في مِـداد» (escaped), original line «هنا يهدأُ الحبرُ على الورق، وتبقى الكلمةُ أثرًا لا يجفّ.» + inline SVG copper stroke.
- Font: Amiri Bold Arabic self-hosted (`public/fonts/amiri`, OFL licence included, 98KB, preloaded on home only).
- Animation ≈3.5s: RTL mask sweep + blur→sharp + slight drift; stroke draws right→left. Final state is the default; `.is-animating` added by inline script (full load) or `midad:page-loaded` (in-site nav), once per session (sessionStorage), skipped for reduced motion.
- Browser check: animates on first visit only, not on 2nd visit or in-site nav; reduced motion static; CLS 0.0024 (1440) / 0 (390); no overflow. 42/42 tests.

## T2 Phase 4 — Auth UX · DONE
- Root cause of failing reset: `.env` had `MAIL_MAILER=smtp` → 127.0.0.1:2525 (Laravel placeholder, nothing listening); every send threw, user still saw "check inbox". Now `MAIL_MAILER=log` + `MAIL_LOG_CHANNEL=mail` → dev-only `storage/logs/mail.log` (git-ignored), so links never land in laravel.log. Old tokens already in laravel.log (pre-session, default mailer) redacted.
- Login: email-only (`email` field, old `identifier` still accepted), errors under fields, email kept, rate-limit message with seconds, `?next=` (same-site only) + `?reason=` → explains why login is needed and returns afterwards; guest like/save/comment/follow links use it. Protected GET pages explain too.
- Register/reset: `x-form-field` + new `x-password-input`; confirmation error on the confirmation field (`same:password`); neutral reset message «إن كان البريد مسجّلًا لدينا فستصلك رسالة خلال دقائق».
- Inline Arabic validation for `form[data-validate]` (blur/input/submit, replaces browser bubbles), aria-describedby/invalid.
- Found while testing on 390px: logout + profile link were `display:none` on mobile; favorites/notifications had no link anywhere → new account menu (`site-header`) with profile, add quote, saved, notifications (+unread badge), edit profile, admin, logout. `scroll-padding-bottom` so the fixed bottom nav never covers focused fields/buttons.
- Tests: 5 new (wrong login, next/reason, unsafe next, protected page, reset flow). 47/47. Browser e2e on copied DB via log mailer: register inline errors, register, logout via menu, wrong password, neutral message ×2, one mail in mail.log (subject/sender/60 min/ignore/signature), invalid link, mismatch, reset, reused link rejected, login with new password, no token in laravel.log — all PASS (when runs are >1 min apart; rate limits otherwise trip, as intended).

## T2 Phase 5 — Profile redesign · DONE
- `PageController@profile`: tabs `quotes` / `liked` / `saved` (liked+saved owner-only; visitors fall back to quotes), real stats (quotes, likes received, followers, following; liked/saved counts on owner tabs), 9 per page with query string.
- `profile/show.blade.php` rebuilt: ivory cover band (no dark gradient block), avatar above the cover (no clipping), name/role/@handle/«عضو منذ أكتوبر 2026», actions (owner: add quote + edit; visitor: follow), stats row, tabs, shared `x-quote-card`, friendly empty states. Gender-neutral labels.
- Quote card: owner-only edit + delete (`@can`, server still enforces via policy). Delete returns to the page you were on (profile) or to the profile when deleted from the quote's own page.
- Shared `x-confirm-dialog` replaces all `confirm()` (5 forms).
- Profile edit: name 2–50 with hint, all fields via `x-form-field` (errors under each), inline validation. No password-change feature exists → not added.
- Tests: 2 new (tabs/privacy/stats/owner actions/403; delete from profile + name rules). 49/49. Screenshots: 1440 light/dark, 390, owner view — no overflow.

## T2 Phase 6 — UX consistency · DONE
- Old brand «أثر» removed from 4 views; one term for bookmarks everywhere: «المحفوظات» (menu, tab, page title, toasts, login reason); quote page save button shows its state.
- Forms: field labels/inputs always full width (label grid was shrinking to 219px on phones); inline validation on create/edit/composer/comment; duplicate top-of-page error hidden when field errors are shown.
- Toasts above the mobile bottom nav, high contrast, error variant (role=alert). Focus-visible outlines for links/buttons/summary/chips/pagination; search box focus ring.
- Bug found by e2e and fixed: the POST double-submit guard also caught `method="dialog"` forms, so after one «إلغاء» the confirm dialog's «نعم، احذف» did nothing.
- Browser audit (11 guest + 7 signed-in pages × 390/1440 + keyboard Tab walk): no overflow, no unnamed controls, no unlabeled inputs, no clipped text.
- Browser e2e regression (demo DB): create (inline error, counter, first on home, toast), edit, like/unlike via AJAX, save + المحفوظات page, search + clear, delete via dialog (cancel/confirm), 404 after delete, other user's edit → 403 — 18/18 PASS. PHP tests 49/49.
- Final numbers (warm, cache disabled): home TTFB 76–99ms / load 311–326ms (was 454/819); /quotes 56–67ms / 164–181ms (was 357/446). In-site nav 59–101ms, hover-then-click 13–29ms.
- Not refreshed: `deliverables/` (SRS PDF, zip) still reflect Task 1.
