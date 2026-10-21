# Stripe issues #56–#64: fix log (started 2026-10-19)

Resumable log for fixing the nine Stripe defects confirmed in
[`stripe-issues-56-61-deep-verification-2026-10-19.md`](stripe-issues-56-61-deep-verification-2026-10-19.md).
If the machine restarts, start at **Resume checklist**, then read **Current state** and the last
entries of **Timeline**.

## Request

User, 2026-10-19 ~18:00 UTC: pull latest `main` in every repo, then fix all of #56–#64 and test them
properly end to end. **Do not commit.** Keep this log so work can resume exactly where it stopped.

## Rules for this work

- Plugin code changes are now authorised (the earlier QA-run rule "do not change plugin code" applied
  to the testing phases only). No commits, no pushes.
- No backward compatibility is needed (AGENTS.md, development phase).
- Follow AGENTS.md QA issue fix workflow: move the task to `in-progress`, verify, plan, fix,
  browser-verify, then `closed` (or `blocked`). Run `kanban-md` only from `qa/issues/`.
- After each completed fix, append a `fixed:` entry to `arraysubs/changelog.draft.txt` and/or
  `arraysubspro/changelog.draft.txt` (no QA ids or fixture ids in those entries).
- Every touched file in `arraysubs`/`arraysubspro` must stay ≤ 3000 lines (`wc -l`).
- Settings changes go only through `stripe-dev-support-2026-10-18/scripts/snapshot-settings.php` →
  change → `restore-settings.php … apply` → `diff-settings.php` against
  `snapshots/settings-original-r2.json` (must be `all_identical: true`). Never change the Stripe
  gateway settings or ArraySubs Stripe extras (credentials).
- Run and clean only fixture actions; register every new customer/subscription/order/action with
  `stripe-dev-support-2026-10-18/scripts/register-fixture.php` (`SQA_PHASE=fix-56-64`).
- Never print or store secrets, full card data or order keys in this log or evidence
  (`grep -r wc_order_` before finishing).

## Resume checklist

1. `git -C arraysubs status --short && git -C arraysubs diff --stat` and the same for `arraysubspro`:
   the uncommitted diff **is** the fix work so far (nothing is committed).
2. Read **Current state** below and the newest **Timeline** entries; continue the first issue that
   is not `verified`.
3. `cd qa/issues && kanban-md list --compact --status in-progress,blocked` to see board state.
4. Browser helpers: `stripe-dev-support-2026-10-18/scripts/sqa-browser-helpers.sh` (no secrets).
   Fixture customer passwords live outside the repo in `/root/.sqa/sqa-passwords.txt` (mode 0600,
   uids 576-596); the helper reads that file by default (`SQA_PW`). Never copy it into `qa/` or
   commit it. If it is missing, reset with `wp user update <id> --user_pass=<new> --allow-root`.
5. If assets were changed: `cd arraysubs && npm run build` (and `arraysubspro` if touched); builds
   are untracked (`public/build`).
6. If a settings window was open when work stopped, restore immediately:
   `wp eval-file <abs>/stripe-dev-support-2026-10-18/scripts/restore-settings.php <snapshot> arraysubs_settings apply --allow-root`
   then diff against `settings-original-r2.json`.

## Fix plan (order of work)

1. **#57** — Stripe adapter passes the most specific code (`decline_code`, steered by `advice_code`)
   to the classifier, on both the synchronous renewal path and the webhook failure path; the
   classifier maps the missing Stripe decline codes; `renewal_payment_method_binding_invalid` maps to
   `missing_payment_method` (hard) instead of `unknown` (#62 item 3).
2. **#60** — clear `_payment_retry_next_attempt_at` at every stop exit of `scheduleNextRetry()` and on
   overdue cancellation; readers (GracePolicy snapshot, core REST detail, Pro Renewal Failures) report
   a next retry only while a retry `arraysubs_process_renewal` action is actually pending.
3. **#64 + #56** — `handlePaymentMethodUpdated()` never resets retry/failure state; same-PM events only
   refresh card display meta. New cards are bound only on explicit customer intent: a change of the
   Stripe customer's default payment method (`customer.updated` with a changed
   `invoice_settings.default_payment_method`, re-read from Stripe), verified attached + reusable, for
   that customer's Stripe subscriptions. Order-scoped SetupIntents keep their scoped binding. The dead
   `woocommerce_payment_token_added` listener is replaced by `woocommerce_payment_token_set_default`.
4. **#59** — the verification sweep queues a notice only for provider-proven authentication intents
   (local action context or a remote `requires_action`/`authentication_required` PI); delivery that
   finds a non-authentication PI clears the stale action context; stale markers left by the old sweep
   are cleaned by the sweep; subscription-level markers count for an order only when they reference it.
5. **#62** — automatic-renewal order-pay (order carries the Stripe attempt marker, auto-renew on)
   requires a reusable method and saves it; the order-context bridge rebinds a renewal only to a
   PaymentMethod attached to the subscription's Stripe customer and of a reusable type.
6. **#63** — Store API order payment of a renewal in a live verification context is refused with the
   same rule as the classic form; express checkout is hidden on verification pages; a paid renewal
   retires a zero-money plain-decline sibling PI so its markers are cleared.
7. **#58** — verification page works without a visible WooCommerce token (bound PM attached to the
   customer) and when WC Stripe saved cards are disabled.
8. **#61** — General settings get a "Payment retries" card (enabled, interval ladder, stop on hard
   decline); retry notes point to it; stale docs fixed.
9. Build assets, `php -l`, live browser E2E per issue, adversarial review, changelog drafts.

## Review round 1 (workflow `wf_d61f2fa4-ed3`) — patch checklist

Reports saved (session scratchpad, lost on reboot) and summarised here. `[ ]` = to do, `[x]` = done.

- [x] B1 HIGH — rebinding a subscription whose due renewal awaits authentication makes the verification page refuse (bound card ≠ PI card). Fix: after a rebind, when the renewal is due/unpaid and no renewal action is pending, queue `arraysubs_process_renewal` (+60 s) so the superseded auth PI is retired and the new card is charged.
- [x] B2 MEDIUM — first default (`previous=''`) moves nothing; after the default card was deleted, subscriptions stay on a detached card. Fix: on a first default, also move the customer's subscriptions whose card Stripe no longer has attached. (Case "portal from S, wanted card already default": no Stripe event; documented path = pay the renewal with that saved card.)
- [x] B3 LOW — compare-and-swap uses the value read inside the bind, not the decision-time card. Pass the expected card in.
- [x] B4 LOW — a late/redelivered payment-success capture can revert a newer binding. Skip when `_payment_method_updated_at` is newer than the order's payment; stamp it on capture rebinds.
- [x] B5 LOW — refresh path writes without compare-and-swap.
- [x] B6 LOW — rebind leaves stale wallet/brand/last4/expiry keys.
- [x] B7 LOW — order-scoped SetupIntent scope narrowed to [] when the REST context picked another subscription.
- [x] B8 LOW — bridge treats a Stripe read failure as a one-time card (should be a retryable failure).
- [x] B9 LOW — `customer.updated` calls Stripe before checking for local subscriptions.
- [x] R1 MEDIUM — ladder validation invisible → `onFinishFailed` toast (done 19:26, T7).
- [x] R2 MEDIUM — help text: retries stop when the subscription goes on hold (queued retry unscheduled); example 24, 48, 72 misleading.
- [x] R3 MEDIUM — auth predicates in `StripeSubscriptionCompatibility` accept only `code=authentication_required`; delegate paths also accept `decline_code`. Align (accept both).
- [x] R4 LOW — retries-off note: decide gateway ownership first (`BillingClockOwnership`), no manual-retry claim for provider-billed gateways.
- [x] R5 LOW — map `card_decline_rate_limit_exceeded` → `generic_decline`.
- [x] R6 LOW — `confirm_card_data` must not override a more specific hard reason.
- [x] R7 LOW — next-retry helper: drop the `_renewal_failure_resolved` gate (Pro "Mark resolved" does not unschedule the retry).
- [x] R8 LOW — General settings save: changed values from all form values (hidden fields), not only mounted ones.
- [x] M2 MEDIUM — the customer's own on-session 3DS with a new card on the Pay page (Woo Stripe reuses the renewal PI) was published as an off-session verification → permanent lock if abandoned. Now `queueRenewalRequiresActionNotice()` publishes only when the PI's card equals the order's attempt snapshot; otherwise outbox `superseded`.
- [x] L4 — Store API route regexes case-insensitive (`#…#i`), as WordPress matches routes.
- [x] L5 — sweep re-checks markers for every terminal outbox status (not only `superseded`), throttled to once per order per hour (`arraysubs_stripe_action_recheck_{order}` transient).
- [x] L6 — `requires_confirmation` handled like an authentication failure on the verification page (confirm with the bound card).
- [x] L7 — last installment of a fixed-length plan no longer forces card saving.
- [x] I7 — sweep remote branch requires renewal metadata matching the order, else `superseded` (no endless re-reads of Woo Stripe on-session intents).
- [x] I9 — token-free bound-card choice rendered only for the signed-in order owner.
- [x] I10 — stale comments/docblocks updated (event map, provisioner, finder, sibling retirement, trait headers).
- [x] Changelog drafts + docs (#61) — core draft +13 entries, Pro draft +1; docs updated by 19:59: `documentations/architecture/payment-retry-system.md`, `documentations/user-guides/subscription-lifecycle-and-plan-switching.md`, user-manual `payment-recovery.md`, `recovery-and-grace-flows.md`, `integration-recipes.md`, `status-grace-periods-and-trials.md` (user-manual is its own repo).

Applied 19:29–19:45 UTC; `php -l` clean on every touched file. Live re-tests after patching: see Timeline.

## Review round 2 (workflow `wf_0cbd7f6a-c97`, finished 20:26) — patch checklist

Reports: `stripe-dev-support-2026-10-18/evidence/fix/rev2-money-binding-2.md`, `rev2-retry-settings-2.md`.
All findings verified against code before patching.

- [x] MB-F1 HIGH (probe: settling detection; B1 regression live 20:56) — new-card renewal attempt (B1) during a settling renewal payment (bank debit, Radar review: order on-hold with a transaction id) → `getPendingRenewalOrder()` skips the settling order → second invoice + charge. Fix: B1 queues only for a `pending`/`failed` order with no transaction id, paid date, settled/pending-deadline meta, not awaiting settlement; root guard `hasSettlingRenewalPayment()` in `RenewalProcessor::processUnderLock()` and `createRenewalInvoice()` (pre-existing hazard: a queued retry + Pay-page bank debit).
- [x] MB-F2 MEDIUM (probe: unstamped → false) — bridge recency guard: empty `_payment_method_updated_at` → `strtotime(' UTC')` = now → real card changes skipped. Fix: empty/unparseable → false.
- [x] MB-F3 MEDIUM — B1 charges on-hold subscriptions (admin holds; on-hold is not billable). Fix: active/trial only.
- [x] MB-F4 LOW (CAS rebind live 20:49) — capture of ArraySubs' own off-session charge can revert a card change made seconds earlier. Fix: skip rebind when the paid card equals the order's attempt snapshot; rebind with compare-and-swap; drop stale display keys.
- [x] MB-F5 LOW — display-only refresh stamps `_payment_method_updated_at`. Fix: no stamp on refresh.
- [x] MB-F6 LOW — empty next payment date passes B1's due check; false note for manual/external/non-Stripe orders. Fix: early return; `isAutomaticRenewalOrder()` + order gateway.
- [x] MB-F7 LOW (hook ran on 69222 payment, correctly no-op) — abandoned-attempt cleanup skipped for good when the success webhook arrives before WooCommerce marks the order paid. Fix: also run it on `woocommerce_payment_complete`.
- [x] MB-F8 LOW (live 20:47) (pre-existing) — after an abandoned on-session 3DS (M2), retries find the renewal PI in `requires_action` without markers → `invalid` → order stuck. Fix: cancel the customer's abandoned attempt (exact scope, zero money, card ≠ attempt snapshot), then the normal terminal path mints the replacement.
- [x] MB-F9 LOW — M2 guard only for `pm_` snapshots (legacy `src_` ids may not echo back).
- [x] RS-F1 MEDIUM (probe) — Stripe 401 → `WC_Stripe_API::retrieve()` null → `[]` → `binding_invalid` → hard `missing_payment_method` → dunning stops. Fix: `normalizeStripeResponse()` returns `stripe_empty_response` WP_Error for null/non-object.
- [x] RS-F2 LOW (probe: Stripe resource_missing) — `resource_missing` PM reported as soft processing error forever. Fix: map to `binding_invalid` (hard).
- [x] RS-F3 LOW (probe; docs pending) — default ladder 24,24,24 vs 3-day hold + spread offset: 3rd retry usually removed by the hold. Fix: `GracePolicy::thresholds()` waits for a queued retry due within hold + spread offset + 1 h; help text/docs.
- [x] RS-F4 LOW — B1 skips when the renewal action is running (`isScheduled` counts it). Fix: skip only for a pending action.
- [x] RS-minor — hidden-ladder toast names a field the admin cannot see. Fix: add how to reach it.

## Review round 3 (one agent, read-only, finished 21:29) — patch checklist

- [x] R3-H1 HIGH (code; no live outage test) (pre-existing; makes the API-key changelog claim false for retries) — a Stripe read error while resolving the stored PI on a retry returns `pending` + 2-day deadline; `handleManualPaymentPending` schedules nothing and the reconciler skips failed orders → no attempt ever again. Fix: queue a recheck (+1 h) for active/trial subscriptions when the stored intent cannot be read.
- [x] R3-M1 MEDIUM (probe) — `resource_missing` also covers a test/live mode or account mismatch ("a similar object exists in live mode") → hard decline for working cards. Fix: hard only when the message has no "similar object exists" and the customer is readable in this account.
- [x] R3-M2a MEDIUM (probe + live 21:49) — abandoned-attempt cancel has no recency guard: a +60 s new-card attempt can cancel a customer's live 3DS on the Pay page. Fix: record when ArraySubs saw the customer's attempt (requires_action webhook) and defer the cancel for 30 min.
- [ ] R3-M2b MEDIUM — NOT fixed: pre-existing (any stored PI that ArraySubs did not create dead-ends the same way); filed as #65 — second abandoned visit: Woo Stripe creates a new PI without ArraySubs metadata → classification `invalid` → dead end. (Investigate.)
- [x] R3-L1 LOW (probe) — `as_next_scheduled_action()` returns true for a running action even when a pending one exists → B1 replaces a pending retry/next-cycle action; helper reports now() during any run. Fix: query pending explicitly.
- [x] R3-L2 LOW (+5 min while a run is in progress; lock-contention re-queue not changed) — the +60 s attempt can be dropped on lock contention while an old-card run is in flight. Fix: +5 min when a run is in progress (and/or re-queue on contention).
- [x] R3-L3 LOW (probe; current invoice itself still resolved by the gateway) — `hasSettlingRenewalPayment()` ignores a paid-but-unprocessed pointer order and runs only when `getPendingRenewalOrder()` found nothing. Fix: include paid-unprocessed; check first.
- [x] R3-L4 LOW (probe) — hold-wait window ignores queue lateness. Fix: wait for a queued retry whose planned position in the configured schedule fits the window, not a time bound.
- [ ] R3-ctx (pre-existing, out of scope; filed as #66) — a Pay-page SEPA/review payment (transaction id, on-hold, no deadline) is not "awaiting settlement" for the grace sweeps → held/cancelled while money is in flight. File as a new QA issue.

## Review round 4 (one agent, read-only, round-3 diff) — patch checklist

No HIGH. Report saved as `stripe-dev-support-2026-10-18/evidence/fix/rev4-round3-review.md`.

- [x] R4-M1 MEDIUM (live 22:34) — a new card loses to a far-future retry: `queueRenewalAttempt()` refuses whenever any retry is pending, so with a long schedule (24/72/168 h, 3-day grace) the hold removes that retry and the new card is never tried. Fix: the new-card attempt replaces a pending attempt that is later than its own time.
- [x] R4-M1b LOW (code) — a +5 min new-card attempt queued during a running attempt is pushed out a full interval when that attempt soft-declines (`scheduleNextRetry()` → `scheduleSingle()` dedupe). Fix: `scheduleNextRetry()` keeps an earlier pending attempt as the retry.
- [x] R4-L1 LOW (code) — a stale `_arraysubs_pending_deadline` survives fresh charges started outside the terminal path (superseded path, reconcile-before-retry) → grace paused and new-card attempts refused up to 2 days. Fix: clear it right before `ChargeAttemptGuard::beginAttempt()`.
- [x] R4-L2 LOW (code) — a paid-but-unprocessed current invoice can halt billing silently (e.g. refunded after a blocked lifecycle run). Fix: ignore refunded/cancelled invoices; note once when blocking on one paid over an hour ago.
- [x] R4-L3 LOW (probe) — soft `resource_missing` also covers a real account migration (customer gone) → ladder runs out without asking the customer. Fix: soft only for Stripe's "similar object exists" (other mode).

## Repo state at start

| Repo | Pulled to | Notes |
|---|---|---|
| root (`plugins`) | `5dbace2` | docs only |
| `arraysubs` | `b9d6c44` | 54 files incoming, none in the Stripe gateway code |
| `arraysubspro` | `ffa7bc8` | removed old Paddle/Stripe settings assets |
| `qa` | `adcb059` | already current; earlier QA work uncommitted (report, issues 055–064, evidence) |
| `user-portal`, `web-content`, `screenshot-marker` | current | nothing incoming |
| `user-manual` | `b70d132` | 1 local commit ahead of origin (pre-existing, left alone) |

## Current state

| Issue | Status | Fix summary | Files | Verification |
|---|---|---|---|---|
| #56 | **verified** (T4a/T4b/T4c, B1, B2 path in code; round 2: B1 regression 68753/69364) | `customer.updated` with a changed `invoice_settings.default_payment_method`: re-read customer; move subscriptions billed to the **previous** default plus the subscription whose billing-portal visit is open (`_arraysubs_stripe_portal_requested_at`, 1 h) to the new default after Stripe proves it attached + reusable; compare-and-swap write; first defaults (WooCommerce auto-default) move nothing; attach/SetupIntent events never bind on their own; order-scoped SetupIntent binds only an unbound subscription; deliberate refusals return 200; Woo Stripe card cache cleared after a bind; portal return URL validated | core `StripeWebhookEventsTrait.php`, `StripePaymentMethodsTrait.php` (verifier split `verifyStripePaymentMethodIsReusable()`), `StripeWebhooksTrait.php` (`previous_attributes`), `StripeDelegate.php` (dead listener removed, portal constants) | smoke OK |
| #57 | **verified** (T2, T9; round 2: probes for null response + resource_missing) | Stripe adapter classifies `decline_code`+`advice_code` (`stripeFailureCode()`); classifier maps missing Stripe codes; `do_not_honor` now soft; auth check also reads `decline_code` | core `functions/gateway-helpers.php`, `Gateways/Stripe/Traits/StripeApiTrait.php`, `StripeRenewalPaymentTrait.php`, `StripeWebhookEventsTrait.php` | WP-CLI probe 18 cases OK (generic/insufficient/do_not_honor soft; lost/stolen/do_not_try_again/confirm_card_data hard; binding_invalid → missing_payment_method hard) |
| #58 | **verified** (T5) | verification page lists exactly the subscription's card: its WC token if present, else a token-free `arraysubs-bound` choice; new-card form hidden; saved-card list forced on for that request (stores with saved cards off); server accepts the token-free choice only for the order's signed-in customer, with no other credential posted, after Stripe confirms the bound PM is attached to the customer and reusable; confirmation path unchanged (same PI, bound PM) | core `Services/StripeSubscriptionCompatibility.php` (2824 lines) | |
| #59 | **verified** (T1, T10, sweep 101447, M2; round 2: F8 abandoned attempt 69222) | sweep queues only provider-proven auth intents (local context or remote `requires_action`/`authentication_required`); non-auth → outbox `superseded` without markers; delivery + sweep clear stale markers of zero-money declines (`clearNonAuthenticationActionContext()`); subscription-level markers count only when they name this order (`subscriptionActionContextNamesOrder()`) | core `StripeRequiresActionTrait.php`, `Services/StripeSubscriptionCompatibility.php` | natural sweep action 101447 (18:29:07Z) cleared markers on 68473, 68550, 68614, 68694, 68903, 69022; genuine auth 68494/68846 kept — `evidence/fix/fix59-markers-*.txt` |
| #60 | **verified** (T2, T8; round 2: hold waits for edge retry, probe) | `arraysubs_get_payment_retry_next_attempt_at()` reports a retry only while a retry action is pending; meta cleared at every non-scheduling exit and on overdue cancellation; readers switched | core `functions/gateway-helpers.php`, `RenewalProcessor.php`, `GracePolicy.php`, `SubscriptionController.php`; Pro `Audits/REST/RenewalFailureController.php` | probe: 68512 (exhausted) and 68525 (cancelled) now report null |
| #61 | **verified** (T7, T7b) | General settings → new "Payment Retries" card (Automatic Payments module): enable switch, comma-separated hours ladder with validation (1–720 h, ≤10 steps), stop-on-hard-decline switch, gateway note; retries-off note now says whether the store setting or the gateway (PayPal/Paddle) turned retries off; docblock fixed; docs pending | core `resources/pages/Settings/GeneralSettings.jsx` (built), `RenewalProcessor.php`, `functions/gateway-helpers.php` | `npm run build` OK 18:40 |
| #62 | **verified** (T1, T9; round 2: CAS rebind 69222 → 69202) | `StripeRecurringPaymentPolicy::isAutomaticRenewalOrder()`; `orderRequiresReusableMethod()` true for automatic Stripe renewals (forces save/off-session on order-pay, classic + Store API); bridge rebinds a renewal only to a PM attached to the customer and reusable, else keeps the binding + one warning note | core `StripeRecurringPaymentPolicy.php`, `StripeOrderContextBridge.php` | smoke: 68473 is an automatic renewal order |
| #63 | **verified** (T1, T6) | Store API order payment refused (RouteException before WC saves addresses, plus `woocommerce_checkout_validate_order_before_payment`) while the renewal's PI awaits authentication (`hasLiveRenewalVerification()`, fail-closed on Stripe read errors); Woo Stripe express buttons hidden on verification pages (`wc_stripe_show_payment_request_on_checkout`); paid renewal retires zero-money plain-decline sibling PI; renewal orders skip checkout subscription creation; non-locked renewals paid by Link now save + rebind only reusable (#62) | core `Services/StripeSubscriptionCompatibility.php`, `StripeRequiresActionTrait.php`, `SubscriptionCheckout/Services/Traits/SubscriptionCreationTrait.php` | probe: 68494/68846 live=true, 68473/68903 live=false |
| #64 | **verified** (T3) | payment-method events never call `arraysubs_reset_payment_retry_attempts()`; same-card events only refresh brand/last4/expiry/wallet when changed (`refreshBoundStripePaymentMethod()`) | core `StripeWebhookEventsTrait.php`, `StripePaymentMethodsTrait.php` | |

## Observation points after the fix (UTC, read 2026-10-19 20:07)

These natural renewals run the fixed code. Check each one with
`wp eval-file <abs>/stripe-dev-support-2026-10-18/scripts/fix-sub-state.php <sub> --allow-root`.
They supersede the 2026-10-20 table in the deep-verification report.

| When (UTC) | Sub | Action(s) | Bound card now | Expect |
|---|---|---|---|---|
| 2026-10-20 09:53:38 | 68466 | 101481 invoice, 101482 renewal | `pm_1USClq…` (3DS card 3184) | authentication required → one verification email, markers on the renewal, Pay page offers only the bound card (#58/#59) |
| 2026-10-20 11:13:36 | 68677 | 101076 / 101077 | `pm_1USM4i…` (Mastercard 3222, rebound T4c) | charged on the new card (#56) |
| 2026-10-20 11:25:07 | 68439 | 101477 / 101478 | `pm_1USLi8…` (rebound T1) | charged on `pm_1USLi8…` (#62) |
| 2026-10-20 11:45:11 | 68770 | 101230 / 101231 | `pm_1USG0T…` (Link PM, `customer=null`, old #62 damage) | fails before charging with `renewal_payment_method_binding_invalid` → category `missing_payment_method`, **no** retry scheduled, customer asked to update the card (#57) |
| 2026-10-20 14:45:21 | 68599 | 101564 / 101565 | `pm_1USMZe…` (rebound M2) | charged on the new card |
| 2026-10-20 15:53:51 | 68781 | 101551 / 101552 | `pm_1USMNE…` (rebound B1) | charged on the new card |
| 2026-10-21 03:25:13 | 69202 | 101708 invoice (2026-10-20 21:25:13), 101709 renewal | `pm_1USNTx…` (Visa 4242, bound by Make default in the round-4 M1 test) | charged on 4242. (Earlier legs 101503/101607/101609/101610/101665/101667/101669/101670/101695/101696/101702/101703 were run, cancelled or replaced during the F8, CAS, M2a and M1 tests.) |
| 2026-10-20 19:12:54 | 69245 | 101513 / 101514 | `pm_1USLzz…` (Mastercard 4444, rebound T4b) | charged on the new card (#56) |
| 2026-10-20 21:08:30 | 68788 | 101536 / 101537 | `pm_1USM9B…` (rebound T9) | charged on the new card (#62) |
| 2026-10-20 23:10:51 | 68709 | 101560 / 101561 | `pm_1USMUl…` (rebound B1) | charged on the new card |
| 2026-10-20 23:10:25 | 68753 | 101623 invoice (17:10:25), 101624 renewal | `pm_1USNZa…` (Visa 4242, rebound in the round-2 B1 regression) | charged on the new card |
| 2026-10-21 01:02:42 | 69234 | 101508 / 101509 | `pm_1USM2W…` (Visa 5556, rebound T4a) | charged on the new card (#56) |

## Timeline (UTC, append-only)

- 18:06 — Pulled all repos (table above). Created this log.
- 18:10 — Background read-only investigation workflow `wf_3ffa815d-49f` (WC Stripe order-pay/Store API/tokens/3DS page; retry/settings readers).
- 18:18 — #57 implemented + probed (board: in-progress).
- 18:23 — #60 implemented + probed (board: in-progress).
- 18:27 — #59 implemented (sweep/delivery/route) + #63 sibling retirement.
- 18:29 — Natural sweep 101447 ran the new code: stale markers cleared on 6 declined orders, auth orders untouched.
- 18:31 — Renewal orders no longer enter checkout subscription creation (Store API order-pay logged `arraysubs_checkout_recovery_ambiguous_item`, part of #63).
- 18:36 — #62 policy + bridge; #56/#64 handler rewrite + helpers; smoke OK (board: in-progress).
- 18:40 — #61 settings card + note; core assets built (`arraysubs/public/build`, includes the pulled JSX changes).
- 18:43 — #63 Store API refusal + express hiding; probe OK.
- 18:45 — Investigation reports read (scratchpad `inv-*.md`). Applied: unknown Stripe decline codes → `generic_decline`; velocity codes soft; next-retry helper requires an open failure; respreader uses helper; ladder capped at 10; Store API gate moved to `rest_dispatch_request` (before any write; covers `wc/store` alias + batch); express buttons hidden on all automatic renewal pay pages; verification page excluded from the new-card saving flow.
- 18:51 — #56 scope revised (previous-default subscriptions + portal-requested one; local first-token hook removed). #58 token-free bound-card choice implemented.
- 18:51 — Adversarial review workflow `wf_d61f2fa4-ed3` launched (read-only, 4 reviewers).
- 18:55 — **T1 PASS (#59, #62, #63-express):** customer 579 opened the Pay page of 68473 (previously locked): all saved cards + new card offered, no express buttons, save box forced/hidden; paid with a new 4242 card → same PI `pi_3USDE3…` confirmed (one charge `ch_3USDE3…`), new PM `pm_1USLi8…` attached to `cus_VT8u…`, token 63 updated, subscription 68439 rebound to it, schedule advanced (next 2026-10-20 09:22:51). Evidence `evidence/fix/fix-t1-*`, screenshots `fix-t1-*`. Follow-up: 68439's 2026-10-20 ~11:25Z renewal must charge the new card.
- 18:57 — **T5 PASS (#58):** customer 580 (no WC token) opened the 68494 verification link: page offered only "Visa ending in 3184" (token-free `arraysubs-bound`), new-card form hidden, no express; Pay → 3DS test modal → COMPLETE → order completed on the same PI `pi_3USCsO…` (one charge `ch_3USCsO…`; Stripe lists only the signup PI and this one), markers cleared, next renewal scheduled 2026-10-20 09:53Z. Evidence `evidence/fix/fix-t5-*`, screenshots `fix-t5-*`. Cosmetic: an empty bordered box remains under the card (hidden UPE wrapper) — check.
- 19:01 — **T6 PASS (#63):** customer 590 on the 68846 verification page: no express buttons (only Woo Stripe's attribution inputs element), bound card offered. Store API order payment (`POST /wp-json/wc/store/v1/checkout/68846` and the unversioned `/wp-json/wc/store/checkout/68846`, non-chargeable payload) → both **409 `arraysubs_renewal_verification_required`**; order still pending/unpaid, order + user billing email/phone unchanged. Control: same probe on unlocked failed renewal 68614 (customer 581, its own billing data) → normal validation 400 (reusable-method rule), not the gate; order unchanged.
- 19:06 — **T2 PASS (#57, #60 positive, #59 no-marking pending sweep):** new fixture customer 593 `sqa-fix57-decline` → trial checkout 69201 (card 0341) → sub 69202; `endTrial()` + `setNextPaymentDate(+90 s)`; invoice 101496 ran naturally, fixture action 101497 run explicitly → renewal 69222 declined: Stripe `code=card_declined, decline_code=generic_decline, advice_code=try_again_later` → stored category **generic_decline (soft)**, "Scheduled retry 1 of 3" (action 101503 at 2026-10-20 19:06:41Z) under default settings; developer `recovery.next_retry_at` = 1792523201 = the action; customer email "Reason: the card was declined."; admin detail shows Retry Payment + notes. Fixtures 593, 69201, 69202, 69222, actions 101496/101497/101503 registered (`SQA_PHASE=fix-56-64`).
- 19:09 — **T8 PASS (#60 display):** Pro Audits → Renewal Failures: #69202 "Retries: 1 Next: Oct 21, 2026, 1:06 AM" (site time); #68512 (exhausted) "Retries: 3" with **no** stale Next. Screenshot `fix-t8-renewal-failures.png`.
- 19:09 — **T3 PASS (#64):** real Stripe `payment_method.updated` (`evt_1USLvB…`, billing-details edit of 69202's bound card via `WC_Stripe_API`) processed 19:08:34 for sub 69202 / order 69222 → every failure/retry key identical, derived next retry unchanged, retry 101503 still pending. Also seen: `customer.updated` events for first defaults (593's checkout, 579's token update) bound nothing, as designed.
- 19:14 — **T4b PASS (#56, My Account):** fixtures 594/595 bought SQA Daily Stripe Plan with 4242 (orders 69233/69244 → subs 69234/69245; checkout card = Stripe default). Customer 595 added Mastercard 4444 in My Account → `payment_method.attached` + `setup_intent.succeeded` (19:13:35-36) bound nothing; clicked **Make default** → `customer.updated` 19:14:01 → 69245 bound to `pm_1USLzz…` (Mastercard 4444, attached to `cus_VTIZ…`). Evidence `fix-t4b-*`.
- 19:16 — **T4a PASS (#56, billing portal, follow-the-default):** customer 594 → 69234 page → Update payment method → Stripe portal (portal marker written on 69234) → Add payment method Visa debit 5556 with "Use as default" → `customer.updated` 19:16:14 → 69234 bound to `pm_1USM2W…` (5556, attached); marker cleared; Return to store → subscription page shows "Visa ending in 5556".
- 19:18 — **T4c PASS (#56, portal-scoped rule):** 68677 (customer 584) was on Visa 4242 `pm_1USEEs…` while the Stripe default was another card `pm_1USERy…`; portal opened from 68677, new default Mastercard 3222 → 68677 bound to `pm_1USM4i…` (Stripe default now the same). Note: changes the 2026-10-20 11:13 observation (68677 now renews on Mastercard 3222).
- 19:20 — **T10 PASS (#59):** natural sweep 101483 (19:12:05Z) processed the new ordinary decline 69222: outbox `superseded`, **no** `_arraysubs_payment_action_*` markers on order or subscription; customer 593's Pay page for 69222 offers Stripe (saved card + new) and the other gateways, no express buttons (`fix-t10-69222-paypage.png`).
- 19:23 — **T9 PASS (#62 repair + binding_invalid classification):** 68788 (customer 588) was still bound to the one-time card `pm_1USEmE…` (`customer=null`) from the old bug. `setNextPaymentDate(+90 s)`; invoice 101520 + renewal 101521 run (fixture actions; replaced the 2026-10-20 08:36/14:36 actions) → renewal 69261 failed closed **before any PaymentIntent** with `renewal_payment_method_binding_invalid` → category **missing_payment_method (hard)**, no retry scheduled. Customer 588 paid 69261 on its Pay page with a new Mastercard 4444 → force-saved, attached to `cus_VTB3…` → 68788 rebound to `pm_1USM9B…` although the order had no automatic attempt marker. Evidence `fix-t9-*`.
- 19:24 — **SETTINGS WINDOW OPEN (T7 #61)**: snapshot `snapshots/settings-before-fix61-ui.json`. If work stops here, restore with `restore-settings.php snapshots/settings-before-fix61-ui.json arraysubs_settings apply` and diff vs `settings-original-r2.json`.
- 19:27 — **T7 PASS (#61):** General settings → "Payment Retries" card shows the stored values (switch on, "24, 24, 24", stop-on-hard on) with the explanations. FormBuilder fields never print rule errors (pre-existing), so an invalid ladder blocked Save silently → added a page-level `onFinishFailed` toast (rebuilt 19:26). "24, abc" → toast "Each retry must be a number of hours from 1 to 720.", nothing saved; "12, 48" + stop-on-hard off → "Settings saved!", stored `{"enabled":true,"interval_hours":[12,48],"stop_on_hard_decline":false}`, effective config `[43200,172800]`, reload shows "12, 48". **SETTINGS WINDOW CLOSED 19:27:12:** restored from `settings-before-fix61-ui.json` → sha256 `8cacd06b…` identical; `diff-original-vs-after-fix61-ui-restore.json` `all_identical: true`.
- 19:39 — **B1 live test, first run:** customer 590 changed card in the portal while 68846 awaited authentication → 68781 rebound to 4242 `pm_1USMNE…`, attempt 101547 queued (+60 s) and ran 19:39:05: the superseded PI `pi_3USElv…` was **cancelled** (abandoned, zero money) and markers cleared, but **no new charge** (pending result). Root cause (pre-existing, reachable only now that cards can change): `clearSupersededAuthenticationIntentContext()` calls `clearRequiresActionContext()`, which deletes the `_arraysubs_stripe_retiring_authentication_intent_id` marker, then demanded that marker still name the intent → `context_changed`. Fixed in `StripeIntentResolutionTrait.php` (only a marker naming a *different* intent fails). Next: fresh 3DS re-test.
- 19:42 — 68781 finished with one more fixture attempt (101549): new PI `pi_3USMRj…` succeeded on the new card; old `pi_3USElv…` cancelled with 0 received. One charge for 68846.
- 19:47 — **B1 PASS (fresh, single attempt):** 68709 (customer 586, 3DS card 3184) renewal pulled forward (fixture actions 101553/101554) → renewal 69304 pending with verification markers (`pi_3USMTt…`). Customer 586 opened the portal from 68709 and added Mastercard 4444 as default → 68709 rebound → attempt 101558 queued and ran: superseded PI `pi_3USMTt…` cancelled (0 received), new PI `pi_3USMWU…` succeeded on the new card, order completed, schedule advanced. Evidence `fix-b1b-*`.
- 19:50 — **M2 PASS:** customer 581 started 3DS with a new card (3184) on 68614's Pay page (Woo Stripe reused renewal PI `pi_3USDAt…`) and abandoned it → `payment_intent.requires_action` processed, **no** verification markers (order stayed an ordinary failed renewal; Pay page unlocked). Isolated probe (`scripts/fix-m2-probe.php`, order 69222 with a `superseded` outbox): an auth intent carrying another card than the attempt snapshot → no markers. Customer then paid 68614 with 4242 → new PI `pi_3USMZh…` succeeded, 68599 rebound to the new attached card.
- 19:52 — Added `retireAbandonedRenewalAttemptIntent()` (payment-success path): when a renewal is paid with a different PI and no markers exist, cancel the renewal attempt's own zero-money open PI (exact order/subscription metadata) so a late 3DS completion cannot collect twice. Applied to 68614 via `scripts/fix-abandoned-pi-probe.php`: `pi_3USDAt…` cancelled (abandoned, 0 received).
- 19:59 — Docs updated for #57/#60/#61 (see review checklist). Review round 2 workflow `wf_0cbd7f6a-c97` (read-only; money-binding + retry-settings) still running; next: process its findings, re-test settings save after R8, close kanban tasks, final checks.
- 20:01:50 — **SETTINGS WINDOW OPEN (R8 save re-test, #61)**: snapshot `snapshots/settings-before-fix61-r8.json` (identical to original-r2). If work stops here, restore with `restore-settings.php snapshots/settings-before-fix61-r8.json arraysubs_settings apply` and diff vs `settings-original-r2.json`. GeneralSettings.jsx: hidden ladder validated on submit (rebuilt 20:01).
- 20:03 — **T7b PASS (#61 save after R8 + hidden-ladder check):** (A) ladder "6, 48, 168", then retries switched off (ladder hidden) → Save → "Settings saved!", stored `{"enabled":false,"interval_hours":[6,48,168],"stop_on_hard_decline":true}`; diff vs window snapshot changed only `payment_retry.enabled` + `interval_hours.0-2`, no other option. (B) reload: switch off; switching on shows "6, 48, 168". (C) ladder "abc", switched off, Save → toast "Each retry must be a number of hours from 1 to 720.", store unchanged. Screenshots `fix-r8-hidden-ladder-saved.png`, `fix-r8-hidden-invalid-refused.png`. **SETTINGS WINDOW CLOSED 20:03:54:** restored from `settings-before-fix61-r8.json` → sha256 `8cacd06b…`; `diff-original-vs-after-fix61-r8-restore.json` `all_identical: true`.
- 20:07 — Observation table added (section above Timeline). Final checks so far: `php -l` clean on every touched PHP file; largest touched file `StripeSubscriptionCompatibility.php` 2847 lines; no `wc_order_` keys or Stripe secrets in `qa/` evidence, snapshots, issues or this log. Project memory updated. Per-issue closing notes drafted (scratchpad `issue-notes/0NN.md`; regenerate from Current state if lost).
- 20:07 — Empty bordered box under the card on the verification page: also present on the stock WooCommerce Stripe saved-card view before the fix (`screenshots/dv58-16-verify-saved-card-selected.png`), so it is WooCommerce Stripe/theme styling, not ArraySubs. Left alone.
- 20:36 — Review round 2 finished at 20:26 (2 reviewers, 13 findings + 1 minor). All verified against code; patch checklist added above. Starting patches.
- 20:42 — Round-2 patches applied (all 14 items), `php -l` clean, core assets rebuilt 20:42. Probe `scripts/fix-r2-probes.php` (read-only, in-process filters) → `evidence/fix/fix-r2-probes-before-live.json`: null response → `stripe_empty_response` (soft `processing_error`); missing PM → Stripe `resource_missing` → `binding_invalid` → `missing_payment_method` (hard); unstamped binding not "newer"; settling invoice detected (on-hold + transaction id) / not for failed 69222; hold waits for a retry at the window edge or 30 min past it (offset 17876 s) → `hold_eligible_at` = retry + 1 h, not for a retry 12 h past the window.
- 20:47 — **F8 PASS (live):** customer 593 opened 69222's Pay page, entered 3DS card 3184, reached the 3DS challenge and left. Renewal PI `pi_3USLtL…` (attempt result) → `requires_action` with the new card `pm_1USNQs…`, no markers. Ran queued retry 101503 → note "Cancelled the unfinished Stripe payment pi_3USLtL… that the customer started…", PI canceled (abandoned, 0 received), new off-session PI `pi_3USNSH…` on the card on file (0341) → `generic_decline` (soft) → "Scheduled retry 2 of 3" (fixture action 101607, 2026-10-20 20:46:50), failure email sent. Before the fix this retry returned "could not be matched safely" and nothing could charge the order again. Evidence `fix-r2-f8-69222-*.json`, screenshots `fix-r2-f8-69222-*.png`.
- 20:49 — **CAS rebind + payment-complete cleanup PASS (live, MB-F4/F7 paths):** customer 593 paid 69222 on its Pay page with a new 4242 card → order completed, one charge (`pi_3USNSH…` reused by Woo Stripe, 731 received; `setup_future_usage=off_session`), 69202 rebound by the bridge's compare-and-swap path to `pm_1USNTx…` (brand/last4/expiry replaced, wallet empty, `_payment_method_updated_at` stamped), failure + retry state cleared by the payment, retry 101607 cancelled, next cycle queued (101609/101610, registered). Attempt PI = paid PI, so the new payment-complete cleanup correctly did nothing. Evidence `fix-r2-cas-69222-after-pay.json`, `fix-r2-cas-69202-notes.txt`. (Registry: two mistaken entries from a shell split, ids 18 and 0, removed; file re-encoded in the writer's format, diff is append-only.)
- 20:56 — **B1 regression PASS (round-2 guards, live):** fixture 68753 (customer 587, 3DS card 3184, Stripe default) renewal pulled forward (fixture actions 101612/101613) → renewal 69364 pending, `pi_3USNYm…` `authentication_required`, markers + verification email. Customer 587 added Visa 4242 in My Account (68753 unchanged), then Make default 20:54:50 → `customer.updated` → 68753 bound to `pm_1USNZa…` ("Payment method updated by Stripe: Visa ending in 4242"), "Renewal order #69364 is still unpaid, so the new payment method will be charged in about a minute." → attempt 101621 ran 20:56:05: superseded PI cancelled (abandoned, 0 received), new PI `pi_3USNbF…` succeeded (731), order completed, markers cleared, next cycle queued (101623/101624, registered). Evidence `fix-r2-b1-*`.
- 20:58 — Round-3 read-only review of the round-2 diff launched (one agent, background). Meanwhile: docs + changelog for round 2.
- 21:32 — Round-3 review finished 21:29 (findings in the checklist above; report `evidence/fix/rev3-round2-review.md`). Verifying findings against code.
- 21:43 — Round-3 patches applied, `php -l` clean: `ActionScheduler::getNextPendingTime()`; next-retry helper pending-first; B1 pending-only + 5 min while a run is in progress + shared `queueRenewalAttempt()`; `resource_missing` hard only for a readable customer and no "similar object exists"; `queueUnreadableIntentRecheck()` (hourly for a day, `_arraysubs_stripe_unreadable_since`); stale pending deadline cleared when a stored intent is proven dead; `_arraysubs_customer_attempt_seen` + 30-min grace before cancelling a customer attempt; settling guard covers paid-unprocessed and spares the current invoice; GracePolicy waits for a queued retry by planned schedule position. Probes `scripts/fix-r3-probes.php` → `evidence/fix/fix-r3-probes.json` all as expected. Docs + changelog wording updated (still 120 lines).
- 21:49 — **R3-M2a PASS (live):** 69202 moved to the declining 0341 card via My Account Make default (4242 then 0341; no charge, nothing unpaid). Renewal pulled forward (fixture actions 101661/101662) → 69406 declined `generic_decline`, retry 1 (101665). Customer 593 opened the Pay page, entered 3DS card 3184 and stopped at the challenge; the webhook stored `_arraysubs_customer_attempt_seen` = `pi_3USOP7…|pm_1USOPv…|21:48:33`. Retry 101665 run at 21:48:56 → PI left `requires_action` (not cancelled), no charge, attempt 101667 queued at 22:18:33 (seen + 30 min). Customer then pressed COMPLETE → order 69406 completed, one charge (731), 69202 bound to the 3184 card, failure cleared, 101667 cancelled by the payment, next cycle 101669/101670 (registered). Evidence `fix-r3-m2a-*`.
- 21:51 — Found while checking that run: Pro's scheduled-job log marked the deferred run "Failed — Your card was declined." (it attributes any failure from the last 15 minutes to a renewal run). Fixed in `arraysubspro/src/Features/Audits/Services/ScheduledJobLogger.php`: only a failure recorded after the run started counts (`rememberRunStart()` on `action_scheduler_before_execute`; 15-minute fallback when the start is unknown). Probe `evidence/fix/fix-r3-logger-probe.json`. Pro changelog +1 entry (49 lines).
- 21:53 — Round-4 read-only review of the round-3 diff launched (background). Filed new open issues for pre-existing gaps not fixed here: #65 (WooCommerce-created Pay-page PaymentIntent dead-ends automatic attempts; R3-M2b), #66 (Pay-page bank debit / review not "settling" for grace sweeps; R3-ctx), #67 (asynchronous Stripe failures not recorded/retried), #68 (Mollie rebinder dead hook, low).
- 22:20 — Round-4 review finished (no HIGH; report `evidence/fix/rev4-round3-review.md`). Patched: new-card attempt replaces a later pending attempt (`queueRenewalAttempt(..., true)`); `scheduleNextRetry()` keeps an earlier pending attempt as the retry (`ActionScheduler::getNextPendingAction()`); `_arraysubs_pending_deadline` cleared right before `ChargeAttemptGuard::beginAttempt()` (the round-3 delete in the terminal path removed as redundant); refunded/cancelled paid invoices no longer block, one-time note after an hour (`_arraysubs_unprocessed_payment_noted`); `resource_missing` soft only for Stripe's "similar object exists" (`isStripeObjectInOtherMode()`). Probes → `evidence/fix/fix-r4-probes.json` as expected.
- 22:34 — **R4-M1 PASS (live):** 69202 moved to the declining 0341 card (Stripe test API default changes 3184 then 0341, the same `customer.updated` the portal sends; fixture customer only). Renewal pulled forward (101695/101696) → 69436 declined `generic_decline`, retry 1 101702 queued for 2026-10-20 22:27:43. Customer 593 Make default Visa 4242 at 22:28:18 → 69202 bound to `pm_1USNTx…`, retry 101702 **cancelled and replaced** by new-card attempt 101703 (22:33:22) → ran 22:34:05 → `pi` succeeded (731), order completed, failure/retry cleared, next cycle 101708/101709 (registered). The note said "in about 5 minutes": `isScheduled()` also counts pending actions, so the "run in progress" test was wrong; fixed to `true === getNextScheduledTime()` (true only while running) → 1 minute in this case from now on. Evidence `fix-r4-m1-*`.
- 22:36 — **DONE.** #56-#64 moved to `closed` on `qa/issues` with verification notes appended to each task file. New open issues #65-#68 for pre-existing gaps not fixed here. Final checks: `php -l` clean on every touched PHP file; no touched file over 3000 lines (largest `StripeSubscriptionCompatibility.php` 2847); no order keys or Stripe secrets in `qa/`. Nothing committed: `arraysubs` HEAD `b9d6c44`, `arraysubspro` HEAD `ffa7bc8` (as pulled); uncommitted changes also in the root (`documentations/`), `user-manual/` and `qa/` repos. Remaining: the natural renewals in the observation table (2026-10-20/21 UTC).
