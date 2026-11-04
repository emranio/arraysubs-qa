# Offboarding UTC+6 correction - 2026-11-05

Scope: align offboarding list/detail dates, notification sent time, new notification email content, and date filters with the user's requested UTC+6 display. Preserve UTC API timestamps, stored instants, and immutable email payloads already prepared for retries.

Schedule (Asia/Dhaka, 2026-11-05): inspect existing formatting, implement the shared display helper and local-calendar filter boundaries, run regression/type/build checks, deploy through main, then read existing production records and exercise date filters. No scheduled watcher.

Targets: user-portal checkout; disposable local PostgreSQL schema used by the existing integration suite; authenticated live portal browser at https://user-portal.arrayhash.com/admin/offboarding/. Existing known event dbd20445-3a8d-4587-aa40-5bfb9ed12d5d occurred at 2026-11-04T19:04:54Z and must display as 2026-11-05 01:04:54 UTC+6. No additional production records or emails are required. Subscription/order/WordPress customer IDs: N/A.

Plan:

1. Reuse one explicit portal timezone formatter for offboarding table, accessibility labels, detail times, sent status, and future offboarding email content.
2. Interpret from/through dates as local calendar days, with an inclusive start and exclusive next-day bound. Keep input date validation and stored UTC values intact.
3. Test midnight/month/year rollover and real PostgreSQL receipt boundaries, including a different database timezone. Run offboarding email/contract tests, TypeScript and production build.
4. Verify the existing production list/detail and November 5 date filter after deployment; record evidence, close the issue, and push QA/parent references. Leave the user's authenticated session open.
