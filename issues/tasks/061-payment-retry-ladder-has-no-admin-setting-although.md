---
id: 61
title: Payment retry ladder has no admin setting although notes point merchants to it
status: closed
priority: medium
created: 2026-10-19T11:49:45.332640229+02:00
updated: 2026-10-20T00:35:28.108706715+02:00
started: 2026-10-19T20:43:07.554385039+02:00
completed: 2026-10-20T00:35:28.108706094+02:00
tags:
    - settings
    - retry
    - admin-ui
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S04-A (board task #10 in qa/stripe-dev-support-2026-10-18/kanban), phase 2, 2026-10-19.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68599 / renewal order 68614 (note text example). Otherwise N/A.
Affected WordPress user/customer: admin (administrator); customer 581 sqa-p2-noretry for the note example.
Exact route: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general and #/settings/gateways (session sqa-admin), WooCommerce > Settings > Payments.
Reproduction: look for the retry settings (enabled, interval ladder, stop on hard decline) in the ArraySubs and WooCommerce admin screens; search arraysubs/src/resources and both plugins' public/build bundles for payment_retry / stop_on_hard_decline.
Expected: merchants can see and change the retry ladder, since the subscription note says "Automatic retries are disabled in the gateway settings" and documentations/architecture/payment-retry-system.md describes the settings on the Stripe gateway page.
Actual: no admin control exists; the values are only reachable through POST /arraysubs/v1/settings/save (SettingsController.php:293-303). The QA run had to change them through that REST route.
Proof: grep results (no matches in src/resources or public/build); General settings screenshot shows only grace/invoice/spread fields.
Scope notes: behaviour of the stored settings is correct (enabled=false stops scheduling; stop_on_hard_decline=false lets the ladder run). Documentation is stale.
Repro video: N/A.

Deep re-verification 2026-10-19: CONFIRMED WITH CORRECTIONS (medium, high confidence). 49 admin screens and every gateway's form fields checked: no control. Correction: besides the REST route, the 1-Minute Setup (#/easy-setup) Export -> edit JSON -> Import 'Subscription Settings' round trip also changes payment_retry (no on-screen values). The note also fires for PayPal/Paddle where retries are hard-coded off. Stale docs listed in the report. Severity tied to #57 (medium while #57 is open). Full report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): ArraySubs > Settings > General has a "Payment Retries" card (Automatic Payments module): Retry Failed Payments Automatically, Retry Schedule (Hours) as a comma-separated list (1-720 h, at most 10 entries, validated in the UI and clamped on the server), and Don't Retry Cards That Can't Be Charged, with explanations. Invalid values show a toast instead of a silent refusal, including a ladder hidden by its switch; edits to hidden fields are saved. The retries-off note names the store setting, or says the gateway (PayPal/Paddle) retries itself. Docs updated (architecture retry doc, user guide, user-manual pages). Files: core `resources/pages/Settings/GeneralSettings.jsx` (built), `RenewalProcessor.php`, `SettingsController.php`, `functions/gateway-helpers.php`.
Verified live 2026-10-19: T7 (stored values shown; "24, abc" refused with toast; "12, 48" + hard-decline switch off saved and reloaded); T7b (ladder edited then hidden by switching retries off -> saved `[6,48,168]`; hidden "abc" refused with toast, store unchanged). Settings restored after each window; diff vs `settings-original-r2.json` all_identical.
Review round 2: help text now says a retry at the very end of the window runs before the hold (GracePolicy waits for it); a hidden invalid ladder toast names the field and how to reach it. Docs updated for both.
