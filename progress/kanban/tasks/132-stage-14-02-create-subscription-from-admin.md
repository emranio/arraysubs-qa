---
id: 132
title: 'stage-14: 02 Create Subscription From Admin'
status: closed
priority: medium
created: 2026-05-19T22:56:17.598471042+02:00
updated: 2026-05-24T14:35:10.582415134+02:00
started: 2026-05-23T08:06:53.444681658+02:00
completed: 2026-05-23T11:19:55.899849058+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/02-create-subscription-from-admin.md

[[2026-05-23]] Sat 11:19
QA notes: Seeded missing prereq user customer1/customer1@arraysubs.test. Add New form opened and showed General, Product & Subscription Details, Different Renewal Price, Billing Information, Billing Address, Shipping Address plus Create Subscription. Customer AJAX search worked for customer1 and selected Customer One - customer1@arraysubs.test. Product selector is a blocker: clicking Subscription Product placeholder navigated away from #/subscriptions/form to #/subscriptions, losing form state; reproduced on clean form. Could not complete product/variation/save/detail verification. Logged #94 critical. Also logged #93 for layout/address-control contract mismatch. Debug log tail had no new product fatal/warning beyond old known entries.

[[2026-05-24]] Sun 12:11
Follow-up fix for linked issue #94 completed. The shared FormBuilder Select now exposes product/customer/unit controls as accessible `type="button"` listbox triggers. Rebuilt ArraySubs assets. Browser verification with Playwright confirmed the Add Subscription form stays on `#/subscriptions/form`, the Subscription Product dropdown opens, `PM Tool (variable)` can be selected, and the Product Variation selector appears. Screenshots saved in `qa/artifacts/issue-94-product-select/`.

[[2026-05-24]] Sun 14:35
Fix verification 2026-05-24 for issue #93: Add Subscription form layout now matches the QA contract with five h3 sections: General, Product & Subscription Details, Trial Settings, Different Renewal Price, Billing Information & Addresses. Country/state controls are dropdowns: billing and shipping country selected United States (US), billing and shipping state selected Texas. Screenshot: qa/artifacts/issue-93-add-form-sections-address-selects.png. Checks: php -l src/Boot.php passed; npm run build passed.
