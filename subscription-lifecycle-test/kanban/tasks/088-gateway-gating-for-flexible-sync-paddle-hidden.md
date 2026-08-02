---
id: 88
title: 'Gateway gating for flexible sync: Paddle hidden from the DOM and blocked at submit, Stripe syncs to the midnight boundary'
status: todo
priority: critical
created: 2026-08-02T03:43:10.547468659+02:00
updated: 2026-08-02T03:43:21.528956959+02:00
tags:
    - renewal-sync
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 2h
depends_on:
    - 10
    - 11
    - 26
    - 23
class: standard
---

> **SLT-SYN-12** · group `sync` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-13`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
On one product whose flex-sync flag is the only variable, establish which gateways permit Flexible Renewal Sync and how unsupported ones are refused: Paddle gone from the DOM (not CSS-hidden) on a sync-eligible cart, the server validator also blocking a forced `arraysubs_paddle`, Stripe producing a synced sub on the midnight boundary.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: both (`/checkout/`, `/slt-classic-checkout`)
- Account: existing `slt-paddle`, `slt-flex2`
- Plugins: both

## Preconditions
- SLT-SETUP-01, -02 (sync OFF), -05, SLT-PROD-16 done. Buy after 12:00.
- Contract: `arraysubs_is_renewal_sync_supported_gateway()` is true for `stripe` and manual ids, false for `arraysubs_paddle`; `maybeHideUnsupportedRenewalSyncGateways()` strips them and `validateRenewalSyncGatewaySupport()`/`…StoreApi()` reject at submit.
- Creates one probe; does NOT repeat SLT-SETUP-05's `SLT Flex Daily Next Cycle` check. `maybeHide…` returns all gateways if hiding leaves zero — do not provoke that.

## Test data
| Item | Value |
|---|---|
| Probe | `SLT Flex Gateway Probe` / `slt-flex-gateway-probe`, Simple, Virtual, day/5, $20.00 |
| Flex plan (phase 2) | 3 segments active, seg1_end 1, seg2_end 3 -> `1 / 2-3 / 4-5` |
| Buyers / cards | Paddle `slt-paddle`, Stripe `slt-flex2` (distinct products, no auto-migrate); `4242…4242` |

## Steps
1. `mailpit-agent latest-id` -> `M0`. Create the probe, **Flexible Renewal Sync UNTICKED**; publish.
2. Paddle control (flex OFF): `--session guest-SLT-SYN-12a`, log in as `slt-paddle`, add the probe, open `/checkout/`, screenshot the gateway list, pay. Record ids.
3. Admin: tick **Flexible Renewal Sync to Next Billing Cycle**, all toggles ON, handles `1 / 2-3 / 4-5`, Update. Confirm `_arraysubs_flex_sync_enabled=yes`, `seg1_end 1`, `seg2_end 3`.
4. Hide probe: `--session guest-SLT-SYN-12b`, log in as `slt-flex2`, add the probe, open `/checkout/` and `eval` the payment-block radio values, then `/slt-classic-checkout` and `eval` `[...document.querySelectorAll('input[name=payment_method]')].map(i=>i.value)`. Screenshot.
5. Block probe: on the classic page `eval` the checked `payment_method` input value to `arraysubs_paddle`, fill billing, **Place order**. Screenshot; confirm at `/wp-admin/admin.php?page=wc-orders` that NO order exists.
6. Stripe positive: reload `/slt-classic-checkout`, select Stripe, pay. Dump the new sub's `_renewal_sync_*` and `_next_payment_date`; file the gateway ids in the registry.

## Expected results
1. Step 2 (flex OFF): Paddle IS offered, purchase succeeds at **$20.00**, no `_renewal_sync_*` meta, `_next_payment_date` = checkout + 5 days. Paddle works here.
2. Step 4 (flex ON), both checkouts: `arraysubs_paddle` is **absent from the DOM**, not merely hidden — the value list holds `stripe` (plus `bacs`/`cheque` if enabled), never `arraysubs_paddle`.
3. Step 5: refused with **"Renewal sync is not available for the selected payment method. Choose a supported payment method to continue."**; no order, sub or Paddle transaction. So the answer is **both** — hide and block.
4. Step 6: **$20.00**; `_renewal_sync_enabled=yes`, mode `full` (a `day` period always resolves to day 1); `_next_payment_date` = **`2026-08-11 18:00:00` UTC** = 2026-08-12 00:00 site, a midnight boundary unlike result 1's anniversary. No `Renewal Sync signup charge was … but … expected` note.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription`, `admin_new_subscription`, Woo order mail | steps 2, 6 | slt-paddle / slt-flex2 / admin | `is active`, `New subscription #` | `wait-new <prev> 180` |
| 2 | NONE, steps 3-5 | save, hide, blocked submit | — | — | `latest-id` unchanged; a failed checkout sends nothing |

## Evidence to capture
- `SLT-SYN-12-01-paddle-offered.png`, `-02-block-gateways.png`, `-03-classic-gateways.png`, `-04-forced-paddle-error.png`, `-05-stripe-meta.png`
- Eval'd gateway-id arrays verbatim; probe, sub, order ids; both `_next_payment_date`s; Mailpit ids; console errors

## Pass criteria
- [ ] Paddle offered and purchasable while flex is OFF
- [ ] `arraysubs_paddle` absent from the DOM on block and classic when flex is ON
- [ ] Forced `arraysubs_paddle` blocked with the verbatim message, zero orders
- [ ] Stripe leg `_renewal_sync_enabled=yes`, mode `full`, next payment `2026-08-11 18:00:00`
- [ ] Matrix filed (Stripe YES, manual YES, Paddle NO); no mismatch note; only the listed mails

## Isolation / teardown
- New artifacts: 1 `SLT ` product, 2 subs, 2 orders — ids to the registry for 99B. Carts emptied.
- The probe's flex meta is mutated once, at step 3, before any flex-synced sub exists on it; the step-2 Paddle sub was bought while flex was off, so its schedule is unaffected. Never re-toggle it. No global setting or gateway toggle changed.
- Handoff: Stripe leg renews 2026-08-12 midnight+spread, Paddle on its anniversary; D10/D11 watch expects both.

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
