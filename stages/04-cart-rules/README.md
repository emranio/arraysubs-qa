# Stage 04 — Cart Validation Rules

**Goal:** Exercise every cart validation rule documented in the Subscription Checkout user manual. For each rule, toggle the relevant General Settings option, attempt to violate the rule in the storefront cart, and confirm the exact error string from the manual is shown. Repeat the critical rules on Block Checkout to confirm Store API parity.

**Prerequisites:**
- Stage 03 complete — the subscription product catalog is available. The canonical members exercised here are:
  - `Standard Weekly` ($19.99/wk, no trial, no fee) — most-used cart-rule subject.
  - `Trial Weekly` ($19.99/wk, 7-day trial) — trial-restriction tests.
  - `Basic Plan` ($9.99/wk), `Pro Plan` ($19.99/wk), `Enterprise Plan` ($39.99/wk) — plan ladder for migration tests.
  - `PM Tool` (Variable: Weekly $9.99 / Bi-weekly $14.99) — variable-product behaviour.
  - `Coffee Plan` (Variable: Daily $2.99 / Weekly $14.99 +$4.99 fee / Bi-weekly $24.99) — different-cycle pairings.
  - `Basic Monthly` ($29.99/mo) — used **only** for the "different billing cycles" pairing because it is the catalog's sole monthly product.
  - A regular non-subscription product (e.g., `Plain Mug`) for the mixed-cart rule.
- Test customer accounts: `cust1@example.com`, `cust2@example.com`, plus the ability to test as a guest.
- Both Classic Checkout (shortcode page) and Block Checkout (block-based page) are available on the test site.
- Stripe test mode is **already configured** by the developer; the QA tester does not run any gateway setup. PayPal and Paddle are out of scope for this regression cycle.

**Run order:**
1. [01 — Mixed cart rule](01-mixed-cart-rule.md) — `Standard Weekly` + `Plain Mug` must be blocked when disabled.
2. [02 — Multiple subscriptions in cart](02-multiple-subscriptions-in-cart.md) — `Standard Weekly` + `Basic Plan` (both weekly) cannot coexist when disallowed.
3. [03 — One subscription per product](03-one-per-product-rule.md) — Quantity 2 of `Standard Weekly` is rejected.
4. [04 — Different billing cycles](04-different-billing-cycles.md) — `Standard Weekly` + `Basic Monthly` (the canonical weekly+monthly pairing) and `Standard Weekly` + `Coffee Plan` Daily (two non-month different cycles) cannot share a cart when disallowed.
5. [05 — One trial per customer](05-one-trial-per-customer.md) — Logged-in customer cannot start a second `Trial Weekly`; guest is exempt.
6. [06 — One per customer with auto-migrate](06-one-per-customer-with-auto-migrate.md) — `Basic Plan` ($9.99/wk) → `Pro Plan` ($19.99/wk) auto-migration at checkout.
7. [07 — Block checkout parity](07-block-checkout-parity.md) — Repeat the critical rule checks on the Store API Block Checkout using the new catalog.

**Exit criteria:**
- Every error string captured and matches the user manual exactly.
- After completing all tests, all toggled-off settings are restored to their default values (defaults are listed in each task) so later stages start from a known baseline.
- Block Checkout shows the same errors via Store API for at least the mixed-cart and multiple-subscriptions rules.
- Cart is empty in the test browser at end of each task.
