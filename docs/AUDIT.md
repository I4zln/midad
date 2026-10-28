# MIDAD — Requirements Audit (Phase 0)

Audit date: 2026-10-28. Baseline commit: `a7631af`. Nothing was fixed in this phase.

Status legend: **Implemented** · **Partial** · **Missing** · **Violating**

## Database snapshot (local `database/database.sqlite`)

| Item | Value |
|---|---|
| users / quotes / likes / favorites / comments | 13 / 29 / 3 / 0 / 0 |
| Quotes longer than 155 chars | **9** (ids 24, 8, 15, 6, 28, 13, 19, 16, 29; 159–433 chars) |
| Quotes shorter than 3 chars | 0 |
| Quotes with `user_id = NULL` (legacy/admin-seeded) | 18 |
| Password hashes | all 13 start with `$2y$` (bcrypt) |
| Migration `2026_10_28_000000_add_latest_quote_index` | **Pending** (not applied to the local DB) |

## Phase 1 — Quotes display

| Requirement | Status | Evidence / responsible file |
|---|---|---|
| Home shows only 5 newest via limited query | Implemented | `QuoteController@index` → `(clone $query)->take(5)` |
| Eager-load author + like/bookmark state (no N+1) | Implemented | `with('user')`, `withCount`, `withExists(is_liked, is_favorited)` |
| Equal, evenly spaced cards; text/author/date; like/bookmark/copy/share | Implemented | `components/quote-card.blade.php`, `.quote-grid` in `app.css` |
| "عرض كل الاقتباسات" button | Implemented | `welcome.blade.php` latest section |
| Other homepage sections kept, long lists shortened | Implemented | poets list `limit(12)`, leaderboard `take(5)` (shows label, not long text) |
| Empty state with "+ أضف اقتباسًا" | Implemented | `welcome.blade.php` `@empty` |
| Quotes page: newest first, 12/page, `withQueryString` | Implemented | `QuoteController@index`, route `quotes.index` |
| Header: title, count, add button | Implemented | `quotes/index.blade.php` |
| Search + category filters + clear control | Implemented | `quotes/index.blade.php`, `applyLibraryFilters()` |
| Grid 3/2/1 columns, consistent height | Implemented | `app.css` `.quote-grid` + media queries |
| Long quotes line-clamped + "قراءة المزيد" | Implemented | `.card-quote-text` `-webkit-line-clamp` (CSS only, stored text untouched) |
| No-results / error states | Partial | No-results exists; validation errors show only `$errors->first()` globally |
| Dates in Arabic | **Violating** | `diffForHumans()` renders English ("2 hours ago") because `APP_LOCALE=en` |
| Pagination labels in Arabic | **Violating** | Default Laravel pagination view renders English "Previous/Next/Showing…" |

## Phase 2 — Functional requirements

| # | Requirement | Status | Evidence / responsible file |
|---|---|---|---|
| 1 | Guests view all quotes with text, publisher, date | Implemented | public routes `home`, `quotes.index`, `quotes.show` |
| 2 | Register: name 2–50, valid unique email, password ≥ 8 + confirmation | Partial | Rules correct in `AuthController@register`; **error messages are English** (no `lang/ar`), password field errors not shown next to the field |
| 3 | Login with email + password | Implemented | `AuthController@login` (also accepts username) |
| 4 | Logout | Implemented | `POST /logout`, session invalidated + token regenerated |
| 5 | Share quote: auth only, 3–155 client + server | Implemented | `auth` middleware, `min:3|max:155`, `minlength/maxlength` + counter |
| 6 | Edit own quotes only | Implemented | `->can('update','quote')` + `QuotePolicy@update` |
| 7 | Delete own quotes only | Implemented | `->can('delete','quote')` + `QuotePolicy@delete` |

## Phase 3 — Non-functional requirements

| Requirement | Status | Evidence / responsible file |
|---|---|---|
| Passwords hashed | Implemented | `User` cast `password => hashed`; DB rows are bcrypt |
| Server-side ownership → 403 | Implemented | route `can:` middleware + `QuotePolicy` |
| CSRF, validation, parameter binding | Implemented | `@csrf` on all forms; Eloquent bindings |
| Field hints | Partial | Present on register/create/edit; home composer dialog hints partly inline; login fine |
| Live character counter | Implemented | `data-char-counter` in `resources/js/app.js` |
| Short register → first quote path | Implemented | register redirects to `quotes.create` |
| Performance: eager loading, pagination | Implemented | see Phase 1 |
| Performance: indexes | Partial | `quotes_latest_index` migration exists but is **pending** on the local DB |
| Validation logic in Form Requests | Missing (optional) | rules duplicated inline in `store`/`update` |

## Phase 4 — Blade inheritance and components

| Requirement | Status | Evidence / responsible file |
|---|---|---|
| All pages `@extends('layouts.app')` | Implemented | every page view; only components/mail/partials lack it (expected) |
| Navbar / footer components | Implemented | `x-site-header`, `x-site-footer` |
| Button / form-field components | Partial | used on create/edit/register name+email; password fields, login and home composer use raw markup |
| ONE quote-card component everywhere | **Violating** | `x-quote-card` on home + quotes page, but `pages/favorites`, `profile/show`, `authors/show` duplicate their own card markup |
| Unused partial | Note | `layouts/navigation-links.blade.php` is not referenced |

## Phase 5 — Extra feature

| Feature | Status | Evidence |
|---|---|---|
| Likes | Implemented | `quote_likes` (FKs cascade, unique `quote_id+user_id`), AJAX toggle with row lock |
| Bookmarks (favorites) | Implemented | `quote_favorites` (FKs cascade, unique), `/favorites` page |
| Search + category filter | Implemented | DB-backed `LIKE` + `category` filter, kept across pages |
| Model relations | Partial | `User` lacks `likes()` / `favorites()` relations; `QuoteLike` has no `belongsTo` |

## Phase 6 — Length conflict

| Item | Finding |
|---|---|
| Mu'allaqat | Separate: stored in `config/muallaqat.php`, not the DB |
| Featured classical poets | Separate: `config/featured_poets.php` |
| Long user content | **Same table**: 9 rows in `quotes` exceed 155 chars (4 owned by users, 5 with `user_id = NULL`) → decision required (ASK) |

## Phase 7 — Deliverables

| Item | Status |
|---|---|
| SRS | Partial — `docs/SRS.md` exists; `docs/QuoteIt_SRS.docx` does not exist |
| ERD | Partial — `docs/ERD.mmd` exists; no image/PDF |
| Demo script | Partial — `docs/DEMO-1-MIN.md` exists |
| Clean source package / deliverables folder | Missing |
| `.env.example` clean | Implemented (no secrets); `APP_LOCALE=en` should be `ar` |

## Testing infrastructure

| Item | Status |
|---|---|
| Test suite runs | **Violating** — Pest aborts: `ExampleTest`, `PasswordResetTest`, `ProjectRequirementsTest` each call `uses(TestCase, RefreshDatabase)` while `tests/Pest.php` already binds them |
| Test DB isolation | OK — `phpunit.xml` uses SQLite `:memory:` |
