---
id: 51
title: SLTNOSUB rejected on a subscription-only classic cart - exact message, mixed-cart partial discount, undiscounted renewal
status: done
priority: high
created: 2026-08-02T03:43:07.422840178+02:00
updated: 2026-08-06T20:10:33.715932746+02:00
started: 2026-08-06T20:10:33.715931614+02:00
completed: 2026-08-06T20:10:33.715931614+02:00
tags:
    - checkout
    - day-03
due: "2026-08-05"
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

> **SLT-CPN-04** · group `checkout` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Capture the exact rejection when `SLTNOSUB` (percent 30, **apply-to-subscriptions UNCHECKED**) hits a subscription-only classic cart; then prove it IS accepted on a mixed cart but discounts only the non-subscription line, and that the subscription from that order captures no coupon - so its first renewal is full price.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (CREATES `slt-cpnrej`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLTNOSUB`), SLT-PROD-01 (`SLT Daily Core` $10.00 day/1), SLT-PROD-09 (`SLT Grouped Extra` $3.00 plain) done.
- Code contract (do not re-derive): `validateSubscriptionCouponEligibility()` returns false only when the cart holds >=1 subscription item and NO regular item; on a mixed cart it stays valid and `filterSubscriptionCouponItems()` strips subscription lines. `allow_mixed_cart=true` permits the mixed order.
- CREATES `slt-cpnrej` / `slt-cpnrej@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03; fresh account per C08.
- Act **18:00-19:00 site (UTC+6) on 2026-08-05**: outside SLT-SYN-04's 09:00-11:00 bracket, and it puts the invoice leg at 12:00-19:00 next day, also clear of it.
- Sessions `admin-SLT-CPN-04` / `customer-SLT-CPN-04` exclusive (C09).

## Test data
| Item | Value |
|---|---|
| Products | SLT Daily Core $10.00/day + SLT Grouped Extra $3.00 |
| Account | slt-cpnrej (created here) |
| Coupon | SLTNOSUB - percent 30, apply-to-subscriptions NO |
| Card | 4242 4242 4242 4242 |
| Amounts | probe A $10.00; mixed $13.00 - $0.90 = $12.10; renewal $10.00 |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)` and `SUBCOUNT_BEFORE=<exact current SLT subscription count>`. In `agent-browser --session admin-SLT-CPN-04`, create `slt-cpnrej` with **Send User Notification** unticked and fill billing; record its numeric WordPress user ID. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail. `wp post meta get <SLTNOSUB ID> _arraysubs_apply_to_subscriptions --allow-root` must print nothing; capture the unticked box as `SLT-CPN-04-01-coupon-subscriptions-off.png`. Only after setup mail is classified, set probe baseline `M0=$(mailpit-agent latest-id)`.
2. In `agent-browser --session customer-SLT-CPN-04`, log in as `slt-cpnrej`; open `/slt-classic-cart`, confirm both the browser cart and persistent cart are EMPTY, and capture `SLT-CPN-04-02-cart-empty-before.png`.
3. **Probe A.** Add SLT Daily Core only. If the frozen one-click setting redirects to block checkout, record that expected redirect and explicitly reopen `/slt-classic-cart`; require the $10.00 subscription line to be present before continuing. Type `SLTNOSUB` in **Coupon code** -> **Apply coupon** -> re-snapshot.
4. Copy the notice **verbatim** (Expected #1) and capture `SLT-CPN-04-03-rejection.png`; confirm total still **$10.00**, no discount row, and no coupon chip. Record the apply request's exact URL/status plus browser console errors.
5. **Probe B.** Add SLT Grouped Extra ($3.00), explicitly reopen `/slt-classic-cart` if navigation moved, and require subtotal $13.00. Apply `SLTNOSUB` again - ACCEPTED. Capture `SLT-CPN-04-04-mixed-cart.png`: `Coupon: sltnosub` **-$0.90**, Total **$12.10**; SLT Daily Core line still $10.00. Reconcile every message newer than `M0`, require zero probe-attributable mail, and classify unrelated/background messages. Only then set purchase-only baseline `M_BUY=$(mailpit-agent latest-id)`.
6. **Probe C.** Open `/slt-classic-checkout`, confirm Total **$12.10**, and capture `SLT-CPN-04-05-checkout-summary.png` before card entry. Select Stripe and fill the hosted card fields without capturing them, then **Place order**. Re-snapshot the safe receipt, record numeric `ORDER_ID`, and capture `SLT-CPN-04-06-order-received.png`.
7. Resolve `SUB_ID` only from the recorded receipt order: `LINK_JSON=$(wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root)` followed by a strict one-element numeric `jq -e` guard. Never use the empty HPOS WooCommerce order-meta accessor or recency. Require one reverse `_parent_order_id=$ORDER_ID` relationship, the exact customer/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`; in `admin-SLT-CPN-04`, open the exact subscription and capture `SLT-CPN-04-07-subscription-admin.png`. `wp post meta list "$SUB_ID" --allow-root | rg _coupon` must return nothing, and no note may begin `Coupon "`.
8. Run `mailpit-agent wait-new "$M_BUY" 180 "is active"`; inspect the complete owner-filtered delta after `M_BUY` and require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Compute the offset with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_ID"`; query the exact pending invoice/charge action rows, and publish their IDs/times plus the first `charge−5m` deadline, `USER_PRE`, setup-mail ID, `M0`, `M_BUY`, all four checkout-mail IDs, user/order/subscription IDs, and count delta to `slt-catalog-registry` and the D03 watch report.
9. Empty the browser cart, prove the exact user's persistent-cart meta empty, capture `SLT-CPN-04-08-cart-empty-after.png`, close only `admin-SLT-CPN-04` and `customer-SLT-CPN-04`, and keep this card `in-progress`. No earlier than five minutes before renewal #1's exact recorded charge gate, store `REN1_PRE=$(mailpit-agent latest-id)` in the registry. **2026-08-07 after 09:00 site** (renewal #1 charged the evening of 08-06): reopen `admin-SLT-CPN-04`; resolve the renewal order through the exact subscription/scheduled-cycle relationship, never recency, and require its reverse subscription link. Capture its item table as `SLT-CPN-04-09-renewal-1-order.png`, run `wp eval '$o=wc_get_order((int)$argv[1]);echo count($o->get_items())."|".count($o->get_fees())."|".count($o->get_coupon_codes())."|".$o->get_total();' <REN1> --allow-root`, require `mailpit-agent wait-new "$REN1_PRE" 900 "Payment received for subscription #$SUB_ID"`, save/show the exact match, and reconcile every message newer than `REN1_PRE`.
10. Re-read the unchanged empty coupon meta and advanced next-payment date, append the renewal order/action/baseline/mail IDs to the registry and D05 watch report, and close only `admin-SLT-CPN-04`. Independently review the complete D3/D5 evidence, move the card through `review` to `done`, and ensure Review returns to zero. If the live rejection text or any other product assertion fails, create a separate issue Markdown file under `issues/` with the required task/plan, fixture IDs, user/context, URL, reproduction, expected/actual, proof, and counterexample fields; do not create a bug/remediation kanban card.

## Expected results
1. Probe A: refused with the verbatim notice **`Coupon is not valid.`** - `class-wc-discounts.php:1149` throws `E_WC_COUPON_INVALID_FILTERED` (100) with a non-numeric message, so `get_coupon_error()` is bypassed and `Coupon "sltnosub" cannot be applied because it is not valid.` is NOT shown. Any other text: record it in a standalone issue file under `issues/`. Cart stays **$10.00**; the coupon never enters the applied list.
2. Probe A sends zero mail; no PHP notice or 4xx/5xx in the network log.
3. Probe B: accepted on the mixed cart; discount exactly **$0.90** (30% x $3.00); total **$12.10**; the $10.00 subscription line untouched.
4. Probe C order total **$12.10**, status processing/completed, coupon `sltnosub` on the order, exactly ONE subscription created (Grouped Extra creates none).
5. The subscription has NO `_applied_coupon_id` / `_coupon_*` metas (`captureOrderCoupons()` skips at its `'yes' !== $apply_to_subs` gate) and no `Coupon "..."` note.
6. Renewal #1 (charged the evening of 2026-08-06, seen on the 08-07 watch run = watch day D5): exactly **one** line item, **zero** fees, **zero** coupons, total **$10.00**. Grouped Extra does not recur.
7. `_next_payment_date` advances exactly 1 day; status stays `arraysubs-active`; `SLTNOSUB`'s meta is still empty at task end.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | **NONE EXPECTED** | probe A and probe B | - | - | Complete delta after `M0` through step 5; zero probe-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | new_subscription | order paid | slt-cpnrej@ | `is active` | `mailpit-agent wait-new "$M_BUY" 180 "is active"` |
| 3 | admin_new_subscription | order paid | admin_email | `New subscription #` | Complete owner-filtered checkout delta after `M_BUY`; save/show the exact matching id |
| 3a | WooCommerce paid-order | order paid | slt-cpnrej@ | order number / `is on its way` | Complete owner-filtered checkout delta after `M_BUY`; save/show the exact matching id |
| 3b | WooCommerce New order | order paid | admin_email | `New order #<ORDER_ID>` | Complete owner-filtered checkout delta after `M_BUY`; save/show the exact matching id |
| 4 | payment_successful | renewal #1 paid | slt-cpnrej@ | `Payment received for subscription #<SUB_ID>` | `mailpit-agent wait-new "$REN1_PRE" 900 "Payment received for subscription #$SUB_ID"`; exact match plus full delta |
| 5 | WP New User Registration | setup before `M0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- Screenshots `SLT-CPN-04-01` through `-09` named in the steps: unticked coupon box, empty cart, rejection, mixed cart, safe checkout summary, safe receipt, exact subscription admin, final empty cart, and renewal #1.
- The rejection string character-for-character, HTTP status and console errors from the apply POST, coupon/user/order/subscription IDs, count delta and bidirectional linkage, `k`, exact action IDs/deadlines, meta and `wp eval` output, `USER_PRE`, setup-mail ID, probe `M0`, purchase `M_BUY`, all four checkout-mail IDs, renewal baseline/mail ID, and final cart/persistent-cart proof.

## Pass criteria
- [ ] Subscription-only cart rejects SLTNOSUB; message verbatim; cart stays $10.00; zero mail
- [ ] Mixed cart accepts it: $0.90 discount, $12.10 total, subscription line undiscounted
- [ ] Mixed order creates one subscription with zero coupon metas and no coupon note
- [ ] Renewal #1 totals $10.00 with one line item, zero fees, zero coupons; SLTNOSUB's meta unchanged
- [ ] Setup mail isolated before `M0`; probe and purchase mail windows are distinct; all four checkout mails captured; no customer account/password mail
- [ ] Exact receipt links bidirectionally to the sole new subscription and the numeric subscription count advances by one
- [ ] Exact natural action deadlines handed off before D3 sessions close; final cart and persistent-cart meta empty; D5 card closes through review

## Isolation / teardown
- Watch D5..D9: this subscription renews at $10.00 daily with no fee line; a `Recurring Discount:` fee is a defect.
- Leaves `slt-cpnrej`, a mixed order and one active daily subscription - add the subscription to SLT-SETUP-99A's cancel list and the account/order to SLT-SETUP-99B's deletion list. Close only `admin-SLT-CPN-04` and `customer-SLT-CPN-04` by exact name; never use a wildcard or `close --all`.


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

## D3 checkpoint — 2026-08-05

- D3 checkout passed and was published to `/home/server-manager/slt-evidence/SLT-CPN-04-D03-armed-facts.txt`.
- Coupon `12076` remained subscription-disabled (`_arraysubs_apply_to_subscriptions` empty). Probe A on a subscription-only classic cart returned the exact notice `Coupon is not valid.` with `POST ?wc-ajax=apply_coupon` HTTP `200` and no attributable browser errors.
- Mixed cart accepted `sltnosub` only on plain product `12583`: subtotal USD `13.00`, discount USD `0.90`, total USD `12.10`; probe mail delta after `M0=2aWU9y2ub7zU3y1J3F0tqY` contained zero probe-attributable mail.
- Real Stripe checkout created order `12718` and sole active subscription `12719` for user `364` / `slt-cpnrej`; subscription count moved `371 -> 372`.
- Order `12718` carries coupon code `sltnosub`; subscription `12719` carries no `_coupon*` meta and no admin note beginning `Coupon`.
- Exact checkout mails after `M_BUY=2aWU9y2ub7zU3y1J3F0tqY`: `3plCGAbOpBH9K1QYhXiX7g`, `4MupoYE4ETpzYdq35IyRnz`, `56AqHweYid7T3MZ977VzlX`, `5dFUr2i3qmDN4QRnMXefcb`.
- Spread offset is `39s`. Pending natural actions: invoice `14902` at `2026-08-06 06:22:32Z`, charge `14903` at `2026-08-06 12:22:32Z`. Capture `REN1_PRE` only during `[2026-08-06 12:17:32Z, 12:22:31Z]` / site `[18:17:32, 18:22:31]`.
- Browser cart and persistent cart meta are empty. D5 remains open for the exact renewal read only; no action was forced.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: D3 publication is complete, exact sessions are closed, and only the future natural renewal read remains. Parking this card back in todo until the recorded D4 baseline and D5 verification windows.

[[2026-08-05]] Wed 14:38
D03 handoff self-review correction: card was returned to todo after setup leg, so restored to in-progress for the authored future follow-up. Core coupon/order assertions passed, but user 364 was created at 2026-08-05 11:50:48Z / 17:50:48 site, nine minutes before the 18:00 gate, and required billing_phone remained empty; record as PASS WITH EXECUTION DEVIATIONS, not a product issue. Retook SLT-CPN-04-07-subscription-admin.png as a safe viewport so retained evidence contains no raw Stripe API identifiers. Exact next gate: D4 2026-08-06 REN1_PRE only 12:17:32Z-12:22:31Z / 18:17:32-18:22:31 site; natural charge action 14903 after 12:22:32Z. D5 after 09:00 site resolve exact renewal and close through review.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: REN1_PRE 2026-08-06 12:17:32Z-12:22:31Z (18:17:32-18:22:31 site), then observe action 14903; D5 after 09:00 site owns final renewal read.

[[2026-08-05]] Wed 16:41
D4 coupon-rejection renewal baseline: 2026-08-06 12:17:32Z-12:22:31Z for action 14903 at 12:22:32Z; D5 after 09:00 site read relationship-owned renewal.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4 baseline 2026-08-06 12:17:32Z-12:22:31Z; action 14903; D5 relationship-owned read.

[[2026-08-05]] Wed 17:44
D4 renewal baseline 2026-08-06 12:17:32Z–12:22:31Z; observe action 14903; D5 relationship-exact read.

[[2026-08-06]] Thu 20:10
Closed from completed D3/D4 evidence. D3 coupon-rejection and mixed-cart assertions were published in SLT-CPN-04-D03-armed-facts.txt. D4 renewal order 12900 completed at the undiscounted 10.00 for subscription 12719 with payment mail 5Am5trVjmlneIWb6vZJvgI, confirming no recurring coupon capture.
