---
id: 116
title: Gateway Logs cards clip Last Webhook on narrow mobile screens
status: open
priority: low
created: 2026-10-25T20:52:10.248658+06:00
updated: 2026-10-25T20:52:10.248658+06:00
tags:
    - bug
    - admin
    - responsive
class: standard
---

Active QA task ID / scheduled day / plan path: N/A; targeted navigation and module verification requested on 2026-10-25. Verification report: qa/gateway-audits-2026-10-25/report.md.
Affected subscriptions and orders: N/A.
Affected user: WordPress user #1, login admin, administrator; customers N/A.
Browser context: isolated agent-browser gateway-admin session, Chromium, viewport 390 x 844, local site http://localhost:10013, Pro active. No data edits needed.
Exact routes: /wp-admin/admin.php?page=arraysubs-mainadmin#/settings/gateways and #/renewal-flow/gateway-logs.
Steps: sign in as admin, open either Gateway Logs destination, use a 390px-wide viewport, inspect the collapsed Paddle/PayPal/Stripe/Mollie cards.
Expected: Status, Subscriptions, and Last Webhook labels and values fit or wrap within each gateway card.
Actual: Last Webhook labels and values extend past the right edge of the cards and are clipped. The new Renewal Flow navigation selector itself fits and switches destinations correctly.
Proof: qa/gateway-audits-2026-10-25/evidence/mobile-existing-audits-gateway.png and evidence/mobile-renewal-gateway.png, captured on the existing Audits destination and the additional Renewal Flow destination respectively. Both show the rightmost metric cut off.
Scope: shared existing GatewayHealthDashboard layout, reproducible at both destinations. Desktop cards fit at 1280px. This task changes navigation and module ownership; gateway card layout source was not changed. Separate nonblocking layout issue.
