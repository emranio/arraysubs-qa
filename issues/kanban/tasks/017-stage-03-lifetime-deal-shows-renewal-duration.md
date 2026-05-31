---
id: 17
title: 'stage-03: Lifetime deal shows renewal/duration instead of no recurring charges'
status: closed
priority: high
created: 2026-05-20T08:22:03.449718534+02:00
updated: 2026-05-22T00:50:20.849975649+02:00
started: 2026-05-22T00:42:57.42018837+02:00
completed: 2026-05-22T00:50:20.849974577+02:00
tags:
    - qa
    - stage-03
    - lifetime
    - checkout
claimed_by: mold-glade
claimed_at: 2026-05-22T00:50:20.849975549+02:00
class: standard
---

Observed with Alumnium on 2026-05-20 for Lifetime Deal #227. Product page showed '99.00 Lifetime Deal' but also '5 billing cycles' after setting Subscription Length 5. Cart/checkout showed 'Renewals: 99.00 Lifetime Deal' and 'Duration: 5 billing cycles'. Expected Stage 03 task 05: length ignored for Lifetime Deal, one-time 99.00, no recurring schedule, checkout summary exact/equivalent 'Lifetime Deal — No recurring charges', no next-charge date.

[[2026-05-22]] Fri 00:44
Verified Stage 03 Task 05 and code paths. Root cause: lifetime products still reuse recurring display helpers, so helpers add 'Lifetime Deal' as a schedule and still render stored subscription length in product/cart/checkout metadata. Plan: normalize lifetime subscription product data to interval 1 + length 0, reset length on product/variation save, render product and cart item pricing as one-time price with no /period suffix, show a single 'Lifetime Deal — No recurring charges' summary row instead of Renewals/Duration/Next charge rows, and skip recurring authorization copy for lifetime-only checkout. Then verify Lifetime Deal #227 product/cart/checkout with Alumnium.

[[2026-05-22]] Fri 00:50
Fix applied. Lifetime product data now normalizes to interval 1 and length 0, lifetime saves reset subscription length, product/cart/checkout displays no recurring schedule suffix, and lifetime cart/checkout metadata shows 'Lifetime Deal — No recurring charges' without Renewals, Duration, Next charge, or recurring authorization rows. Browser QA with #227 and _subscription_length forced to 5: product page shows only Lifetime Deal 99.00 and Subscribe Now; cart shows Subscription: Lifetime Deal — No recurring charges, Today's charge 99.00, Estimated total 99.00; checkout shows Lifetime Deal — No recurring charges, Today's charge 99.00, total 99.00, and payment methods visible. debug.log remains 0 bytes.
