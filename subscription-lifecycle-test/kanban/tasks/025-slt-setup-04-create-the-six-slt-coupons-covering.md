---
id: 25
title: SLT-SETUP-04 Create the six SLT coupons covering recurring, one-time, N-cycle, fee and reject paths
status: todo
priority: high
created: 2026-08-02T03:43:05.087052946+02:00
updated: 2026-08-02T03:43:15.468963007+02:00
tags:
    - setup
    - day-01
    - has-conflicts
due: "2026-08-03"
estimate: 1h
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-04** · group `foundation` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-05`, `SLT-PROD-01`, `SLT-PROD-02`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · impossible-timing** — with `SLT-PROD-14`, `SLT-PROD-15`, `SLT-SYN-04`

- *Problem:* SLT-SETUP-04 sets Coupon expiry date = 2026-08-12 on all six SLT coupons and justifies it as 'past the watch window'. It is not: the watch window runs to D12 = 2026-08-13, and renewals fall on 2026-08-11, 2026-08-12 and 2026-08-13 (SLT Flex Daily Two Seg / Next Cycle, the SLT Flex Variable Daily day/3 cohort, and the SLT-SYN-04 globally-synced subscription). SLTPCT20REC is a RECURRING discount whose renewal-order behaviour is exactly what those tail renewals would prove, and a coupon that has expired by then makes the tail assertion untestable or produces a false negative.
- *Required fix:* Set Coupon expiry date = 2026-08-14 (or leave it blank and rely on SLT-SETUP-99B deletion) for all six SLT coupons, and record in the registry that no SLT coupon may expire before the last watch day. Update SLT-SETUP-04 step 4 and its Description string ('delete on 2026-08-11' -> 'delete on 2026-08-13').

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Build the coupon matrix against the real `ArraySubs\Features\CouponTracking\Services\Hooks` contract: the plugin adds exactly four coupon metas — `_arraysubs_apply_to_subscriptions`, `_arraysubs_discount_duration` (`one-time`|`recurring`), `_arraysubs_discount_cycles`, `_arraysubs_count_initial_checkout` — and rejects any coupon lacking the first one on a subscription-only cart.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Existing coupons `qa-audit-coupon, save20, NONSUB5, RENEW20FOR3, SUB10ONCE, halfoff3, nosub10, welcome15` are OFF-LIMITS — do not open, edit, or apply them.
- Code facts to rely on (verified, do not re-derive): `validateSubscriptionCouponEligibility()` returns false — i.e. the coupon is rejected outright — when the cart contains at least one subscription item and NO regular item and the coupon has `_arraysubs_apply_to_subscriptions != 'yes'`. On a MIXED cart the same coupon stays valid but `filterSubscriptionCouponItems()` strips the subscription lines from the discount. The signup fee is a **cart fee** added by `SubscriptionProducts\Services\Hooks::addSignupFeeToCart()` named `Subscription Signup Fee`; WooCommerce coupons never discount fees, so a "signup-fee-only coupon" is NOT a supported concept — `SLTFEEPROBE` exists to prove that.

## Test data
| Item | Value |
|---|---|
| Product | N/A (coupons only) |
| Account | admin |
| Coupon | the six below |
| Card | N/A |
| Amounts | see table |

| Code | WC discount type | Amount | Apply to subscriptions | Discount duration | Renewal cycles | Count initial checkout | Proves |
|---|---|---|---|---|---|---|---|
| SLTPCT20REC | Percentage discount | 20 | yes | Recurring | 0 | no | Percent off forever, initial + unlimited renewals |
| SLTFIX5FIRST | Fixed cart discount | 5.00 | yes | One-time (initial order only) | 0 | no | Fixed off first payment only, renewals full price |
| SLTREC3 | Percentage discount | 25 | yes | Recurring | 3 | yes | N-cycle counting WITH the checkout consuming a cycle -> 2 discounted renewals |
| SLTREC3NOINIT | Percentage discount | 25 | yes | Recurring | 3 | no | N-cycle counting WITHOUT the checkout consuming a cycle -> 3 discounted renewals |
| SLTFEEPROBE | Fixed cart discount | 10.00 | yes | One-time (initial order only) | 0 | no | Signup fee is a fee, not a discountable line — negative control |
| SLTNOSUB | Percentage discount | 30 | **no** | One-time (initial order only) | 0 | no | Must be REJECTED on a subscription-only cart; applies only to the regular line on a mixed cart |

## Steps
1. Capture `mailpit-agent latest-id` before starting.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=shop_coupon"` -> `agent-browser --session admin snapshot -i`.
3. For each row: type the code into the **Coupon code** title field; set **Description** to `SLT window coupon — delete on 2026-08-11`.
4. On the **General** tab set **Discount type** and **Coupon amount** per the table. Leave **Allow free shipping** off. Set **Coupon expiry date** = `2026-08-12` for all six (past the watch window so nothing expires mid-test, but they self-limit).
5. On the **Usage restriction** tab leave everything empty except **Minimum spend** blank; do NOT set product/category restrictions — later tasks rely on these coupons being cart-wide.
6. On the **Usage limits** tab set **Usage limit per coupon** = blank (unlimited) and **Usage limit per user** = blank.
7. Scroll to the **ArraySubs Subscription Settings** group (rendered by `CouponTracking` under the coupon data panel) and set: **Apply to subscriptions** checkbox, **Discount duration** select, **Number of renewal cycles** number field, **Count initial checkout** checkbox — exactly as the table says. For SLTNOSUB leave **Apply to subscriptions** UNCHECKED.
8. Publish. Re-snapshot and confirm the four ArraySubs fields persisted after the reload (they are saved on `woocommerce_coupon_options_save`).
9. Repeat for all six coupons.
10. Verify the metas from WP root for each coupon id: `wp post meta list <ID> --keys=_arraysubs_apply_to_subscriptions,_arraysubs_discount_duration,_arraysubs_discount_cycles,_arraysubs_count_initial_checkout --allow-root`.
11. Append the six coupon IDs to `slt-catalog-registry`.

## Expected results
1. Six coupons exist with exactly the codes above and status `publish`.
2. `SLTPCT20REC`: `_arraysubs_apply_to_subscriptions=yes`, `_arraysubs_discount_duration=recurring`, `_arraysubs_discount_cycles=0`, `_arraysubs_count_initial_checkout=` (empty).
3. `SLTFIX5FIRST`: apply=yes, duration=one-time, cycles=0, count_initial empty.
4. `SLTREC3`: apply=yes, duration=recurring, cycles=3, count_initial=yes.
5. `SLTREC3NOINIT`: apply=yes, duration=recurring, cycles=3, count_initial empty.
6. `SLTFEEPROBE`: apply=yes, duration=one-time, cycles=0, count_initial empty.
7. `SLTNOSUB`: `_arraysubs_apply_to_subscriptions` is empty string (NOT `yes`).
8. All eight pre-existing coupons are untouched (`post_modified` unchanged).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Coupon publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshot per coupon of the **ArraySubs Subscription Settings** group after reload: `SLT-SETUP-04-0N-<code>.png`.
- `wp post meta list` output for each of the six.
- Six coupon IDs in the registry.

## Pass criteria
- [ ] All six coupons published with the exact codes
- [ ] All four ArraySubs metas correct on each coupon and surviving a reload
- [ ] SLTNOSUB has apply-to-subscriptions unset
- [ ] Pre-existing coupons untouched
- [ ] Zero mail sent

## Isolation / teardown
- State handoff: `SLTPCT20REC` for recurring-discount renewal tests; `SLTFIX5FIRST` for first-payment-only; `SLTREC3` / `SLTREC3NOINIT` for the cycle-counting pair (they must be used on two DIFFERENT subscriptions — the plugin stores only one captured coupon per subscription via `_applied_coupon_id`); `SLTFEEPROBE` only with SLT Signup Fee Daily; `SLTNOSUB` for the rejection path (subscription-only cart) and the mixed-cart partial path.
- Restores: nothing global. SLT-SETUP-99 trashes and permanently deletes all six.

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
