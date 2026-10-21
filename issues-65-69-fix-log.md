# Issues #65–#69: fix log (started 2026-10-20)

Resumable log for fixing open issues #65–#68 (pre-existing gaps found while fixing #56–#64, see
[`stripe-issues-56-64-fix-log.md`](stripe-issues-56-64-fix-log.md)) and #69 (customer report: checkout
"Today's Charge" ignores initial-payment-only coupons and tax). If the machine restarts, start at
**Resume checklist**, then read **Current state** and the last entries of **Timeline**.

## Request

User, 2026-10-20 06:32 UTC: "fix those 65 66 67 68". Then, mid-turn: "also fix this after proper
investigation" with a customer email about "Today's Charge" (filed as #69).
Standing rules from the #56–#64 request still apply: **do not commit or push**, test end to end on
staging, keep this log resumable.

User, 2026-10-20 ~17:20 UTC: "fix all of them. i told u to fix all of them. also fix the new ones like 74 78 . make it
release release ready fast." → scope extended to every issue filed during this work: #71–#78 (all moved to
`in-progress` 17:27). Still no commits.

## Rules for this work

- No commits, no pushes. No backward compatibility needed (AGENTS.md, development phase).
- AGENTS.md QA issue fix workflow per issue: `in-progress` → verify → plan → fix → browser-verify →
  `closed` (or `blocked`). Run `kanban-md` only from `qa/issues/`.
- Changelog: append `fixed:` entries to `arraysubs/changelog.draft.txt` / `arraysubspro/changelog.draft.txt`
  (no QA or fixture ids). Read both drafts first; additions only.
- Every touched file in `arraysubs`/`arraysubspro` must stay ≤ 3000 lines (`wc -l`).
- Settings changes only through `stripe-dev-support-2026-10-18/scripts/snapshot-settings.php` → change →
  `restore-settings.php … apply` → `diff-settings.php` vs `snapshots/settings-original-r2.json`
  (`all_identical: true`). Never change Stripe gateway settings or ArraySubs Stripe extras.
- Run/cancel only fixture actions. Register new fixtures with
  `stripe-dev-support-2026-10-18/scripts/register-fixture.php` (`SQA_PHASE=fix-65-69`).
- Never print or store secrets, full card data or order keys (`grep -r wc_order_ qa/` before finishing).
- Run `date -u` before writing a Timeline time. Never guess times.

## Resume checklist

1. `git -C arraysubs status --short && git -C arraysubs diff --stat` (and `arraysubspro`). The diff
   holds the #56–#64 work **plus** this work; nothing is committed. #56–#64 baseline: `arraysubs`
   22 files +2435/−342, `arraysubspro` 3 files +32/−8 (2026-10-19 22:36 UTC).
2. Read **Current state**; continue the first issue that is not `verified`.
3. `cd qa/issues && kanban-md list --compact --status in-progress,blocked`.
4. Browser helpers: `stripe-dev-support-2026-10-18/scripts/sqa-browser-helpers.sh`. Fixture passwords
   live in `/root/.sqa/sqa-passwords.txt` (never copy into `qa/`).
5. Assets changed? `cd arraysubs && npm run build` (and `arraysubspro` if touched).
6. If a settings window is open (see Timeline), restore it first.

## Current state

| Issue | Status | Fix summary | Files | Verification |
|---|---|---|---|---|
| #65 | **verified** (live round 2 via system cron; round 1 found the cron/HTTPS site-URL bug, fixed) | customer Pay-page intents recognised (scheme-insensitive site check), cancelled 30 min after the last Pay-page activity, pointers handed back to the attempt's own intent; order-scoped search closes other open customer intents before a replacement | core Stripe `StripeIntentResolutionTrait`, `StripeRenewalPaymentTrait`, `StripeRequiresActionTrait`, `StripeDelegate`, `site-url-helpers.php` | `evidence/t65-*` |
| #66 | **verified** (live, pay-page-like ACH via Woo Stripe webhook) | on-hold with a charge → settlement window meta; settlement check honours it | core `StripeAsyncFailureTrait::rememberSettlingPayment`, `PaymentHooksRenewalsTrait` | snapshot awaiting=true; no second charge; window end resumes grace |
| #67 | **verified** (live ACH failures ×2, sync dedupe) | async failures recorded once via `recordProviderPaymentFailure()` (dedupe marker), failed `py_` txn + deadline cleared, retry worker, per-method windows, sweep backstop, bank codes, mandate param fix, ACH code precedence | core `StripeAsyncFailureTrait`, `StripeWebhookEventsTrait`, `StripeWebhooksTrait`, `StripeApiTrait`, `RenewalProcessor`, `PaymentHooksReconciliationTrait`, `gateway-helpers`, Mollie caller | `evidence/t67-*` |
| #68 | **verified** (probe; no Mollie on staging) | dead WooCommerce token listeners + their handlers removed; Mollie `payment_method_update` = false (no dead-end link); rebind restores `active` after errored/needs_payment_method; revoke without key fixed | core `Services/MollieMandateRebinder.php`, `Gateways/Mollie/MollieDelegate.php`; user-manual 3 files | `evidence/fix68-probe.json` |
| #69 | **verified** (browser, with and without VAT; email) | Today's charge = line's share of today's total (coupons, tax, fees, shipping); shared `CartSummary` for item details + classic table; recurring amounts follow tax display | core `cart-charge-helpers.php`, `CartSummary.php`, SubscriptionProducts `Hooks.php`, `CheckoutDisplayTrait`, `CheckoutMigrationTrait`, Box `CartHooks`; Pro Bundle `CartHooks` | screenshots `t69-*`, `t69-tax-window-*` |
| #70 | **verified** (probe in HTTPS and non-HTTPS php) | `arraysubs_get_site_identity_url()` for stored/hashed site URLs, `arraysubs_is_this_site_url()` for checks, across Paddle (+ Stripe #65) | core `site-url-helpers.php`, 10 Paddle files | `evidence/fix70-probe.txt` |

## Review round 1 (one read-only agent, finished 08:45) — patch checklist

Report `qa/fix-65-69/evidence/review-r1.md`. No HIGH. Findings are being verified adversarially (workflow
`wf_d285105f-ed9`) before patching. `[ ]` = to do, `[x]` = done.

- [x] M1 MEDIUM (probe `evidence/r1-m1-probe.json`) — paid renewal put on hold (dispute, Radar review on a paid order, admin) gets a settlement stamp →
  "settling" for 7+ days (blocks admin status changes, renewal-date edits, grace sweeps).
- [x] L1 LOW (live: Pay page refused while a run held the lock `screenshots/r1-l1-paypage-refused.png`; run backed off and re-looked at stamp + 30 min) — a Pay-page submit during an automatic attempt can still race into two charges (pre-existing window of
  seconds); the attempt does not re-check the new Pay-page stamp before charging.
- [x] L2 LOW (code; pointer kept until the search clears it) — the no-attempt release branch deletes the pointers before the order-scoped search.
- [x] L3 LOW (fixture 69693: `evidence/r1-l3-sweep*.json`) — reconcile sweep: `duplicate` counted as handled but the deadline stays (6-hourly re-reads; the
  pre-existing "pending result queues nothing" dead end stays).
- [x] L4 — REFUTED by verification (not reachable: Pay page accepts only card/link under the consolidated `stripe` gateway; no change) — customer intents that legitimately wait for days (`verify_with_microdeposits`) are cancelled after
  30 minutes.
- [x] L5 LOW (live admin Retry → HTTP 409; `evidence/r1-l5-manual-retry.json`) — a deferral reads as "Renewal payment successful" to manual/customer Retry; ignored queue result.
- [x] L6 LOW (simulation: two $10 tax-inclusive lines → $10.00 each, Σ = total) — one-cent rounding with tax-inclusive prices; shares may not add up.
- [x] L7 LOW (probe: nested render depth 1; mini-cart computes once) — `calculate_totals()` from a display filter, no re-entrancy guard.
- [x] L8 LOW (matcher accepts siteurl and home, scheme-insensitive; Stripe metadata written with the identity URL) — ArraySubs' own Stripe metadata `site_url` = `home_url()` compared strictly (http home behind an HTTPS
  proxy).
- [x] L9 LOW (scheme-less `arraysubs_get_site_identity_key()` in Paddle hashes; probe identical in both contexts) — Paddle keys hash the raw `siteurl` (change on a scheme migration).
- [x] Nits (trial line names signup fee + shipping; no tax-inclusive signup breakdown when tax is listed separately) — trial + signup + shipping reads shipping as "first payment"; signup fee row before tax vs Today's charge
  with tax.

## Review round 2 (workflow `wf_aa4c8594-b4f`, finished 10:25) — patch checklist

Findings in the session scratchpad `review-r2.json` (11 confirmed, 0 refuted). `[ ]` = to do, `[x]` = done.

- [x] R2-0 LOW — first automatic attempt (no attempt marker) dead-ends when the no-attempt branch's order-scoped
  search is inconclusive: only a 2-day deadline, no next attempt. Fix: bounded hourly recheck for one day.
- [x] R2-1 LOW — leaked automatic charge lock (worker killed) refuses the Pay page for up to 2.5 h saying "wait a
  minute". Fix: neutral "being processed or confirmed; check back later" wording.
- [x] R2-2 MEDIUM — `manualRetry()` pending says "No new charge was made" although the retry may have just started a
  bank debit. Fix: neutral "not complete yet" message (keep 409).
- [x] R2-3 LOW — RecoveryWorker counts SCA-required and just-started debits as "declined". Fix: `manualRetry()`
  returns `requires_action` when the gateway said so; controllers 409; RecoveryWorker settles `pending` as in progress.
- [x] R2-4 MEDIUM (pre-existing) — Paddle `getReservedContract()` / `isRestorableReadyBinding()` rebuild the contract
  without `tax_mode`/`tax_signature` → reservation never settles, ready binding never restored. Fix: one shared rebuild
  helper with all 13 keys.
- [x] R2-5 LOW — Mollie strict `home_url()` comparisons (:1275, :2466, :2545) and writer (:537); PayPal `site_key`
  hashes `home_url()` (:100, :255, :552). Fix: matcher / identity URL / identity key.
- [x] R2-6 LOW (round-1 regression) — PayPal :584, Paddle :991, Mollie :2307 pass the request's filtered `home_url()`
  as completion evidence; the matcher refuses it under a `home_url` filter. Fix: pass the identity URL.
- [x] R2-7 LOW — Paddle catalogue and plan-switch contract fingerprints still hash the scheme. Fix: identity key in the
  contracts; `buildBinding()` stores it with `sanitize_text_field()`.
- [x] R2-8 LOW — signup fee + sync + tax shown separately reads as one prorated/full charge. Fix: total alone.
- [x] R2-9 LOW — non-trial breakdown folds shipping/fees/store credit into the plan/signup terms. Fix: use the plan
  part; other charges → total alone.
- [x] R2-10 LOW — a negative line share shows "Free today" and other lines exceed the total. Fix: clamp at 0, move the
  excess into the remainder.

## Review round 3 (workflow `wf_2318a2dd-f1b`, finished 11:45) — patch checklist

All 11 round-2 fixes verified fixed (probes, rolled back). 10 confirmed issues (all LOW after skeptics), 4 nits, 0
refuted. Full text: session scratchpad `r3-results.md`. Note: one verifier's probe lost its transaction to an InnoDB
deadlock and leaked a note on fixture 69676/69693; it cleaned it up (post 70073 gone, seq back to 10, checked 11:48).

- [x] R3-1 LOW (C0+C1+C4, partly pre-existing) — a queued renewal attempt survives a later decline: hard decline,
  retries off or limit reached leave the pending `HOOK_PROCESS_RENEWAL` (ladder retry, R2-0 search recheck,
  unreadable-intent recheck), which charges the card again; a soft decline adopts a recheck as the next retry (~1 h,
  not 24 h). Fix in `RenewalProcessor::scheduleNextRetry()`: classify the pending attempt (planned retry = stored
  next-retry time; new-method = order's attempt method ≠ subscription method); after this run's own decline withdraw it
  in the stop branches unless new-method; soft decline adopts only planned/new-method; limit branch defers to a pending
  planned retry (no premature "limit reached").
- [x] R3-2 LOW (pre-existing) — `manualRetry()` reports a no-attempt `process()` false (not due, lock busy, settling,
  status) as a fresh decline (402 + stale reason). Fix: per-call outcome → `already_processing` / `not_attempted` (409),
  RecoveryWorker treats them as non-declines.
- [x] R3-3 LOW (R2-7 regression) — Paddle `buildBinding()` `sanitize_text_field()` strips `%XX` from the site key →
  binding never matches. Fix: store the key exactly as hashed.
- [x] R3-4 LOW — changelog R2-4 entry claims a rate-limited price creation recovers (it does not). Fix: reword.
- [x] R3-5 LOW (pre-existing, #70 class) — Stripe webhook auto-heal compares the request-scheme `rest_url()` with the
  stored endpoint URL; a non-HTTPS cron run replaces a healthy https endpoint. Fix: asymmetric URL match.
- [x] R3-6 LOW (R2-10 regression) — clamped excess of a targeted discount spreads onto lines the discount does not
  cover. Fix: excess first to the same discount's other lines; remainder weighted by post-absorption bases.
- [x] R3-7 LOW (pre-existing) — checkout-migration lines get trial/sync wording and a computed next-charge date. Fix:
  migration line is neither trial nor synced; next charge = proration's new next payment date.
- [x] R3-8 NIT — `requires_action` retry text tells an admin to pay the order. Fix: wording by initiator.
- [x] R3-9 NIT — Pay-page refusal names "an automatic payment" for any lock holder. Fix: holder-neutral wording.
- [x] R3-10 NIT — 0.005 tolerance in 3-decimal currencies. Fix: half a minor unit.
- [x] R3-11 LOW (notes, R2-5 area) — Mollie `handlePaymentSucceeded()` still stores the transaction id and fires
  `arraysubs_gateway_payment_succeeded` after refusing a payment that is not the order's. Fix: stop there.
- [x] R3-12 — residuals from the notes to check: R2-0 docblock accuracy; `StripeRenewalPaymentTrait` inconclusive
  reconcile-before-retry queues no recheck; RecoveryWorker `pending` branch without `rearmSchedule()`; scheme-dependent
  `_arraysubs_payment_action_url` compare; order-side negative attribution shares; admin 409 toasts.
  Done: docblock fixed; reconcile-before-retry inconclusive now queues `queueUnresolvedSearchRecheck()` (shared
  `clearUnresolvedSearchStamp()`); RecoveryWorker re-arms for `pending`/`already_processing`/`not_attempted`;
  `arraysubs_site_urls_match()` for the 3 action-URL compares; admin + Renewal Failures 409/not-declined → info toast
  (`npm run build` OK 12:11); Pro `PortalActionFailureLogger` skips Retry Payment 409; unconfirmed customer-intent
  cancellation recheck bounded to a day per intent (`_arraysubs_stripe_cancel_unconfirmed`). Order-side negative
  attribution → new issue (R3-13). Manual-retry note no longer tagged `renewal_payment_succeeded` (timeline showed it
  as "Renewal Payment Successful").
- [x] R3-13 — filed #71 (Pay page) and #72 (order-side negative attribution). Was: file new issue (out of scope, pre-existing): Pay page accepts a second payment while a renewal debit is
  in flight (Mollie SEPA; Stripe before Woo Stripe's processing webhook).
- Accepted, no change: NIT recheck runs emit attempt events (same as other re-queues).

## Review round 4 (workflow `wf_202befd1-2f3`, finished ~13:58) — patch checklist

All round-3 fixes verified (28 checks; R3-6 "partial" = float residue below). 17 confirmed (1 MEDIUM pre-existing,
rest LOW/NIT), 2 refuted, 6 nits. Full text: session scratchpad `r4-results.md`. All probes reported rolled back
clean; fixture retry 102189 still pending. `[ ]` = to do, `[x]` = done.

- [x] R4-1 LOW (C1+C7, pre-existing half of R3-1) — a provider-reported (async) failure of the renewal's own attempt
  (Stripe bank debit, Mollie) never withdraws the queued attempt: after a hard decline / at the limit / retries off the
  planned retry or a recheck debits the same account again; R3-1's "customer's own Pay-page payment" rationale matches
  no caller. Fix: `recordProviderPaymentFailure(..., $is_run_attempt)`; Stripe passes true when the failed intent
  tried the order's own method, Mollie when the payment is the attempt result; fix docblocks + docs.
- [x] R4-2 LOW (C0, R3-1 regression) — a hard pre-charge failure (no order card snapshot) withdraws the attempt queued
  for a card the customer saved during the run. Fix: attempt method = order snapshot, else the subscription method
  read at run start; fresh meta read; cancel only the examined action.
- [x] R4-3 LOW (C2+C3+C8, pre-existing) — billing-context recovery re-arms a charge leg after its own retry stopped
  the ladder (hard decline / limit / retries off) → same card charged again ~4 h later. Fix: no re-arm after `failed`.
- [x] R4-4 LOW (C9, pre-existing) — a queued attempt that fires while the renewal lock or the lifecycle lock is held
  is used up; nothing re-queues (ladder or first charge silently ends; R3-1 limit note never written). Fix: re-queue
  the leg ~2 min later when nothing else is pending (keep the planned time).
- [x] R4-5 LOW (C5+C10, R3-6 regression) — clamp loop float residue: a fully absorbed peer is re-clamped and its
  absorbed amount overwritten → Today's charge rows exceed the order total. Fix: round bases on the currency grid,
  `remainder +=`.
- [x] R4-6 LOW (C6+C12+C16, incomplete R3-7) — migration line to a trial target still shows trial wording in the cart
  Price / Subtotal / checkout quantity cells and the block cart. Fix: `CartSummary::hasTrial()` there + Store API.
- [x] R4-7 LOW (C13, incomplete R3-7) — segment-plan "First billing cycle" note on a migration line. Fix: skip it.
- [x] R4-8 NIT (C14, C15, nits) — wording: Pro changelog 51 (also no-failed-renewal / another attempt running);
  core changelog 128 + checkout manual (excess first off covered lines; sums equal the total for subscription-only
  carts); changelog 137 (limit: only an earlier check is withdrawn).
- [x] R4-9 NIT — Mollie mismatch early return lacks `'ignored' => true` (router logs "processed").
- [x] R4-10 NIT — `manualRetry()` counter restore overwrites a concurrent failure's counter (restore only if still 1).
- [x] R4-11 — unverified (skeptic crashed, API 529): Mollie payment of the renewal settling after the order was paid
  another way is dropped with only a log line (R3-11 removed the last trace). Check; add a refund-check order note.
- [x] R4-12 — filed #73 (per-cart trial cap, MEDIUM), #74 (per-item member price overrides trial $0, HIGH, needs repro), #75 (plan_switch_checkout never finalized, HIGH, code reading), #76 (migration needs_payment false), #77 (Pro LIKE DELETE, LOW). Was: file new issues (pre-existing, out of scope): DiscountEngine counts a free-trial line at its regular
  price for per-cart member discounts (MEDIUM, revenue leak); `isTrialOnlyCart()` ignores checkout migration
  (needs_payment false with a total > 0); per-item member price overrides a trial's $0 price; checkout-migration order
  may never fire `arraysubs_order_paid` (code reading, verify first); Pro `OverviewHooks::invalidateCache()` unescaped
  LIKE DELETE scans/locks wp_options.
- Not changing: old http Stripe endpoint left enabled after an http→https move (pre-existing nit); overlapping
  third-party negative fees share one peer pool (docblock note only).
- Done (14:24, code only, `php -l` clean, WP loads): R4-1 `recordProviderPaymentFailure(..., $is_own_attempt)`
  (`$is_run_attempt` renamed `$is_own_attempt` throughout), Stripe async passes true when the intent's attempted PM is
  the order's `_gateway_payment_method_id` (or unknown), Mollie when the payment is `attemptResult()`. R4-2
  `processUnderLock()` reads the subscription PM fresh before the charge (`$run_method`), passed to
  `queuedRenewalAttempt()` as fallback attempt method; that re-reads meta (`wp_cache_delete`). R4-3 RecoveryWorker no
  re-arm after `failed`. R4-4 `RenewalProcessor::requeueAttemptThatFoundBusy()` (+2 min, only when nothing pending and
  chargeable; no planned-time relabel, so a stale stored time cannot turn a recheck into the planned retry) from
  `RecurringBilling\Hooks::processRenewal()` on renewal-lock busy and on `lastNoAttemptReason() === 'busy'`. R4-5 clamp
  loop rounds peer bases, `remainder +=`. R4-6 `CartSummary::hasTrial()` in cartItemPrice / cartItemSubtotal /
  checkoutCartItemQuantity / Store API `getCartItemData()`. R4-7 `CartSummary::isMigration()`; segment note skipped.
  R4-9/R4-11 Mollie mismatch returns `'ignored' => true`; renewal's own Mollie payment (`attemptResult`) settling on an
  order already paid another way → one order note (refund check, marker `_arraysubs_mollie_paid_after_order`) + payment
  log. R4-10 counter restore with prev value '1'. Docs: payment-retry-system.md (stop + async, busy requeue, recovery,
  counter), payment-recovery.md :74/:82, subscription-checkout.md (sums, discount spill, plan switch). Changelog core
  139 (entries 128/129/134/136/137 reworded, +2 new: busy requeue, recovery), Pro 51 (reworded). Line counts:
  RenewalProcessor 1654, RecurringBilling Hooks 1572, MollieDelegate 2926, SubscriptionProducts Hooks 2497.

## Review round 5 (workflow `wf_fa633bb8-f85`, finished ~16:20) — patch checklist

41 checks: all round-4 fixes hold (3 partial = R5-4, nit N7, R5-6). 11 confirmed (3 MEDIUM), 0 refuted, 15 nits.
Full text: session scratchpad `r5-results.md`. One skeptic probe lost its transaction and left 5 rows on fixture
68996/69022 (restored 16:23, see Timeline). `[ ]` = to do, `[x]` = done.

- [x] R5-1 MEDIUM (pre-existing) — Mollie async failure keeps the failed `tr_` as the order transaction id → order
  counts as settled: Retry says "No failed renewal order to retry", the next retry mints a second renewal order.
  Fix: compare-clear the transaction id in `handlePaymentFailed()` / sweeper fail path (as Stripe :135-145).
  Side effect to check (C3/N1): a Mollie debit started on a `failed` order keeps it `failed` → planned retry mints
  another order + concurrent debit. Investigate; fix or file.
- [x] R5-2 LOW (R4-1) — at the limit, a kept new-method attempt still gets the limit note + `retry_exhausted`
  (again when it fails). Fix: no note/event when the queued attempt was kept.
- [x] R5-3 LOW (R3-1) — stale order snapshot from an earlier attempt taken as the failed method when this run never
  charged → new-card attempt kept, fails again. Fix: snapshot counts only if this run wrote/changed it, else run method.
- [x] R5-4/R5-9 LOW (R4-4) — busy re-queue undoes a stop (hard / limit / retries off) or an on-hold the holder
  recorded while the attempt was starting (5–15 ms) → stopped method charged again. Fix: re-queue only active/trial;
  mark the re-queued attempt; when it runs, skip if the ladder stopped meanwhile (open failure, no stored retry time,
  method unchanged).
- [x] R5-5 LOW (pre-existing) — `MollieTrialAvailability::cartHasTrial()` raw trial check (migration to a trial
  plan). Same family as #76; fixing alone breaks the 0.00 case (Z1). Append to #76.
- [x] R5-6/R5-11 LOW — renewal paid on the Pay page with the Mollie host card gateway (alias): own debit settling
  → `provider_payment_transaction_conflict` → HTTP 500 loop, no note; changelog 136 over-claims. Refunding the own
  debit in that case fully refunds the order + cancels the subscription. Fix: paid-another-way check before the
  binding check; refund handler ignores a payment that is not the order's transaction.
- [x] R5-7 LOW→MEDIUM (R3-11) — own Mollie payment settling while the order moved to an unfinished other method
  (BACS / Stripe debit on-hold / abandoned) → plain ignore with a wrong log line, no note. Fix: de-duplicated note
  for unpaid orders too + correct log text; uncredited-payment consequences → #71.
- [x] R5-8 MEDIUM (R4-1) — Pro early renewal paid by a bank debit that fails later (hard / retries off) withdraws
  the cycle's own due-date charge leg. Fix: never withdraw a queued attempt while the renewal is not due yet; docs.
- [x] R5-10 MEDIUM (pre-existing, #74 scope) — per-item member price replaces every engine-set price (migration,
  renewal sync, segment prorate), not only trial $0. Widen #74. Side observation: Pro bundle `get_price()` ignores
  `set_price()` (segment-plan bundle charged in full) → file new issue.
- [x] Nits to fix: N8 signup fee cells without tax → `CartSummary::signupFee()`; N9 Mollie refund/chargeback
  mismatch `'ignored' => true`; N10 unknown order → ignored, no success action with subscription 0; N11 note before
  de-dup meta; N12 changelog 137 + payment-recovery.md:74 wording; N13–N15 QA files #75/#76/#73; N3 forced retry
  counter → request-local flag (if contained); N7 early run busy hop (if contained); N2 Mollie used mandate → order
  snapshot (if contained).
- Not changing (documented): N1 card change between run-method read and gateway read; N4 fixed with R5-2 (notes
  say the queued attempt still runs); N5 scheduleSingle dedupe vs holder's retry; N6 re-queued last retry not
  "planned" (duplicate limit note in a rare case).
- Done (16:45, code only, `php -l` clean, WP loads): R5-1 Mollie `clearTerminalPayment($order, $payment_id)`
  compare-clears the unpaid order's txn; reconcile sweep expiry compare-clears a txn equal to
  `_arraysubs_pending_transaction_id`; `hasSettlingRenewalPayment()` also counts a `failed` invoice with a txn inside
  its `_arraysubs_pending_deadline` (R5-1b: retry's bank debit on a failed order). R5-2/R5-8/N4
  `withdrawOrKeepQueuedAttempt(..., $due)` returns kept; keeps everything before the due date; limit branch returns
  without note/event when kept; hard-stop / retries-off notes say the queued attempt still runs. R5-3 attempt method =
  order snapshot only when this run changed it, else run method (`processUnderLock()`), async = snapshot
  (`scheduleNextRetry()`); N2 Mollie `interpretPayment()` writes the used mandate to the order snapshot. R5-4/R5-9
  busy re-queue only active/trial, marks `_renewal_attempt_requeued_busy` before queuing (stop's withdraw clears it);
  own-attempt stops of a due renewal that keep nothing store `_payment_retry_stopped` {failure, method}; marked run
  ends `stopped` when that matches the current failure, no retry time, same method (`busyRequeueWasStopped()`,
  scheduled runs only); reset helper deletes it. N3 `process($id, true)` from `manualRetry()`: verification without
  writing the counter, attempt events ≥ 1; forced counter + restore removed. N7 renewal-lock-busy early run →
  `requeueEarlyRenewalCharge()`. R5-6/7/11 + N10/N11 Mollie `handlePaymentSucceeded()`: unknown order / no
  subscription → ignored; own payment (attemptResult) with failed binding or paid with another txn → note first, then
  de-dup meta, paid / unpaid wording, ignored. Refund handler ignores a refund of a payment that is not the order's
  txn (+ N9 `'ignored' => true`). N8 6 cart cells use `CartSummary::signupFee()`. Docs payment-retry-system.md
  (queued attempt, stop record, busy, manual flag, settling), payment-recovery.md :74. Changelog core 142 (136/137/138
  reworded, +3: Mollie failed debit, manual retry counter, signup fee tax). QA: #74 widened, #76 + R5-5 + N14, #75 N13,
  #73 N15, #71 + R5-7 consequences, new #78 (Pro bundle get_price ignores set_price). Lines: RenewalProcessor 1785,
  RecurringBilling Hooks 1582, MollieDelegate 2967, SubscriptionProducts Hooks 2497.

## Review round 6 (workflow `wf_d8df6dbc-ffd`, finished ~18:25) — patch checklist

46 checks: round-5 fixes hold (6 partial: R5-9 with no order snapshot, busy docs, R5-11 empty txn refund, R4-9 X7,
changelog 142 mini-cart row). 17 confirmed (3 MEDIUM: C3 refund of the flagged duplicate refunds the order when its
txn is empty, C4 sweep fails/emails an order whose own Mollie payment was taken, C10 hourly invoice sweep can mint a
second order after a failed invoice's deadline), 0 refuted, 12 nits. Full text: scratchpad `r6-results.md`. Fixtures
unchanged (each owner verified its hash). Patch workflow `wf_540327fa-6bd` (task wpq1qi32a, launched 18:33): groups
mollie (MollieDelegate + split, PaymentHooksRenewalsTrait), retry (RenewalProcessor, RecurringBilling Hooks,
ActionScheduler, gateway-helpers, PaymentHooksReconciliationTrait), minicart (SubscriptionProducts Hooks).

- [x] C3/C15 Mollie refund guard: ignore unless the order txn is the refunded payment (+ note on paid orders).
- [x] C5/C14 note branch skips an order paid by this very payment; N3/N9 cancelled order keeps the refund wording.
- [x] C4 own Mollie payment taken → sweep leaves the order for the merchant (no failure/email), no dunning meanwhile.
- [x] C6/C9 Mollie `reconcileUnsettledRenewal()` records a failure whose webhook was lost.
- [x] N4/N6 orderless Mollie payments ignored, log names the order.
- [x] C2 unknown failed method → subscription's method in the stop record.
- [x] C10 failed invoice settling while its deadline meta exists; compare-clear in the unresolvable branch.
- [x] C11 busy mark bound to its action; stop record tied to the due date; mark deleted on reset.
- [x] C12 on-hold retry planned during the hold is re-queued when busy; dropped attempt clears the stored time.
- [x] C13 sweep expiry also clears a txn equal to `_arraysubs_settlement_txn` (Stripe).
- [x] C17/N5 Retry Payment while a debit settles answers the settling message.
- [x] C7 mini-cart signup fee row by charge kind, tax-aware; C8 totals calculated before the mini-cart renders.
- [x] C1 gateway reports the method it verified (Stripe/Mollie result key) — after the #71 fixer releases Stripe/*.
- [x] Docs/changelog: C16 (payment-recovery.md :92/:225, payment-retry-system.md :48), N2, N8, N10, C17 docs,
  changelog 134/136/137/142 amendments; QA text N11 (#78: bundles have no trials), N12 (#71 note).
- Done (20:58): patch workflow finished 20:52 (10 agents; minicart verified yes, mollie + retry partial with
  LOW/NIT leftovers). MollieDelegate split into `Mollie/Traits/MollieReconciliationTrait.php` (verify, reconcile,
  taken-payment hold, lost-failure recording) and `MollieRefundsTrait.php` (refunds, chargebacks); 2606 lines now.
  NOTE: during that split the staging site fataled for ~10 s (18:47:14–18:47:23 UTC, missing abstract methods),
  resolved by the agent. My integration after it: refund guard regression fixed (`date_paid` empty but `is_paid()`
  still copies a real refund); partial refund before a lost paid webhook gets an accurate note; sweep unresolvable
  branch clears the txn by the order's own `_subscription_id` link; Mollie lost-create records the found payment
  (`storePaymentMeta` + `closeAttempt` in the reconcile-before-retry branch); RenewalProcessor prefers a
  gateway-reported `attempt_payment_method_id` (Stripe part of C1 still to do); mini-cart fee row escaped via
  `wp_kses_post(wc_price(...))`. Changelog core 151 (134/136/138/140/142 amended, +8), Pro 53 (+2: #77, bundle
  mini-cart). Docs: payment-retry-system.md (Mollie rows, busy paragraph, stop record, manual step 2, settling row),
  payment-recovery.md, mollie.md (bullets, troubleshooting), product-experience.md (mini-cart). Skipped (documented):
  signup fee on a checkout plan switch (product decision), `_on_hold_date` overwrite on every hold, Mollie P8 wording.

## Issues #71–#78 (added 17:20) — checklist

Fixer workflow `wf_76c32a8e-d78` (task wyhdhaw5s, launched 17:26): 4 fixers on disjoint files, adversarial verifier
each, one repair loop. They must not edit the round-6 verification targets (RenewalProcessor, RecurringBilling Hooks,
MollieDelegate, PaymentHooksReconciliationTrait, gateway-helpers, SubscriptionProducts Hooks, CartSummary), changelogs,
docs or qa/; they return changelog/doc text and any edit needed in those files. Probe folders: scratchpad `fix7x/`.

- [x] #71 Pay page refuses a second payment while the renewal's own debit settles (new guard service; fixer fix-71).
- [x] #72 order-side initial charge attribution clamped like the cart (fixer fix-72).
- [x] #73 per-cart member discount uses the cart's line price for a trial (fixer fix-73-74-78).
- [x] #74 member price filter keeps engine-set cart prices (fixer fix-73-74-78).
- [x] #75 plan_switch_checkout orders finalized on payment (fixer fix-75-76).
- [x] #76 trial-only cart / Mollie trial filter ignore checkout migration (fixer fix-75-76).
- [x] #77 Pro `OverviewHooks::invalidateCache()` escaped LIKE prefixes (`$wpdb->prepare` + `esc_like`), done 17:25:
  EXPLAIN type=range key=option_name rows=2 (was type=ALL); `php -l` clean.
- [x] #78 Pro bundle `get_price()` keeps the cart's set price (fixer fix-73-74-78).
- Fixer workflow done (20:28): #73/#74/#78 verified yes; #71, #72, #75/#76 partial. Leftovers (HIGH: refund of a
  plan_switch_checkout order cancels the subscription; MEDIUM: migration order skews later switch pricing, guest
  subscriptions migratable by email, #72 reader float residue, Mollie lost-create keeps the Pay page open; LOW/NIT
  rest) → follow-up workflow `wf_45120eae-801` (task w76muwe6o, 20:32): groups migration, attribution (+ pre-existing
  zero-share charge never finalizes: free shipping, 9 stuck staging orders), payguard (+ PayPal/Paddle-billed
  renewals), memberhtml. Per-fix reports: scratchpad `fix7x/followup-*.json`. Later, after the round-6 patch
  workflow: C1 (gateway-reported attempt method), Mollie lost-create storePaymentMeta, migration-line signup fee.
- [x] Apply returned changelog/doc text; browser checks; close #71–#78 (closed 00:1x 2026-10-21).

## Fix plans

### #65 — customer Pay-page PaymentIntents dead-end automatic attempts

Root cause (code, verified 2026-10-20): WooCommerce Stripe does not reuse a `requires_action` /
`requires_confirmation` intent (class-wc-stripe-upe-payment-gateway.php:4137-4172), so a second Pay-page
visit creates PI2 with Woo Stripe metadata (`order_id` = order number, `order_key`, `signature`,
`site_url` = `esc_url(get_site_url())`, plus ArraySubs' `save_payment_method`/`arraysubs_subscription_cart`)
and stores it in `_stripe_intent_id`. `resolveExistingIntent()` only knows ArraySubs' own intents:
open PI2 → `isExactRenewalAuthenticationIntent()` false → `invalid` → pending + deadline; declined PI2 →
reconcile search skips Woo Stripe PIs (no `subscription_id` metadata) and finds PI1 nonterminal → pending;
or `clearTerminalIntentForReplacementWhileLocked()` refuses (attempt result = PI1). No later attempt is
queued. Same dead end for a Woo Stripe PI from an early Pay-page visit before ArraySubs' first attempt.

Plan:
1. `isCustomerPayPageIntent()`: PI for this unpaid automatic renewal (no ArraySubs `subscription_id`/`renewal`
   metadata, `order_id` = order number, Woo Stripe `site_url`, customer = order/subscription Stripe customer,
   currency, single linked subscription, Stripe gateway).
2. `resolveExistingIntent()`: a stored customer PI that is open with zero money is cancelled (after a 30-min
   grace from the latest customer activity: PI `created`, the requires_action sighting, or a new
   `_arraysubs_customer_pay_started_at` stamp written when the customer submits the Pay page, classic or
   Store API); a cancelled one is released. Then `_stripe_intent_id` points back to the renewal's own attempt
   PI and that PI is resolved as before (abandoned attempt → cancel → replacement), or, with no attempt PI,
   the order-scoped search runs before a fresh charge.
3. `searchPaymentIntentsForOrder()`: Woo Stripe PIs for the order are no longer ignored: money taken or in
   flight → not conclusive (no replacement); open zero-money → listed as `customer_attempts`.
4. Before any replacement charge (terminal path and reconcile-before-retry), open customer attempts are
   cancelled, or the attempt is deferred while one is recent. A failed cancel queues a recheck in 1 h.
5. The ArraySubs abandoned-attempt grace (R3-M2a) also counts the Pay-page submit stamp.

Live test: fixture renewal declines → customer Pay page 3DS with another card, abandon → second visit, 3DS,
abandon (PI2) → retry: both cancelled, card on file charged once.

## Timeline (UTC, append-only)

- 06:32 — Started. Repo heads unchanged since 2026-10-19 (`arraysubs` b9d6c44, `arraysubspro` ffa7bc8,
  `qa` adcb059); uncommitted #56–#64 work intact. Filed #69 (customer report).
- 06:42 — #65 → `in-progress`. Investigation agents launched (read-only) for #66, #67, #68, #69; reports land in
  `qa/fix-65-69/evidence/inv-*.md`.
- 06:58 — #65 code in place: `StripeDelegate` (`CUSTOMER_PAY_STARTED_META`, `CUSTOMER_ATTEMPT_GRACE`, Pay-page
  submit hooks), `StripeRequiresActionTrait::rememberPayPageSubmission()`, `StripeIntentResolutionTrait`
  (`isCustomerPayPageIntent`, `isOpenZeroMoneyIntent`, `deferWhileCustomerIsPaying`, `releaseCustomerPayPageIntent`,
  `closeCustomerPayPageAttempts`, `unconfirmedCustomerCancellationResult`; `resolveExistingIntent(..., $depth)`),
  `StripeRenewalPaymentTrait` (search lists Woo Stripe intents; reconcile-before-retry closes them). `php -l` clean.
- 07:04 — **T65 live, part 1:** new fixture customer 597 `sqa-fix65-paypage` → trial checkout 69675 (card 0341) →
  sub 69676; trial ended + renewal pulled forward → renewal 69693 declined (`generic_decline`), PI1
  `pi_3USX1Y…` (attempt result), retry 1 = action 102032. (Actions 102027/102029 ran before the due time and only
  re-queued; 102030 was the real run.) Customer visit 1: new card 3184, 3DS challenge, left → Woo Stripe reused
  PI1 → `requires_action` with the customer's card, seen stamp + new `_arraysubs_customer_pay_started_at`. Visit 2:
  same → Woo Stripe created **PI2 `pi_3USX5B…`** (no ArraySubs metadata) and stored it: the #65 state. Ran retry
  102032 at 07:04:12 → **deferred**: no charge, no deadline, attempt 102036 queued 07:33:37 (= last Pay-page
  submit + 30 min), retry count unchanged. Evidence `qa/fix-65-69/evidence/t65-after-deferral.json`, screenshots
  `qa/fix-65-69/screenshots/t65-0*.png`. All ids registered (`SQA_PHASE=fix-65-69`).
- 07:05 — Found while checking: the pre-retry verification stores the open stored intent as
  `_arraysubs_pending_transaction_id` (PI2), which would make the terminal cleanup of PI1 refuse. Release now moves
  that pointer back to the attempt intent too. Probe: PI2 classified as the customer's Pay-page intent, PI1 not.
  **Next: let 102036 run (07:33:37) → expect PI2 + PI1 cancelled, replacement PI on card 0341 (declines) → retry 2.**
- 07:11 — **#68 closed** (probe-verified; browser test impossible: no Mollie plugin/gateway on staging). Investigation
  `qa/fix-65-69/evidence/inv-68-mollie-rebinder.md`; probe `qa/fix-65-69/scripts/fix68-probe.php` →
  `evidence/fix68-probe.json` (temporary draft subscription 69712 deleted with its 4 notes, no actions). Core changelog
  +2 entries (122 lines). Docs: user-manual payment-and-shipping, mollie, actions-and-filters.
- 07:25 — All four investigation reports in (`qa/fix-65-69/evidence/inv-66-settlement.md`, `inv-67-async-failures.md`,
  `inv-68-mollie-rebinder.md`, `inv-69-todays-charge.md`). #67 → `in-progress`; code so far:
  core `RenewalProcessor` (`RECORDED_FAILURE_META` written per provider attempt in `handlePaymentAttemptFailure()`;
  `recordProviderPaymentFailure()` returns `duplicate`, refuses orders not pending/on-hold/failed, a non-current
  invoice, externally billed subscriptions); Mollie caller treats `duplicate` as handled; new
  `Stripe/Traits/StripeAsyncFailureTrait.php` (`recordAsynchronousRenewalFailure()`: renewal attempt's own PI only,
  fresh Stripe read, lifecycle lock, superseded/duplicate checks, compare-deletes Woo Stripe's failed `py_`/`ch_`
  transaction id + `_stripe_charge_id` + deadline, then `recordProviderPaymentFailure()`;
  `reconcileUnsettledRenewal()` for the 6-hourly sweep; `settlementWindowDays()` card 2 / ACH 7 / BECS 7 /
  BACS 10 / ACSS 10 / SEPA 21 days, extensions up to 30 days after the attempt); `handlePaymentFailed()` routes
  through it (lock busy → official metadata retry queued, delivery not failed); retry worker handles
  `EVENT_PAYMENT_FAILED`; reconcile sweep consults the gateway before expiring; classifier maps bank-debit codes
  (closed/no account/frozen/restricted → invalid_card hard; not authorized/blocked/disputed/refer → card_declined
  hard; source/weekly limits → generic_decline; generic_could_not_process → processing_error); reason wording
  neutral ("insufficient funds", "the card issuer or bank refused the payment", "the payment details on file are
  invalid") + user-manual updated. **Also found and fixed:** renewal PIs with a cached bank-debit mandate sent
  `payment_method_options[<type>][mandate]`; Stripe answers `parameter_unknown` (verified with a test-mode request
  that created nothing), so every such renewal failed. Now only the top-level `mandate`.
  Stripe account (read-only): US/USD, `us_bank_account_ach_payments` inactive (test mode may still work), no SEPA.
- 07:30 — #69 → `in-progress`. Code: new `arraysubs/src/functions/cart-charge-helpers.php`
  (`arraysubs_get_cart_initial_charge_breakdown()`: each cart line's share of today's total = line after coupons +
  tax + its share of ArraySubs-weighted fees + chosen shipping by package quantity + remainder, Σ = cart total;
  `arraysubs_get_cart_item_initial_charge()`; `arraysubs_format_cart_recurring_amount()` follows the cart tax display
  with WooCommerce's own labels); new `SubscriptionProducts/Services/CartSummary.php` (billing, signupFee,
  todayCharge, nextCharge, recurringAmount, syncContext) used by the cart item details (classic + block cart,
  checkout, mini-cart, Pro email snapshot) and by the classic checkout table (`CheckoutDisplayTrait`, rows named
  by product when there are several subscriptions; migration row no longer a second "Due today"); the three
  private builders removed from `SubscriptionProducts/Services/Hooks.php` (2665 → 2497 lines); signup fees marked
  `arraysubs_charge_kind=signup_fee` (core signup + Box, Pro Bundle); migration item "Due today" row removed (Today's
  charge now carries it); dead view `SubscriptionCheckout/views/checkout-subscription-summary.php` deleted (only
  `.pot` referenced it). In-memory simulation (agent script, no DB writes) with 21% VAT: reporter case → Today's
  charge **$227.60 (incl. VAT)**, Recurring $209.00; signup 206+welcome15 → $20.56 + $6.04 signup = $26.60; trial+fee
  55 → $1,210.00; display incl → Recurring $252.89 (incl. VAT), Today $227.60; prices incl/display excl → Recurring
  $16.53 (ex. VAT), Today $18.00 (incl. VAT); two lines, mixed cart, sync prorate, qty 2, trial → all Σ = total.
- 07:34 — **T65 part 2 FAIL (found a bug):** attempt 102036 ran via the system cron (`php wp-cron.php`) at 07:34:03 and
  left a 2-day deadline with no next attempt: the old dead end. By then Stripe had expired both abandoned 3DS
  challenges (07:16 / 07:18, `payment_intent_authentication_failure`, PIs back to `requires_payment_method`).
  Cause: `isCustomerPayPageIntent()` compared Woo Stripe's `site_url` metadata (`https://…`, recorded during the
  HTTPS payment) with `esc_url(get_site_url())`, which is `http://…` in a non-HTTPS CLI cron run
  (`set_url_scheme()` follows `is_ssl()`); WP-CLI sets HTTPS, so CLI probes passed. Verified by a traced replay as
  www-data through WP-CLI (`scripts/t65-trace-run.php` → `evidence/t65-rerun-trace.txt`): with HTTPS the path
  cancelled PI2 (`canceled/abandoned`), restored PI1 (terminal), minted PI3 `pi_3USXdM…` on the card on file →
  `card_declined` → retry 2 (102071), attempts 2, deadline cleared. Fix: `isThisSiteUrl()` compares the stored
  `siteurl` option and the recorded URL without the scheme.
- 07:42 — **T65 round 2 (cron path):** visit A (Woo Stripe reused PI3 → `requires_action`), visit B → PI4
  `pi_3USXgY…` stored. Retry 2 (102071) run early via WP-CLI → deferred, attempt **102073 at 08:12:15** (PI4 created
  + 30 min), no deadline, attempts unchanged. 102073 will run via the system cron. Expect PI4 cancelled, PI3 retired,
  new charge on 0341 → decline → retry 3.
- 07:47 — **T67 PASS (live, real ACH):** fixture customer 598 `sqa-fix67-ach` → trial checkout 69755 → sub 69756;
  test ACH method `pm_1USXis…` (account 000222222227, insufficient funds) + mandate created by
  `scripts/create-ach-pm.php` (SetupIntent + test microdeposits) and bound (mandate included). Renewal 69774:
  ArraySubs PI `pi_3USXky…` created with the top-level `mandate` (proves the parameter fix) → `processing`, Woo Stripe
  put the order on-hold with `py_…`, deadline +7 days (ACH window). Stripe failed it ~20 s later →
  `payment_intent.payment_failed` → **recorded once**: category, "Scheduled retry 1 of 3", attempts 1, failed `py_`
  transaction id and deadline cleared, order on-hold → failed, one customer email, one WC "Failed order"; the second
  endpoint's copy was skipped as a duplicate event. Retry 1 (102090) on the **same order** → new PI `pi_3USXmw…`
  processing → failed → recorded (attempts 2, retry 2 = 102092). Reason first showed "card was declined" (Stripe sends
  ACH `code=insufficient_funds` + `decline_code=generic_decline`); `stripeFailureCode()` now prefers a known
  top-level code over `generic_decline` → second failure classified `insufficient_funds`. Sync-decline dedupe seen live
  on #65's PI3: webhook → `duplicate` (07:38:58). Evidence `evidence/t67-*.json`.
- 07:50 — **#70 filed + fixed** (user: "fix that for paddle as well now"): new `src/functions/site-url-helpers.php`
  (`arraysubs_get_site_identity_url()` = saved `siteurl`; `arraysubs_is_this_site_url()` scheme-insensitive); all
  Paddle writers/hash inputs use the identity URL, all Paddle comparisons the matcher (10 files, 22 lines); Stripe
  `isCustomerPayPageIntent()` uses the shared matcher. Probe `scripts/fix70-probe.php` → `evidence/fix70-probe.txt`:
  in a non-HTTPS php run as www-data `get_site_url()` is `http://…`, identity URL/matching/Paddle checkout and
  plan-switch keys identical to the WP-CLI run. No Paddle subscriptions on staging for an end-to-end run.
- 07:57 — **T66 PASS (live):** #66 code: `StripeAsyncFailureTrait::rememberSettlingPayment()` on
  `woocommerce_order_status_on-hold` stamps `_arraysubs_settlement_txn` / `_arraysubs_settlement_until` (method
  window, at least 7 days) for an unpaid Stripe renewal put on hold with a charge; `orderIsAwaitingSettlement()` honours
  it (on-hold/pending, same transaction id, before the window ends); subscription-level query includes it. Live: the
  Pay page's test bank (Link "Success") settled instantly (order 69774 completed, 69756 rebound to the bank method),
  so `scripts/paypage-like-bank-payment.php` paid unpaid invoice 69807 with test ACH account 000000000009 (stays
  processing) and Woo Stripe metadata → Stripe `payment_intent.processing` → Woo Stripe on-hold + `py_…` → stamp
  (+7 days) → `GracePolicy::snapshot()` `awaiting_gateway_settlement=true` (was false before the fix). Renewal action
  run as www-data during settlement: no Stripe call, no second charge. Window moved into the past → false, restored →
  true. Fixture grace on 69756 set to 0/0 for this test.
- 07:59:12 — **SETTINGS WINDOW OPEN (#69 tax test):** before = `woocommerce_calc_taxes=no`, `woocommerce_prices_include_tax=yes`,
  `woocommerce_tax_display_cart=excl`, `woocommerce_tax_display_shop=excl`, tax rate table empty
  (`evidence/t69-tax-window-before.json`). Window sets calc_taxes=yes, prices_include_tax=no and adds one NL-only 21% "VAT"
  rate. **If work stops here, restore:** `wp option update woocommerce_calc_taxes no`, `wp option update
  woocommerce_prices_include_tax yes`, `wp option update woocommerce_tax_display_cart excl`, delete every row of
  `wp_woocommerce_tax_rates` with country NL created today (`WC_Tax::_delete_tax_rate(<id>)`), then confirm the count is 0.
- 08:02:32 — **T69 PASS (live, tax window) and SETTINGS WINDOW CLOSED:** reporter's exact case on fixture product 69815
  (209.00/year, guest, NL address, coupon `qa-audit-coupon` 10% initial only): classic checkout Subtotal $209.00,
  Coupon −$20.90, VAT $39.50, Total $227.60, **Recurring $209.00 every 1 year, Today's Charge $227.60 (incl. VAT)**;
  block checkout item details the same. Display incl: Recurring $252.89 (incl. VAT), Today's Charge $227.60 = Total.
  206 + `welcome15`: "$20.56 + $6.04 signup = $26.60 (incl. VAT)" = Total. Before tax: block/classic checkout, block
  and classic cart on 2571 + coupon showed Today's charge $18.00 = Total (was $20.00). Window restored:
  calc_taxes=no, prices_include_tax=yes, display excl, tax rate 2 deleted, rate and location tables empty
  (`evidence/t69-tax-window-after.txt`). Screenshots `t69-*`.
- 08:13 — **T65 PASS (round 2, real system cron):** attempt 102073 ran via WP Cron at 08:13:02 (no HTTPS): cancelled
  the customer's Woo Stripe PI4 `pi_3USXgY…` (`canceled/abandoned`; by then expired to `requires_payment_method`),
  retired the attempt's PI3 (`payment_intent_authentication_failure`, terminal), charged the card on file with PI5
  `pi_3USYAQ…` → `card_declined` → next retry 102116 (+24h), attempts 3, no deadline.
  The decline's webhook arrived while the run still held the lifecycle lock → deferred to gateway metadata retry
  102117 → 08:15:04 `duplicate` (attempts stay 3). The customer's earlier 3DS timeouts (07:56/07:57): PI3 webhook →
  `duplicate`; PI4 (customer's own Woo Stripe payment) → old behaviour (failure note + "Payment failed" emails; not
  an automatic attempt, not counted). Evidence `evidence/t65-r2-*.json`.
- 08:45 — Review round 1 report in (no HIGH; M1 + 9 LOW + 2 nits). 08:48–09:17 verification workflow
  `wf_d285105f-ed9` (6 read-only skeptics): M1 and L1–L3, L5–L9, nits confirmed (L7, L9 partly); **L4 refuted**.
- 09:20 — Round-1 patches applied, `php -l` clean, all files < 3000 lines:
  M1 `rememberSettlingPayment()` takes the status transition (3 args): only pending/failed → on-hold, not manual, no
  paid date; settlement check ignores paid orders. L1 Stripe Pay-page submits (classic `woocommerce_before_pay_action`
  @4, Store API `rest_dispatch_request` @11) write the Pay-page stamp then hold `arraysubs_charge_lock_{order}`
  (10 min TTL, released at shutdown), refusing with a notice/409 while an automatic charge holds it; an automatic run
  that finds the lock taken by a fresh Pay-page payment queues a look at stamp + 30 min. L2 no-attempt release keeps
  the customer intent stored until the search clears it. L3 `duplicate` from a conclusive sweep releases the leftover
  deadline and re-queues the used-up retry at its planned time. L5 `manualRetry()` reports `pending` (409) when
  nothing was charged; RecoveryWorker handles `pending`; deferral logs when no later attempt could be queued. L6 line
  shares rounded like WooCommerce. L7 no totals calculation from inside a running one. L8 matcher accepts `siteurl`
  and `home` without scheme; Stripe metadata written with the identity URL; strict `home_url()` checks replaced
  (Stripe traits, `ProviderPaymentCompletion`, `StripeSubscriptionCompatibility` verification lookup). L9 Paddle hashes
  use `arraysubs_get_site_identity_key()`. Nits in `CartSummary::todayCharge()`.
- 09:33 — Round-1 verification done (probes + live): M1 probe (dispute/review/admin holds of paid orders no longer
  stamp; pending/failed holds still do); L1 live both directions on fixture 69693 (Pay page refused while a run held
  the lock; run backed off while the Pay page held it and queued 102188 at stamp + 30 min; fixture retry restored as
  102189 at 2026-10-21 08:13:07); L3 on 69693 (leftover deadline released; with no pending retry it re-queued 102179 at
  the planned time; attempts unchanged); L5 live admin Retry → HTTP 409, message "No new charge was made…"; L6/nits
  simulation; L7 probe; L8/L9 context probe (`evidence/r1-l8-l9-context-probe.txt`). Core changelog +4 entries, #70
  entry extended (134 lines).
- 10:01 — **Regression after round-1 patches (www-data, non-HTTPS, like the cron):** fixture 69676 / 69693: stored PI5
  (customer's 09:30 3DS on it had expired → `requires_payment_method`) retired as terminal, order-scoped search clean,
  new PI6 `pi_3USZr7…` on the card on file (metadata `site_url` = identity URL `https://…`) → `card_declined` → retry
  ladder exhausted (attempts stay 3), deadline empty; webhook: duplicate event skipped on one endpoint, `duplicate` on
  the other. Evidence `evidence/r1-regression-*.{txt,json}`. Note: fixture retry 102189 (2026-10-21 08:13) is still
  queued because this run was triggered directly, not through that action.
- 10:01 — Review round 2 (workflow `wf_aa4c8594-b4f`, 4 lenses + per-finding verification) running on the round-1
  patches.
- 10:25 — Review round 2 done: 11 confirmed (2 MEDIUM: R2-2 manual-retry wording, R2-4 Paddle reservation
  fingerprint), 0 refuted. Checklist above.
- 10:28 — R2-4 + R2-7 patched (Paddle `rebuildStoredRecurringContract()` with all 13 keys used by both rebuilds;
  contract `site_url` = identity key in `PaddleProductSync` and both plan-switch contracts; `buildBinding()` keeps it
  with `sanitize_text_field()`); `php -l` clean.
- 10:38 — Remaining round-2 patches applied (code only, verification next), `php -l` clean, all files < 3000 lines:
  R2-0 `queueUnresolvedSearchRecheck()` (stamp `_arraysubs_stripe_search_unresolved_since`, hourly for a day, cleared
  by a conclusive search) in `reconcileTerminalIntentBeforeReplacement()`. R2-1 Pay-page lock message "An automatic
  payment for this renewal is being processed or confirmed…". R2-2/R2-3 `RenewalProcessor::$lastUnpaidResultStatus`:
  `manualRetry()` returns `requires_action` (409) or `pending` with neutral wording (manual-payment fallback named);
  controllers 409 for `requires_action`; RecoveryWorker settles `pending` as `repaired_future_renewal`; portal shows a
  409 as an info toast (`npm run build` OK). R2-5/R2-6 Mollie writer + 3 checks → identity URL / matcher; PayPal
  `site_key` → identity key; PayPal/Paddle/Mollie completion evidence → identity URL. R2-8/R2-9 `todayCharge()` uses
  the plan part; total alone when other charges or a credit are in a paid plan's share, or signup + tax listed
  separately. R2-10 negative line base clamped to 0, excess left in the shared remainder.
- 10:44 — Changelog (core) updated for round 2: entries for Pay-page wording, Retry statuses, Today's charge totals,
  Paddle catalogue prices + new Paddle reservation entry, Mollie/PayPal site check; 136 lines (Pro draft unchanged, 50).
  Verification + review round 3 workflow `wf_2318a2dd-f1b` launched (5 fix verifiers, 3 review lenses, skeptic per
  issue; read-only, probes memory-only or rolled back). No live fixture runs while it is going.
- 11:45 — Review round 3 done (`wf_2318a2dd-f1b`, 18 agents): R2-0…R2-10 all verified fixed; 10 new confirmed (all
  LOW), 4 nits. One verifier's rolled-back probe leaked a note on 69676/69693 through an InnoDB deadlock; it removed it
  (checked 11:48: post 70073 gone, `_arraysubs_lifecycle_attempt_seq` 10, order 69693 failed, retry 102189 pending).
- 11:55 — Round-3 checklist written (R3-1…R3-13). Patching next; no live fixture runs until patches are in.
- 12:12 — Round-3 patches applied (code only; verification next), `php -l` clean, all touched files < 3000 lines
  (largest: MollieDelegate 2887, StripeSubscriptionCompatibility 2817, SubscriptionProducts Hooks 2497):
  R3-1 `RenewalProcessor::scheduleNextRetry($sid, $order, $is_run_attempt)` + `queuedRenewalAttempt()` +
  `withdrawOrKeepQueuedAttempt()`; R3-2 `$lastAttemptFailed` / `$lastNoAttemptReason` / `noAttempt()` →
  `already_processing` / `not_attempted` (409, both controllers), RecoveryWorker non-declines; R3-3 Paddle
  `buildBinding()` stores the key unchanged; R3-4 changelog reworded; R3-5 `StripeWebhookProvisioner::endpointUrlMatches()`;
  R3-6 peer-first clamp in `arraysubs_get_cart_initial_charge_breakdown()`; R3-7 `CartSummary::hasTrial()` /
  `migration()` (also Hooks + CheckoutDisplayTrait); R3-8 requires_action text by initiator; R3-9 holder-neutral
  Pay-page text; R3-10 `CartSummary::epsilon()`; R3-11 Mollie mismatch returns early; R3-12 residuals (see checklist).
  Docs: payment-retry-system.md, user-manual payment-recovery.md + subscription-checkout.md. Changelog core 137,
  Pro 51. `npm run build` (core) OK.
- 12:16 — Filed #71 (Pay page accepts a second payment while an automatic renewal debit is in flight; Mollie SEPA,
  Stripe before Woo Stripe's processing webhook) and #72 (initial-order charge attribution gives a subscription a
  negative share; refunds / plan-switch credit read it) — both open, out of scope. Verification + review round 4
  workflow `wf_202befd1-2f3` launched (4 verifiers, 3 lenses, skeptic per non-NIT issue; probes rolled back or
  memory-only under scratchpad r4/). No live fixture runs while it is going.
- 13:58 — Review round 4 done (`wf_202befd1-2f3`, 26 agents, 1 skeptic crashed on API 529): all round-3 fixes hold
  except a float residue in R3-6; 17 confirmed (1 MEDIUM pre-existing DiscountEngine), 2 refuted, 6 nits.
- 14:09 — Round-4 checklist written (R4-1…R4-12). Patching next; no live fixture runs until patches are in.
- 14:24 — Round-4 patches applied (see the round-4 checklist "Done" line); filed #73–#77 (evidence copied to
  `fix-65-69/evidence/r4-*`). Next: live browser checks (R3-9 Pay-page message, admin + portal Retry 409 info toast),
  then review round 5.
- 14:26–14:38 — Live browser checks on fixture 69676 / 69693 (customer 597 session `c65`, admin session `adm`):
  R3-9 — automatic charge lock held (`r2-hold-auto-lock.php 69693 90`), Pay-page submit with saved card 0341 refused:
  "A payment for this renewal is already being processed or confirmed. Please check back later; if the order is still
  unpaid then, you can pay it here." (`screenshots/r3-9-paypage-refused-neutral.png`), nothing charged. The submit
  writes the Pay-page stamp, so the Retry checks waited 10 min (PAY_PAGE_CHARGE_LOCK_TTL) to avoid an attempt at
  stamp + 30 min replacing fixture retry 102189. Then, lock held again (120 s): admin Retry Payment → POST
  `/subscriptions/69676/retry-payment` 409, blue info toast "The renewal payment is not complete yet…", page refetched
  (`screenshots/r4-admin-retry-409-info-toast.png`); customer portal Retry → POST `/my-subscriptions/69676/retry-payment`
  409, info toast, same text (`screenshots/r4-portal-retry-409-info-toast.png`). After: attempts 3, order failed, same
  intent, 0 new order notes, 2 "Manual renewal retry initiated" notes, no Pro portal-failure note, 102189 still pending
  at 2026-10-21 08:13:07, no new Mailpit message. Guest cart 2571 + `qa-audit-coupon`: Today's charge $18.00 = total
  (`screenshots/r4-guest-block-cart-coupon.png`).
- 14:40 — Charge lock on 69693 released (14:39:15). Verification + review round 5 workflow `wf_fa633bb8-f85` launched
  (4 verifiers: v5-ladder on 68996/69022, v5-busy-recovery on 68770/69645, v5-cart, v5-mollie; 2 lenses; skeptic per
  non-NIT issue with one retry; probes rolled back or memory-only under scratchpad r5/; 69676/69693 off limits). No
  live fixture runs while it is going.
- 16:20 — Review round 5 done (`wf_fa633bb8-f85`, 17 agents): all round-4 fixes hold (3 partial); 11 confirmed
  (3 MEDIUM: R5-1 Mollie failed txn kept, R5-8 early-renewal leg withdrawn, R5-10 #74 scope), 0 refuted, 15 nits.
  Probe leak: skeptic `r5/skeptic-r5-money-1/probe-holder-lock.php` lost its transaction to a deadlock (~15:54) and a
  parallel probe's restore re-inserted rows from a contaminated capture: 68996 `_payment_retry_attempts`=1,
  `_renewal_action_id`=103413 (action does not exist), lifecycle seq 3; 69022 `_arraysubs_pending_deadline`
  2026-10-22 15:54:14, `_arraysubs_stripe_unreadable_since`, `date_updated_gmt`.
- 16:23 — Fixture 68996/69022 restored with the guarded script `r5/skeptic-r5-money-1/restore-fixture.php`
  (applies only from the analysed hash e2717e…, commits only if the result equals the pre-run hash 9c7e…):
  committed, diff vs golden snapshot empty, 102189 still pending 2026-10-21 08:13:07. Round-5 checklist written
  (R5-1…R5-10 + nits). Patching next; no live fixture runs until patches are in.
- 16:47 — Round-5 patches applied (see the round-5 checklist "Done" line). Evidence copied to
  `fix-65-69/evidence/r5-*`; filed #78; #71/#73/#74/#75/#76 updated. Next: review round 6 (verify R5 fixes with
  rolled-back / memory-only probes on 68996/69022 and 68770/69645; 69676/69693 + 102189 off limits).
- 16:50 — Verification + review round 6 workflow `wf_d8df6dbc-ffd` launched (task whxfdkrgk): verifiers v6-ladder (owns
  68996/69022), v6-busy (owns 68770/69645), v6-mollie and v6-cart (throwaway entities / memory-only); lenses r6-money,
  r6-mollie-docs; skeptic per non-NIT issue (no fixture writes, autocommit off). Round-5 diff: scratchpad
  `r5-patch.diff`, snapshot `snap-r5-patched/`. No live fixture runs while it is going.
- 17:26 — User asked to fix all filed issues (#71–#78) and make it release ready. #77 fixed directly (escaped
  LIKE, EXPLAIN range scan). Fixer workflow `wf_76c32a8e-d78` launched for #71–#76/#78; round 6 keeps running.
  #71–#78 moved to in-progress (17:27).
- 18:29 — Review round 6 done (`wf_d8df6dbc-ffd`, 23 agents): 46 checks (6 partial), 17 confirmed (3 MEDIUM),
  0 refuted, 12 nits; fixtures unchanged. 18:33 — patch workflow `wf_540327fa-6bd` launched for them (C1 deferred
  until the #71 fixer is done with Stripe files).
- 20:32 — Fixer workflow `wf_76c32a8e-d78` done (16 agents): new RenewalPaySettlementGuard (#71), order-side hold
  (#72, new initial-charge-share-helpers.php), member price keeps engine prices + per-cart trial weight + bundle
  get_price (#73/#74/#78), migration finalizer + trial checks + ownership (#75/#76). Follow-up workflow
  `wf_45120eae-801` launched for the verifiers' leftovers.
- 20:58 — Round-6 patch workflow `wf_540327fa-6bd` done (20:52); integration edits + changelog/docs applied (see
  round-6 Done line). Follow-up workflow `wf_45120eae-801` still running (4 groups).
- 22:50 — Follow-up workflow `wf_45120eae-801` done (22:42): migration, attribution, memberhtml verified yes;
  payguard partial with a HIGH its own round 2 introduced (after a full refund of a Stripe payment taken for an
  unpaid renewal the reopened order kept the refunded PaymentIntent, so WooCommerce Stripe's Pay page completed it
  for free). Fixed by me (option b): the release detaches `_stripe_intent_id`, the pending transaction id, the
  transaction id and `_stripe_charge_id` of the refunded payment and bumps the attempt sequence; verification and
  the metadata search skip `REFUNDED_TAKEN_META`. Also: Mollie lost-create recorded in `handleCreateError()` too;
  grace sweeps wait for a Stripe taken payment; `hasSettlingRenewalPayment()` is true for an invoice whose payment
  was already received (no second charge on a new gateway); C1 Stripe part (`attempt_payment_method_id` from the
  locked read). php -l clean, WP loads. Final round 7 `wf_01967bbd-aed` (task w0dlgzed1) launched: stripe-final,
  mollie-final, regression-lens (HIGH/MEDIUM only), docs-merge (applies fixers' changelog/doc text).
- 22:59 — SETTINGS WINDOW OPEN for the #75/#76 browser check: snapshot
  `qa/stripe-dev-support-2026-10-18/snapshots/settings-before-r7-migration.json` (arraysubs_settings sha256 8cacd06b…),
  then REST save of multiple_subscriptions.one_per_customer, auto_migrate_on_checkout, plan_switching.enabled,
  allow_upgrades, allow_customer_switch = true (scratchpad `r7browser/set-migration-settings.php 1`). Restore with
  `restore-settings.php <snapshot> --apply` + `diff-settings.php` before anything else if resuming.
  Browser checks done so far (screenshots `r7-*`): #74 member trial Free today ✓; #71 Pay page refused while
  68903 was put into a Stripe settling state (restored after, payable again) ✓; #78 not reproducible live today
  (bundle 11095 is in its full-charge segment on the 21st; consistent $46.74).
- 23:28 — SETTINGS WINDOW CLOSED: restore-settings.php settings-before-r7-migration.json apply; diff vs
  settings-after-r7-migration-restore.json all_identical true (sha256 8cacd06b…). #75 live check passed: fixture
  customer 1520 (sqa-fix75-c1, registered fix-71-78) bought Basic Monthly ×2 (order 87087, sub 87088; cart added
  twice by mistake), then Pro Monthly ×2 at checkout: "Replaces your current Basic Monthly subscription", credit
  $59.97, today $39.99, no Paddle/PayPal offered; order 87640 completed, plan_switch_checkout, one "Existing
  subscription updated from checkout migration." note, sub 87088 → product 495 ×2, same next date 2026-11-20
  23:03:06, no second subscription (screenshot r7-75-c1-migration-checkout). Full WooCommerce refund of 87640
  (refund 87650, no gateway refund): order refunded, sub 87088 stays active on 495 (HIGH fix holds live).
  Round 7 done (23:27): stripe-final MEDIUM + NIT, mollie-final MEDIUM + 2 LOW + 2 NIT, regression-lens HIGH +
  MEDIUM (Stripe taken-payment refund copied to WooCommerce cancels the subscription), docs-merge done (core
  changelog 171 lines). Patching them now.
- 23:32 — Round-7 fixes applied (php -l clean, WP loads): refunds of a payment taken for an unpaid renewal never
  cancel or count (RefundPlanSwitchTrait::isUnpaidTakenRenewalOrder() at every RefundOrderEventsTrait exit;
  StripeRefundsTrait no longer copies a Stripe refund onto a never-paid order, note instead);
  hasSettlingRenewalPayment() also true for a received payment on the current invoice; Stripe attempt method
  captured inside the locked closure; Mollie sweep adopts a lost-create payment (adoptLostCreatePayment), a found
  failed payment in handleCreateError() is a classified failure, logging + _last_gateway_transaction_id for
  adopted payments, per-method deadline in the reconcile-before-retry branch, in-flight refund note skips
  cancelled/refunded orders and fully returned payments. Core changelog entry 152 amended. Targeted verification
  `wf_fa0eaa14-0b2` (task wzgtc6jnq) launched.
- 23:34 — #65, #66, #67, #69, #70 closed (closing notes = notes/0NN*.md + a rounds 6-8 line). Final checks: no order key
  values in qa/ (0 matches of wc_order_ + key chars), all touched files <= 3000 lines (largest OrderIntegration
  2962), heads unchanged (arraysubs b9d6c44, arraysubspro ffa7bc8; 79 / 10 changed paths, nothing committed), JS
  build newer than every source, changelog lines vs HEAD: 0 removed, core +69, Pro +10. Filed low-priority
  follow-ups #79 (checkout plan switch signup fee, product decision), #80 (legacy attribution owner, product
  decision), #81 (Pro renewal shipping label), #82 (Store API pay-for-order session-cart check), #83 (developer
  API / Store API bundle member price display). #71-#78 close after round 8.
- 00:09 — Round 8 done (wf_fa0eaa14-0b2): mollie verified yes (LOW + NITs), refund-settling partial with a MEDIUM my
  round-7 guard introduced (a renewal set to Processing by hand has no paid date, so its later full refund no longer
  cancelled). Fixed: isUnpaidTakenRenewalOrder() also false when _subscription_payment_processed = yes; Stripe full
  refund of an unpaid renewal's own attempt payment is detached like a taken one (no later completion with refunded
  money); unpaid-order refund note reworded; Mollie sweep adoption keeps the fresh deadline (returns true), skips a
  payment whose failure was recorded, persists _last_gateway_transaction_id. php -l clean, WP loads. Changelog 152
  amended. Leftover (documented, not changed): WooCommerce Stripe's own charge.refunded handler can still record a
  taken amount as a WooCommerce refund when it runs before ArraySubs (no cancellation; refund stays on the order).
- 00:10 (2026-10-21) — DONE. #71-#78 closed with closing notes (as were #65-#70). Board: #65-#78 closed; open
  follow-ups #79-#83 (low; #79/#80 product decisions). Browser sessions closed. Fixture retry 102189 still pending
  2026-10-21 08:13:07. Nothing committed (arraysubs b9d6c44, arraysubspro ffa7bc8). Changelogs: core 171, Pro 57
  lines. Still open with the user: legacy PayPal site_key fallback for staging subs 28650/28663 (no-BC rule).
- 05:19 (2026-10-21) — User: "fix that known leftover. also fix #81, #82, #83 . all others are ok nd intended, nothing to
  do there." → #79, #80 closed as intended; no PayPal legacy site_key fallback (intended). #81-#83 in progress.
  Workflow `wf_120dc454-bb4` (task w4o92au7t) launched: stripe-leftover (detach a taken Stripe payment from the unpaid
  order so WooCommerce Stripe's charge.refunded handler cannot record it; re-attach when the merchant marks the order
  paid), shipping-81, payorder-82, bundle-83; verifier + one repair each. Fixture retry 102189 (08:13 UTC today) off
  limits.
- 08:09 — Workflow `wf_120dc454-bb4` done (16 agents): stripe-leftover yes (taken payment detached from the unpaid
  order: WooCommerce Stripe cannot record its refund; re-attached when the merchant marks the order paid; marker
  lookups indexed), shipping-81 yes, payorder-82 partial (HIGH: Pay route could pay a block checkout's unplaced
  checkout-draft and skip One Trial per Customer; MEDIUM: guest changing billing email on the pay request), bundle-83
  partial (LOW guard order). Fixed by me: #82 refuses a checkout-draft holding a subscription ("This order has not
  been placed yet…"), counts the order's own subscriptions from line-item `_subscription_id`, checks other live
  subscriptions on the current and the pre-request billing email; #81 new `_arraysubs_shipping_package_total` (the
  checkout package's own cost) decides relabel on a plan switch; #83 guard set before qualifying-rule evaluation;
  Stripe Refund via Stripe refused whenever a taken payment is still recorded. php -l clean, WP loads. Workflow
  `wf_08df485f-742` (task w0jfs6wxu): docs merge + final verify.
- 08:09 — #83 live check: Store API /products as guest 33290 price 25.00 / 11095 46.74 (unchanged); as member (fixture
  593 given pro_member temporarily) 33290 price=sale=20.50 on_sale true, 11095 39.73 (before: sale 21.68); member
  cart line for 33290 = 20.50. Role removed, cart cleared, browsers closed. 102189 still pending (08:13:07).
- 08:48 — Final verify + docs merge done (wf_08df485f-742): #82 HIGH confirmed fixed (drafts refused on store, classic,
  batch, case-variant routes; normal checkout of the draft still works), #81/#83/Stripe refusal hold; one MEDIUM (a
  guest could pass the email check in two requests because WooCommerce saves the new email on a refused request) →
  fixed: the identity also covers the email of the order's own subscriptions; re-ran the verifier's probe:
  two-step refused, own-order control allowed, single-request cases unchanged, all rolled back clean. Docs merged
  (core changelog 177 lines, Pro 58; 0 HEAD lines removed). #81, #82, #83 closed. Final checks: 0 order-key values
  in qa/, every touched file lint clean and <= 3000 lines, nothing committed (b9d6c44 / ffa7bc8), JS build current,
  WP loads. Fixture retry 102189 ran at 08:14 on the patched code: declined (generic_decline), "Reached the
  configured retry limit (3)" written once, order 69693 failed; Mailpit 08:14: one customer "Payment failed" email for
  sub 69676 plus the admin copy and the WooCommerce "Order #69693 has failed" notice — ladder end confirmed live.
- 08:52 — Post-check: the site's Stripe sweep (AS 109345, 08:17:06) saved order 69693 once. It marked the declined
  08:14 attempt "superseded" in the requires-action outbox (an ordinary decline, so no verification link), as
  designed; no email was sent (Mailpit shows nothing for sub 69676 after 08:14). AS 109349 was WooCommerce's own
  report-transient cleanup after that save. Board: #65-#83 closed; older unrelated items #5 #8 #12 #19 #26 #27 #36
  #51 #55 stay open. TASK COMPLETE — nothing committed.

## 2026-10-21 — #81 follow-up (user: "run browser test, and finish the job")
- 09:46 — Fixed the older-subscription case: Pro SubscriptionShipping Hooks::hasFreeShippingPackage() decides free vs paid
  from _arraysubs_shipping_package_total, or (when missing) from the stored method: empty or free_shipping:* = free.
  _arraysubs_shipping_total no longer decides (each switch overwrites it, so a paid "Flat rate" became "Subscription
  Shipping" on its 2nd switch). Doc: documentations/architecture/plan-switching.md (Pro Shipping Alignment, step 2).
  php -l clean, 1071 lines. No changelog change (released code never had either label bug).
  NEXT: re-run sh81 probes (scratchpad fix8x/final-verify/sh81/v2-*.php) + live browser test: fixture customer buys
  product 61 (overrides 150/100) with Free shipping, then its renewal must say "Subscription Shipping $100".
- 09:53 — LIVE BROWSER TEST PASSED (#81). Fixture customer 2595 (sqa-fix81-c1, registered fix-71-78) bought product 61
  ([QA] Physical Box Monthly, overrides 150/100) on the block checkout: only option "Free shipping", Direct bank
  transfer; order 97429: "Shipping: Free shipping", "ArraySubs initial shipping" $150, total $1,950. Sub 97430 stored
  flat_rate "Subscription Shipping", package 0, renewal 100. Admin set 97429 Processing in the order screen → sub
  active. Renewal order 97541 created with RenewalProcessor::createRenewalInvoice(97430) (same call the scheduled
  invoice job makes; the job itself refuses before the lead time): pending, $1,800 + shipping "Subscription
  Shipping" $100 = $1,900. Seen in the browser: My Account order view and the pay page "$100.00 via Subscription
  Shipping"; admin order screen line "Subscription Shipping $100.00, Renewal shipping: Yes". Invoice email (Mailpit,
  to the fixture) shows Amount $1,900.00 with no shipping line. Screenshots r8-81-* in
  qa/stripe-dev-support-2026-10-18/screenshots/. Fixtures left in place: sub 97430 active, orders 97429, 97541.
- 10:24 — Verify run 1 (wf_2e2cc237-8ec): #81 probe suite re-run passes; only change is L1 T2 keeping "Flat rate" 4.00.
  Review found F1 LOW (caused by the first follow-up: legacy subs from wp-admin/REST orders store a zero-cost line's
  own method, e.g. "Local pickup", which the method-prefix rule called paid), F2 LOW (free package + a charge not from
  the target's override, e.g. admin REST renewal total, still named "Free shipping"), F3 MEDIUM pre-existing in HEAD
  (blank "0" overrides zero renewal shipping on plan switches; amounts) -> filed as a new board issue, not fixed.
  Fixed F1+F2 in Pro Hooks: hasFreeShippingPackage() checks the purchase order's shipping line for the stored method
  when the package total is missing; the switch path relabels any charge > 0 on a free package; renewals without a
  pending switch go through nameFreePackageCharge() (same rule). 1107 lines, php -l clean. Read-only pass over all 34
  staging shipping subscriptions: 0 renewal label changes. Docs: plan-switching.md step 2, user-manual
  subscription-detail-cards.md (Shipping Method row). Pro changelog line 56 extended (existing subscriptions).
  NEXT: wait for verify run 2 (wf_c1fc2c1e-da1: suite + review probes p1/p2/p5/p7/p8 + adversarial review).
- 10:47 — Verify run 2 (wf_c1fc2c1e-da1) PASSED. Suite identical to run 1; review probes changed labels only, as intended
  (p1/p7 legacy zero-cost captures, p2 P4i/P6j/P6k -> "Subscription Shipping"; paid controls keep names); 0 money/tax
  changes; 105 rolled-back transactions clean; protected fixtures unchanged. Adversarial review pass; no PHP notices;
  the purchase order is loaded only for subscriptions without a stored package total. Known leftover (not fixed): for
  admin-REST-edited or legacy zero-cost captures the stored Shipping Method (admin detail, shipping REST) still names
  the free option while renewal orders say "Subscription Shipping" (staging has 0 such subscriptions). #84 note added.
  Two DB deadlocks at 09:52 UTC (lock-transient insert) came from probes running beside cron; all 19 actions in that
  window completed. Fixture 97430 left active (next invoice action 2026-11-21). #81 FOLLOW-UP COMPLETE — nothing committed.

## 2026-10-21 — #84 fix + push (user: "fix no 2. and push all the changes to git main")
- 11:16 — #84 fixed in Pro SubscriptionShipping Hooks: new getShippingOverride() (an override counts only when > 0;
  a variation uses its parent's only when it never stored the field) used by checkout capture, the checkout
  initial-shipping fee and the plan-switch context. Plan switch without an override now charges the checkout
  package cost (_arraysubs_shipping_package_total) for renewal and initial totals instead of an earlier plan's
  override; subscriptions without that meta keep their totals. php -l clean, 1117 lines. Docs: user-manual
  subscription-shipping/README.md (override + plan switch + variation), plan-switching.md step 2. Pro changelog +1.
  Verification wf_1322ef03-dd1 running. Push prep: 5 repos with changes (root docs, arraysubs, arraysubspro,
  user-manual [already 1 local commit ahead], qa); all fetched, behind=0. qa and user-manual remotes are PUBLIC;
  qa scan: no secrets/order keys/passwords; the user's gmail in evidence/dv61-admin-ui-sweep.json redacted.
  Pre-existing (already pushed) gmail addresses remain in qa/artifacts/issue-98-export-*.csv.
- 11:54 — Verify run wf_1322ef03-dd1: probe re-run PASS (all 50 differing lines expected; D/G/P8 paid packages now keep
  10.00 after "0"/"0" switches). Review FAIL on one MEDIUM caused by the fix: subscriptions captured by released code
  (no package meta = every existing subscription) carried the old plan's totals to a "0"/"0" target, e.g. product 67
  one-time 300 -> 197 renewed 300 (HEAD: 0). Fixed: getShippingPackageTotal() derives the missing package cost from
  the purchase order's shipping lines via getAllocatedOrderItemNetCharge() (free line = 0; charged line without a
  share or no shipping lines = unknown -> keep totals; current one-time type -> renewal 0). Staging check: 643/653/663
  (purchase orders without shipping lines, renewal 5) -> unknown -> keep 5; 97430 -> stored 0. 1158 lines, php -l ok.
  Known limitation kept: a renewal shipping amount set via the admin REST route resets to the checkout package cost
  on a switch to a no-override product (no admin UI). Re-verify wf_706aea83-c12 running.
- 11:55 — Own review: the one-time guard set only the renewal total; on an in-place switch core's renewal fallback to
  _arraysubs_shipping_total would still have charged the old 300. Fixed ($first_order_charge_only zeroes the initial
  total too). Rolled-back probes (scratchpad probe-84-onetime.php / probe-84-recurring.php): legacy one-time 300,
  unknown package -> 197 "0"/"0": renewal 0, initial 0, next renewal none; legacy recurring Flat rate 5, unknown
  package -> 197: keeps renewal 5, next renewal "Flat rate 5" (HEAD: none). 0 rows left. 1163 lines, php -l ok.
  Note: wf_706aea83-c12 started before this guard edit; judge its one-time/unknown results against this.
- 12:31 — Verify run wf_706aea83-c12: re-run PASS (all differences conform: legacy one-time 300 -> package 0/10,
  legacy carry-overs -> package cost, paid legacy keeps amount/name). Review MEDIUM (caused by the fix): a paid line
  with unreadable attribution plus a free line derived 0 -> renewal shipping stopped. Fixed per the reviewer's tested
  candidate: a charged line counts as another owner's only with a valid attribution envelope, otherwise the cost is
  unknown (keep totals). Live-copy probe (fix84b-review/final/out-p3-live.txt) equals the candidate's: M1 10, M2-M5
  and M8 keep current 10, M6/M7 free 0 / paid 10; all rolled back. Guard probes re-run OK. Hooks.php 1170 lines.
  Docs/changelog final. #84 CLOSED. Next: commit + push 5 repos (root docs, arraysubs, arraysubspro, user-manual, qa).
