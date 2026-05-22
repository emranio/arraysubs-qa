---
id: 14
title: 'stage-03: Trial checkout summary missing next-charge authorization copy'
status: closed
priority: high
created: 2026-05-20T01:22:41.722080363+02:00
updated: 2026-05-22T00:37:59.114838744+02:00
started: 2026-05-22T00:30:58.405438816+02:00
completed: 2026-05-22T00:37:59.114837552+02:00
tags:
    - qa
    - stage-03
    - checkout
    - trial
claimed_by: mold-glade
claimed_at: 2026-05-22T00:37:59.114838634+02:00
class: standard
---

Observed with Alumnium on 2026-05-20 for Trial Weekly #202. Checkout summary showed recurring '9.99 Every week', free trial '7 days', total '/bin/bash.00', payment methods visible. Expected Stage 03 task 02.5 also requires next charge date 7 days in future and authorization notice beginning 'By completing this purchase, you authorize us to charge'. Actual: next charge date not visible; notice text is generic 'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy'.

[[2026-05-22]] Fri 00:32
Verified against Stage 03 Task 02.5. The expected next charge date and authorization notice exist in checkout-subscription-summary.php, but the active hook uses CheckoutDisplayTrait::displaySubscriptionSummaryInTable(), which only prints recurring/signup/trial rows after the order total. Plan: add live checkout rows for Today's Charge, Next Charge, and a single authorization notice to CheckoutDisplayTrait using the same trial-aware calculations as the richer view. Verify with browser checkout for Trial Weekly #202 that 7-day trial summary shows Free (trial starts today), Next Charge date 7 days out, and notice beginning 'By completing this purchase, you authorize us to charge'.

[[2026-05-22]] Fri 00:37
Fix applied. Added trial-aware Today's charge, Next charge, and authorization summary rows to classic checkout table output, and Store API cart item metadata so block checkout order summary shows same required copy. Browser QA on Trial Weekly checkout now shows: Today's charge Free (trial starts today), Next charge 29 May, 2026 (UTC+6) (9.99), and Authorization: By completing this purchase, you authorize us to charge your payment method for the recurring subscription amounts on the scheduled dates. debug.log remains 0 bytes.
