---
id: 14
title: 'Segment 1 full: prove the full recurring charge now and the exact next-cycle boundary date'
status: todo
priority: critical
created: 2026-08-02T03:43:04.154415714+02:00
updated: 2026-08-02T03:43:14.082675206+02:00
tags:
    - renewal-sync
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 1.5h
depends_on:
    - 10
    - 11
    - 12
    - 8
    - 13
class: standard
---

> **SLT-SYN-05** · group `sync` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / audit-before-purchase vs segment window** — with `SLT-SYN-01`, `SLT-PROD-13`, `SLT-PROD-12`, `SLT-PROD-14`

- *Problem:* The +1 date shift (audit C19) breaks audit C10's fix. start_of_week=6, so the week cycle is Sat 2026-08-01 -> Sat 2026-08-08 and boundaries [2,5] give seg1 = days 1-2, seg2 = days 3-5, seg3 = days 6-7. SLT-SYN-05 needs day-in-cycle 1 or 2, i.e. 08-01 or 08-02 only. D0 is now 08-02, so SYN-05 is HARD-PINNED to D0. But C10's fix moved the week seg-1 purchase to D1 ('it stays in segment 1') - which was true when D0=08-01 and D1=08-02, and is false now: D1=08-03 is day 3 = segment 2 = prorate, which would charge $6.00 instead of $14.00 and destroy SYN-05, SYN-06's 'identical boundary' proof and SYN-09's 'second charge full' headline. Meanwhile C10 still requires SLT-SYN-01's meta surgery on the week product to precede that purchase, and SYN-01 is a D1 task.
- *Required fix:* Split SLT-SYN-01 into two passes. SLT-SYN-01A runs D0 morning against SLT Flex Week Segments only (created by SLT-PROD-13, also D0), completes its restore and posts the 'purchase-authorised configuration' dump to the registry before SLT-SYN-05 buys after 12:00 the same day. SLT-SYN-01B runs D1 morning against SLT Flex Month Segments (PROD-12) and the two daily flex products (PROD-14), before SLT-SYN-08's D1 afternoon purchases and before SLT-SYN-06's D2 month purchase. Neither pass may touch a product that already carries a live subscription; add an explicit gate step to 01A/01B re-dumping the six _arraysubs_flex_sync_* metas as the purchase authorisation.

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Prove segment-1 `full` mode on a live Stripe checkout: the first charge is the FULL recurring amount (ratio 1.0, gateway minimum forced 0.0, `Hooks.php:389-392`), the boundary is not rewritten, and `_next_payment_date` is the upcoming week boundary — SLT-SYN-07's anchor.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-flex`) — ALSO CREATES two accounts
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-13 (`SLT Flex Week Segments`, week/1 $14.00, seg1_end=2, seg2_end=5, all active) done.
- SLT-SYN-01 done with restores proven; no flex meta surgery afterwards. Global `sync_to_billing_cycle` OFF — the product syncs only via `filterSupportsRenewalSync()`. Never run in SLT-SYN-04's bracket.
- **Also creates** `slt-flex2` + `slt-flex3` (`slt-flexN@example.test`, `customer`, pw `SltQa!2026#Pass`, notification unticked, billing as SLT-SETUP-03 step 4) — plan-audit #10: `auto_migrate_on_checkout=true` makes a rebuy migrate. Binding: seg1=`slt-flex`, seg2=`slt-flex2`, seg3=`slt-flex3`.

## Test data
| Item | Value |
|---|---|
| Product / buyer / card | `SLT Flex Week Segments` $14.00 week/1 / `slt-flex` / `4242 4242 4242 4242` |
| Buy on | D0 2026-08-02, after SLT-SYN-01 closes |
| Cycle start | Sat 2026-08-01 00:00 +06 = `2026-07-31 18:00:00` UTC (`start_of_week`=6) |
| Day in cycle | 2 → boundaries [2,5] → **segment 1** → `full` |
| Charge / next payment | **$14.00** / 2026-08-08 00:00 +06 = **`2026-08-07 18:00:00`** UTC |

## Steps
1. `mailpit-agent latest-id` → `MAILID_A`. As `--session admin` create both users at `wp-admin/user-new.php`; set billing at `user-edit.php?user_id=<ID>`.
2. From WP root dump the six `_arraysubs_flex_sync_*` keys + `_regular_price` for `<WEEK_ID>` to `slt-evidence/SLT-SYN-05-plan.csv`; abort unless `yes,2,5,yes,yes,yes,14.00`.
3. `agent-browser --session slt05cust open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i` → log in as `slt-flex`; `latest-id` → `MAILID_B`.
4. `open ".../checkout/?add-to-cart=<WEEK_ID>"` → `snapshot -i`. Screenshot `SLT-SYN-05-01-summary.png`; record the verbatim total-due-today; confirm NO "Today's payment covers the full billing cycle starting …" note (seg 3 only).
5. Confirm **Paddle** absent / **Stripe** present; click the Stripe radio explicitly, then re-read totals (gateway change re-prices). Screenshot `SLT-SYN-05-02-gateways.png`.
6. Pay; screenshot `SLT-SYN-05-03-received.png`; record `ORDER_ID`; `mailpit-agent wait-new $MAILID_B 120 "is active"`, then `… "New subscription #"`.
7. Get `SUBID` from `edit.php?post_type=arraysubs_data`; screenshot `SLT-SYN-05-04-schedule.png`; dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `SLT-SYN-05-sub-meta.csv`; screenshot the order item mirror `SLT-SYN-05-05-item-meta.png`.
8. Compute `k` (README crc32 one-liner); screenshot `page=action-scheduler&status=pending&s=<SUBID>` as `SLT-SYN-05-06-pending.png`.

## Expected results
1. `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-07-31 18:00:00` (NOT rewritten); `_renewal_sync_initial_recurring_amount=14`; order total exactly `$14.00`, no tax line.
2. `_renewal_sync_first_full_renewal_date = _next_payment_date = 2026-08-07 18:00:00`.
3. `_completed_payments=1`; sub `arraysubs-active`; order `processing`/`completed`; no bonus note; Paddle absent.
4. Pending: `arraysubs_generate_renewal_invoice[<SUBID>]` at `2026-08-07 18:00:00 +k −6h`; `arraysubs_process_renewal[<SUBID>]` at `+k`; `arraysubs_send_renewal_reminder[<SUBID>,3]` at `2026-08-04 18:00:00 +k` — assert windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | **Add New User** ×2 | — | — | `latest-id` must still equal `MAILID_A` |
| 2 | `new_subscription` | order paid | slt-flex@example.test | `is active` | `wait-new $MAILID_B 120` |
| 3 | `admin_new_subscription` | order paid | admin_email | `New subscription #` | same |
| 4 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = finding |

## Evidence to capture
- `SLT-SYN-05-01..06`; `ORDER_ID`, `SUBID`, `k`, user IDs; both CSVs; Mailpit ids; console errors.

## Pass criteria
- [ ] Charge exactly $14.00, mode `full`, cycle start `2026-07-31 18:00:00`
- [ ] `_next_payment_date` = `2026-08-07 18:00:00` = `_renewal_sync_first_full_renewal_date`
- [ ] No seg-3 note; Paddle hidden, Stripe offered
- [ ] Three scheduled actions at due+k; both created-mails arrived; no `renewal_invoice`
- [ ] `slt-flex2`/`slt-flex3` created, zero mail

## Isolation / teardown
- Handed on: `SUBID` renews for real 2026-08-08 (SLT-SYN-09 owns it); `2026-08-08 00:00 +06` is SLT-SYN-07's anchor; `slt-flex2`/`slt-flex3` go to SLT-SYN-06/07/08. `slt-flex` never rebuys it.
- Restores: none. Close `slt05cust`; SLT-SETUP-99 deletes users, order, subscription.


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
