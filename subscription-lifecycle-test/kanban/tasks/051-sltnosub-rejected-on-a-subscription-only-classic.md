---
id: 51
title: SLTNOSUB rejected on a subscription-only classic cart - exact message, mixed-cart partial discount, undiscounted renewal
status: todo
priority: high
created: 2026-08-02T03:43:07.422840178+02:00
updated: 2026-08-02T03:43:17.829339911+02:00
tags:
    - checkout
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
    - 39
class: standard
---

> **SLT-CPN-04** · group `checkout` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-12`, `SLT-CHK-09`, `SLT-SYN-14`, `SLT-CHK-05`, `SLT-ADM-05`, `SLT-EML-06`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

---
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
- Sessions `cpn04-admin` / `cpn04-cust` exclusive (C09).

## Test data
| Item | Value |
|---|---|
| Products | SLT Daily Core $10.00/day + SLT Grouped Extra $3.00 |
| Account | slt-cpnrej (created here) |
| Coupon | SLTNOSUB - percent 30, apply-to-subscriptions NO |
| Card | 4242 4242 4242 4242 |
| Amounts | probe A $10.00; mixed $13.00 - $0.90 = $12.10; renewal $10.00 |

## Steps
1. `cpn04-admin`: create `slt-cpnrej`, fill billing. `wp post meta get <SLTNOSUB ID> _arraysubs_apply_to_subscriptions --allow-root` must print nothing; screenshot the unticked box. `mailpit-agent latest-id` -> `M0`.
2. `cpn04-cust`: log in as `slt-cpnrej`; open `/slt-classic-cart`, confirm EMPTY, screenshot.
3. **Probe A.** Add SLT Daily Core only. Type `SLTNOSUB` in **Coupon code** -> **Apply coupon** -> re-snapshot.
4. Copy the notice **verbatim** (Expected #1) and screenshot; confirm total still **$10.00**, no discount row, no coupon chip.
5. **Probe B.** Add SLT Grouped Extra ($3.00) -> $13.00. Apply `SLTNOSUB` again - ACCEPTED. Screenshot: `Coupon: sltnosub` **-$0.90**, Total **$12.10**; SLT Daily Core line still $10.00.
6. **Probe C.** Open `/slt-classic-checkout`, confirm Total **$12.10**, pay with Stripe, **Place order**. Record order ID and SUB_ID.
7. `wp post meta list <SUB_ID> --allow-root | grep _coupon` must return nothing; no note beginning `Coupon "`.
8. Compute `k = crc32('arraysubs-spread-'.SUB_ID) % 21600`; record invoice window `due+k-6h` and charge `due+k`. `mailpit-agent wait-new $M0 120 "is active"`; record checkout mail.
9. **2026-08-07 after 09:00 site** (renewal #1 charged the evening of 08-06): open the renewal order, screenshot its item table, run `wp eval '$o=wc_get_order(<REN1>);echo count($o->get_items()).count($o->get_fees()).$o->get_total();' --allow-root`.
10. Empty the cart, screenshot it, append all IDs to `slt-catalog-registry`.

## Expected results
1. Probe A: refused with the verbatim notice **`Coupon is not valid.`** - `class-wc-discounts.php:1149` throws `E_WC_COUPON_INVALID_FILTERED` (100) with a non-numeric message, so `get_coupon_error()` is bypassed and `Coupon "sltnosub" cannot be applied because it is not valid.` is NOT shown. Any other text: record it and file a `bug` issue. Cart stays **$10.00**; the coupon never enters the applied list.
2. Probe A sends zero mail; no PHP notice or 4xx/5xx in the network log.
3. Probe B: accepted on the mixed cart; discount exactly **$0.90** (30% x $3.00); total **$12.10**; the $10.00 subscription line untouched.
4. Probe C order total **$12.10**, status processing/completed, coupon `sltnosub` on the order, exactly ONE subscription created (Grouped Extra creates none).
5. The subscription has NO `_applied_coupon_id` / `_coupon_*` metas (`captureOrderCoupons()` skips at its `'yes' !== $apply_to_subs` gate) and no `Coupon "..."` note.
6. Renewal #1 (charged the evening of 2026-08-06, seen on the 08-07 watch run = watch day D5): exactly **one** line item, **zero** fees, **zero** coupons, total **$10.00**. Grouped Extra does not recur.
7. `_next_payment_date` advances exactly 1 day; status stays `arraysubs-active`; `SLTNOSUB`'s meta is still empty at task end.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | **NONE EXPECTED** | probe A and probe B | - | - | `mailpit-agent latest-id` after step 5 equals `M0` |
| 2 | new_subscription | order paid | slt-cpnrej@ | `is active` | `wait-new $M0 120 "is active"` |
| 3 | admin_new_subscription | order paid | admin_email | `New subscription #` | `mailpit-agent list 20` |
| 4 | payment_successful | renewal #1 paid | slt-cpnrej@ | `Payment received for subscription #<SUB_ID>` | `wait-new <prev> 900 "Payment received"` |

## Evidence to capture
- Screenshots `SLT-CPN-04-NN-<slug>.png`: unticked coupon box, rejection notice, mixed cart $12.10, checkout review, renewal #1 $10.00.
- The rejection string character-for-character, HTTP status and console errors from the apply POST, coupon ID, SUB_ID, order IDs, `k`, meta and `wp eval` output, Mailpit ids.

## Pass criteria
- [ ] Subscription-only cart rejects SLTNOSUB; message verbatim; cart stays $10.00; zero mail
- [ ] Mixed cart accepts it: $0.90 discount, $12.10 total, subscription line undiscounted
- [ ] Mixed order creates one subscription with zero coupon metas and no coupon note
- [ ] Renewal #1 totals $10.00 with one line item, zero fees, zero coupons; SLTNOSUB's meta unchanged

## Isolation / teardown
- Watch D5..D9: this subscription renews at $10.00 daily with no fee line; a `Recurring Discount:` fee is a defect.
- Leaves `slt-cpnrej`, a mixed order and one active daily subscription - add all to SLT-SETUP-99A's cancel list. Close `cpn04-*` sessions only.


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
