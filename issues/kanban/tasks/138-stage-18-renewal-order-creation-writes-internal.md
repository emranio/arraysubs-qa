---
id: 138
title: 'stage-18: Renewal order creation writes internal _payment_tokens meta'
status: closed
priority: medium
created: 2026-05-23T16:45:23.889783853+02:00
updated: 2026-05-24T20:52:26.128284044+02:00
started: 2026-05-24T20:47:42.374578868+02:00
completed: 2026-05-24T20:52:26.128282761+02:00
tags:
    - qa
    - stage-18
    - renewals
    - woocommerce
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:52:26.128283944+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/03-successful-automatic-renewal-pro.md\n\nDuring Task 18.03, running arraysubs_generate_upcoming_renewals for Stripe subscription #1436 created renewal order #1440 but WP-CLI emitted WooCommerce notices twice:\n\nFunction is_internal_meta_key was called incorrectly. Generic add/update/get meta methods should not be used for internal meta data, including "_payment_tokens". Use getters and setters.\n\nTrace points to ArraySubs\Features\RecurringBilling\Services\OrderCreation->createRenewalOrder(), where renewal order token copying uses update_meta_data('_payment_tokens', ...).\n\nImpact: renewal generation works, but creates WooCommerce doing_it_wrong notices and may pollute debug output/logs. Expected: renewal orders copy payment tokens using WooCommerce order token APIs or approved storage, with no internal-meta notices.

[[2026-05-24]] Sun 20:47
Claimed. Inspecting renewal order token copy and WooCommerce payment-token APIs.

[[2026-05-24]] Sun 20:49
Implemented: renewal order token copy now uses WC_Payment_Tokens::get() and WC_Order::add_payment_token(); removed direct update_meta_data('_payment_tokens', ...).

[[2026-05-24]] Sun 20:52
Fixed and verified. Renewal order creation now uses WC_Order::add_payment_token() instead of internal _payment_tokens meta. Verification on Stripe subscription #1436: generated renewal order #3019 without WooCommerce doing_it_wrong notices; order payment_tokens=[7], _payment_tokens meta empty, subscription stayed active; cleanup cancelled order #3019 and restored next_payment=2026-05-31 12:01:22 with pending meta 0. Browser screenshot qa/artifacts/issue-138/subscription-1436-token-renewal-order.png.
