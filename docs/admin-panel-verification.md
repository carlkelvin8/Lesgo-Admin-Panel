# Admin panel verification

## Implemented

- Business Records catalog for 21 audited operational datasets, including realtime notifications, partner branches, driver locations, registration payments, duty/mission records, surveys, vouchers, linked accounts and support chat records.
- Permission checks on every catalog list, detail and export. Explicit field allowlists, masked verification phone numbers, excluded credentials/private payloads, and formula-safe CSV exports.
- Search, date/status/relationship filters, stable ID pagination, record details and exports for the catalog.
- Compatibility with `partner_admin`/`staff` user roles and `active`/`closed` partner statuses.
- Current/deleted/all account browsing and retained deleted-user names in historical orders, wallets and other relationships. Deleted accounts remain unable to sign in.
- Detailed order fare calculations, partner branch information and delivery proof images with restricted URL handling.
- Legacy/current 2FA configuration resolution without copying secrets, five-minute challenge expiry, recovery-code handling and remember-me preservation.
- Dashboard rendering/cache/currency fixes, report date/weighted-average fixes, literal PostgreSQL-compatible search, confirmation forms and optional-input validation.

## Verification

- 118 automated tests passed with 634 assertions on isolated SQLite fixtures.
- PHP syntax, changed-file Pint checks and production frontend build passed.
- npm audit reported zero vulnerabilities.
- Read-only PostgreSQL probes validate live table/query/view/export coverage. Detailed production inventories and runtime audit artifacts are kept local and excluded from the Git commit.
- Final catalog visual inspection was limited by a browser-helper timeout; controller/view and functional checks passed.
- The build retains a non-blocking warning about the optional fontaine font-fallback optimizer.

## Boundaries

Catalog screens are read-only views of existing business tables, not new financial or operational mutation workflows. Payment, registration-fee and wallet top-up ledgers remain separate to avoid inventing or double-counting transactions. Sensitive configuration values, encrypted linked-account data, verification hashes and authentication secrets are excluded from browsing/export.

No production writes, migrations or deployment were performed. Provider-side refunds, real SMTP/SMS/FCM delivery and production queue processing were not exercised. Existing production schema is required for each catalog dataset; an unavailable dataset is reported explicitly in other environments.
