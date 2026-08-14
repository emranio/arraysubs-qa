---
id: 61
title: SLT-SYN-04 Prove global sync_to_billing_cycle=true + first_charge_mode=full, and that flex overrides it
status: done
priority: critical
created: 2026-08-02T03:43:08.374116915+02:00
updated: 2026-08-05T10:33:09.1500029+02:00
started: 2026-08-05T10:31:50.743234483+02:00
completed: 2026-08-05T10:31:50.743234483+02:00
tags:
    - renewal-sync
    - day-03
due: "2026-08-05"
estimate: 2h
depends_on:
    - 11
    - 12
    - 26
    - 21
    - 27
class: standard
---

> **SLT-SYN-04** · group `sync` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove what PLAIN global renewal sync does on its own — with `renewals.sync_to_billing_cycle = true` and `renewals.sync_first_charge_mode = "full"`, a subscription's first renewal is snapped to the site-local calendar boundary instead of the checkout anniversary, and the first charge is the FULL recurring amount regardless of how far into the cycle the customer buys — and then prove that a per-product flexible segment plan OVERRIDES that global mode, so a segment-2 purchase prorates even while the global mode says `full`. This is the only task in the window permitted to turn global sync on, and it restores it in the same task.

## Scope
- Gateway: Stripe test
- Checkout: classic (`/slt-classic-cart` + `/slt-classic-checkout`)
- Account: existing (slt-flex)
- Plugins: both

## Preconditions
- SLT-SETUP-02 complete; the window baseline is `renewals.sync_to_billing_cycle = false` and `renewals.sync_first_charge_mode = "full"`. This task temporarily flips ONLY the first of those and restores it before finishing.
- SLT-SETUP-03 complete (account `slt-flex` / `slt-flex@example.test` / `SltQa!2026#Pass`, billing address populated).
- SLT-SETUP-05 complete (gateway capability matrix recorded).
- SLT-SYN-03 complete (`SLT Sync Global Daily`, day/3, $18.00, no flex).
- SLT-PROD-12 complete (`SLT Flex Month Segments`, month/1, $30.00, seg1_end=2 seg2_end=6).
- **Exclusive mutation bracket:** the primary window is 09:00-11:00 site on D3 (2026-08-05). If that window is missed, a same-day recovery bracket is permitted only before any later D3 product-save/cart/checkout task has started. The recovery bracket must be declared as `RECOVERY`, repeat every board/queue/cart preflight, run with no other active mutation session, close within two hours, and restore before the next D3 task begins. Dormant in-progress cards that have no open session/cart and are waiting on future natural gates do not block the bracket. In either form, inspect Pending actions for the whole proposed interval and abort for a known order/subscription-mutating conflict; inventory and classify harmless background maintenance rather than treating every routine non-SLT cron row as a blocker. No other SLT task may save a product, touch a cart, reach checkout, place an order, or run an action while this bracket is open.
- Verified date facts: site is UTC+6. For the real day/3 purchase on 2026-08-05, global sync yields `cycle_start_date = 2026-08-04 18:00:00` UTC (2026-08-05 00:00 site) and `next_payment_date = 2026-08-07 18:00:00` UTC (= 2026-08-08 00:00 site).
- Verified gateway fact: turning global sync ON HIDES Paddle from every sync-eligible cart (`arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false). That is expected here and is re-confirmed as a checkpoint, not a defect.

## Test data
| Item | Value |
|---|---|
| Product | SLT Sync Global Daily (day/3, $18.00, no flex); SLT Flex Month Segments (month/1, $30.00) — probe only, not purchased here |
| Account | slt-flex / slt-flex@example.test / `SltQa!2026#Pass` |
| Coupon | N/A |
| Card | 4242 4242 4242 4242, any future expiry, any CVC, any postcode |
| Amounts | expected charge today $18.00 (FULL, not prorated); expected renewal $18.00 every 3 days on the site-local midnight boundary |

## Steps
1. During the early-morning preparation phase, while global sync is still OFF, run the board/pending-queue preflight above and record `SUBCOUNT_PRE=<exact current SLT subscription count>`. Record the prior value in this task's Notes and on disk: `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public && wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json`. Confirm `renewals.sync_to_billing_cycle` is currently `false` and `renewals.sync_first_charge_mode` is `"full"`. Record a preparation timestamp, not a bracket-open timestamp.
2. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
3. At or after 09:00 site, re-run the board/pending-queue stop checks. Use the primary 09:00-11:00 bracket when available; otherwise label and justify the same-day recovery bracket allowed by the precondition above. Record the exact UTC bracket-open time and bracket type in `/home/server-manager/slt-evidence/SLT-SYN-04-bracket.txt` and `slt-catalog-registry`, then `agent-browser --session admin-SLT-SYN-04 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin-SLT-SYN-04 snapshot -i`. Immediately before the first save set `SYNC_ON_PRE=$(mailpit-agent latest-id)`. In the **Renewal Sync** card switch **Sync Renewals to Next Billing Cycle** ON. Confirm the **First Charge** select reappears reading `Charge the full recurring amount` and do NOT change it. Save; screenshot `SLT-SYN-04-01-global-sync-on.png`, inspect the bounded delta after `SYNC_ON_PRE`, and require zero save-attributable mail.
4. Verify from WP root: `wp option get arraysubs_settings --allow-root | rg -o 'sync_to_billing_cycle";b:[01]'` (expect `b:1`) and `wp eval 'var_dump(arraysubs_is_renewal_sync_enabled(), arraysubs_get_renewal_sync_first_charge_mode());' --allow-root` (expect `true` and `"full"`).
5. PROBE BLOCK — flex overrides the global mode, no purchase required. Run:
   `wp eval '$d=["product_id"=><SLT Flex Month Segments ID>,"period"=>"month","interval"=>1,"trial_length"=>0,"price"=>30.0,"signup_fee"=>0]; foreach(["2026-08-01 03:00:00","2026-08-04 03:00:00","2026-08-08 03:00:00"] as $s){$c=arraysubs_get_renewal_sync_context($d,1,$s,"stripe"); printf("%s applies=%s mode=%-10s seg=%s day=%s unit=%.2f line=%.2f cs=%s np=%s\n",$s,var_export($c["applies"],true),$c["mode"],$c["flex_segment"]??"-",$c["flex_day_in_cycle"]??"-",$c["initial_unit_amount"],$c["initial_line_amount"],$c["cycle_start_date"],$c["next_payment_date"]);}' --allow-root`
   Tee the output to `/home/server-manager/slt-evidence/SLT-SYN-04-flex-override-globalON.txt`.
6. Run the SAME probe against a hypothetical NON-flex month product by passing `"product_id"=>0` (which makes `filterRenewalSyncContext()` bail immediately) and tee to `/home/server-manager/slt-evidence/SLT-SYN-04-plain-global-globalON.txt`. This is the side-by-side that isolates the override.
7. Gateway checkpoint: `agent-browser --session guest-SLT-SYN-04 open "https://mirror-help.arrayhash.com/slt-classic-checkout/?add-to-cart=<SLT Sync Global Daily ID>"`. If one-click redirects to block checkout, record it and explicitly reopen `/slt-classic-checkout`; then `snapshot -i`, record which gateways the classic payment list offers, and capture `SLT-SYN-04-01a-gateways-sync-on.png`. Empty the cart afterwards via `https://mirror-help.arrayhash.com/slt-classic-cart/`.
8. Log in as the customer: `agent-browser --session customer-SLT-SYN-04 open "https://mirror-help.arrayhash.com/my-account"` -> `snapshot -i` -> sign in as `slt-flex` / `SltQa!2026#Pass`; require the cart and `_woocommerce_persistent_cart_1` to be empty.
9. Snapshot the mail id again immediately before the purchase: `PREBUY=$(/usr/local/bin/mailpit-agent latest-id)`.
10. `agent-browser --session customer-SLT-SYN-04 open "https://mirror-help.arrayhash.com/slt-classic-checkout/?add-to-cart=<SLT Sync Global Daily ID>"`; if one-click redirects to block checkout, record it and explicitly reopen `/slt-classic-checkout`. `snapshot -i`, then read and screenshot the classic order summary BEFORE paying as `SLT-SYN-04-02-checkout-summary-global.png`. Record the exact "total due today" string and any subscription schedule line.
11. Select **Stripe** in the payment accordion, enter card `4242 4242 4242 4242`, and place the order without capturing the populated hosted frame. Re-snapshot the order-received page, record numeric `ORDER_GLOBAL`, and capture the safe receipt as `SLT-SYN-04-02a-order-received.png`.
12. `/usr/local/bin/mailpit-agent wait-new "$PREBUY" 180 "is active"`, then inspect the complete owner-filtered delta after `$PREBUY`; require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs, and classify unrelated/background mail separately.
13. Resolve the exact subscription from the recorded parent order, never by recency: `LINK_JSON=$(wp post meta get "$ORDER_GLOBAL" _subscription_ids --format=json --allow-root)` followed by a strict one-element numeric `jq -e` guard. Do not use `WC_Order::get_meta('_subscription_ids')`, which returns an empty value for this legacy linkage on the live HPOS runtime. Cross-check reverse `_parent_order_id=$ORDER_GLOBAL`, exact customer/product, and `SUBCOUNT_POST == SUBCOUNT_PRE + 1`. Record it as numeric `SUBID_GLOBAL`.
14. Dump its sync meta: `wp post meta list <SUBID_GLOBAL> --keys=_renewal_sync_enabled,_renewal_sync_first_charge_mode,_renewal_sync_cycle_start_date,_renewal_sync_first_full_renewal_date,_renewal_sync_initial_recurring_amount,_next_payment_date,_recurring_amount,_billing_period,_billing_interval,_payment_gateway --allow-root`.
15. Dump the ORDER ITEM mirror of the same data: in `admin-SLT-SYN-04`, open `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=$ORDER_GLOBAL` and screenshot the line-item meta rows as `SLT-SYN-04-03-order-item-sync-meta.png`.
16. RESTORE THE BASELINE NOW, before anything else: set `SYNC_OFF_PRE=$(mailpit-agent latest-id)`, then in `admin-SLT-SYN-04` open `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general`, switch **Sync Renewals to Next Billing Cycle** back OFF, Save, and screenshot `SLT-SYN-04-04-global-sync-restored-off.png`. Inspect the bounded delta after `SYNC_OFF_PRE` and require zero restore-attributable mail.
17. Prove the restore: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json` then `diff <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json) <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json)`. Append the exact UTC bracket-close time to the bracket file and registry. A primary bracket must close no later than 11:00 site; a recovery bracket must close within two hours of its recorded open and before any later D3 task begins.
18. Re-confirm the gateway list is back to both after the restore: repeat step 7 with a guest cart, capture `SLT-SYN-04-04a-gateways-restored.png`, then empty the cart.
19. Empty both task carts and verify `slt-flex`'s persistent-cart meta empty. Compute `k` safely with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUBID_GLOBAL"`; query the exact pending reminder/invoice/charge rows, and publish the user/order/subscription, count delta, offset, action IDs/times, and every future `charge−5m` deadline to the registry and D03 watch report. Close only `admin-SLT-SYN-04`, `guest-SLT-SYN-04`, and `customer-SLT-SYN-04` by explicit name, independently review the restored bracket and purchase evidence, and move this card through review to done.

**Restore-first failure rule:** after step 3 succeeds, any probe, browser, checkout, payment, mail, or evidence failure jumps immediately to step 16. Restore and prove the settings diff before investigating or filing the gap; optional assertions become `UNVERIFIED` rather than extending the shared-state deviation.

**Hard restore deadline:** in the primary bracket, if step 16 has not started by 10:45 site, restore immediately and never remain ON past 11:00. In a declared recovery bracket, reserve the final 15 minutes of its two-hour cap for restoration and restore earlier if the queue preflight's next mutation boundary approaches. Leave unfinished optional assertions `UNVERIFIED` rather than overrunning either deadline.

## Expected results
1. With global sync ON, `arraysubs_is_renewal_sync_enabled()` is `true` and `arraysubs_get_renewal_sync_first_charge_mode()` is the string `full`.
2. Step 6 (plain global, `product_id = 0`, month/1 $30.00): ALL THREE start dates produce `applies=true`, `mode=full`, `initial_unit_amount=30.00`, `cycle_start_date=2026-07-31 18:00:00`, `next_payment_date=2026-08-31 18:00:00`. Global sync alone charges the full amount whether you buy on day 1, day 4 or day 8, and always lands the first renewal on 2026-09-01 00:00 site.
3. Step 5 (same dates, but `product_id` = the flex month product): the flexible plan OVERRIDES the global mode —
   - `2026-08-01 03:00:00` -> `flex_day_in_cycle=1`, `flex_segment=1`, `mode=full`, unit `$30.00`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-04 03:00:00` -> `flex_day_in_cycle=4`, `flex_segment=2`, `mode=prorate`, unit `$26.13`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-08 03:00:00` -> `flex_day_in_cycle=8`, `flex_segment=3`, `mode=next_cycle`, unit `$30.00`, `cycle_start_date` rewritten to `2026-08-31 18:00:00` and `next_payment_date=2026-09-30 18:00:00`.
   The day-4 row is the headline: the global mode says `full` and the product still prorates to `$26.13`.
4. Step 7 gateway checkpoint with global sync ON: the payment accordion offers **Stripe** and does NOT offer **Paddle** — expected by design, recorded as a checkpoint, not filed as a defect.
5. Checkout summary for `SLT Sync Global Daily` shows a total due today of exactly `$18.00` — the FULL recurring amount, not a prorated fraction — because the global first-charge mode is `full`.
6. The parent order totals `$18.00` and reaches status `processing` or `completed`.
7. On `SUBID_GLOBAL`: `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-08-04 18:00:00`, `_renewal_sync_first_full_renewal_date=2026-08-07 18:00:00`, `_renewal_sync_initial_recurring_amount=18.00`, `_next_payment_date=2026-08-07 18:00:00` (= 2026-08-08 00:00 site), `_recurring_amount=18.00`, `_billing_period=day`, `_billing_interval=3`, `_payment_gateway=stripe`, post status `arraysubs-active`.
8. The renewal date is a site-local MIDNIGHT boundary, NOT the checkout anniversary. Concretely: `_next_payment_date` ends in `18:00:00` UTC regardless of what time of day the order was placed. Record the actual checkout timestamp alongside it to make the contrast explicit.
9. The same five `_renewal_sync_*` keys are mirrored onto the order line item with identical values.
10. After the restore, the jq diff between `SLT-SYN-04-settings-before.json` and `-after.json` is EMPTY — `sync_to_billing_cycle` is `false` again, `sync_first_charge_mode` is still the string `full`, and no other key moved.
11. After the restore, step 18's guest checkout for `SLT Sync Global Daily` offers BOTH Stripe and Paddle again (the product is no longer sync-eligible with the global switch off and no per-product plan).
12. The exclusive bracket has recorded open/close UTC timestamps and type, no other SLT cart/order/product save occurred inside it, and any routine background cron action was identified and classified. A primary bracket closed by 11:00 site; a recovery bracket closed within two hours and before the next D3 task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Subscription activated by the paid parent order (step 11) | slt-flex@example.test | `Your subscription #` … `is active` | `mailpit-agent wait-new "$PREBUY" 180 "is active"`; save the exact id and `mailpit-agent text <matched-id>` |
| 2 | admin_new_subscription | Same activation | site admin address | `New subscription #` … `from` | Complete owner-filtered delta after `$PREBUY`; save/show the exact matching id |
| 3 | WooCommerce order emails (customer "order is now processing"/"completed", admin "New order") | Parent order status change | customer + admin | order number | Complete owner-filtered delta after `$PREBUY`; count these expected side effects without asserting body content |
| 4 | NONE EXPECTED for renewal_invoice | No renewal has been generated yet in this task | — | — | Complete owner-filtered delta after `$PREBUY` contains no subject matching `Invoice for subscription` |
| 5 | NONE EXPECTED from the two settings saves (steps 3 and 16) | Settings save | — | — | Capture a baseline around each save and inspect its complete delta; require zero save-attributable mail while classifying unrelated/background mail |

## Evidence to capture
- Screenshots: `SLT-SYN-04-01-global-sync-on.png`, `-01a-gateways-sync-on.png`, `-02-checkout-summary-global.png`, `-02a-order-received.png`, `-03-order-item-sync-meta.png`, `-04-global-sync-restored-off.png`, and `-04a-gateways-restored.png`. No image may contain a full card number.
- `SLT-SYN-04-settings-before.json`, `SLT-SYN-04-settings-after.json`, the jq diff (expected empty).
- `SLT-SYN-04-flex-override-globalON.txt` and `SLT-SYN-04-plain-global-globalON.txt` — the two side-by-side probe dumps.
- `SUBID_GLOBAL`, the parent order ID, the full `wp post meta list` dump, the exact checkout timestamp, the checkout total string.
- Mailpit ids for every message produced; `$PREV`, `$SYNC_ON_PRE`, `$PREBUY`, and `$SYNC_OFF_PRE`; exact future action/deadline handoff.
- Any console/network error from the block checkout or the Stripe UPE iframe.

## Pass criteria
- [x] Global sync switched ON with first_charge_mode still "full"
- [x] Plain-global probe: full $30.00 and 2026-09-01 boundary on all three purchase dates
- [x] Flex-override probe: day 4 prorates to $26.13 while the global mode is "full"
- [x] Flex-override probe: day 8 pushes next_payment to 2026-09-30 18:00:00 UTC
- [x] Paddle hidden while global sync is ON; both gateways offered again after the restore
- [x] Checkout charged exactly $18.00 (full, not prorated)
- [x] Subscription carries all five `_renewal_sync_*` metas with the exact expected values
- [x] `_next_payment_date` = 2026-08-07 18:00:00 UTC (site-local midnight boundary, not the anniversary)
- [x] Order line item mirrors the same five metas
- [x] jq settings diff after restore is EMPTY
- [x] Exclusive bracket is measured from the sync-ON save to the sync-OFF save, opened after clean board/queue preflight, and closed within the applicable primary/recovery deadline with registry timestamps
- [x] Only the listed emails appeared; no renewal_invoice mail
- [x] Exact receipt relationship and +1 subscription count proved; all future natural gates handed off before sessions close

## Isolation / teardown
- State handoff: `SUBID_GLOBAL` is a LIVE globally-synced day/3 subscription that will renew unattended on 2026-08-08, 2026-08-11 and 2026-08-14 site-local midnight plus its per-subscription renewal spread offset. The daily renewal watcher must include it and assert that it stays on the midnight boundary even though the global setting was turned back off — the subscription's own `_renewal_sync_enabled=yes` meta is what governs it from here, not the store setting.
- Compute and record the spread offset once with the step-19 positional-argument command — every renewal-timing assertion on this subscription uses it.
- Restores: `renewals.sync_to_billing_cycle` is returned to `false` in step 16, inside this task, and the empty jq diff is the proof. No other setting is touched. The cart is emptied. The subscription, its order and the product are left in place and are removed by SLT-SETUP-99B.
- Binding on later authors: no other SLT task may turn global sync on. If a later task observes sync behaviour it did not expect, check `_renewal_sync_enabled` on the subscription rather than assuming the global switch moved.

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

[[2026-08-05]] Wed 09:16
Recovery preparation: QA plan audit C192 added because the primary 09:00-11:00 site bracket was missed before this card started. Settings remain OFF; count=367; all task carts empty. Action 14521/sub 12263 at 08:05:42Z is a real invoice mutation, so the bracket will open only after it completes and must restore before action 14542/sub 11959 at 09:37:52Z. See SLT-SYN-04-preflight.txt.

[[2026-08-05]] Wed 10:30
PASS. Action 14521 completed naturally at 08:06:11Z. The RECOVERY bracket verified ON/full at 08:09:25Z and restored OFF/full at 08:17:53Z; jq-sorted before/after settings diff is empty, zero settings-save mail, and the close preceded action 14542 by 01:19:59. Plain global and flex-override probes matched every expected row. Real checkout produced order 12563 and sole linked active subscription 12564, count 367→368, exact USD 18.00, mirrored sync metas, and the four expected mail IDs only. Guest gateways were Stripe/no Paddle while ON and Stripe+Paddle after restore. Both task carts and persistent cart are empty. k=10163s; pending invoice 14796 at 2026-08-07 14:49:23Z and charge 14797 at 20:49:23Z; R1 baseline window [20:44:23Z,20:49:23Z). Full evidence: /home/server-manager/slt-evidence/SLT-SYN-04-facts.txt. Static Stripe test-helper digits were redacted from retained screenshots during review.

[[2026-08-14]] Fri 06:46
D12 watch verdict: FAIL — `issues/SLT-SYN-04-successful-renewal-leaves-stale-pending-order-pointer.md`.
