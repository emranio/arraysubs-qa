---
id: 118
title: Gateway health settings links open blank Stripe and Mollie payment sections
status: closed
priority: medium
created: 2026-10-26T19:19:48.446905+06:00
updated: 2026-10-26T19:24:05.961726+06:00
started: 2026-10-26T19:20:32.429539+06:00
completed: 2026-10-26T19:24:05.961726+06:00
tags:
    - bug
    - admin
    - gateway-settings
class: standard
---

Active QA task ID/scheduled day/plan: N/A (user-reported fix; no active QA cycle).
Affected subscriptions/orders: N/A.
Affected WordPress user: ID 1, login admin, administrator (also demo_member); customer: N/A.
Site/browser: http://localhost:10013, agent-browser administrator session gateway-card-design.
Route: /wp-admin/admin.php?page=arraysubs-mainadmin#/renewal-flow/gateway-logs.
Reproduction: Expand Stripe or Mollie and follow WooCommerce Settings while the official provider plugin is inactive.
Expected: Open a valid provider settings or setup destination.
Actual: section=stripe and section=mollie open WooCommerce Payments with no gateway form. Stripe has a registered arraysubs_stripe_setup placeholder; Mollie has its own mollie_settings tab when active, not a checkout section named mollie.
Proof: qa/cron-notice-scope-2026-10-26/evidence/stripe-settings-before.png and mollie-settings-before.png; browser snapshots show Payments plus Save changes only. Native WC gateway enumeration contains Paddle, PayPal, Stripe setup placeholder and offline methods, with no stripe or mollie gateway.
Scope/counterexamples: PayPal and Paddle card links rendered their proper settings forms in this checkout; both still need regression coverage. Pro shares the core dashboard/controller. No payment settings or plugin activation changed.
Fix plan: Resolve native gateway settings from registered WooCommerce gateway IDs; use Stripe setup placeholder when Stripe is absent; send Mollie to its actual settings tab when active or an appropriate setup destination when inactive; browser-check all card links and retain the new card design and keyboard disclosure behavior.

Implemented native settings URL resolution in the core GatewayHealthController and a setup action label for inactive Mollie. Browser-followed all four refreshed card links: Paddle and PayPal settings forms, Stripe setup screen, and the filtered Mollie plugin setup list all populated successfully. Evidence: qa/cron-notice-scope-2026-10-26/evidence/gateway-settings-results.json and each *-settings-after snapshot/screenshot. Core-only WP-CLI check with arraysubspro skipped returned identical destinations. Build and PHP syntax checks passed. Card design and keyboard disclosure checks also passed; no provider plugin activation, payment configuration, orders or subscriptions changed. Active-host Mollie URL verified from its settings registration; browser verification covers the current inactive-host state.
