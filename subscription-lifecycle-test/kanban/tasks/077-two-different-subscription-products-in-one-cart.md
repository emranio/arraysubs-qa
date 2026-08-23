---
id: 77
title: Two different subscription products in one cart must be rejected — capture the exact string on every add-to-cart surface
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-05
due: "2026-08-28"
estimate: 1h
depends_on:
    - 10
    - 11
    - 5
    - 6
class: standard
---

> **SLT-CHK-06** · group `checkout` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `multiple_subscriptions.allow_multiple_in_cart = false` stops two DIFFERENT subscription products coexisting in one cart, capture the rejection string byte-for-byte, and show the rule keys on DISTINCT product ids — the same product added twice merges quantity instead. Distinct from SLT-PROD-09, which probes this only from the grouped page; here the two dedicated product pages, the archive AJAX path and both cart implementations are driven. No order is placed.

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
| Product A | SLT2 Daily Core, `/product/slt2-daily-core/`, $10.00, day/1 |
| Product B | SLT2 Fixed Three Cycles, `/product/slt2-fixed-three-cycles/`, $7.00, day/2 |
| Session | `--session guest-SLT-CHK-06` (unique; no cart collision) |
| Expected error | `Multiple subscription plans are disabled for one checkout. Keep only one subscription plan in the cart, then place a separate order for any other plan.` |

## Steps
1. Resolve strict numeric, distinct `A_ID` and `B_ID` by exact slugs; verify their titles, prices, schedules, and subscription flags, then record `MB06=$(mailpit-agent latest-id)`.
2. `agent-browser --session guest-SLT-CHK-06 open "https://mirror-help.arrayhash.com/slt2-classic-cart"` → `snapshot -i`; assert empty and capture `SLT-CHK-06-00-cart-empty-before.png`.
3. Open `/product/slt2-daily-core/`, click add-to-cart, and account for frozen one-click mode: if redirected to checkout, do not proceed; explicitly reopen `/slt2-classic-cart`. Require exactly `$A_ID`, qty 1, subtotal $10.00 and capture `SLT-CHK-06-01-classic-cart-one-sub.png`.
4. Open `/product/slt2-fixed-three-cycles/`, click add-to-cart, and re-snapshot the resulting page/notice before navigating. Capture `SLT-CHK-06-02-rejection-notice.png`; then reopen `/slt2-classic-cart` and require `$B_ID` absent while `$A_ID` remains qty 1.
5. Copy the notice verbatim to `/home/server-manager/slt-evidence/SLT-CHK-06-rejection.txt` and compare its bytes against a task-owned expected-text file using `cmp`; save the comparison result.
6. Capture the verified classic cart as `SLT-CHK-06-03-classic-cart-still-one.png`.
7. Open `/cart` (block, page 7), require one `$A_ID` line and no error banner, and capture `SLT-CHK-06-04-block-cart-one.png`.
8. Open `/checkout` (block, page 8), require the unpopulated single-line summary and no `arraysubs_cart_error`, and capture `SLT-CHK-06-05-block-checkout-clean.png`. Do not enter payment data or place an order.
9. Archive AJAX surface: open `/?post_type=product&s=SLT+Fixed+Three+Cycles`, clear the task-session network buffer, click the exact `$B_ID` archive **Add to cart**, and re-snapshot. Capture `SLT-CHK-06-06-archive-ajax-result.png` plus the exact request/status/body; then reopen the classic cart and require `$B_ID` still absent.
10. Discriminator: return to `/product/slt2-daily-core/` and add `$A_ID` a second time. If one-click redirects, explicitly reopen `/slt2-classic-cart`; require the sole line qty 2/subtotal $20.00 and capture `SLT-CHK-06-07-same-product-qty2.png`.
11. Empty the cart, capture `SLT-CHK-06-08-cart-empty-after.png`, and close only `guest-SLT-CHK-06`.
12. Inspect every Mailpit message newer than `MB06`; require zero task-attributable mail and classify background mail. If the archive rejection is silent, create `qa/issues/` kanban card named `SLT-CHK-06-archive-rejection-has-no-visible-feedback`; for that UX finding or any assertion failure include this progress task/stage and plan path, product IDs, user/order/subscription IDs as `N/A`, guest session and exact URLs, reproduction, expected/actual, screenshot/network/console/Mailpit proof, and the product-page rejection as counterexample. create or update the mandatory `qa/issues/` kanban card. Independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Step 4 refuses the add; cart item count stays 1, no SLT2 Fixed Three Cycles line.
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
- [ ] Any silent-archive UX finding exists only as a QA issue card; exact session closed and evidence reviewed to done

## Isolation / teardown
- Cart emptied and session closed at step 11. No product, user, coupon or setting is created or changed.
- Handed on: the confirmed rejection string, reused as the negative control in SLT-CHK-07 and SLT-CHK-09.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
