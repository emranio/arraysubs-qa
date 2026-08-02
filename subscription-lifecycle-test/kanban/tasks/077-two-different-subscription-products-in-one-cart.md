---
id: 77
title: Two different subscription products in one cart must be rejected — capture the exact string on every add-to-cart surface
status: todo
priority: high
created: 2026-08-02T03:43:09.675926975+02:00
updated: 2026-08-02T03:43:20.546394511+02:00
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
Prove `multiple_subscriptions.allow_multiple_in_cart = false` stops two DIFFERENT subscription products coexisting in one cart, capture the rejection string byte-for-byte, and show the rule keys on DISTINCT product ids — the same product added twice merges quantity instead. Distinct from SLT-PROD-09, which probes this only from the grouped page; here the two standalone product pages, the archive AJAX path and both cart implementations are driven. No order is placed.

## Scope
- Gateway: N/A
- Checkout: both
- Account: N/A (anonymous session — the composition guard applies to guests too)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 (harness pages), SLT-SETUP-02, SLT-PROD-01, SLT-PROD-06 complete.
- Frozen baseline, do NOT touch: `allow_multiple_in_cart=false`, `allow_mixed_cart=true`, `one_per_customer=false`, `one_per_product=false`, `allow_different_cycles=true`.
- Code contract: the add-time guard is `CartValidationTrait::validateCartComposition()` on `woocommerce_add_to_cart_validation`; it refuses when `count($cart_would_have_distinct_subscriptions) > 1`. The same message also exists in `CartValidation::getCartValidationErrors()` (classic `woocommerce_check_cart_items` + Store API `arraysubs_cart_error`).

## Test data
| Item | Value |
|---|---|
| Product A | SLT Daily Core, `/slt-daily-core`, $10.00, day/1 |
| Product B | SLT Fixed Three Cycles, `/slt-fixed-three-cycles`, $7.00, day/2 |
| Session | `--session guest-SLT-CHK-06` (unique; no cart collision) |
| Expected error | `Multiple subscription plans are disabled for one checkout. Keep only one subscription plan in the cart, then place a separate order for any other plan.` |

## Steps
1. `mailpit-agent latest-id` → record `MB06`.
2. `agent-browser --session guest-SLT-CHK-06 open "https://mirror-help.arrayhash.com/slt-classic-cart"` → `snapshot -i`. Assert cart empty.
3. Open `https://mirror-help.arrayhash.com/slt-daily-core` → `snapshot -i` → click add-to-cart → re-snapshot (1 item).
4. Open `https://mirror-help.arrayhash.com/slt-fixed-three-cycles` → `snapshot -i` → click add-to-cart → re-snapshot and screenshot the notice.
5. Copy the notice verbatim to a `.txt` and diff character-for-character against Expected error.
6. Open `/slt-classic-cart` → `snapshot -i`: only SLT Daily Core, qty 1, subtotal $10.00.
7. Open `https://mirror-help.arrayhash.com/cart` (block, page 7) → `snapshot -i`: one line, no error banner.
8. Open `https://mirror-help.arrayhash.com/checkout` (block, page 8) → `snapshot -i`: no `arraysubs_cart_error`. Do not place the order.
9. Archive AJAX surface: open `https://mirror-help.arrayhash.com/?post_type=product&s=SLT+Fixed+Three+Cycles` → `snapshot -i` → click the archive **Add to cart** → re-snapshot; record whether the item was silently dropped or a message rendered. Capture the network response.
10. Discriminator: return to `/slt-daily-core` and add it a SECOND time; re-snapshot the classic cart.
11. Empty the cart; `agent-browser --session guest-SLT-CHK-06 close`.
12. `mailpit-agent latest-id` must equal `MB06`.

## Expected results
1. Step 4 refuses the add; cart item count stays 1, no SLT Fixed Three Cycles line.
2. The notice equals the Expected error exactly, including the final period.
3. Cart subtotal at step 6 is `$10.00`; no tax line anywhere.
4. Block cart and block checkout show the single line and zero `arraysubs_cart_error`.
5. Step 9: the second distinct subscription is not added by the AJAX path either. Whether any message is surfaced is EXPLORATORY — record actual behaviour; silence is a UX finding, not a pass/fail assertion.
6. Step 10 SUCCEEDS: the same product merges to qty 2 (rule counts distinct ids); subtotal `$20.00`.
7. The cart-level copy of the message is unreachable through the UI because the add-time guard fires first. Record as documented defence-in-depth, not a defect.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | no order is placed | — | — | `mailpit-agent latest-id` after step 12 equals `MB06`; if it moved, `mailpit-agent show latest` and file the leak |

## Evidence to capture
- `SLT-CHK-06-01-cart-one-sub.png`, `-02-rejection-notice.png`, `-03-block-checkout-clean.png`, `-04-archive-ajax-result.png`, `-05-same-product-qty2.png`.
- Verbatim notice text file; console + network log for step 9.

## Pass criteria
- [ ] Second distinct subscription never enters the cart
- [ ] Notice string matches exactly
- [ ] Block cart and block checkout raise no cart error
- [ ] Archive AJAX path also refuses; its messaging recorded
- [ ] Same product twice merges to qty 2
- [ ] Zero mail sent

## Isolation / teardown
- Cart emptied and session closed at step 11. No product, user, coupon or setting is created or changed.
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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
