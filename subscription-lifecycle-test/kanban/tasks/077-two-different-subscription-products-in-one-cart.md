---
id: 77
title: Two different subscription products in one cart must be rejected — capture the exact string on every add-to-cart surface
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
| Product A | SLT Daily Core, `/product/slt-daily-core/`, $10.00, day/1 |
| Product B | SLT Fixed Three Cycles, `/product/slt-fixed-three-cycles/`, $7.00, day/2 |
| Session | `--session guest-SLT-CHK-06` (unique; no cart collision) |
| Expected error | `Multiple subscription plans are disabled for one checkout. Keep only one subscription plan in the cart, then place a separate order for any other plan.` |

## Steps
1. Resolve strict numeric, distinct `A_ID` and `B_ID` by exact slugs; verify their titles, prices, schedules, and subscription flags, then record `MB06=$(mailpit-agent latest-id)`.
2. `agent-browser --session guest-SLT-CHK-06 open "https://mirror-help.arrayhash.com/slt-classic-cart"` → `snapshot -i`; assert empty and capture `SLT-CHK-06-00-cart-empty-before.png`.
3. Open `/product/slt-daily-core/`, click add-to-cart, and account for frozen one-click mode: if redirected to checkout, do not proceed; explicitly reopen `/slt-classic-cart`. Require exactly `$A_ID`, qty 1, subtotal $10.00 and capture `SLT-CHK-06-01-classic-cart-one-sub.png`.
4. Open `/product/slt-fixed-three-cycles/`, click add-to-cart, and re-snapshot the resulting page/notice before navigating. Capture `SLT-CHK-06-02-rejection-notice.png`; then reopen `/slt-classic-cart` and require `$B_ID` absent while `$A_ID` remains qty 1.
5. Copy the notice verbatim to `/home/server-manager/slt-evidence/SLT-CHK-06-rejection.txt` and compare its bytes against a task-owned expected-text file using `cmp`; save the comparison result.
6. Capture the verified classic cart as `SLT-CHK-06-03-classic-cart-still-one.png`.
7. Open `/cart` (block, page 7), require one `$A_ID` line and no error banner, and capture `SLT-CHK-06-04-block-cart-one.png`.
8. Open `/checkout` (block, page 8), require the unpopulated single-line summary and no `arraysubs_cart_error`, and capture `SLT-CHK-06-05-block-checkout-clean.png`. Do not enter payment data or place an order.
9. Archive AJAX surface: open `/?post_type=product&s=SLT+Fixed+Three+Cycles`, clear the task-session network buffer, click the exact `$B_ID` archive **Add to cart**, and re-snapshot. Capture `SLT-CHK-06-06-archive-ajax-result.png` plus the exact request/status/body; then reopen the classic cart and require `$B_ID` still absent.
10. Discriminator: return to `/product/slt-daily-core/` and add `$A_ID` a second time. If one-click redirects, explicitly reopen `/slt-classic-cart`; require the sole line qty 2/subtotal $20.00 and capture `SLT-CHK-06-07-same-product-qty2.png`.
11. Empty the cart, capture `SLT-CHK-06-08-cart-empty-after.png`, and close only `guest-SLT-CHK-06`.
12. Inspect every Mailpit message newer than `MB06`; require zero task-attributable mail and classify background mail. If the archive rejection is silent, create `issues/SLT-CHK-06-archive-rejection-has-no-visible-feedback.md`; for that UX finding or any assertion failure include this progress task/stage and plan path, product IDs, user/order/subscription IDs as `N/A`, guest session and exact URLs, reproduction, expected/actual, screenshot/network/console/Mailpit proof, and the product-page rejection as counterexample. Never add a kanban bug card. Independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

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
| 1 | NONE EXPECTED | no order is placed | — | — | Complete delta after `MB06`; file only a task-attributable leak, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Safe named `SLT-CHK-06-00` through `-08` cart/rejection/archive captures; numeric product IDs; exact-byte notice files/comparison; archive console/network response; Mailpit/session/review proof.

## Pass criteria
- [ ] Second distinct subscription never enters the cart
- [ ] Notice string matches exactly
- [ ] Block cart and block checkout raise no cart error
- [ ] Archive AJAX path also refuses; its messaging recorded
- [ ] Same product twice merges to qty 2
- [ ] Zero mail sent
- [ ] Any silent-archive UX finding exists only as a standalone issue file; exact session closed and evidence reviewed to done

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
