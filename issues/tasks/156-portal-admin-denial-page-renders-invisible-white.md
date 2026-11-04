---
id: 156
title: Portal admin denial page renders invisible white 404 text
status: closed
priority: medium
created: 2026-11-05T00:51:54.135137+06:00
updated: 2026-11-05T00:58:10.724105+06:00
started: 2026-11-05T00:51:54.151332+06:00
completed: 2026-11-05T00:58:10.724105+06:00
claimed_by: oscine-urman
claimed_at: 2026-11-05T00:58:10.724105+06:00
class: standard
---

Active QA task #4, scheduled 2026-11-05 Asia/Dhaka, plan qa/offboarding-2026-11-05/plan.md. Subscription/order IDs: N/A. User/customer: run-owned offboard-user@example.test, ordinary portal user (UUID in private fixture source; no WordPress role); no customer subscriptions. URL: http://127.0.0.1:3016/admin/offboarding/ and /admin/offboarding/7d750c83-f7b8-4231-8be9-756278ecbcfc/. Browser: offboard-portal-user, signed in normally with nonadmin fixture. Steps: sign in as ordinary user; navigate directly to list or detail. Expected: admin access denied with readable not-found message. Actual: access correctly denied but 404 heading text is white on white, main area appears blank under dark OS preference. Proof: evidence/portal-user-denied-list.png and portal-user-denied-detail.png plus text snapshots; computed headings rgb(255,255,255). Scope: admin gate protects data; guest redirects correctly; authenticated admin record view works. Fix plan: theme scoped admin not-found boundary with existing portal foreground/background and verify both denied routes in browser.

Fixed with scoped admin not-found page using existing portal components and colors. Re-tested list and detail: HTTP 404, no feedback content, readable foreground, Return to dashboard succeeds. Evidence: portal-user-denied-list-fixed.png, portal-user-denied-detail-fixed.png, portal-user-denied-fixed-response.json, portal-user-return-dashboard.txt. Typecheck and production build pass.
