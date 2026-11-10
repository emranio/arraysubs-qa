---
id: 164
title: Live checkout rejects the advertised Personal Lifetime promotion
status: open
priority: high
created: 2026-11-11T02:03:48.494002+06:00
updated: 2026-11-11T02:17:57.008098+06:00
tags:
    - bug
    - checkout
    - coupons
class: standard
---

Active QA task ID, scheduled day, and plan path are N/A. Found during focused checkout verification on 2026-11-11. Subscription and order IDs are N/A. WordPress and customer IDs and logins are N/A. The guest browser used a synthetic email and submitted no order.

### Reproduction

1. Open https://subscriptionplugin.pro/pricing/ in fresh Chromium 146 Incognito at 1280 × 800. Choose Personal Lifetime, fill Your details, then select Continue.
2. On https://user-portal.arrayhash.com/checkout/personal/?coupon=special30&billing_cycle=lifetime, inspect the embedded total. Open Have a promotional code?, enter special30, and select Apply.

The pricing card advertises $188.30. The later live run showed the $269.00 total, and manual application displayed “Invalid promotional code.” The portal and provider requests both carried coupon=special30. The current deployed code was used for this control.

Earlier that day, the same live plan and coupon displayed “Coupon discount (Save 30%)” and $188.30. The later fresh checkout rejected the code even with its original prefill mode. The provider's rejection reason beyond the visible invalid-code message is unknown.


### Black Friday follow-up

The campaign source now sends bfcm60 from every pricing card and defaults to it on paid portal checkout through December 10. Live Freemius coupon 100606 was verified at 60% across plans and billing cycles. Its expiration was set and read back as 2026-12-10 17:59:59 UTC, the last second of December 10 in Bangladesh. The other coupon settings were checked against the original values. The updated website, guide and portal are verified locally and await rollout.
