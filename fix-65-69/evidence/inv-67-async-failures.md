# Issue #67: Stripe asynchronous renewal failures (investigation)

- Investigated on 2026-10-20, 06:45–07:15 UTC. Read-only: no code or settings edits and no Stripe API calls.
- The only probe was a local, redacted `wp eval-file` read of options and meta. Its output is in the Appendix.
- Paths are relative to `wp-content/plugins/`. Line numbers are from the working tree at about 07:10 UTC.
- **Line numbers may move.** The in-progress #65 work changed `StripeDelegate.php`, `StripeIntentResolutionTrait.php`, `StripeRenewalPaymentTrait.php` and `StripeRequiresActionTrait.php` between 06:44 and 07:05 UTC. The #68 work changed `MollieDelegate.php` and `MollieMandateRebinder.php` at about 07:08 UTC.

## Summary

**The bug is confirmed.**
- `StripeWebhookEventsTrait::handlePaymentFailed()` (`arraysubs/.../Stripe/Traits/StripeWebhookEventsTrait.php:83-179`) only does three things: it logs, it adds notes, and it fires `arraysubs_gateway_payment_failed`.
- It never calls `RenewalProcessor::recordProviderPaymentFailure()`. That shared helper already exists (`RenewalProcessor.php:651-684`) and Mollie already uses it (`MollieDelegate.php:1871`).

**What goes wrong today when a bank debit fails days later:**
- None of these are written:
  - `_last_payment_failure`
  - `_last_payment_failure_reason`
  - `_last_payment_failure_category`
  - `_payment_retry_attempts`
  - `_payment_retry_next_attempt_at`
- No retry is queued.
- `arraysubs_renewal_payment_failed` never fires. Integrations, FluentCRM and the Pro store-credit reversal therefore never run.
- The admin "Retry Payment" button stays hidden (`SubscriptionController.php:993` needs `_last_payment_failure`).
- Pro Audits → Renewal Failures does not list the subscription (`RenewalFailureController.php:131-163`).
- The failure email's "Reason:" line is empty, or stale, because the category was never written (`PaymentFailedEmail.php:81`).

**Other gaps the fix has to cover.** Calling the helper alone is not enough.

1. **WooCommerce Stripe marks the failed charge as if it were a payment.**
   - On `payment_intent.processing`, WooCommerce Stripe stores the bank-debit charge id (`py_…`/`ch_…`) as the order transaction id and puts the order on-hold (`woocommerce-gateway-stripe/includes/class-wc-stripe-webhook-handler.php:1367-1374`).
   - `RenewalProcessor::renewalOrderIsSettled()` (`:1295-1306`) treats any transaction id as settled.
   - Consequences after a recorded async failure:
     - `getPendingRenewalOrder()` skips the failed order.
     - The queued retry creates a **second renewal order** for the cycle (`processUnderLock()` `:357-402`).
     - The old failed order stays payable on the Pay page, so a double payment is possible.
     - `manualRetry()` answers "No failed renewal order to retry" (`:555-563`).
   - The failed charge id has to be compare-deleted.
2. **`_arraysubs_pending_deadline` survives the terminal failure.** `filterAwaitingGatewaySettlement()` (`PaymentHooksRenewalsTrait.php:210-283`) keeps the grace sweeps paused until the deadline passes.
3. **The Stripe `processing` deadline is a flat 2 days for every method** (`StripeIntentResolutionTrait.php:1505-1509`, called from `StripeRenewalPaymentTrait.php:399` and `StripeIntentResolutionTrait.php:213`).
   - Stripe documents SEPA as taking up to 14 business days, against Mollie's 21-day SEPA window (`MollieDelegate.php:90`).
   - When the deadline expires, the reconcile sweep fails a still-processing debit (`PaymentHooksReconciliationTrait.php:377-421`). It sends untracked failure emails and records no failure and no retry.
   - When the real webhook arrives later, it adds a second email.
4. **Nothing marks a failure as "already recorded for this PaymentIntent".**
   - A synchronous card decline is recorded by `process()`, and its `payment_intent.payment_failed` webhook still arrives.
   - Once the webhook also records, the attempt would be **counted twice** unless the attempt is deduplicated.
5. **The official webhook route cannot rely on redelivery.**
   - WooCommerce Stripe always answers 200 (`class-wc-stripe-webhook-handler.php:238-241`).
   - On lock contention, the event is lost unless a durable retry is queued.
   - Today's metadata retry excludes `EVENT_PAYMENT_FAILED` (`StripeWebhooksTrait.php:354-368`, `:636-717`).
6. **Bank-debit failure codes are not classified.** `account_closed`, `no_account`, `debit_not_authorized`, `invalid_account_number` and `bank_account_restricted` are missing from `arraysubs_classify_payment_failure_reason()` (`gateway-helpers.php:558-662`). They become `unknown`, so they are retried, get no reason note and get no "Reason:" line in the email.

**Probable separate bug (also a test blocker).**
- When `_gateway_mandate_id` is cached, the renewal PaymentIntent sends `payment_method_options[<type>][mandate]` (`StripeRenewalPaymentTrait.php:274-278`).
- That is not a documented Stripe PaymentIntent parameter. Only top-level `mandate` and `payment_method_options.<type>.mandate_options` are documented.
- Stripe normally rejects unknown parameters (HTTP 400 `parameter_unknown`).
- WooCommerce Stripe stores `_stripe_mandate_id` for SetupIntent checkouts such as trials (`abstract-wc-stripe-payment-gateway.php:1902-1906`), and ArraySubs caches it (`StripeOrderContextBridge.php:296`). Real SEPA or ACH renewals would therefore probably fail synchronously.
- **This was not verified** because no Stripe API calls were allowed.

**Testing.**
- SEPA cannot run on this store. The store is USD, renewal orders are created in the store currency, and `sepa_debit` accepts EUR only.
- Use ACH Direct Debit (`us_bank_account`, USD). The account is a US account.
- The PaymentMethod and its mandate have to be created by a QA script (a SetupIntent plus test microdeposits) and then bound with meta writes. No customer-facing flow can save a bank debit: checkout offers only `card` and `link`.
- **Possible blocker:** the connected test account may not have the ACH capability. Enabling it is a Stripe Dashboard change and needs the user's approval. Section 6 covers the test plan.

---

## 1. Synchronous decline path (today)

### Call chain

1. `RecurringBilling/Services/Hooks.php:1200-1231` `processRenewal()` acquires the lock `HOOK_PROCESS_RENEWAL [sid]`.
2. `RenewalProcessor::process()` (`:303-314`) acquires `HOOK_SUBSCRIPTION_MUTATION [sid]`, then calls `processUnderLock()` (`:322-523`).
   - The subscription must be active, trial or on-hold (`:337-340`).
   - It must not be waiting for cancellation (`:348-350`) and must be due (`:353-355`).
   - Pending order and settling guards: `:357-416`.
   - Pre-retry verification when attempts > 0: `:422-480`.
   - It fires `arraysubs_renewal_payment_attempt` (`:485`).
3. `PaymentProcessor::processPayment()` applies the filter `arraysubs_process_automatic_renewal_payment`.
4. `PaymentHooksRenewalsTrait::processAutomaticRenewalPayment()` (`:297-357`) calls `StripeDelegate::processRenewalPayment()`.
5. `StripeRenewalPaymentTrait::processRenewalPayment()` (`:25-59`) takes the per-order charge lock (`ChargeAttemptGuard::acquireChargeLock`, option `arraysubs_charge_lock_<order>`, TTL 9000 s `StripeDelegate.php:125`, **not re-entrant** `ChargeAttemptGuard.php:225`).
6. `processRenewalPaymentWhileLocked()` (`:64-440`):
   - Resolves any stored PaymentIntent first (`:98-114`) and reconciles before a retry (`:119-173`).
   - Under the GatewayMetaStore MySQL advisory lock (`:216-298`):
     - verifies the payment method;
     - calls `prepareRenewalOrderMeta()`;
     - deletes the deadline (`:283`);
     - calls `ChargeAttemptGuard::beginAttempt()` (`:288`);
     - sends the off-session PaymentIntent create+confirm.
   - On a decline:
     - Wire error with a PaymentIntent: `storeIntentMeta`, then `closeAttempt(pi)` (`:310-319`).
     - Authentication required: goes to the requires-action path (`:322-328`).
     - Otherwise: `buildFailure(stripeFailureCode($error_data), message, $pi_id, …)` (`:343-348`).
   - A PaymentIntent that comes back already terminal: `buildFailure(..., $intent_id, …)` (`:430-439`).
   - `stripeFailureCode()` is at `StripeApiTrait.php:162-201`: `decline_code` wins over `code`, and `advice_code` steers it.
7. Back in `processAutomaticRenewalPayment()` (`:337-356`):
   - `PaymentLoggingService::logPaymentFailure()` (`PaymentLoggingService.php:485-…`) writes the "[Gateway: stripe] Payment failed — code: msg" private subscription note (deduplicated within 60 s) and an order note.
   - It returns `failedAutomaticRenewalResult()` (`:451-465`), which keeps `transaction_id`, the PaymentIntent id.
8. `processUnderLock()` fires `arraysubs_renewal_payment_attempt_result(..., 'failed')` (`:510`), then calls `handlePaymentAttemptFailure($sid, $order, message, error_code, transaction_id)` (`:516-522`).

### `RenewalProcessor::handlePaymentAttemptFailure()` (`:783-847`)

- **Meta written:**
  - `_last_payment_failure` = `current_time('mysql', true)` (`:791`)
  - `_last_payment_failure_reason` (`:792`)
  - deletes `_renewal_failure_resolved`, `_renewal_failure_resolved_at`, `_renewal_failure_resolved_by` (`:793-795`)
  - `_last_payment_failure_category` (`:803`)
- **Classification:** `arraysubs_classify_payment_failure_reason($code, $message)` (`gateway-helpers.php:558-662`).
- **Order status:** set to `failed` with "Renewal payment failed: %s", unless it is already failed or cancelled (`:805-808`). This triggers the WooCommerce admin "Failed order" email (`woocommerce/includes/emails/class-wc-email-failed-order.php:43-44`).
- **Note:** a private subscription note "Renewal payment failed: <description>. Gateway message: "…"." with event `renewal_payment_failed`, only when the category is not `unknown` (`:812-827`).
- **Retry:** `scheduleNextRetry()` (`:829`).
- **Hooks:**
  - `arraysubs_renewal_payment_failed($sid, $order_id, $msg)` (`:831`). Listeners:
    - `LifecycleDispatcher.php:92,171` — `renewal_failed` plus the grace-entry integration events
    - `FluentCrm/RenewalPaymentFailedTrigger`
    - Pro `CreditLedger.php:62,251` — reverses store credit on the order
    - `AutoNotes` and `EmailManager` — these defer to the gateway hook when `_payment_gateway` is set
  - `arraysubs_gateway_payment_failed($sid, $order_id, $msg, $gateway, $provider_attempt)` (`:833-844`). `$provider_attempt` is the PaymentIntent id.
    - `EmailManager::on_gateway_payment_failed()` (`EmailManager.php:1066-…`) sends the customer `payment_failed` and the admin `admin_payment_failed` emails, **once per (subscription, order, provider attempt)**, using `PaymentLoggingService::paymentFailureEffectKey()` (`:330-363`).
    - `AutoNotes::onGatewayPaymentFailed()` (`AutoNotes.php:333-…`) adds one customer-visible note per attempt (`addPaymentFailureNoteOnce`, `PaymentLoggingService.php:386-…`).

### `RenewalProcessor::scheduleNextRetry()` (`:856-983`)

- **Config:** `arraysubs_get_payment_retry_config()` (`gateway-helpers.php:246-306`). For Stripe the store ladder is published by `StripeRenewalPaymentTrait::getRetryConfig()` and `publishRetryConfigForSubscription()` (`:514-537`).
- **Order of checks:**
  1. Always deletes `_payment_retry_next_attempt_at` first (`:863`).
  2. **Retries disabled:** private note, either "…turned off in ArraySubs → Settings → General → Payment Retries…" or, for gateways that own the billing clock, "…gateway handles retries…". Returns (`:865-882`).
  3. **Attempts ≥ max:** note "Reached the configured retry limit…", plus `arraysubs_payment_retry_exhausted`. Returns (`:884-899`).
  4. **Stop on hard decline** (`stop_on_hard_decline` and `arraysubs_is_hard_payment_decline()`, `gateway-helpers.php:369-392`):
     - Hard categories: `expired_card`, `invalid_card`, `incorrect_cvc`, `card_declined`, `authentication_required`, `missing_payment_method`.
     - Adds the note "No automatic retry was scheduled because this failure will not resolve on its own…" and returns (`:904-927`).
  5. **Otherwise:**
     - Interval = `intervals[attempts]`, or the last entry, with a minimum of 1 h (`:929-939`).
     - An earlier pending `HOOK_PROCESS_RENEWAL` is reused if it is sooner (`:943-951`). If not, a new one is scheduled with `scheduleSingle(HOOK_PROCESS_RENEWAL, at, [sid], GROUP_RENEWALS)` (`:952-959`).
     - Writes `_payment_retry_attempts` (+1), `_payment_retry_last_attempt_at` and `_payment_retry_next_attempt_at` (`:961-963`).
     - Fires `arraysubs_payment_retry_scheduled` (`:964-966`).
     - Adds the note "Scheduled retry N of M at <local time>…" (`:968-982`).
- **What the UI shows:** the next retry is derived by `arraysubs_get_payment_retry_next_attempt_at()` (`gateway-helpers.php:416-445`). It needs attempts > 0, `_last_payment_failure` set, and a pending action.
  - Consumers: `SubscriptionController.php:522,602`, `GracePolicy.php:46,113`, and Pro `RenewalFailureController.php:324`.

### Locks on this path

```
HOOK_PROCESS_RENEWAL [sid]
  → HOOK_SUBSCRIPTION_MUTATION [sid]   request-local re-entrant (ActionScheduler.php:1165-1172), TTL 600 s (:506)
    → HOOK_RENEWAL_INVOICE_CREATION [sid]   only when creating the order
    → Stripe charge lock [order]   not re-entrant
      → GatewayMetaStore advisory lock [sid]   re-entrant, also used by note and email claims
```

- `manualRetry()` (`:534-631`) takes `HOOK_PROCESS_RENEWAL`, then `process()`.
- Paying an order (`payment_complete`) is fenced by `OrderIntegration::guardRenewalPaymentCompletion()` (`OrderIntegration.php:188-256`). It takes `HOOK_SUBSCRIPTION_MUTATION [sid]`, then `HOOK_GATEWAY_ORDER_PAYMENT [order]`, and **throws** if either is busy.

---

## 2. Asynchronous path today (processing → failed)

### At charge time

- `StripeRenewalPaymentTrait.php:393-407`: the PaymentIntent status is `processing`.
  - `GatewayMetaStore::persist($sid, ['_last_gateway_transaction_id' => pi])`
  - `markOrderPendingDeadline($order)` writes `_arraysubs_pending_deadline` = now + 2 days (`StripeIntentResolutionTrait.php:1505-1509`).
  - Returns `success: true, status: pending`.
- Before that, `storeIntentMeta()` (`:881-896`) and `closeAttempt($order, pi)` (`:353-356`) set:
  - order meta `_stripe_intent_id` = pi
  - `_arraysubs_pending_transaction_id` = pi
  - order `_last_gateway_transaction_id` = pi
  - attempt result = pi
- `processAutomaticRenewalPayment()` turns this into "pending" (`successfulAutomaticRenewalResult`, `:378-409`).
- `processUnderLock()` fires `…_attempt_result('pending')`, then calls `handleManualPaymentPending()` (`:714-771`). That only links the order and sets `_pending_renewal_order_id`. **No failure meta and no counter changes.**

### WooCommerce Stripe's `payment_intent.processing` (official endpoint)

- `process_payment_intent()` (`class-wc-stripe-webhook-handler.php:1320-1481`) finds the order through `metadata.order_id` (`get_order_from_intent()` `:2750-2793`). ArraySubs sets `metadata.order_id`.
- The status must be pending or failed (`:1338-1347`). It then:
  - sets the **transaction id** to the latest charge (`py_…` for bank debits);
  - moves the order to **on-hold** with the note "Stripe charge awaiting payment: py_…" (`:1367-1374`).
- ArraySubs does not map `payment_intent.processing` (`StripeWebhooksTrait.php:469-485`), so its own handler ignores the event.
- WooCommerce's customer on-hold email is suppressed for renewal orders (`EmailManager.php:307-317, 372`).

### While the payment is processing

- `arraysubs_is_awaiting_gateway_settlement()` returns true because the deadline is in the future. The grace sweeps wait.
- `hasSettlingRenewalPayment()` (`RenewalProcessor.php:1213-1251`) returns true (pending or on-hold with a transaction id). `process()` and `createRenewalInvoice()` refuse a second charge or invoice.
- If a retry or B1 attempt runs anyway, `resolveExistingIntent()` sees `processing`, returns pending, and moves the deadline forward 2 days from now (`StripeIntentResolutionTrait.php:212-224`).

### Failure webhooks

Stripe sends both `payment_intent.payment_failed` and `charge.failed`, in either order.

**WooCommerce Stripe (official endpoint):**
- `charge.failed` goes to `process_webhook_charge_failed()` (`:852-883`):
  - It finds the order by the charge id = transaction id (`WC_Stripe_Helper::get_order_by_charge_id()`, `class-wc-stripe-helper.php:851-875`).
  - If the order is not already failed, it sets **failed** with "This payment failed to clear." That transition sends WooCommerce's admin "Failed order" email.
  - It fires `wc_gateway_stripe_process_webhook_payment_error`.
  - ArraySubs does not map `charge.failed` (not in `StripeDelegate.php:153-174` or `StripeWebhooksTrait.php:469-485`) and ignores it.
- `payment_intent.payment_failed` goes to `process_payment_intent()`:
  - **If the order is on-hold** (the usual case after processing), WooCommerce Stripe returns at `:1338-1347` with **no change**. `wc_stripe_webhook_received` still fires, with `$resolved_order = null` (`:2716`, `:2725-2742`).
  - **If the order is pending or failed** (the processing webhook was missed, or `charge.failed` came first), the default branch runs (`:1450-1477`):
    - `update_status(failed, "Stripe SCA authentication failed. Reason: <msg>")`. The wording is misleading for a bank debit. Same-status updates add no note (`woocommerce/includes/class-wc-order.php:319-339`).
    - `send_failed_order_email()` (`abstract-wc-stripe-payment-gateway.php:748-770`) explicitly triggers the admin Failed-order email when no pending→failed or on-hold→failed transition already did.
  - WooCommerce Stripe always answers Stripe with 200 (`:238-241`).

**ArraySubs handler** (`StripeDelegate.php:195`, `wc_stripe_webhook_received` priority 30, after WooCommerce Stripe):
- `handleOfficialStripeWebhook()` (`StripeWebhooksTrait.php:273-386`):
  - Claims the event with `INSERT IGNORE` into `wp_arraysubs_webhook_events`, keyed on (`arraysubs_stripe`, event id) (`:220-245`). The ArraySubs REST endpoint `wp-json/arraysubs/v1/webhooks/arraysubs_stripe` (`WebhookRouter.php:81-96`) uses the same key, so one delivery processes.
  - Resolves entities from metadata `order_id`/`subscription_id`, the canonical order link, and the Stripe customer compared with `_stripe_customer_id`/`_gateway_customer_id` (`:61-160`).
  - Dispatches to `handlePaymentFailed()` (`:504-561`).
- `handlePaymentFailed()` (`StripeWebhookEventsTrait.php:83-179`):
  - Classifies with `stripeFailureCode(last_payment_error, 'payment_failed')` (`:88-92`).
  - Branches only for authentication-required (`:105-137`) and for clearing a terminal authentication action (`:139-166`).
  - Logs with `PaymentLoggingService::logPaymentFailure()` (`:168`), which adds the private subscription note and the order note.
  - Fires `arraysubs_gateway_payment_failed(..., 'stripe', pi)` (`:169-176`). The customer and admin ArraySubs failure emails go out once per PaymentIntent.
  - Returns success.
  - **There is no `recordProviderPaymentFailure()`, no category, no counter, no retry, no `arraysubs_renewal_payment_failed`, and no cleanup of the deadline or transaction id.**

**Redelivery:**
- The claim table deduplicates per event id across both routes.
- A failed handler releases the claim:
  - REST route: returns HTTP 500 and Stripe redelivers (`WebhookRouter.php:171-180`).
  - Official route: no redelivery. Only the metadata event types listed at `StripeWebhooksTrait.php:354-362` get the `HOOK_GATEWAY_META_RETRY` repair, and `EVENT_PAYMENT_FAILED` is not one of them.

**ArraySubs listeners on order status `failed`:**
- `OrderIntegration::onOrderStatusChanged()` (`:880-940`) adds the global audit note "Order #X status changed from … to Failed."
- `handleOrderFailure()` (`:2255-2300`) returns early for renewal orders and leaves the subscription status alone.
- Nothing in Pro listens to `failed`. Nothing interferes.

### Email deduplication gap

`EmailManager::dedupe_woocommerce_stripe_renewal_failed_order_email()` (`EmailManager.php:402-…`) sends WooCommerce's admin Failed-order email once per Stripe attempt, but only when `PaymentLoggingService::resolveLegacyStripeFailureAttempt()` can prove the attempt. That check requires an **empty transaction id and an empty `_stripe_charge_id`** (`PaymentLoggingService.php:295-296`).

After the processing webhook has stored `py_…`, the deduplication fails open:
- `charge.failed`'s transition sends one WooCommerce admin email.
- The later `payment_intent.payment_failed` triggers `send_failed_order_email()` explicitly, which sends a second.
- The ArraySubs customer email is still sent only once.

### Result today

- The order ends failed, with a stray `py_…` transaction id and a 2-day deadline still set. The grace sweeps stay paused until that deadline passes.
- The subscription has no failure state and no retry queued.
- The admin cannot retry: the button is hidden, and `manualRetry()` cannot find the order.
- The customer can still pay on the Pay page. Otherwise the subscription drifts: on-hold at due + 3 d, cancelled after 7 more days (staging grace 3/7).

---

## 3. Processing → succeeded, and deadline expiry with no webhook

### Success

1. WooCommerce Stripe `charge.succeeded` (`:752-842`):
   - Finds the on-hold order by charge id, and the charge is captured.
   - Calls `payment_complete($charge->id)`, guarded by `guardRenewalPaymentCompletion()`.
   - Notes "Stripe charge complete (Charge ID: py_…) (via webhook)".
2. ArraySubs' paid fan-out (`OrderIntegration.php:1640-1700`):
   - Sets `_last_payment_date`.
   - Clears `_pending_renewal_order_id`.
   - Calls **`arraysubs_reset_payment_retry_attempts()`** (`:1680-1682`).
   - Advances `_next_payment_date`, reschedules, reactivates.
3. The ArraySubs handler then runs for `charge.succeeded` (it is in the official map): `handlePaymentSucceeded()` (`StripeWebhookEventsTrait.php:23-81`).
   - Calls `capturePaymentContext`, `clearRequiresActionContext` and `retireAbandonedRenewalAttemptIntent`.
   - Adds the once-only note "Stripe payment confirmed via official webhook…".
   - Fires `arraysubs_gateway_payment_succeeded`.
4. `payment_intent.succeeded` is deferred by WooCommerce Stripe (`:1389-1447`, `process_deferred_webhook()` `:1661-…`).
   - It skips orders that are already paid.
   - It completes orders that are still pending or failed, for example when `payment_intent.processing` was missed and the order was never on-hold.
   - ArraySubs re-handles it through `handleDeferredOfficialStripeWebhook()` (`StripeWebhooksTrait.php:400-413`).
5. If no webhook ever arrives, the 6-hourly `HOOK_GATEWAY_RECONCILE` sweep (`PaymentHooksScheduledJobsTrait.php:280-284`; `PaymentHooksReconciliationTrait.php:242-375`) completes the order from `verifyRecentChargeForOrder()` → `succeeded` → `ProviderPaymentCompletion`. The deadline is deleted on paid orders (`:250-255`).

**Side note, not #67:** `extractChargeIdFromIntent()` (`StripeRenewalPaymentTrait.php:960-…`) accepts only `ch_`. Bank-debit charges are `py_…`, so a bank-debit success reconciled by ArraySubs records the PaymentIntent id as the transaction.

### Deadline expiry with no webhook

`reconcilePendingRenewalOrder()` (`PaymentHooksReconciliationTrait.php:377-421`) handles it.

**Before the deadline:**
- `verifyRecentChargeForOrder()` (`StripeRenewalPaymentTrait.php:539-611`) answers inconclusive for a PaymentIntent that is still `processing` (`:575-585`).
- For a terminal failed PaymentIntent it answers "conclusive, not charged". Either way the sweep only refreshes the order date and keeps waiting until the deadline, even when the PaymentIntent has provably failed.

**At expiry:**
1. Deletes the deadline.
2. If the order is already `failed`, returns. The comment says "Dunning already recorded this failure". That is false for Stripe orders that WooCommerce Stripe itself marked failed.
3. Otherwise:
   - `logPaymentFailure('processing_error', 'The gateway never confirmed this renewal payment.')`
   - `update_status('failed', 'Renewal payment was never confirmed by the gateway within the settlement window.')`
   - Fires `arraysubs_gateway_payment_failed` with **4 arguments and no provider attempt** (`:421`).
     - EmailManager and AutoNotes try `resolveLegacyStripeFailureAttempt()`.
     - That fails while a `py_` transaction id is present, so the emails and note are sent untracked.
4. Records no failure meta, no retry and no `arraysubs_renewal_payment_failed`.

**Effect with the flat 2-day deadline:** a SEPA debit that is still processing (up to about 14 business days) is reported as failed after 2 days.
- If it later succeeds, the deferred `payment_intent.succeeded` completes the failed order; failed is an allowed status.
- If it later fails, the webhook adds a second, tracked failure email.

---

## 4. Other gateways, the shared helper, concurrency, and double counting

### Other gateways

- **Mollie already records asynchronous failures.** `MollieDelegate::handlePaymentFailed()` (`MollieDelegate.php:1817-1895`; the file shifted at 07:08 UTC because of the parallel #68 edits, and the logic is unchanged):
  - Acts only on the order's current payment (`:1838-1850`).
  - Takes `HOOK_SUBSCRIPTION_MUTATION` (`:1855-1857`). When busy it returns failure and Mollie redelivers.
  - `clearTerminalPayment()` (`:887-895`) deletes the payment id, `_arraysubs_pending_transaction_id` and the deadline, and bumps the sequence.
  - Logs, then calls `(new RenewalProcessor())->recordProviderPaymentFailure($sid, $order, $message, $code, $payment_id)` (`:1871`).
  - A `busy` result means "retry" (`:1874-1876`). Any other non-recorded result marks the order failed and fires the 5-argument hook (`:1878-1887`).
  - Mollie's SEPA deadline is 21 days (`:90`).
- **PayPal and Paddle** record failures on the order and fire the hook (`PayPalWebhooksTrait.php:286-339`; `PaddlePaymentProcessingTrait.php:~690-760`, `PaddleWebhooksTrait.php:1034`).
  - By design they do not use the ArraySubs retry ladder: the provider owns the billing clock and its retries (`BillingClockOwnership`; `RenewalProcessor.php:869-876`).
  - Aside: Paddle writes `_last_payment_failure` as an integer `time()` (`PaddlePaymentProcessingTrait.php:732`). Core uses a MySQL UTC datetime, and `GracePolicy::timestamp()` returns null for the integer.

### The reusable helper: `RenewalProcessor::recordProviderPaymentFailure()` (`:651-684`)

- **What it does:**
  - Takes `HOOK_SUBSCRIPTION_MUTATION [sid]`, which is re-entrant within a request.
  - Reloads the order.
  - Requires: not paid, a renewal order, `arraysubs_order_belongs_to_subscription`, not waiting for cancellation, and status active, trial or on-hold.
  - Then runs exactly the synchronous treatment, `handlePaymentAttemptFailure()`.
  - Returns `recorded`, `busy` or `not_applicable`. Reuse it rather than duplicating logic.
- **What it is missing for Stripe:**
  1. An "already recorded for this provider attempt" check.
  2. An order-status guard. A cancelled or refunded order would still get a retry, and that retry would then mint a new order.
  3. A check that `_pending_renewal_order_id` equals the order. Without it, a failure of an older order could be counted against a newer invoice.

### Concurrency

| Actor | Locks held | Interaction with an async-failure recorder holding `HOOK_SUBSCRIPTION_MUTATION [sid]` |
|---|---|---|
| Scheduled renewal or retry (`process()`) and manual retry | `HOOK_PROCESS_RENEWAL`, `HOOK_SUBSCRIPTION_MUTATION`, charge lock, GatewayMetaStore | Fully serialized. The webhook gets `busy`. While the PaymentIntent is processing, `process()` returns quickly because `hasSettlingRenewalPayment()` is true. |
| Customer Pay page | none while creating the PaymentIntent; `payment_complete` takes `HOOK_SUBSCRIPTION_MUTATION`, then `HOOK_GATEWAY_ORDER_PAYMENT` (`OrderIntegration.php:188-256`) | Serialized at completion. A completion that collides with the recorder **throws**, an existing design choice, so keep the lock hold short: retrieve the PaymentIntent from Stripe *before* taking the lock. The recorder must re-read `is_paid()` inside the lock. |
| Reconcile sweep (deadline) | only the global `HOOK_GATEWAY_RECONCILE` | **Not serialized** with the webhook today. Both can mark the order failed and send emails. Route the sweep's expiry through the same recorder, under the subscription lock. |
| Both webhook routes or redelivery | event claim row | Deduplicated per event id. The metadata retry worker does not claim before running (`PaymentHooksGatewayMetaTrait.php:171-226`), so it can overlap a REST redelivery. A per-PaymentIntent marker checked inside the subscription lock covers this. |
| WooCommerce Stripe's own handlers in the same request | WooCommerce Stripe's order payment lock, released before `wc_stripe_webhook_received` | Run first. Re-read the order inside the ArraySubs lock. |

### Double counting and how to avoid it

| Case | Today | Required behaviour |
|---|---|---|
| Synchronous decline (`process()` records with provider attempt = PaymentIntent) followed by its `payment_intent.payment_failed` webhook | Webhook only logs and re-fires the hook (emails deduplicated by PaymentIntent) | Write a marker in `handlePaymentAttemptFailure()` and return `duplicate` from `recordProviderPaymentFailure()`. The webhook only logs. |
| Same event delivered to both endpoints, or redelivered | Event claim | Event claim, plus the PaymentIntent marker as backstop |
| `charge.failed` and `payment_intent.payment_failed` | ArraySubs maps only the PaymentIntent event | Keep `charge.failed` unmapped. If it is ever mapped, key it on `charge.payment_intent` so the marker deduplicates. |
| Deadline expiry, then a late real failure | Two failure emails (untracked, then tracked) | Extend the deadline while Stripe says `processing`. Expiry and proven-terminal states go through the same recorder with the stored PaymentIntent. The late webhook then returns `duplicate`. |
| Late event for a superseded attempt (a newer `closeAttempt()` result) | Notes and emails | `superseded` → log only |
| Customer Pay-page PaymentIntent fails (WooCommerce Stripe metadata, no `renewal`/`subscription_id`) | Notes and emails | Unchanged. It is not an automatic attempt, so it must not consume the ladder. |
| `invoice.payment_failed` (also mapped to `EVENT_PAYMENT_FAILED`) | Notes and emails | Unchanged. It is not a `payment_intent` object. |

---

## 5. Recommended design

### Core (`arraysubs`), shared contract in `RenewalProcessor`

1. **In `handlePaymentAttemptFailure()` (`:783-847`):** when `$provider_attempt !== ''`, write the order meta `_arraysubs_recorded_failure_attempt` = the attempt id. Write it before `update_status()`. Every caller holds `HOOK_SUBSCRIPTION_MUTATION`, so the check and the write are serialized.
2. **In `recordProviderPaymentFailure()` (`:651-684`)**, inside the lock and after the reload:
   - If `$provider_attempt !== ''` and it matches the marker, return **`duplicate`** with no side effects.
   - Return `not_applicable` when any of these holds:
     - the order status is not pending, on-hold or failed;
     - `_pending_renewal_order_id` is set and names another order;
     - `arraysubs_is_subscription_externally_billed()`.
   - Update Mollie's caller (`MollieDelegate.php:1878-1887`) to treat `duplicate` as already handled, so it does not re-fire the hook.

### Stripe adapter

Add a new private method, for example `recordAsynchronousRenewalFailure()`. A new `StripeAsyncFailureTrait` keeps responsibilities split; all touched files stay well under 3000 lines. Call it from `handlePaymentFailed()` right after the authentication-required branch and the terminal-action cleanup (after `:166`).

**Step 1 — Pre-lock eligibility (local only).** Otherwise return `not_applicable` and keep today's behaviour.
- `$order` is a `WC_Order`, `_is_renewal_order = yes`, payment method `stripe`, `ChargeAttemptGuard::META_ATTEMPT_GATEWAY = stripe`.
- The payload object is `payment_intent` with a `pi_` id.
- Metadata: `renewal` is `true`, `order_id` = the order, `subscription_id` = the subscription, `site_url` = `home_url()`.
- `findSubscriptionIdsForOrder(order)` is exactly `[sid]`.
- The subscription `_payment_gateway` is `stripe`.
- Not `GatewayMetaStore::isDetached()`.

**Step 2 — Retrieve the PaymentIntent from Stripe** (`stripeRetrieve('payment_intents/…')`) **before** taking any lock. Event snapshots can be stale or out of order, as `handlePaymentRequiresAction()` already handles at `:216-228`.
- A `WP_Error` result → `retry_later`.
- Require all of:
  - identity matches;
  - `isProviderProvenTerminalZeroMoneyIntent()` (`StripeIntentResolutionTrait.php:646-…`);
  - not `isAuthenticationRequiredPaymentIntent()`;
  - customer = the order's `_gateway_customer_id`;
  - amount and currency = the order.
- Otherwise return `stale` (log only).
- Take the code and message from the retrieved `last_payment_error` through `stripeFailureCode()`.

**Step 3 — Acquire `HOOK_SUBSCRIPTION_MUTATION [sid]`.** If it is busy, return `retry_later`. Do not take the non-re-entrant Stripe charge lock. The subscription lock already excludes `process()` and `payment_complete`.

**Step 4 — Inside the lock,** reload the order (`refreshLockedRenewalOrder()`), then check in this order:
- Paid → `not_applicable`.
- `ChargeAttemptGuard::requireReconcileBeforeRetry()`, meaning an attempt is in flight → `retry_later`.
- `ChargeAttemptGuard::attemptResult(order) !== pi` → `superseded`.
- Marker = pi → `duplicate`.

**Step 5 — Compare-delete stale settlement state, then save:**
- `_arraysubs_pending_deadline`: delete.
- Transaction id: clear only if it equals the retrieved PaymentIntent's `latest_charge`, the failed `py_`/`ch_` stored by WooCommerce Stripe. Same for `_stripe_charge_id` if equal.
- **Leave alone:** `_stripe_intent_id`, `_arraysubs_pending_transaction_id`, `_last_gateway_transaction_id` and the attempt markers.
  - The next attempt's `resolveExistingIntent()` already retires a terminal PaymentIntent safely (`StripeIntentResolutionTrait.php:326-367`, `clearTerminalIntentForReplacementWhileLocked()` `:375-…`).
  - `resolveLegacyStripeFailureAttempt()` needs these values to deduplicate WooCommerce's admin email.
- Clearing the transaction id before the status change is what lets that deduplication match.

**Step 6 — Record.** Call `recordProviderPaymentFailure($sid, $order, $message, $code, $pi)`. It is re-entrant. The result:
- `recorded`: done. It already wrote the meta and category, ran the ladder with its stop-on-hard and retries-disabled notes, failed the order and fired both hooks.
- `duplicate`: done.
- `not_applicable` (waiting for cancellation, inactive subscription, and so on): Mollie parity. Mark the order failed if it is unpaid and not cancelled, then fire the 5-argument hook once.

**Step 7 — Release the lock.**

### What `handlePaymentFailed()` does with each result

| Result | Action |
|---|---|
| `recorded` | One `logPaymentFailure()` call, before or after recording; it is the async counterpart of `PaymentHooksRenewalsTrait.php:343-349`. Do **not** fire `arraysubs_gateway_payment_failed` again (move `:169-176` into the fallback). Return success. |
| `duplicate`, `superseded`, `stale` | `logWebhook()` at info level only. No notes, no hook. Return success. |
| `retry_later` | Return `success: false`. |
| `not_applicable` | Today's behaviour, unchanged. |

**For `retry_later`:**
- REST route: HTTP 500, and Stripe redelivers.
- Official route: add `self::EVENT_PAYMENT_FAILED` to the list at `StripeWebhooksTrait.php:354-362`, and add a `EVENT_PAYMENT_FAILED` branch to `retryOfficialMetadataEvent()` (`:636-717`). That branch re-dispatches `handlePaymentFailed()` with the retrieved, immutable event payload.
- Consider more than 3 attempts in `scheduleGatewayMetaRetry()` (`PaymentHooksGatewayMetaTrait.php:239-262`, backoff 60/120/240 s) for this event type, because the lifecycle lock TTL is 600 s.

### Deadline and sweep alignment (part of this fix, or an immediate follow-up)

1. **Per-method processing deadline.** `markOrderPendingDeadline()` currently uses a flat 2 days.

   | Method | Suggested window |
   |---|---|
   | `sepa_debit` | 21 days (Mollie parity) |
   | `us_bank_account` | about 7–10 days |
   | `bacs_debit` | about 10 days |
   | `au_becs_debit` | about 7 days |
   | `acss_debit` | about 10 days |
   | card | 2 days |

   Confirm these against current Stripe documentation. Use the attempt's `_stripe_upe_payment_type`, which `prepareRenewalOrderMeta()` writes (`:854-…`).
2. **`reconcilePendingRenewalOrder()`:**
   - If the stored attempt PaymentIntent is reported `processing` (`$verification['raw']['status']`), extend the deadline. Bound it with a hard maximum counted from `ChargeAttemptGuard::META_ATTEMPT_AT`.
   - If it is provider-proven terminal and failed, call the Stripe recorder now.
   - On a real expiry, call `recordProviderPaymentFailure(..., 'processing_error', $stored_pi)` instead of the 4-argument hook.
   - Replace the "already failed means already recorded" shortcut (`:392-394`) with the marker check.

### Classification (`gateway-helpers.php:558-662`)

- **Add hard bank-debit codes:**
  - `account_closed`
  - `no_account`
  - `invalid_account_number`
  - `bank_account_restricted`
  - `debit_not_authorized`
  - `bank_ownership_changed`

  Map them to an existing hard category, or add one such as `bank_account_invalid` with its description and hard-list entry.
- **Decide soft or hard for:** `generic_could_not_process`, `charge_exceeds_source_limit`, `charge_exceeds_weekly_limit`, `refer_to_customer`.
- **Wording:** consider neutral text for `insufficient_funds`. It currently reads "…on the card" (`:670-697`).

### Optional hardening

- **Duplicate WooCommerce admin email when `charge.failed` is processed first.** Add a signed pre-WooCommerce-Stripe hook on `woocommerce_api_wc_stripe`, following the pattern of `captureOfficialAuthenticationRequiredRenewalWebhook()` (`StripeDelegate.php:197`). For the exact ArraySubs attempt PaymentIntent, it compare-deletes the failed charge's transaction id before WooCommerce Stripe runs. WooCommerce Stripe then cannot find the order, the ArraySubs recorder makes the one failed transition, and the email is deduplicated.
- **Stale `payment_intent.processing` redelivered after the recorded failure.** Through filter `wc_stripe_allowed_payment_processing_statuses`, return `[]` when the order's marker equals that PaymentIntent. Otherwise WooCommerce Stripe would put the order back on-hold with the dead charge id.

### What the user should see after the fix

**Renewal order notes:**
- "Stripe charge awaiting payment: py_…" (from WooCommerce Stripe at processing)
- "[Gateway: stripe] Payment failed — insufficient_funds: …"
- "Renewal payment failed: …"
- "Order status changed from On hold to Failed."

**Subscription notes:**
- "[Gateway: stripe] Payment failed — …" (private)
- "Renewal payment failed: <reason>. Gateway message: "…"." (private)
- One of these, depending on settings and category:
  - "Scheduled retry 1 of 3 at <site-local time>…"
  - the hard-decline note
  - the retries-off note
  - the limit-reached note
- One customer-visible failure note per PaymentIntent
- The global audit note for the order status change

**Emails:**
- ArraySubs customer "payment failed" email: **once**, with "Reason: …" and the pay link.
- ArraySubs admin failed-payment email: once.
- WooCommerce admin "Failed order": once when ArraySubs makes the transition. It can be twice only in the `charge.failed`-first ordering unless the optional hardening is added.
- No WooCommerce customer failed-order email; it is suppressed for renewal orders.
- No email at all on a redelivered event, on `charge.failed`, or on a synchronous decline's webhook.

**UI:**
- Subscription detail shows the Retry Payment button and the next retry time.
- `GracePolicy::snapshot()` shows `failure_category` and `next_retry_at`.
- The Pro Renewal Failures screen lists the subscription.

**Changelog:** core only, one `fixed:` line in `arraysubs/changelog.draft.txt`. Pro needs no code change. Pro's Renewal Failures screen and CreditLedger simply start receiving these failures.

---

## 6. Testing on staging in Stripe test mode (no gateway setting changes)

### Staging facts (local read, Appendix)

- Store currency **USD**. WooCommerce Stripe 10.9.0 in test mode, connected through Stripe Connect.
- The Stripe account is US, default currency USD (from the 2026-10-19 `preflight-stripe-r2.json`). It reports `charges_enabled: false`, but test card charges worked in the #56–#64 tests.
- UPE methods at checkout: `card` and `link` only.
- Payment retry settings: enabled, 24/24/24 h, `stop_on_hard_decline` on. Grace 3/7 days.
- Pro 1.2.8 is active.
- No Stripe subscription currently uses a bank debit (91 card, 1 link). No `_gateway_mandate_id` values, and no orders with a pending deadline.
- Webhook endpoints:
  - Official endpoint: subscribed to `charge.failed`, `payment_intent.processing` and `payment_intent.payment_failed`.
  - ArraySubs secondary endpoint: subscribed to `payment_intent.payment_failed` only.
- **The same Stripe test account also delivers to `test1.emran.io`.** WooCommerce Stripe there looks up `metadata.order_id` locally and could fail or hold an unrelated order with the same id. Accept or check this before testing.

### Which payment method to use

- **SEPA: not usable.**
  - `sepa_debit` accepts EUR only (`woocommerce-gateway-stripe/.../class-wc-stripe-upe-payment-method-sepa.php:85`).
  - Renewal orders are created in the store currency. `OrderCreation.php:104-115` uses `wc_create_order()` and overrides the currency only for pending plan switches.
  - The test IBAN `AT861904300235473202` (processing → `requires_payment_method`) would therefore need a EUR fixture order. That means a fixture-data currency hack on the renewal order, not recommended, and a SEPA capability on the US account, which is unknown.
- **ACH Direct Debit (`us_bank_account`, USD): the viable path.**
  - Routing number `110000000`.
  - Account numbers:

    | Account | Outcome |
    |---|---|
    | `000123456789` | succeeds |
    | `000222222227` | fails, insufficient funds |
    | `000111111113` | fails, account closed |
    | `000333333335` | fails, debit not authorized |
    | `000111111116` | fails, no account |

  - Confirm the exact failure codes and the test-mode timing on the first run. Stripe's documentation also lists a number that stays processing indefinitely; check it before relying on it.
  - BACS, BECS and ACSS need GBP, AUD or CAD and UK, AU or CA accounts, so they are not usable here.
- **ArraySubs accepts these types.**
  - `verifyStripePaymentMethodIsReusable()` (`StripePaymentMethodsTrait.php:812-…`) needs the PaymentMethod attached to the bound customer and a type that WooCommerce Stripe marks reusable. The probe confirms `us_bank_account` and `sepa_debit` are reusable, because WooCommerce Stripe registers every UPE class (`class-wc-stripe-upe-payment-gateway.php:231-247`).
  - `filterShouldProcessPayment()` has no type gate.
  - The PaymentIntent create parameters (`StripeRenewalPaymentTrait.php:250-271`) are `payment_method_types=[<bound type>]`, `customer`, `payment_method`, `off_session`, `confirm`, `return_url` and metadata. These are valid for `us_bank_account` provided the PaymentMethod has an active multi-use mandate.
  - **Leave `_gateway_mandate_id` empty** so the probably invalid `payment_method_options[us_bank_account][mandate]` is not sent (`:274-278`). Stripe uses the PaymentMethod's mandate for off-session confirmation.

### What must be bound on the subscription

| Meta | Value |
|---|---|
| `_payment_gateway` | `stripe` |
| `_gateway_customer_id` | the `cus_…` the PaymentMethod is attached to (unchanged from checkout) |
| `_gateway_payment_method_id` | the `pm_…` of `us_bank_account` |
| `_payment_method_type` | `us_bank_account` (the pre-charge verification also repairs it from Stripe) |
| `_gateway_status` | `active` or empty |
| `_gateway_mandate_id` | empty |

Write these with `GatewayMetaStore::persist()` in a QA script. No customer flow can save a bank debit, because UPE on this store offers only `card` and `link`.

### Possible blockers

1. **The connected test account must have the `us_bank_account_ach_payments` capability.**
   - Check with one read-only call in the fix session: `GET /v1/account`, printing only `capabilities.us_bank_account_ach_payments`. Alternatively, let the SetupIntent creation fail.
   - If the capability is missing, enabling ACH in the Stripe Dashboard (test mode) needs the user's approval.
2. **The mandate parameter question above.** If a mandate is ever cached, the first renewal fails with HTTP 400. Check the WooCommerce Stripe log.

### Plan

Follow the #65–#69 rules: register fixtures with `SQA_PHASE=fix-65-69`, run only fixture actions, never print keys, and check Mailpit with `mailpit-agent wait-new`.

**Reproduce the bug first, before the fix:**

1. **Fixture.** Use a new fixture customer and a Stripe subscription bought at checkout with card 4242, as T2 did (`stripe-dev-support-2026-10-18/scripts/create-customers.php` and `create-trial-product.php`).
2. **Create the ACH PaymentMethod and mandate** with a QA script through `WC_Stripe_API::request()` in test mode:
   - `POST setup_intents` with:
     - `customer=<cus>`, `usage=off_session`, `confirm=true`
     - `payment_method_types[]=us_bank_account`
     - `payment_method_data[type]=us_bank_account`
     - `payment_method_data[us_bank_account][routing_number]=110000000`
     - `payment_method_data[us_bank_account][account_number]=000222222227`
     - `payment_method_data[us_bank_account][account_holder_type]=individual`
     - `payment_method_data[billing_details][name]=…`
     - `payment_method_options[us_bank_account][verification_method]=microdeposits`
     - `mandate_data[customer_acceptance][type]=online` (with `ip_address` and `user_agent`) or `offline`
   - Expect `requires_action` with `verify_with_microdeposits`.
   - `POST setup_intents/<id>/verify_microdeposits` with `amounts[]=32&amounts[]=45` (test values; the descriptor code `SM11AA` also works). Expect `succeeded`, with `mandate` and `payment_method`.
   - The resulting `setup_intent.succeeded` and `payment_method.attached` events do not bind anything in ArraySubs (`StripeWebhookEventsTrait.php:359-440`).
3. **Bind** as in the table above, then capture state with `stripe-dev-support-2026-10-18/scripts/fix-sub-state.php`.
4. **Drive the renewal:**
   - Make it due: `_next_payment_date` = now + 90 s, as T2 did.
   - Let the invoice leg create the order.
   - Run the fixture `arraysubs_process_renewal` action.
   - Expect:
     - The PaymentIntent is `processing`.
     - The order goes on-hold with "Stripe charge awaiting payment: py_…" and a `py_` transaction id.
     - `_arraysubs_pending_deadline` = +2 d.
     - `GracePolicy::snapshot()` reports `awaiting_gateway_settlement=true`.
     - No failure meta.
5. **Wait for the failure.** Test-mode ACH transitions are expected within minutes; confirm. Expect `payment_intent.payment_failed` and `charge.failed` on the official endpoint, and `payment_intent.payment_failed` on the secondary one. Check `wp_arraysubs_webhook_events` and the WooCommerce Stripe and ArraySubs logs.
6. **Bug evidence (before the fix):**
   - No `_last_payment_failure`, `_last_payment_failure_category` or `_payment_retry_attempts`.
   - No pending `arraysubs_process_renewal`.
   - The order failed with a `py_` transaction id, and the deadline still set.
   - Failure emails sent, possibly 2× WooCommerce admin "Failed order".
   - No Retry button. Not listed in Renewal Failures.

**After the fix, assert:**

1. **Recorded once:**
   - `_last_payment_failure*` written, category `insufficient_funds`.
   - `_payment_retry_attempts=1`.
   - Exactly one pending `arraysubs_process_renewal` at about +24 h, matching `_payment_retry_next_attempt_at`.
   - The order failed, with the transaction id and deadline cleared and the marker = the PaymentIntent.
   - The notes from §5.
   - Mailpit: exactly 1 customer, 1 ArraySubs admin and at most 1 WooCommerce admin email.
   - Admin detail shows Retry and the next retry time. Pro Renewal Failures lists it.
2. **Redelivery:**
   - Resend the event (Dashboard → Events → Resend, or Stripe CLI `stripe events resend`): no change, because of the claim.
   - Then delete only that event's claim row (QA only) and resend: the result is `duplicate` because of the marker, with no new notes or emails.
3. **Synchronous control:** a renewal on card `4000000000000341` declines synchronously. Its webhook must not increase attempts beyond 1 or send a second email.
4. **Retry on the same order:**
   - Run the queued retry. `resolveExistingIntent()` retires the terminal PaymentIntent and creates PI2 **on the same order**, so no second renewal order appears.
   - Still on the failing account: PI2 fails, "Scheduled retry 2 of 3", attempts=2.
   - Then rebind the success account (`000123456789`). The next retry processes, then succeeds through `charge.succeeded`. The order completes and the retry state resets (`OrderIntegration.php:1680-1682`).
5. **Hard decline:** with `000111111113` or `000333333335`, once the classifier is extended, expect the hard-decline note, no pending action and no `_payment_retry_next_attempt_at`. Before the extension these codes are `unknown` and are retried.
6. **Retries disabled:** this is an ArraySubs setting, not a gateway setting. Toggle it only through `snapshot-settings.php` → change → `restore-settings.php … apply` → `diff-settings.php`. Expect the retries-off note and no action.
7. **Deadline path, if implemented:**
   - Set the fixture order's `_arraysubs_pending_deadline` in the past while the PaymentIntent is processing. Run the fixture `arraysubs_gateway_reconcile`; staging currently has no other deadline orders. Expect an extension, not a failure.
   - With a terminal PaymentIntent and the webhook claim pre-inserted, expect the sweep to record the failure once.
8. **Concurrency spot check:** hold `HOOK_SUBSCRIPTION_MUTATION` for the subscription (`wp eval` with `acquireLock` plus `sleep`) while the failure event arrives. Expect `retry_later`: the claim is released and `HOOK_GATEWAY_META_RETRY` is queued on the official route (or HTTP 500 and redelivery on the REST route), and the failure is then recorded exactly once.

**If ACH cannot be enabled,** the only remaining live option is SEPA with a EUR fixture renewal order (a currency hack on fixture data only). A signed synthetic webhook for an existing processing PaymentIntent can exercise the handler, but the AGENTS.md QA rules do not count that as a real end-to-end test.

---

## Appendix: local probe (redacted, no Stripe API)

- Probe script, scratchpad only: `/tmp/claude-0/-home-server-manager-www-arrayhash-mirror-help-arrayhash-com-public-wp-content-plugins/38333750-918d-41d9-9e98-8f405025965a/scratchpad/inv67-readonly.php`
- Output at 2026-10-20 06:53 UTC:
  - Currency USD.
  - Stripe enabled, test mode, `upe_accepted_payments [card, link]`, capture yes, Optimized Checkout element yes, plugin 10.9.0. WooCommerce 11.0.1.
  - `payment_retry {enabled: true, interval_hours: [24,24,24], stop_on_hard_decline: true}`. Grace 3/7.
  - Pro 1.2.8, core 2.0.9.
  - Reusable: `sepa_debit`, `us_bank_account`, `bacs_debit`, `au_becs_debit`, `acss_debit` and `card` are all true.
  - ArraySubs `payment_failed` and `admin_payment_failed` emails enabled. WooCommerce `failed_order` and `customer_failed_order` enabled; the customer one is suppressed for renewals by ArraySubs.
  - Stripe subscriptions by type: card 91, link 1. Mandates 0. Orders with a pending deadline 0. The webhook events table exists.
