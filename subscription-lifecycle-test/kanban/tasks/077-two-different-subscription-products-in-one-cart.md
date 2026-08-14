---
id: 77
title: Verify one-click replacement and the default-mode composition guard on every add-to-cart surface
status: done
priority: high
created: 2026-08-02T03:43:09.675926975+02:00
updated: 2026-08-07T16:07:58.248093968+02:00
started: 2026-08-07T16:07:57.9644793+02:00
completed: 2026-08-07T16:07:57.9644793+02:00
tags:
    - checkout
    - day-05
due: "2026-08-07"
estimate: 1h
depends_on:
    - 10
    - 11
    - 5
    - 6
class: standard
---

> **SLT-CHK-06** · group `checkout` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove the two intentionally different cart contracts without conflating them. In the frozen
`subscription_items` one-click mode, each successful one-click add replaces the cart and
keeps the quantity from that request. In a tightly bracketed `default`-mode control,
`multiple_subscriptions.allow_multiple_in_cart = false` rejects a second DIFFERENT
subscription while a second add of the same product merges quantity. Exercise standalone,
archive, classic-cart, and block-cart surfaces. No order is placed.

## Scope
- Gateway: N/A
- Checkout: both
- Account: N/A (anonymous session — the composition guard applies to guests too)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 (harness pages), SLT-SETUP-02, SLT-PROD-01, SLT-PROD-06 complete.
- Frozen baseline: `one_click_mode=subscription_items`, `allow_multiple_in_cart=false`, `allow_mixed_cart=true`, `one_per_customer=false`, `one_per_product=false`, `allow_different_cycles=true`.
- One-click contract: Stage 05 task 08 and the General Settings UI explicitly say that a one-click add clears existing cart contents and keeps only the clicked item. `OneClickCheckoutTrait::maybeKeepOnlyOneClickItemInCart()` performs that normalization after a successful add, while `CartValidationTrait::validateCartComposition()` deliberately bypasses composition checks for one-click products.
- Default-mode contract: the add-time guard in `CartValidationTrait::validateCartComposition()` runs on `woocommerce_add_to_cart_validation` and refuses when `count($cart_would_have_distinct_subscriptions) > 1`. The same message exists in `CartValidation::getCartValidationErrors()` for classic and Store API defence in depth.
- This task alone authorizes one short `one_click_mode=default` bracket. Preserve the exact full settings value/hash before changing it, restore `subscription_items` immediately after the default-mode checks, and prove the full settings hash is identical after restore.

## Test data
| Item | Value |
|---|---|
| Product A | SLT Daily Core, `/product/slt-daily-core/`, $10.00, day/1 |
| Product B | SLT Fixed Three Cycles, `/product/slt-fixed-three-cycles/`, $7.00, day/2 |
| Session | `--session guest-SLT-CHK-06` (unique; no cart collision) |
| Expected error | `Multiple subscription plans are disabled for one checkout. Keep only one subscription plan in the cart, then place a separate order for any other plan.` |

## Steps
1. Resolve strict numeric, distinct `A_ID` and `B_ID` by exact slugs; verify their titles, prices, schedules, and subscription flags, then record `MB06=$(mailpit-agent latest-id)`.
2. `agent-browser --session guest-SLT-CHK-06 open "https://mirror-help.arrayhash.com/slt-classic-cart"` → `snapshot -i`; assert empty and capture `SLT-CHK-06-00-cart-empty-before.png`.
3. While the frozen mode remains `subscription_items`, add `$A_ID`. Require a direct checkout redirect and exactly `$A_ID`, quantity 1, `$10.00`; capture the checkout and classic-cart states.
4. Add distinct `$B_ID`. Require a direct checkout redirect, no composition error, and exactly `$B_ID`, quantity 1, `$7.00`; `$A_ID` must be absent because one-click replacement is the documented behavior. Repeat through the exact archive add link and require the same result.
5. Add `$A_ID` twice using separate quantity-1 requests. Require only `$A_ID`, quantity 1, `$10.00`. Then make one `$A_ID` request with quantity 3 and require exactly quantity 3, `$30.00`. This proves one-click retains the current request quantity rather than accumulating earlier requests.
6. Empty the cart. Preserve the exact complete settings value and hash, then in `admin-SLT-CHK-06` change only **One Click Checkout** to **Default**. Save, reload, and require every other setting unchanged.
7. In the guest session add `$A_ID`, then attempt distinct `$B_ID`. Require the second add to fail, `$A_ID` to remain the sole quantity-1 line, and the exact expected error on the classic cart. Copy it byte-for-byte to the task evidence and compare it with the expected-text file.
8. Add `$A_ID` again in default mode. Require the sole line to merge to quantity 2 and subtotal `$20.00`.
9. Open `/cart` and `/checkout`; require the one-line quantity-2 state and no `arraysubs_cart_error`. Do not enter payment data or place an order.
10. Empty the cart. Restore **One Click Checkout** to **Enabled for subscription items**, save/reload, and require the complete settings hash to match the pre-bracket hash. Verify both the browser cart and the user's persistent cart are empty.
11. Inspect every Mailpit message newer than `MB06`; require zero task-attributable mail and classify background mail. Capture browser errors/console, close only `guest-SLT-CHK-06` and `admin-SLT-CHK-06`, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. In `subscription_items` mode, each successful one-click request leaves only the clicked product; a repeated quantity-1 request stays at quantity 1, while one quantity-3 request stays at quantity 3.
2. In `default` mode, the distinct `$B_ID` add is refused, cart item count stays 1, and `$A_ID` remains untouched.
3. The default-mode notice equals the Expected error exactly, including the final period.
4. In `default` mode, the second same-product add merges to quantity 2 and subtotal `$20.00` because the rule counts distinct product IDs.
5. Block cart and block checkout show the valid one-line state and zero `arraysubs_cart_error`.
6. The complete settings hash, session cart, and persistent cart are restored exactly; no order or email is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | no order is placed | — | — | Complete delta after `MB06`; file only a task-attributable leak, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Safe named `SLT-CHK-06-00` through `-08` cart/rejection/archive captures; numeric product IDs; exact-byte notice files/comparison; archive console/network response; Mailpit/session/review proof.

## Pass criteria
- [ ] One-click mode replaces the cart and retains the current request quantity
- [ ] Default mode rejects the second distinct subscription and preserves the first
- [ ] Default-mode notice string matches exactly
- [ ] Default mode merges the same product to qty 2
- [ ] Block cart and block checkout raise no cart error for the valid state
- [ ] Full settings hash and both browser/persistent carts are restored exactly
- [ ] Zero mail sent
- [ ] Exact sessions closed and evidence reviewed to done

## Isolation / teardown
- Cart emptied and both task sessions closed. The one-click setting is temporarily bracketed and restored with an exact full-value hash; no other setting, product, user, coupon, order, or subscription is created or changed.
- Handed on: the confirmed rejection string, reused as the negative control in SLT-CHK-07 and SLT-CHK-09.

---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 21:32
Preflight 2026-08-06: authored guest fixtures still match the task. Product A slt-daily-core = 11927 (published). Product B slt-fixed-three-cycles = 11933 (published). No extra fixture creation is needed before the guest-only, orderless D5 run.

[[2026-08-06]] Thu 22:22
As of 2026-08-06 readiness review: no current source-block is visible from live evidence. This card remains a valid Friday, August 7, 2026 candidate and must stay in todo until that date; do not open it early.

[[2026-08-07]] Fri 16:07
D05 execution complete — FAIL. Anonymous session `guest-SLT-CHK-06` began and ended with an empty cart and emitted zero mail (Mailpit baseline/final newest ID `5hm1LQfe2IKo0vIamcv9kd`). With frozen `allow_multiple_in_cart=false` plus `one_click_mode=subscription_items`, standalone and archive adds of B=11933 while A=11927 was present both bypassed the authored refusal: success notice appeared, A was silently replaced by B, and the exact expected rejection was never observed (`cmp` exit 1). The same-product discriminator also failed: adding A twice left qty 1 / $10 instead of qty 2 / $20. Single-A block cart and checkout were clean. Browser errors empty; no order/user/subscription/setting mutation. Evidence: `/home/server-manager/slt-evidence/SLT-CHK-06-facts.txt`, screenshots `SLT-CHK-06-00` through `-08`, exact text/cmp artifacts. Issue: `issues/critical-plugin-SLT-CHK-06-one-click-replaces-cart-and-bypasses-composition-guard.md`.

[[2026-08-12]] Wed
Oracle corrected after issue remediation. The D05 failure was a false positive: task 077
applied the default-mode composition oracle while the frozen site was in
`subscription_items` one-click mode. Formal Stage 05 task 08, shipped settings copy, code,
and feature history all require one-click replacement and current-request quantity. Live
retest proved both contracts: one-click A→B left B alone; quantity-1 repeat stayed 1; one
quantity-3 request stayed 3. In an exact-hash-restored default-mode bracket, B was rejected
with the exact authored text while A remained, and a repeated A merged to quantity 2. Zero
mail/browser errors; cart and persistent cart empty; settings restored exactly. Resolved
report: `issues/done-critical-plugin-SLT-CHK-06-one-click-replaces-cart-and-bypasses-composition-guard.md`.
