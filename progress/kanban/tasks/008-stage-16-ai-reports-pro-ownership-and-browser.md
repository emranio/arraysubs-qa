---
id: 8
title: 'stage-16: AI Reports Pro Ownership and Browser Verification'
status: closed
priority: critical
created: 2026-08-15T22:59:57.730533464+02:00
updated: 2026-08-15T23:37:49.727690681+02:00
started: 2026-08-15T23:37:12.954003377+02:00
completed: 2026-08-15T23:37:12.954003377+02:00
tags:
    - qa
    - analytics
    - ai-reports
claimed_by: root-vivid
claimed_at: 2026-08-15T23:37:49.727690361+02:00
class: standard
---

Verify AI Reports are owned and booted only by ArraySubsPro, remain listed as Pro in the Reports Hub, disappear when Pro is inactive, and load successfully in the browser.

QA plans:
- qa/stages/16-analytics/01-reports-hub.md
- qa/stages/16-analytics/06-ai-reports-pro.md

QA result: PASS on the configured live installation https://mirror-help.arrayhash.com. The requested mirror-arraysubs hostname is tracked separately as blocked issue #9.

Browser: agent-browser 0.27.3 / HeadlessChrome 149.0.0.0.

Evidence:
- With Pro active, AI Churn Analysis and AI Revenue Forecast each rendered deterministic data; their Pro CSS/JS and REST requests returned 200.
- Reports Hub rendered 13 categories / 49 reports / 9 free / 40 pro, with the AI category and all six AI cards marked PRO.
- With Pro inactive, Churn Analysis and Revenue Forecast disappeared from Analytics, direct AI routes showed the WooCommerce access page, and no new AI asset or REST request occurred.
- With Pro reactivated, AI Churn Analysis returned with one mount/root and no browser errors.
- Runtime ownership: four AI REST routes and one Pro menu callback; old Core AI classes are absent.

Screenshots:
- /tmp/arraysubs-ownership-qa/screenshots/ai-churn-pro-active.png
- /tmp/arraysubs-ownership-qa/screenshots/ai-forecast-pro-active.png
- /tmp/arraysubs-ownership-qa/screenshots/reports-ai-pro-badges.png
- /tmp/arraysubs-ownership-qa/screenshots/ai-route-pro-disabled.png

Affected subscription/order/customer IDs: N/A.
