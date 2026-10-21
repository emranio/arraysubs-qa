I found 2 real defects in the patches, 1 policy conflict and 6 smaller issues. No PHP fatals: `php -l` passes on every changed file, and every method and constant the traits call exists. Nothing was edited, and the only database access was read-only queries and `wp eval` probes.

## Findings

### F1 · HIGH (money) · Card change during a settling renewal payment charges the cycle twice
- **Where:** `StripePaymentMethodsTrait.php:513-533`, `queueRenewalAttemptWithNewCard()`.
- **What happens:**
  1. Renewal order R is settling. Either ArraySubs' own off-session SEPA/ACH/BACS charge is `processing`, or the customer paid R on the Pay page with a bank debit, or Radar holds a card payment for review.
  2. Woo Stripe gives R a transaction id and moves it to on-hold (`class-wc-stripe-webhook-handler.php:1367-1373`, which finds R through `metadata.order_id`; also `abstract-wc-stripe-payment-gateway.php:655-665`). R is not paid, so `_next_payment_date` is still in the past and no `arraysubs_process_renewal` action is queued.
  3. During the settlement window (days for SEPA) the customer makes another card the default. Subscriptions billed to the previous default follow it (portal-opened or detached-card ones too), so the subscription is bound.
  4. The new function's checks all pass: R is unpaid, on-hold is in its status list, the renewal is due, and nothing is scheduled. It queues `process_renewal` for 60 seconds later.
  5. `RenewalProcessor::processUnderLock()` (`RenewalProcessor.php:357-395`) calls `getPendingRenewalOrder()`, which skips R because `renewalOrderIsSettled()` (`:1179-1190`) treats any order with a transaction id as settled.
  6. The subscription is still due, so `createRenewalOrder()` makes a second order R′ for the same cycle and charges it with the new card. The pre-retry check only searches R′'s payment intents, so it does not catch this.
  7. When R settles, the customer has paid twice and the subscription has moved forward two cycles.
- **Why it is new:** before B1, nothing ran `process_renewal` during a settlement window that had no queued retry.
- **Fix:**
  - In the new function, bail out when the pending order has a transaction id, a paid date, `_arraysubs_renewal_settled_at` or `_arraysubs_pending_deadline`, or when `arraysubs_is_awaiting_gateway_settlement()` is true.
  - Queue only when the order's `_stripe_intent_id` has provably failed or is waiting for authentication.
  - Root fix: `processUnderLock()` should never create a new order while a settled-but-unpaid renewal order exists for the current cycle.

### F2 · MEDIUM · Recency guard treats a missing timestamp as "just now" and drops real card changes
- **Where:** `StripeOrderContextBridge.php:406-414` (called at `:157-164`).
- **What happens:** `strtotime('' . ' UTC')` returns the current time, not false (checked in PHP: `strtotime(" UTC") === time()`). For any subscription without `_payment_method_updated_at`, `bindingIsNewerThanPayment()` is true as soon as the capture runs one second or more after `date_paid`.
- **Evidence:** a read-only reflection probe returned `true` for unstamped subscriptions 35313 and 33298 against their old paid orders. 16 of the 44 live Stripe-bound subscriptions have no timestamp; the old bridge never wrote it.
- **Effect:**
  - A customer pays a failed renewal or a plan switch with a new card, which #62 forces to be saved. The capture is skipped silently, as "skipped … manual renewal".
  - Next month's renewal charges the old, failing card again.
  - The gap happens in practice: the M2 run captured at 19:50:28 for a payment dated 19:50:27.
  - The B8 metadata retry, 60 seconds or more later, always skips too.
- **Fix:** read the raw meta value first; when it is empty or does not parse, return false.

### F3 · MEDIUM (policy; whether it is intended is uncertain) · B1 charges on-hold subscriptions and follower subscriptions
- **Where:** `StripePaymentMethodsTrait.php:523`.
- **What happens:** the status list allows `arraysubs-on-hold`, and B1 runs for every bound subscription: the previous-default followers, the detached-card followers (B2) and the portal-opened one.
  - The lifecycle defines on-hold as not billable. It removes the renewal charge when a subscription enters on-hold (`OrderIntegration.php:1016-1019`: "On Hold is payment/admin state").
  - Scenario: an admin holds S2 (dispute or investigation) while its renewal is unpaid. The customer changes the default card for S1. S2 follows the default, is charged within a minute, and reactivates against the admin's hold.
  - The same happens to a subscription whose card the customer deleted on purpose.
- **Fix:** queue only for active or trial subscriptions in grace, or only for the subscription the customer opened the portal from. Never queue for admin holds or for followers.

### F4 · LOW (race) · A renewal charge's capture can undo a card change made in the same seconds
- **Where:** `StripeOrderContextBridge.php:157-164` and `:225-228`.
- **What happens:**
  1. The off-session charge locks the payment details and sends the payment intent with card A (`StripeRenewalPaymentTrait.php:207-285`).
  2. A `customer.updated` webhook waiting on that lock binds card C as soon as it is released (T2).
  3. `payment_complete` follows later (T3 > T2). The capture sees `_stripe_source_id` = A, which differs from C, but the guard compares T2 with `date_paid` T3, so it lets the capture through.
  4. `persist()` has no compare-and-swap, so the subscription goes back to A and the customer's choice is lost.
- **Fix:** when the order's `_stripe_source_id` equals the order's attempt snapshot `_gateway_payment_method_id` (ArraySubs charged the subscription's own card), never rebind. Otherwise, persist with compare-and-swap against the card read when the decision was made.

### F5 · LOW · Display-only refreshes stamp the "card changed" time
- **Where:** `StripePaymentMethodsTrait.php:599`.
- **What happens:** `refreshBoundStripePaymentMethod()` stamps `_payment_method_updated_at` on brand, last-4 or expiry changes. The bridge reads that stamp as "binding newer than payment". A late capture (B8 retry, or a webhook) of a renewal paid with a new card is skipped if any refresh of the old card landed after the payment.
- **Fix:** don't stamp on refresh, or keep a separate `_payment_method_bound_at` for the guard.

### F6 · LOW · Empty next payment date passes the due check
- **Where:** `StripePaymentMethodsTrait.php:515`.
- **What happens:** an empty `_next_payment_date` gives `strtotime(' UTC')` = now, so the check passes. `process()` then refuses to charge, but the note "will be charged in about a minute" is written anyway.
- **Same false note:** an externally billed child, a manual-mode subscription, or an order whose payment method is not `stripe` (`renewal_order_binding_invalid`).
- **Fix:** return early when the date is empty.

### F7 · LOW · The abandoned-attempt cleanup can be skipped for good
- **Where:** `StripeRequiresActionTrait.php:1559`; call at `StripeWebhookEventsTrait.php:54`.
- **What happens:**
  1. A payment completed by webhook (bank debit) arrives at ArraySubs' own endpoint before Woo Stripe has marked the order paid. `is_paid()` is false, so the cleanup returns.
  2. The event id is now recorded, so Woo Stripe's later delivery is dropped as a duplicate (claim at `StripeWebhooksTrait.php:295`).
  3. The retry path for failed metadata (`retryOfficialMetadataEvent`) only recaptures and never runs the cleanup.
- **Fix:** also run the cleanup from `woocommerce_payment_complete`, or re-queue it when the order is not yet paid.

### F8 · LOW (problem predates the patches; B1's note misleads)
- **What happens:** after M2 (an abandoned on-session 3DS for a new card on the renewal payment intent, with verification markers suppressed), `isExactRenewalAuthenticationIntent()` needs markers. So `resolveExistingIntent()` classifies the attempt as `invalid` and leaves the order pending forever.
- **Effect:** neither retries nor B1 can charge that order. B1 still logs "will be charged in about a minute".
- **Where:** `StripeIntentResolutionTrait.php:700-734`, and the call at `:215-228`.

### F9 · UNCERTAIN · LOW · M2 guard with legacy `src_` cards
- **Where:** `StripeRequiresActionTrait.php:137-147`.
- **What could happen:** `src_` payments are created with `source`. If Stripe reports a payment method id that differs from the `src_` snapshot, the guard would hide a genuine off-session authentication: no email, no markers, renewal stuck as pending.
- `pm_` and `card_` ids are echoed back unchanged, so they are fine. The live site has no `src_` or `card_` bindings.
- **Fix:** apply the guard only when the snapshot starts with `pm_`.

## Checked, no finding
- **compareAndMutate:** the delete list and context are valid. Keys are allowlisted and never both set and deleted; deleting a missing key succeeds.
- **Compare-and-swap in bind and refresh:** the B3 and B5 checks against the expected card are correct.
- **Default-card change handling:**
  - The previous default is read correctly from `previous_attributes`.
  - The official Woo Stripe route never receives `customer.updated`.
  - Customer re-read, verification, early returns and the portal marker are all correct.
- **B1 guards:**
  - Not due, already paid, auto-renew off, pending cancellation are all handled. Auto-renew and cancellation are rechecked when the charge runs.
  - `isScheduled` also sees an action that is running.
  - Replacing a regular scheduled renewal fixes itself through `requeueEarlyRenewalCharge`.
- **Abandoned-attempt cleanup:** it cannot cancel a payment intent that has moved money. It only cancels `requires_*` intents with zero received and zero capturable, for the exact order and subscription, and Woo Stripe ignores `payment_intent.canceled`.
- **`clearSupersededAuthenticationIntentContext`:** the loosened check still proves the context through `_stripe_intent_id`, the attempt result and the markers, and it runs under the charge lock.
- **M2 guard after a #56 rebind:** `prepareRenewalOrderMeta` refreshes the snapshot on every attempt, so stale payment intents are correctly marked superseded.
- **Recency guard on first captures:** initial checkout and trial SetupIntent orders are not blocked, because the subscription has no card yet.
- **Smaller patches:**
  - Scope narrowing (B7) and `applyOrderScopedStripePaymentMethod` are correct.
  - The throttled recheck (L5) and the metadata check (I7) are correct.
  - The Store API gate runs after `is_authorized`, so it leaks nothing to non-owners.
  - The routes are case-insensitive (L4), `intentFailedForAuthentication` includes `requires_confirmation` (L6), and the bound card is rendered only for the order owner (I9).
  - `renewalLeavesFutureCharge` matches the expiry rule `completed >= length`.

Read-only probe scripts are in `/tmp/claude-0/-home-server-manager-www-arrayhash-mirror-help-arrayhash-com-public-wp-content-plugins/38333750-918d-41d9-9e98-8f405025965a/scratchpad/`.