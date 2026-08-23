---
id: 61
title: SLT-SYN-04 Prove global sync_to_billing_cycle=true + first_charge_mode=full, and that flex overrides it
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-03
due: "2026-08-26"
estimate: 2h
depends_on:
    - 11
    - 12
    - 26
    - 21
    - 27
class: standard
---

> **SLT-SYN-04** · group `sync` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove what PLAIN global renewal sync does on its own — with `renewals.sync_to_billing_cycle = true` and `renewals.sync_first_charge_mode = "full"`, a subscription's first renewal is snapped to the site-local calendar boundary instead of the checkout anniversary, and the first charge is the FULL recurring amount regardless of how far into the cycle the customer buys — and then prove that a per-product flexible segment plan OVERRIDES that global mode, so a segment-2 purchase prorates even while the global mode says `full`. This is the only task in the window permitted to turn global sync on, and it restores it in the same task.

## Scope
- Gateway: Stripe test
- Checkout: classic (`/slt2-classic-cart` + `/slt2-classic-checkout`)
- Account: existing (slt2-flex)
- Plugins: both

## Preconditions
- SLT-SETUP-02 complete; the window baseline is `renewals.sync_to_billing_cycle = false` and `renewals.sync_first_charge_mode = "full"`. This task temporarily flips ONLY the first of those and restores it before finishing.
- SLT-SETUP-03 complete (account `slt2-flex` / `slt2-flex@example.test` / `SltQa!2026#Pass`, billing address populated).
- SLT-SETUP-05 complete (gateway capability matrix recorded).
- SLT-SYN-03 complete (`SLT2 Sync Global Daily`, day/3, $18.00, no flex).
- SLT-PROD-12 complete (`SLT2 Flex Month Segments`, month/1, $30.00, seg1_end=24 seg2_end=27).
- **Exclusive mutation bracket:** the primary window is 09:00-11:00 site on D3 (2026-08-26). If that window is missed, a same-day recovery bracket is permitted only before any later D3 product-save/cart/checkout task has started. The recovery bracket must be declared as `RECOVERY`, repeat every board/queue/cart preflight, run with no other active mutation session, close within two hours, and restore before the next D3 task begins. Dormant in-progress cards that have no open session/cart and are waiting on future natural gates do not block the bracket. In either form, inspect Pending actions for the whole proposed interval and abort for a known order/subscription-mutating conflict; inventory and classify harmless background maintenance rather than treating every routine non-SLT2 cron row as a blocker. No other SLT2 task may save a product, touch a cart, reach checkout, place an order, or run an action while this bracket is open.
- Verified date facts: site is UTC+6. For the real day/3 purchase on 2026-08-26, global sync yields `cycle_start_date = 2026-08-25 18:00:00` UTC (2026-08-26 00:00 site) and `next_payment_date = 2026-08-28 18:00:00` UTC (= 2026-08-29 00:00 site).
- Verified gateway fact: turning global sync ON HIDES Paddle from every sync-eligible cart (`arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false). That is expected here and is re-confirmed as a checkpoint, not a defect.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Sync Global Daily (day/3, $18.00, no flex); SLT2 Flex Month Segments (month/1, $30.00) — probe only, not purchased here |
| Account | slt2-flex / slt2-flex@example.test / `SltQa!2026#Pass` |
| Coupon | N/A |
| Card | 4242 4242 4242 4242, any future expiry, any CVC, any postcode |
| Amounts | expected charge today $18.00 (FULL, not prorated); expected renewal $18.00 every 3 days on the site-local midnight boundary |

## Steps
1. During the early-morning preparation phase, while global sync is still OFF, run the board/pending-queue preflight above and record `SUBCOUNT_PRE=<exact current SLT2 subscription count>`. Record the prior value in this task's Notes and on disk: `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public && wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json`. Confirm `renewals.sync_to_billing_cycle` is currently `false` and `renewals.sync_first_charge_mode` is `"full"`. Record a preparation timestamp, not a bracket-open timestamp.
2. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
3. At or after 09:00 site, re-run the board/pending-queue stop checks. Use the primary 09:00-11:00 bracket when available; otherwise label and justify the same-day recovery bracket allowed by the precondition above. Record the exact UTC bracket-open time and bracket type in `/home/server-manager/slt-evidence/SLT-SYN-04-bracket.txt` and `slt2-catalog-registry`, then `agent-browser --session admin-SLT-SYN-04 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin-SLT-SYN-04 snapshot -i`. Immediately before the first save set `SYNC_ON_PRE=$(mailpit-agent latest-id)`. In the **Renewal Sync** card switch **Sync Renewals to Next Billing Cycle** ON. Confirm the **First Charge** select reappears reading `Charge the full recurring amount` and do NOT change it. Save; screenshot `SLT-SYN-04-01-global-sync-on.png`, inspect the bounded delta after `SYNC_ON_PRE`, and require zero save-attributable mail.
4. Verify from WP root: `wp option get arraysubs_settings --allow-root | rg -o 'sync_to_billing_cycle";b:[01]'` (expect `b:1`) and `wp eval 'var_dump(arraysubs_is_renewal_sync_enabled(), arraysubs_get_renewal_sync_first_charge_mode());' --allow-root` (expect `true` and `"full"`).
5. PROBE BLOCK — flex overrides the global mode, no purchase required. Run:
   `wp eval '$d=["product_id"=><SLT2 Flex Month Segments ID>,"period"=>"month","interval"=>1,"trial_length"=>0,"price"=>30.0,"signup_fee"=>0]; foreach(["2026-08-24 03:00:00","2026-08-25 03:00:00","2026-08-29 03:00:00"] as $s){$c=arraysubs_get_renewal_sync_context($d,1,$s,"stripe"); printf("%s applies=%s mode=%-10s seg=%s day=%s unit=%.2f line=%.2f cs=%s np=%s\n",$s,var_export($c["applies"],true),$c["mode"],$c["flex_segment"]??"-",$c["flex_day_in_cycle"]??"-",$c["initial_unit_amount"],$c["initial_line_amount"],$c["cycle_start_date"],$c["next_payment_date"]);}' --allow-root`
   Tee the output to `/home/server-manager/slt-evidence/SLT-SYN-04-flex-override-globalON.txt`.
6. Run the SAME probe against a hypothetical NON-flex month product by passing `"product_id"=>0` (which makes `filterRenewalSyncContext()` bail immediately) and tee to `/home/server-manager/slt-evidence/SLT-SYN-04-plain-global-globalON.txt`. This is the side-by-side that isolates the override.
7. Gateway checkpoint: `agent-browser --session guest-SLT-SYN-04 open "https://mirror-help.arrayhash.com/slt2-classic-checkout/?add-to-cart=<SLT2 Sync Global Daily ID>"`. If one-click redirects to block checkout, record it and explicitly reopen `/slt2-classic-checkout`; then `snapshot -i`, record which gateways the classic payment list offers, and capture `SLT-SYN-04-01a-gateways-sync-on.png`. Empty the cart afterwards via `https://mirror-help.arrayhash.com/slt2-classic-cart/`.
8. Log in as the customer: `agent-browser --session customer-SLT-SYN-04 open "https://mirror-help.arrayhash.com/my-account"` -> `snapshot -i` -> sign in as `slt2-flex` / `SltQa!2026#Pass`; require the cart and `_woocommerce_persistent_cart_1` to be empty.
9. Snapshot the mail id again immediately before the purchase: `PREBUY=$(/usr/local/bin/mailpit-agent latest-id)`.
10. `agent-browser --session customer-SLT-SYN-04 open "https://mirror-help.arrayhash.com/slt2-classic-checkout/?add-to-cart=<SLT2 Sync Global Daily ID>"`; if one-click redirects to block checkout, record it and explicitly reopen `/slt2-classic-checkout`. `snapshot -i`, then read and screenshot the classic order summary BEFORE paying as `SLT-SYN-04-02-checkout-summary-global.png`. Record the exact "total due today" string and any subscription schedule line.
11. Select **Stripe** in the payment accordion, enter card `4242 4242 4242 4242`, and place the order without capturing the populated hosted frame. Re-snapshot the order-received page, record numeric `ORDER_GLOBAL`, and capture the safe receipt as `SLT-SYN-04-02a-order-received.png`.
12. `/usr/local/bin/mailpit-agent wait-new "$PREBUY" 180 "is active"`, then inspect the complete owner-filtered delta after `$PREBUY`; require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs, and classify unrelated/background mail separately.
13. Resolve the exact subscription from the recorded parent order, never by recency: `LINK_JSON=$(wp post meta get "$ORDER_GLOBAL" _subscription_ids --format=json --allow-root)` followed by a strict one-element numeric `jq -e` guard. Do not use `WC_Order::get_meta('_subscription_ids')`, which returns an empty value for this legacy linkage on the live HPOS runtime. Cross-check reverse `_parent_order_id=$ORDER_GLOBAL`, exact customer/product, and `SUBCOUNT_POST == SUBCOUNT_PRE + 1`. Record it as numeric `SUBID_GLOBAL`.
14. Dump its sync meta: `wp post meta list <SUBID_GLOBAL> --keys=_renewal_sync_enabled,_renewal_sync_first_charge_mode,_renewal_sync_cycle_start_date,_renewal_sync_first_full_renewal_date,_renewal_sync_initial_recurring_amount,_next_payment_date,_recurring_amount,_billing_period,_billing_interval,_payment_gateway --allow-root`.
15. Dump the ORDER ITEM mirror of the same data: in `admin-SLT-SYN-04`, open `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=$ORDER_GLOBAL` and screenshot the line-item meta rows as `SLT-SYN-04-03-order-item-sync-meta.png`.
16. RESTORE THE BASELINE NOW, before anything else: set `SYNC_OFF_PRE=$(mailpit-agent latest-id)`, then in `admin-SLT-SYN-04` open `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general`, switch **Sync Renewals to Next Billing Cycle** back OFF, Save, and screenshot `SLT-SYN-04-04-global-sync-restored-off.png`. Inspect the bounded delta after `SYNC_OFF_PRE` and require zero restore-attributable mail.
17. Prove the restore: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json` then `diff <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json) <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json)`. Append the exact UTC bracket-close time to the bracket file and registry. A primary bracket must close no later than 11:00 site; a recovery bracket must close within two hours of its recorded open and before any later D3 task begins.
18. Re-confirm the gateway list is back to both after the restore: repeat step 7 with a guest cart, capture `SLT-SYN-04-04a-gateways-restored.png`, then empty the cart.
19. Empty both task carts and verify `slt2-flex`'s persistent-cart meta empty. Compute `k` safely with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUBID_GLOBAL"`; query the exact pending reminder/invoice/charge rows, and publish the user/order/subscription, count delta, offset, action IDs/times, and every future `charge−5m` deadline to the registry and D03 watch report. Close only `admin-SLT-SYN-04`, `guest-SLT-SYN-04`, and `customer-SLT-SYN-04` by explicit name, independently review the restored bracket and purchase evidence, and move this card through review to done.

**Restore-first failure rule:** after step 3 succeeds, any probe, browser, checkout, payment, mail, or evidence failure jumps immediately to step 16. Restore and prove the settings diff before investigating, then create/update the QA issue and schedule a safe retry; unfinished required assertions keep the card blocked.

**Hard restore deadline:** in the primary bracket, if step 16 has not started by 10:45 site, restore immediately and never remain ON past 11:00. In a declared recovery bracket, reserve the final 15 minutes for restoration. Rerun unfinished assertions in a newly declared safe bracket; do not count them as passed.

## Expected results
1. With global sync ON, `arraysubs_is_renewal_sync_enabled()` is `true` and `arraysubs_get_renewal_sync_first_charge_mode()` is the string `full`.
2. Step 6 (plain global, `product_id = 0`, month/1 $30.00): ALL THREE start dates produce `applies=true`, `mode=full`, `initial_unit_amount=30.00`, `cycle_start_date=2026-07-31 18:00:00`, `next_payment_date=2026-08-31 18:00:00`. Global sync alone charges the full amount on days 24, 25 and 29 and always lands the first renewal on 2026-09-01 00:00 site.
3. Step 5 (same dates, but `product_id` = the flex month product): the flexible plan OVERRIDES the global mode —
   - `2026-08-24 03:00:00` -> `flex_day_in_cycle=24`, `flex_segment=1`, `mode=full`, unit `$30.00`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-25 03:00:00` -> `flex_day_in_cycle=25`, `flex_segment=2`, `mode=prorate`, unit `$5.81`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-29 03:00:00` -> `flex_day_in_cycle=29`, `flex_segment=3`, `mode=next_cycle`, unit `$30.00`, `cycle_start_date` rewritten to `2026-08-31 18:00:00` and `next_payment_date=2026-09-30 18:00:00`.
   The day-25 row is the headline: the global mode says `full` and the product still prorates to `$5.81`.
4. Step 7 gateway checkpoint with global sync ON: the payment accordion offers **Stripe** and does NOT offer **Paddle** — expected by design, recorded as a checkpoint, not filed as a defect.
5. Checkout summary for `SLT2 Sync Global Daily` shows a total due today of exactly `$18.00` — the FULL recurring amount, not a prorated fraction — because the global first-charge mode is `full`.
6. The parent order totals `$18.00` and reaches status `processing` or `completed`.
7. On `SUBID_GLOBAL`: `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-08-25 18:00:00`, `_renewal_sync_first_full_renewal_date=2026-08-28 18:00:00`, `_renewal_sync_initial_recurring_amount=18.00`, `_next_payment_date=2026-08-28 18:00:00` (= 2026-08-29 00:00 site), `_recurring_amount=18.00`, `_billing_period=day`, `_billing_interval=3`, `_payment_gateway=stripe`, post status `arraysubs-active`.
8. The renewal date is a site-local MIDNIGHT boundary, NOT the checkout anniversary. Concretely: `_next_payment_date` ends in `18:00:00` UTC regardless of what time of day the order was placed. Record the actual checkout timestamp alongside it to make the contrast explicit.
9. The same five `_renewal_sync_*` keys are mirrored onto the order line item with identical values.
10. After the restore, the jq diff between `SLT-SYN-04-settings-before.json` and `-after.json` is EMPTY — `sync_to_billing_cycle` is `false` again, `sync_first_charge_mode` is still the string `full`, and no other key moved.
11. After the restore, step 18's guest checkout for `SLT2 Sync Global Daily` offers BOTH Stripe and Paddle again (the product is no longer sync-eligible with the global switch off and no per-product plan).
12. The exclusive bracket has recorded open/close UTC timestamps and type, no other SLT2 cart/order/product save occurred inside it, and any routine background cron action was identified and classified. A primary bracket closed by 11:00 site; a recovery bracket closed within two hours and before the next D3 task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Subscription activated by the paid parent order (step 11) | slt2-flex@example.test | `Your subscription #` … `is active` | `mailpit-agent wait-new "$PREBUY" 180 "is active"`; save the exact id and `mailpit-agent text <matched-id>` |
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
- [ ] Global sync switched ON with first_charge_mode still "full"
- [ ] Plain-global probe: full $30.00 and 2026-09-01 boundary on all three purchase dates
- [ ] Flex-override probe: day 25 prorates to $5.81 while the global mode is "full"
- [ ] Flex-override probe: day 29 pushes next_payment to 2026-09-30 18:00:00 UTC
- [ ] Paddle hidden while global sync is ON; both gateways offered again after the restore
- [ ] Checkout charged exactly $18.00 (full, not prorated)
- [ ] Subscription carries all five `_renewal_sync_*` metas with the exact expected values
- [ ] `_next_payment_date` = 2026-08-28 18:00:00 UTC (site-local midnight boundary, not the anniversary)
- [ ] Order line item mirrors the same five metas
- [ ] jq settings diff after restore is EMPTY
- [ ] Exclusive bracket is measured from the sync-ON save to the sync-OFF save, opened after clean board/queue preflight, and closed within the applicable primary/recovery deadline with registry timestamps
- [ ] Only the listed emails appeared; no renewal_invoice mail
- [ ] Exact receipt relationship and +1 subscription count proved; all future natural gates handed off before sessions close

## Isolation / teardown
- State handoff: `SUBID_GLOBAL` is a LIVE globally-synced day/3 subscription that will renew unattended on 2026-08-29, 2026-09-01 and 2026-09-04 site-local midnight plus its per-subscription renewal spread offset. The daily renewal watcher must include it and assert that it stays on the midnight boundary even though the global setting was turned back off — the subscription's own `_renewal_sync_enabled=yes` meta is what governs it from here, not the store setting.
- Compute and record the spread offset once with the step-19 positional-argument command — every renewal-timing assertion on this subscription uses it.
- Restores: `renewals.sync_to_billing_cycle` is returned to `false` in step 16, inside this task, and the empty jq diff is the proof. No other setting is touched. The cart is emptied. The subscription, its order and the product are left in place and are removed by SLT-SETUP-99B.
- Binding on later authors: no other SLT2 task may turn global sync on. If a later task observes sync behaviour it did not expect, check `_renewal_sync_enabled` on the subscription rather than assuming the global switch moved.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
