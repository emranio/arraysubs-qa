---
id: 109
title: 'Negative sweep: cancelled gets no reminders, lifetime gets no renewal mail, expired gets no further mail'
status: todo
priority: critical
created: 2026-08-02T03:43:12.077261052+02:00
updated: 2026-08-02T03:43:23.482621854+02:00
tags:
    - email
    - day-08
    - has-conflicts
due: "2026-08-10"
estimate: 2h
depends_on:
    - 6
    - 7
    - 23
    - 68
class: standard
---

> **SLT-EML-14** · group `emails` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-CHK-13`, `SLT-SYN-09`, `SLT-SYN-13`, `SLT-SYN-12`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`medium` · contradictory-expected-result** — with `SLT-LIFE-04`, `SLT-EML-08`, `SLT-PROD-06`

- *Problem:* SLT-LIFE-04 derives from code (OrderIntegration.php:1489-1502) that SLT Fixed Three Cycles stamps _end_date at the moment of the FINAL renewal charge and flips to arraysubs-expired inside that payment - so with a D0 (2026-08-02) purchase the expiry is 2026-08-06, not the catalog's 'expires 6 days after checkout' (which LIFE-04 itself proves is unbacked - arraysubs_calculate_end_date_from_length() has zero callers). SLT-EML-08 states 'S_FIX expired 2026-08-08' and hunts for the 'has expired' message dated 08-08; SLT-EML-14 states 'Fixed Three Cycles renews 08-04 and 08-06 and expires 08-08 (_end_date)'; SLT-PROD-06's title still says 'expires on 2026-08-07' (the pre-shift anchor). Three different dates for one event; EML-08 and EML-14 will both report a missing email.
- *Required fix:* Adopt LIFE-04's code-derived model as authoritative and restate the dates everywhere for D0 = 2026-08-02: renewal #1 2026-08-04, renewal #2 2026-08-06, _end_date = the 08-06 charge moment, status arraysubs-expired 08-06, subscription_expired mail 08-06 PM (readable on watch D5, 2026-08-07). Update SLT-EML-08 step 1 to search 08-06, SLT-EML-14's dated contract, SLT-PROD-06's title and objective, and the watch schedule. LIFE-04's 'file an issue if _end_date is not the final charge moment' stays as the open question.

**`medium` · impossible-timing / single-day contention** — with `SLT-LIFE-01`, `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-10`, `SLT-DUN-05`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Prove the three standing email negatives with dated evidence: a cancelled subscription gets no renewal reminder or renewal mail after cancellation; the lifetime product gets no renewal-class mail across the 12-day watch; an expired subscription gets nothing after its single `has expired` message. Each negative names the watch days that prove it, plus a positive control so the reminder negative is not vacuous.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation only)
- Account: existing (`slt-fail`, `slt-core`, `slt-email`)
- Plugins: both

## Preconditions
- SLT-PROD-06, -07, -16 and SLT-EML-13 complete. Dated contract, D0 = 2026-08-02: Retry Daily fails 08-05 PM → on-hold 08-06 → **cancelled 08-09**; Fixed Three Cycles renews 08-04 and 08-06 and **expires 08-08** (`_end_date`).
- Code basis: reminders schedule only at `_next_payment_date − renewal_upcoming.days_before(3) + spread offset`, and only if still future (`EmailManager.php:760-794`); the handler requires status exactly `arraysubs-active` (`:806`); `RenewalScheduler::unschedule()` (`:103-107`) drops the reminder on cancellation. Not a bug, state plainly: for day/1 and day/2 products the 3-day lead exceeds the cycle, so the meaningful reminder cohort is the day/3 and week flex subscriptions.
- **No drain.** D8 is another group's time-travel day; do not run while that drain is in progress.

## Test data
| Item | Value |
|---|---|
| Cancelled | SLT Retry Daily **SR** (`slt-fail@`), cancelled 08-09 |
| Expired | SLT Fixed Three Cycles **SF** (`slt-core@`), expired 08-08 |
| Lifetime | SLT Lifetime One Time **SL** (`slt-core@`) and **H1** (`slt-email@`) |
| Control | a day/3 or week flex subscription that got `renews soon` |

## Steps
1. `wp post list --post_type=arraysubs_data --post__in=SR,SF,SL,H1 --fields=ID,post_status --allow-root`, then `wp post meta list <ID> --keys=_next_payment_date,_end_date,_pending_renewal_order_id --allow-root` on each.
2. Tools → Scheduled Actions: search each of SR, SF, SL, H1 on **Pending** and **Failed**; screenshot the empty results. Open `?page=wc-orders`, filter by each account, record the newest order date.
3. `mailpit-agent list 200` — the source list for steps 4–6.
4. **Cancelled negative.** Extract every message referencing `#SR` or To `slt-fail@` with timestamps; mark the 08-09 cancellation moment and classify each.
5. **Positive control.** Locate at least one `renews soon` for a flex day/3 or week subscription; record its id, subject and subscription id. Read-only.
6. **Lifetime and expired negatives.** Extract every message referencing `#SL`, `#H1`, `#SF` with timestamps; mark SF's 08-08 expiry. Re-read `SL._next_payment_date`/`_end_date` and `SF._next_payment_date` vs `_end_date` for residue.
7. Write the reconciliation table (id, timestamp, subject, To, fixture, verdict) to `/home/server-manager/slt-evidence/SLT-EML-14-sweep.md`.
8. Declare the follow-up watch days in the notes and the registry: SR → **D8 (08-10)** … **D12 (08-14)**; SF → **D7 (08-09)** carries the `has expired` mail, **D8–D12** prove silence; SL → **every** watch day **D1 (08-03)** … **D12 (08-14)**; H1 → **D2 (08-04)** … **D12**.
9. Re-run steps 3–6 as a delta on the morning of **08-14 (watch D12)**, before SLT-SETUP-99B; append it to the same file.

## Expected results
1. SR is `arraysubs-cancelled` with zero pending and zero failed action rows, exactly three lifecycle messages, and **zero** `renews soon`.
2. No message of any kind for SR after its 08-09 cancellation, through 08-14.
3. At least one `renews soon` exists in the window for a longer-cycle subscription, proving the pipeline is live and SR's zero is real.
4. SL has empty `_next_payment_date`/`_end_date`, no pending or failed action, no order beyond its 08-02 parent and zero renewal-class mail across watch D1–D12; H1 matches exactly what SLT-EML-11/12/13 declared.
5. SF received exactly one `has expired` on 08-08 and nothing after; zero `is ending soon` anywhere; the 08-14 delta adds nothing for any fixture.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED — renewal_reminder for SR | whole window | — | — | zero `renews soon` for `#SR` |
| 2 | NONE EXPECTED — anything for SR | after 08-09 | — | — | 08-14 delta yields no new SR message |
| 3 | NONE EXPECTED — renewal mail for SL/H1; expiring_soon for anything | watch D1 … D12 | — | — | zero `renews soon`, `Invoice for subscription`, `Payment received`, `is ending soon` |
| 4 | NONE EXPECTED — anything for SF | after 08-08 | — | — | 08-14 delta yields no new SF message |
| 5 | Reference only, already sent | 08-05/-06/-09 SR, 08-08 SF | slt-fail@ / slt-core@ | `Payment failed`, `is on hold`, `has been cancelled`, `has expired` | `show <id>` |

## Evidence to capture
- `SLT-EML-14-sweep.md` with the full table plus the 08-14 delta.
- Screenshots `-01-statuses.png`, `-02-actions-SR.png`, `-03-actions-SL.png`, `-04-actions-SF.png`, `-05-orders.png`, `-06-mailpit-SR.png`, `-07-mailpit-SF.png`; IDs SR/SF/SL/H1; every Mailpit id per fixture with timestamp and To; the control `renews soon` id.

## Pass criteria
- [ ] SR cancelled, no pending/failed actions, zero `renews soon` ever
- [ ] Zero mail for SR after 08-09, re-verified 08-14
- [ ] Positive control `renews soon` located, so the negative is non-vacuous
- [ ] SL inert and mail-silent across watch D1–D12; H1 matches its declarations
- [ ] SF has one `has expired` and nothing after; zero `expiring_soon` anywhere
- [ ] Watch days recorded in the registry and the 08-14 delta appended

## Isolation / teardown
- Fully read-only: no setting changed, no status edited, no order or subscription touched, no drain.
- Handed on: the negative-assertion table is the standing check for watch days D8–D12. SLT-SETUP-99B must not run on 08-14 until step 9's delta is captured — its cancellation mail would contaminate the silence proof. Restores: nothing.


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
