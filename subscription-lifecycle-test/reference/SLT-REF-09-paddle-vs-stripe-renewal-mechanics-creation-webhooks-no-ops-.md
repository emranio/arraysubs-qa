# SLT-REF-09 Paddle vs Stripe renewal mechanics: creation, webhooks, no-ops, SCA path

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-09 — Paddle and Stripe renewal mechanics (reference note)

## PART A — PADDLE (`arraysubs_paddle`)

Code: `arraysubspro/src/Features/AutomaticPayments/Gateways/Paddle/PaddleGateway.php` (91 KB), `PaddleApiClient.php`, `PaddleProductSync.php`, `PaddleBlocksPaymentMethod.php`. Routing: `arraysubspro/src/Features/AutomaticPayments/Services/WebhookRouter.php` + `REST/WebhookController.php`.

### Declared capabilities — `PaddleGateway.php:88-114`

`automatic_payments: true`, `trials: true`, `pause/resume: true`, `cancel_at_period_end(+reversible): true`, `product_sync: true`, `payment_method_update: true`, `card_auto_update: true`, `refunds: true`, `hosted_payment_page: true`, `customer_portal: true`, `mixed_cart: true`, `multiple_subscriptions: true`, `supports_sync: true`.
**False:** `retention_amount_update`, `card_expiry_notice`, `disputes`, **`sca`**, `different_billing_cycles`, **`early_renewal`** (with the reason inline at `:109-112`).

### Subscription creation

Checkout runs through Paddle's own overlay/inline checkout. `prepareCheckoutPayment()` (`:390-466`) ensures the product is synced to Paddle (`ensureProductSynced()` `:2119`), resolves/creates the Paddle customer (`findOrCreatePaddleCustomer()` `:2179`), builds items and a checkout transaction (`buildCheckoutItems()` `:1772`, `createCheckoutTransaction()` `:1948`) and returns checkout data (`buildCheckoutData()` `:2043`). The WooCommerce order stays **pending** until Paddle's webhook confirms — `renderPendingOrderNotice()` / `filterOrderReceivedText()` exist purely to explain that (`:467-506`).

The Paddle subscription id is persisted on the ArraySubs subscription as **`_gateway_paddle_subscription_id`** (`META_PADDLE_SUBSCRIPTION_ID` `:59`, written at `:1330-1334`). Paddle payout totals are stored in `_gateway_paddle_payout_amount` (`META_PADDLE_PAYOUT_AMOUNT` `:66`, `:1336-1345`).

### Renewals are 100% Paddle-driven

`processRenewalPayment()` — `:598-629` — is an **explicit no-op**:

```php
// "Paddle manages billing internally. This method is a no-op — the webhook
//  handler confirms actual charges when Paddle fires transaction.completed." (:590-592)
$paddle_sub_id = get_post_meta($subscription_id, META_PADDLE_SUBSCRIPTION_ID, true);
if (empty($paddle_sub_id)) return ['success'=>false, ...];        // :604-611
$this->addSubscriptionPrivateNote(... 'awaiting automatic charge from Paddle' ...); // :613-621
return ['success'=>true, 'transaction_id'=>'', 'message'=>'Paddle manages billing automatically. Awaiting webhook confirmation.', 'raw'=>[...]];
```

Because `success = true` and no `status` key is set, `PaymentProcessor::normalizeAutomaticResult()` resolves the status to **`pending`** (`arraysubs/src/Features/RecurringBilling/Services/PaymentProcessor.php:135-142`), which `RenewalProcessor::process()` treats as `handleManualPaymentPending()` — a **non-failure** (`RenewalProcessor.php:404-406,552-567`). Consequences:
- **`scheduleNextRetry()` is never reached for Paddle from the local pipeline** — Paddle subscriptions get no ArraySubs retry schedule at all.
- The local `arraysubs_process_renewal` action still runs on schedule; it just creates/keeps a pending order and writes a note.

### Local actions that are effectively no-ops for Paddle

| Action | Behaviour | Line |
|---|---|---|
| `processRenewalPayment()` | no charge, returns `pending` | `:598-629` |
| `setupTrialPaymentMethod()` | returns success with existing meta; "Paddle handles trial periods natively" | `:645-657` |
| `handlePaymentRequiresAction()` | `return ['success'=>true,'message'=>'Paddle handles SCA internally.']` | `:1465-1469` |
| Early renew | blocked — `early_renewal: false` | `:113` |
| Renewal Sync / Flexible Renewal Sync | blocked — `arraysubs_paddle` is hard-coded unsupported at `arraysubs/src/functions/renewal-sync-helpers.php:78-79` |
| Card-expiring email | never fires — `card_expiry_notice: false` (`:99`); `handleCardExpiring()` exists at `:1513` but is only reachable via a mapped event Paddle does not emit here |

### Webhook

- **Endpoint (verified live):** `POST https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/webhooks/arraysubs_paddle` (route registered `arraysubspro/.../REST/WebhookController.php:44-54`, namespace `arraysubs/v1` from `arraysubs/src/Supports/BaseRestController.php:37`; confirmed present in `rest_get_server()->get_routes()`).
- Signature: `verifyWebhookSignature()` `:1151`; parse: `parseWebhook()` `:1186`; entity resolution: `resolveEntityIds()` `:1227`.
- Event map (`:117-127`): `transaction.completed` → payment succeeded; `transaction.payment_failed` → payment failed; `subscription.canceled` → cancelled; `subscription.updated` → payment-method updated; `adjustment.created` → refund created; `subscription.created` / `.paused` / `.resumed` handled specially.

**`handlePaymentSucceeded()` `:1293-1405`:**
- Initial checkout branch (order not yet paid): persists `_paddle_transaction_id` + `_last_gateway_transaction_id`, stores the Paddle subscription id and payout totals, `capturePaymentContext()`, `markOrderPaid()`, emits `arraysubs_gateway_payment_succeeded` (`:1320-1367`).
- Renewal branch: finds the pending renewal order via `PaymentMetaNormalizer::findOrderForSubscription($id,'renewal')`; **if none exists it creates one retroactively** (`createRetroactiveRenewalOrder()` `:1371-1377,2341+`) — this is how Paddle charging ahead of the local cron is reconciled. Then `markOrderPaid()` → `arraysubs_order_paid` → the whole core fan-out, **then** `syncNextPaymentDate()` (`:1379-1384`).
- **Ordering matters:** `markOrderPaid()` runs *first*, so core computes `_next_payment_date` from `_renewal_scheduled_date`; `syncNextPaymentDate()` then **overwrites it** with Paddle's `next_billed_at` (`:2289-2306`). Paddle's date always wins.
- **`syncNextPaymentDate()` does NOT reschedule the Action Scheduler legs** — it only writes the meta (`:2305`). The local invoice/charge actions stay at whatever core queued, so they can be misaligned with the authoritative date until the next hourly sweep.

**`handlePaymentFailed()` `:1415-1455`:** reads `payments[0].error_code` and `payments[0].method_details.error.type`, calls `recordPaymentFailure()` (order → `failed`, notes on order + subscription, `Abstracts/AbstractArraySubsGateway.php:896-910`), sets `_last_payment_failure` to a **Unix timestamp** (`:1435` — note: everywhere else in core this meta is a UTC MySQL string, e.g. `RenewalProcessor.php:580`), and emits `arraysubs_gateway_payment_failed`. Arg order is normalized by `normalizeWebhookActionPayload()` (`Abstracts/AbstractArraySubsGateway.php:934-956`) so the core listeners receive `(sub_id, order_id, message, gateway_slug)` correctly. The note text says *"Grace period will handle status transitions"* — i.e. dunning for Paddle is **entirely the ArraySubs grace sweep** (1 day → on-hold, +3 days → cancelled), with **no retry attempts of ArraySubs' own**.

`syncStatusFromPaddle()` `:2315-2332` maps `active→active, paused→paused, canceled→cancelled, past_due→active, trialing→active` into **`_gateway_status` only** — it does **not** change the WordPress post status.

---

## PART B — STRIPE off-session renewals (one-paragraph summary + the SCA path)

**Summary.** Stripe renewals are charged **site-side** by `StripeDelegate::processRenewalPayment()` (`arraysubspro/src/Features/AutomaticPayments/Gateways/Stripe/StripeDelegate.php:194-420`) using the saved customer + payment method (`_gateway_customer_id`, `_gateway_payment_method_id`, `:200-203`). It first short-circuits on a stored `_stripe_intent_id` for the order rather than minting a second PaymentIntent (`:209-216`), then runs a reconcile-before-retry pass when `ChargeAttemptGuard` shows an open attempt with no recorded transaction — if Stripe conclusively says "already charged" it calls `payment_complete()` without re-charging, and if the answer is inconclusive it returns `pending` and refuses to charge (`:221-264`). Zero-total orders are completed with transaction id `renewal_zero_amount` (`:271-280`). Otherwise it POSTs `payment_intents` with `off_session: 'true'`, `confirm: 'true'`, and metadata `{order_id, subscription_id, renewal:'true', site_url}`, under a per-order charge mutex and a deterministic idempotency key so a lost response replays the same intent inside Stripe's 24 h window (`:282-332`). `succeeded` → `payment_complete($charge_id ?: $intent_id)` + `_last_gateway_transaction_id` (`:374-391`); `processing` → status `pending` with a reconcile deadline (`:394-404`); `requires_action`/`requires_confirmation`, or a `authentication_required` error, → the SCA branch (`:346-348,406-408`); anything else → `unexpected_status` failure (`:410-416`). Retry policy is published as `enabled/3/86400` via `arraysubs_payment_retry_config` (`:118,476-483,510-514`). Stripe declares `early_renewal: true` (`:87`), so it is the only in-scope gateway where early renew and Renewal Sync both work.

### SCA / `requires_action` path

`handleRequiresActionResult()` `:1930-1943` returns `['success'=>false,'status'=>'requires_action','requires_action'=>true,'error_code'=>'requires_action','transaction_id'=>$intent_id]`.

`storeRequiresActionContext()` `:1953-1978`:
- builds `action_url = add_query_arg('wc-stripe-confirmation', 1, $order->get_checkout_payment_url(false))` (`:1956`)
- order meta: `_arraysubs_payment_action_intent_id`, `_arraysubs_payment_action_required_at`, `_arraysubs_payment_action_url` (`:1962-1965`)
- subscription meta: `_arraysubs_payment_action_url`, `_arraysubs_payment_action_intent_id` (`:1967-1968`)
- fires **`do_action('arraysubs_renewal_requires_verification', $sub_id, $order_id, $intent_id, 'stripe')`** (`:1977`)

`EmailManager::on_renewal_requires_verification()` (`EmailManager.php:97,184-205`) gates on `arraysubs_is_email_enabled('renewal_requires_verification')` (global helper, key absent from defaults ⇒ **enabled**), recomputes the action URL from the order, and sends **`RenewalRequiresVerificationEmail`** (`customer-renewal-requires-verification.php`, subject `[{site_title}] Verify your subscription renewal #{subscription_id}`).

**Critically, `requires_action` is NOT a failure in the core pipeline.** `RenewalProcessor::process()` routes `requires_action` to `handleManualPaymentPending()` (`RenewalProcessor.php:404-406,552-567`), which merely records the order, sets `_pending_renewal_order_id`, adds an order note, and returns **true**. Therefore for the 3DS card `4000 0027 6000 3184` on an off-session renewal:
- **no** `payment_failed` / `admin_payment_failed` email
- **no** retry scheduled, `_payment_retry_attempts` stays 0
- order stays `pending` (not `failed`)
- the subscription still marches through the ordinary grace clock: on-hold ≈ D+1 day, cancelled ≈ D+4 days, unless the customer completes verification at `action_url`

### Stripe webhook wiring

Stripe events arrive through the **official WooCommerce Stripe Gateway** and are intercepted rather than routed through `arraysubs/v1/webhooks/*`: `add_action('wc_stripe_webhook_received', …, 30, 3)` and `add_action('wc_stripe_deferred_webhook', …, 20, 3)` (`StripeDelegate.php:119-120`). `payment_intent.requires_action` maps to `EVENT_PAYMENT_REQUIRES_ACTION` (`:98,1242`). Success/failure re-emit the core actions with the correct signature: `arraysubs_gateway_payment_succeeded` `:1273`, `arraysubs_gateway_payment_failed` `:1287`.

### Card behaviour cheat-sheet (in-scope test cards)

| Card | Off-session renewal result | Downstream |
|---|---|---|
| `4242 4242 4242 4242` | `succeeded` | `payment_complete` → `payment_successful` email, dates advance |
| `4000 0027 6000 3184` | `requires_action` | verification email, **no retry, no failure email**, grace clock runs |
| `4000 0000 0000 0341` | decline on every off-session charge | `failed` order, 3 retries at 24 h, on-hold D+1, cancel D+4 |
| `4000 0000 0000 9995` | `insufficient_funds` decline | same as above; `_last_payment_failure_category = insufficient_funds` (`gateway-helpers.php:330-340`) |

