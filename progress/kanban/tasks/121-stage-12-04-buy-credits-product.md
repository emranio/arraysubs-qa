---
id: 121
title: 'stage-12: 04 Buy Credits Product'
status: closed
priority: medium
created: 2026-05-19T22:56:15.905806226+02:00
updated: 2026-05-24T10:02:33.378537128+02:00
started: 2026-05-23T08:06:53.43150373+02:00
completed: 2026-05-23T09:39:32.983157683+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/04-buy-credits-product.md

[[2026-05-23]] Sat 09:39
QA done with failures/workarounds. Created fixtures #1106 Credit Pack 0 and #1108 Custom Credit via WP-CLI to proceed. 4.1/4.2 admin edit reload FAIL logged #77: product edit shows Simple product, standard tabs visible, credit fields absent despite arraysubs_store_credit term/meta. 4.3 fixed storefront partial PASS: title, 5.00, Credit Amount 0.00, +10% Bonus visible; FAIL logged #78 for Buy Credit label instead of Buy Credits. Cart fixed line PASS: 5 total, no quantity control. Checkout fixed via BACS PASS: order #1110 processing, no subscription created, order note adds 5, balance 40 -> 95. 4.4/4.6 custom product storefront FAIL logged #78: no amount input/default or buy button on product page; seeded backend order #1130 for 00 custom credit so Stage 12 data continues; order note adds 10, balance 95 -> 205. 4.7 browser Credit History blocked by #75, but rest_do_request purchase filter returns #1128 +55 balance 95 and #1131 +110 balance 205. 4.8 email arrival blocked by existing #40. Customer portal Store Credit confirms balance 05 and rows: purchase #1130 +110, purchase #1110 +55, debit -10, admin +50. Debug log no fresh related errors; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 09:56
Issue #77 fix verified: Store Credit product #1106 reloads as Store Credit, hides standard inventory/shipping/linked/attribute tabs, and shows persisted credit fields. wc_get_product(1106)->get_type() now returns arraysubs_store_credit; control product #233 still returns simple.

[[2026-05-24]] Sun 10:02
Issue #78 fix verified: fixed Store Credit product button is Buy Credits; custom Store Credit product now renders default amount input 50 with min 10/max 500 and Buy Credits button, and browser validation blocks below-min/above-max values.
