---
id: 160
title: Portal admin dates use inconsistent timezone and customer timezone is missing
status: closed
priority: medium
created: 2026-11-05T02:06:47.715277+06:00
updated: 2026-11-05T02:31:27.252709+06:00
started: 2026-11-05T02:31:27.252708+06:00
completed: 2026-11-05T02:31:27.252708+06:00
class: standard
---

Active QA task 1; scheduled 2026-11-05 Asia/Dhaka; plan qa/portal-timezones-2026-11-05/plan.md. Subscription/order IDs N/A. Existing live admin emran@arrayhash.com, signed-in Edge. Routes /admin/emails/, /admin/subscribers/, /admin/users/: view displayed dates; expected UTC+6, actual old code uses raw UTC. Customer /settings/ has no timezone preference; desired defaultUTC and savedtimezone control of account UI/emails. Proof: audited formatting functions toISOString/UTC in those pages and previous live Subscribers snapshot showing UTC. Scope: portal-owned rendering; API/database instants must remainUTC0. Plan: shared formatting/admin local-calendarfilters, profilepreference and customer display/email conversion; integration and actualbrowser verification. Local disposable customer b9d14b0f-9f86-4c79-80ce-980efe9eb688 timezone-customer@example.test and admin 6c3596c4-93b3-4a54-96bc-4a407d3ff821 timezone-admin@example.test, URLs http://127.0.0.1:3017/. Preserved all prior issues.

Final review also found and corrected server-local parsing of provider payment.created timestamps before UTC persistence. Targeted real-handler regression covers host UTC, Asia/Dhaka and America/New_York, native UTC strings and ISO offsets. A new profile-fetch failure guard prevents permanent customer loading screens and provides Try again. Browser invoice layout inspection exposed clipped date years; date controls were widened and visually rechecked. Details/evidence: qa/portal-timezones-2026-11-05/customer-browser.md and admin-browser.md. Live deployment verification pending.

Resolved in user-portal main 6288649. All 54 tests plus four-page React loading/retry scenarios passed; typecheck and production build passed. Actual local browser, UTC DB/API baseline, and captured customer reset-email proof passed. Production deploy 41906506685 succeeded. Signed-in live Settings, Email logs/detail/calendar filters, Subscribers and Users verified, browser error log empty. Report: qa/portal-timezones-2026-11-05/report.md. No production account mutations or real email sends during this timezone cycle; disposable local fixture cleaned.
