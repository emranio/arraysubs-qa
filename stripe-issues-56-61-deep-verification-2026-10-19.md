# Stripe issues #56–#61: deep re-verification (2026-10-19)

**Short answer:** all six issues are real. None was refuted. Every one needed corrections, and #58 is much narrower than first written (lowered from high to medium). The re-verification also found three new defects, filed as #62, #63 and #64.

| | |
|---|---|
| Site | https://mirror-help.arrayhash.com (staging), WooCommerce 11.0.1, official WooCommerce Stripe Gateway 10.9.0 in test mode |
| Revisions | ArraySubs core `4791832`, ArraySubs Pro `edaa401` (both trees clean before and after) |
| Issue files | `qa/issues/tasks/056-…` to `064-…` (verdict notes appended to #56–#61) |
| Evidence | `qa/stripe-dev-support-2026-10-18/` — `evidence/dv56-*` … `dv61-*`, `evidence/dv2-*`, `snapshots/`, `screenshots/dv*`, `capture/lifecycle-events.jsonl` |
| Finished | 2026-10-19 14:01 UTC |

## How it was checked

**Round 1 (20 agents).** For each issue, two independent checks ran at the same time:

- A code reviewer tried to *refute* the issue from source: ArraySubs core, Pro and the official Stripe plugin.
- One or more live re-tests ran on fresh fixtures. Each used a different card or path from the original, plus a deliberate counterexample where one existed:
  - #58: a classic checkout.
  - #59: a pre-sweep payment.

An adjudicator then weighed both and could run extra read-only checks. Finally a completeness critic reviewed all verdicts for gaps, contradictions and cross-issue effects.

**Round 2 (6 agents).** The critic's gaps that could be tested the same day were investigated: three code-plus-live investigations, each followed by an adversarial skeptic. All three turned into new issues.

**Rules followed:**

- Every new customer, subscription, order and action was registered in `fixtures.json` (phases `deep-verify` and `deep-verify-2`).
- Only fixture actions were run; no sweep was triggered by hand.
- No plugin code was edited.
- Settings were changed only in two documented windows, each snapshotted, restored and diffed (see Settings).
- Card data was limited to Stripe test cards.
- Order keys and secrets were redacted from evidence.

## Verdicts

| Issue | Verdict | Severity (filed → now) | What is true, in one line |
|---|---|---|---|
| [#56](issues/tasks/056-stripe-portal-card-updates-are-always-refused-for.md) Portal card change refused | Confirmed with corrections | high → **high** | Any card that differs from the stored one is refused, through the Stripe portal **and** WooCommerce My Account. Renewals keep charging the old card. |
| [#57](issues/tasks/057-stripe-declines-are-always-classified-as-hard-card.md) Stripe declines classed as hard | Confirmed with corrections | high → **high** | Every issuer decline that carries a `decline_code` is stored as hard `card_declined`, so under default settings it is never retried. `processing_error` is still retried. |
| [#58](issues/tasks/058-authentication-required-stripe-renewal-cannot-be.md) Auth renewal cannot be completed | Confirmed with corrections, **narrowed** | high → **medium** | The dead end happens only when the bound card has no visible WooCommerce token. Normal 3DS checkouts keep the token, and verification then completes with one charge. |
| [#59](issues/tasks/059-verification-sweep-turns-declined-stripe-renewals.md) Sweep locks Pay Now | Confirmed with corrections | high → **high** | The 10-minute sweep marks ordinary declines as authentication cases, and nothing ever clears the mark, so both Pay links stay locked to the declining card. |
| [#60](issues/tasks/060-developer-data-keeps-a-past-retry-time-after-the.md) Stale next retry time | Confirmed with corrections | low → **low** | The stale "next retry" survives exhaustion, the stop exits and overdue cancellation. It is also visible on the Pro Renewal Failures page. |
| [#61](issues/tasks/061-payment-retry-ladder-has-no-admin-setting-although.md) No retry settings UI | Confirmed with corrections | medium → **medium** | No admin control exists. The values can be changed through REST **or** a 1-Minute Setup export → edit JSON → import round trip. |
| [#62](issues/tasks/062-paying-a-failed-stripe-renewal-with-an-unsaved.md) **new** Unsaved-card rebind | Confirmed (live + skeptic) | **high** | Paying a declined renewal with an unsaved card silently rebinds the subscription to a one-time card. Every later renewal fails closed, and nothing in the UI can repair it. |
| [#63](issues/tasks/063-store-api-order-payment-link-bypasses-the-renewal.md) **new** Store API bypass | Confirmed (live + skeptic) | **medium** | "Pay securely with Link" pays a locked renewal through the Store API without any ArraySubs check. It rebinds to the Link card and leaves stale lock markers. |
| [#64](issues/tasks/064-same-payment-method-stripe-events-wipe-renewal.md) **new** Reset on same-card events | Confirmed (live + skeptic) | **medium** | Any Stripe event for the already-bound card wipes the open failure and retry state. Retry buttons and the Pro failure row disappear, and the queued retry restarts at attempt 0. |

Where my original write-ups were wrong:

- **#58:** "after a 3DS checkout" was too general.
- **#57:** "always" and "any card decline" overstated it.
- **#59:** the cause missed the cleanup half.
- **#61:** "only REST" missed the Easy Setup import.
- **#56:** the reason checkout binding works was wrong.
- **#60:** understated how widely the stale value appears.

## #56 — Card changes are never bound

**Confirmed live again** with a different card brand:

- **Fixture:** customer 584 `sqa-dv56-portal`, subscription 68677, `cus_VTAa4eDbx66HA2`.
- **Refused:** the Mastercard 4444 added as Default in the Stripe billing portal, `pm_1USEHN…`, was refused twice (10:59:00–01Z, `renewal_payment_method_binding_changed`).
- **Also refused:** the same card added through **WooCommerce My Account → Add payment method + Make default**, `pm_1USERy…`.
- **Not a fix:** admin **Resync from Gateway** reported "Local state already matches gateway".
- **Old card charged:** the early renewal 68726 charged the old Visa 4242 (`pi_3USEPy…`). The same happened to the original fixture: 68418 / order 68443 / `pi_3USCkH…`.

**Root cause (corrected).** `handlePaymentMethodUpdated` uses the renewal-path verifier as its binding gate.

- The verifier is called with the new card at `StripeWebhookEventsTrait.php:455-458`.
- `verifyAutomaticRenewalPaymentMethod` re-reads the stored `_gateway_payment_method_id` and refuses anything that differs (`StripePaymentMethodsTrait.php:383-392`, repeated at 409-419).
- Nothing writes the new card first. The only write is after verification, at line 499.
- Checkout binding works for a different reason than the issue said: `StripeOrderContextBridge::capture` writes the order's card *without* verification. Checkout webhooks that arrive before that capture are refused too (68512, 68525, 68599, 68665).

**Contributing defects:**

- **`customer.updated` is a no-op by construction.** A Customer payload has no `customer` field, so the event resolves to subscription 0. `invoice_settings.default_payment_method` is never read (`StripeWebhooksTrait.php:124-146`).
- **The My Account rebind listener never runs.** It hangs on `woocommerce_payment_token_added`, which nothing fires; WooCommerce fires `woocommerce_new_payment_token` (`StripeDelegate.php:183`).
- **The official-route retry always fails for portal cards.** It requires an order-owned SetupIntent, which portal SetupIntents never have, so it exhausts every time.

**Scope and impact (corrected):**

- Every existing Stripe subscription whose customer replaces the card, through either path, is affected.
- With several Stripe subscriptions, the customer-wide fan-out refuses each one.
- The customer is told nothing. The merchant gets only a WooCommerce log warning.
- Each refused REST delivery returns HTTP 500 and Stripe redelivers it; the refusals were seen again at 11:59–12:11Z.
- Same-card updates pass, but they trigger #64.
- **Fix caution:** the equality check is currently the only thing stopping a new card from being bound to *all* of a customer's subscriptions. Any fix must scope the rebind, for example to the portal default or an explicit verified return-path rebind. It must not just drop the check.

**Open:** Stripe disabling the endpoint over repeated 500s was not observed. Whether a portal default change should apply to one subscription or all of them is a product decision.

## #57 — Stripe declines are always hard

**Confirmed live again:**

- **Fixture:** customer 585 `sqa-dv57-decline`, subscription 68665, renewal 68694, `pi_3USEJEJG5OzSNVs21r9uU7nr`.
- **Stripe returned:** `code=card_declined`, `decline_code=generic_decline`, `advice_code=try_again_later`. Stripe itself advises a retry.
- **ArraySubs stored** category `card_declined` and scheduled no retry. Its note says the failure "will not resolve on its own".
- **Classifier probe (read-only):**

| Input | Result |
|---|---|
| `generic_decline` | soft |
| `insufficient_funds` | soft |
| `card_declined` + "Your card has insufficient funds." | hard |
| `processing_error` | soft (retried) |

**Root cause (corrected):**

- `StripeRenewalPaymentTrait.php:325` passes only Stripe's top-level `error.code`. `decline_code` and `advice_code` travel only in the raw payload, which `RenewalProcessor.php:504-510` never reads.
- The classifier returns the direct code mapping (`gateway-helpers.php:522`, returning at 546-548) *before* its message fallback, so Stripe's own wording cannot rescue the category.
- `card_declined` is a hard category (line 374).
- The webhook failure path reads only `code` too (`StripeWebhookEventsTrait.php:86`).
- A fix also needs mappings for unmapped Stripe codes: `lost_card`, `stolen_card`, `card_velocity_exceeded`, `pickup_card`, `fraudulent`. Ideally it should follow `advice_code`.

**Scope and impact (corrected):**

- Under the shipped defaults, Stripe's most common recoverable declines get zero automatic retries. Stripe does not retry these off-session PaymentIntents itself, so this means involuntary churn.
- **Not affected:**
  - `processing_error`, which is retried;
  - `expired_card`, `incorrect_cvc` and `incorrect_number`, which are hard by design;
  - `authentication_required`, which has its own path.
- The customer email shows only the category ("declined by the issuer"); the notes keep the raw Stripe message.
- The Pro Renewal Failures audit shows the wrong category.
- The architecture doc promises decline_code-based classification.
- With `stop_on_hard_decline=false`, the ladder does run on these declines. This was proven live by the #60 test on 68881.

**Caution for re-checks:** the live failure meta on 68439 and 68665 has since been wiped by #64, which is not a fix. Rely on `evidence/dv57-state-*.json` and `snapshots/sub-68439-s03-after-decline.json`.

## #58 — Authentication-required renewal dead end (narrowed)

**What is true:**

- For subscription 68466 / renewal 68494 (customer 580), no WooCommerce token exists.
- The verification link and the plain Pay link show an empty Payment Element. Every submission is refused with "Use the saved Stripe payment method shown for this renewal verification."
- The renewal PaymentIntent stays unpaid, and no second PaymentIntent is created.
- The same dead end reproduced on a fresh fault-injected fixture: customer 590, subscription 68781, renewal 68846. Only the browser's post-3DS call was aborted there.

**Counterexamples (the reason it is narrowed):** normal 3DS signups kept their token, and the verification link then completed the *same* renewal PaymentIntent with exactly one charge:

- **Block checkout:** customer 586, subscription 68709, renewal 68736.
- **Classic checkout:** customer 587, subscription 68753, renewal 68843.

**Root cause (two layers):**

1. **WooCommerce Stripe 10.9.0.** It saves the card token *before* 3DS. For a user's first token, WooCommerce core's `set_users_default` triggers WC Stripe's token sync. That sync deletes the token because the card is not yet attached, then caches an empty list for 24 hours. The browser's post-3DS `wc_stripe_update_order_status` call normally repairs this. When that call never lands, the order is finished only by WC Stripe's deferred webhook, which neither creates a token nor clears the cache. For 68465 and 68780, `_stripe_upe_waiting_for_redirect=1` is still set.
2. **ArraySubs.** `validateRenewalActionPaymentMethod` (`StripeSubscriptionCompatibility.php:762-784`) requires a posted WooCommerce token equal to the bound card. Yet confirmation itself needs only the bound card ID (lines 810, 841, `confirmBoundRenewalActionIntent`). No token-less path exists, and the page still offers options that are always refused.

**Scope:** the dead end needs all four of these:

- the shopper's first saved token;
- 3DS at signup;
- the post-3DS browser call never reaching the site (a closed tab, an app switch, or possibly a WC Stripe lock race — the logs cannot tell which);
- an authentication-required renewal within the 24-hour stale-cache window.

By code it should heal once that cache expires; this was not observed.

**Code-only risk that would raise it to high:** on stores with WC Stripe "saved cards" disabled, the verification page never renders token radios. ArraySubs forces saved cards only on checkout POSTs, so every authentication-required renewal would dead-end permanently. Not tested, because the change would touch credential-bearing Stripe settings.

## #59 — Verification sweep locks Pay Now

**Confirmed live again,** with a control:

- **Locked order:** fresh order 68822 (subscription 68770) had an ordinary `card_declined`/`generic_decline`.
  - **Before the sweep:** no action meta, and its Pay page offered all five gateways.
  - **Marked:** sweep 101129 (11:37:04Z) wrote `_arraysubs_payment_action_intent_id`, `_arraysubs_payment_action_url` and `_arraysubs_payment_action_required_at`.
  - **Delivery:** action 101166 found it was not an authentication case and marked the outbox `superseded`, but **left the meta**.
  - **After:** the same link offered only Stripe with the declining 0341 card, and a new 4242 card was refused.
  - **Lock persists:** the next sweep changed nothing.
- **Control:** order 68819 declined just after a sweep and was paid with a new card before the next one, on the same PaymentIntent, with one charge.

**Root cause (corrected):**

- The sweep (`PaymentHooksScheduledJobsTrait.php:572-618` → `StripeRequiresActionTrait::recoverRequiresActionNotice` → `queueRenewalRequiresActionNotice`) writes the authentication context from local data only. It never fetches the PaymentIntent; the webhook path does.
- Delivery (`StripeRequiresActionTrait.php:280-296`) detects the mistake but never calls `clearTerminalFailureActionContext`.
- `isRenewalActionRoute` (`StripeSubscriptionCompatibility.php:650-663`) treats any leftover meta as the verification route, so the lock is permanent.

**Scope (corrected):**

- **Which orders:** only renewals the Stripe delegate tried to charge automatically (`_arraysubs_charge_attempt_gateway=stripe`) that hold a `pi_` id.
- **Soft-retry windows too:** it also hits soft-retry windows (68553 was marked while retry 100974 was pending).
- **Both Pay links are locked:** the email Pay Now and the My Account Pay link.
- **Saved card refused too:** by code, the saved declining card is refused as well.
- **Marked so far:** 68473, 68550, 68553, 68614, 68694, 68822 (later paid via #63) and 68903.
- **Re-marking:** an admin Retry clears it, but the next sweep marks the new declined attempt again.

**Fix together with #62 and #63:** unlocking the page sends recovered customers into the #62 rebind trap. The Link button already bypasses the lock (#63).

## #60 — Stale next retry time

**Confirmed live again** on fresh subscription 68881: retries ran at their real +24 h schedule, and after exhaustion the developer `recovery.next_retry_at` still pointed at the retry that had already run.

**Also confirmed live:**

- **After overdue cancellation:** 68525 still reports 2026-10-20 09:43:29 after its overdue cancellation at 13:19:04Z.
- **Pro Renewal Failures page:** shows "Next: Oct 20, 2026, 5:52 PM" for a retry that will never run.

**Root cause:**

- `RenewalProcessor::scheduleNextRetry()` is the only writer (lines 925-927). Its three stop exits — retries disabled, limit reached, hard decline — never clear the meta.
- Overdue cancellation and the unschedule paths do not clear it either.
- The readers report the raw meta: `GracePolicy.php:101`, Pro `RenewalFailureController.php:344-345`, core `SubscriptionController.php:599`.

**Scope:**

- **Read-model and display only:** there is no billing effect, apart from a respread skip for future-dated values (`RenewalRespreader.php:197-207`).
- **Inverse case in #64:** the meta is deleted while a retry is still queued.
- **Fix:** derive `next_retry_at` from a pending `arraysubs_process_renewal` action.

## #61 — No retry settings UI

**Confirmed:**

- 49 admin screens were swept live, plus every registered gateway's form fields. None has a control for `payment_retry.enabled`, `interval_hours` or `stop_on_hard_decline`.
- The keys appear in no React source or build bundle of either plugin.
- The note "Automatic retries are disabled in the gateway settings" (`RenewalProcessor.php:852`) points to settings that do not exist.

**Corrections:**

- **There is a second route besides REST:** ArraySubs → 1-Minute Setup (`#/easy-setup`) Export → hand-edit the JSON → Import "Subscription Settings". It is not a form, never shows the values, and replaces whole settings groups.
- **The note also fires for PayPal and Paddle,** whose retries are hard-coded off.

**History:**

- A settings page existed and was deleted in March 2026.
- The note and the architecture doc were written for Pro's old Stripe gateway, deleted in April 2026.
- The current store-level ladder was added in August–September 2026 without a control.

**Stale documentation:**

- `documentations/architecture/payment-retry-system.md`
- `documentations/user-guides/subscription-lifecycle-and-plan-switching.md:63`
- `user-manual/markdowns/developers/integration-recipes.md:221`
- the `gateway-helpers.php:236-243` docblock

`user-manual/.../payment-recovery.md` is accurate.

**Severity:** medium while #57 is open. Turning `stop_on_hard_decline` off is then the merchant's only lever, and it is reachable only through JSON import or REST.

## New defects

### #62 — Paying a failed renewal with an unsaved card breaks automatic billing (high)

**Live on subscription 68788** (customer 588):

1. **Bad binding:** after its declined renewal 68819 was paid with a new card on the ordinary Pay page, `StripeOrderContextBridge::capture` silently bound the subscription to `pm_1USEmE…`. Stripe shows that card with `customer=null`, and it was not set up for off-session use.
2. **Fail-closed renewal:** the next renewal (68965, action 101226) failed closed with `renewal_payment_method_binding_invalid` before any charge attempt. There was no PaymentIntent and no attempt marker.
3. **Futile retries:** the error is unmapped and classifies as `unknown`, so a pointless retry 1 of 3 was scheduled and the "update your payment method" note was skipped.
4. **No repair:** paying 68965 with another new card did not rebind (the marker-less order is treated as intentionally manual), and that card was unsaved too.
5. **Misleading UI:** My Account still says "Renewals On" and "Visa ending in 4242".

**Cause:**

- The bridge (`StripeOrderContextBridge.php:97-114, 127, 159, 174-177`) persists the order's card without checking attachment or reusability.
- Renewal order-pay never saves cards: `orderRequiresReusableMethod` is false for renewals, and Link hides the save box.

**Recovery:** none through any UI. The portal and My Account are refused (#56), and Resync changes nothing. It takes a meta edit or detaching to manual billing.

**Exposure:** today it is limited to the window before the sweep locks the order. Fixing #59 alone would make this the normal outcome.

### #63 — Link pays locked renewals through the Store API (medium; rebinding part is #62)

**Live on locked order 68822** (customer 589): "Pay securely with Link" paid it through `POST /wp-json/wc/store/v1/checkout/68822`.

- **No ArraySubs check:** there was no refusal and no log.
- **One collection:** a new PaymentIntent `pi_3USG0Z…`; the old declined one received nothing.
- **Rebound:** subscription 68770 was rebound to the Link card `pm_1USG0T…` (`customer=null`), which the delegate's own check now rejects.
- **Stale markers:** the lock markers stayed.
- **Overwritten data:** WooCommerce replaced the order's and customer's billing email with the Link email and cleared both phone numbers.

**Cause:** the gate listens only on `woocommerce_before_pay_action`, which the Store API route never fires, and no Store API validator checks the lock.

**Not tested live:** Amazon Pay and a direct owner POST bypass the same way by code. Amazon Pay's sandbox needs buyer credentials.

### #64 — Same-card Stripe events wipe failure and retry state (medium)

**Trigger:** a billing-details edit on the bound card (`payment_method.updated`).

**Live results:**

- **68881 (13:13:47Z):** every failure and retry key was deleted, the developer recovery block went to 0/null, both Retry Payment buttons disappeared, and the subscription dropped out of Pro Renewal Failures. The failed order stayed unpaid.
- **68996 (13:25:17Z):** the same wipe, and its queued retry 101277 stayed pending. It later ran as attempt 0 and skipped the `arraysubs_pre_retry_charge_verification` filter. No double charge: WC Stripe's own stored-intent checks still ran.

**Earlier natural wipes:** on 68439, 68599 and 68665, the wipe came from Stripe redelivering checkout events that #56 had refused.

**Cause:** `StripeWebhookEventsTrait.php:508-513` resets retry state after every successful bind. Because of #56, every bind that reaches it is a same-card bind.

## How the defects combine

Here is what happens today after an ordinary Stripe renewal decline:

1. **No automatic retry:** none is scheduled (#57).
2. **About 5–10 minutes later:** the sweep locks the email Pay Now and My Account Pay links to the declining card (#59).
3. **Changing the card does not help:** through the Stripe portal or My Account it is refused (#56).
4. **The ways that do pay leave billing broken:** paying with a new card before the sweep (#62), or with Link afterwards (#63), settles the current invoice but leaves the subscription bound to an unusable card. Every later renewal then fails closed.
5. **A same-card Stripe event can erase the open failure,** which hides the last remaining tool, Retry Payment (#64).

The overdue sweep still moves the subscription to on-hold and cancellation correctly. This was confirmed live on 68525: on-hold 13:18:07Z, cancelled 13:19:04Z, `grace_ended` carrying order 68553 with a deterministic event ID.

**Fix order that avoids new traps:**

- Fix #59 together with #62 and #63: save or attach the card on renewal order-pay, or bind only an attached reusable card, and gate the Store API route.
- Fix #57 together with #59. More retry windows otherwise mean more locked pages.
- Keep #56's rebind scoped.
- Stop the #64 reset when nothing changed.
- Fix #60 by deriving the next retry from the pending action.

## Who owns what

- **WooCommerce Stripe 10.9.0:** the #58 trigger (token saved before attachment and deleted by the sync, a 24-hour empty cache, the deferred webhook creating no token), the roughly 4-minute 3DS completion, and the portal card not appearing in My Account.
- **WooCommerce core:** overwrites the order's payment method title with "Payment methods" after a refused Pay submission.
- **ArraySubs:** #56, #57, #59–#64, and the token-only gate in #58.

## Smaller findings (not filed)

These are code-derived or minor. Re-check before acting on them.

- `StripeApiTrait.php:50-52` returns the exception message. For a `WC_Stripe_Exception`, that is a dump of the whole HTTP response, which could become a customer-visible failure reason during a Stripe outage. Code only.
- `SubscriptionController.php:715` marks every $0 order as fully refunded, so trial order 68880 shows struck through.
- The note "Creating Stripe off-session renewal PaymentIntent" is written before verification, even when no PaymentIntent is created (#62).
- "[Send Stripe Renewal Verification] ✓ Success" is written when nothing was sent (#59).
- `endTrial()` sends "your trial has converted to a paid subscription" before any payment is taken.

## Observation points still pending (2026-10-20, UTC)

| Time | What to watch | Why |
|---|---|---|
| 09:22:23 | Open the 68494 verification link as customer 580 (this creates a token, so it is a mutating check) | #58 self-heal after WC Stripe's cache expires |
| 11:13:36 | Action 101077 on 68677 | #56: should charge the old Visa 4242 again |
| 11:25:19 | Reload the 68846 verification page as customer 590 | #58 self-heal on the variant |
| 11:45:11 | Action 101231 on 68770 | #63/#62: expected `binding_invalid`; check whether the stale markers lock the new invoice's Pay page |
| 13:47:24, 14:36:37 | Actions 101164 (68753) and 101111 (68709) | 3DS subscriptions: authentication emails and verification with a token |
| 14:36:32 | Action 101240 on 68788 | #62: expected `binding_invalid` again |

## Fixtures created during deep verification

All are registered in `qa/stripe-dev-support-2026-10-18/fixtures.json`, for phase 7 cleanup.

| Customer | Subscription | Orders | Used for |
|---|---|---|---|
| 584 `sqa-dv56-portal` | 68677 | 68676, 68726 | #56 |
| 585 `sqa-dv57-decline` | 68665 | 68664, 68694 | #57 |
| 586 `sqa-dv58-block` | 68709 | 68708, 68736 | #58 Block counterexample |
| 587 `sqa-dv58-classic` | 68753 | 68752, 68843 | #58 classic counterexample |
| 588 `sqa-dv59-before` | 68788 | 68786, 68819, 68965 | #59 control, #62 |
| 589 `sqa-dv59-after` | 68770 | 68769, 68822 | #59, #63 |
| 590 `sqa-dv58-block-nt` | 68781 | 68780, 68846 | #58 fault-injected variant |
| 591 `sqa-dv60-ladder` | 68881 | 68880, 68903 | #60, #64 |
| 592 `sqa-dv2-reset` | 68996 | 68995, 69022 | #64 with a queued retry |

A Stripe Link sandbox account was created for #63, under `sqa-dv2-link-1792414105@example.com`.

## Settings

Two short windows changed `payment_retry.stop_on_hard_decline` to `false`, each through the prescribed scripts:

| Window (UTC) | Test | Before diff | After diff |
|---|---|---|---|
| 11:45:59–11:53:16 | #60 retry ladder | `all_identical: true` | `all_identical: true` |
| 13:19:41–13:26:08 | #64 queued retry | `all_identical: true` | `all_identical: true` |

**Final check (13:59 UTC):** `snapshots/settings-after-deep-verify.json` vs `settings-original-r2.json` gives `all_identical: true`.

- The Stripe gateway settings and the ArraySubs Stripe extras are hash-identical.
- The active plugins are the original set.
- Both plugin trees are clean at `4791832` / `edaa401`.
- No other test ran inside either window.

(Phase 2's own earlier settings window, 09:41–09:49, is recorded in `handoff-phase2.md`.)

## Environment caveats

- **Browser daemons kept dying:** agent-browser daemons were killed when root's systemd user session ended, or when another project's controller recreated `/run/user/0/agent-browser`. Testers re-logged in and verified that no duplicate orders resulted. The leftover socket workaround directories were removed afterwards.
- **#58 trigger cannot be classified:** with only 5 PHP-FPM workers, WooCommerce Stripe debug logging off and no web access log, it was impossible to tell whether the original #58 run lost its post-3DS call to the browser or to a WC Stripe lock race.
- **Edge cache:** front-end HTML is cached at the Cloudflare edge for guests. All entitlement checks used cache-busted URLs.

## QA tooling changes made during this work

- `scripts/dev-snapshot.php` now redacts order keys (`wc_order_…` and `key=` values). Two snapshot files and one evidence file that held keys were redacted. None had been committed.
- `scripts/restore-settings.php` now also accepts the bare word `apply`, because WP-CLI rejects an unknown `--apply` flag.
