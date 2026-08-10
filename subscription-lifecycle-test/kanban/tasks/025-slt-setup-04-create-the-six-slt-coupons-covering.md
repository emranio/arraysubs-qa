---
id: 25
title: SLT-SETUP-04 Create the six SLT coupons covering recurring, one-time, N-cycle, fee and reject paths
status: done
priority: high
created: 2026-08-02T03:43:05.087052946+02:00
updated: 2026-08-03T02:31:56.826827399+02:00
started: 2026-08-03T02:31:56.826826507+02:00
completed: 2026-08-03T02:31:56.826826507+02:00
tags:
    - setup
    - day-01
due: "2026-08-03"
estimate: 1h
depends_on:
    - 10
class: standard
---

> **SLT-SETUP-04** · group `foundation` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

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
1. Capture `M0=$(mailpit-agent latest-id)` before starting. Before opening any coupon, run the following read-only query from the WP root and require exactly eight rows, one per protected code. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner:
   `wp db query "SELECT ID,LOWER(post_title) AS code,post_status,post_modified FROM wp_posts WHERE post_type='shop_coupon' AND LOWER(post_title) IN ('qa-audit-coupon','save20','nonsub5','renew20for3','sub10once','halfoff3','nosub10','welcome15') ORDER BY LOWER(post_title),ID;" --batch --raw --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-04-protected-before.tsv`.
   Then run `wp db query "SELECT ID,LOWER(post_title) AS code,post_status FROM wp_posts WHERE post_type='shop_coupon' AND LOWER(post_title) IN ('sltpct20rec','sltfix5first','sltrec3','sltrec3noinit','sltfeeprobe','sltnosub') ORDER BY LOWER(post_title),ID;" --batch --raw --allow-root` and require only the header row (zero target records). If any target exists, stop and document the collision rather than overwriting it.
2. `agent-browser --session admin-SLT-SETUP-04 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=shop_coupon"` -> `agent-browser --session admin-SLT-SETUP-04 snapshot -i`.
3. For each row: type the code into the **Coupon code** title field; set **Description** to `SLT window coupon — delete on 2026-08-15`.
4. On the **General** tab set **Discount type** and **Coupon amount** per the table. Leave **Allow free shipping** off. Set **Coupon expiry date** = `2026-08-15` for all six. WooCommerce stores a date-only coupon expiry at `00:00:00` site time, so `2026-08-14` would expire at the start of the final watch day; `2026-08-15` keeps them valid throughout D12, and `SLT-SETUP-99B` deletes them later that morning.
5. On the **Usage restriction** tab leave everything empty except **Minimum spend** blank; do NOT set product/category restrictions — later tasks rely on these coupons being cart-wide.
6. On the **Usage limits** tab set **Usage limit per coupon** = blank (unlimited) and **Usage limit per user** = blank.
7. Scroll to the **ArraySubs Subscription Settings** group (rendered by `CouponTracking` under the coupon data panel) and set: **Apply to subscriptions** checkbox, **Discount duration** select, **Number of renewal cycles** number field, **Count initial checkout** checkbox — exactly as the table says. For SLTNOSUB leave **Apply to subscriptions** UNCHECKED.
8. Publish. Re-snapshot and confirm the four ArraySubs fields persisted after the reload (they are saved on `woocommerce_coupon_options_save`).
9. Repeat for all six coupons.
10. Verify the metas from WP root for each coupon id: `wp post meta list <ID> --keys=_arraysubs_apply_to_subscriptions,_arraysubs_discount_duration,_arraysubs_discount_cycles,_arraysubs_count_initial_checkout,date_expires --allow-root`. Convert `date_expires` to site time and require `2026-08-15 00:00:00 +06`, not the start of D12.
11. Append the six coupon IDs to `slt-catalog-registry`. Re-run the exact protected-coupon query from step 1 to `/home/server-manager/slt-evidence/SLT-SETUP-04-protected-after.tsv` and require `diff -u /home/server-manager/slt-evidence/SLT-SETUP-04-protected-before.tsv /home/server-manager/slt-evidence/SLT-SETUP-04-protected-after.tsv` to produce no output.
12. Close only `admin-SLT-SETUP-04`.

## Expected results
1. Six coupons exist with exactly the codes above and status `publish`.
2. `SLTPCT20REC`: `_arraysubs_apply_to_subscriptions=yes`, `_arraysubs_discount_duration=recurring`, `_arraysubs_discount_cycles=0`, `_arraysubs_count_initial_checkout=` (empty).
3. `SLTFIX5FIRST`: apply=yes, duration=one-time, cycles=0, count_initial empty.
4. `SLTREC3`: apply=yes, duration=recurring, cycles=3, count_initial=yes.
5. `SLTREC3NOINIT`: apply=yes, duration=recurring, cycles=3, count_initial empty.
6. `SLTFEEPROBE`: apply=yes, duration=one-time, cycles=0, count_initial empty.
7. `SLTNOSUB`: `_arraysubs_apply_to_subscriptions` is empty string (NOT `yes`).
8. All six `date_expires` values resolve to `2026-08-15 00:00:00` site time, so every coupon remains valid through all of D12.
9. All eight pre-existing coupons are untouched (`post_modified` unchanged).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Coupon publish | — | — | Complete delta after `M0`; zero message attributable to this task, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshot per coupon of the **ArraySubs Subscription Settings** group after reload: `SLT-SETUP-04-0N-<code>.png`.
- `wp post meta list` output for each of the six; protected-coupon before/after TSV files and the empty diff.
- Six coupon IDs in the registry.

## Pass criteria
- [ ] All six coupons published with the exact codes
- [ ] All four ArraySubs metas correct on each coupon and surviving a reload
- [ ] SLTNOSUB has apply-to-subscriptions unset
- [ ] Every expiry resolves to 2026-08-15 00:00 site, after the final watch day
- [ ] Pre-existing coupons untouched
- [ ] Zero mail sent

## Isolation / teardown
- State handoff: `SLTPCT20REC` for recurring-discount renewal tests; `SLTFIX5FIRST` for first-payment-only; `SLTREC3` / `SLTREC3NOINIT` for the cycle-counting pair (they must be used on two DIFFERENT subscriptions — the plugin stores only one captured coupon per subscription via `_applied_coupon_id`); `SLTFEEPROBE` only with SLT Signup Fee Daily; `SLTNOSUB` for the rejection path (subscription-only cart) and the mixed-cart partial path.
- Restores: nothing global. SLT-SETUP-99B trashes and permanently deletes all six on 2026-08-15.

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

## D1 execution checkpoint — 2026-08-03

**PASS.** Published the six task-owned coupons through `admin-SLT-SETUP-04`: `SLTPCT20REC` `12064`, `SLTFIX5FIRST` `12067`, `SLTREC3` `12068`, `SLTREC3NOINIT` `12071`, `SLTFEEPROBE` `12075`, and `SLTNOSUB` `12076`. All are `publish`; their WooCommerce types/amounts and four ArraySubs metas match the authored matrix. Every `date_expires` value is `1786730400`, resolving to `2026-08-15 00:00:00 +06:00`.

The protected eight-coupon query returned the same IDs, statuses, and `post_modified` values before and after; `/home/server-manager/slt-evidence/SLT-SETUP-04-protected-before.tsv` and `-protected-after.tsv` have an empty semantic diff. Mailpit stayed at baseline `42DI8ELEccd8qFsaMtyeag`, so coupon creation emitted zero mail. The six IDs and contracts were saved to registry page `11847`.

Evidence: `/home/server-manager/slt-evidence/SLT-SETUP-04-facts.txt`, the six `SLT-SETUP-04-0N-*.png` screenshots, and the protected-coupon TSV pair. Browser errors were empty; console output was limited to the informational JQMIGRATE banner.
