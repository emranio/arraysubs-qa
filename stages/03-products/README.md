# Stage 03 — Subscription Product Creation

**Goal:** Create and validate the full library of subscription products that the rest of the regression suite will exercise. Every billing pattern (simple, trial, signup fee, tiered renewal, lifetime, fixed length, variable, plan-switching links, fixed-period membership, feature manager, coupons, lifecycle) is covered here so later stages have a clean, known-good catalog to use.

**Prerequisites:**
- Stage 00 — Preflight passed (plugins active, WooCommerce healthy).
- Stage 01 — Setup Wizard completed.
- Stage 02 — Settings configured (general settings, plan switching defaults).
- Two test customer accounts available: `cust1@example.com`, `cust2@example.com` (used in later stages).

**Canonical product catalog**

To make renewals feasible inside a real-time regression run, almost every test product uses **days or weeks** for its billing interval. **Exactly one product** uses `month` so that month-interval edge cases (calendar-aware date math) still get coverage.

| # | Product name | Type | Billing | Price | Trial | Notes |
|---|---|---|---|---|---|---|
| 1 | **Basic Monthly** | Simple | every 1 month | $29.99 | – | The single monthly product. Used wherever month-interval behaviour must be checked. |
| 2 | **Standard Weekly** | Simple | every 1 week | $19.99 | – | Canonical reusable subscription. Most lifecycle, portal, and email tests run on this. |
| 3 | **Trial Weekly** | Simple | every 1 week | $19.99 | 7 days | Trial-related tests. |
| 4 | **Signup Fee Weekly** | Simple | every 1 week | $19.99 | – | $4.99 signup fee. |
| 5 | **Stepped Weekly** | Simple | every 1 week | $19.99 → $29.99 after 3 cycles | – | Different-renewal-price tests. |
| 6 | **Lifetime Deal** | Simple | Lifetime | $199 | – | Lifetime / one-time path. |
| 7 | **Fixed-Length Weekly (6 cycles)** | Simple | every 1 week | $24.99 | – | Length-based expiration. |
| 8 | **Limited 3-Cycle Weekly** | Simple | every 1 week | $19.99 | – | Expiring-soon and end-of-term tests. |
| 9 | **PM Tool** | Variable | Weekly $9.99 / Bi-weekly (every 2 weeks) $14.99 | – | – | Variable-product baseline. |
| 10 | **Coffee Plan** | Variable | Daily $2.99 / Weekly $14.99 (with $4.99 signup fee) / Bi-weekly $24.99 | – | varied | Variable + signup-fee + per-variation independence. |
| 11 | **Basic Plan** | Simple | every 1 week | $9.99 | – | Plan ladder — Basic. |
| 12 | **Pro Plan** | Simple | every 1 week | $19.99 | – | Plan ladder — Pro (upgrade target). |
| 13 | **Enterprise Plan** | Simple | every 1 week | $39.99 | – | Plan ladder — Enterprise. |
| 14 | **Crossgrade Plus** | Simple | every 1 week | $19.99 | – | Crossgrade target for Pro. |

**Run order:**
1. [01 — Simple subscription (Basic Monthly)](01-simple-subscription-basic.md) — Establish the canonical "Basic Monthly" $29.99/mo product (the sole monthly product).
2. [02 — Simple with trial](02-simple-with-trial.md) — `Trial Weekly` $19.99/wk + 7-day trial.
3. [03 — Simple with signup fee](03-simple-with-signup-fee.md) — `Signup Fee Weekly` $19.99/wk + $4.99 signup fee.
4. [04 — Simple with different renewal price](04-simple-with-different-renewal-price.md) — `Stepped Weekly` $19.99 → $29.99 after 3 cycles.
5. [05 — Simple Lifetime Deal](05-simple-lifetime-deal.md) — One-time billing period; verify interval lock to 1.
6. [06 — Simple fixed-length subscription](06-simple-fixed-length.md) — `Fixed-Length Weekly (6 cycles)` $24.99/wk.
7. [07 — Variable subscription (Weekly vs Bi-weekly)](07-variable-subscription-monthly-vs-annual.md) — `PM Tool` Variable: Weekly $9.99 + Bi-weekly $14.99.
8. [08 — Variable subscription (mixed trials/fees)](08-variable-subscription-mixed-trials.md) — `Coffee Plan` Variable: Daily / Weekly / Bi-weekly with mixed trials and fees.
9. [09 — Validation rules](09-validation-rules.md) — Trigger and confirm the four save-time validation messages.
10. [10 — Linked Products: upgrade / downgrade / crossgrade](10-linked-products-upgrade-downgrade-crossgrade.md) — Plan ladder `Basic Plan` / `Pro Plan` / `Enterprise Plan` + `Crossgrade Plus` (all weekly).
11. [11 — Fixed Period Membership (Pro)](11-fixed-period-membership-pro.md) — Absolute end date (week-out), recurring annual cutoff, enrollment window.
12. [12 — Feature Manager entitlements (Pro)](12-feature-manager-entitlements-pro.md) — Define Seats / Storage features on `Pro Plan` and verify storefront toggle.
13. [13 — Product experience display](13-product-experience-display.md) — Cross-check pricing display on product page, cart, checkout, mini-cart, order confirmation across the new catalog.
14. [14 — Coupon: Apply to subscriptions](14-coupon-applicable-to-subscriptions.md) — Eligible vs ineligible coupons against `Standard Weekly`.
15. [15 — Recurring coupon with cycle limit](15-recurring-coupon-with-cycle-limit.md) — 3-cycle recurring discount on `Standard Weekly`.
16. [16 — Product lifecycle: trash and restore](16-product-lifecycle-trash-and-restore.md) — Trash/restore admin warnings on a weekly product.

**Exit criteria:**
- All 16 task files signed off as PASS (or failures recorded with reproduction notes).
- The full canonical catalog is publishable in WooCommerce (see catalog table above).
- WooCommerce coupon catalog includes:
  - `WELCOME15` — one-time
  - `HALFOFF3` — recurring, 3 cycles, count initial
  - `NOSUB10` — no "Apply to subscriptions" flag
- Validation message strings recorded verbatim for downstream regression diffing.
- No PayPal / Paddle gateway references remain anywhere in this stage. Only Stripe + manual payment are exercised — Stripe is already configured by the developer, so the QA tester does not run any Stripe setup task.
