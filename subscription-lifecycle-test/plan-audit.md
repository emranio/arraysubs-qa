# Plan audit — 44 conflicts on a shared staging site

> Two audit passes ran over this plan: one across the Day-0 foundation set, one across the full
> 115-task plan. Together they found 44 concrete ways these tasks would corrupt each other when run
> against a single shared site. **Every one has a fix, applied in the calendar and in the affected
> task bodies.** This file is the record of why the plan is shaped the way it is.

> **Historical record only — do not execute the `Problem` or `Fix` prose below.** Dates and task shapes
> inside individual audit entries describe earlier drafts. The authoritative final instructions are the
> current files under `kanban/tasks/`, ordered by `calendar.md` and `watch-schedule.md`. In particular,
> the all-in-one `SLT-SETUP-99` card is retired; only `SLT-SETUP-99A` on 2026-08-12 and
> `SLT-SETUP-99B` on 2026-08-15 may run.

The first pass's severity field was not populated, so those entries show as `unrated` — they are not
less important, they were simply produced by an earlier schema.

## Standing rules these conflicts produced

| Rule | Why |
|---|---|
| Never drain Action Scheduler by hook or group; run only one recorded action ID at a time | A broad drain fires other tests and pre-existing subscriptions early and destroys their evidence |
| One `agent-browser --session <TASK-KEY>` per task | Sessions are keyed by name and share a cart; ten tasks shared one guest cart in the draft |
| A global setting changed outside the D0 baseline is restored in the same task | Otherwise the next task silently runs against the wrong configuration |
| No account rebuys a product it already subscribes to | With `one_per_customer=false`, the enabled `auto_migrate_on_checkout` path is inert; a repeat creates a duplicate subscription and makes the named control fixture ambiguous |
| Teardown never runs before the watch window closes | `SLT-SETUP-99` deleted all SLT data on D10 while the watch ran to D12 |

## Conflicts (44)

### C01 · `critical` · duplicate-purchase / control-spine destruction

**Tasks:** `SLT-CHK-01`, `SLT-REN-01`

**Problem.** Both are tagged d0 and both place the SAME purchase: slt-core buys SLT Daily Core on the block checkout with Stripe 4242. CHK-01 step 3-6 and REN-01 step 3-4 are the same checkout. With multiple_subscriptions.auto_migrate_on_checkout=true (frozen baseline) the second checkout does not create a second subscription - CheckoutMigrationTrait migrates the existing one, rewriting _product_id/_recurring_amount and re-anchoring the schedule. That destroys the reference record (CHK-01's meta baseline for CHK-02's field-by-field diff) AND the day/1 renewal spine that REN-02, EML-02, EML-03, EML-05, EML-15, MYA-02, ADM-02, ADM-06 and the whole D1-D12 watch depend on. CHK-01's own precondition ('slt-core owns no SLT Daily Core sub and must never rebuy it - C08') is violated by REN-01.

**Fix.** Merge into one owner. SLT-CHK-01 is the sole purchaser and must execute inside REN-01's clock window (13:00-13:30 site, 2026-08-02) so both tasks' timing constraints are satisfied. SLT-REN-01 drops steps 1-5 and becomes an observation leg attached to CHK-01's SUB/ORDER: it keeps steps 6-11 (cron-not-CLI proof, AS leg timestamps, wp_actionscheduler_logs 'via WP Cron' assertion, D1/D2 follow-ups). Publish SUB_CORE=S1 and k to the registry from CHK-01. Add a hard precondition to REN-01: 'places no order'.

### C02 · `critical` · duplicate-purchase / same-account collision

**Tasks:** `SLT-CHK-05`, `SLT-REN-05`

**Problem.** Both tasks declare 'this task CREATES slt-sca / slt-sca@example.test (must not pre-exist)' and both buy SLT Daily Core with the 3DS card 4000 0027 6000 3184, then both assert the same off-session requires_action renewal, the same five _arraysubs_payment_action_* metas, the same renewal_requires_verification email and the same verification-link completion. CHK-05 is d2, REN-05 is d3. Whichever runs second aborts on its own 'no slt-sca user may exist' precondition, or - worse - proceeds and migrates CHK-05's subscription (auto_migrate_on_checkout=true, same account + same product), destroying the pending requires_action order that is the entire point of the test.

**Fix.** Merge. Keep SLT-CHK-05 on D2 as the sole purchaser (its 'no later than D2' constraint is the binding one - the renewal must fire, be verified, and still beat the on-hold sweep at due+24h). Fold REN-05's stronger assertions into it: the wc-stripe-confirmation=1 URL shape, the 200-with-pay-form check, the '_next_payment_date recomputed from _renewal_scheduled_date, not payment time' assertion, and the on-hold-if-late escape clause. Retire the SLT-REN-05 key or repoint it to 'verification leg of SLT-CHK-05, D3 morning'.

### C03 · `critical` · impossible-timing / cross-group date contradiction

**Tasks:** `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`, `SLT-EML-14`, `SLT-ADM-09`, `SLT-MYA-05`

**Problem.** SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.

**Fix.** DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

### C04 · `critical` · dependency-inversion / date contradiction

**Tasks:** `SLT-SYN-08`, `SLT-PROD-14`, `SLT-SYN-01`, `SLT-EML-01`, `SLT-SYN-09`

**Problem.** SLT-SYN-08 is tagged d0 and buys SLT Flex Daily Two Seg + SLT Flex Daily Next Cycle, but SLT-PROD-14 creates those products on D1 in the corrected calendar and audit C10 forbids purchasing a flex product before SLT-SYN-01's destructive meta surgery has run and been restored. Worse, SYN-08's stated dates encode a D0 purchase (cycle_start 2026-08-01 18:00 UTC, Two Seg next payment 08-04 18:00 UTC) while SLT-EML-01 - which owns the only reachable renewal_reminder in the window - encodes a D1 purchase (SUB_2SEG due 2026-08-06 00:00 site, SUB_NC due 2026-08-09 00:00 site, reminder fires 08-06 00:00-06:00 = watch D4). Both cannot be true and neither product can be bought twice by the same account.

**Fix.** SLT-SYN-08 moves to D1 (2026-08-03), purchases after 12:00, strictly after SLT-PROD-14 and after SLT-SYN-01B's restore is proven. That makes EML-01's numbers correct as written (SUB_2SEG due 08-06 00:00 site, SUB_NC due 08-09 00:00 site, reminder 08-06 00:00-06:00 site, watch D4) and SYN-08's own Test data must be recomputed to cycle_start 2026-08-02 18:00 UTC, Two Seg next payment 2026-08-05 18:00 UTC, Next Cycle cycle_start rewritten to 2026-08-05 18:00 UTC and next payment 2026-08-08 18:00 UTC. Knock-on: SLT-SYN-09's SUB_A row is now wrong (it assumes #2 at 08-04 18:00 and #3 at 08-07 18:00). Move SLT-SYN-09 from D6 to D7 (2026-08-09 morning) where the week pair's 08-08 00:00 renewals AND SUB_A's #2 at 08-09 00:00 are both already visible; hand SUB_A's #3 (08-12 00:00) to watch D10 as a grid assertion.

### C05 · `critical` · impossible-timing / audit-before-purchase vs segment window

**Tasks:** `SLT-SYN-05`, `SLT-SYN-01`, `SLT-PROD-13`, `SLT-PROD-12`, `SLT-PROD-14`

**Problem.** The +1 date shift (audit C19) breaks audit C10's fix. start_of_week=6, so the week cycle is Sat 2026-08-01 -> Sat 2026-08-08 and boundaries [2,5] give seg1 = days 1-2, seg2 = days 3-5, seg3 = days 6-7. SLT-SYN-05 needs day-in-cycle 1 or 2, i.e. 08-01 or 08-02 only. D0 is now 08-02, so SYN-05 is HARD-PINNED to D0. But C10's fix moved the week seg-1 purchase to D1 ('it stays in segment 1') - which was true when D0=08-01 and D1=08-02, and is false now: D1=08-03 is day 3 = segment 2 = prorate, which would charge $6.00 instead of $14.00 and destroy SYN-05, SYN-06's 'identical boundary' proof and SYN-09's 'second charge full' headline. Meanwhile C10 still requires SLT-SYN-01's meta surgery on the week product to precede that purchase, and SYN-01 is a D1 task.

**Fix.** Split SLT-SYN-01 into two passes. SLT-SYN-01A runs D0 morning against SLT Flex Week Segments only (created by SLT-PROD-13, also D0), completes its restore and posts the 'purchase-authorised configuration' dump to the registry before SLT-SYN-05 buys after 12:00 the same day. SLT-SYN-01B runs D1 morning against SLT Flex Month Segments (PROD-12) and the two daily flex products (PROD-14), before SLT-SYN-08's D1 afternoon purchases and before SLT-SYN-06's D2 month purchase. Neither pass may touch a product that already carries a live subscription; add an explicit gate step to 01A/01B re-dumping the six _arraysubs_flex_sync_* metas as the purchase authorisation.

### C06 · `critical` · shared-global-setting / undeclared exclusive bracket

**Tasks:** `SLT-EML-12`, `SLT-CHK-09`, `SLT-CPN-04`, `SLT-SYN-14`, `SLT-CHK-05`, `SLT-ADM-05`, `SLT-EML-06`

**Problem.** SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.

**Fix.** Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to /home/server-manager/slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

### C07 · `critical` · evidence-destruction / teardown vs watch window

**Tasks:** `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-13`, `SLT-SYN-12`, `SLT-SYN-04`

**Problem.** SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.

**Fix.** Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

### C08 · `high` · same-account-collision / duplicate account creation

**Tasks:** `SLT-SW-04`, `SLT-SW-06`, `SLT-SW-08`

**Problem.** SLT-SW-06 (d5) states 'this task also creates slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it, then upgrades to Pro. SLT-SW-04 (d7) states 'this task CREATES slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it again. SLT-SW-08 (d7) then operates on 'slt-switch2, on SLT Plan Pro from SLT-SW-06'. SW-04 either aborts on a duplicate user, or buys SLT Plan Basic a second time on an account that already holds a Pro subscription from the same ladder - and with auto_migrate_on_checkout the checkout-migration ladder in CheckoutMigrationTrait becomes reachable, silently converting SW-08's Pro subscription instead of creating SW-04's Basic one.

**Fix.** SLT-SW-04 creates and uses a distinct account, slt-switch4 / slt-switch4@example.test (Customer, SltQa!2026#Pass, SETUP-03 step 4 billing), registered for 99B deletion. SLT-SW-06 remains the sole creator of slt-switch2 and the sole owner of its subscription; SLT-SW-08 continues to inherit it. Add to SW-04's preconditions: 'slt-switch2 belongs to SLT-SW-06/SLT-SW-08 and must not be reused'.

### C09 · `high` · shared-global-setting / same-day bracket collision

**Tasks:** `SLT-SW-08`, `SLT-SW-04`, `SLT-SW-02`, `SLT-ADM-01`, `SLT-MYA-04`, `SLT-DUN-05`

**Problem.** SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.

**Fix.** Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

### C10 · `high` · shared-global-setting / multi-day deviation vs frozen baseline

**Tasks:** `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-07`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-03`, `SLT-MYA-04`, `SLT-ADM-03`

**Problem.** SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.

**Fix.** Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D6 negative) and the shifted $20.00 charge lands 08-09 PM (watch D7) - which also clears 2026-08-10 for SLT-LIFE-01.

### C11 · `high` · same-subscription collision / duplicate coverage

**Tasks:** `SLT-EML-08`, `SLT-SW-02`, `SLT-SW-03`, `SLT-SW-01`, `SLT-PROD-11`

**Problem.** Both tasks run on D8 and both drive the on_expire auto-downgrade of a slt-switch plan-ladder subscription. SLT-EML-08 step 5 sets _end_date on 'S_PRO - slt-switch's active Pro subscription' and fires arraysubs_expire_subscription to capture the auto_downgrade email and the expired-suppression negative. SLT-SW-02 Leg B does exactly the same on 'S-BASIC (on Pro since SLT-SW-01)'. There are only two slt-switch ladder subscriptions and SLT-SW-03 (d6) already crossgraded the other one (S-PRO) off Pro onto SLT Plan Peer - at which point Pro's _arraysubs_auto_downgrade_product no longer applies to it and EML-08's leg is unrunnable as written. Whichever task expires the remaining Pro subscription first consumes the other's canvas.

**Fix.** Single owner: SLT-SW-02 Leg B owns the hand-set _end_date and the expiry of S-BASIC (which SLT-SW-01 left on SLT Plan Pro). SLT-EML-08 becomes observation-only for that leg - it reads the auto_downgrade mail ('has been changed to SLT Plan Basic'), proves the subscription_expired suppression negative (EmailManager.php:317-322) and confirms S-BASIC re-activated on Basic at $5.00 - and runs strictly after SW-02 in the D8 order. Delete EML-08 steps 4-5 (queue screenshot + _end_date write) and replace with 'quote SLT-SW-02's pre-flight queue screenshot and _end_date timestamp'. Update EML-08's Test data to name S-BASIC, not S_PRO.

### C12 · `high` · session/cart collision (persistent cart)

**Tasks:** `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`, `SLT-ADM-02`, `SLT-CHK-15`, `SLT-EML-09`, `SLT-SYN-05`, `SLT-SYN-13`

**Problem.** Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.

**Fix.** Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

### C13 · `high` · session collision (shared admin session)

**Tasks:** `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`, `SLT-EML-13`, `SLT-LIFE-03`, `SLT-SYN-05`, `SLT-SYN-09`, `SLT-SYN-10`, `SLT-SYN-12`, `SLT-SW-06`, `SLT-SW-07`, `SLT-SW-08`, `SLT-SW-09`, `SLT-SW-10`, `SLT-ADM-06`, `SLT-ADM-09`, `SLT-ADM-10`, `SLT-IMP-02`, `SLT-IMP-03`, `SLT-IMP-05`, `SLT-MYA-05`

**Problem.** More than twenty tasks originally opened one shared bare admin session, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task closing that shared session logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.

**Resolved rule.** Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is never permitted because unrelated browser work may be active. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

### C14 · `high` · shared-global-setting / undeclared exclusive bracket

**Tasks:** `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`, `SLT-IMP-03`, `SLT-ADM-03`, `SLT-ADM-05`

**Problem.** SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.

**Fix.** Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

### C15 · `high` · dependency-gap / unowned purchases

**Tasks:** `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`, `SLT-EML-08`, `SLT-SYN-10`, `SLT-SYN-07`

**Problem.** Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 forced renewals for the live month segment-2 cohort and week segment-3 cohort have no dedicated owner. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.

**Fix.** Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add a D6 purchase leg to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments after 12:00 - day-in-cycle 8, segment 3, next payment 2026-10-01 site'. Make SLT-TT-00 the D8 pre-flight and the exclusive owner of the live month segment-2 and week segment-3 invoice/charge pairs, one exact action ID at a time. Keep month segment 3 in SLT-SYN-10 and variable-flex on its natural schedule. Every later D8 task quotes the shared non-SLT snapshot.

### C16 · `high` · same-subscription collision / ambiguous target

**Tasks:** `SLT-LIFE-02`, `SLT-EML-05`, `SLT-EML-02`, `SLT-EML-15`, `SLT-MYA-02`

**Problem.** SLT-LIFE-02 (d6) targets 'S1 - a live arraysubs-active SLT Daily Core subscription from the SLT-CHK-* run' without naming it, and its arithmetic uses $10.00 day/1, which describes SUB_CORE (slt-core, the control spine). It consumes one cycle by paying it early, replaces both legs and shifts the anniversary. SLT-EML-05 runs on the SAME day (d6) and also consumes one SUB_CORE cycle by setting _auto_renew=off and paying the invoice manually. Two tasks eating the same cycle on the same day makes both results unreadable, and either one silently invalidates the D1-D12 watch's 'SLT Daily Core renews $10.00 unattended every afternoon' baseline that REN-01/REN-02/EML-15/ADM-06 established.

**Fix.** Pin SLT-LIFE-02's S1 to SLT-CHK-02's subscription (slt-core2 + SLT Daily Core, day/1, $10.00, Stripe, saved token, unsynced, no pending skip) - structurally identical to the spine and claimed by nothing else after D0. Name the subscription id explicitly in LIFE-02's Test data and preconditions, and keep its step 8 registry note ('slt-core2's cycle N was paid early on 2026-08-08') so the watch does not read the missing unattended renewal as a failure. Leave SUB_CORE to EML-05 on D6. Add a standing registry section 'control-spine reservations' naming SUB_CORE's owning tasks per day.

### C17 · `high` · dependency-inversion (product creation after first consumer)

**Tasks:** `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`, `SLT-PROD-15`, `SLT-PROD-16`, `SLT-SETUP-04`, `SLT-SETUP-05`

**Problem.** The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.

**Fix.** Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

### C18 · `unrated` · shared-global-setting

**Tasks:** `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-PROD-15`, `SLT-PROD-16`, `SLT-PROD-01`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`

**Problem.** renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.

**Fix.** Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-05) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

### C19 · `unrated` · impossible-timing

**Tasks:** `SLT-SYN-04`, `SLT-PROD-01`, `SLT-PROD-16`, `SLT-PROD-14`, `SLT-PROD-06`

**Problem.** SLT-SYN-04's global-sync-ON window is not just a checkout hazard: any renewal that Action Scheduler processes while the switch is ON can pick up sync context and be re-anchored from its checkout anniversary to the site-local midnight boundary. By the time SLT-SYN-04 can realistically run (after SETUP-01/02/PROD-16/SETUP-05/SYN-03 have completed), several day/1 and day/2 subscriptions bought on D0/D1 already have renewals due, and their anniversary times are whatever clock time those checkouts happened. If a checkout was done at 09:30 site on D0, its renewal fires at 09:30 site the next day - inside a morning ON window.

**Fix.** Two-part rule. (1) Every SLT purchase on D0, D1 and D2 must be executed AFTER 12:00 site time, so all anniversary renewals land in the afternoon. (2) SLT-SYN-04's ON bracket is fixed at 09:00-11:00 site on D3 and no `wp action-scheduler run` of any kind may be issued during it. Record the exact UTC open/close timestamps of the bracket in the evidence root as SLT-SYN-04-bracket.txt so any anomalous renewal in that interval can be attributed.

### C20 · `unrated` · dependency-inversion

**Tasks:** `SLT-SETUP-05`, `SLT-PROD-14`

**Problem.** SLT-SETUP-05 declares deps SLT-SETUP-02,SLT-PROD-16 but its step 7, expected result 4 and pass criterion 'Paddle hidden for SLT Flex Daily Next Cycle' all require the product SLT Flex Daily Next Cycle, which is created only by SLT-PROD-14. Run as authored (both on d0, no ordering edge) SLT-SETUP-05 can start before that product exists and its third gateway probe is unrunnable.

**Fix.** Add SLT-PROD-14 to SLT-SETUP-05's dependency list (deps become SLT-SETUP-02, SLT-PROD-16, SLT-PROD-14) and schedule both on D1 with SLT-PROD-14 strictly before SLT-SETUP-05.

### C21 · `unrated` · dependency-inversion

**Tasks:** `SLT-SYN-04`, `SLT-SYN-03`, `SLT-SYN-01`, `SLT-SYN-02`

**Problem.** Four tasks bind handoffs to task keys that do not exist anywhere in the plan index. SLT-SYN-04 declares 'MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it'. SLT-SYN-01 declares its positional-meta finding 'binding on SLT-SYN-07'. SLT-SYN-02 says 'the contract SLT-SYN-08 buys against'. SLT-SYN-03 states SLT Sync Excl Probe 'is bought exactly ONCE, by SLT-SYN-09'. SLT-SYN-05..09 are not authored. Consequence: SLT Sync Excl Probe (created and registered by SLT-SYN-03) has no owning purchaser at all and will be created, never exercised, then deleted by SLT-SETUP-99 - a wasted artifact and a wasted creation slot.

**Fix.** Either author SLT-SYN-05..09 or re-point the handoffs. Minimum viable repair for this window: delete the SLT Sync Excl Probe half of SLT-SYN-03 (its exclusivity evidence is already produced identically by SLT-PROD-05 steps 7-9), keep only SLT Sync Global Daily, and rewrite SLT-SYN-04's ordering clause to reference the actual successor tasks or to say 'no successor sync purchase task may run until this task's restore is proven'.

### C22 · `unrated` · impossible-timing

**Tasks:** `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`, `SLT-PROD-02`, `SLT-PROD-03`, `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-06`, `SLT-PROD-07`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-PROD-15`, `SLT-PROD-16`, `SLT-SYN-01`, `SLT-SYN-02`, `SLT-SYN-03`, `SLT-SYN-04`

**Problem.** 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.

**Fix.** Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

### C23 · `unrated` · impossible-timing

**Tasks:** `SLT-SETUP-99`, `SLT-PROD-14`, `SLT-SYN-04`, `SLT-PROD-15`, `SLT-PROD-10`

**Problem.** SLT-SETUP-99 is scheduled on d10 (2026-08-11) and cancels + permanently deletes every SLT subscription, order, product and user, but the automated renewal watch runs to D12 (2026-08-13) and several subscriptions have renewals due after D10: SLT Flex Daily Two Seg and SLT Flex Daily Next Cycle renew 2026-08-11, the SLT Flex Variable Daily Full/Next Cycle variations renew 2026-08-12, the SLT-SYN-04 globally-synced day/3 subscription renews 2026-08-13, and SLT Box Daily renews 2026-08-11. Any dunning ladder started on D8-D10 also cancels at +3 days, i.e. 2026-08-11..08-13. Deleting on D10 destroys exactly the tail evidence D11 and D12 exist to collect. The task's own precondition notices the clash and then leaves it to the operator.

**Fix.** Split SLT-SETUP-99 into two tasks. SLT-SETUP-99A (D10, 2026-08-11): Part 1 settings restore + jq diff, plus cancel ONLY the subscriptions whose evidence is complete (all day/1 workhorses: SLT Daily Core, SLT Signup Fee Daily, SLT Renewal Price Step, SLT Paddle Daily, the plan-ladder rungs, SLT Free Signup Daily, SLT Trial Four Day, SLT Variable Daily tiers) so D11/D12 are not polluted by daily-renewal noise. SLT-SETUP-99B (2026-08-13, after the D12 watch check has been captured): Parts 2-4, cancel the remaining tail cohort and delete all artifacts. Settings restore is safe on D10 because it only affects NEW subscriptions.

### C24 · `unrated` · shared-global-setting

**Tasks:** `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-PROD-11`, `SLT-SETUP-99`, `SLT-SYN-04`

**Problem.** The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires every due pending action for that hook, including pre-existing non-SLT subscriptions and every other SLT test's pending invoice, renewal, hold, cancel, or expiry action. The affected forced cases are the two live month cohorts, the week segment-3 cohort, and the hand-set expiry/status probes. One broad drain would prematurely fire unrelated work and irreversibly contaminate the window.

**Fix.** Ban hook/group drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot Tools -> Scheduled Actions -> Pending and capture the shared non-SLT schedule baseline; (2) mutate and re-queue only the named target subscription, parking its new rows in the future so cron cannot race the browser; (3) query and record the exact target action IDs; (4) run the invoice and charge rows from the Scheduled Actions UI one exact ID at a time, re-snapshotting before each click; (5) require an empty non-SLT diff after every target. There is no broad-drain fallback. Confine all date-meta time travel to D8 (2026-08-10).

### C25 · `unrated` · same-account-collision

**Tasks:** `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-SETUP-03`, `SLT-SYN-03`, `SLT-SYN-04`

**Problem.** `multiple_subscriptions.auto_migrate_on_checkout = true` is a baseline the plan never changes, yet the draft required the same account to buy the same month, week, and variable products repeatedly. Later checkouts could migrate an earlier subscription instead of creating an independent cohort, silently destroying segment evidence and making My Account assertions ambiguous.

**Fix.** Add `slt-flex2` and `slt-flex3` and guarantee no account buys the same product twice. The live month cohorts are segment 2 -> `slt-flex2` and segment 3 -> `slt-flex3`; segment 1 is config-only because its live window predates product creation. Split the three live week cohorts across `slt-flex`, `slt-flex2`, and `slt-flex3`. Buy only the Full and Next Cycle variable cohorts with distinct accounts; keep No Sync as a config-only control. Record the auto-migrate probe before any repeated-product scenario.

### C26 · `unrated` · same-account-collision

**Tasks:** `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-SYN-03`, `SLT-SYN-04`

**Problem.** Ten tasks originally performed cart previews through one shared guest session and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.

**Resolved rule.** Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser --session <name> close`); never use `agent-browser close --all`.

### C27 · `unrated` · same-account-collision

**Tasks:** `SLT-SYN-01`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

**Problem.** SLT-SYN-01 performs destructive meta surgery on the four live flex products: steps 7-9 write inverted/out-of-range/collapsing boundary pairs to SLT Flex Month Segments and save; step 12 sets all three _active metas to 'no'; step 13 writes seg1_active=0; step 17 unticks and re-ticks the master checkbox on SLT Flex Week Segments. As authored these products are purchased on d0 by SLT-PROD-12/13/14's own isolation notes, so the surgery lands on products that already carry live subscriptions. SegmentPlan config is read from the product for checkout AND for the renewal sync context, so a subscription created between the probe and the restore, or a renewal computed inside the all-'no' window, resolves against a plan that no task expects. The empty before/after diff proves only that the END state matches - not that nothing was observed mid-probe.

**Fix.** Enforce audit-before-purchase: SLT-SYN-01 runs on D1 immediately after SLT-PROD-12 and SLT-PROD-14 are created and BEFORE any flex purchase is placed (all flex purchases move to D1 after 12:00). Move the SLT Flex Week Segments segment-1 purchase from D0 to D1 afternoon - it stays in segment 1 (days 1-2 of the week cycle) with the same $14.00 charge and the same 2026-08-08 renewal, so nothing in the contract changes. Additionally, no flex product may be purchased on any later day without first re-reading its six flex metas and attaching them to the purchase evidence.

### C28 · `unrated` · same-account-collision

**Tasks:** `SLT-SYN-01`, `SLT-PROD-06`

**Problem.** SLT-SYN-01 step 16 targets SLT Fixed Three Cycles - a product SLT-PROD-06 requires to be purchased on D0 and whose subscription must expire exactly on 2026-08-07. The step is also self-contradictory: it says 'ticking it and saving yields getConfig() = null' and then 'Do NOT save the tick'. If an executing agent resolves the contradiction by saving, a live, date-critical subscription's product gains _arraysubs_flex_sync_enabled=yes mid-life, and the pass criterion 'left with _arraysubs_flex_sync_enabled ABSENT' depends on a manual untick that is not itself verified against the live subscription.

**Fix.** Do not use a purchased product as the sub-minimum-cycle canvas. Have SLT-SYN-01 create its own throwaway probe product `SLT Flex SubMin Probe` (simple, virtual, subscription, day/2, $7.00, never purchased by anyone), run the tick/save/getConfig()===null probe there, and leave SLT Fixed Three Cycles completely untouched. Rewrite step 16 to remove the contradictory 'do not save' clause. The probe product matches the `SLT ` prefix so SLT-SETUP-99's existing product-search teardown already removes it.

### C29 · `unrated` · same-account-collision

**Tasks:** `SLT-SYN-02`, `SLT-PROD-15`

**Problem.** Same shape as the SLT-SYN-01 case at variation level: SLT-SYN-02 toggles segments off on the Full variation, reconfigures the Next Cycle variation to segment-1-only, and ticks/unticks the master checkbox on the No Sync variation, saving each time. SLT-PROD-15's isolation note has all three variations purchased on d0. Any purchase placed while a probe state is in effect resolves filterRenewalSyncContext() against the wrong plan, and SLT-SYN-02's own handoff ('if SLT-SYN-08 observes identical next-payment dates, this task's evidence proves the fault is in product_id resolution') is void if the purchases straddled the probes.

**Fix.** Bind SLT-SYN-02 to run on the same day as SLT-PROD-15 (D2), immediately after creation and strictly BEFORE the three variation purchases, which move to D2 after 12:00. Add an explicit gate step at the end of SLT-SYN-02: re-dump the three variations' six metas and post them to the registry as the 'purchase-authorised configuration'; the purchasing task must quote that dump in its evidence.

### C30 · `unrated` · impossible-timing

**Tasks:** `SLT-SETUP-04`, `SLT-PROD-14`, `SLT-PROD-15`, `SLT-SYN-04`

**Problem.** SLT-SETUP-04 sets Coupon expiry date = 2026-08-12 on all six SLT coupons and justifies it as 'past the watch window'. It is not: the watch window runs through D12 = 2026-08-14, and renewals fall on 2026-08-11, 2026-08-12, 2026-08-13 and 2026-08-14 (SLT Flex Daily Two Seg / Next Cycle, the SLT Flex Variable Daily day/3 cohort, and the SLT-SYN-04 globally-synced subscription). SLTPCT20REC is a RECURRING discount whose renewal-order behaviour is exactly what those tail renewals would prove, and a coupon that has expired by then makes the tail assertion untestable or produces a false negative.

**Fix.** Set Coupon expiry date = 2026-08-15 (or leave it blank and rely on SLT-SETUP-99B deletion) for all six SLT coupons, and record in the registry that no SLT coupon may expire before the last watch day. A live `WC_Coupon::set_date_expires('2026-08-14')` runtime probe resolves to `2026-08-14 00:00:00` site time, so the old date does not cover D12; `2026-08-15 00:00:00` does. Update SLT-SETUP-04 step 4 and its Description string to say 'delete on 2026-08-15'.

### C31 · `unrated` · shared-global-setting

**Tasks:** `SLT-SETUP-02`, `SLT-SETUP-99`, `SLT-PROD-16`, `SLT-SYN-04`

**Problem.** SLT-SETUP-02 flips five booleans ON for the whole window (allow_early_renew, allow_reactivation, pause_subscription.enabled, pause_subscription.customer_can_pause; plus sync OFF) and declares them frozen. Nothing in the plan republishes that baseline where a my-account or customer-action task will see it, so any later task auditing the my-account subscription screen against the site's shipped defaults will file Renew Early / Reactivate / Pause buttons as unexpected UI. The reverse trap also exists: cancellation.retention_offers_enabled has pause/skip OFF while the pause FEATURE is now ON, so the retention modal legitimately shows no pause offer even though pausing works - easy to misfile as a defect. SLT-PROD-16 already relies on the baseline being ON to assert Paddle's Renew Early button stays hidden.

**Fix.** SLT-SETUP-02 must append a 'WINDOW BASELINE (frozen)' table to slt-catalog-registry listing all five booleans with prior value / window value / restoring task, and every customer-facing audit task must quote that table in its preconditions instead of the shipped defaults. Add a pass criterion to SLT-SETUP-02: the registry table exists. SLT-SETUP-99A restores all five and proves it with the empty jq diff.

### C32 · `unrated` · duplicate-coverage

**Tasks:** `SLT-SETUP-05`, `SLT-PROD-14`

**Problem.** The assertion 'Paddle is hidden from checkout for SLT Flex Daily Next Cycle, Stripe is offered' is executed twice: SLT-SETUP-05 step 7 / expected result 4 / pass criterion 3, and SLT-PROD-14 step 10 / expected result 7 / pass criterion 5. Both drive a guest cart, both screenshot the accordion (SLT-SETUP-05-04-checkout-flex-nextcycle-gateways.png and SLT-PROD-14-05-paddle-absent.png). It is the same code path, the same product, the same day, and it also doubles the guest-cart collision surface described above.

**Fix.** Keep the probe in SLT-SETUP-05, which owns the gateway capability matrix. Reduce SLT-PROD-14 step 10 to a cart-note check only (the 'covers the full billing cycle starting 4 August, 2026' bonus-access string) and replace its gateway pass criterion with 'gateway gating verified by SLT-SETUP-05; cite that task's evidence id'. Saves one full guest-checkout cycle on the busiest day.

### C33 · `unrated` · duplicate-coverage

**Tasks:** `SLT-SYN-03`, `SLT-PROD-15`, `SLT-PROD-05`

**Problem.** SLT-SYN-03 creates two products that are already covered. (a) SLT Sync Global Daily (day/3, non-flex) is functionally identical as a control to the 'No Sync' variation of SLT Flex Variable Daily (day/3, flex unticked) created by SLT-PROD-15 - both exist to show anniversary scheduling on a 3-day cycle. (b) SLT Sync Excl Probe exists only to demonstrate that Different Renewal Price hides the Flexible Renewal Sync section, which SLT-PROD-05 steps 7-9 already capture verbatim with three screenshots, and its declared purchaser SLT-SYN-09 does not exist. Two product-creation slots on the most overloaded day are spent on coverage the catalog already holds.

**Fix.** Keep SLT Sync Global Daily - SLT-SYN-04 needs a simple (not variation) product so the five _renewal_sync_* metas read cleanly - but drop SLT Sync Excl Probe entirely and delete its half of SLT-SYN-03 (steps 10-16, screenshots 02/03). Point SLT-SYN-03's exclusivity claim at SLT-PROD-05's evidence ids instead. Conversely, do not spend a checkout on the SLT-PROD-15 'No Sync' variation as a scheduling control; assert it by meta + SegmentPlan::getConfig()===null only, and let SLT Sync Global Daily carry the purchased control.

### C34 · `unrated` · impossible-timing

**Tasks:** `SLT-PROD-12`, `SLT-PROD-13`

**Problem.** The two reachable live month cohorts land their first full renewals on 2026-09-01 and 2026-10-01 site, outside the watch window. The week segment-3 cohort lands on 2026-08-15, also outside the window, while week segments 1 and 2 renew naturally. Without explicit owners, these three forced renewals would be improvised late in the window.

**Fix.** On D8 (2026-08-10), make `SLT-TT-00` own the shared queue/non-SLT pre-flight plus the live month segment-2 and week segment-3 renewal pairs. Make `SLT-SYN-10` own the separately purchased month segment-3 pair immediately afterward. Run each invoice/charge row by exact ID and prove the non-SLT schedule diff is empty. Variable-flex renewals remain natural; segment 1 is config-only. Other D8 status/date probes retain their own named targets and quote the same baseline.

### C35 · `unrated` · duplicate-coverage

**Tasks:** `SLT-SETUP-01`, `SLT-SETUP-05`, `SLT-SYN-04`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`

**Problem.** SLT-SETUP-01 builds the classic cart/checkout harness pages (slt-classic-cart, slt-classic-checkout) and binds them on every task whose Scope says 'Checkout: classic' or 'both' - but not a single authored task actually visits them. SLT-SETUP-05 uses /checkout/ (block), SLT-SYN-04's Scope says 'Checkout: block' and it uses /checkout/, and every cart preview (SLT-PROD-02/04/09/12/13/14, SLT-SYN-03) uses /cart/ (block). The 'Checkout: both' scope declarations are therefore unbacked, and two published pages are created and torn down without being exercised.

**Fix.** Assign the classic surface explicitly rather than declaratively: route SLT-SYN-04's purchase through /slt-classic-checkout (it is a plain Stripe purchase and is the cleanest classic candidate), route SLT-PROD-04's qty-1/qty-2 signup-fee cart probes through /slt-classic-cart (fee rendering differs between block and classic), and change every remaining 'Checkout: both' to the surface actually used. Never repoint the site's real Cart/Checkout pages - the harness pages are the only permitted classic surface.

### C36 · `unrated` · impossible-timing

**Tasks:** `SLT-SETUP-01`, `SLT-PROD-06`, `SLT-PROD-13`

**Problem.** Clock drift against the authored anchor. The plan is written for D0 = 2026-08-01 with hard D0 purchase deadlines (SLT-PROD-06 'MUST be purchased on D0'; SLT-PROD-13 relies on 2026-08-01 being the Saturday start-of-week). The evidence root /home/server-manager/slt-evidence is empty - no task has executed - and the host clock has already rolled past the start of the window, so a literal D0 is partly or wholly gone before SLT-SETUP-01 runs.

**Fix.** Two of the three D0 constraints are softer than authored and can absorb the slip without shifting the window: SLT Fixed Three Cycles ends at start + 6 days, so a 2026-08-02 purchase expires 2026-08-08 (still D7, still observable); SLT Flex Week Segments purchased 2026-08-02 is day 2 of the same Saturday-anchored week cycle, so it stays in segment 1 with the same $14.00 charge and the same 2026-08-08 renewal. Keep the D0=2026-08-01 labels in the calendar but treat them as ordinal slots: if execution actually begins on 2026-08-02, shift every date by +1 and re-verify only two things - that SLT Fixed Three Cycles still expires on or before D9, and that the watch tail still reaches the last renewal (which moves to 2026-08-14).

### C37 · `medium` · contradictory-expected-result

**Tasks:** `SLT-LIFE-04`, `SLT-EML-08`, `SLT-EML-14`, `SLT-PROD-06`

**Problem.** SLT-LIFE-04 derives from code (OrderIntegration.php:1489-1502) that SLT Fixed Three Cycles stamps _end_date at the moment of the FINAL renewal charge and flips to arraysubs-expired inside that payment - so with a D0 (2026-08-02) purchase the expiry is 2026-08-06, not the catalog's 'expires 6 days after checkout' (which LIFE-04 itself proves is unbacked - arraysubs_calculate_end_date_from_length() has zero callers). SLT-EML-08 states 'S_FIX expired 2026-08-08' and hunts for the 'has expired' message dated 08-08; SLT-EML-14 states 'Fixed Three Cycles renews 08-04 and 08-06 and expires 08-08 (_end_date)'; SLT-PROD-06's title still says 'expires on 2026-08-07' (the pre-shift anchor). Three different dates for one event; EML-08 and EML-14 will both report a missing email.

**Fix.** Adopt LIFE-04's code-derived model as authoritative and restate the dates everywhere for D0 = 2026-08-02: renewal #1 2026-08-04, renewal #2 2026-08-06, _end_date = the 08-06 charge moment, status arraysubs-expired 08-06, subscription_expired mail 08-06 PM (readable on watch D5, 2026-08-07). Update SLT-EML-08 step 1 to search 08-06, SLT-EML-14's dated contract, SLT-PROD-06's title and objective, and the watch schedule. LIFE-04's 'file an issue if _end_date is not the final charge moment' stays as the open question.

### C38 · `medium` · shared-per-subscription-meta vs published watch contract

**Tasks:** `SLT-EML-02`, `SLT-EML-05`, `SLT-EML-15`, `SLT-REN-02`

**Problem.** SLT-EML-15 (d2) publishes to the registry the reconciled expected-mail set for one SLT Daily Core renewal, explicitly asserting 'zero renewal_invoice - suppressed for automatic subs with auto-renew on' and states 'this is the reference the D3-D12 watch uses to classify daily renewal mail'. SLT-EML-02 (d4) and SLT-EML-05 (d6) then each write _auto_renew=off on that very subscription for one cycle, deliberately producing an 'Invoice for subscription #SUB_CORE' email plus a manually-paid renewal on D4 and D6. The watcher, reading EML-15's table, will classify both as UNMAPPED and file them as leaks - and will also see the charge leg leave the order in a non-standard state.

**Fix.** EML-02 and EML-05 must each post a dated exception to the registry BEFORE flipping the meta ('SUB_CORE cycle due <date>: _auto_renew=off, one renewal_invoice + one customer-paid renewal order expected; suppression restored at <time>'), and the watch schedule rows for D4/D5 and D6/D7 must carry those exceptions as expected rather than negative. Add to both tasks a pass criterion 'the registry exception exists and was posted before the meta write' and a teardown criterion 'the next cycle after restore sends no invoice mail'.

### C39 · `medium` · contradictory-precondition (factually wrong)

**Tasks:** `SLT-CHK-03`, `SLT-CHK-10`, `SLT-SETUP-03`

**Problem.** SLT-CHK-03's objective and precondition assert 'a logged-out visitor cannot check out anonymously - woocommerce_enable_guest_checkout=no'. The README's verified environment baseline says the option is `yes`, and SLT-CHK-10 carries an explicit documentation correction ('That is FALSE - verified yes on 2026-08-02, alongside woocommerce_enable_signup_and_login_from_checkout=yes') and files an issue against SLT-SETUP-03 for the same claim. CHK-03 runs two days before CHK-10, so it will observe an offered guest path for a non-subscription cart, or reason about the wrong mechanism and file a false bug against the checkout registration force.

**Fix.** Rewrite CHK-03's objective and precondition to the correct mechanism: guest checkout IS enabled site-wide; registration is forced only for subscription carts, via woocommerce_checkout_registration_required (SubscriptionCheckout/Services/Hooks.php:103, CheckoutHelpersTrait.php:93-100) gated on checkout.auto_create_account=true AND cartHasSubscriptionCheckoutItems(). Keep the assertion 'no continue-as-guest option for THIS cart' and add step 1a: `wp option get woocommerce_enable_guest_checkout --allow-root` must print `yes`. Correct SLT-SETUP-03's objective in the catalog at the same time so CHK-10's issue is a confirmation rather than a discovery.

### C40 · `medium` · contradictory-expected-result

**Tasks:** `SLT-CHK-15`, `SLT-EML-09`, `SLT-EML-01`

**Problem.** SLT-CHK-15 expected result 7 requires SLT Trial Four Day's subscription to carry `_renewal_reminder_action_id` due 2026-08-05 (trial end 08-08 minus the 3-day lead) and asserts it exists. SLT-EML-09 step 4 asserts the opposite - the old EML-09 step 4 asserted that no reminder action existed - and expected result 3 says 'No pending arraysubs_send_renewal_reminder or arraysubs_send_expiring_soon action for S_TR'. Both tasks buy/attach to the same subscription on D2. One of them will file a bug the other declares correct.

**Fix.** Separate the action from the mail. Per SLT-REF-05 / EmailManager.php:806 the reminder handler requires post_status exactly `arraysubs-active`, and a trialling subscription is `arraysubs-trial` - so the ACTION may legitimately be scheduled while the MAIL is legitimately never sent. Restate CHK-15 ER7 as 'an arraysubs_send_renewal_reminder action for S_TR exists at trial_end - 3d + k; record whether it does'. Restate EML-09 ER3 as 'no reminder MAIL for S_TR on 2026-08-05; whether the action exists is recorded, not asserted'. Make the D4 watch row carry both as an explicit paired check.

### C41 · `medium` · action-scheduler policy / broad-fire risk

**Tasks:** `SLT-LIFE-04`, `SLT-EML-01`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-ADM-05`, `SLT-SETUP-99`

**Problem.** No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.

**Fix.** Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

### C42 · `medium` · impossible-timing / single-day contention

**Tasks:** `SLT-LIFE-01`, `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-10`, `SLT-EML-14`, `SLT-DUN-05`

**Problem.** D8 (2026-08-10) is the single authorized time-travel day and several tasks mutate target dates or scheduler state. Independent pre-flights and unspecified ordering make them observe one another's intentional work as contamination, while a stale browser snapshot can cause the wrong action row to run.

**Fix.** Fix a strict D8 running order in the calendar and make it a precondition in each body: (0) `SLT-TT-00` captures the shared baseline, then executes live month segment 2 and week segment 3 one exact invoice/charge ID at a time; (1) `SLT-SYN-10` executes month segment 3; (2) `SLT-SW-02`; (3) `SLT-EML-08`; (4) `SLT-EML-10`; (5) `SLT-LIFE-01`; (6) `SLT-EML-14`. Re-snapshot before every manual action and close with the shared post-mutation non-SLT diff. Variable-flex remains natural-watch evidence.

### C43 · `medium` · shared-product-meta / undeclared bracket

**Tasks:** `SLT-SYN-13`, `SLT-SYN-02`, `SLT-PROD-15`, `SLT-MYA-05`

**Problem.** SLT-SYN-13 step 2 writes a decoy segment plan onto the SLT Flex Variable Daily PARENT product and deletes it only at step 7, the same day - but between those steps two live checkouts are placed and the window is unbounded in the body. SLT-SYN-02 audits the same product family on the same day (D2). Any other cart or checkout touching that parent inside the decoy window resolves filterRenewalSyncContext() against a plan no task expects, and the decoy's own null-vs-config proof depends on nothing else having read it. Separately SLT-MYA-05 leaves two appended members_access rules and a product-level _arraysubs_features meta live from D2 morning until its step-10 teardown on D7 - a five-day global deviation during which the pre-existing 'Gold members save 15%' rule (which targets pro_member on ALL products) can alter front-end prices for slt-fail.

**Fix.** For SYN-13: declare the decoy a bracket - record open/close UTC in /home/server-manager/slt-evidence/SLT-SYN-13-decoy-bracket.txt, post it to the registry, keep it under 90 minutes, and assert no other SLT task carts or checks out SLT Flex Variable Daily inside it. Add a pass criterion 'decoy removed and getConfig(<PARENT>) is null before the bracket closes'. For MYA-05: shorten the deviation by moving its teardown from D7 to immediately after follow-up B (D5 morning, once the on-hold role removal is captured) and re-adding the rules only if follow-up C needs them; record the bracket in the registry either way, and add an explicit price check on SLT Retry Daily renewals proving the pro_member discount never reached a cron renewal.

### C44 · `low` · duplicate-coverage

**Tasks:** `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`, `SLT-EML-07`, `SLT-SW-10`, `SLT-EML-04`, `SLT-DUN-02`, `SLT-SYN-11`, `SLT-SYN-01`, `SLT-PROD-05`, `SLT-LIFE-02`, `SLT-CHK-04`

**Problem.** Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.

**Fix.** Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 retains one checkout because it owns the S_EML downstream canvas; it owns the gating-key, subject-string, recipient-resolution, and B4 dead-setting proof, and cites CHK-01 only for generic happy-path mechanics. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

### C45 · `high` · invalid-product-permalink

**Tasks:** every task that navigates directly to an SLT WooCommerce product page; first caught in `SLT-SYN-03` during the D1 live pre-audit.

**Problem.** Several task bodies used root-level paths such as `/slt-daily-core` or `https://mirror-help.arrayhash.com/slt-sync-global-daily`. This site's product permalink base is `/product/`; the authored paths can land on a 404 and turn a storefront assertion into a false product failure. The custom `/slt-classic-cart`, `/slt-classic-checkout`, and `/slt-member-area` routes are real harness/page routes and must not be rewritten.

**Fix.** Normalize every direct product navigation and product path shown in test-data tables to `/product/<slug>/`, including legacy `/?p=<ID>` placeholders. Keep the three intentional root-level harness/page routes unchanged. `SLT-SYN-03` step 13 now also explicitly captures its already-required frontend screenshot after loading the corrected URL. A suite-wide search confirms no root-level product permalink or placeholder reference remains.

### C46 · `low` · renewal-invoice-window arithmetic

**Tasks:** `SLT-REN-03`

**Problem.** The task permits its D1 purchase through 13:00 site time and the spread offset can approach six hours. Therefore `due + k - 6h` can approach 13:00 on D2; the authored 12:45 upper bound excluded valid schedules created during the last fifteen minutes of the checkout gate.

**Fix.** Correct both D2 invoice-window references to 06:30–13:00 site time. The execution still records and follows the exact action timestamp, so this correction changes no live gate or product expectation.

### C47 · `medium` · impossible-evidence / missing-browser-session

**Tasks:** `SLT-SYN-08`

**Problem.** Steps 2, 7, and 8 each asked one file to prove two different browser pages: two product legends in `SLT-SYN-08-01-positional.png`, two order-item mirrors in `-04-item-meta.png`, and two separately filtered subscription queues in `-05-pending.png`. A single uncomposed browser screenshot can show only one of each pair. The task also defined only the two customer sessions even though all three proof pairs require an authenticated administrator. An executor would overwrite first-item evidence, reuse another task's admin state, or omit half of each pair.

**Fix.** Define `admin-SLT-SYN-08`, make the product views read-only, require a fresh snapshot after every product/order/queue navigation, and split all three pairs into `01a/01b`, `04a/04b`, and `05a/05b`. Each pending screenshot is filtered by one exact numeric subscription ID. Add the named admin session to the explicit teardown list.

### C48 · `medium` · incomplete-email-manifest

**Tasks:** `SLT-SYN-08`, with `SLT-SYN-05` as the live control

**Problem.** Each of the two paid virtual Stripe checkouts has the same mail shape already proved by SLT-SYN-05: WC completed order, WC new order, customer new-subscription, and admin new-subscription. The task listed only the latter two. Its own instruction to reconcile the complete delta would therefore encounter four legitimate WooCommerce order messages that were absent from the expected table and could be misclassified as unrelated mail or leakage.

**Fix.** Add both WooCommerce order-mail rows for both purchases and change the pass criterion from two created-mail pairs to two complete four-message checkout sets. Keep `renewal_invoice` as the explicit negative.

### C49 · `low` · contradictory-email-scope

**Tasks:** `SLT-EML-06`

**Problem.** The objective said the checkout emits exactly two ArraySubs signup emails "and nothing else", while step 6 and expected result 5 correctly require WooCommerce order mail to be recorded separately. A literal reading would fail a healthy checkout as soon as its normal WC completed-order or admin new-order message arrived.

**Fix.** Narrow the negative to "no other ArraySubs lifecycle email" and explicitly retain the separate WooCommerce order-mail classification. This changes no live expectation; it removes only the contradictory wording.

### C50 · `medium` · omitted-past-due-reminder-observation

**Tasks:** `SLT-SYN-08`

**Problem.** The task correctly says the Two Seg subscription's `due−3d+k` reminder timestamp is already past at its D1 afternoon signup and that any immediate `renews soon` result is observational. Its email table omitted that possible message, however, while instructing a complete delta reconciliation. A legitimate immediate reminder could therefore be mistaken for an unmapped leak or make the stated checkout-mail set appear over-counted.

**Fix.** Add an observation-only reminder row for the Two Seg checkout, requiring its action/mail timing and presence or absence to be recorded without making either outcome alone a failure. Update the D1/D2 watch rows so their blanket `renews soon` negatives exclude and classify this named observation. Keep `renewal_invoice` as the true negative and require the pass summary to classify the reminder explicitly.

### C51 · `high` · known-failure dependency could strand execution

**Tasks:** `SLT-SETUP-05`, `SLT-CHK-04`, and every downstream task that consumes `SUB_PAD`

**Problem.** Live SETUP-05 execution completed with Paddle offered at checkout but all three catalogue-sync metadata keys absent on product 12112. CHK-04 depended only on SETUP-05 being `done` and assumed the overlay, order, and `SUB_PAD` would necessarily exist. A literal executor could repeatedly re-save the product, attempt a credential/source repair outside QA scope, leave CHK-04 blocked forever, or invent a substitute Paddle subscription; all four responses violate this run's finding-only and finish-every-task rules.

**Fix.** Make CHK-04 read the existing SETUP-05 issue, attempt the customer flow once, and define a complete failure branch: capture UI/console/network and unchanged counts, clean the cart, write a standalone checkout-impact issue, review the execution card to `done`, and publish `SUB_PAD unavailable`. Add explicit fallback clauses to `SLT-REN-04`, `SLT-ADM-07`, `SLT-EML-03`, and `SLT-MYA-03`: preserve any independent Stripe leg, record the Paddle-only result `UNVERIFIED (no source subscription)`, and close rather than substituting data, mutating source, or blocking unrelated work.

### C52 · `low` · watch-count arithmetic

**Tasks:** D2 watch row; D1 purchase set

**Problem.** The D2 mail inventory called the prior day's set "seven D1 purchases" while naming CHK-03, REN-03, CPN-01, CPN-02, EML-06, LIFE-05, and both SYN-08 purchases — eight checkouts. The prose list was complete but its total was wrong, making an eight-set reconciliation appear one checkout over budget.

**Fix.** Correct the watch total to eight without changing the named owners or any expected message.

### C53 · `medium` · incomplete-email-manifest

**Tasks:** `SLT-REN-03`, with the D2 watch row as the binding inventory

**Problem.** `SLT-REN-03` creates `slt-invoice@example.test` during a paid virtual Stripe checkout, so its healthy checkout produces five owned messages: WC customer new-account, WC admin new-order, WC customer completed-order, customer `new_subscription`, and admin `admin_new_subscription`. The task required a complete Mailpit delta but explicitly listed only the two ArraySubs signup messages. Its vague reference to Woo account/order mail as "record-only" did not name, count, or require the three messages already mandated by the D2 watch row.

**Fix.** Expand the task's email table to the complete five-message checkout set, require every exact message id in the evidence and pass criteria, and keep only payment-success, payment-failed, and renewal-reminder mail as the post-charge negatives. The future `renewal_invoice` row remains unchanged.

### C54 · `medium` · impossible-cross-session-cart-evidence

**Tasks:** `SLT-SYN-08`

**Problem.** The task requires two independently authenticated customer carts and persistent-cart records to be empty after their respective purchases, but named only one plural `SLT-SYN-08-03b-carts-empty-after.png` captured in the second customer session. One browser screenshot cannot prove the first session's post-checkout cart, so half of the pass criterion had no task-keyed visual evidence.

**Fix.** Capture `SLT-SYN-08-02b-two-seg-cart-empty-after.png` in the Two Seg customer session immediately after its purchase and `SLT-SYN-08-03b-next-cycle-cart-empty-after.png` in the Next Cycle session after its purchase. Keep the per-user persistent-cart metadata readbacks paired with those captures.

### C55 · `medium` · deterministic-order-mail-left-optional

**Tasks:** `SLT-EML-06`, with `SLT-CHK-01` and the D2 watch row as controls

**Problem.** C49 correctly narrowed the primary assertion to exactly two ArraySubs signup emails, but the task still said the normal WooCommerce order mail only "may" arrive and omitted it from the email table. This is a paid virtual Stripe checkout by an existing user, so the WC admin new-order and customer completed-order messages are deterministic and are already required by the binding D2 watch inventory. Leaving them optional makes the complete Mailpit reconciliation under-specified.

**Fix.** Keep the two-message ArraySubs assertion intact, but add separate required rows for WC new-order and WC completed-order, preserve all four exact checkout message ids, and state explicitly that the Woo rows are classified outside the ArraySubs count.

### C56 · `medium` · coupon-checkout-mail-manifest-incomplete

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`, with the D2 watch row as the binding inventory

**Problem.** Both coupon tasks require complete checkout-mail reconciliation but list only the two ArraySubs signup messages after their paid parent orders. Each registered-customer virtual checkout also deterministically emits an admin WC new-order message and a customer WC completed-order message. The daily watch already requires those four-message checkout sets, so the task-local manifests could undercount healthy mail by two messages apiece.

**Fix.** Add the WC new-order and completed-order rows to both tasks, require all four exact checkout message ids in evidence, and add the complete four-message set to each pass checklist. Keep setup-user mail and later renewal mail in their distinct baselines.

### C57 · `medium` · missing-browser-session

**Tasks:** `SLT-CHK-03`, `SLT-LIFE-05`

**Problem.** Both tasks required screenshots of the administrator-only Scheduled Actions screen but defined and tore down only their guest/customer session. An executor had to borrow an unrelated admin session, omit the queue evidence, or leave an undeclared browser state open.

**Fix.** Define isolated `admin-SLT-CHK-03` and `admin-SLT-LIFE-05` sessions, load the documented admin auth state, use each only for its exact-subscription Pending view, and close it alongside that task's customer session.

### C58 · `medium` · contradictory-account-generation-contract

**Tasks:** `SLT-CHK-03`, `SLT-LIFE-05`

**Problem.** The task said the username would be derived from the email address, allowed a password field, and expected account-mail subject text `Your account on`. The frozen live WooCommerce settings have username and password generation both enabled. With billing name `SLT Guest`, checkout deterministically created login `slt.guest`, displayed no password field, and sent subject `Your mirror-help.arrayhash.com account has been created!`. Treating those healthy WooCommerce outcomes as failures would create a false product issue.

**Fix.** Add the two option probes, reserve/check both the email and generated login, require `slt.guest` plus no password field under the current fixture, and match the stable account-mail phrase `account has been created`.

### C59 · `medium` · HPOS-linkage-read-path

**Tasks:** `SLT-CHK-03`, `SLT-LIFE-05`, `SLT-EML-06`

**Problem.** These tasks required exact order `_subscription_ids` linkage but did not name the read path. On this HPOS install the linkage is retained in `wp_postmeta`; `WC_Order::get_meta('_subscription_ids')` returns empty even though `get_post_meta($ORDER, '_subscription_ids', true)` returns the sole linked subscription. The ambiguous instruction could therefore produce a false missing-linkage bug or tempt a recency lookup.

**Fix.** Require the exact `get_post_meta()` read, assert one ID, and retain the independent reverse checks (`_parent_order_id`, customer, product, and count delta).

### C60 · `low` · stale-account-mail-negative

**Tasks:** `SLT-EML-06`

**Problem.** The admin-created-user negative still searched for the obsolete customer-account subject phrase `Your account on`. The frozen live WooCommerce mail contract uses `account has been created`, so the old phrase could miss the very customer notification this step is meant to prove absent.

**Fix.** Match the stable live phrase `account has been created` and retain the independent password/setup-message negative across the complete post-user-creation Mailpit delta.

### C61 · `medium` · one-click-checkout-redirect

**Tasks:** `SLT-EML-06`

**Problem.** The task expected `/cart/?add-to-cart=<Daily Core ID>` to remain on the cart, but the frozen QA-window setting `checkout.one_click_mode = subscription_items` intentionally redirects subscription additions straight to `/checkout/`. A healthy redirect could therefore be mistaken for a missing cart assertion.

**Fix.** Require the direct checkout redirect and `$10.00` checkout summary, then open `/cart/` explicitly to capture the populated cart row before returning to checkout.

### C62 · `medium` · unsupported-wp-eval-arguments

**Tasks:** `SLT-EML-06`

**Problem.** The corrected exact-linkage example passed the order ID as a positional argument to `wp eval`, but the installed WP-CLI build rejects that syntax with `Too many positional arguments`. The guard therefore stopped before reading the linkage.

**Fix.** Read `_subscription_ids` through `wp post meta get --format=json`, require a one-element numeric JSON array with `jq -e`, and retain all reverse-link and before/after-count checks.

### C63 · `medium` · ephemeral-context-treated-as-durable-meta

**Tasks:** `SLT-EML-06`

**Problem.** The task required `_arraysubs_status_change_context = initial_payment` as a post-checkout post-meta value. The live runtime retains no `_arraysubs_status_change_context` row on any subscription, including healthy checkouts whose customer and admin signup emails fired exactly once. The task had converted a hook-time activation context into an invalid durable-storage assertion.

**Fix.** Keep the context meta probe as diagnostic evidence but do not require it to persist. Prove the initial-payment path durably with the completed linked parent order, `_completed_payments = 1`, active status, start/next dates, and exact signup-mail pair.

### C64 · `medium` · coupon-hpos-linkage-read-path

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`

**Problem.** Both coupon cards required exact parent-order `_subscription_ids` linkage but left the read mechanism ambiguous. This HPOS runtime retains that linkage in WordPress post meta while the WooCommerce order meta accessor does not expose it, so an executor could report a false missing-linkage failure or fall back to a recency lookup.

**Fix.** Require `wp post meta get <PARENT_ORDER> _subscription_ids --format=json`, a strict one-element numeric `jq -e` guard, and the existing reverse-link/count checks. Explicitly forbid both `WC_Order::get_meta('_subscription_ids')` and recency selection.

### C65 · `medium` · coupon-one-click-redirect-and-receipt-capture

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`

**Problem.** The frozen `checkout.one_click_mode=subscription_items` redirects a subscription add straight to block checkout. CPN-01 assumed an in-cart subtotal and CPN-02 assumed the add action would remain on its classic cart; either healthy redirect could be mistaken for a failed cart flow. Both evidence manifests also named an order-received screenshot without requiring the capture at the receipt step.

**Fix.** Make CPN-01 require the direct block-checkout redirect and its $10.00 summary. Make CPN-02 accept that redirect, then explicitly reopen the classic cart and require its one product row before coupon application. Require each named receipt screenshot immediately after the successful order.

### C66 · `high` · coupon-future-gate-handoff-deferred

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`

**Problem.** Each multi-day coupon card deferred its registry append until after renewal #2 even though the D1 browser sessions close immediately after checkout and the D2 watcher must capture `REN1_PRE` before a subscription-specific spread-adjusted charge. Without a D1 fixture/action/deadline handoff, the next phase could miss the only safe baseline or identify the renewal by recency.

**Fix.** Before closing the D1 leg, publish the exact user, coupon, parent order, subscription, pending action IDs/times, and `charge−5m` deadline to both the live registry and D01 report. Keep the card in progress with that timestamp, then append renewal IDs/baselines as the dated legs complete.

### C67 · `high` · remaining-hpos-linkage-read-paths

**Tasks:** `SLT-CHK-04`, `SLT-CHK-05`, `SLT-CPN-03`, `SLT-CHK-09`, `SLT-SYN-04`, `SLT-SYN-14`, `SLT-CHK-08`, `SLT-IMP-03`, `SLT-CHK-07`, `SLT-SW-10`, `SLT-SYN-10`, `SLT-DUN-05`

**Problem.** Twelve still-unexecuted purchase plans either left `_subscription_ids` resolution ambiguous or explicitly used `WC_Order::get_meta()`. The live HPOS runtime keeps this relationship in legacy WordPress post meta, and the WooCommerce accessor returns empty, so the same known read-path defect could generate false failures or recency-selected subscriptions throughout D2-D9.

**Fix.** Standardize every listed purchase on `wp post meta get <ORDER> _subscription_ids --format=json --allow-root`, a strict one-element numeric `jq -e` guard, and independent parent/customer/product checks. Explicitly ban the WooCommerce order meta accessor and recency selection for this relationship.

### C68 · `medium` · d2-product-preview-one-click-redirect

**Tasks:** `SLT-PROD-02`, `SLT-PROD-15`

**Problem.** Both D2 product-creation cards used subscription add actions as cart-preview probes while the frozen `checkout.one_click_mode=subscription_items` redirects those actions directly to block checkout. PROD-02 required a cart snapshot at the redirecting URL, and PROD-15 did not say where to inspect each variation after the redirect, so healthy previews could be scored as missing cart output.

**Fix.** Require the direct checkout redirect and inspect its summary first, then explicitly reopen `/cart/` to capture the product or selected variation rows and totals. Empty the cart between variation probes and at task end.

### C69 · `low` · d2-product-session-teardown-omitted

**Tasks:** `SLT-PROD-02`, `SLT-PROD-03`, `SLT-PROD-15`

**Problem.** Each D2 product card names separate admin and guest browser sessions but its task-local teardown omitted both. Relying only on phase-level cleanup would leave task completion unable to prove its own isolation contract and could carry stale admin or cart state into the following variation audit.

**Fix.** Require explicit closure of both named sessions at the end of each product task, after cart cleanup and evidence capture, while preserving every unrelated browser session.

### C70 · `medium` · members-access-bracket-opened-during-read-only-preflight

**Tasks:** `SLT-MYA-05`

**Problem.** The task recorded its multi-day bracket-open timestamp during a read-only baseline step, before the QA page, member rules, or feature meta existed. This contradicted the binding rule that a bracket opens only when the first non-default value is saved and would overstate the duration of a sensitive shared-state deviation.

**Fix.** Keep the baseline dump and user-role probe outside the bracket. Prepare the page without publishing, then record the open timestamp immediately before that first publish mutation; retain the authored D5 close point after the on-hold proof and restoration.

### C71 · `medium` · ren02-admin-session-and-route-missing

**Tasks:** `SLT-REN-02`

**Problem.** The task required two administrator-only screenshots but defined only a customer session and named a nonexistent WooCommerce Subscriptions screen. The live navigation exposes subscription detail under ArraySubs, so the literal plan could borrow another task's admin state or omit the evidence.

**Fix.** Add isolated `admin-SLT-REN-02`, use it for Tools → Scheduled Actions and the exact-ID ArraySubs subscription detail route, and close it alongside the customer session.

### C72 · `high` · ren02-daylong-mail-baseline-and-stranding-branch

**Tasks:** `SLT-REN-02`

**Problem.** The D2 morning read captured `PRE2` roughly twelve hours before the evening charge, forcing its reconciliation to absorb every unrelated D2 setup, checkout, and lifecycle message. The precondition also required `blocked` if renewal #1 failed, conflicting with the campaign's non-stranding rule for missing upstream fixtures.

**Fix.** Keep the morning read baseline-free, record and publish `PRE2` at least five minutes before the exact newly queued D2 charge action, and reconcile only that bounded delta. If renewal #1 is absent, file its standalone finding, close REN-02 as `UNVERIFIED (no upstream renewal #1)`, and never force or block it.

### C73 · `high` · dun01-linkage-receipt-and-admin-evidence-gaps

**Tasks:** `SLT-DUN-01`

**Problem.** The dunning fixture published `S_FAIL` without a defined HPOS linkage read or reverse/count guard, named five screenshots without requiring four of the captures, and defined only a customer session even though its failed-order, retry-row, and notes evidence is administrator-only.

**Fix.** Resolve S_FAIL from O0 through the proven post-meta JSON path and strict numeric guard, require the receipt screenshot at checkout, add isolated `admin-SLT-DUN-01` for the three D3 admin captures, and close both named sessions after each dated leg.

### C74 · `high` · dun01-attempt-baseline-captured-a-day-early

**Tasks:** `SLT-DUN-01`, handoff to `SLT-EML-04`

**Problem.** The task captured `DUN_ATTEMPT0_PRE` immediately after its D2 purchase even though the first failure occurs at `D+k` on D3. That day-long delta would contain every intervening shared-site mail and defeat exact failure-pair reconciliation.

**Fix.** Publish the exact D3 charge action and `charge−5m` deadline during the D2 handoff, but capture `DUN_ATTEMPT0_PRE` only at least five minutes before the actual D3 charge. Keep the post-failure `DUN_RETRY1_PRE` handoff unchanged.

### C75 · `medium` · dun01-parent-checkout-mail-manifest-implicit

**Tasks:** `SLT-DUN-01`

**Problem.** The task asked for a complete MP0 checkout delta but its email table and pass criteria named only future renewal-failure messages. A paid virtual Stripe parent checkout by the existing slt-fail customer deterministically emits the same four-message WC plus ArraySubs signup set as the other D1/D2 fixtures.

**Fix.** Add all four parent-checkout rows and exact IDs to the evidence/pass contract, keep them under MP0, and keep the later payment-failure pair isolated under the newly bounded MP1 baseline.

### C76 · `medium` · d2-checkout-admin-queue-sessions-missing

**Tasks:** `SLT-CHK-04`, `SLT-CHK-05`

**Problem.** Both D2 checkout cards required administrator-only Scheduled Actions screenshots but defined only customer sessions. CHK-05 also left its browser-based user creation without a named admin context. A literal run could borrow stale authentication, omit queue evidence, or leave an undeclared session open.

**Fix.** Add isolated `admin-SLT-CHK-04` and `admin-SLT-CHK-05`, route every admin creation/queue action through them, and close each alongside its customer session after the dated leg.

### C77 · `high` · chk15-two-order-resolution-and-sensitive-screenshot-gap

**Tasks:** `SLT-CHK-15`

**Problem.** The two-trial card said only "record order + sub ID" for checkout A, did not record or link checkout B at all, required admin-only Scheduled Actions evidence without an admin session, and listed a `-06-card.png` capture that could expose a populated hosted card frame. Its add-to-cart steps also ignored the frozen one-click redirect.

**Fix.** Record both numeric receipt orders, resolve each sole subscription through the HPOS post-meta JSON path with strict guards and reverse/count checks, add an isolated admin queue session, and classify distinct complete Mailpit deltas. Handle the one-click redirects by explicitly reopening the required carts. Replace the card screenshot with the second safe receipt screenshot and forbid any evidence containing a full card number.

### C78 · `medium` · eml09-screenshot-session-contradiction

**Tasks:** `SLT-EML-09`

**Problem.** The email rider required three browser screenshots while declaring that it used no browser session. That left the evidence source, authentication isolation, and teardown undefined.

**Fix.** Add one read-only `mail-SLT-EML-09` session for exact Mailpit message/search captures, name each capture in its dated step, and close only that session after the final conversion-path evidence.

### C79 · `high` · eml09-reminder-baseline-and-missing-action-source

**Tasks:** `SLT-EML-09`

**Problem.** `T2` was captured during D2 setup roughly a day before the D3 reminder gate, making the negative delta absorb unrelated site mail. The follow-up also assumed a reminder action existed even though action presence is explicitly observational, and it had no completion branch if CHK-15 failed to create S_TR.

**Fix.** Publish the exact scheduled reminder timestamp or, when absent, the computed theoretical `trial_end−3d+k` timestamp; capture T2 at least five minutes before that gate and inspect only the bounded delta. Capture T3 similarly before conversion. If S_TR is unavailable, close the rider as `UNVERIFIED` with the upstream issue rather than substituting or blocking.

### C80 · `high` · syn06-unresolved-purchases-and-shared-admin-evidence

**Tasks:** `SLT-SYN-06`

**Problem.** The two-purchase proration card assigned ORDER/SUB aliases without an HPOS linkage path or reverse/count guards, required two order-item and two queue proofs with no admin session, collapsed both queues into one screenshot, and omitted the deterministic WooCommerce half of both checkout mail sets.

**Fix.** Resolve each order through post-meta JSON with strict numeric guards, add an isolated admin session, capture each exact order and queue independently, require both four-message checkout sets, publish all action handoffs, and close both task sessions.

### C81 · `high` · syn13-decoy-bracket-and-purchase-handoff-gaps

**Tasks:** `SLT-SYN-13`

**Problem.** The temporary parent-decoy bracket bought two variations without recording either order/subscription relationship, complete mail set, persistent-cart proof, or task-session teardown. It also had no restore-first cutoff, so slow checkout evidence could consume the full 90-minute bracket and leave parent decoy metadata active. Future renewal baselines had no D2 action/deadline handoff.

**Fix.** Resolve and cross-check both exact purchases, use separate customer sessions and complete four-message deltas, restore immediately on failure and no later than minute 75, prove the parent clean before release, publish every action/deadline, close the D2 sessions, and keep the card in progress for its dated renewals.

### C82 · `critical` · imp01-sensitive-evidence-and-stale-mail-baseline

**Tasks:** `SLT-IMP-01`

**Problem.** The midnight-boundary card set its checkout mail baseline during user setup, potentially hours before the 23:45 purchase, and requested its readiness screenshot only after hosted card entry. The broad mail delta could absorb unrelated traffic, while the screenshot could expose payment-card data. Its administrator-only user, subscription, order, and Mailpit UI work also had no isolated admin session.

**Fix.** Move `M0` to 23:40 immediately before checkout preparation, capture readiness before any card entry, forbid evidence containing a full card number, name a safe receipt capture, and route all admin/UI evidence through `admin-SLT-IMP-01` with explicit per-leg teardown.

### C83 · `high` · imp01-unresolved-purchase-and-future-gate-handoff

**Tasks:** `SLT-IMP-01`

**Problem.** The card resolved “one new subscription” without an exact HPOS relationship path or reverse/count guards, recorded only one of four deterministic checkout messages, and could close its D2 browser context without publishing the renewal actions and exact baseline deadline needed by the D4 follow-up.

**Fix.** Resolve the sole subscription from the numeric receipt order's `_subscription_ids` JSON with a strict guard, cross-check the reverse/customer/product relationship and +1 count, require the complete four-message checkout set, and publish exact action IDs/times plus `charge−5m` before closing both sessions while leaving the card in progress.

### C84 · `high` · mya05-multi-day-session-and-mail-evidence-leak

**Tasks:** `SLT-MYA-05`

**Problem.** The multi-day member-access card opened its only mail baseline during read-only preflight, omitted screenshot names for the on-hold/cancelled surfaces, and deferred browser teardown until D7 even though its setup, active, on-hold, and cancelled reads occur in four separate phases. A literal run would produce an unbounded cross-task mail delta and leave authenticated sessions dangling for days.

**Fix.** Use a just-in-time bounded baseline for every dated leg, name the missing status screenshots, close the exact sessions after each phase, and reopen/re-authenticate only those needed for the next phase. Keep the shared configuration bracket limited to D2 setup through the immediate D5 teardown.

### C85 · `high` · ren02-estimated-gate-and-incomplete-dated-handoff

**Tasks:** `SLT-REN-02`

**Problem.** The second-control-renewal card retained an obsolete generic charge estimate, did not say when to close sessions across its morning/evening/D3 phases, and listed an admin renewal-order screenshot that no step captured. It also lacked an explicit D02 report handoff for the exact baseline deadline.

**Fix.** Make the handed-off action rows authoritative, publish both rows and `charge−5m` before the morning sessions close, capture the exact renewal order after the natural charge, close sessions per phase, and keep the card in progress until the D3 re-arm/customer read passes review.

### C86 · `critical` · ren05-day-early-baseline-and-ambiguous-renewal-order

**Tasks:** `SLT-REN-05`, `SLT-CHK-05`

**Problem.** The SCA renewal rider captured `PRE5B` during its D2 handoff even though the off-session charge is on D3, reused a literal non-expanding subscription placeholder in its payment-success wait, and selected “one new renewal order” without an exact relationship guard. Its listed verification-email capture also had no executable browser step, and an absent upstream subscription could strand both cards.

**Fix.** Publish the D3 deadline on D2 but capture `PRE5B` only five minutes before the exact charge, use the expanded numeric subject, resolve the pending order bidirectionally with a count guard, add the local Mailpit capture, forbid populated-card evidence, and close a missing-source branch as reviewed `UNVERIFIED` without fabricating a replacement.

### C87 · `high` · ren04-multi-day-admin-evidence-and-closure-gap

**Tasks:** `SLT-REN-04`

**Problem.** The Paddle-renewal rider required private subscription notes and an exact renewal-order screenshot with only a customer session, left that session nominally open across D2-D4, and did not define when its 24-hour no-billing `UNVERIFIED` branch should close the board card.

**Fix.** Add an isolated admin session, publish the exact gate/deadline before D2 teardown, capture the D3 notes and D4 exact renewal order in their named phases, close sessions after every leg, and move the fully reviewed Paddle-timeout conclusion through review to done.

### C88 · `high` · syn02-unexecuted-reorder-and-missing-purchase-authorisation

**Tasks:** `SLT-SYN-02`, `SLT-SYN-13`

**Problem.** The variation audit promised that plans survive reordering but never reordered anything, listed two probe screenshots without capture steps, and closed without publishing the ID-keyed authorised configuration that the later purchase card explicitly consumes. Its isolation note also named the wrong purchasing task.

**Fix.** Add a real save/reload reorder probe keyed by variation ID, restore and diff the original `menu_order`, name both missing captures, publish the final IDs/config/order proof to the registry and D02 report, close the exact admin session, and hand the state to `SLT-SYN-13`.

### C89 · `medium` · d2-product-evidence-without-capture-steps

**Tasks:** `SLT-PROD-02`, `SLT-PROD-03`, `SLT-PROD-15`

**Problem.** Tomorrow's three product cards listed nine required screenshots, but most existed only in their evidence manifests: the steps did not assign filenames to the subscription panels, hidden-flex states, storefront/cart results, or per-variation legends. A literal run could satisfy the behavior while omitting required evidence.

**Fix.** Bind every listed screenshot to the exact step and UI state that owns it, including the zero-total cart, four-day trial storefront summary, all three variation panels, and the Next Cycle cart note.

### C90 · `medium` · dun01-no-final-board-transition

**Tasks:** `SLT-DUN-01`

**Problem.** The two-day first-failure card correctly handed its live fixture to downstream retry tasks but ended after session closure without saying that its own evidence must be reviewed and its execution card closed. A literal runner could leave it in progress for the rest of the campaign.

**Fix.** After the D3 failure delta and retry handoff are complete, close the exact sessions, independently review the D2/D3 evidence, and move `SLT-DUN-01` through review to done while leaving only the subscription itself live.

### C91 · `high` · cpn03-ambiguous-two-purchase-and-mail-contract

**Tasks:** `SLT-CPN-03`

**Problem.** The two-account N-cycle card did not establish subscription-count deltas, reverse relationship checks, safe receipt/checkout captures, or the deterministic WooCommerce half of either checkout mail set. Its broad “classify every message” wording could therefore leave two ambiguous fixtures while still appearing complete.

**Fix.** Record both numeric users and receipt orders, resolve each sole subscription through strict HPOS post-meta JSON with reverse and cumulative count guards, capture totals before card entry plus safe receipts, and require two separate four-message checkout deltas.

### C92 · `critical` · cpn03-multi-day-action-session-and-board-handoff

**Tasks:** `SLT-CPN-03`

**Problem.** The five-day coupon ladder supplied no D2 action/deadline handoff, no per-phase session teardown, no unique renewal screenshot naming, and no final review transition after the second drop-off. A literal run could miss its first renewal baselines and leave three authenticated sessions plus the card open until teardown.

**Fix.** Publish both action sets and first `charge−5m` deadlines before D2 teardown, use exact per-sub/per-cycle baselines and renewal linkage, append each next deadline to the daily report, reopen only the admin session for morning reads, and close the card through review after the D7 drop-off proof.

### C93 · `high` · chk15-orphan-captures-count-and-gate-handoff

**Tasks:** `SLT-CHK-15`, `SLT-EML-09`

**Problem.** The two-trial card listed four cart/form/error screenshots without capture instructions, invoked two subscription-count deltas without a baseline or numeric assertions, and handed `S_TR` to its email rider without publishing the exact reminder/charge deadline set to D02.

**Fix.** Bind every orphan screenshot to its safe pre-card UI state, record a pre-count and cumulative +1/+2 guards with reverse linkage, and publish both exact action sets plus each `gate−5m` deadline before the D2 sessions close.

### C94 · `high` · eml09-cross-day-mail-session-and-issue-proof-gap

**Tasks:** `SLT-EML-09`

**Problem.** The read-only trial-email rider opened its Mailpit browser on D2 and did not close it until D6/D7, used loosely timed future baselines, and prescribed a finding file without the mandatory fixture, context, reproduction, proof, and counterexample fields.

**Fix.** Open and close the exact Mailpit session around each dated capture, publish the selected reminder deadline to D02, capture baselines only in the final five minutes before each gate, and create the standalone issue only after live proof with the complete required context.

### C95 · `high` · eml09-ambiguous-conversion-order

**Tasks:** `SLT-EML-09`, `SLT-CHK-15`

**Problem.** The conversion step said only “the $12 renewal order,” so a shared-site run could attribute a recent unrelated HPOS order to `S_TR` and close both cards on false evidence.

**Fix.** Resolve the paid transition order from the exact HPOS subscription relationship plus scheduled-date/cycle metadata, cross-check the reverse link, and prohibit recency selection before the email and checkout cards close.

### C96 · `medium` · d3-product-orphan-evidence-and-session-teardown

**Tasks:** `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`

**Problem.** The four D3 catalog cards listed sixteen screenshots without binding most filenames to a capture step, left their admin/guest browser teardown implicit, and in the box card did not assign its product creation work to the declared admin session.

**Fix.** Bind every screenshot to the exact panel/cart/wizard state, route all box creation through its admin session, inspect each bounded no-mail delta, and close only the exact task sessions before each catalog card enters review.

### C97 · `high` · d3-product-one-click-cart-displacement

**Tasks:** `SLT-PROD-04`, `SLT-PROD-09`

**Problem.** The signup-fee and grouped-product cards assumed add-to-cart would remain on classic/grouped cart surfaces despite the frozen subscription one-click redirect. Their required fee, multiple-subscription refusal, and mixed-cart evidence could instead be captured on the wrong destination or omitted.

**Fix.** Treat the redirect as expected, record it, explicitly reopen the required classic/standard cart after every subscription add, and then capture the exact fee/refusal/mixed-cart state before cleanup.

### C98 · `critical` · syn04-shared-setting-restore-and-preflight-deadlock

**Tasks:** `SLT-SYN-04`

**Problem.** The exclusive global-sync card deferred restoration until after every positive assertion with no failure jump, required an impossible absence of all routine non-SLT scheduled work, and promised save-specific no-mail checks without setting save-specific baselines. Any mid-bracket browser/payment failure could leave the global setting on, while harmless maintenance could prevent the task from ever starting.

**Fix.** Make restore the immediate response to any post-open failure and prove it before diagnosis, abort only for known order/subscription-mutating conflicts while classifying routine background cron, and bound both settings-save mail deltas with just-in-time baselines.

### C99 · `high` · syn04-one-click-linkage-offset-and-future-handoff

**Tasks:** `SLT-SYN-04`

**Problem.** Both classic-checkout probes ignored the frozen one-click redirect, the purchase had no count guard or safe receipt, its offset command used an invalid literal PHP identifier, and the live day/3 subscription closed without exact future action/deadline handoff. Several gateway/order screenshots were also unnamed.

**Fix.** Explicitly reopen classic checkout after redirects, require the complete four-message purchase and +1 bidirectional relationship, capture only safe named UI states, compute the offset from `$argv[1]`, and publish all natural renewal actions/deadlines before review-to-done.

### C100 · `critical` · adm07-paddle-fallback-contradiction-and-invoice-baseline

**Tasks:** `SLT-ADM-07`, `SLT-CHK-04`

**Problem.** Although the invoice card declared a no-source Paddle fallback, its D4 step still required `SUB_PAD` and aborted unless it existed. It also captured one shared mail baseline before two real invoice windows, making the no-invoice assertion absorb unrelated traffic and leaving renewal orders selectable by portal recency.

**Fix.** Resolve and run Stripe independently, conditionally skip only Paddle, capture a separate final-five-minute baseline per available exact invoice action, and resolve each renewal order bidirectionally from the subscription's pending-order relationship and scheduled cycle.

### C101 · `high` · adm07-phase-a-linkage-evidence-and-multi-day-closure

**Tasks:** `SLT-ADM-07`

**Problem.** The owned `S_FEE` purchase had no count guard, one-click handling, complete four-message requirement, safe receipt, or D4 deadline handoff; seven listed screenshots had no owning steps. The card also left both customer sessions open across D3-D5 and prescribed an underspecified double-charge issue without a final board transition.

**Fix.** Add exact +1 HPOS linkage and the full checkout mail set, safe named checkout/pay receipts, publish every D4 deadline before D3 teardown, close/reopen sessions per phase, require complete standalone issue context if double charge occurs, and review the card to done after the D5 check or Paddle `UNVERIFIED` branch.

### C102 · `high` · adm05-orphan-evidence-issue-and-d4-closure

**Tasks:** `SLT-ADM-05`

**Problem.** The admin-create card listed six UI captures without executable filenames, prescribed a known scheduling issue without mandatory fixture/proof fields, left its D4 renewal order selectable ambiguously, and supplied no per-phase session/card-state transition.

**Fix.** Bind every form/toast/queue/order capture, file the issue only after live inert-activation proof with the On Hold→Active counterexample, publish exact D4 deadlines, resolve the renewal order bidirectionally by cycle, and close the session/card through review after the D4 read.

### C103 · `critical` · adm06-placeholder-order-selection-and-paddle-stranding

**Tasks:** `SLT-ADM-06`, `SLT-REN-04`

**Problem.** The HPOS audit used literal `<S1>/<R1>` placeholders in executable PHP/SQL, discovered renewal IDs from an unguarded customer order list, kept one mail/session window across its deferred Paddle phase, and had no no-source Paddle branch or final review transition. It also treated a missing compatibility link as an informal handoff instead of a standalone finding.

**Fix.** Resolve numeric registry fixtures and exact cycle-2/3 HPOS relationships before browsing, use executable numeric commands and named screenshots, bound and close each dated phase, file the compatibility finding only from complete live proof, and close the Paddle branch as observed or `UNVERIFIED` before review-to-done.

### C104 · `high` · chk09-admin-context-one-click-and-renewal-handoff

**Tasks:** `SLT-CHK-09`

**Problem.** The quantity-three card performed admin-only user/subscription/renewal evidence with no admin session, lost its required cart step to the one-click redirect, left five screenshots unnamed, required only three of four checkout mails, and used a placeholder offset with no dated session/card closure.

**Fix.** Add an isolated admin session, explicitly reopen the cart, capture every safe named state, require the full four-message delta and exact +1 relationship, compute/publish numeric action deadlines, and resolve the natural renewal by relationship before review-to-done.

### C105 · `high` · syn14-baseline-session-evidence-and-finding-gaps

**Tasks:** `SLT-SYN-14`

**Problem.** The prorated-quantity card called an authenticated customer a guest, mixed product-save and checkout mail into one baseline, omitted a count guard and the WooCommerce half of signup mail, left six captures unowned, and described the line-first rounding failure without a standalone issue or final dated closure.

**Fix.** Use explicit admin/customer sessions and per-phase baselines, require the exact +1 relationship and four-message purchase, bind every safe screenshot, publish the 08-08 action deadline, resolve the renewal by cycle, and file/review any rounding finding before moving the card to done.

### C106 · `critical` · cpn04-one-click-linkage-and-mail-window-gaps

**Tasks:** `SLT-CPN-04`

**Problem.** The classic coupon card could be displaced by the frozen one-click redirect, selected its resulting subscription ambiguously, combined coupon probes and checkout mail under one baseline, and required only the ArraySubs half of the paid-order message set.

**Fix.** Explicitly reopen classic cart/checkout after redirects, isolate probe and purchase mail windows, require the complete four-message checkout set, and resolve the sole new subscription through strict bidirectional HPOS linkage plus a numeric +1 count guard.

### C107 · `high` · cpn04-safe-evidence-action-and-board-handoff

**Tasks:** `SLT-CPN-04`

**Problem.** Six captures had no executable filenames, the populated hosted card could be exposed, the offset/action handoff was not executable, and the D3/D5 session and board lifecycle had no explicit closure.

**Fix.** Name every safe pre-card/receipt/admin/renewal capture, prohibit populated-card images, compute the offset with numeric argv, publish exact action deadlines before D3 teardown, resolve renewal by relationship, and close the D5 card through review while keeping any live finding solely in a standalone issue file.

### C108 · `high` · eml01-baseline-evidence-and-cross-day-closure

**Tasks:** `SLT-EML-01`

**Problem.** The D3 reminder card captured its D4 mail baseline at an unspecified early time, left every screenshot orphaned, kept its admin session open across days, and supplied no final board transition after the dedupe probe.

**Fix.** Publish the exact D4 action and final-five-minute baseline deadline before D3 teardown, bind all action/Mailpit captures to named sessions and steps, record the duplicate action ID, and close the D4 task through review with any live finding only in a standalone issue file.

### C109 · `critical` · eml03-paddle-fallback-deadlock-and-order-selection

**Tasks:** `SLT-EML-03`

**Problem.** The task's first executable step aborted unless `SUB_PAD` was numeric, contradicting its authored no-source Paddle fallback, while the Stripe renewal order was selected from a recent Orders view rather than its exact subscription/cycle relationship.

**Fix.** Resolve Stripe and Paddle independently, close only the absent Paddle leg as `UNVERIFIED`, and require bidirectional relationship/cycle resolution for every available renewal order.

### C110 · `high` · eml03-evidence-issue-and-review-closure

**Tasks:** `SLT-EML-03`

**Problem.** Its pending/order/mail captures were ambiguous or orphaned, its known Paddle date finding lacked the mandatory issue context, and the task supplied no exact session teardown or final review transition.

**Fix.** Name and bind every conditional Stripe/Paddle capture, use bounded Mailpit sessions, require the complete standalone issue fields only after live divergence, and close both the full and no-source paths through review to done.

### C111 · `critical` · eml07-early-baseline-session-and-terminal-closure

**Tasks:** `SLT-EML-07`

**Problem.** The scheduled-cancel card captured its D4 mail baseline during D3, left browser contexts and screenshots implicit, prescribed a potentially unavailable repeat-cancel control, and supplied neither exact gate handoff nor final board closure.

**Fix.** Publish the literal action and final-five-minute baseline deadline before D3 teardown, make the duplicate probe executable for both UI states, bind every safe screenshot/session, require complete terminal mail/action evidence, and close the D4 card through review with findings only in standalone issue files.

### C112 · `critical` · eml11-checkout-linkage-and-nonexact-setting-restore

**Tasks:** `SLT-EML-11`

**Problem.** The harness purchase had no one-click recovery, safe receipt, count/linkage guard, or complete checkout-mail set, while a previously absent WooCommerce option was deliberately left behind after the global-setting test.

**Fix.** Require safe exact +1 bidirectional checkout evidence and four messages, preserve option presence as well as value, and restore the absent baseline by deleting the temporary row after the live ON proof.

### C113 · `critical` · eml11-unbounded-bracket-evidence-and-session-closure

**Tasks:** `SLT-EML-11`

**Problem.** The global email-off mutation had no queue/board preflight, duration limit, restore-first branch, owned screenshot steps, explicit task-session teardown, or final board transition.

**Fix.** Bound the exclusive bracket to 20 minutes with immediate restoration on failure, bind every safe capture and Mailpit session, prove empty carts, and close all paths through review with product findings confined to standalone issue files.

### C114 · `critical` · eml15-observes-past-cycle-and-misses-invoice-baseline

**Tasks:** `SLT-EML-15`

**Problem.** The D3 card claimed to observe renewal #2 due on D2 and attempted to capture its invoice baseline only after that window had already passed, making the central zero-mail proof impossible.

**Fix.** Target renewal #3 due D3, arm its exact invoice/charge IDs and baseline deadlines from the D2 watcher after renewal #2 advances the date, and carry those bounded deltas into D3 without forcing either action.

### C115 · `high` · eml15-recency-evidence-issue-and-board-gaps

**Tasks:** `SLT-EML-15`

**Problem.** The renewal order was selected from a customer-filtered recent list, all five screenshots lacked owning sessions/steps, and UNMAPPED mail had no complete standalone issue or final review contract.

**Fix.** Resolve the pending/paid order bidirectionally from the subscription and cycle, bind every action/order/Mailpit capture, close each bounded session, and finish through review with any unmapped product finding solely in a fully evidenced issue file.

### C116 · `critical` · eml12-nonexact-option-restore-and-failure-path

**Tasks:** `SLT-EML-12`

**Problem.** The override test preserved an absent option but deliberately left a populated blank row, and any failure after the non-default save could strand a site-wide email subject/body override through the rest of D3.

**Fix.** Preserve and restore storage presence as well as value, prove the default render before deleting an originally absent row, and make UI/default plus exact storage restoration the immediate failure path.

### C117 · `high` · eml12-save-baseline-evidence-issue-and-review-gaps

**Tasks:** `SLT-EML-12`

**Problem.** The two settings saves had no bounded mail baselines, all five screenshots lacked owning steps/sessions, and the fabricated-date finding and final board/session closure were incomplete.

**Fix.** Isolate both save deltas, bind every admin/Mailpit capture, require complete standalone issue context after live proof, and close the restored card through review with exact sessions terminated.

### C118 · `critical` · d1-coupon-count-command-and-renewal-selection-gaps

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`

**Problem.** Both live D1 checkout cards promised a subscription-count delta without ever recording its baseline, used placeholder/grep commands, and later selected renewal orders without an exact subscription/cycle relationship.

**Fix.** Record and assert numeric +1 counts with strict bidirectional parent linkage, use executable argv/rg commands and exact action rows, and resolve both future renewals by subscription and scheduled cycle rather than recency.

### C119 · `high` · d1-coupon-safe-evidence-issue-and-final-closure

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`

**Problem.** Empty-cart and checkout captures were unnamed, populated hosted-card evidence was not explicitly forbidden, multi-day sessions/review closure were incomplete, and the known one-time wording issue lacked the mandatory standalone context.

**Fix.** Bind every safe before-card/receipt/cart/renewal capture, close sessions per phase, require complete standalone issue fields after live proof, and finish both cards through review only after their second renewal.

### C120 · `critical` · chk08-two-purchase-linkage-one-click-and-mail-gaps

**Tasks:** `SLT-CHK-08`

**Problem.** The two-purchase migration negative lacked an admin context and cumulative count guards, lost its second cart proof to one-click checkout, omitted safe receipts and the WooCommerce half of both mail sets, and handed neither subscription to the natural watcher.

**Fix.** Use isolated admin/customer sessions, require cumulative +1/+2 bidirectional relationships, reopen cart after redirect, capture two safe receipts and complete four-message deltas, and publish both exact renewal action sets before review.

### C121 · `critical` · chk13-box-linkage-one-click-evidence-and-watch-handoff

**Tasks:** `SLT-CHK-13`

**Problem.** The box checkout selected order/subscription implicitly, assumed add-to-box remained on cart despite one-click mode, used admin UI without an admin session, omitted safe receipt/count/four-message proof, and left all captures and future gates unowned.

**Fix.** Require +1 bidirectional receipt linkage, explicit cart reopen, separate admin/customer contexts, safe named evidence, the complete checkout mail set, exact future action handoff, empty carts, and final review closure.

### C122 · `critical` · eml13-nonexact-four-option-restore-and-failure-path

**Tasks:** `SLT-EML-13`

**Problem.** The four-option global email test accepted newly populated residue instead of restoring absent options and had no immediate restoration path if any browser/mail assertion failed inside its 20-minute exclusive bracket.

**Fix.** Preserve existence and value per option, prove the live enabled state, then delete or restore each row exactly; make that full restoration the first response to any post-disable failure.

### C123 · `high` · eml13-save-baseline-evidence-issue-and-review-gaps

**Tasks:** `SLT-EML-13`

**Problem.** Eight screenshots and both settings-save deltas lacked exact ownership, the silence finding contract was incomplete, and sessions/card closure remained implicit.

**Fix.** Bind every admin/Mailpit capture, isolate OFF/ON save deltas, require complete standalone issue context after live proof, and close the exact sessions and reviewed card before 08:20.

### C124 · `critical` · dun03-blocking-wait-sweep-session-and-board-gaps

**Tasks:** `SLT-DUN-03`

**Problem.** The hard-gated grace test prescribed 60- and 30-minute blocking waits, did not identify the exact hourly sweeps, left evidence/session names incomplete, and had no fully contextual issue or review closure.

**Fix.** Select and publish the two eligible natural sweep gates, poll in intervals of at most 60 seconds with one bounded baseline, bind all admin/customer captures, and close the evidenced pass/fail path through review without forcing a sweep.

### C125 · `critical` · eml02-literal-action-search-and-nonexact-meta-restore

**Tasks:** `SLT-EML-02`

**Problem.** The invoice card searched Action Scheduler for literal `SUB_CORE`, assumed `_auto_renew` was originally absent, and could strand its manual-payment override if the mail/pay-link path failed before teardown.

**Fix.** Interpolate the numeric subscription, preserve/restore exact meta existence and value, and make restoration the immediate failure response with the natural charge gate as a hard deadline.

### C126 · `high` · eml02-order-selection-safe-evidence-issue-and-final-closure

**Tasks:** `SLT-EML-02`

**Problem.** Its invoice/pay screenshots and Mailpit context were orphaned, the paid renewal order was selectable by recent Orders view, and neither the missing-invoice issue nor D5 session/review closure was complete.

**Fix.** Bind safe pre-card/receipt/mail captures, resolve the order from pay URL plus bidirectional subscription/cycle relationship, require complete standalone issue context, and close the restored D5 path through review.

### C127 · `critical` · adm03-early-baseline-count-and-renewal-selection-gaps

**Tasks:** `SLT-ADM-03`

**Problem.** The admin-created canvas lacked a +1 count guard, captured its D5 invoice baseline during D4, and selected its eventual renewal order without an exact subscription/cycle relationship.

**Fix.** Assert the new subscription count, publish the future deadline then capture the baseline in the final five minutes, and resolve the D6 pending order bidirectionally from SUB-B and scheduled cycle.

### C128 · `high` · adm03-evidence-rest-probe-issue-and-review-closure

**Tasks:** `SLT-ADM-03`

**Problem.** Queue/edit captures were not tied to filenames, the REST negative lacked before/after mail/date guards, and failures plus multi-day session/card closure were unspecified.

**Fix.** Bind every queue/edit/order capture, bound the REST probe with unchanged state/mail evidence, require complete standalone issue context, and close the D4-D6 execution through review with exact sessions.

### C129 · `critical` · imp03-three-purchase-count-mail-and-baseline-gaps

**Tasks:** `SLT-IMP-03`

**Problem.** The concurrency fixture lacked cumulative count guards, safe receipts, WooCommerce checkout mail, executable offset arguments, and just-in-time per-subscription baseline handoffs.

**Fix.** Require +1/+2/+3 bidirectional purchases with three complete four-message deltas, safe evidence, argv offsets, exact action deadlines, and independent final-five-minute renewal baselines.

### C130 · `high` · imp03-route-order-log-issue-and-review-gaps

**Tasks:** `SLT-IMP-03`

**Problem.** It used the wrong scheduler route, selected D5 renewal orders broadly, used grep, treated a possible hash collision as a product failure, and omitted issue/session/review closure.

**Fix.** Use the real scheduler route, resolve orders by cycle relationship, inspect runtime logs with rg, close a hash collision as fixture-limited, and finish evidenced failures only in standalone files before review-to-done.

### C131 · `high` · mya01-orphan-detail-access-issue-and-review-gaps

**Tasks:** `SLT-MYA-01`

**Problem.** Four required detail/access probes had no named evidence or exact IDs, and the read-only portal audit supplied no standalone-failure, session, or board closure.

**Fix.** Resolve every registry ID, bind nine list/detail/denial captures, require contextual standalone findings after live proof, and close the exact read-only session through review.

### C132 · `high` · prod08-orphan-variation-storefront-and-review-gaps

**Tasks:** `SLT-PROD-08`

**Problem.** Variation/admin and four distinct storefront states were collapsed into orphan captures, the mail baseline was unnamed, and sessions/findings/card closure were implicit.

**Fix.** Bind every variation and tier state, reconcile the named mail baseline, close exact admin/guest sessions, and route any live failure to a standalone issue before review-to-done.

### C133 · `critical` · sw00-two-purchase-baseline-linkage-and-review-gaps

**Tasks:** `SLT-SW-00`

**Problem.** The ladder seed referenced two purchase baselines but defined one, selected subscriptions implicitly, lacked count/safe receipt/admin/action handoff proof, and supplied no final review transition.

**Fix.** Use separate four-message baselines, cumulative +1/+2 bidirectional linkage, safe named receipts, an admin detail/action context, exact future deadlines, and session-bounded review closure.

### C134 · `critical` · syn07-linkage-command-mail-and-d8-handoff-gaps

**Tasks:** `SLT-SYN-07`

**Problem.** The segment-3 purchase used literal placeholders in executable date/action commands, selected its subscription implicitly, omitted count/admin/Woo mail proof, and left its D8 forced-action handoff and review closure unspecified.

**Fix.** Resolve numeric registry/order relationships, use argv date math, require +1/four-message/admin evidence, publish exact reminder/invoice/charge gates to the D8 owner, and close exact sessions through review.

### C135 · `critical` · syn07-unassigned-date-command-input

**Tasks:** `SLT-SYN-07`

**Problem.** The corrected executable date-delta command consumed `SUB_W3_NEXT`, but the task never assigned or validated that shell variable.

**Fix.** Read the exact numeric subscription's `_next_payment_date` into `SUB_W3_NEXT`, validate one UTC datetime, and only then execute the argv delta assertion.

### C136 · `critical` · sw09-multi-purchase-and-multi-day-selection-gaps

**Tasks:** `SLT-SW-09`

**Problem.** The retention fixture selected both purchase subscriptions and six later renewal orders implicitly, lacked cumulative counts, safe receipts, complete per-purchase mail sets, exact final-five-minute baselines, and final multi-day review closure.

**Fix.** Require +1/+2 bidirectional purchases, card-safe named evidence, two complete four-message deltas, exact action/cycle-owned renewal selection and baselines, phase-local sessions, standalone issue files, and a final D9 review-to-done transition.

### C137 · `critical` · syn11-auth-linkage-mail-action-and-review-gaps

**Tasks:** `SLT-SYN-11`

**Problem.** The exclusivity probe logged a customer into a guest-labelled session, selected three created subscriptions and order items implicitly, lacked cumulative count and complete mail proof, and did not publish exact conversion/renewal handoffs or close through review.

**Fix.** Use explicit admin/customer sessions, require +1/+2/+3 bidirectional linkage, numeric exact-object meta reads, safe captures, complete A/C and trial/Woo deltas, exact A/B action deadlines plus C negatives, standalone issue files, and D4 review closure.

### C138 · `high` · sw09-unresolved-ladder-product-placeholder

**Tasks:** `SLT-SW-09`

**Problem.** The corrected retention checkout still left Plan Basic as a literal numeric-ID placeholder and referred to Plan Peer and the downgrade target by title only.

**Fix.** Resolve and validate distinct `BASIC_ID`/`PEER_ID` values from the registry once, then use those exact variables for both purchases and the downgrade target.

### C139 · `critical` · adm09-impossible-success-row-and-deferred-closure-gaps

**Tasks:** `SLT-ADM-09`

**Problem.** The notes/audits card implicitly selected subscriptions and notes, requested both a failed and successful job row for the failing fixture, and deferred its cancellation-note proof to D7 without a natural gate, phase session, issue contract, or final board transition.

**Fix.** Resolve all exact source IDs, compare the failure on S_FAIL with the success on S1, select manual/cancellation notes by set difference, bind every audit capture, consume the published natural D7 sweep, and close standalone findings plus the final card through review.

### C140 · `high` · chk06-one-click-evidence-and-ux-finding-gaps

**Tasks:** `SLT-CHK-06`

**Problem.** The multi-subscription rejection flow assumed each add stayed on its product/cart surface despite frozen one-click checkout, left several cart states uncaptured, and did not specify how its authored silent-archive UX finding or final card should close.

**Fix.** Reopen and assert exact carts after every redirect-capable add, bind all product/cart/archive evidence and byte comparison, route a silent rejection only to a contextual standalone issue file, and finish through review.

### C141 · `critical` · chk10-partial-user-search-literal-order-and-hpos-negative-gaps

**Tasks:** `SLT-CHK-10`

**Problem.** The anonymous checkout used partial grep searches, omitted one required option check, left the paid order literal, checked only one possible subscription meta, and lacked card-safe/control/session/issue/review closure.

**Fix.** Use exact JSON user guards and numeric products/order, verify both guest options and all relationship metas, capture only unpopulated checkout plus safe receipt states, complete the two-message delta and subscription control, and close standalone findings through review.

### C142 · `critical` · chk11-preview-purchase-linkage-and-renewal-handoff-gaps

**Tasks:** `SLT-CHK-11`

**Problem.** The variation card mixed preview and purchase mail windows, selected two subscriptions implicitly, lacked Woo mail/count/safe receipts and one-click recovery, and left both natural renewal legs without exact baselines, relationships, sessions, or final closure.

**Fix.** Separate preview/Starter/Plus boundaries, require +1/+2 bidirectional variation linkage and four-message sets, bind safe evidence and exact actions, then reconcile each natural renewal from a final-five-minute baseline before review-to-done.

### C143 · `critical` · chk12-grouped-linkage-mail-watch-and-review-gaps

**Tasks:** `SLT-CHK-12`

**Problem.** The exploratory grouped purchase lacked numeric parent/child/user/count/linkage guards, one-click recovery, safe receipt, Woo mail, exact admin/order evidence, and an executable child-only renewal watch or final closure.

**Fix.** Resolve every artifact, require +1 bidirectional child linkage and a complete four-message delta, bind the grouped probes/order diff safely, publish the exact natural renewal deadline, and separate observations from standalone actionable issues before final review.

### C144 · `critical` · dun02-early-baselines-blocking-waits-note-recency-and-closure

**Tasks:** `SLT-DUN-02`

**Problem.** Retry baselines were captured ten minutes or a day early, waits blocked for 900 seconds, the cap note was selected as newest, browser state crossed days, and no standalone-issue or final review path existed.

**Fix.** Capture each owner baseline in its final five minutes, poll in intervals of at most 60 seconds, resolve actions/orders/notes exactly, close phase sessions, and finish D6 findings solely in issue files before review.

### C145 · `critical` · eml04-literal-route-long-waits-session-and-final-closure-gaps

**Tasks:** `SLT-EML-04`

**Problem.** The dunning-email audit searched the scheduler for literal S_FAIL, prescribed 3600/1800-second waits, reused browser sessions across days, selected notes broadly, and did not complete required standalone issue context or D7 review closure.

**Fix.** Interpolate exact IDs, consume final-five-minute boundaries with ≤60-second polling, bind cycle-owned notes/pay pages and phase sessions, require full contextual issue files, and close the cancellation evidence through review.

### C146 · `critical` · imp02-recency-fabricated-payload-log-and-closure-gaps

**Tasks:** `SLT-IMP-02`

**Problem.** The replay plan selected newest unrelated events/logs, rebuilt a Paddle payload from two columns, used grep, compared broad recent rows, and supplied neither a safe missing-payload branch nor complete issue/session/review closure.

**Fix.** Require exact order-owned event IDs and byte-identical payload provenance, keyed pre/post sets and log cursors with rg, sanitized secrets/responses, half-specific UNVERIFIED handling, standalone issues, and final review.

### C147 · `critical` · life03-long-waits-wrong-route-early-baseline-and-restoration-gaps

**Tasks:** `SLT-LIFE-03`

**Problem.** The skip bracket used a legacy scheduler route with literal S5, conflated mutation mail windows, prescribed 90/900-second waits, captured the shifted baseline early, and lacked restore-first, exact order, phase-session, issue, and final closure.

**Fix.** Use exact numeric actions/routes, distinct bounded mutation baselines, ≤60-second polling, exact snapshot restoration, final-five-minute natural gates and cycle-owned orders, phase sessions, standalone issues, and D7 review closure.

### C148 · `critical` · mya02-literal-ids-stranding-branch-and-renewal-selection-gaps

**Tasks:** `SLT-MYA-02`

**Problem.** The payment-method plan contained literal ID arrays, could STOP mid-card when the token existed, waited broadly, captured the renewal baseline early, and selected target/control renewals without exact actions or cycle relationships.

**Fix.** Resolve the complete numeric owner set, close contamination as reviewed UNVERIFIED, poll ≤60 seconds, require exactly one target plus exact control, consume final-five-minute actions/orders, protect card data, and finish standalone findings through review.

### C149 · `critical` · sw01-literal-ids-order-linkage-early-baseline-and-final-closure

**Tasks:** `SLT-SW-01`

**Problem.** The upgrade plan used literal subscription/order IDs in executable commands, did not count or link the switch order exactly, lacked safe card-state rules, captured renewal mail early, and provided no phase/session/standalone-issue/final review path.

**Fix.** Resolve all numeric artifacts, require +1 bidirectional switch-order linkage and argv reads, bind safe pay/receipt evidence, publish/take the exact final-five-minute natural renewal boundary, and close contextual findings through review.

### C150 · `critical` · cpn01-blocking-waits-ambiguous-baselines-and-issue-context

**Tasks:** `SLT-CPN-01`

**Problem.** The live recurring-coupon card still used 120/900-second waits, described its renewal baseline as at least five minutes early, reused a cross-day admin session, and left generic failure files without the mandatory QA context.

**Fix.** Poll in ≤60-second calls, capture both renewal baselines only in their final five minutes, use cycle-keyed sessions, and require complete standalone issue fields before final review.

### C151 · `critical` · cpn02-blocking-waits-ambiguous-baselines-and-generic-failure-path

**Tasks:** `SLT-CPN-02`

**Problem.** The one-time coupon card retained the same long waits/early-baseline/session risks and specified full standalone context only for its wording finding, not for other live failures.

**Fix.** Use final-five-minute owner boundaries with ≤60-second polling and phase sessions, and route every failure through the complete standalone issue-file contract before review.

### C152 · `critical` · sw06-literal-linkage-card-mail-and-renewal-closure-gaps

**Tasks:** `SLT-SW-06`

**Problem.** The second upgrade fixture used literal product/subscription IDs, could STOP on cart contamination, selected both orders/subscription/renewal implicitly, lacked safe receipts and complete mail sets, captured its renewal baseline early, and had no final review path.

**Fix.** Resolve numeric products and +1/+2 relationships, recover safely from task-user cart contamination, bind card-safe parent/switch evidence and mail deltas, consume the exact final-five-minute natural renewal, and close contextual findings through review.

### C153 · `critical` · syn12-auth-count-linkage-mail-and-unavailable-paddle-gaps

**Tasks:** `SLT-SYN-12`

**Problem.** The gateway-gating card used guest-labelled sessions for logged-in buyers, selected two created orders/subscriptions implicitly, lacked cumulative counts/safe receipts/full mail sets, proved rejection through a recent Orders view, and had no safe unavailable-Paddle or final review branch.

**Fix.** Use explicit admin/customer sessions, exact option/probe/count/linkage and four-message boundaries, card-safe captures, before/after forced-submit counts/network proof, half-specific UNVERIFIED handling, exact future actions, standalone issues, and review closure.

### C154 · `critical` · adm04-long-waits-upstream-state-and-review-gaps

**Tasks:** `SLT-ADM-04`

**Problem.** The status ladder used 120-second waits, could replay an already-natural on-hold transition, left repeated action evidence implicit, and lacked full standalone-issue/session/review closure.

**Fix.** Resolve exact source relationships and state sets, consume or mark the upstream leg without replay, poll ≤60 seconds, bind every queue/mail transition, and finish contextual findings through review.

### C155 · `critical` · chk07-one-click-count-mail-renewal-and-closure-gaps

**Tasks:** `SLT-CHK-07`

**Problem.** The mixed-cart purchase could lose its authored composition under one-click redirect, used literal order commands, omitted count/admin-Woo/safe-receipt proof, captured renewal mail early, selected the renewal broadly, and never closed sessions/issues/review.

**Fix.** Resolve numeric products, recover/reassert the mixed cart, require +1 order/subscription linkage and four-message mail, bind card-safe evidence, consume an exact final-five-minute natural renewal, and close standalone findings through review.

### C156 · `critical` · eml05-hour-waits-cross-cycle-sessions-and-evidence-mismatch

**Tasks:** `SLT-EML-05`

**Problem.** The multipart-email card authorized hour-long and 15-minute Mailpit waits, retained authenticated sessions across a later natural cycle, named raw HTML evidence as PNG screenshots, and supplied no final standalone-issue or review closure.

**Fix.** Poll immutable baselines in calls of at most 60 seconds, close the mutation phase before the post-restore cycle, name raw message parts accurately, require full contextual standalone findings, and close the final natural-cycle evidence through review.

### C157 · `critical` · imp04-three-purchase-linkage-mail-card-and-renewal-gaps

**Tasks:** `SLT-IMP-04`

**Problem.** The three destructive edge probes omitted card-safe checkout captures, exact cumulative order/subscription relationships, complete four-message purchase sets, exact action handoffs, final-five-minute renewal baselines, phase-local sessions, and final review closure.

**Fix.** Bind every purchase to its receipt and +1/+2/+3 counts, keep hosted card data out of evidence, reconcile independent complete mail sets, publish exact action deadlines, take each baseline in its final five minutes, resolve cycle-owned renewals, and close all branches through standalone issues and review.

### C158 · `high` · life02-admin-paddle-session-order-and-review-gaps

**Tasks:** `SLT-LIFE-02`

**Problem.** The early-renew card lacked an admin session for queue evidence, did not derive the paid order from the exact REST/subscription relationship, used a 120-second wait, attempted a Paddle-negative read while authenticated as another customer, and never completed issue/review closure.

**Fix.** Assign isolated admin and Paddle-customer sessions, require the numeric response order and reverse subscription linkage, poll at most 60 seconds, verify exact replacement actions, and finish contextual findings through independent review.

### C159 · `critical` · mya03-card-capture-admin-renewal-and-cross-day-closure-gaps

**Tasks:** `SLT-MYA-03`

**Problem.** The Paddle payment-method audit ambiguously captured a hosted card page, read admin notes without an admin session, waited 15 minutes in one call, selected the remote renewal without exact transaction linkage, retained sessions across days, and did not require the confirmed missing local surface to be a standalone issue.

**Fix.** Capture only the safe post-submit state, use isolated admin phases, take the remote baseline in the final five minutes and poll at most 60 seconds, bind the renewal to the exact transaction/subscription, close every phase session, and file the fully contextual live surface defect before review.

### C160 · `critical` · sw03-unobserved-renewal-and-final-closure-gap

**Tasks:** `SLT-SW-03`

**Problem.** The crossgrade card claimed the next renewal stayed at $15.00 but stopped immediately after the switch, with no exact future action handoff, baseline, relationship-exact renewal, cron/mail evidence, or final review path.

**Fix.** Publish the replacement actions and deadline, close the switch session, take a final-five-minute renewal baseline, resolve the exact scheduled-cycle order with cron and mail proof, and finish any standalone issue plus independent review.

### C161 · `critical` · sw05-fabricated-completion-checkout-and-remote-renewal-gaps

**Tasks:** `SLT-SW-05`

**Problem.** The Paddle-switch plan would manually mark an unpayable proration order Completed, fabricating the downstream state; it also lacked safe/count-exact parent linkage, bounded webhook polling, phase teardown, exact remote-renewal linkage, complete issue context, and final review closure.

**Fix.** Remove the manual-completion fallback, close an unavailable payment path as evidenced UNVERIFIED, require card-safe +1 receipt linkage and ≤60-second settlement polling, bind the remote renewal to its transaction/subscription, and file the confirmed price divergence only as a standalone issue before review.

### C162 · `critical` · sw07-setup-baseline-purchase-switch-mail-and-closure-gaps

**Tasks:** `SLT-SW-07`

**Problem.** The variation-switch card set its checkout baseline before an admin configuration step, selected its parent and switch orders implicitly, omitted count/card/complete-mail proof, used long waits, and supplied no future queue handoff, standalone-issue contract, or review closure.

**Fix.** Separate setup and just-in-time checkout boundaries, require card-safe +1 receipt and exact switch-response linkage, poll all action deltas at most 60 seconds, publish replacement legs, and finish the reverted probes through contextual standalone findings and review.

### C163 · `critical` · sw10-long-waits-cross-day-sessions-and-reactivation-closure-gaps

**Tasks:** `SLT-SW-10`

**Problem.** The pending-cancellation card used 180/900-second waits, retained D6 sessions through a D7 natural action, took an imprecise cancellation baseline, omitted safe/count-exact purchase evidence, and lacked a bounded contextual issue/review closeout for the known reactivation scheduling risk.

**Fix.** Require safe +1 checkout linkage and the full mail set, close D6 sessions after exact action handoff, take the cancellation baseline only in its final five minutes, poll in ≤60-second calls with cron/order negatives, and close the D7 reactivation finding solely through a standalone issue and review.

### C164 · `critical` · syn10-early-baselines-purchase-linkage-renewal-and-review-gaps

**Tasks:** `SLT-SYN-10`

**Problem.** The month-overflow card set both purchase and forced-renewal mail baselines too early, used long waits, omitted count/card-safe receipt evidence and the complete checkout set, selected the new renewal broadly, and had no standalone-issue or final review path.

**Fix.** Set both baselines immediately before their owned mutations, require safe +1 purchase linkage and four-message checkout proof, poll at most 60 seconds, resolve the single scheduled-cycle renewal with reverse linkage, and finish the empty non-SLT diff through contextual findings and review.

### C165 · `high` · adm01-stale-count-search-evidence-paddle-fallback-and-review-gaps

**Tasks:** `SLT-ADM-01`

**Problem.** The read-only list audit hard-coded the original 354-subscription count, reused one filename for four searches, assumed a Paddle source always existed, and supplied neither complete issue context nor independent review closure.

**Fix.** Resolve the live count and registry SLT set, name every search capture, honor the source task's unavailable-Paddle branch without substituting another record, re-prove the cancelled target after the cancelled dialog, and close only live non-destructive findings through review.

### C166 · `critical` · dun04-hour-waits-early-baseline-and-terminal-closure-gaps

**Tasks:** `SLT-DUN-04`

**Problem.** The terminal grace card captured mail 30+ minutes early, allowed two one-hour waits, did not bind the failed order/sweep exactly, and supplied neither exact session teardown nor complete standalone-issue/review closure.

**Fix.** Resolve the numeric subscription/order and eligible sweep, capture the baseline only in its final five minutes, poll at most 60 seconds through sweep+5 minutes without forcing, and close the exact cron/order/mail/UI evidence through review.

### C167 · `critical` · dun05-early-baselines-cross-day-sessions-card-and-order-gaps

**Tasks:** `SLT-DUN-05`

**Problem.** The recovery ladder set both failure and recovery baselines early, used 900/300-second waits, retained sessions from D7 through D9, omitted card-safe/count-exact parent proof, and selected the failed order without a strict scheduled-cycle relationship.

**Fix.** Use just-in-time purchase and recovery boundaries plus a final-five-minute failure boundary, safe +1 receipt linkage, ≤60-second polling, exact cron/cycle order resolution, phase-keyed sessions, contextual standalone issues, and final review.

### C168 · `critical` · mya04-recency-selection-early-baseline-and-next-day-closure-gaps

**Tasks:** `SLT-MYA-04`

**Problem.** The unpaid-invoice portal card selected the newest slt-core order, captured its invoice boundary imprecisely, used a five-minute blocking wait, retained a customer session overnight, and had no standalone-issue or D8 review closeout.

**Fix.** Capture the exact invoice baseline in its final five minutes, derive the order from subscription/scheduled-cycle linkage, poll at most 60 seconds, prove the recorded charge action is a cron no-op against exact order sets, close the customer phase, and finish the D8 read through review.

### C169 · `critical` · sw04-cart-stranding-parent-switch-linkage-mail-and-review-gaps

**Tasks:** `SLT-SW-04`

**Problem.** The admin-switch comparison could strand on a fresh user's cart, omitted card-safe/count-exact parent purchase proof and the four-message set, selected the proration order implicitly, and lacked replacement-action, standalone-issue, and review closure.

**Fix.** Preserve then clear only task-user cart contamination, require a safe +1 parent receipt and exact switch-response order, poll bounded complete deltas, verify replacement schedule math, and close both sessions and contextual findings through review.

### C170 · `critical` · sw08-non-restoring-failure-path-order-renewal-and-long-wait-gaps

**Tasks:** `SLT-SW-08`

**Problem.** The global switch-fee bracket restored only on the happy path, used grep and a 15-minute wait, selected switch/renewal orders broadly, captured renewal mail early, and provided no full issue/session/review closure.

**Fix.** Make exact-prior restoration the first action on every exit path, use rg, bind the response-created switch order and natural scheduled-cycle renewal, take the renewal baseline in its final five minutes, poll at most 60 seconds, and close the proven empty settings diff through review.

### C171 · `high` · syn09-overwritten-captures-order-recency-and-d10-closure-gaps

**Tasks:** `SLT-SYN-09`

**Problem.** The multi-subscription sync audit overwrote shared screenshot names, selected renewal orders by customer lists, captured its D10 baseline imprecisely, retained one admin session across days, and omitted contextual issue/review closure.

**Fix.** Use alias/cycle-keyed captures and strict scheduled-cycle relationships, publish/take the D10 baseline in its final five minutes, poll at most 60 seconds in a fresh phase, and close all five events through review.

### C172 · `high` · eml08-reactivation-wait-session-handoff-and-review-gaps

**Tasks:** `SLT-EML-08`

**Problem.** The three-transition email rider used a 90-second wait, did not bind/close its customer reactivation session explicitly, and supplied no full issue or review path before handing active S_EML downstream.

**Fix.** Use a just-in-time immutable baseline with ≤60-second polling, verify exact subscription/meta/mail state, publish the downstream handoff, close the exact session, and finish contextual findings through review.

### C173 · `critical` · eml10-false-empty-history-cron-race-long-waits-and-closure-gaps

**Tasks:** `SLT-EML-10`

**Problem.** The expiring-soon plan required an empty all-status history even though SLT-LIFE-04 owns a known targeted row, scheduled new probes in the past where cron could race the UI, used 120/90-second waits, and lacked explicit admin/customer/session/review and full issue context.

**Fix.** Assert zero pending/natural provenance while classifying targeted history, park each probe 12 hours ahead before exact-ID UI execution, poll at most 60 seconds, bind both sessions and teardown mail, and close standalone findings through review.

### C174 · `high` · eml14-order-recency-session-and-final-review-gaps

**Tasks:** `SLT-EML-14`

**Problem.** The long-window negative sweep used customer-newest orders as proof, lacked phase-keyed admin sessions, and had no D12 standalone-issue or final review closure.

**Fix.** Use exact fixture-owned order/action sets, close D8 and re-open D12 independently, retain the immutable watch boundary, and finish the full silence table only through contextual issues and review.

### C175 · `critical` · life01-long-waits-broad-orders-stale-nonslt-and-recovery-session-gaps

**Tasks:** `SLT-LIFE-01`

**Problem.** The late-renewal probe used five-minute waits, selected both new orders broadly, hard-coded the original 13 non-SLT count, left a three-hour recovery watch/session ambiguous, and omitted complete issue/review closure.

**Fix.** Poll action/mail state at most 60 seconds, require anchor/cycle-owned orders, diff the live shared non-SLT set, close/reopen short recovery sessions while the card remains active, and verify a healthy exact-pair teardown before review.

### C176 · `critical` · sw02-order-mail-followup-and-terminal-closure-gaps

**Tasks:** `SLT-SW-02`

**Problem.** The two-leg downgrade card used a five-minute blocking wait, proved no Leg-A order through a broad customer screen, left the D9 detached/manual renewal vague, retained its portal session, and omitted full issue/review closure.

**Fix.** Compare exact order sets, poll at most 60 seconds, publish exact post-downgrade actions and a relationship-owned D9 manual outcome, close phase sessions, and finish contextual findings through review.

### C177 · `critical` · tt00-early-baseline-long-waits-renewal-selection-and-review-gaps

**Tasks:** `SLT-TT-00`

**Problem.** The D8 safety owner captured its first mail baseline before the full preflight, used two five-minute waits, selected each new renewal by count only, and had no complete standalone-issue or independent review path.

**Fix.** Take each target baseline immediately before its exact UI-run pair, poll at most 60 seconds, require scheduled-cycle/reverse-linked orders, preserve every non-SLT diff, and close the shared safety contract through review.

### C178 · `high` · adm02-literal-alias-route-order-recency-and-review-gaps

**Tasks:** `SLT-ADM-02`

**Problem.** The detail audit opened literal `SUB-A`, used an inconsistent scheduler route and customer-recency orders, and lacked numeric contrast resolution, full issue context, session teardown, and review closure.

**Fix.** Resolve both aliases numerically, compare the exact relationship-owned HPOS set and correct Tools route, capture console/network proof, and close the read-only canvases through review.

### C179 · `critical` · adm08-latest-renewal-long-wait-refund-scope-and-review-gaps

**Tasks:** `SLT-ADM-08`

**Problem.** The refund plan chose the latest paid renewal by recency, used a 180-second cancellation wait, did not guard exact refund/order scope, and omitted contextual issue/review closure.

**Fix.** Resolve RX from S_FEE's scheduled-cycle relationship and transaction, poll at most 60 seconds, require exact pre/post refund sets plus untouched control, and close the gateway/portal evidence through review.

### C180 · `high` · adm10-wrong-day-and-security-review-closure-gap

**Tasks:** `SLT-ADM-10`

**Problem.** The capability card contradicted the binding calendar by saying D6 instead of D9 and did not finish its security issue, probe-mail, session, or independent review workflow.

**Fix.** Pin execution to D9, reconcile the complete permission-probe mail boundary, require fully contextual redacted standalone security findings, close all four sessions, and return Review to zero.

### C181 · `high` · imp05-session-issue-and-review-handoff-gaps

**Tasks:** `SLT-IMP-05`

**Problem.** The terminal regression sweep did not close its admin/customer sessions, define complete standalone finding context, or independently review its logs/actions/screens/reconciliation before handing teardown a supposedly clean inventory.

**Fix.** Require one contextual issue per confirmed bucket, close exact sweep sessions, verify the complete zero-mail boundary, and move through review before SLT-SETUP-99A consumes its outputs.

### C182 · `critical` · setup99a-admin-session-count-issue-and-review-gaps

**Tasks:** `SLT-SETUP-99A`

**Problem.** The partial teardown mutated settings/rules and many subscription statuses without a named admin session, final count guard, contextual issue path, or review closure.

**Fix.** Bind all UI mutations to one exact session, re-count every artifact class, require only intended status changes, close contextual findings through independent review, and leave the live registry for 99B.

### C183 · `critical` · setup99b-stale-nonslt-count-session-and-terminal-board-gaps

**Tasks:** `SLT-SETUP-99B`

**Problem.** The destructive terminal task hard-coded 13 pre-existing active subscriptions, lacked a named admin session and complete issue/retry contract, and did not assert the board itself ended with every non-done column empty.

**Fix.** Compare the exact D0 non-SLT ID set at its live size, bind every cancellation/deletion to the isolated teardown session and exact allowlists, stop safely on ownership failure, and finish only when review/blocked/todo/in-progress all equal zero.

### C184 · `critical` · coupon-checkout-ambiguous-timeout-double-submit-gap

**Tasks:** `SLT-CPN-01`, `SLT-CPN-02`

**Problem.** Both coupon plans said only to wait for order-received after payment, so a slow hosted-payment redirect could be mistaken for a failed submit and the active Place Order control could be clicked again. During D1 this produced a second paid QA fixture before the first redirect became visible.

**Fix.** Record the HPOS order-count and submit-time boundaries, click Place Order exactly once, poll in calls of at most 60 seconds through two minutes, and treat any matching HPOS order or successful/in-flight checkout request as an absolute retry prohibition. Resolve an ambiguous timeout through network, reload, and exact customer/time/total/coupon evidence before any retry.

### C185 · `high` · prod15-variable-parent-virtual-control-does-not-exist

**Tasks:** `SLT-PROD-15`

**Problem.** The product plan instructed the operator to tick a parent-level **Virtual** checkbox after selecting **Variable product**, but WooCommerce exposes virtuality only inside each variation panel for variable products. The absent parent control would strand an otherwise valid fixture or invite a false product finding.

**Fix.** Require **Virtual** on Full, Next Cycle, and No Sync individually, include `_virtual` in every variation meta check, and explicitly avoid fabricating a parent-level virtual assertion.

### C186 · `critical` · syn02-hidden-variable-parent-flex-precondition-gap

**Tasks:** `SLT-SYN-02`

**Problem.** The audit assumed a newly published variable parent would begin without flex metadata, but the hidden parent Subscription tab can submit an unindexed default month/1 flex plan even though the operator can configure only the indexed variation panels. Starting independence probes from that contaminated parent would either strand the task or erase the original defect evidence.

**Fix.** Preserve the first contaminated parent read and file it as a standalone product issue, delete only the six parent flex keys as explicit QA-fixture containment, recapture the authoritative baseline, avoid the parent Update action, and require every variation AJAX probe to leave the parent clean. The task verdict continues to report the initial product finding.

### C187 · `high` · syn02-parent-flex-regex-false-positive

**Tasks:** `SLT-SYN-02`

**Problem.** The parent-leakage command used `rg arraysubs_flex`, which also matches the unrelated `_arraysubs_flexible_periods` key and therefore reports a false flex-sync leak even after the six `_arraysubs_flex_sync_*` keys are absent.

**Fix.** Match the exact `_arraysubs_flex_sync_` prefix so the guard detects only the variation-sync contract under test.

### C188 · `high` · syn02-cross-write-probe-disables-last-active-first

**Tasks:** `SLT-SYN-02`

**Problem.** The cross-write probe instructed the operator to disable Next Cycle's segment 3 before enabling segment 1. Segment 3 is initially the sole active segment, and the UI correctly rejects an attempt to leave the plan with no active segment, so the stated click order cannot reach the intended probe state.

**Fix.** Enable segment 1 before disabling segment 3, and restore in the safe inverse order by enabling segment 3 before disabling segment 1.

### C189 · `critical` · syn02-cross-write-restore-leaves-boundary-drift

**Tasks:** `SLT-SYN-02`

**Problem.** Restoring only Next Cycle's `no/no/yes` active flags after the segment-1-only probe leaves its hidden boundary metas at the probe-normalized `2/3` values instead of the authoritative `1/2` baseline. The later byte-diff would fail even though the visible one-row legend looks restored.

**Fix.** Temporarily reactivate all three segments, restore handles `1/2`, then disable segments 1 and 2 while keeping segment 3 active. Require both actives and preserved raw boundaries to match the step-2 rows before continuing.

### C190 · `critical` · syn02-disabled-boundary-ui-restore-is-not-byte-exact

**Tasks:** `SLT-SYN-02`

**Problem.** Even after temporarily restoring all three visible segments and handles `1/2`, disabling segments 1 and 2 re-normalizes the hidden boundary inputs to `2/3`. The plan demanded a byte-identical final dump but supplied no way to restore dormant boundary keys once their controls are hidden.

**Fix.** Restore the visible `no/no/yes` plan through the UI, preserve any dormant-boundary drift read, then use exact ID-keyed WP-CLI meta updates only for the two boundary keys when they differ from the captured step-2 baseline. Abort unless the complete final rows are byte-identical.

### C191 · `critical` · syn02-nosync-probe-materializes-dormant-active-keys

**Tasks:** `SLT-SYN-02`

**Problem.** Enabling then disabling No Sync correctly deletes the master key but leaves the three submitted active keys materialized as dormant `yes` values. The original never-enabled fixture had no active keys, so the required final byte-diff cannot be empty without an explicit key-existence restore.

**Fix.** Record the post-untick dormant state, preserve its boundary values, delete only the three probe-created active keys that were absent from the authoritative baseline, and require the complete No Sync rows to match step 2 exactly.

### C192 · `critical` · syn04-missed-primary-bracket-has-no-safe-recovery

**Tasks:** `SLT-SYN-04`

**Problem.** The global-sync task hard-stopped after 11:00 site even when the primary bracket was missed, no later D3 mutation had begun, and the remainder of the day still contained an isolated gap with identical date semantics. That stranded the sole global-sync fixture and every downstream midnight-boundary assertion, while dormant in-progress renewal cards were also worded as false blockers despite having no active cart or session.

**Fix.** Keep 09:00-11:00 as the primary bracket, but allow one declared same-day `RECOVERY` bracket only before any later D3 mutation starts. Repeat the full board/queue/cart preflight, treat only active mutation legs as blockers, cap the bracket at two hours, restore before the next task, and retain the restore-first/no-overrun rules and exact registry timestamps.

### C193 · `critical` · d3-fixed-coupon-window-stranded-behind-untimed-queue

**Tasks:** `SLT-LIFE-05`, `SLT-CPN-04`, `SLT-EML-12`, and the D3 untimed queue

**Problem.** The D3 calendar called its left-to-right order binding but placed the fixed 18:00–19:00 `SLT-CPN-04` checkout behind several untimed hour-long cards. It also omitted `SLT-LIFE-05`'s exact 17:38–17:48 O2 gate from the row. Literal execution could either miss the coupon fixture's only valid anniversary window or contaminate the renewal baseline, and then collide with the 21:00 email-settings bracket.

**Fix.** Give exact windows priority over the untimed queue: observe `SLT-LIFE-05` first, run `SLT-CPN-04` at 18:00–19:00, resume untimed cards, then pre-empt them for `SLT-EML-12` at 21:00–21:40. Resume the same untimed card after each hard window; no overlapping checkout or mail baseline is permitted.
