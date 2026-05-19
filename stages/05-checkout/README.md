# Stage 05 — Checkout Flow

**Goal:** Walk every supported subscription checkout path end-to-end in a real browser. Stage 05 verifies that the cart, classic checkout, block checkout, trial handling, signup fees, account auto-creation, one-click checkout, coupons, plan switching, Stripe gateway flow, Checkout Builder customization, and mixed carts all render the correct subscription summary, charge the correct amount today, and create the correct subscription record once payment completes. Every task ends with a paid order that Stage 06 will inspect.

**Prerequisites:**
- Stage 00 (Pre-flight & Environment Verification) passed.
- Stage 01 (Setup Wizard) passed — store currency, base address, and email defaults are set.
- Stage 02 (Settings) passed — General Settings cart rules, grace periods, and email reminder schedule confirmed.
- Stage 03 (Products) passed — the reusable products listed below exist and are published.
- Stage 04 (Cart Rules) passed — mixed-cart, multiple-subscriptions, one-trial-per-customer rules verified.
- **Stripe is already configured** by the developer (test-mode keys + webhook secret saved). No Stripe setup is performed in this stage; only verification of the existing connection. PayPal and Paddle are out of scope for this regression cycle.

**Reusable products (created in Stage 03):**
- `Standard Weekly` — simple subscription, $19.99 / week, no trial, no signup fee. (Canonical "the simple weekly subscription".)
- `Basic Monthly` — simple subscription, $29.99 / month, no trial, no signup fee. The single monthly product in the catalog.
- `Trial Weekly` — simple subscription, $19.99 / week after a 7-day trial, no signup fee.
- `Signup Fee Weekly` — simple subscription, $19.99 / week, no trial, $4.99 signup fee.
- `Stepped Weekly` — simple subscription, first 3 payments at $19.99/week, then $29.99/week thereafter.
- `Basic Plan` / `Pro Plan` / `Enterprise Plan` / `Crossgrade Plus` — all $9.99 / $19.99 / $39.99 / $19.99 per week respectively, used for the plan-switch ladder.
- `Standard Tee` — regular (non-subscription) product, $15.00, used for mixed-cart tests.

**Gateway scope:**
- **Stripe** — already configured (test mode). Verify connection only.
- **Manual** — Direct bank transfer (BACS) and Cash on delivery enabled.
- **PayPal / Paddle** — out of scope. No setup, no flows.

**Run order:**
1. [01 — Classic checkout: basic subscription](01-classic-checkout-basic-subscription.md) — Logged-in customer buys `Standard Weekly` via Classic Checkout. Verifies the subscription summary block, "every 1 week" recurring text, today's charge total, and next charge date.
2. [02 — Block Checkout parity](02-block-checkout-parity.md) — Repeat the same `Standard Weekly` purchase through Block Checkout and confirm parity.
3. [03 — Trial checkout, no fee](03-trial-checkout-no-fee.md) — Buy `Trial Weekly` (7-day trial). Verify "Free (trial starts today)" text, status `Trial`, next payment = trial end date.
4. [04 — Trial checkout with signup fee](04-trial-checkout-with-signup-fee.md) — Buy a trial-with-signup-fee combination. Confirm only the signup fee is charged today, recurring is weekly.
5. [05 — Different renewal price summary](05-different-renewal-price-summary.md) — Buy `Stepped Weekly`. Verify "$19.99 every 1 week for the first 3 payments, then $29.99 every 1 week".
6. [06 — Auto-create account as guest](06-auto-create-account-as-guest.md) — Guest buys `Standard Weekly` with auto-create-account ON.
7. [07 — Guest checkout with auto-create OFF](07-guest-checkout-with-auto-create-off.md) — Guest buys `Standard Weekly` with auto-create OFF.
8. [08 — One-click checkout](08-one-click-checkout.md) — One-click on `Standard Weekly`.
9. [09 — Checkout with coupon](09-checkout-with-coupon.md) — Apply one-time and recurring coupons to `Standard Weekly`.
10. [10 — Plan switching at checkout](10-plan-switching-at-checkout.md) — Customer with active `Basic Plan` ($9.99/wk) buys `Pro Plan` ($19.99/wk).
11. [11 — Stripe test card flow](11-stripe-test-card-flow.md) — *(Pro)* Verify the existing Stripe connection, place success/SCA/decline transactions on the new catalog.
12. [12 — Stripe webhook & payment-method lifecycle](12-paypal-and-paddle-flows.md) — *(Pro)* Webhook event verification, card-expiring-soon flow, payment-method update through the customer portal.
13. [13 — Checkout Builder customization](13-checkout-builder-customization-pro.md) — *(Pro)* Custom Text/Heading/Image Select fields on a `Standard Weekly` purchase.
14. [14 — Mixed cart and Block Checkout](14-mixed-cart-and-block-checkout.md) — Mixed cart of `Standard Weekly` + `Standard Tee` through Block Checkout.

**Exit criteria:**
- Every task ends with a paid order (or a successfully created Trial subscription) and a subscription record visible in **ArraySubs → Subscriptions**.
- The "By completing this purchase, you authorize us to charge..." authorization notice appears in every checkout summary covered by these tasks.
- Subscription status, today's charge text, and next charge date are correct for every variant tested (Active, Trial, Pending).
- Plan switching modifies the existing subscription rather than creating a duplicate.
- Stripe test mode renders SetupIntent (trial) or PaymentIntent (paid), completes 3DS challenge for the SCA card, and reflects decline cleanly for the decline card.
- Stripe webhook events (`payment_intent.succeeded`, `payment_intent.payment_failed`, etc.) are received and listed in the Pro Gateway Health Dashboard.
- Checkout Builder fields are stored as `_arraysubs_cf_*` order meta and copied to the subscription when **Copy to subscription** is enabled.
- All test order IDs and subscription IDs are recorded in the Sign-off blocks for use in Stage 06.
