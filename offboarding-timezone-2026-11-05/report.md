# Offboarding UTC+6 correction

QA issue: #159. Plan: [plan.md](plan.md). Scheduled and executed on November 5, 2026, Asia/Dhaka.

The offboarding table, record details, notification sent time, accessibility labels, and newly prepared notification messages now display UTC+6. Receipt-date filters use the same local calendar days. API and database timestamps retain their original UTC instants; previously prepared notification payloads remain immutable for safe retries.

Portal implementation: `08bfb19cf53373de425d2724771e9c57cb2cb9cf` on `main`.

## Automated verification

| Check | Result | Evidence |
| --- | --- | --- |
| Offboarding email/database integration | 11 passed | [Log](evidence/email-tests.log) |
| Offboarding event contract | 15 passed | [Log](evidence/contract-tests.log) |
| TypeScript | Passed | [Log](evidence/typecheck.log) |
| Production build | Passed | [Log](evidence/build.log) |

The integration suite exercised midnight, month, and year rollover; a host timezone of UTC or America/Los_Angeles; and real receipt queries with the PostgreSQL session in America/New_York. Four records immediately before, at, and after UTC+6 day boundaries verified both bounds, each one-sided bound, adjacent days, counters, and unchanged stored UTC instants. HTML and plain-text email assertions verified the exact next-day conversion. Existing immutable delivery retries, duplicate handling, claims, and migrations still passed. Outbound email was intercepted and the disposable local schema was removed.

The build emitted the existing Turbopack tracing warning through `next.config.ts`, licensing updates, and the plugin update route. It did not fail the build or concern any changed offboarding file.

## Production browser verification

Target: https://user-portal.arrayhash.com/admin/offboarding/, authenticated administrator `emran@arrayhash.com` in the existing Edge session. Subscription/order/WordPress customer IDs: N/A. Browser automation uses the already-established fallback to the user's authenticated Edge tab because agent-browser could not attach that session during the original offboarding QA.

Before deployment, all six existing local QA records displayed November 4 UTC, with the latest at 19:04. [Before screenshot](evidence/live-before-utc.jpg).

Deployment [41901618002](https://github.com/emranio/arrayhash-user-portal/actions/runs/41901618002) completed successfully for the exact portal commit above. [Deployment result](evidence/deployment.json).

- The deployed table and both date-filter labels show UTC+6. The six existing records display November 5, 00:43–01:04, with UTC+6 included in accessible record-link labels.
- Applying both date inputs as November 5 through the UI, scoped to `localhost:10013`, returns all six records (three deactivations, three help requests). [Screenshot](evidence/live-nov5-filter.jpg), [DOM snapshot](evidence/live-nov5-filter.txt).
- Applying both inputs as November 4 returns zero records and the expected empty-table message. This confirms the filters follow local receipt days rather than the old UTC calendar day. [Screenshot](evidence/live-nov4-filter.jpg), [DOM snapshot](evidence/live-nov4-filter.txt).
- Existing event `dbd20445-3a8d-4587-aa40-5bfb9ed12d5d` shows Occurred `2026-11-05 01:04:54`, Received `2026-11-05 01:04:55`, and notification sent `2026-11-05 01:04:55 UTC+6`. These match the original UTC instants plus six hours. [Screenshot](evidence/live-detail.jpg), [Visible text](evidence/live-detail.txt).

All scoped checks passed. No production data was changed and no new production emails were sent. The user's signed-in browser remains open on the corrected record detail page. The change affects offboarding surfaces; historical email bodies already sent or prepared remain unchanged.
