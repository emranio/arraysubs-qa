---
id: 78
title: 'stage-12: Store Credit storefront purchase controls broken'
status: closed
priority: high
created: 2026-05-23T09:33:32.172376779+02:00
updated: 2026-05-24T10:02:40.418662412+02:00
started: 2026-05-24T09:56:43.912562961+02:00
completed: 2026-05-24T10:02:40.418661421+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:02:40.418662312+02:00
class: standard
---

Stage 12 Task 04 Subtasks 4.3/4.4. Fixed Credit Pack #1106 storefront shows title, $45.00, Credit Amount $50.00, +10% Bonus, but button label is Buy Credit (singular) instead of expected Buy Credits. Custom Credit #1108 storefront shows min/max text and +10% Bonus but no amount input/default 50 and no buy/add-to-cart button, so below-min/above-max validation cannot be performed and the product cannot be purchased from the single product page.

[[2026-05-24]] Sun 10:02
Fix verified: Store Credit products now render custom single-product purchase controls. Fixed Credit Pack page shows 5.00, Credit Amount 0.00, +10% Bonus, no quantity input, and button label Buy Credits. Custom Credit page shows min/max text 0.00-00.00, +10% Bonus, Credit Amount input default 50 min 10 max 500, and Buy Credits button. Browser invalid checks block 5 with value >=10 warning and 9999 with value <=500 warning.
