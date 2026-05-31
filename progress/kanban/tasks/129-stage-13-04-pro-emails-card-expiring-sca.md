---
id: 129
title: 'stage-13: 04 Pro Emails — Card Expiring + SCA Authentication Required'
status: closed
priority: medium
created: 2026-05-19T22:56:17.131404141+02:00
updated: 2026-05-24T11:50:58.067175613+02:00
started: 2026-05-23T08:06:53.442658841+02:00
completed: 2026-05-23T10:52:16.427235374+02:00
tags:
    - qa
    - stage-13
class: standard
---

Source: stages/13-emails/04-pro-emails.md

[[2026-05-23]] Sat 10:52
Stage 13 Task 04 complete with delivery blocker. PayPal/Paddle skipped per instruction. WC mailer registered/enabled: arraysubs_renewal_requires_verification, arraysubs_card_expiring, plus Woo Stripe SCA emails. Code wiring present in StripeDelegate: arraysubs_card_expiring and arraysubs_renewal_requires_verification actions. Render smoke via wp_mail interception: Card Expiring for #683 to cust1, subject Update the card for subscription #683, body shows card ending 3184, expiry 06/2026, update payment link, no raw placeholders. SCA/Renewal Requires Verification for #683 order #1034 to cust1, subject Verify your subscription renewal #683, body shows renewal order #1034, Visa ending 3184, grace period, wc-stripe-confirmation link, no raw placeholders; missing payment amount, issue #89. Toggle smoke: card disabled=0/re-enabled=1; SCA disabled=0/re-enabled=1. Actual Stripe checkout/3DS/browser renewal + inbox proof blocked by #40. Debug log: no new product Stripe/email errors beyond old AS fatal and prior QA-command typo warning.

[[2026-05-24]] Sun 11:50
Follow-up fix for linked issue #89 completed. SCA renewal verification email now includes payment amount and renders `{auth_url}`, `{payment_amount}`, `{auth_deadline}` aliases. Verified with wp_mail interception for subscription #683 / renewal order #1034: amount `$28.99`, Stripe confirmation URL, and deadline were present.
