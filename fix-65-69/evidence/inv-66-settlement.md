# Issue #66: Pay-page bank debits and Radar reviews vs the grace sweeps

Read-only investigation, 2026-10-20, 06:35–07:13 UTC. I changed no code, settings, orders or Stripe objects and made no Stripe API call. I read staging only through WP-CLI:

- the non-secret keys of `woocommerce_stripe_settings`
- the cached Stripe payment method configuration option
- the store's currency and country
- the grace and retry settings
- fixture billing countries
- Action Scheduler listings
- one read-only `GracePolicy::snapshot()`

**Working tree.** `arraysubs` is read as it stands, including the uncommitted #56–#64 work. While I worked, another agent was editing `StripeIntentResolutionTrait.php`, `StripeRenewalPaymentTrait.php`, `StripeRequiresActionTrait.php` and `StripeDelegate.php` (latest edit 07:05 UTC, probably for #65). I re-read line numbers in those files at 07:13 UTC, but they may drift; each reference also names its method so it can be found again. The other `arraysubs` files cited here were last modified before today. WooCommerce Stripe 10.9.0 and WooCommerce 11.0.1 are not being edited.

Path shorthand, all relative to `wp-content/plugins/`:
- `AP` = `arraysubs/src/Features/AutomaticPayments`
- `RB` = `arraysubs/src/Features/RecurringBilling/Services`
- `WCS` = `woocommerce-gateway-stripe/includes`

## Summary

1. **One handler, one meta.** `arraysubs_is_awaiting_gateway_settlement()` has exactly one filter handler: core `AP/Services/Hooks.php:108` → `PaymentHooksRenewalsTrait::filterAwaitingGatewaySettlement()`. It only honours `_arraysubs_pending_deadline`. Pro adds no handler. Mollie, PayPal and Paddle take part only by writing that meta.
2. **The bug.** A payment that Woo Stripe makes on the Pay page never gets that meta. So `GracePolicy::unpaidOrder()` sees the on-hold order as unpaid, the filter says "not settling", and the hourly sweep:
   - holds the subscription at due + 3 days,
   - cancels it at due + 10 days,
   - cancels the order.

   `RenewalProcessor` meanwhile treats the same order as settled, because it has a transaction id. Since 2026-10-19, `hasSettlingRenewalPayment()` blocks any new invoice or charge for the cycle, with no time limit. So the subscription is dunned, but no further charge is attempted.
3. **A late success is lost.** Woo Stripe 10.9.0 ignores a success that arrives after cancellation:
   - `charge.succeeded` acts only on on-hold orders.
   - `payment_intent.succeeded` acts only on pending or failed orders.
   - The 10.9.0 auto-refund for cancelled orders cannot be reached on this path.

   ArraySubs neither reactivates nor refunds. The customer has paid for a cancelled subscription, and nothing tells the merchant.
4. **Two cancel paths disagree.** A manual "cancel now" already refuses to cancel an invoice that carries a transaction id or a Stripe intent (`PendingRenewalOrderCancellation::validateNoSettlement()`). Only the automatic overdue cancel path ignores that.
5. **Recommended fix.**
   - A Stripe-only "producer" writes a new, gateway-neutral order meta. It reacts to Woo Stripe's own signals: `wc_gateway_stripe_process_response`, plus `wc_stripe_webhook_received` for `payment_intent.processing` and `review.opened`.
   - A gateway-neutral "consumer" reads that meta in `orderIsAwaitingSettlement()`.
   - The window depends on the payment type: ACH 7 days, SEPA and other bank debits 14 days, Radar review 7 days, an uncaptured authorization 7 days.
   - When the window ends, grace resumes as normal.
   - Do not reuse `_arraysubs_pending_deadline` for these payments. At expiry, its reconciler fails the order and emails a failure, and it cannot complete payments that Woo Stripe created.
6. **Staging can test the real Woo Stripe path without any settings change.**
   - ACH Direct Debit (`us_bank_account`) is ON in the cached Stripe payment method configuration.
   - The store is USD, and all fixture customers have US billing addresses.
   - Stripe's test account `000000000009` "stays in processing indefinitely".
   - SEPA is OFF.
   - A Radar review needs a review rule on the Stripe account. The test card 4000 0000 0000 9235 is "elevated" risk, and "Radar might queue it for review".

## 1. `arraysubs_is_awaiting_gateway_settlement()`: definition, handlers, callers

### Definition
`arraysubs/src/functions/gateway-helpers.php:697-736`, `function arraysubs_is_awaiting_gateway_settlement(int $subscription_id, $order = null): bool` (717). It returns `(bool) apply_filters('arraysubs_is_awaiting_gateway_settlement', false, $subscription_id, $order)` (730-735).

The docblock at 710-712 is out of date. It still says core "answers false and lets the gateway integration (Pro) speak for itself", but the handler now lives in core. The Stripe and Mollie delegates are constructed in core at `AP/Services/Hooks.php:86-87`.

### Handlers
| Handler | Where | What it checks |
|---|---|---|
| `filterAwaitingGatewaySettlement()` | Registered at `AP/Services/Hooks.php:108` (priority 10, 3 args). Body at `AP/Services/Traits/PaymentHooksRenewalsTrait.php:210-260`; per-order test `orderIsAwaitingSettlement()` at 268-283. | If an earlier filter already said true, it returns true (212-214).<br><br>**With an order:** true only if the order is not paid, not cancelled or refunded, and its `_arraysubs_pending_deadline` (UTC `Y-m-d H:i:s`) is in the future (270-282). **Failed orders count.**<br><br>**Without an order (the sweeps):** `wc_get_orders` fetches the 5 newest renewal orders of the subscription in pending, on-hold or failed that have `_arraysubs_pending_deadline` (227-247), then applies the same per-order test (253-257).<br><br>It checks no transaction id, no Stripe meta and no status history. |

There are no other handlers:
- `arraysubspro` never mentions the filter.
- The Stripe, Mollie, PayPal and Paddle code never hooks it.
- The only other uses are QA regression scripts that add a temporary filter (`qa/developer-support-2026-10-18/subscription-order-api-regression.php:165`, `qa/developer-support-2026-10-18/subscription-alternate-renewal-price-regression.php:55`).

### Callers, and what `true` would change for the Pay-page case
The case: R is the subscription's `_pending_renewal_order_id`. R is on-hold, has a Stripe transaction id and is not paid.

| Caller | file:line | Gets an order? | Today, for R | If it returned true |
|---|---|---|---|---|
| Overdue sweep phase 1, `processActiveToOnHoldBatch()` | `RB/Hooks.php:729-738` (method 688-755) | no | Answer is false. The subscription moves to `arraysubs-on-hold`, `_on_hold_date` is set and `arraysubs_data_put_on_hold` fires (740-750). That writes the auto note and sends the status email; access becomes on-hold access. | Skipped (`continue`). The subscription stays active, with no note, email or `_on_hold_date`. |
| Overdue sweep phase 2, `processOnHoldToCancelBatch()` | `RB/Hooks.php:781-788` (method 764-797) | no | Answer is false. `RenewalProcessor::cancelOverdueSubscription()` runs (790-791). | Skipped (`continue`). No cancellation. |
| `RenewalProcessor::cancelPendingRenewalOrders()`, called only from `cancelOverdueSubscription()` (191) | `RB/RenewalProcessor.php:283-288` (method 214-295) | yes, each unpaid renewal order | R is cancelled: `update_status('cancelled', 'Subscription cancelled due to non-payment.')` (290). | R is left on-hold. The pointer is deleted anyway (294). This only matters if a cancellation still happens, for example in a race with the sweep check. |
| `GracePolicy::snapshot()`. Developer API `recovery` group: `arraysubs/src/Developer/SubscriptionSnapshot.php:45-47`; documented in `documentations/architecture/developer-subscriptions-orders.md:103`. | `RB/GracePolicy.php:96, 109-111` | yes, the `unpaidOrder()` result (null means the subscription query) | `awaiting_gateway_settlement=false`. `can_enter_on_hold` and `can_cancel_overdue` become true once due. | `awaiting_gateway_settlement=true` and both flags false. `phase` is unchanged. |
| `RenewalProcessor::hasSettlingRenewalPayment()`. Callers: `createRenewalInvoice()` 118, `processUnderLock()` 363, 394, 404. | `RB/RenewalProcessor.php:1243-1250` (method 1213-1251) | yes, the pointer order, only if pending or on-hold | Already true through the transaction-id branch (1245). | No change for R. It would matter only for a pointer order with no transaction id. |
| `StripePaymentMethodsTrait::queueRenewalAttemptWithNewCard()`, called from `bindStripePaymentMethod()` (494) | `AP/Gateways/Stripe/Traits/StripePaymentMethodsTrait.php:530` (method 515-583) | yes, the pointer order | Already returns early, for three reasons: R is on-hold and only pending or failed pass (526); an R paid by ACH or SEPA has payment method `stripe_*`, not `stripe` (527); and R has a transaction id (528). | No change. |
| `SubscriptionLifecycle::dateGuard()`. Reached from Developer `setNextPaymentDate()`, `setTrialEnd()`, `endTrial()` (`arraysubs/src/Developer/Subscriptions.php:309-323`) and from trial conversion (`SubscriptionMutation.php:453, 474`). | `arraysubs/src/Features/SubscriptionAdmin/Services/SubscriptionLifecycle.php:66` | no | Already blocked by `GracePolicy::unpaidOrder($id)` (`payment_pending`). | No change. |
| `SubscriptionMutation::validateUpdates()`. Reached from REST `POST arraysubs/v1/subscriptions/{id}/update` and `/status` (`SubscriptionController::updateSubscription()` and `changeStatus()`, `arraysubs/src/Features/SubscriptionAdmin/REST/SubscriptionController.php:803-822`); from the admin UI (`SubscriptionEdit.jsx:188`, `SubscriptionForm.jsx:437`); and from Developer `update()`, `changeStatus()`, `activate()` (`arraysubs/src/Developer/Subscriptions.php:259-270`). | `arraysubs/src/Features/SubscriptionAdmin/Services/SubscriptionMutation.php:136-138` | no | Billing-term edits are already refused as `frozen_renewal_invoice` (149-153), because R is unpaid and on-hold. **Status-only edits go through.** | Status edits are also refused for the whole window: 409 `gateway_settlement_pending`, "Wait for the current gateway payment to settle." This covers status-field Activate, On hold and Cancel. The separate cancel endpoint `/subscriptions/{id}/cancel` does not call the function, though an immediate cancel already refuses R (§4.1). |

**Listeners that stop firing while the sweeps skip.** None of these calls the function itself; none of the emails, reminders or Pro audits does.
- `AutoNotes::onSubscriptionPutOnHold` (`arraysubs/src/Features/SubscriptionNotes/Services/AutoNotes.php:73, 716`)
- `EmailManager::on_status_change` (`arraysubs/src/Features/Emails/Services/EmailManager.php:134`)
- `LifecycleDetailsTrait::onGraceEnded` (`arraysubs/src/Features/Integrations/Services/Traits/LifecycleDetailsTrait.php:24, 101-108`). It emits the `grace_ended` and `cancelled` integration events.
- `PaymentHooksLifecycleTrait::onSubscriptionCancelledOverdue` (`AP/Services/Traits/PaymentHooksLifecycleTrait.php:46-49`). For Stripe it sets `_gateway_status=inactive`.
- Pro `LifecycleEventLogger::onOverdueCancellation` (`arraysubspro/src/Features/RetentionAnalytics/Services/LifecycleEventLogger.php:127, 507`)

**Sweeps and snapshot look at different order sets.** The sweeps pass no order, so any of the 5 newest unpaid renewal orders with a deadline counts. `snapshot()` passes the single `unpaidOrder()`. They agree when the settling order is the unpaid one, which is the normal case.

## 2. `_arraysubs_pending_deadline`

### Writers
The deadline is always "now + days" at the moment of writing. Stripe and Mollie rewrite it, which restarts the window. PayPal and Paddle write it only once.

| Writer | file:line | Days | When |
|---|---|---|---|
| Stripe `markOrderPendingDeadline(\WC_Order $order, int $days = 2)` | `AP/Gateways/Stripe/Traits/StripeIntentResolutionTrait.php:1505-1508` (07:13 UTC) | 2 at every call site | – |
| ArraySubs off-session charge returned `processing` | `StripeRenewalPaymentTrait.php:393-407` (deadline at 399) | 2 | – |
| Stored intent is `processing` or `requires_capture` on a later attempt | `StripeIntentResolutionTrait::resolveExistingIntent()` 212-213 | 2 | – |
| Stored intent unreadable | `resolveExistingIntent()` 134 | 2 | – |
| Succeeded intent that does not bind to the renewal | `resolveExistingIntent()` 185; `StripeRenewalPaymentTrait.php:368` | 2 | – |
| Reconcile-before-retry found a charge that does not match, or gave no clear answer | `StripeRenewalPaymentTrait.php:134, 158` | 2 | – |
| Any `buildExistingIntentPendingResult()` | `StripeIntentResolutionTrait.php:1412` (deadline at 1418) | 2 | Unclear states of an existing intent |
| Mollie `markOrderPendingDeadline($order, $days)` | `AP/Gateways/Mollie/MollieDelegate.php:714-716`. Calls at 402, 411, 566, 669, 691, 780, 826, 845-848. | `SEPA_PENDING_DAYS = 21` (90); `CARD_PENDING_DAYS = 3` (97) | Payments ArraySubs created that are open, pending or authorized; unreadable payments; unknown create outcomes |
| PayPal `markPayPalRenewalPendingReconciliation()` | `AP/Gateways/PayPal/Traits/PayPalRenewalsTrait.php:74-97` (call at 54) | 5; filter `arraysubs_paypal_pending_renewal_days`, clamped to 1-30 | Waiting for `PAYMENT.SALE.COMPLETED`. Write-once check at 76. |
| Paddle `markPaddleRenewalPendingReconciliation()` | `AP/Gateways/Paddle/Traits/PaddleBillingTrait.php:402-425` (calls at 164, 190, 739) | 5; filter `arraysubs_paddle_pending_renewal_days`, 1-30 | Write-once check at 404. |

### Readers
- The filter handler: `PaymentHooksRenewalsTrait.php:243, 274` (§1).
- The reconciler: `AP/Services/Traits/PaymentHooksReconciliationTrait.php:92` (query), 109 and 118 (paging), 244 (deadline).
- Immediate cancellation: `arraysubs/src/Features/Subscriptions/Services/PendingRenewalOrderCancellation.php:387`, inside `validateNoSettlement()` (381-415). It refuses to cancel when the order has:
  - this meta,
  - an open charge attempt, or
  - any payment reference (397-411): transaction id, `_arraysubs_pending_transaction_id`, `_last_gateway_transaction_id`, `_stripe_intent_id`, `_stripe_charge_id`, `_mollie_payment_id`, `_paddle_transaction_id`.

  It runs from `resolve()` (292-295) → `prepare()`, which `arraysubs/src/functions/cancellation-helpers.php:432` uses.
- The PayPal and Paddle write-once checks (PayPal 76, Paddle 404).

### Clearers
- **Stripe:** cleared before a fresh charge, `StripeRenewalPaymentTrait.php:283`.
- **Reconciler:**
  - order paid: 250-255
  - cancelled or refunded: `releaseDeadOrderAfterVerification()` 160-234 (deletes at 173, 202, 210, 221)
  - subscription or gateway no longer found, and expired: 278-280
  - reconciled as paid: 331
  - expired: 387
- **Mollie:** 626, `clearTerminalPayment()` 882-889 (886), 1804, `completeRenewal()` 2335.
- Nothing clears it on a WooCommerce status change. The handler simply ignores it on paid, cancelled and refunded orders.

### What happens at expiry
`reconcileGateways()` (`PaymentHooksReconciliationTrait.php:38-53`) runs every 6 hours (`AP/Services/Traits/PaymentHooksScheduledJobsTrait.php:277-284`; the next pending run on staging is 08:20 UTC). `reconcilePendingRenewalOrder()` (242-422) handles each order:

- **Paid:** the meta is cleared.
- **Cancelled or refunded:** it asks the gateway. A late settlement gets the order note "The gateway charge %s settled after this renewal was cancelled. Refund it in your gateway dashboard — the subscription was not reactivated." plus a warning log (185-200). Nothing is reactivated or refunded.
- **Otherwise:** it calls `verifyRecentChargeForOrder()`. A verified charge that binds correctly completes the order (294-375).
- **Not expired:** it touches `date_modified`, so unpaid-order reapers leave the order alone (377-385).
- **Expired:** it deletes the meta. If the order is already failed, it stops there. Otherwise it:
  - logs a `processing_error` failure,
  - sets the order to `failed` with "Renewal payment was never confirmed by the gateway within the settlement window.",
  - fires `arraysubs_gateway_payment_failed` (387-421). That triggers an AutoNote and the failure email (`EmailManager.php:155`).

  No retry is scheduled (that is #67) and the subscription itself is not touched. Grace then simply runs from the original due date.

### The existing "bounded window" for ArraySubs' own Stripe charges
**During the 2 days** after the charge returned `processing` (or the unclear state was seen):
- both sweeps skip the subscription,
- `cancelPendingRenewalOrders()` spares the order,
- `hasSettlingRenewalPayment()` is true,
- `SubscriptionMutation` refuses status and term edits,
- the reconciler asks Stripe every 6 hours.

**Afterwards**, grace resumes on the next hourly sweep. The thresholds are not extended by the time spent settling (`GracePolicy::thresholds()` 33-59). The reconciler fails the order.

**A weakness of its own (not #66).** For Stripe, a `processing` intent verifies as `found=true, charged=false, conclusive=false` (`StripeRenewalPaymentTrait::verifyRecentChargeForOrder()` 539-611; branch 575-586). So after 2 days the reconciler fails an ACH or SEPA renewal that Stripe is still processing, and emails the customer a failure. 2 days is shorter than ACH settlement (T+4 business days) and SEPA settlement (T+6 business days).
- If the payment later succeeds, Woo Stripe's `payment_intent.succeeded` still completes a *failed* order through its deferred path (§3.4).
- `charge.succeeded`, however, ignores any order that is not on-hold.

**Both kinds look alike after the processing webhook.** Woo Stripe's own `payment_intent.processing` webhook also moves these ArraySubs-created orders from pending to on-hold, with the charge id as transaction id (`WCS/class-wc-stripe-webhook-handler.php:1367-1374`). After that webhook, an ArraySubs-created processing renewal looks exactly like a Pay-page one, apart from `_arraysubs_pending_deadline`.

## 3. Woo Stripe 10.9.0 when a Pay-page payment does not settle at once

### 3.1 Pay-page submit
The order-pay form goes to `WC_Form_Handler::pay_action()` (`woocommerce/includes/class-wc-form-handler.php` ~502-544). It does not change the status before calling `process_payment()`, so a renewal that ArraySubs marked failed is still `failed` at this point.

`WC_Stripe_UPE_Payment_Gateway::process_payment()` (`WCS/payment-methods/class-wc-stripe-upe-payment-gateway.php:1445-1467`) calls `process_payment_with_payment_method()` (1593-1766), which does the following:

1. **Stores the payer.** It writes `_stripe_source_id`, `_stripe_customer_id` and `_stripe_upe_payment_type` (1631-1637).
2. **Gets a PaymentIntent.** `process_payment_intent_for_order()` (3248-3311) either reuses a compatible stored PaymentIntent or creates and confirms a new one. "Compatible" means the same method types and amount, and a status other than succeeded, canceled, requires_action or requires_confirmation (`get_existing_compatible_payment_intent()` 4136-4175).
   - ArraySubs' declined PaymentIntent allows only cards. So an ACH or SEPA payment always gets a **new** PaymentIntent.
   - `save_intent_to_order()` (`WCS/abstracts/abstract-wc-stripe-payment-gateway.php:1881-1906`) stores `_stripe_intent_id`. When the id changes, it also adds the note "Stripe payment intent created (Payment Intent ID: %1$s)" (`WCS/class-wc-stripe-order-helper.php:1064-1081`).
3. **Renames the payment method.** `set_payment_method_title_for_order()` (called at 1706; body 2953-2979) sets the order's payment method to `stripe` for card and Link, and to `stripe_<type>` for anything else, for example `stripe_us_bank_account` or `stripe_sepa_debit`. The UPE method id is set at `WCS/payment-methods/class-wc-stripe-upe-payment-method.php:172`.
4. **Processes the charge.**
   - If the PaymentIntent needs no customer action, it calls `process_response($charge, $order)` (1740).
   - Otherwise the customer completes the action in the browser, and the return runs through `process_upe_redirect_payment()` (2212) or `process_order_for_confirmed_intent()` (2478), which also calls `process_response()` (~2572).

`process_response()` (`WCS/abstracts/abstract-wc-stripe-payment-gateway.php:601-736`):

| Charge | Effect |
|---|---|
| any | Fires `wc_gateway_stripe_process_payment_charge` (623). Sets `_stripe_charge_captured` to yes or no (630). Records fees when a balance transaction is present (632-634). |
| captured, `status=pending`: a bank debit still processing (SEPA, ACH, BACS, BECS, ACSS) | Reduces stock if not yet done. Sets the transaction id to the charge id (`py_…` for bank debits). Calls `update_status('on-hold', 'Stripe charge awaiting payment: %s.')` (655-665). |
| captured, `succeeded`, `outcome.type=manual_review` (Radar review) | Sets `_stripe_status_before_hold` to the default paid status (processing or completed) and the transaction id to `ch_…`. **The status does not change**, so a Pay-page renewal stays `failed` until `review.opened` arrives (679-682). |
| captured, `succeeded`, normal | `payment_complete()` and the note "Stripe charge complete (Charge ID: %s)" (683-697). |
| `failed` | Adds a note and throws (702-709). |
| not captured ("capture later" setting; staging has `capture=yes`) | Sets the transaction id and puts the order on-hold with "Stripe charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization…" (712-721). |
| always, at the end | `do_action('wc_gateway_stripe_process_response', $response, $order)` (733) |

Then:
- WooCommerce sends its own customer email for failed → on-hold and pending → on-hold (`woocommerce/includes/emails/class-wc-email-customer-on-hold-order.php:42-44`).
- An on-hold order no longer needs payment, because `WC_Order::needs_payment()` accepts only pending and failed (`woocommerce/includes/class-wc-order.php:1848-1859`). So the Pay page closes.
- ArraySubs adds a lifecycle check on top: `OrderIntegration::guardRenewalOrderNeedsPayment()`, `arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:155-172`.

**The order after an ACH payment:**
- status `on-hold`; transaction `py_…`
- `_stripe_intent_id` = the new PaymentIntent
- `_stripe_charge_captured=yes`, `_stripe_upe_payment_type=us_bank_account`
- payment method `stripe_us_bank_account`
- notes "Stripe payment intent created…" and "Stripe charge awaiting payment: py_…."
- no `_arraysubs_pending_deadline`, and no ArraySubs note

### 3.2 `payment_intent.processing` webhook
`process_payment_intent()` (`WCS/class-wc-stripe-webhook-handler.php:1320-1481`) only accepts orders that are pending or failed (filter `wc_stripe_allowed_payment_processing_statuses`, 1338-1347).
- **A Pay-page order that is already on-hold:** it returns without touching the order, and `resolved_order` stays null.
- **A pending or failed order** (for example ArraySubs' own processing charge): it sets the latest charge as transaction id, sets on-hold, and adds the same note (1367-1374). It does not write `_stripe_charge_captured`.

`wc_stripe_webhook_received` fires in both cases (`process_webhook()` 2628-2716; `run_webhook_received_action()` 2725-2744). ArraySubs maps no handler to this event (`AP/Gateways/Stripe/Traits/StripeWebhooksTrait.php:469-485`).

### 3.3 Radar review
- **`review.opened`** → `process_review_opened()` (1147-1190). It finds the order by PaymentIntent, or by charge.
  - It overwrites `_stripe_status_before_hold` with the *current* status (1167). For a Pay-page renewal that is `failed`.
  - It adds the note "A review has been opened for this order. Action is needed. Please go to your Stripe Dashboard to review the issue. Reason: (…)."
  - It puts the order on-hold, unless `_stripe_status_final` is set or the `wc_stripe_webhook_review_change_order_status` filter says no (1184-1189).
- **`review.closed`** → `process_review_closed()` (1198-1240). It acts only when all of these hold: `_stripe_charge_captured=yes`, the status is not final, the order is on-hold, and `closed_reason=approved`.
  - It then calls `update_status(processing or completed)`. A stored pending, failed, on-hold or cancelled status is mapped to the default paid status (1231-1236).
  - This is `update_status`, not `payment_complete()`. WooCommerce sets `date_paid` in `maybe_set_date_paid()` (`woocommerce/includes/class-wc-order.php:355-376`).
  - ArraySubs' `onOrderStatusChanged()` fires `arraysubs_order_paid` (`OrderIntegration.php:880-949`), and the renewal is processed.
  - Any other close (refunded, refunded_as_fraud, a dispute, or an order that is not on-hold) only adds a note.
- **Card payments:** `charge.succeeded` returns early for "synchronous" methods (784-794), so a card in review completes only through `review.closed`.
- **Per the Stripe docs:** a captured payment in review stays in review until the merchant approves or refunds it, and a dispute closes it automatically. ACH and SEPA payments cannot be reviewed manually.

### 3.4 Delayed success
- **`charge.succeeded`** → `process_webhook_charge_succeeded()` (752-848):
  - finds the order by charge id, which is the transaction id;
  - sets `resolved_order` (800);
  - requires the order to be `on-hold` (802) and the charge to be `captured` (810);
  - sets the transaction id and the fees;
  - unless the risk outcome is `manual_review`, calls `payment_complete($charge->id)` and adds the note "Stripe charge complete (Charge ID: %s) (via webhook)" (832-836).

  ArraySubs then:
  - checks the lifecycle in `guardRenewalPaymentCompletion()` (`OrderIntegration.php:188-262`);
  - processes the renewal in `activateSubscriptionsFromOrder()` (starts at 1262) → `processRenewalPayment()` (starts at 1555; lifecycle check at 1590-1612).

  On-hold subscriptions are allowed through, so the subscription is reactivated.
- **`payment_intent.succeeded`** is ignored for on-hold and cancelled orders (1338-1347). Pending and failed orders are deferred (1389-1446): `process_deferred_webhook()` (1661-1755) → `handle_deferred_payment_intent_succeeded()` (1875-1904) → `process_response()`.

### 3.5 Delayed failure
- **`charge.failed` / `charge.expired`** → `process_webhook_charge_failed()` (852-883). It finds the order by charge id. Unless the order is already failed or its status is final, it sets `failed` with "This payment failed to clear." (871) or "This payment has expired.", and fires `wc_gateway_stripe_process_webhook_payment_error`. This also turns a *cancelled* order into `failed`.
- **`payment_intent.payment_failed`** acts only on pending or failed orders (default branch 1447-1477). It sets `failed`, adds "Stripe SCA authentication failed. Reason: %s" and sends the admin failed-order email. An on-hold order is skipped.
- Stripe does not guarantee the order of these two events. The on-hold order therefore becomes `failed` through `charge.failed`, and it keeps its transaction id.

### 3.6 When ArraySubs cancels the order
`WC_Stripe_Order_Handler::cancel_payment()` (`WCS/class-wc-stripe-order-handler.php:29, 423-447`) only refunds or voids an **uncaptured** authorization, and only for `stripe`, `stripe_affirm`, `stripe_klarna`, `stripe_afterpay_clearpay` and `stripe_amazon_pay` (`WCS/class-wc-stripe-helper.php:1639-1651`). It leaves alone:
- a bank debit still processing (`stripe_us_bank_account`, `stripe_sepa_debit`),
- a captured card payment in review.

So the money keeps moving after the order is cancelled.

### 3.7 A late success against a cancelled order (today)
**Woo Stripe does nothing.** No status change, no note, no refund:
- `charge.succeeded` returns at 802 because the order is not on-hold.
- `payment_intent.succeeded` returns at 1338-1347 because cancelled is not an allowed status.

  So the 10.9.0 auto-refund `handle_deferred_payment_for_cancelled_order()` (1921-2063, routed at 1688-1714) is never reached. It only covers an order that was pending or failed when the webhook arrived and was cancelled before the deferred job ran.
- An approved `review.closed` only adds a note, because the order is not on-hold.

**ArraySubs does not help either.** `wc_stripe_webhook_received` (`charge.succeeded`) → `handleOfficialStripeWebhook()` (`StripeWebhooksTrait.php:273-398`) → `handlePaymentSucceeded()` (`AP/Gateways/Stripe/Traits/StripeWebhookEventsTrait.php:23-81`).
- **For a bank debit:** `capturePaymentContext()` → `StripeOrderContextBridge::capture()` returns `[]`, because the payment method is `stripe_*`, not `stripe` (`AP/Services/StripeOrderContextBridge.php:87-89`). So the handler reports failure (39) and queues a metadata retry (`StripeWebhooksTrait.php:349-366`).
- **For cards:** that event already returned before setting `resolved_order`. ArraySubs at most logs "Stripe payment confirmed via official webhook for order #%d" and fires `arraysubs_gateway_payment_succeeded`, which no core code listens to.
- **No reactivation.** `processRenewalPayment()` refuses a cancelled subscription with `renewal_payment_lifecycle_blocked` (`OrderIntegration.php:1590-1612`), and `guardRenewalPaymentCompletion()` throws for it.
- **No refund and no warning.** The reconciler's late-settlement note (`PaymentHooksReconciliationTrait.php:160-234`) does not apply, because the order never had `_arraysubs_pending_deadline`.

### 3.8 Other gateways that settle slowly, and the recommendation
- **PayPal and Paddle** are ArraySubs' own gateways, and the gateway runs the billing clock. Their renewal orders already carry a 5-day `_arraysubs_pending_deadline`. The ArraySubs PayPal code has no eCheck or "pending sale" handling (no `PAYMENT.SALE.PENDING` or eCheck references). Customers do not pay these renewals on the Woo Pay page.
- **Mollie**, through a delegate that rides the Mollie plugin, supports only `mollie_wc_gateway_creditcard` and `mollie_wc_gateway_directdebit` (`MollieDelegate.php:77-80`). There is no bank transfer.
  - Payments ArraySubs creates get the 21-day or 3-day deadlines.
  - A Pay-page payment made through the Mollie plugin is watched only if a later ArraySubs attempt finds its `_mollie_payment_id` (`resolveExistingPayment()` 845-848).
  - The Mollie plugin is not installed on staging, so I could not check its order statuses.
- **WooCommerce BACS and cheque** put orders on-hold without a transaction id, and no webhook ever settles them.
- **Installed on staging:** active plugins are arraysubs 2.0.9, arraysubspro 1.2.8, woocommerce 11.0.1, woocommerce-gateway-stripe 10.9.0 and wp-mail-smtp 4.9.0. Mollie, WooCommerce PayPal Payments and WooPayments are not installed.

**Recommendation: detect only Stripe payments, but store the result in a gateway-neutral meta that the core handler reads.** Only Woo Stripe gives reliable signals here: charge `status=pending`, `outcome.type=manual_review`, `captured=false` and `review.opened`.

Do not use a blanket "pending or on-hold with a transaction id" rule for every gateway:
- unknown gateways and admins also put orders that have transaction ids on hold;
- the other ArraySubs gateways already speak through `_arraysubs_pending_deadline`.

A neutral meta also lets a WooPayments or PayPal Payments producer join later.

## 4. Finding the unpaid renewal order, and today's timeline

### 4.1 Which code looks at which order
| Function | file:line | Rule | Result for R (Pay page, on-hold, has transaction id) |
|---|---|---|---|
| `GracePolicy::unpaidOrder()` | `RB/GracePolicy.php:62-88` | The pointer `_pending_renewal_order_id` if it is pending, failed or on-hold, a renewal of the same subscription, with the same customer. Otherwise the newest such order (ID DESC, pages of 50). | **Unpaid**: returns R |
| `Hooks::hasUnpaidRenewalOrder()` | `RB/Hooks.php:1126-1129` | `unpaidOrder() !== null` | true |
| `RenewalProcessor::getPendingRenewalOrder()` | `RB/RenewalProcessor.php:1109-1172`, with `firstUnsettledRenewalOrder()` 1180-1195 and `renewalOrderIsSettled()` 1295-1306 | The newest of up to 10 pending, on-hold or failed renewal orders that has no transaction id, no paid date and no `_arraysubs_renewal_settled_at`. | **Skipped as settled** |
| `RenewalProcessor::hasSettlingRenewalPayment()` | `RB/RenewalProcessor.php:1213-1251` | Pointer only. Paid but not yet processed counts as true. Otherwise true when pending or on-hold with a transaction id, or settling according to the filter. | true, with no time limit |
| `cancelPendingRenewalOrders()` | `RB/RenewalProcessor.php:214-295` | Every pending, on-hold or failed renewal order, plus the pointer. | Cancelled |
| Immediate cancellation: `PendingRenewalOrderCancellation::resolve()` and `validateNoSettlement()` | `.../PendingRenewalOrderCancellation.php:224-300, 381-415` | Refuses when any payment reference is present. | Refused: "The renewal order still has a provider payment reference and cannot be cancelled safely." |

### 4.2 Timeline today
Staging policy:
- grace is 3 days before on-hold and then 7 days before cancel, set at site level;
- retries are on: 3 attempts, 1 day apart, stopping on a hard decline;
- the renewal spread window is up to 6 hours;
- the hourly overdue job runs at about 26 minutes past each hour; phases 2 and 3 follow 5 and 10 seconds later.

T0 = `_next_payment_date`.

1. **Before T0** (by the invoice lead time): `createRenewalInvoice()` creates R as pending and sets the pointer.
2. **T0 + spread offset:** the automatic charge declines. `handlePaymentAttemptFailure()` (`RB/RenewalProcessor.php:783-847`):
   - sets R to `failed` and writes the failure meta,
   - schedules retry 1 for one day later (soft decline) or none (hard decline),
   - sends the failure email with the Pay link.
3. **T1, the customer pays R with ACH on the Pay page** (§3.1): R becomes on-hold with a `py_…` transaction, and WooCommerce sends its on-hold email.
4. **Retry 1, if one was scheduled (T0 + offset + 1 day):** `process()` runs.
   - `getPendingRenewalOrder()` returns null, because R counts as settled. `hasSettlingRenewalPayment()` is true, so it returns false: nothing is charged, and no further retry is scheduled (`scheduleNextRetry()` is not called).
   - A manual retry answers "No failed renewal order to retry." (555-563).
   - The new-card retry path also skips R.
5. **First hourly sweep after `hold_eligible_at`** = max(T0 + 3 days, T0 + offset + 1 hour, …) (`GracePolicy::thresholds()` 33-59): phase 1 finds R unpaid, and the filter answers false. The subscription goes to `arraysubs-on-hold`, `_on_hold_date` is set, and the "put on hold" note and status email go out.
6. **First sweep after `cancel_eligible_at`** = max(T0 + 10 days, `_on_hold_date` + 7 days): phase 2 calls `cancelOverdueSubscription()` (`RB/RenewalProcessor.php:161-206`).
   - **Subscription:** cancelled, with `_cancellation_reason=overdue_payment` and `_cancelled_by=system`.
   - **Order:** R is cancelled ("Subscription cancelled due to non-payment.").
   - **Schedule:** removed, and `_payment_retry_next_attempt_at` is deleted.
   - **Hook:** `arraysubs_data_cancelled_overdue` fires (Stripe `_gateway_status=inactive`, integration `grace_ended`, the Pro lifecycle log, the cancellation email).
   - **The debit:** Woo Stripe does not stop it (§3.6).
7. **Settlement:**
   - before step 6: `charge.succeeded` pays R, the renewal is processed and the subscription is reactivated from on-hold. The customer had paid but sat on hold for days.
   - after step 6: it is ignored (§3.7). The money is kept, and the order and subscription stay cancelled with no trace.
   - failure: `charge.failed` sets R to `failed` (or turns cancelled into failed after step 6). Grace carries on unchanged.

Examples, with payment at T0 + 2 hours (calendar days):
- **ACH.** Stripe: up to 4 business days. The debit usually settles between T0 + 4 and T0 + 6 days, so the subscription is held at T0 + 3 days and reactivated on settlement. A payment made on day 5 or later can miss the T0 + 10 day cancellation.
- **SEPA.** Stripe: T+6 business days ("wait at least 6 business days"); most failures arrive within 6 business days. That is around T0 + 8 to 10 days, so a SEPA payment made late, or around weekends and holidays, is often cancelled first.
- **Card in Radar review.** R stays `failed` with `ch_…` until `review.opened` (seconds later), then goes on-hold. Approval before T0 + 10 days pays R through the status change and processes the renewal. Approval after T0 + 10 days only adds a note, and the money is kept.

### 4.3 Related risks found while tracing (outside #66)
- **A duplicate invoice in the review gap.** Between the synchronous response (status stays `failed`, transaction id set) and the `review.opened` webhook:
  - `getPendingRenewalOrder()` skips R because of its transaction id;
  - `hasSettlingRenewalPayment()` returns false because R is `failed`.

  A retry that runs in that gap creates a second invoice for the cycle and charges the saved method (`RB/RenewalProcessor.php:367-416`). That method is usually the declined card, so the usual result is a duplicate invoice and failure emails. If the saved method works, it is a double charge. The gap is only as long as webhook latency.
- **ACH and SEPA do not update the saved payment method (suspected).** A Pay-page payment by ACH or SEPA changes the order's payment method to `stripe_us_bank_account` or `stripe_sepa_debit`. But `StripeOrderContextBridge::captureFromPaidOrder()` and `capture()` (`AP/Services/StripeOrderContextBridge.php:68-71, 87-89`) only accept `stripe`. So after a successful bank-debit Pay-page payment, the subscription would keep its old, declined card, and `handlePaymentSucceeded()` would report failure and queue metadata retries. This is from reading the code only; confirm it live before filing.
- **ArraySubs' own Stripe processing charges get only 2 days** (§2). That is shorter than ACH and SEPA settlement. At expiry the reconciler fails the order and emails a failure while Stripe is still processing.

## 5. Recommended design

### 5.1 What marks an order as settling (new; Stripe only)

**Producer.** A small service in core, for example next to `StripeOrderContextBridge`, registered from `StripeDelegate::__construct()`.

It listens to two hooks:
- **`wc_gateway_stripe_process_response($charge, $order)`** (`WCS/abstracts/abstract-wc-stripe-payment-gateway.php:733`). This fires for the Pay page, for redirect returns and for deferred webhooks.

  | Charge | Kind |
  |---|---|
  | `captured`, `status === 'pending'` | `bank_debit` |
  | `captured`, `status === 'succeeded'`, `outcome.type === 'manual_review'` | `review` |
  | `!captured` | `authorization` |
- **`wc_stripe_webhook_received($type, $notification, $resolved_order)`** (`WCS/class-wc-stripe-webhook-handler.php:2725-2744`).

  | Event | Condition | Kind |
  |---|---|---|
  | `payment_intent.processing` | non-null `$resolved_order` (Woo Stripe accepted a pending or failed order and moved it on-hold, 1350, 1367-1374) | `bank_debit` |
  | `review.opened` | `$resolved_order` is set at 1165 | `review` |

Stamp only when all of these hold:
- `_is_renewal_order=yes`;
- exactly one owning subscription (`_subscription_id` matches `PaymentMetaNormalizer::findSubscriptionIdsForOrder()`);
- the payment method is `stripe` or `stripe_*` (Woo Stripe's own test, `WCS/class-wc-stripe-order-helper.php:1290-1292`);
- no `date_paid`, and the order is not paid;
- the transaction id is set and equals the event's charge id.

The stamp is three new order metas (names are suggestions):

| Meta | Content |
|---|---|
| `_arraysubs_settlement_until` | UTC `Y-m-d H:i:s` |
| `_arraysubs_settlement_txn` | charge id |
| `_arraysubs_settlement_kind` | `bank_debit`, `review` or `authorization` |

- **Write once per transaction id.** Keep the first stamp; do not extend it.
- **Optional order note:** "ArraySubs pauses dunning until %s while Stripe confirms this payment."
- **Optional:** if the charge carries `payment_method_details.{sepa_debit|us_bank_account}.expected_debit_date`, use max(default, expected date + 2 days), within the cap.
- **Never stamp from:**
  - read paths (sweeps, snapshot, REST),
  - admin status changes,
  - anything that needs a Stripe API call.

**Consumer**, gateway-neutral, in `PaymentHooksRenewalsTrait::orderIsAwaitingSettlement()` (268-283):
- Keep the `_arraysubs_pending_deadline` branch exactly as it is.
- Add a branch that returns true when all of these hold:
  - `_arraysubs_settlement_until` is in the future;
  - the status is on-hold or pending, or `failed` only when the kind is `review` (this covers the time before `review.opened`);
  - there is no `date_paid`;
  - `get_transaction_id() === _arraysubs_settlement_txn`.
- In `filterAwaitingGatewaySettlement()`, make the subscription query (227-247) match either meta key (nested `OR`, which HPOS supports), or also test the pointer order.
- In `RenewalProcessor::hasSettlingRenewalPayment()` (1243-1250), also accept a `failed` pointer that the filter reports as settling. That closes the review gap in §4.3. Leave the rest unchanged.
- Optional: expose `awaiting_gateway_settlement_until` in `GracePolicy::snapshot()`.
- Optional cleanup: delete the stamp when the order turns paid, failed or refunded. Stale stamps are harmless anyway, because the status checks ignore them.

### 5.2 Window lengths
Use one filter, for example `arraysubs_stripe_settlement_window_days($days, $kind, $payment_type, $order)`, clamped to 1-30 days.

| Kind or method | Stripe timing | Default |
|---|---|---|
| `us_bank_account` (ACH) | up to 4 business days (T+4; T+2 with faster settlement) | 7 days |
| `sepa_debit` | T+6 business days: 1-2 days to submit plus a 5-day refusal window. Mollie's ArraySubs SEPA window is 21 days. | 14 days |
| `bacs_debit`, `au_becs_debit`, `acss_debit` (not enabled on staging) | debits that take several days | 14 days |
| Radar `review` | no expiry; the merchant decides | 7 days |
| `authorization` (capture later; staging has `capture=yes`) | card authorizations usually stay valid about 7 days | 7 days |

**Anchor:** the time of the first Woo Stripe signal, which is when the stamp is written. Rejected alternatives:

| Alternative anchor | Why not |
|---|---|
| `date_modified` | Any save changes it, and the reconciler bumps it on purpose (`PaymentHooksReconciliationTrait.php:381-382`). |
| order-note timestamps | The note text is translated. |
| Woo Stripe meta | None of it carries a timestamp. |
| Stripe API lookups | One call per subscription per hourly sweep. |

### 5.3 After the window
- **Grace resumes.** The filter returns false and grace picks up the existing thresholds: counted from the due date, and cancellation never earlier than `_on_hold_date` plus the before-cancel days. This is the same as when `_arraysubs_pending_deadline` expires.
- **ArraySubs must not change the order status.** Woo Stripe still owns the order. If `charge.succeeded` arrives while the order is on-hold, Woo Stripe completes it, and that reactivates an on-hold subscription.
- **No second charge.** `hasSettlingRenewalPayment()` keeps blocking another charge for the cycle. This is deliberately unlimited: never charge again while a debit may still arrive.

Suggested additions:
- When `cancelPendingRenewalOrders()` cancels an order that still has a stamp, add an order note naming the transaction ("refund it in Stripe if it settles") and log a gateway warning.
- Add a `wc_stripe_webhook_received` listener for `charge.succeeded` whose `$resolved_order` is a cancelled ArraySubs renewal. It writes the same note and warning as `releaseDeadOrderAfterVerification()`, without reactivating or refunding.

### 5.4 How this fits with the existing pieces
- **`_arraysubs_pending_deadline`.** Keep its meaning: a charge ArraySubs created, reconciled by ArraySubs, failed at expiry. Do not write it for payments Woo Stripe created, for three reasons:
  - the reconciler would fail the order and email a failure at expiry;
  - it cannot complete these payments, because Woo Stripe's PaymentIntent metadata lacks the `subscription_id` and `site_url` binding that `ProviderPaymentCompletion` checks (`PaymentHooksRenewalsTrait.php:132-156`);
  - if #67 connects reconciler failures to the retry ladder, a retry could charge the saved card while the bank debit is still in flight.

  Both markers can sit on an ArraySubs-created processing charge after Woo Stripe's `payment_intent.processing` webhook. The reconciler still fails that order after 2 days, and the new marker then stops counting because the status is failed. Making ArraySubs' own window depend on the payment type is a separate change, for #67 or a follow-up.
- **`GracePolicy::unpaidOrder()`** should keep returning R, so dunning can resume after the window.
- **`SubscriptionMutation`** returns 409 `gateway_settlement_pending` for status edits during the window, which for SEPA can last up to 14 days. Decide whether a change to `arraysubs-cancelled` should stay allowed there.
- **Immediate cancellation** already refuses R because of its payment references. No change.

### 5.5 Side effects to avoid
- An admin puts an order with an old transaction id on hold: no stamp, because admin changes do not stamp and the stamp must match the current transaction id.
- A failed order carrying the transaction id of a failed debit (after `charge.failed`): not settling, because its status is failed and its kind is `bank_debit`. Grace resumes at the next sweep.
- BACS or cheque on-hold orders without a transaction id, or any other gateway: never stamped.
- Paid orders that later go on-hold: excluded by `date_paid`. For example, the Stripe dispute handler stores `_stripe_status_before_hold` and sets on-hold (`WCS/class-wc-stripe-webhook-handler.php:569-614`, on-hold at 595).
- Cancelled and refunded orders: excluded.
- No Stripe API calls inside the filter, and no meta writes from read paths.
- Keep the sweeps and `snapshot()` looking at the same orders.
- `queueRenewalAttemptWithNewCard()` and `manualRetry()` must keep refusing R. They already do.

## 6. Testing on staging without changing gateway settings

### 6.1 Staging facts (read 06:45–07:00 UTC)
**Stripe settings** (`woocommerce_stripe_settings`, non-secret keys only):
- `enabled=yes`, `testmode=yes`, `capture=yes`
- `upe_checkout_experience_enabled=yes`, `upe_checkout_experience_accepted_payments=["card","link"]`
- `pmc_enabled=yes`, `optimized_checkout_element=yes`
- `saved_cards=yes`, `express_checkout=yes`
- `test_connection_type=connect`

**Enabled payment methods.** With PMC on, Woo Stripe reads the enabled methods from the Stripe payment method configuration (`WCS/class-wc-stripe-payment-method-configurations.php:414-440`). The cached copy is `wcstripe_cache_test_payment_method_configuration`, refreshed at 06:30 UTC with a 20-minute TTL:
- **ON:** `afterpay_clearpay`, `amazon_pay`, `card`, `link`, `us_bank_account`
- **OFF:** among others, `sepa_debit`, `bacs_debit`, `au_becs_debit`, `acss_debit`

I read that option directly. I did not call `get_upe_enabled_*()`, because with an expired cache it would call Stripe.

**ACH should be available on the renewal Pay page.**
- The store currency is USD and the base country GB; the Stripe account country is US.
- ACH needs USD and a US billing address, and it is reusable (`WCS/payment-methods/class-wc-stripe-upe-payment-method-ach.php`).
- All 22 fixture customers (576–597) have billing country US.
- ArraySubs offers reusable, enabled methods on an automatic Stripe renewal Pay page (`StripeRecurringPaymentPolicy::getAvailableReusablePaymentTypes()`, `AP/Services/StripeRecurringPaymentPolicy.php:449-468`).

So ACH should appear there; confirm it in the browser.

**Other staging state:**
- **SEPA** cannot be tested without turning it on in the Stripe payment method configuration, which is a gateway setting change and not allowed.
- **Webhooks** are arriving: `wc_stripe_wh_test_last_success_at` is 2026-10-19 22:34 UTC, and the last validation result is `validation_succeeded`.
- **Scheduled jobs:** pending overdue-sweep parents at 07:26 and 07:27 UTC; the next reconcile at 08:20 UTC.
- **Overdue subscriptions right now:** only fixtures 68512, 68665, 68881 and 68996. All are active, due on 2026-10-19, and hold-eligible from 2026-10-22 under the site grace. A hand-run phase 1 or 2 acts on *every* overdue subscription, so re-check this set before running one.
- **Orders that carry `_arraysubs_pending_deadline`:** none.
- **Snapshot shape**, read-only, for fixture 68512: `phase=before_on_hold`, `hold_eligible_at=2026-10-22 09:43:18 UTC`, `cancel_eligible_at=2026-10-29 09:43:18 UTC`, `awaiting_gateway_settlement=false`.

### 6.2 The real Woo Stripe path (ACH that stays processing)
1. **Fixture.** Create a new fixture (`SQA_PHASE=fix-65-69`; register it with `register-fixture.php`): a US customer with an automatic Stripe subscription whose saved card declines at renewal. Use 4000 0000 0000 0341, which attaches but fails when charged; earlier fixtures used it.
2. **Failed renewal.** Let the renewal fail by running only that fixture's `arraysubs_process_renewal` action. R becomes `failed`.
3. **Pay.** As the customer, open R's Pay page. Do not record the order key.
   - Choose "ACH Direct Debit", then "Enter bank details manually".
   - Enter routing 110000000 and account 000000000009. Stripe's test docs say this account "stays in processing indefinitely".
   - Stripe test mode first verifies manually entered test accounts with microdeposits (descriptor code SM11AA, or amounts 32 and 45).
   - Woo Stripe 10.9.0 has no explicit microdeposit handling for payments, only for SetupIntents (`WCS/class-wc-stripe-order-handler.php:524-530`). So confirm in the browser that the Pay page completes.
   - If it does not, use a Financial Connections test institution, which verifies instantly. Its test payments settle almost at once ("test transactions are credited instantly"), so the on-hold state lasts only briefly.
4. **Expected state of R:**
   - `on-hold`, transaction `py_…`, payment method `stripe_us_bank_account`
   - `_stripe_intent_id` = a new PaymentIntent
   - `_stripe_charge_captured=yes`, `_stripe_upe_payment_type=us_bank_account`
   - notes "Stripe payment intent created…" and "Stripe charge awaiting payment: py_…."
   - WooCommerce's on-hold email in Mailpit
   - no `_arraysubs_pending_deadline`
5. **Pre-fix proof, without waiting days.**
   1. Set that subscription's grace to 0 and 0 through the developer API: `ArraySubs\Developer\Subscriptions::fromId($id)->setGracePolicy(['before_on_hold'=>0,'before_cancel'=>0])` (→ `SubscriptionLifecycle::grace()`, `SubscriptionLifecycle.php:147`). This is supported, and settlement does not block it.
   2. Read `GracePolicy::snapshot($id)`. Expect `unpaid_renewal_order_id=R`, `awaiting_gateway_settlement=false` and `can_enter_on_hold=true`.
   3. Let the hourly job hold and cancel it, or run phase 1 and 2 by hand after checking the overdue set in §6.1.
   4. Confirm R becomes `cancelled`.
6. **Post-fix check.**
   1. Repeat the same steps. Expect `awaiting_gateway_settlement=true`, both flags false, and the sweeps skipping the subscription.
   2. Then move the fixture order's `_arraysubs_settlement_until` into the past (a test-only meta edit). The flags turn true again and hold and cancel resume.
   3. R's cancellation note should name the transaction still in flight.
7. **Failure and success variants.** These settle quickly in test mode.
   - Account 000222222227 (insufficient funds): `charge.failed` sets R to `failed`, and grace resumes.
   - Account 000123456789: `charge.succeeded` pays R and the subscription is reactivated.

### 6.3 Radar review
**Live route (needs approval).** Card 4000 0000 0000 9235: "The charge has a risk level of elevated. Radar might queue it for review." It goes into review only if the Stripe account has a review rule, for example with Radar for Fraud Teams: "Review if :risk_level: = 'elevated'". WordPress cannot show whether such a rule exists, and adding one changes the Stripe account, so it needs your approval.

**Without a rule.** Drive Woo Stripe's own handlers on a fixture order from WP-CLI. None of these calls Stripe.

- **Synchronous manual review:**
  ```php
  $gw = WC()->payment_gateways()->payment_gateways()['stripe'];
  $gw->process_response(
      (object) ['id' => 'ch_sqa…', 'status' => 'succeeded', 'captured' => true,
                'outcome' => (object) ['type' => 'manual_review']],
      $order
  );
  ```
  Leave out `balance_transaction`, `payment_method` and `payment_method_details` so the call stays local.
- **`review.opened`:**
  ```php
  (new WC_Stripe_Webhook_Handler())->process_review_opened(
      json_decode('{"data":{"object":{"payment_intent":"<order _stripe_intent_id>","reason":"rule"}}}')
  );
  ```
  The order goes on-hold and gets the review note.
- **Approved `review.closed`:** call `process_review_closed()` with `"closed_reason":"approved"`. The order goes to processing and the renewal is processed. This requires `_stripe_charge_captured=yes`.
- **Bank debit:** `process_response()` with `{id: 'py_sqa…', status: 'pending', captured: true}` puts the order on-hold with "Stripe charge awaiting payment".
- **Late success:** `process_webhook_charge_succeeded()` with `{data: {object: {id: <txn>, captured: true, payment_method_details: {type: 'us_bank_account'}}}}`. It completes an on-hold order and ignores a cancelled one.

**Cautions:**
- These synthetic charge ids do not exist at Stripe. Use them only on throwaway fixtures, never run refunds or the reconciler on those orders, and cancel the fixtures afterwards.
- Calling the handler methods directly does not fire `wc_stripe_webhook_received`. `process_webhook($json)` would fire it, but then ArraySubs' listener also runs and may call Stripe, so review that before using it.

### 6.4 Regression scripts
`qa/developer-support-2026-10-18/subscription-order-api-regression.php:163-166` already checks that settlement suppresses `can_enter_on_hold` and `can_cancel_overdue`, using a forced filter. Extend it to use a stamped on-hold order instead.

## Sources outside the code
- Stripe, SEPA Direct Debit (T+6 business days; "wait at least 6 business days"; most failures within 6 business days): https://docs.stripe.com/payments/sepa-debit
- Stripe, ACH Direct Debit (up to 4 business days; T+4, or T+2 with faster settlement): https://docs.stripe.com/payments/ach-direct-debit
- Stripe, Radar reviews (payments in review are usually already processed; ACH and SEPA cannot be reviewed manually; a dispute closes a review; `review.opened` and `review.closed`): https://docs.stripe.com/radar/reviews
- Stripe, testing (4000000000009235 "Radar might queue it for review"; ACH 000000000009 "stays in processing indefinitely", 000123456789, 000222222227; SEPA IBANs with a delayed outcome): https://docs.stripe.com/testing
- Stripe, ACH with the Payment Element (microdeposit codes SM11AA, or 32 and 45; "test transactions are credited instantly"): https://docs.stripe.com/payments/ach-direct-debit/accept-a-payment?payment-ui=elements&api-integration=paymentintents
