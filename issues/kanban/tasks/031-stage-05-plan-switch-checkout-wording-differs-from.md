---
id: 31
title: 'stage-05: Plan switch checkout wording differs from manual'
status: closed
priority: medium
created: 2026-05-20T12:54:25.416381647+02:00
updated: 2026-05-22T04:57:31.794362885+02:00
started: 2026-05-22T04:47:49.665224676+02:00
completed: 2026-05-22T04:57:31.794361823+02:00
tags:
    - qa
    - stage-05
    - checkout
    - plan-switching
    - copy
claimed_by: mold-glade
claimed_at: 2026-05-22T04:57:31.794362765+02:00
class: standard
---

Stage 05 Task 10 found two documentation/UI wording mismatches. Checkout/cart migration text says: 'Replaces the customer's current Upgrade plan from Basic Monthly' instead of expected 'Replaces your current Basic Monthly subscription'. Subscription detail notes show 'Upgraded from Basic Monthly to Pro Monthly.' and 'Plan upgrade: Basic Monthly → Pro Monthly'; the exact expected phrase 'Existing subscription updated from checkout migration' exists only as WooCommerce order note #32 on migration order #511, not in the Subscription Notes panel.

[[2026-05-22]] Fri 04:48
Plan: update checkout migration metadata copy to exact manual phrase Replaces your current [Current Product] subscription in both block/cart item metadata and classic checkout summary; add same exact checkout-migration completion phrase to the subscription notes panel when migration order processes; verify code with WP-CLI/browser plan-switch flow, skipping lint/phpcs.

[[2026-05-22]] Fri 04:57
Fix: changed checkout migration copy to exact manual phrase Replaces your current [Current Product] subscription in cart/block metadata and classic checkout summary; added subscription system note Existing subscription updated from checkout migration when checkout migration order processes. Verification: agent-browser customer-switch flow added Pro Monthly, checkout summary showed Replaces your current Basic Monthly subscription and not old Replaces the customer wording; placed BACS order #1013, completed it, admin subscription #508 notes panel showed Existing subscription updated from checkout migration. Lint/phpcs skipped per instruction.
