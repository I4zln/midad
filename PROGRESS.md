# PROGRESS

Resume rule: read this file, then continue from the first phase not marked done.

## Phase 0 — Audit · DONE
- `docs/AUDIT.md` written. Baseline commit `a7631af` (repo initialised; `.env`, `vendor`, `node_modules`, `*.sqlite` are git-ignored).
- Key gaps: test suite does not run (duplicate Pest `uses()`), English validation/date/pagination text, three views duplicate the quote card, index migration pending on the local DB, 9 long rows in `quotes`.
- Phase 6 decision (owner, 2026-10-28): **keep the 9 long rows as legacy**, no schema/data change; show a notice on their edit form.
- Next: Phase 1.
