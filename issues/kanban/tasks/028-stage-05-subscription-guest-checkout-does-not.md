---
id: 28
title: 'stage-05: Subscription guest checkout does not force account creation form'
status: closed
priority: critical
created: 2026-05-20T12:06:42.269619758+02:00
updated: 2026-05-21T21:23:26.16090868+02:00
started: 2026-05-21T21:12:06.902136181+02:00
completed: 2026-05-21T21:23:26.160905634+02:00
tags:
    - qa
    - stage-05
    - checkout
    - account-creation
    - guest
claimed_by: mold-glade
claimed_at: 2026-05-21T21:23:26.160908499+02:00
class: standard
---

Stage 05 Task 06 with auto_create_account=true, Woo guest checkout enabled, and checkout signup enabled. As an unauthenticated guest with Standard Weekly in cart, checkout showed email and billing fields plus a Log in link, but no Account password field, no Create an account toggle forced on, and no registration form. A notification also appeared: 'Only express payment methods are available for this order. Please select one to continue.' This violates the manual requirement that ArraySubs forces WordPress/WooCommerce registration form for subscription purchases rather than allowing guest-only checkout.

[[2026-05-21]] Thu 21:13
Plan: verify Stage 05 Task 06 against current Woo settings; patch core checkout registration handling so subscription carts with auto_create_account force checkout registration enabled, registration required, and a required account password field even when WooCommerce auto-generates passwords globally; retest guest classic checkout form and order/subscription ownership in browser; clean test artifacts and close/block.

[[2026-05-21]] Thu 21:23
Fix: core checkout now forces checkout registration enabled and required for guest subscription carts when ArraySubs auto_create_account is on, and injects a required Create account password field even if WooCommerce globally auto-generates passwords. Verification: guest checkout on Classic Checkout QA showed Create account password with no Create account opt-out toggle; completed BACS order #813 after agent-browser XHR workaround; Woo order customer_id was new user #31, role customer; subscription #814 had _customer_id=31 and _parent_order_id=813; My Account Subscriptions showed #814 Standard Weekly pending; password Test1234!Guest verified. Cleaned order #813, subscription #814, and user #31.
