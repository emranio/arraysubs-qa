I found one MEDIUM defect and three LOW ones. The worst: since the classifier map changed, a Stripe 401 during the pre-charge card check stops all retries and tells the customer they have no payment method. I made no edits; I only ran `git diff`, `php -l`, and read-only `wp eval` probes (scripts in `/tmp/claude-0/-home-server-manager-www-arrayhash-mirror-help-arrayhash-com-public-wp-content-plugins/38333750-918d-41d9-9e98-8f405025965a/scratchpad/`).

Paths below are relative to `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/`.

## Findings

**F1 — MEDIUM — A Stripe 401 now stops dunning as "no payment method" (regression from the new classifier mapping)**
- **Where:**
  - `arraysubs/src/functions/gateway-helpers.php:631` (new `'renewal_payment_method_binding_invalid' => 'missing_payment_method'`)
  - `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/Traits/StripeApiTrait.php:77-91`
  - `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/Traits/StripePaymentMethodsTrait.php:751-803`
  - `woocommerce-gateway-stripe/includes/class-wc-stripe-api.php:422`
- **Scenario:** the merchant's Stripe secret key is rolled or expired, or the account is reconnected. Every renewal due in that window does this:
  1. The pre-charge read of the payment method gets HTTP 401.
  2. Woo Stripe 10.9.0 `WC_Stripe_API::retrieve()` returns `null` on any 401. After 5 consecutive 401s it returns `null` for 2 hours without calling Stripe.
  3. `normalizeStripeResponse(null)` turns that into `[]`, not a `WP_Error`.
  4. `verifyStripePaymentMethodIsReusable()` sees no object or id and returns `renewal_payment_method_binding_invalid`.
  5. That now classifies as `missing_payment_method`, a hard decline. With `stop_on_hard_decline` on (the default), no retry is scheduled.
  6. The note says the customer must update their payment method, the order is marked failed and the failure email goes out.

  Once the key is fixed, nothing retries, so the subscription drifts to on-hold and then cancelled.
- **Why it is real:**
  - Probe: `normalizeStripeResponse(null)` returns an empty array and `is_wp_error` is false.
  - Probe: `binding_invalid` classifies as `missing_payment_method`, which is hard.
  - Probe: `stop_on_hard_decline` is true for Stripe subscription 93.
  - In HEAD, `gateway-helpers.php` has no `binding_invalid` key, so this was `unknown` and was retried 24 h later.
- **Fix:** make `normalizeStripeResponse()` return a `WP_Error` (e.g. `stripe_empty_response`) when the response is null or not an array/object. The check then yields `renewal_payment_method_verification_failed`, which is soft. Alternatively, treat a response with no `id`/`object` as `verification_failed` inside `verifyStripePaymentMethodIsReusable()`.
- **Same root cause elsewhere:**
  - `customer.updated` acknowledges and drops a new default card with a 200 during a 401 window.
  - The sweep's branch with no local context marks a real authentication intent `superseded`.

**F2 — LOW — `renewal_payment_method_verification_failed` → `processing_error` also covers permanent provider errors**
- **Where:**
  - `arraysubs/src/functions/gateway-helpers.php:621`
  - `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/Traits/StripePaymentMethodsTrait.php:755-759` (the provider error is reduced to `provider_error => 'stripe_api_error'`, so `resource_missing` is lost)
- **Scenario:** the stored `pm_` no longer exists in the connected account or mode (Stripe `resource_missing`, e.g. after switching account or test→live). Every attempt is reported as "a temporary processing error occurred at the gateway" and the full ladder runs. The hard-stop "update your payment method" path is never reached. Before the patch this was `unknown`: retried the same way, but with no misleading reason note.
- **Fix:** when the error data `code` is `resource_missing` (HTTP 404), return `renewal_payment_method_binding_invalid`. Keep `verification_failed` for transport, 5xx, 429 and outage errors.

**F3 — LOW — The new help text uses the default ladder as its example, but with default settings the 3rd retry is usually cancelled**
- **Where:** `arraysubs/src/resources/pages/Settings/GeneralSettings.jsx:709`
- **How the timing works:**
  - When the subscription goes on hold: `arraysubs/src/Features/RecurringBilling/Services/GracePolicy.php:38-41` sets it to due + 3 days. The spread offset is only considered for the first charge.
  - When the first charge runs: due + spread offset, which is 0–6 h with defaults (`RenewalScheduler::schedule`).
  - When retries run: each one is `time() + interval` after the previous failure (`arraysubs/src/Features/RecurringBilling/Services/RenewalProcessor.php:928`).
  - What cancels them: an hourly overdue sweep moves the subscription to on-hold (`arraysubs/src/Features/RecurringBilling/Services/Hooks.php:179-185, 741-744`), and on-hold unschedules the queued retry (`arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:1017-1019`).
- **Scenario:** default settings (3 days active after due, 6 h spread, ladder 24/24/24). A subscription with an offset of 2 h 14 m (68525's offset is 8021 s) has its 3rd retry at due + 74 h 14 m. The sweep puts it on hold at about due + 72–73 h, which cancels that retry. This happens for every subscription whose offset plus latency is longer than the gap to the next sweep run, which is all subscriptions with an offset of 1 h or more (about 5/6 of them).
- **Minor:** `handleFailure()` sets on-hold before `scheduleNextRetry()`, so retries after an order-creation failure do run while on hold. "Retries stop when put on hold" is therefore not always true.
- **Fix:** subtract the spread window in the text (with defaults use "24, 24", or set 4 days active after due). Or fix the product:
  - change the default ladder;
  - or extend the on-hold time to `due + offset + sum(ladder) + 1h` (bounded) while retries are on;
  - or have the on-hold sweep skip subscriptions whose retry is queued within the window.

**F4 — LOW (outside the focus list; race in B1)**
- **Where:** `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/Traits/StripePaymentMethodsTrait.php:526-530`
- **Problem:** `ActionScheduler::isScheduled()` (`as_has_scheduled_action`) counts an in-progress action as queued.
- **Scenario:** a `customer.updated` event arrives during a renewal attempt.
  1. It waits on the gateway-meta lock (`GatewayMetaStore::withLock` blocks).
  2. It binds the new card after the charge request, while the attempt is still finishing.
  3. B1 sees the running action and returns without queuing anything.
  4. If that attempt ends in a hard decline, or the ladder is exhausted, nothing is ever queued, so the new card is never charged automatically.
- **Fix:** when `as_next_scheduled_action()` returns `true` (running only), still queue the +60–120 s attempt.
  - If the running attempt ends in a soft decline, its retry replaces this one.
  - If it ends hard, this one survives and charges the new card.
  - If it succeeds, the queued attempt does nothing because `isRenewalDue` is false.

## Checked, no finding
- **Next-retry helper (`arraysubs_get_payment_retry_next_attempt_at`, `gateway-helpers.php:416`):**
  - The helper reports a running retry as `time()`, which is fine.
  - The return types used by `SubscriptionController`, `GracePolicy` and the Pro `RenewalFailureController` are unchanged.
  - Dropping the resolved gate is correct.
  - Every way a renewal gets paid resets the attempt count (`OrderIntegration.php:1680`, etc.), so the next regular renewal is not shown as a retry.
- **`RenewalRespreader.php`:**
  - It only handles subscriptions whose due date is in the future, and only calls the helper when the retry meta is in the future.
  - The change only makes respreading more permissive when nothing is pending.
  - No fatal risk: the functions files are always loaded.
- **`RenewalProcessor.php`:**
  - The meta deletes are correct.
  - `BillingClockOwnership::forSubscription()` exists and returns a bool. Live probe results:

    | Gateway | Owns billing clock | Note shown |
    |---|---|---|
    | stripe | false | store setting turned retries off |
    | arraysubs_paddle, arraysubs_paypal | true | gateway retries on its own side (retries off) |
    | bacs, cheque, no gateway | false (resolver returns null) | store setting |
    | Mollie | false (`SubscriptionCapabilities::ownsBillingClock()`) | store setting |
- **`stripeFailureCode`:**
  - A 24-case reflection probe matched the intended design, including `confirm_card_data` and unknown decline codes.
  - Authentication plus `try_again_later` maps to `generic_decline`, but that is unreachable: the synchronous path checks for authentication first. The webhook path only logs the code (`handlePaymentFailed` does not call `recordProviderPaymentFailure`).
  - The new map keys do not collide with codes used by Mollie, Paddle or PayPal.
- **`SettingsController` 10-entry cap:**
  - It matches the page's limits (at most 10 steps, 1–720 h).
  - EasySetup import uses the same sanitizer.
  - The partial-payload merge leaves the other `payment_retry` keys untouched.
- **`GeneralSettings.jsx` save change (`getFieldsValue(true)`):**
  - Untouched values are not sent:
    - All 37 fields on the page are in the GET response.
    - The 380 flat keys, including 160 indexed `cancellation.reasons.N.*` keys, start out identical in the form and in the saved copy.
    - Field names are flat, with no initial values and no change-on-mount.
    - Hidden fields are unmounted.
    - Discard restores from a store snapshot.
  - Saving a hidden dependent field is harmless: the server ignores each one while its parent switch is off.
  - Visible invalid fields are still blocked and show the toast.
  - The hidden-invalid-ladder hole was patched at 20:00:29 UTC (`GeneralSettings.jsx:1218-1220`). Before that, it silently saved `[24, null]`, which the server turned into `[24]` or the default.
  - Remaining annoyance: that toast refers to a field the admin cannot see.
- **Build:** `arraysubs/public/build/105.ee7def25.chunk.js` (built 20:01:07 UTC, after the last edit to the page; referenced by `arraysubs/public/build/admin/mainadmin.js`) contains "Some settings are not valid", "keep the whole schedule inside that window" and the hidden-ladder check. The old chunk is gone. `arraysubs/languages/arraysubs.pot` still has the old retries-off string.
- **Lint and size:** `php -l` is clean on every touched PHP file, and no touched file is over 3000 lines.