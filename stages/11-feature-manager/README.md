# Stage 11 — Feature Manager (Pro)

**Goal:** Verify the Pro Feature Manager module end to end — defining feature entitlements on subscription products, surfacing those entitlements on the storefront and in the customer's My Account area, controlling display via Feature Manager settings (per-subscription vs combined, usage tracking visibility), and confirming the admin Feature Log / entitlement review screen with proper capability gating.

**Prerequisites:**
- Stages 00–10 complete.
- ArraySubsPro is active and the Feature Manager module is enabled in **ArraySubs → Settings → Feature Manager**.
- At least one simple subscription product exists from Stage 03 (e.g., `Pro Plan` $19.99/week).
- Test accounts `cust1@test.local`, `cust2@test.local`, `shopmgr@test.local` exist and can log in.
- Stripe test mode is already configured, or a manual fallback (BACS / COD) is enabled, so customers can complete subscription checkouts.

**Run order:**
1. [01 — Define product features](01-define-product-features.md) — Create a feature set (`Seats`, `Storage`, `API_calls`) on the `Pro Plan` weekly subscription product and confirm features persist across reload.
2. [02 — Customer and storefront display](02-customer-and-storefront-display.md) — Enable storefront highlights, confirm the "What's Included" section renders on the `Pro Plan` product page, and verify the My Features page lists entitlements after a customer subscribes.
3. [03 — Feature Manager settings](03-feature-manager-settings.md) — Toggle aggregation between Per-Subscription and Combined, observe display difference for a customer holding both `Pro Plan` and `Enterprise Plan`, and verify the usage column reacts to manual usage updates.
4. [04 — Feature log and entitlement review](04-feature-log-and-entitlement-review.md) — Open the admin Feature Log from the subscription detail screen and from Manage Members, confirm history is visible, and validate capability gating (admin and shop manager see the log; customers cannot).

**Exit criteria:**
- A simple subscription product has at least three features (one Toggle, one Number, one Text), all persisting after page reload.
- The "What's Included" section renders the enabled features on the storefront product page in the order set in admin.
- A subscribed customer sees their feature entitlements on `/my-account/features/` in the configured aggregation mode.
- Switching aggregation mode between **Per Subscription** and **Combine** in settings visibly changes the My Features layout for a customer with two active subscriptions.
- Manual updates to a number-feature's usage counter (via REST or admin tool) are reflected on the customer's My Features page within one refresh.
- The admin Feature Log loads from both the subscription detail and the Manage Members entry points; a logged-in customer cannot reach the same admin URL.
