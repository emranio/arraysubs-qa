---
id: 27
title: 'stage-05: Different renewal summary wording does not match manual'
status: closed
priority: high
created: 2026-05-20T12:03:36.003126246+02:00
updated: 2026-05-22T01:53:24.750677133+02:00
started: 2026-05-22T01:47:42.301059583+02:00
completed: 2026-05-22T01:53:24.750676041+02:00
tags:
    - qa
    - stage-05
    - checkout
    - renewal-price
    - summary
claimed_by: mold-glade
claimed_at: 2026-05-22T01:53:24.750677033+02:00
class: standard
---

Stage 05 Task 05 expected checkout summary wording: '9.99 every 1 week for the first 3 payments, then 9.99 every 1 week'. Observed in cart/checkout for Stepped Weekly: 'First payment: 9.99' and 'Renewals: After 3 renewals: 9.99 Every week'. The pricing calculation/order creation works, but the displayed wording does not match the manual contract and uses renewals instead of payments.

[[2026-05-22]] Fri 01:49
Verified Stage 05 Task 05 and code. Cart item metadata splits different-renewal display into First payment + Renewals and uses 'After N renewals'. Classic checkout table also appends a separate 'then after payments' line. Plan: add a shared lowercase billing phrase helper ('every 1 week'), use the manual full tier string in checkout summary, cart item metadata, and storefront/product helper display; remove redundant cart First payment row because Today's charge already covers initial charge; syntax-check PHP; browser-verify Stepped Weekly cart/checkout wording and totals.

[[2026-05-22]] Fri 01:53
QA fix complete. Added shared billing phrase helper for manual copy ('every 1 week') and updated different-renewal tier display in product info, checkout summary, classic checkout table, and Store API cart metadata. Removed redundant cart First payment row; Today's charge still shows initial charge. Syntax checks passed. Browser verified Stepped Weekly product page, cart, and checkout all show '9.99 every 1 week for the first 3 payments, then 9.99 every 1 week'; cart/checkout total 9.99; Today's charge 9.99; Next charge 29 May 2026; authorization text present; no trial/signup fee text. debug.log remains 0 bytes.
