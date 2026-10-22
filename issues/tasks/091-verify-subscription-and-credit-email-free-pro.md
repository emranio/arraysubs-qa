---
id: 91
title: Verify subscription and credit email Free Pro boundaries
status: closed
priority: medium
created: 2026-10-23T03:16:48.445196+06:00
updated: 2026-10-23T03:44:18.448363+06:00
started: 2026-10-23T03:39:42.23556+06:00
completed: 2026-10-23T03:44:18.448363+06:00
tags:
    - qa
    - emails
    - free-pro
class: standard
---

Active QA task / scheduled day / plan: fresh targeted extension requested 2026-10-23, immediate manual run; plan qa/email-boundaries-2026-10-23/plan.md.
Affected subscriptions/orders/users: N/A at creation. Choose isolated disposable fixtures for rendering and record IDs in evidence; do not modify existing lifecycle or recipient data.
Exact test context: local WordPress http://localhost:10013; email settings/preview, core/pro email hooks, customer/admin links and premium optional data.
Scope/plan: inspect both plugins and templates for free/pro ownership and endpoint links, unavailable modules/providers, missing/deleted customer/order/product data, escaping and internal feature-type privacy. Test actual WordPress email classes and preview/UI with real disposable fixtures, intercept all mail before delivery (no external email or SMTP). Compare Pro active versus request-local Free; restore any temporary settings and remove own fixtures.
Expected: Free lifecycle emails work without Pro; Pro-only email registrations and content remain conditional; links target the right customer/admin/payment routes; render paths return usable content without fatal errors and avoid internal feature-type metadata.
Actual/proof: pending. Preserve all existing issues, especially closed85-89 and open Woo cart90. Record new confirmed defects separately before fixing. Skip PHPCS/lint/Composer installation; perform focused runtime and browser checks. No deployment/push or version bump.

[[2026-10-23]] Fri 03:22
Root baseline verified 246 actual-WP render checks with95failures,0PHPwarnings and38intercepted mails (no external transport). Real disposable alpha/beta users/subscriptions/products and one manual unpaid renewal order were created/deleted in finally; IDs in render-before.json. Confirmed shared email singleton retains previous order/payment placeholders across different customers when order missing/deleted; template recurring prices ignore storedGBP currency; core plaintext has escaped names/footerHTML; preview bodies use#0 despite sample subject12345. Fix plan beforeediting: reset order context and derived order placeholders centrally during population; reuse existing core email helpers for currency-aware formatting and plaintext normalization; keep real raw subscriptionID0 separate from display-number sample to avoid preview DB lookups; use shared template arguments for product/date/money instead of repeated metadata reads. Sourcebranch codex/email-context, isolated /tmp/arraysubs-email-context. Agent92 handles delivery markers/adminlinks;93 handles credit hooks/plain/preview.

[[2026-10-23]] Fri 03:39
Completed all confirmed email findings and user-requested fixes. Local core main includes exact delivery-result tracking/static public helper, independent pending-cancel sent markers, six repaired admin links, shared context reset against stale customer orders/payment placeholders, stored-currency formatting and plaintext normalization, consistent preview display values/nonpersistent Woo order context and valid list/checkout links. Pro main includes notification hooks honoring deferred queues, source credit/order currency and owner/currency balance isolation, shared plaintext formatting and coherent safe previews. Issues92/93 closed after acceptance. Primary real WP checks:246render+48delivery+398missing-record cases in EACH Free and Pro,70boot-moduleoff,29full-Pro credit dispatch/render/order-link and16GBP credit cases. Browser all29Pro previewtypes+25Free previews passed; final link destinations verified, safe corelist navigation clicked. All mail intercepted before external transport (application testing, not external-inbox delivery). Exact activation/config/ComingSoonyes/all51emailoptions restoration7/7, no sends or saves, own browser session closed. Own fixture users/products/subscriptions/orders/creditlogs deleted; runtime filters request-only. Canonical4core/3Pro draft entries preserve earlier entries; PHP/template-only changes need noassetbuild. Sourcefiles<=3000, whitespace checks pass. Final report qa/email-boundaries-2026-10-23/report.md. Existing unrelated Cart issue90 preserved.

[[2026-10-23]] Fri 03:40
Final evidence review caught two remaining preview-only fields not covered by the earlier aggregate no-zero checks: Renewal Reminder leaves its date label/value empty before any trigger, and Card Expiring preview has blank card digits. Reopened before completion. Plan: shared parent display-date fallback for preview reminder context; sample card4242/expiry only when subscription_id0; actual missing card digits use readable N/A. Add focused assertions and browser-check just these two previews before final close.

[[2026-10-23]] Fri 03:44
Final follow-up accepted: reminder date now matches its3-day sample notice in site timezone, and Card Expiring preview displays4242/11-2026; real card metadata remains9876/12-2030 in focused actualWP fixture and absent real digits show N/A without sample leakage. preview-fields-{free,pro}.json7/7 each; own fixtures deleted and settings/plugins unchanged. Browser final-fields evidence confirmsOct26 forOct23+3days, noerrors; latest state-last-date-comparison.json all7true. No pending work. Root runtime suites and prior29Pro/25Free browser acceptance remain valid; final report updated.
