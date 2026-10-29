---
id: 149
title: Edge embedded Freemius checkout stalls for default and custom coupons
status: open
priority: high
created: 2026-10-30T04:10:20.171702+06:00
updated: 2026-10-30T04:10:20.171702+06:00
tags:
    - portal
    - freemius
    - edge
    - checkout
class: standard
---

## Scope and identities
- Active QA task ID, scheduled day and plan path: N/A. Follow-up production verification of the customer-requested portal coupon fix on 2026-10-30 (Asia/Dhaka).
- Subscription IDs, order IDs, WordPress customer/user IDs, login/email and roles: N/A. No checkout identity or payment details were entered.
- Browser context: signed-in user-owned Microsoft Edge Profile 1; checkout forms remained anonymous. Browser protections were not changed.
- Custom-coupon route: https://user-portal.arrayhash.com/checkout/agency/?billing_cycle=lifetime&coupon=teclogic80
- Control route: https://user-portal.arrayhash.com/checkout/agency/?coupon=special60&billing_cycle=lifetime&ref=arraysubs

## Reproduction
1. Open the custom-coupon route in Edge after deployment of portal commit 9318a24432a92cdb1167e26e38f48ad7c8f50cb7.
2. Observe the correct coupon and lifetime plan in the portal summary and generated Freemius iframe src.
3. Wait for the embedded checkout to render. Repeat with the normal special60 promotion.

## Expected and actual
- Expected: Freemius renders its hosted payment form inside the modal.
- Actual: both routes remained on Loading Freemius Checkout, with the iframe accessibility page reporting about:blank. The portal status read Secure checkout is ready. The iframe src contained the correct plan 55250, lifetime cycle and coupon for each case.
- Pressing Escape on the custom-coupon page did not dismiss the loading overlay. No diagnostic console error was captured. Root cause is unconfirmed; this comparison does not establish whether the behavior predates the deployment.

## Scope notes and counterexamples
- The direct hosted Freemius Agency checkout works in the same Edge profile: https://checkout.freemius.com/product/33435/plan/55250/?billing_cycle=lifetime&coupon=teclogic80 . It showed $899 subtotal, $719.20 discount and $179.80 USD total.
- The custom-coupon portal flow also worked in a real isolated Chromium session for Personal before deployment, showing $53.80 USD. Edge's local portal iframe had stalled as well.
- The production deployment itself succeeded, including server health checks, Cloudflare purge and public portal verification: https://github.com/emranio/arrayhash-user-portal/actions/runs/40963252418 .
- No provider responses were mocked, no purchase was made and no recovery email was sent.

## Proof
- ../../artifacts/portal-recovery-2026-10-30/deployed-custom-coupon-edge-loading.png
- ../../artifacts/portal-recovery-2026-10-30/deployed-default-coupon-edge-loading.png
- Working hosted checkout: ../../artifacts/portal-recovery-2026-10-30/agency-80-percent-edge.png

## Follow-up
Investigate iframe navigation, provider messaging and Edge behavior without weakening browser protections. Retest both standard and custom offers and preserve the existing checkout handoff guarantees.
