---
id: 63
title: 'stage-05: 03 Trial Checkout — No Signup Fee'
status: closed
priority: high
created: 2026-05-19T22:56:06.926342136+02:00
updated: 2026-05-20T11:50:29.816747051+02:00
started: 2026-05-20T10:28:05.933646119+02:00
completed: 2026-05-20T11:50:29.816746049+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T11:50:29.816746951+02:00
class: standard
---

Source: stages/05-checkout/03-trial-checkout-no-fee.md

[[2026-05-20]] Wed 11:50
QA done on active block checkout (classic unavailable due task #61/#26). Created/reset customer-trial@example.test user #10. One-trial and require-payment-method settings enabled. Cart Trial Weekly passed: added without prior-trial error, line showed previous 9.99, discounted /bin/bash.00, renewals 9.99 Every week, Trial 7 days free trial, cart total /bin/bash.00. Checkout summary showed renewals/trial/payment methods, but did not show exact 'Free (trial starts today)', next charge date, or authorization notice; covered by existing summary defects. Direct bank transfer checkout placed order #368 total /bin/bash.00. Thank-you page related subscription #384 status Trial, next 27 May 2026 (UTC+6). WP-CLI verified subscription #384 status arraysubs-trial, product #202, trial_length 7, recurring amount 19.99, next_payment_date and trial_end_date both 2026-05-27 09:49:26, parent order #368.
