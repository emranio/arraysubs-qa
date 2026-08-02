---
id: 46
title: 'Variation-level flexible sync: prove the purchased variation''s segment plan wins over a parent decoy'
status: todo
priority: high
created: 2026-08-02T03:43:06.771934046+02:00
updated: 2026-08-02T03:43:17.334034319+02:00
tags:
    - renewal-sync
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h30m
depends_on:
    - 40
    - 44
    - 11
    - 12
class: standard
---

> **SLT-SYN-13** · group `sync` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-12`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`medium` · shared-product-meta / undeclared bracket** — with `SLT-SYN-02`, `SLT-PROD-15`, `SLT-MYA-05`

- *Problem:* SLT-SYN-13 step 2 writes a decoy segment plan onto the SLT Flex Variable Daily PARENT product and deletes it only at step 7, the same day - but between those steps two live checkouts are placed and the window is unbounded in the body. SLT-SYN-02 audits the same product family on the same day (D2). Any other cart or checkout touching that parent inside the decoy window resolves filterRenewalSyncContext() against a plan no task expects, and the decoy's own null-vs-config proof depends on nothing else having read it. Separately SLT-MYA-05 leaves two appended members_access rules and a product-level _arraysubs_features meta live from D2 morning until its step-10 teardown on D7 - a five-day global deviation during which the pre-existing 'Gold members save 15%' rule (which targets pro_member on ALL products) can alter front-end prices for slt-fail.
- *Required fix:* For SYN-13: declare the decoy a bracket - record open/close UTC in slt-evidence/SLT-SYN-13-decoy-bracket.txt, post it to the registry, keep it under 90 minutes, and assert no other SLT task carts or checks out SLT Flex Variable Daily inside it. Add a pass criterion 'decoy removed and getConfig(<PARENT>) is null before the bracket closes'. For MYA-05: shorten the deviation by moving its teardown from D7 to immediately after follow-up B (D5 morning, once the on-hold role removal is captured) and re-adding the rules only if follow-up C needs them; record the bracket in the registry either way, and add an explicit price check on SLT Retry Daily renewals proving the pro_member discount never reached a cron renewal.

---
## Objective
Prove `filterRenewalSyncContext()` resolves the segment plan from the PURCHASED VARIATION, not the parent. Two variations of `SLT Flex Variable Daily` share one day/3 $12.00 schedule and differ only in segment plan; a decoy plan force-written onto the parent would collapse both to `next_cycle` if the parent won.

## Scope
- Gateway: Stripe test
- Checkout: block (`/checkout/`)
- Account: existing `slt-flex`, `slt-flex3`
- Plugins: pro-required

## Preconditions
- SLT-PROD-15 created parent `<PARENT>` and variations `<V_FULL>` (3 active, seg1_end 1, seg2_end 2), `<V_NEXT>` (segment 3 only), `<V_NOSYNC>` (flex off). Quote SLT-SYN-02's authorised dump in evidence.
- This task OWNS both `SLT Flex Variable Daily` purchases; no other may buy this parent. Both run **after 12:00 site on 2026-08-04**; the decoy must be gone before day end.
- Per plan-audit fix `<V_NOSYNC>` is NOT purchased — asserted by `getConfig() === null`.

## Test data
| Item | Value |
|---|---|
| Product | `SLT Flex Variable Daily`, attribute **SLT Sync Mode**, variations day/3 $12.00 |
| Parent decoy | `_arraysubs_flex_sync_enabled=yes`, seg1/seg2 `_active` `no`, seg3 `yes`, seg1_end 1, seg2_end 2 |
| Buy date / card | 2026-08-04 (cycle_start 08-04 00:00 site, day-in-cycle 1 for both) / `4242…4242` |

## Steps
1. `mailpit-agent latest-id` -> `M0`. Re-dump the six flex metas on all three variations; check against SLT-SYN-02's dump.
2. Write the decoy on the PARENT only: `wp post meta update <PARENT> _arraysubs_flex_sync_enabled yes` + the four values above. Touch no variation.
3. `wp eval 'use ArraySubsPro\…\SegmentPlan as S; foreach([<PARENT>,<V_FULL>,<V_NEXT>,<V_NOSYNC>] as $id) var_dump($id,S::getConfig($id));' --allow-root`.
4. `--session guest-SLT-SYN-13a`, log in as `slt-flex`, pick **SLT Sync Mode = Full**, add to cart, screenshot the subscription meta rows, open `/checkout/`, pay. Empty the cart.
5. `--session guest-SLT-SYN-13b`, log in as `slt-flex3`, repeat with **Next Cycle**, screenshotting the cart note. Empty the cart.
6. Dump both subs: `_renewal_sync_enabled`, `…_first_charge_mode`, `…_cycle_start_date`, `_next_payment_date`, `_variation_id`.
7. Remove the decoy (`wp post meta delete <PARENT> _arraysubs_flex_sync_enabled` + the 4 segment keys); re-run step 3, confirm `getConfig(<PARENT>)` null.
8. Follow-up on the 08-07 and 08-10 watch days: each renewal fired in `[due, due+offset+10min]`, offset `crc32('arraysubs-spread-'.$id) % 21600`.

## Expected results
1. `getConfig()`: `<V_FULL>` actives `[1,2,3]`, boundaries `[1,2]`, cycle_days 3; `<V_NEXT>` actives `[3]`, boundaries `[]`; `<V_NOSYNC>` **null**; `<PARENT>` the decoy — present, never used.
2. **Full**: **$12.00**, mode `full`, `_next_payment_date` **`2026-08-06 18:00:00` UTC** = 2026-08-07 00:00 site; cart shows no bonus-access note.
3. **Next Cycle**: **$12.00**, mode `next_cycle`, `_renewal_sync_cycle_start_date` rewritten to `2026-08-06 18:00:00` UTC, `_next_payment_date` **`2026-08-09 18:00:00` UTC** = 2026-08-10 00:00 site; cart reads `Today's payment covers the full billing cycle starting 7 August, 2026`.
4. The dates differ by exactly one 3-day cycle. **If both read 2026-08-10 the decoy won and `product_id` resolves to the parent — file a defect against `filterRenewalSyncContext()`.**
5. Each sub's `_variation_id` matches the variation bought; `<V_NOSYNC>` is never purchased.
6. After step 7 the parent has no flex meta and `getConfig(<PARENT>)` is null; both renewals then fire unattended at midnight + their own offset, each $12.00.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription`, `admin_new_subscription`, Woo order mail | steps 4-5 | slt-flex / slt-flex3 / admin | `is active`, `New subscription #` | `wait-new <prev> 180` |
| 2 | `payment_successful` | 08-07, 08-10 renewals | each buyer | `Payment received for subscription #` | watch `mailpit-agent list 50` |
| 3 | NONE, steps 1-3, 6-7 | meta work | — | — | `latest-id` unchanged |

## Evidence to capture
- `SLT-SYN-13-01-cart-full.png`, `-02-cart-next-cycle-note.png`, `-03-next-payment-dates.png`.
- Step 3 and 7 `getConfig` transcripts; sub and order ids; both offsets; Mailpit ids.

## Pass criteria
- [ ] `getConfig()` differs per variation, null for No Sync
- [ ] Full -> `2026-08-06 18:00:00` UTC, mode `full`, $12.00
- [ ] Next Cycle -> `2026-08-09 18:00:00` UTC, mode `next_cycle`, $12.00, note shown
- [ ] The dates diverge, so variation resolution beat the parent decoy; decoy then removed and `getConfig(<PARENT>)` null; both renewals fire in window

## Isolation / teardown
- The decoy is written in step 2 and MUST be deleted in step 7 the same day; record both dumps. No variation meta touched.
- New artifacts: 2 subs, 2 orders — ids to the registry for 99B. Separate guest sessions so carts cannot collide.
- Handoff: Full renews 08-07 then 08-10, Next Cycle 08-10; the watch must expect that cadence.

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
