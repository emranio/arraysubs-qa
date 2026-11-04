# Portal timezone consistency and customer preferences

Scope: all portal-owned displayed dates/times, admin date filters, customer profile timezone, and dates rendered in portal emails. Admin screens/internal emails use Asia/Dhaka (UTC+6). Customer UI and customer emails use the saved profile timezone, default UTC for both existing and new users. Database instants, API timestamp values, authentication/expiry calculations, and external service timestamp contracts remain UTC+0. Saved timezone identifiers are preference metadata. Frozen historical email payloads remain unchanged.

Schedule: November 5, 2026, Asia/Dhaka. Audit and implementation, focused database/email/date regression suites, typecheck/build, isolated local browser QA, push main/deploy, signed-in live admin readback, save evidence/close issues. No watcher or recurring schedule.

Tasks:

1. Shared timezone validation/formatting, repeatable UTC-default schema change, authenticated profile persistence and selection.
2. Audit and correct all admin date displays and filters, including email logs/subscribers/users/domains/invoices; admin timezone must not depend on customer preference.
3. Apply saved timezone to customer product/license/manage/domain/invoice displays and local-day filtering, retaining UTC API fields.
4. Resolve customer email timezone at rendering (UTC fallback) and internal email UTC+6, preserving recorded email contents and idempotent retries.
5. Test UTC, Asia/Dhaka, negative DST offsets, fractional offsets, local-day/year boundaries, migrations, invalid/unauthenticated profile requests, email logs and fresh-session persistence. Run existing relevant suites/typecheck/build.
6. Browser test actual local app with disposable customer/admin accounts and UTC fixtures; inspect UI screenshots before judging. Verify production admin email list/detail, subscribers, users and settings after deploy. No real customer data edits, purchases, cancellations, or unsolicited production emails.

Targets: user-portal repo; isolated loopback PostgreSQL schema and local app; live https://user-portal.arrayhash.com using the existing admin session. User/customer IDs and exact routes will be recorded in the report when fixtures exist. Existing qa/issues are preserved.
