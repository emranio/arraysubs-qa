---
id: 133
title: 'stage-5: Stripe subscription, regular, and mixed checkout QA'
status: done
priority: critical
created: 2026-08-15T08:57:01.454075383+02:00
updated: 2026-08-15T09:57:48.963956332+02:00
started: 2026-08-15T09:57:48.96395531+02:00
completed: 2026-08-15T09:57:48.96395531+02:00
tags:
    - checkout
    - stripe
    - payments
    - regression
class: standard
---

Real staging browser checkout matrix after automatic-payment migration: Stripe test-mode subscription-only, regular-product-only, mixed cart, SCA/cancel negative checks, order/subscription/payment binding, console/network, webhook and Mailpit evidence. Use disposable identities and record exact IDs.

[[2026-08-15]] Sat 08:59
Stripe QA started by hawk-twig. Required matrix: paid subscription, regular-only, mixed cart, SCA, and Stripe-hosted cancel/return; all fixtures will remain for root audit.

[[2026-08-15]] Sat 09:57
Stripe matrix completed on real staging with isolated agent-browser sessions.

PASS subscription-only: customer #463, order #27280 processing USD 19.99, exactly one active subscription #27296, Stripe cus_/pm_/ch_ binding, Visa 4242, three indexed renewal actions, two Stripe success webhooks, four expected mails.
PASS regular-only: customer #464, order #27315 processing USD 15.00 for Tee #447, subscription delta exactly zero, no ArraySubs actions, two Stripe success webhooks, two order mails.
PASS mixed: customer #465, order #27346 processing USD 34.99 with subscription product #200 plus regular product #447, subscription delta exactly one active #27362, correct Stripe binding/actions, two success webhooks, four expected mails.
PASS SCA: customer #466, 3D Secure 2 modal captured and COMPLETE clicked once; the same pending order #27387 and pending subscription #27403 became processing/active. Visa3184 binding, indexed actions #23942/#23943/#23944, webhook chain #1206 requires_action -> #1208 charge.succeeded -> #1210 payment_intent.succeeded, verification plus four final mails.
PASS cancel/return equivalent for installed embedded Payment Element: customer #467 received visible incomplete-payment validation, returned to cart with item retained, and remained at zero orders, zero subscriptions, zero webhook delta, and zero mail delta.
Browser errors were empty in every session; network payment/challenge/order endpoints returned 200; only the known WooCommerce wcBlocksData dependency warning appeared. Debug checkout windows had no runtime errors. Final refreshed Gateway Logs showed Stripe Connected Test Mode, both webhooks Configured, and subscription count 13 -> 16, matching the exact three successful subscription paths.
Historical duplicate official-webhook subscription notes are a longstanding non-payment defect, not introduced by the migration; coordinator logged formal QA issue #1.
Full report: qa/artifacts/payment-migration-regression-20260815/stripe/report.md
Screenshots: qa/artifacts/payment-migration-regression-20260815/stripe/screenshots/
