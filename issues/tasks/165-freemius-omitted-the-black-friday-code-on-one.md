---
id: 165
title: Freemius omitted the Black Friday code on one Agency mobile checkout
status: open
priority: medium
created: 2026-11-11T02:17:57.0381+06:00
updated: 2026-11-11T02:17:57.0381+06:00
tags:
    - bug
    - checkout
    - coupons
class: standard
---

Active QA task ID, scheduled day, and plan path are N/A. Focused Black Friday checkout verification on 2026-11-11. Subscription and order IDs are N/A. WordPress and customer IDs and logins are N/A. This was a signed-out browser with synthetic checkout details.

### Reproduction and observation

1. Open http://localhost:3005/pricing/ from the updated production build in a fresh Chromium Incognito session at 390 × 844 with --test-third-party-cookie-phaseout. Choose Agency Lifetime and submit the guest details modal.
2. On http://localhost:3001/checkout/agency/ verify the inline Freemius order summary. Expected bfcm60 and a 60% discount. The first run showed the full $899.00 total and Have a promotional code?.
3. The actual Freemius POST returned 200 with coupon=bfcm60, plan_id=55250, billing_cycle=lifetime and licenses=1000. The three guest fields rendered. Manually applying bfcm60 in that same browser succeeded and changed the total to $359.60.

### Scope

The first miss did not recur in fresh Agency mobile sessions with strict cookie blocking or standard Incognito. Both applied the coupon automatically, as did the Agency desktop flow and the current live hosted and embedded Agency checkout URLs. Both Personal pricing paths applied 60% automatically. The cause of the single provider-side omission is unconfirmed.
