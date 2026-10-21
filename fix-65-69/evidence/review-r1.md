# Review r1: today's changes for #65 to #70 (read-only)

Date: 2026-10-20. Reviewer: one read-only agent. Scope: the #65 to #70 code listed in
`qa/issues-65-69-fix-log.md`, read in full together with the code it calls, and checked against
the WooCommerce 11 and WooCommerce Stripe 10.9.0 source. Yesterday's #56 to #64 code was read only
where today's code depends on it.

Method: code reading, WooCommerce and Woo Stripe source checks, and read-only probes. The probes
used `wp eval-file` on unsaved in-memory objects and pure arithmetic, made no database writes and
made no Stripe calls. No plugin file was edited.

**Verdict.** There is no HIGH finding: I found no code path that charges twice or loses a charge
on its own. There is one MEDIUM finding (M1), a settlement stamp that lands on paid orders. The
nine LOW findings are mostly narrow races, a one-cent display error, and cleanup or robustness
gaps. Each one below was checked against the actual code.

---

## M1 (MEDIUM): paid renewal orders put on hold by a dispute, a Radar review or an admin are treated as "settling" for 7 or more days

**Where**
- `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/Traits/StripeAsyncFailureTrait.php:237-265`
  (`rememberSettlingPayment()`). The "unpaid" guard at :246 is `$order->is_paid()`.
- `arraysubs/src/Features/AutomaticPayments/Services/Traits/PaymentHooksRenewalsTrait.php:280-298`
  (`orderIsAwaitingSettlement()`) and :215-272 (the subscription-level query includes on-hold
  orders that have `_arraysubs_settlement_until`).
- Woo Stripe: `includes/class-wc-stripe-webhook-handler.php:569-603`. A dispute calls
  `update_status( ON_HOLD )` at :595. A review uses `process_review_opened()` :1147-1189, with
  `ON_HOLD` at :1185.

**Why.** `WC_Order::is_paid()` only accepts the paid statuses `processing` and `completed`. An
order that was paid and is then moved to `on-hold` returns `false`. The probe confirmed this on an
unsaved order: status on-hold, `date_paid` 30 days ago, transaction `ch_…`. It returned
`is_paid=false`, and `orderIsAwaitingSettlement()=true` once stamped.

**Scenario**
1. Renewal order R was paid by card a month ago (completed, transaction id `ch_X`).
2. The customer opens a chargeback. Woo Stripe receives `charge.dispute.created` and sets R to
   on-hold, which fires `woocommerce_order_status_on-hold`.
3. `rememberSettlingPayment()` finds a transaction id, `is_paid()` returns false, R is a renewal
   order on Stripe, and no stamp exists yet. It writes `_arraysubs_settlement_txn=ch_X` and
   `_arraysubs_settlement_until=now+7d` (card window 2 days, minimum 7).
4. For the next 7 days, `arraysubs_is_awaiting_gateway_settlement($sub)` returns true:
   - Admin status changes and billing-term edits through `SubscriptionMutation` are refused with
     409 "Wait for the current gateway payment to settle." (`SubscriptionMutation.php:136-137`,
     used by REST `changeStatus` and `updateSubscription`). The merchant cannot put the disputing
     customer's subscription on hold or cancel it through a status change.
   - Renewal-date changes are refused (`SubscriptionLifecycle.php:66`).
   - The on-hold and cancel grace sweeps skip the subscription
     (`RecurringBilling/Services/Hooks.php:734`, `:784`). This delays dunning if another renewal
     of the subscription is overdue.
   - `GracePolicy::snapshot()` reports `awaiting_gateway_settlement=true`.

The same happens when Radar opens a review on a paid renewal, and when an admin moves a completed
renewal order to on-hold by hand.

**Fix**
- Stamp only orders that were never paid: require `null === $order->get_date_paid()`, and accept
  the hook's third argument and require `$status_transition['from']` to be `pending` or `failed`.
- Make `orderIsAwaitingSettlement()` ignore orders with a paid date.
- Optionally delete the stamp on `woocommerce_payment_complete` and on failure.

---

## L1 (LOW): a Pay-page payment can still race an automatic attempt into a double charge, and #65 does not re-check the new stamp before charging

**Where**
- `StripeRenewalPaymentTrait.php:98-182`: stored-intent resolution, then reconcile, then charge.
  `beginAttempt()` and `POST payment_intents` follow at :285-297.
- `StripeIntentResolutionTrait.php:1018-1028`: the release path writes `_stripe_intent_id` back
  to the attempt's own PI.
- Woo Stripe reuses the stored intent while it is `requires_payment_method`
  (`class-wc-stripe-upe-payment-gateway.php:3262-3271` and `:4137-4172`). Its `process_payment`
  takes no order lock.

**Scenario**
1. Renewal R declined: PI1 is `requires_payment_method` with a decline error. Retry 1 starts at
   10:00:00.
2. `resolveExistingIntent(PI1)` finds a terminal intent, the order-scoped search is clean, and
   `clearTerminalIntentForReplacementWhileLocked()` deletes `_stripe_intent_id` at about +1.5 s.
3. At 10:00:01 the customer submits a new card on the Pay page. `rememberPayPageSubmission()`
   stamps `_arraysubs_customer_pay_started_at`, but nothing reads it again. Woo Stripe then either
   reuses PI1, if it read the pointer before step 2, or creates a new PI. That payment succeeds.
4. The run creates PI3 off-session on the card on file at about +2.5 s, and it succeeds too.

The same window exists in the release path, which re-points `_stripe_intent_id` at PI1 just
before resolving it. `deferWhileCustomerIsPaying()` only protects against activity recorded
**before** the check, and only when an open intent exists. A recent `CUSTOMER_PAY_STARTED_META`
with a terminal PI1 does not defer anything.

This race existed before today, and the window is a few seconds.

**Fix**
- Inside the gateway-meta lock, just before `beginAttempt()`, re-read
  `CUSTOMER_PAY_STARTED_META` and `_stripe_intent_id`. If the stamp is newer than the run start or
  within the grace window, defer and return pending.
- Cancel PI1 before minting the replacement. `requires_payment_method` can be cancelled, so a
  concurrent confirm on it fails.
- Refuse classic and Store API order-pay submissions while `arraysubs_charge_lock_{order}` is held,
  reusing the #63 refusal plumbing.

---

## L2 (LOW): the "no attempt yet" release branch deletes the intent pointer before the search, so a later run charges without the new customer-attempt protection

**Where**
- `StripeIntentResolutionTrait.php:1031-1039`: `_stripe_intent_id` and the pending pointer are
  deleted, then `reconcileTerminalIntentBeforeReplacement()` runs.
- `StripeRenewalPaymentTrait.php:98-119`: with no stored intent and no open attempt, the fresh
  charge runs without any order-scoped search.

**Scenario**
1. This is the first automatic attempt (no attempt marker). The stored intent is a customer PI_b,
   which is cancelled. The older customer PI_a is still open.
2. The search lists PI_a, but `cancelAbandonedCustomerAttemptIntent(PI_a)` is not confirmed (a
   transient Stripe error). The result is pending with a re-queue in 1 h. Alternatively the search
   itself is inconclusive.
3. The 1 h run finds `_stripe_intent_id` empty and `requireReconcileBeforeRetry()` false. It
   charges the card on file without listing or cancelling PI_a. If the customer still completes
   PI_a, the cycle is paid twice.

Impact is low: PI_a is no longer stored, so Woo Stripe will not reuse it. Only a 3DS challenge
still open in the browser could complete it.

**Fix.** Delete the pointers only after `reconcileTerminalIntentBeforeReplacement()` returns
null. Or run the order-scoped search before a fresh charge whenever the order has a Pay-page
stamp.

---

## L3 (LOW): the reconcile sweep treats "duplicate" as handled but keeps the deadline

**Where**
- `StripeAsyncFailureTrait.php:111-117`: `duplicate` returns before the deadline is deleted at
  :130.
- `StripeAsyncFailureTrait.php:219-223`: `duplicate` counts as handled.
- `PaymentHooksReconciliationTrait.php:377-384`: the sweep returns before its expiry branch at
  :386-414.

**Scenario**
1. PI1 declines. `handlePaymentAttemptFailure()` records it (`RECORDED_FAILURE_META=PI1`, order
   failed, retry 1 queued).
2. Retry 1's pre-replacement search hits a Stripe API error. `buildExistingIntentPendingResult()`
   sets a 2-day `_arraysubs_pending_deadline` and queues nothing. This dead end predates today.
3. Every 6-hour sweep from then on reads PI1 (terminal) and runs a conclusive search, which
   returns `duplicate` and then `return`. The deadline is never deleted and the expiry branch never
   runs.

Before today, the sweep dropped the order after 2 days. Now it re-reads Stripe (at least one GET
plus a list) every 6 hours until grace cancels the subscription and its failed order.

The pre-existing dead end also stays: the sweep is the natural place to queue the missed retry,
and it does not.

**Fix.** On `duplicate`, delete the deadline under the lock and return true. If no
`HOOK_PROCESS_RENEWAL` is pending and the retry ladder is not exhausted, queue the next attempt.
Alternatively return false so the normal expiry runs.

---

## L4 (LOW): the 30-minute abandonment rule cancels customer intents that legitimately wait for days

**Where**
- `StripeIntentResolutionTrait.php:929-936` (`isOpenZeroMoneyIntent()`).
- `StripeIntentResolutionTrait.php:954-975` (`deferWhileCustomerIsPaying()`).
- `StripeIntentResolutionTrait.php:1051-1066` (`closeCustomerPayPageAttempts()`).

**Scenario**
1. The customer pays a failed renewal on the Pay page with ACH (a reusable method, so it is
   allowed). Instant verification is not available, so the PI sits in `requires_action` with
   `next_action.type=verify_with_microdeposits` for 1 to 2 business days. The order is not put on
   hold and has no transaction id, so the retry still runs.
2. The next retry, more than 30 minutes later, treats the intent as abandoned and cancels it. It
   then charges the card on file, which already declined once.
3. When the customer later verifies the microdeposits, their payment is gone.

No money is charged twice. The customer's chosen payment is silently discarded.

**Fix.** Treat open intents whose `next_action.type` waits on the customer for days as in flight,
not abandoned. Examples: `verify_with_microdeposits`, and the voucher and bank-transfer display
types if they are ever offered. Defer until their own expiry.

---

## L5 (LOW): a deferral reads as success to manual and customer "Retry payment", and a failed re-queue is ignored

**Where**
- `StripeIntentResolutionTrait.php:966`: the result of `queueRenewalAttempt()` is ignored.
- `RenewalProcessor.php:608-618`: `manualRetry()` maps any `true` from `process()` to "Renewal
  payment successful."

**Scenario.** A customer abandons 3DS on the Pay page and within 30 minutes clicks "Retry payment"
in My Account (`CustomerController.php:493`). The deferral returns pending,
`handleManualPaymentPending()` returns true, and the portal shows "Renewal payment successful."
Nothing was charged.

If the subscription is on-hold (manual retries are allowed there), `queueRenewalAttempt()` refuses.
Nothing is queued, yet the message still says "checked again later".

The success mapping already applied to every pending result, but #65 adds a pending result that a
customer can trigger easily.

**Fix**
- Have `manualRetry()` report `pending` for a pending gateway result.
- In `deferWhileCustomerIsPaying()`, use the return value of `queueRenewalAttempt()` and word the
  message or note when nothing was queued.

---

## L6 (LOW): "Today's charge" per line can be off by one cent, and the shares then do not add up to the cart total

**Where.** `arraysubs/src/functions/cart-charge-helpers.php:58-69` and :123-143. The line amount
is the unrounded `line_total + line_tax`. The remainder is rounded, and each gross is rounded
again.

**Why.** For tax-inclusive prices, WooCommerce stores `line_total` with fractional cents
(`class-wc-cart-totals.php:684,689`; `wc_remove_number_precision()` does not round) and rounds
each line separately into the cart total (`trait-wc-item-totals.php:43-64`).

**Scenario (probe-verified).** Prices include 21% VAT, round-at-subtotal is off, and the cart has
two subscription lines at $10.00 each.
- WooCommerce stores line_total 8.264463 and line_tax 1.74 per line, and the cart total is $20.00.
- The helper computes attributed 20.008926, remainder -0.01, then gross A $9.99 and gross B $10.00.
- The shares add up to $19.99, and line A shows $9.99 while its subtotal shows $10.00.

The probe script is in the session scratchpad (`rounding-sim.php`): it is arithmetic only and uses
the real `arraysubs_allocate_currency_amount_by_weights()`.

**Fix.** Round each component to the price decimals before computing the remainder (fee and
shipping shares are already whole cents). Then the gross is an exact sum and needs no second
rounding. Alternatively, allocate the cart total itself across lines by their unrounded
contributions using largest remainder.

---

## L7 (LOW): the item-data display path runs a full `calculate_totals()` and has no re-entrancy guard

**Where.** `cart-charge-helpers.php:43-48`.

**Effect.** Any request that has not calculated totals yet (for example a classic mini-cart
rendered in a theme header) now recalculates the whole cart from a display filter. That includes
shipping rates, fee hooks and coupon recalculation. Live-rate shipping plugins make remote calls
here. The cost is paid on every such page view for shoppers with a subscription in their cart.

I found no ArraySubs or Pro code that renders item data inside a totals hook, so there is no
recursion today. A third-party plugin that does so would cause unbounded recursion, because
`did_action('woocommerce_after_calculate_totals')` stays 0 until the outer calculation ends.

**Fix.** Add a static in-progress flag. Calculate only when the cart actually has
ArraySubs-weighted fees or shipping, otherwise fall back to the line amounts.

---

## L8 (LOW, depends on configuration): ArraySubs' own Stripe metadata still relies on `home_url()`, and new #67 code adds another strict comparison

**Where**
- Written at `StripeRenewalPaymentTrait.php:263` (`'site_url' => home_url()`).
- Compared at `StripeAsyncFailureTrait.php:58` (new), `StripeRenewalPaymentTrait.php:780`,
  `StripeIntentResolutionTrait.php:1128`, `StripeRequiresActionTrait.php:1830` and
  `Services/ProviderPaymentCompletion.php:252`.

**Why.** `get_home_url()` uses https on an SSL request and falls back to the stored scheme
otherwise (`wp-includes/link-template.php:3471-3477`). On staging the home option is https, so the
value is stable and this does not occur here.

**Scenario.** On a store whose home option is `http://…` behind an HTTPS proxy, a renewal PI
created by the system cron carries `http://…`. A webhook over HTTPS compares it with `https://…`.
The results:
- `recordAsynchronousRenewalFailure()` returns `not_renewal_attempt`, so the failure is not
  recorded and no retry is scheduled.
- The order-scoped search reports "contradictory scope" and stays inconclusive, so the renewal
  stays pending.

This is the #70 class of bug in Stripe's own metadata.

**Fix.** Write `arraysubs_get_site_identity_url()` and compare with `arraysubs_is_this_site_url()`
everywhere, as #70 did for Paddle and the customer-intent matcher.

---

## L9 (LOW, robustness): Paddle keys and fingerprints now hash the raw `siteurl` option

**Where.** `functions/site-url-helpers.php:20-23`, used for example at
`PaddleCheckoutTrait.php:1204-1207` and `PaddlePlanSwitchPricingTrait.php:1382-1386`, and in the
contract and fingerprint hashes listed in the #70 diff.

**Effect.** Hashes are consistent across cron and web requests, which is the #70 fix. But they now
change when the option's scheme changes. A store migrating `siteurl` from http to https, or one
whose option is http while it is served over HTTPS (where the old in-request keys used https),
invalidates every stored `checkout_key`. Paddle events for existing subscriptions are then
rejected (`PaddleWebhooksTrait.php:719-734`). Development rules say no backward compatibility is
needed, so this only matters as a forward-looking point.

**Fix.** Hash a scheme-less normalized identity (the same normalization
`arraysubs_is_this_site_url()` uses) instead of the raw option.

---

## Wording nits (no fix required)
- `CartSummary::todayCharge()` (`CartSummary.php:172-198`): on a trial line with a signup fee and
  shipping, the shipping share is shown as the "first payment". Example: "$4.99 + $5.00 signup =
  $9.99" where the $4.99 is shipping. Before today it read "(signup fee only)".
- When the cart shows prices excluding tax, the "Signup fee" row shows the fee before tax
  (`CartSummary.php:120-129`) while "Today's charge" shows it with tax. One screen shows two
  different signup amounts.

---

## Checked and found correct
- **No double counting.**
  - Sync decline plus webhook: `RECORDED_FAILURE_META` is written in
    `handlePaymentAttemptFailure()` while the run holds the lifecycle lock. A webhook arriving
    during the run gets `retry_later`, and afterwards `duplicate` (seen live in T65 and T67).
  - Two endpoints: one event claim plus `RECORDED_FAILURE_META`.
  - The retry worker and the sweep: the same lock and meta check.
  - Mollie treats `duplicate` as handled.
- **Failures are attributed correctly.**
  - The customer's own Woo Stripe intents have no ArraySubs metadata and end at
    `not_renewal_attempt`, which keeps the old log-and-email behaviour only.
  - A customer reusing the attempt's PI after a recorded decline gives `duplicate`.
  - Late events from older attempts give `superseded`.
  - The new order guards (current invoice, pending/on-hold/failed status, not externally billed)
    hold.
- **Woo Stripe assumptions verified in the 10.9.0 source.**
  - Intent reuse rules (`class-wc-stripe-upe-payment-gateway.php:4137-4172`).
  - Metadata `site_url = esc_url(get_site_url())` and `order_id` = order number
    (`abstract-wc-stripe-payment-gateway.php:2880-2919`).
  - `payment_intent.payment_failed` is ignored for on-hold orders (allowed statuses
    pending/failed, `class-wc-stripe-webhook-handler.php:1338-1347`).
  - `charge.failed` moves on-hold to failed (`class-wc-stripe-webhook-handler.php:852-883`).
  - `payment_intent.processing` sets the transaction id and on-hold
    (`class-wc-stripe-webhook-handler.php:1367-1374`).
  - `woocommerce_store_api_checkout_order_processed` and `woocommerce_before_pay_action` both
    exist on the order-pay paths.
- **Idempotency key and attempt sequence after releasing a customer intent.** Releasing back to
  the attempt PI ends in `clearTerminalIntentForReplacementWhileLocked()`, which bumps the
  sequence. The no-attempt branch is reached only with no marker or a closed `no_charge` attempt
  (already bumped). An open attempt goes through reconcile-before-retry. The release also moves
  the pending-transaction pointer back.
- **Deferral math.** The latest of the Pay-page stamp, the first sighting and the PI's creation
  time, plus 30 minutes. It re-queues at exactly the abandonment moment (at least 60 s), and never
  defers forever without new customer activity.
- **PHP types.** `array_map([$this, 'settlementWindowDays'], …)` on a private trait method works
  (tested on PHP 8.3). No null reaches a typed parameter in the new code. `php -l` is clean on
  every changed file.
- **Settlement windows.** `_arraysubs_settlement_until` is finite. The processing extension is
  capped at 30 days from the attempt. Failed orders are excluded by the on-hold/pending check.
  After an asynchronous failure, the failed charge is removed from the order's transaction id, so
  `renewalOrderIsSettled()` and `hasSettlingRenewalPayment()` do not block the retry.
- **Cart split.**
  - Fees without weights, and fees whose weights are all removed lines or zero, go into the
    remainder.
  - Shipping is split by package quantity.
  - The custom fee properties survive `fees_api()->add_fee()`.
  - The cache key includes the cart hash and the calculation count.
  - The migration credit now sits in the line price, so dropping "Due today" is safe.
- **Escaping.** Amounts are `wc_price()` passed through `wp_kses_post`, product names, labels and
  dates are `esc_html`, and tax labels are WooCommerce's own. The checkout table wraps every row
  value. No XSS path found.
- **#68.** `payment_method_update=false` leaves no dead link. `restoreChargeableStatus()` is
  correct. Revoke without an API key now returns a defined array, and rebind's `$failure` is
  defined before use.
- **#70.** The matcher normalizes scheme, case and trailing slash. Every Paddle comparison was
  switched, and writers and hash inputs are consistent with each other.
- **File sizes.** No touched file exceeds 3000 lines; the largest is `MollieDelegate.php` at
  2885, then `StripeSubscriptionCompatibility.php` at 2847. The deleted
  `checkout-subscription-summary.php` is not referenced anywhere.
