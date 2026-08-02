# Plan audit — 44 conflicts on a shared staging site

> Two audit passes ran over this plan: one across the Day-0 foundation set, one across the full
> 115-task plan. Together they found 44 concrete ways these tasks would corrupt each other when run
> against a single shared site. **Every one has a fix, applied in the calendar and in the affected
> task bodies.** This file is the record of why the plan is shaped the way it is.

The first pass's severity field was not populated, so those entries show as `unrated` — they are not
less important, they were simply produced by an earlier schema.

## Standing rules these conflicts produced

| Rule | Why |
|---|---|
| Never drain Action Scheduler without `--hooks=`, and prefer a single subscription | A bare drain fires every other test's pending renewal early and destroys its evidence |
| One `agent-browser --session <TASK-KEY>` per task | Sessions are keyed by name and share a cart; ten tasks shared one guest cart in the draft |
| A global setting changed outside the D0 baseline is restored in the same task | Otherwise the next task silently runs against the wrong configuration |
| No account rebuys a product it already subscribes to | `auto_migrate_on_checkout=true` migrates the existing subscription instead of creating one, destroying control spines |
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

**Fix.** Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

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

**Fix.** Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

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

**Problem.** More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.

**Fix.** Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

### C14 · `high` · shared-global-setting / undeclared exclusive bracket

**Tasks:** `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`, `SLT-IMP-03`, `SLT-ADM-03`, `SLT-ADM-05`

**Problem.** SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.

**Fix.** Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

### C15 · `high` · dependency-gap / unowned purchases

**Tasks:** `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`, `SLT-EML-08`, `SLT-SYN-10`, `SLT-SYN-07`

**Problem.** Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.

**Fix.** Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

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

**Fix.** Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

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

**Problem.** The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires EVERY due pending action for that hook, including the 13 pre-existing non-SLT active subscriptions (which the isolation contract forbids touching) and every other SLT test's pending renewal invoice / renewal / hold / cancel / expire action. This is the single largest cross-contamination risk in the plan. Tasks that will necessarily drain: any renewal of SLT Flex Month Segments (next payment 2026-09-01 / 2026-10-01, unreachable naturally), the SLT Flex Week Segments segment-3 cohort (next payment 2026-08-15), the SLT Flex Variable Daily Next Cycle tail, the SLT-PROD-11 auto-downgrade case (requires a hand-set _end_date), and SLT-SETUP-99's wind-down. One broad drain on any of those days would prematurely fire the pending renewals of SLT Daily Core, SLT Retry Daily (destroying the 1-day/3-day grace ladder timing), SLT Fixed Three Cycles (destroying its 2026-08-07 expiry contract) and the Box.

**Fix.** Ban bare hook drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot wp-admin -> Tools -> Scheduled Actions filtered to Pending and record EVERY action due within the next 24h, aborting if any non-SLT action is due; (2) move only the target subscription's _next_payment_date and its paired schedule meta; (3) execute the single action by id from the Scheduled Actions screen (Run action) rather than by hook, or invoke the processor for one subscription id via `wp eval` passing that id explicitly; (4) if a hook drain is truly unavoidable, first cancel/park every other pending action for that hook from the Scheduled Actions UI, run the drain, then restore them, and record before/after _next_payment_date for all 13 pre-existing active subscriptions as proof they did not move. Confine all time-travel to D8 (2026-08-09), the single authorized drain day in the calendar.

### C25 · `unrated` · same-account-collision

**Tasks:** `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-SETUP-03`, `SLT-SYN-03`, `SLT-SYN-04`

**Problem.** multiple_subscriptions.auto_migrate_on_checkout = true is a baseline the plan never changes, yet three tasks require the SAME account (slt-flex) to buy the SAME product three separate times: SLT-PROD-12 demands three purchases of SLT Flex Month Segments (segments 1/2/3), SLT-PROD-13 three purchases of SLT Flex Week Segments, and SLT-PROD-15 three purchases of three variations of one variable parent. With auto-migrate on, the second and third checkouts are liable to MIGRATE the customer's existing subscription for that product rather than create an independent one - which silently destroys the segment-1 subscription that the earlier purchase created, and makes the whole segment matrix unobservable. On top of that, slt-flex is additionally loaded with SLT Sync Global Daily (SLT-SYN-04) and SLT Sync Excl Probe (SLT-SYN-03) by explicit deviation, so one account would end up owning 9+ concurrent subscriptions and the my-account list becomes ambiguous for every later assertion.

**Fix.** Extend SLT-SETUP-03's matrix from 7 to 9 accounts: add A9 slt-flex2 / slt-flex2@example.test and A10 slt-flex3 / slt-flex3@example.test, same password and billing address. Bind: segment-1 purchases -> slt-flex, segment-2 purchases -> slt-flex2, segment-3 purchases -> slt-flex3, and the same 1/2/3 split for the SLT Flex Variable Daily variations. No account ever buys the same product twice. Before the first repeat purchase would have happened, run a one-line probe of auto_migrate behaviour and record it in the registry so the split is evidence-backed.

### C26 · `unrated` · same-account-collision

**Tasks:** `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-SYN-03`, `SLT-SYN-04`

**Problem.** Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.

**Fix.** Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

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

**Problem.** SLT-SETUP-04 sets Coupon expiry date = 2026-08-12 on all six SLT coupons and justifies it as 'past the watch window'. It is not: the watch window runs to D12 = 2026-08-13, and renewals fall on 2026-08-11, 2026-08-12 and 2026-08-13 (SLT Flex Daily Two Seg / Next Cycle, the SLT Flex Variable Daily day/3 cohort, and the SLT-SYN-04 globally-synced subscription). SLTPCT20REC is a RECURRING discount whose renewal-order behaviour is exactly what those tail renewals would prove, and a coupon that has expired by then makes the tail assertion untestable or produces a false negative.

**Fix.** Set Coupon expiry date = 2026-08-14 (or leave it blank and rely on SLT-SETUP-99B deletion) for all six SLT coupons, and record in the registry that no SLT coupon may expire before the last watch day. Update SLT-SETUP-04 step 4 and its Description string ('delete on 2026-08-11' -> 'delete on 2026-08-13').

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

**Problem.** Neither calendar-interval product can produce a natural renewal for its later cohorts, and the plan does not schedule the time travel. SLT Flex Month Segments: every purchase (segment 1, 2 and 3) lands its next payment on 2026-09-01 or 2026-10-01, i.e. 21 to 61 days past the window - the product yields zero renewal evidence unless a dedicated time-travel task exists before D10. SLT Flex Week Segments segment-3 cohort (purchased 2026-08-06) pushes next payment to 2026-08-15, also outside the window. Only the week segment-1/segment-2 cohorts renew naturally, on 2026-08-08. Combined with the drain hazard above, all four of these forced time-travel events would otherwise be improvised late in the window by whichever task notices first.

**Fix.** Create one dedicated, explicitly scheduled task on D8 (2026-08-09) that owns ALL time travel for the window: the month segment-1/2/3 renewals, the week segment-3 renewal, the flex-variable Next Cycle tail, and any hand-set _end_date needed for the SLT-PROD-11 auto-downgrade case. It must use the targeted single-action procedure (never a bare hook drain), record the pending-queue screenshot before and after, and prove the 13 pre-existing active subscriptions' _next_payment_date values are unchanged. D8 is the only authorized drain day in the corrected calendar.

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

**Problem.** SLT-CHK-15 expected result 7 requires SLT Trial Four Day's subscription to carry `_renewal_reminder_action_id` due 2026-08-05 (trial end 08-08 minus the 3-day lead) and asserts it exists. SLT-EML-09 step 4 asserts the opposite - 'wp action-scheduler list --hooks=arraysubs_send_renewal_reminder ... | grep <S_TR>' must return nothing - and expected result 3 says 'No pending arraysubs_send_renewal_reminder or arraysubs_send_expiring_soon action for S_TR'. Both tasks buy/attach to the same subscription on D2. One of them will file a bug the other declares correct.

**Fix.** Separate the action from the mail. Per SLT-REF-05 / EmailManager.php:806 the reminder handler requires post_status exactly `arraysubs-active`, and a trialling subscription is `arraysubs-trial` - so the ACTION may legitimately be scheduled while the MAIL is legitimately never sent. Restate CHK-15 ER7 as 'an arraysubs_send_renewal_reminder action for S_TR exists at trial_end - 3d + k; record whether it does'. Restate EML-09 ER3 as 'no reminder MAIL for S_TR on 2026-08-05; whether the action exists is recorded, not asserted'. Make the D4 watch row carry both as an explicit paired check.

### C41 · `medium` · action-scheduler policy / broad-fire risk

**Tasks:** `SLT-LIFE-04`, `SLT-EML-01`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-ADM-05`, `SLT-SETUP-99`

**Problem.** No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.

**Fix.** Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

### C42 · `medium` · impossible-timing / single-day contention

**Tasks:** `SLT-LIFE-01`, `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-10`, `SLT-EML-14`, `SLT-DUN-05`

**Problem.** D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.

**Fix.** Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

### C43 · `medium` · shared-product-meta / undeclared bracket

**Tasks:** `SLT-SYN-13`, `SLT-SYN-02`, `SLT-PROD-15`, `SLT-MYA-05`

**Problem.** SLT-SYN-13 step 2 writes a decoy segment plan onto the SLT Flex Variable Daily PARENT product and deletes it only at step 7, the same day - but between those steps two live checkouts are placed and the window is unbounded in the body. SLT-SYN-02 audits the same product family on the same day (D2). Any other cart or checkout touching that parent inside the decoy window resolves filterRenewalSyncContext() against a plan no task expects, and the decoy's own null-vs-config proof depends on nothing else having read it. Separately SLT-MYA-05 leaves two appended members_access rules and a product-level _arraysubs_features meta live from D2 morning until its step-10 teardown on D7 - a five-day global deviation during which the pre-existing 'Gold members save 15%' rule (which targets pro_member on ALL products) can alter front-end prices for slt-fail.

**Fix.** For SYN-13: declare the decoy a bracket - record open/close UTC in slt-evidence/SLT-SYN-13-decoy-bracket.txt, post it to the registry, keep it under 90 minutes, and assert no other SLT task carts or checks out SLT Flex Variable Daily inside it. Add a pass criterion 'decoy removed and getConfig(<PARENT>) is null before the bracket closes'. For MYA-05: shorten the deviation by moving its teardown from D7 to immediately after follow-up B (D5 morning, once the on-hold role removal is captured) and re-adding the rules only if follow-up C needs them; record the bracket in the registry either way, and add an explicit price check on SLT Retry Daily renewals proving the pro_member discount never reached a cron renewal.

### C44 · `low` · duplicate-coverage

**Tasks:** `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`, `SLT-EML-07`, `SLT-SW-10`, `SLT-EML-04`, `SLT-DUN-02`, `SLT-SYN-11`, `SLT-SYN-01`, `SLT-PROD-05`, `SLT-LIFE-02`, `SLT-CHK-04`

**Problem.** Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.

**Fix.** Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.
