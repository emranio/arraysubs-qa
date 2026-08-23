---
id: 51
title: SLT2NOSUB rejected on a subscription-only classic cart - exact message, mixed-cart partial discount, undiscounted renewal
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-03
due: "2026-08-26"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
    - 39
    - 61
class: standard
---

> **SLT-CPN-04** · group `checkout` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Capture the exact rejection when `SLT2NOSUB` (percent 30, **apply-to-subscriptions UNCHECKED**) hits a subscription-only classic cart; then prove it IS accepted on a mixed cart but discounts only the non-subscription line, and that the subscription from that order captures no coupon - so its first renewal is full price.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (CREATES `slt2-cpnrej`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLT2NOSUB`), SLT-PROD-01 (`SLT2 Daily Core` $10.00 day/1), SLT-PROD-09 (`SLT2 Grouped Extra` $3.00 plain) done.
- Code contract (revalidate against the current code and runtime before using): `validateSubscriptionCouponEligibility()` returns false only when the cart holds >=1 subscription item and NO regular item; on a mixed cart it stays valid and `filterSubscriptionCouponItems()` strips subscription lines. `allow_mixed_cart=true` permits the mixed order.
- CREATES `slt2-cpnrej` / `slt2-cpnrej@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03; fresh account per C08.
- Act **18:00-19:00 site (UTC+6) on 2026-08-26**: outside SLT-SYN-04's 09:00-11:00 bracket, and it puts the invoice leg at 12:00-19:00 next day, also clear of it.
- Sessions `admin-SLT-CPN-04` / `customer-SLT-CPN-04` exclusive (C09).

## Test data
| Item | Value |
|---|---|
| Products | SLT2 Daily Core $10.00/day + SLT2 Grouped Extra $3.00 |
| Account | slt2-cpnrej (created here) |
| Coupon | SLT2NOSUB - percent 30, apply-to-subscriptions NO |
| Card | 4242 4242 4242 4242 |
| Amounts | probe A $10.00; mixed $13.00 - $0.90 = $12.10; renewal $10.00 |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)` and `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>`. In `agent-browser --session admin-SLT-CPN-04`, create `slt2-cpnrej` with **Send User Notification** unticked and fill billing; record its numeric WordPress user ID. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail. `wp post meta get <SLT2NOSUB ID> _arraysubs_apply_to_subscriptions --allow-root` must print nothing; capture the unticked box as `SLT-CPN-04-01-coupon-subscriptions-off.png`. Only after setup mail is classified, set probe baseline `M0=$(mailpit-agent latest-id)`.
2. In `agent-browser --session customer-SLT-CPN-04`, log in as `slt2-cpnrej`; open `/slt2-classic-cart`, confirm both the browser cart and persistent cart are EMPTY, and capture `SLT-CPN-04-02-cart-empty-before.png`.
3. **Probe A.** Add SLT2 Daily Core only. If the frozen one-click setting redirects to block checkout, record that expected redirect and explicitly reopen `/slt2-classic-cart`; require the $10.00 subscription line to be present before continuing. Type `SLT2NOSUB` in **Coupon code** -> **Apply coupon** -> re-snapshot.
4. Copy the notice **verbatim** (Expected #1) and capture `SLT-CPN-04-03-rejection.png`; confirm total still **$10.00**, no discount row, and no coupon chip. Record the apply request's exact URL/status plus browser console errors.
5. **Probe B.** Add SLT2 Grouped Extra ($3.00), explicitly reopen `/slt2-classic-cart` if navigation moved, and require subtotal $13.00. Apply `SLT2NOSUB` again - ACCEPTED. Capture `SLT-CPN-04-04-mixed-cart.png`: `Coupon: sltnosub` **-$0.90**, Total **$12.10**; SLT2 Daily Core line still $10.00. Reconcile every message newer than `M0`, require zero probe-attributable mail, and classify unrelated/background messages. Only then set purchase-only baseline `M_BUY=$(mailpit-agent latest-id)`.
6. **Probe C.** Open `/slt2-classic-checkout`, confirm Total **$12.10**, and capture `SLT-CPN-04-05-checkout-summary.png` before card entry. Select Stripe and fill the hosted card fields without capturing them, then **Place order**. Re-snapshot the safe receipt, record numeric `ORDER_ID`, and capture `SLT-CPN-04-06-order-received.png`.
7. Resolve `SUB_ID` only from the recorded receipt order: `LINK_JSON=$(wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root)` followed by a strict one-element numeric `jq -e` guard. Never use the empty HPOS WooCommerce order-meta accessor or recency. Require one reverse `_parent_order_id=$ORDER_ID` relationship, the exact customer/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`; in `admin-SLT-CPN-04`, open the exact subscription and capture `SLT-CPN-04-07-subscription-admin.png`. `wp post meta list "$SUB_ID" --allow-root | rg _coupon` must return nothing, and no note may begin `Coupon "`.
8. Run `mailpit-agent wait-new "$M_BUY" 180 "is active"`; inspect the complete owner-filtered delta after `M_BUY` and require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Compute the offset with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_ID"`; query the exact pending invoice/charge action rows, and publish their IDs/times plus the first `charge−5m` deadline, `USER_PRE`, setup-mail ID, `M0`, `M_BUY`, all four checkout-mail IDs, user/order/subscription IDs, and count delta to `slt2-catalog-registry` and the D03 watch report.
9. Empty the browser cart, prove the exact user's persistent-cart meta empty, capture `SLT-CPN-04-08-cart-empty-after.png`, close only `admin-SLT-CPN-04` and `customer-SLT-CPN-04`, and keep this card `in-progress`. No earlier than five minutes before renewal #1's exact recorded charge gate, store `REN1_PRE=$(mailpit-agent latest-id)` in the registry. **2026-08-28 after 09:00 site** (renewal #1 charged the evening of 08-27): reopen `admin-SLT-CPN-04`; resolve the renewal order through the exact subscription/scheduled-cycle relationship, never recency, and require its reverse subscription link. Capture its item table as `SLT-CPN-04-09-renewal-1-order.png`, run `wp eval '$o=wc_get_order((int)$argv[1]);echo count($o->get_items())."|".count($o->get_fees())."|".count($o->get_coupon_codes())."|".$o->get_total();' <REN1> --allow-root`, require `mailpit-agent wait-new "$REN1_PRE" 900 "Payment received for subscription #$SUB_ID"`, save/show the exact match, and reconcile every message newer than `REN1_PRE`.
10. Re-read the unchanged empty coupon meta and advanced next-payment date, append the renewal order/action/baseline/mail IDs to the registry and D05 watch report, and close only `admin-SLT-CPN-04`. Independently review the complete D3/D5 evidence, move the card through `review` to `done`, and ensure Review returns to zero. If the live rejection text or any other product assertion fails, create a separate issue Markdown file under `qa/issues/` with the required task/plan, fixture IDs, user/context, URL, reproduction, expected/actual, proof, and counterexample fields; do not create a bug/remediation kanban card.

## Expected results
1. Probe A: refused with the verbatim notice **`Coupon is not valid.`** - `class-wc-discounts.php:1149` throws `E_WC_COUPON_INVALID_FILTERED` (100) with a non-numeric message, so `get_coupon_error()` is bypassed and `Coupon "sltnosub" cannot be applied because it is not valid.` is NOT shown. Any other text: record it in a QA issue card under `qa/issues/`. Cart stays **$10.00**; the coupon never enters the applied list.
2. Probe A sends zero mail; no PHP notice or 4xx/5xx in the network log.
3. Probe B: accepted on the mixed cart; discount exactly **$0.90** (30% x $3.00); total **$12.10**; the $10.00 subscription line untouched.
4. Probe C order total **$12.10**, status processing/completed, coupon `sltnosub` on the order, exactly ONE subscription created (Grouped Extra creates none).
5. The subscription has NO `_applied_coupon_id` / `_coupon_*` metas (`captureOrderCoupons()` skips at its `'yes' !== $apply_to_subs` gate) and no `Coupon "..."` note.
6. Renewal #1 (charged the evening of 2026-08-27, seen on the 08-28 watch run = watch day D5): exactly **one** line item, **zero** fees, **zero** coupons, total **$10.00**. Grouped Extra does not recur.
7. `_next_payment_date` advances exactly 1 day; status stays `arraysubs-active`; `SLT2NOSUB`'s meta is still empty at task end.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | **NONE EXPECTED** | probe A and probe B | - | - | Complete delta after `M0` through step 5; zero probe-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | new_subscription | order paid | slt2-cpnrej@ | `is active` | `mailpit-agent wait-new "$M_BUY" 180 "is active"` |
| 3 | admin_new_subscription | order paid | admin_email | `New subscription #` | Complete owner-filtered checkout delta after `M_BUY`; save/show the exact matching id |
| 3a | WooCommerce paid-order | order paid | slt2-cpnrej@ | order number / `is on its way` | Complete owner-filtered checkout delta after `M_BUY`; save/show the exact matching id |
| 3b | WooCommerce New order | order paid | admin_email | `New order #<ORDER_ID>` | Complete owner-filtered checkout delta after `M_BUY`; save/show the exact matching id |
| 4 | payment_successful | renewal #1 paid | slt2-cpnrej@ | `Payment received for subscription #<SUB_ID>` | `mailpit-agent wait-new "$REN1_PRE" 900 "Payment received for subscription #$SUB_ID"`; exact match plus full delta |
| 5 | WP New User Registration | setup before `M0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- Screenshots `SLT-CPN-04-01` through `-09` named in the steps: unticked coupon box, empty cart, rejection, mixed cart, safe checkout summary, safe receipt, exact subscription admin, final empty cart, and renewal #1.
- The rejection string character-for-character, HTTP status and console errors from the apply POST, coupon/user/order/subscription IDs, count delta and bidirectional linkage, `k`, exact action IDs/deadlines, meta and `wp eval` output, `USER_PRE`, setup-mail ID, probe `M0`, purchase `M_BUY`, all four checkout-mail IDs, renewal baseline/mail ID, and final cart/persistent-cart proof.

## Pass criteria
- [ ] Subscription-only cart rejects SLT2NOSUB; message verbatim; cart stays $10.00; zero mail
- [ ] Mixed cart accepts it: $0.90 discount, $12.10 total, subscription line undiscounted
- [ ] Mixed order creates one subscription with zero coupon metas and no coupon note
- [ ] Renewal #1 totals $10.00 with one line item, zero fees, zero coupons; SLT2NOSUB's meta unchanged
- [ ] Setup mail isolated before `M0`; probe and purchase mail windows are distinct; all four checkout mails captured; no customer account/password mail
- [ ] Exact receipt links bidirectionally to the sole new subscription and the numeric subscription count advances by one
- [ ] Exact natural action deadlines handed off before D3 sessions close; final cart and persistent-cart meta empty; D5 card closes through review

## Isolation / teardown
- Watch D5..D9: this subscription renews at $10.00 daily with no fee line; a `Recurring Discount:` fee is a defect.
- Leaves `slt2-cpnrej`, a mixed order and one active daily subscription - add the subscription to SLT-SETUP-99A's cancel list and the account/order to SLT-SETUP-99B's deletion list. Close only `admin-SLT-CPN-04` and `customer-SLT-CPN-04` by exact name; never use a wildcard or `close --all`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
