# Issue #68: MollieMandateRebinder listens to WooCommerce actions that never fire

- Investigation date: 2026-10-20. Read-only: no code, settings or data were changed. This report is the only file written.
- Workspace root: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins`. Paths below are relative to it.
- Versions: WooCommerce 11.0.1, WooCommerce Stripe Gateway 10.9.0, ArraySubs 2.0.9. The ArraySubs working tree was read as is, including yesterday's uncommitted Stripe work.

Evidence labels:

- **[verified]**: read in this workspace, or observed on the staging site.
- **[memory]**: from my knowledge of the Mollie Payments for WooCommerce source. The plugin is not installed here, so these statements could not be checked.

`arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/StripeDelegate.php` was modified at 08:44 today while this ran. Another agent is probably working in parallel. Stripe line numbers below were re-read at 08:52 and may move again. The Mollie files are unchanged since 2026-09-03 (the rebinder) and 2026-10-18 (the delegate).

## Verdict

1. **Both listeners in the constructor are dead, not only line 49** [verified]:
   - `MollieMandateRebinder.php:49` listens on `woocommerce_payment_token_added`.
   - `MollieMandateRebinder.php:50` listens on `woocommerce_add_payment_method_success`.

   WooCommerce 11.0.1 fires neither action, and no plugin in the workspace does. On staging both are registered with nothing that fires them (runtime probe in §4).
2. **There is no WooCommerce event to move them to.**
   - Mollie for WooCommerce never creates WC payment tokens and does not declare `add_payment_method` or `tokenization` [memory].
   - WooCommerce lists only gateways with one of those features on the add-payment-method page [verified]. A Mollie customer therefore cannot add a Mollie method there at all.
   - The Stripe fix works only because the official Stripe plugin creates WC tokens and pushes "Make default" to Stripe, which then sends `customer.updated`. Mollie has none of these: no tokens, no default mandate, no mandate webhook (§2, §3).
3. **Recommendation.**
   - Delete the two listeners and the four methods only they reach (§3, option A).
   - The real product defect sits next to it: the Mollie "Update payment method" link sends customers to My Account > Payment methods, which cannot add a Mollie method. Hide the link (option B1) until a real flow exists. That flow (option B2) is the build-out plan's original C2 design, which was never built.
4. **Staging cannot test Mollie.** It has no Mollie plugin, no Mollie gateway, no Mollie options or subscriptions, and only `stripe` tokens. Only static and runtime hook-registration verification is possible (§4).

## 1. What MollieMandateRebinder is meant to do

### 1.1 History [verified]

- `git -C arraysubs log -S MollieMandateRebinder --oneline` returns only `e98991a feat: gateway capability build-out across all four payment gateways` (2026-08-17).
- `git -C arraysubs log -S woocommerce_payment_token_added` and `-S woocommerce_add_payment_method_success` return the same single commit.
- That commit also added the Stripe listener on the same dead action: `add_action('woocommerce_payment_token_added', [$this, 'rebindAfterPaymentTokenAdded'], 10, 1)` in `StripeDelegate.php`. The current uncommitted tree removes it.
- The commit implements `developments/gateway-capability-buildout-plan.md`. The plan was added in root-repo commit `900dd9b` and deleted in `b7ae140` (2026-09-21). It can be read with `git show 900dd9b:developments/gateway-capability-buildout-plan.md`.
- **Plan C2 (payment-method change):**
  > "`payment_method_update => true` today renders a customer-facing button that leads to a page no Mollie method can service … Mint a new `sequenceType=first` payment (zero-amount for card and PayPal per C1) to create a fresh mandate, then rebind `_gateway_mandate_id` on success … `MollieMandateResolver` … Rebinding must go through it, not around it … Until this works, set the flag `false` — a dead button is worse than an absent one."
- **Plan D1 (Stripe):**
  > "Also hook `woocommerce_payment_token_added` / the official plugin's add-payment-method success so a card added through WooCommerce's own flow rebinds too."

  This is where the hook names came from. Nobody checked them against WooCommerce.
- **What the progress tracker claimed.** `developments/gateway-capability-buildout-progress.md` (at `c4a9efb`) marks C2 "done":
  > "`MollieMandateRebinder` mints nothing itself — it rebinds. It hooks WooCommerce's own add-payment-method flow and a new ownership-checked `payment-method/rebind` REST route … The flag stays true because there is now a mechanism behind it."
- **What was actually built.** The planned mechanism, a new first payment, never existed. The flag stayed `true` on the strength of two dead hooks and a REST route that no UI calls.

### 1.2 Class walkthrough

File: `arraysubs/src/Features/AutomaticPayments/Services/MollieMandateRebinder.php` (474 lines).

| Lines | Member | What it does | Status |
|---|---|---|---|
| 3-21 | Class docblock | Says "The customer added a card through WooCommerce's own flow, Mollie minted the mandate". | Not true on an ArraySubs-only store |
| 40 | `$loadable = true` | Makes the loader instantiate the class automatically. | |
| 47-49 | `woocommerce_payment_token_added` → `onPaymentTokenAdded` | | **Dead** |
| 50 | `woocommerce_add_payment_method_success` → `onAddPaymentMethodSuccess` | | **Dead** |
| 52-54 | filter `arraysubs_gateway_rebind_payment_method` → `filterRebindResult` | The comment says "called after the customer returns from the provider's add-payment-method flow". | Reached only via REST `/rebind`. No UI calls it. |
| 56-58 | action `arraysubs_gateway_rebind_mollie_mandate` → `rebindSubscription` | Described as an "admin action". | No `do_action` anywhere in either plugin |
| 60, 78-156 | filter `arraysubs_gateway_revoke_payment_authorisation` → `filterRevokeResult` | Plan C4 explicit revoke: two-step confirmation, names other subscriptions on the same mandate, calls `DELETE customers/{cst}/mandates/{mdt}`. | Reached via admin REST `/revoke` |
| 165-197 | `recordMandateRevocation` | Clears `_gateway_mandate_id` and `_gateway_payment_method_id`, sets `_gateway_status = needs_payment_method`, adds a note and a log entry. | |
| 206-228 | `getSubscriptionsSharingMandate` | SQL lookup by `_gateway_mandate_id`. | |
| 237-251 | `filterRebindResult` | Answers only when `_payment_gateway` is Mollie, then calls `rebindSubscription`. | |
| 259-268 | `onPaymentTokenAdded($token_id)` | Loads the WC token and calls `rebindForCustomer(token user, token gateway id)`. | **Dead** |
| 277-282 | `onAddPaymentMethodSuccess($result, $gateway_id = '')` | Calls `rebindForCustomer(get_current_user_id(), $gateway_id)`. | **Dead**. An empty gateway id would also slip past the prefix guard at 299. |
| 291-306 | `rebindForCustomer` | Needs `MollieAvailability::isAvailable()` and a `mollie_wc_gateway_` gateway id, then rebinds every Mollie subscription of the user (active, trial, on-hold or paused; up to 100, one or two Mollie API calls each, synchronously). | Reached only from the two dead handlers |
| 314-443 | `rebindSubscription` | See below. | Reached via REST or the filter |
| 451-473 | `getMollieSubscriptionIds` | SQL lookup by `_customer_id` plus `_payment_gateway = mollie`. | Reached only from `rebindForCustomer` |

`rebindSubscription()` in detail:

1. It skips detached subscriptions and requires `_gateway_customer_id`.
2. It calls `MollieMandateResolver::resolve()` with the stored mandate and `_payment_method_type`.
3. A Mollie `error` result is never treated as "no mandate".
4. If the stored mandate is still the current one, it returns success without changing anything (362-368).
5. Otherwise it persists the new mandate and the display meta (375-385). It does not touch `_gateway_status`.
6. It resets payment retry attempts (395-397) and deletes `_arraysubs_card_expiry_notified` (400).
7. It adds a note, logs, and fires `arraysubs_gateway_payment_credential_rebound` (431-436).

### 1.3 Who loads it [verified]

- `arraysubs/src/Boot.php:47` boots `Features\AutomaticPayments\Provider`.
- `arraysubs/src/Features/AutomaticPayments/Provider.php:27-33` calls `classLoader([... 'Services', ... 'REST'])`.
- `arraysubs/src/Supports/Abstracts/AbstractLoader.php:30-87` globs `*.php` in those folders (53). It instantiates every class whose `$loadable` is `true` (check at 78).
- So the constructor registers all its hooks on every request, whether or not Mollie is installed. Staging confirms this (§4).
- `MollieDelegate` is constructed separately at `Services/Hooks.php:87`.
- ArraySubs Pro has no Mollie mandate code. Its only Mollie mentions are help text in `arraysubspro/src/Features/FlexibleRenewalSync/views/*-fields.php`. **Any fix is core-only.**

### 1.4 Callers of the parts that can run [verified]

- **REST `POST arraysubs/v1/subscriptions/{id}/payment-method/rebind`.**
  - Route at `arraysubs/src/Features/AutomaticPayments/REST/PaymentMethodController.php:88-105`, handler at 220-256.
  - Ownership is checked by `checkSubscriptionPermission` (264-283).
  - No JS, template or build file in either plugin calls it. The portal only calls `/update` (`arraysubs/src/resources/customerPortal.js:1962-2028`).
- **REST `.../payment-method/revoke`:** route at 107-131, handler at 156-208. Admin only.
- **QA suite.** `qa/automation/gateway-tests/mollie.php:180-235` calls `rebindSubscription()` and `filterRebindResult()` directly, using a fake client injected through `arraysubs_mollie_api_client`. Nothing anywhere calls `onPaymentTokenAdded`, `onAddPaymentMethodSuccess` or `rebindForCustomer`.
- **Docs.** `user-manual/markdowns/developers/actions-and-filters.md:273` documents `arraysubs_gateway_payment_credential_rebound` as "rebound to a new mandate after a payment-method change". Nothing in either plugin listens to it.

### 1.5 Every place ArraySubs reads or writes a subscription's Mollie binding [verified]

**Subscription meta (`arraysubs_data` posts):**

- `_payment_gateway = mollie`
- `_gateway_customer_id` (`cst_…`)
- `_gateway_mandate_id` and `_gateway_payment_method_id`: both hold the `mdt_…` id, because Mollie has no separate payment-method object
- `_payment_method_type`
- `_payment_method_brand`, `_payment_method_last4`, `_payment_method_expiry_month`, `_payment_method_expiry_year`
- `_payment_method_updated_at`, `_payment_method_source`
- `_gateway_status`, `_gateway_environment`, `_gateway_host_plugin`, `_last_gateway_transaction_id`

ArraySubs never reads `_mollie_customer_id` or `_mollie_mandate_id` from the subscription. Those keys are order meta. The host plugin's user meta `mollie_customer_id` is read only as a fallback.

In the table, "delegate" means `Gateways/Mollie/MollieDelegate.php`, and paths without a folder are under `arraysubs/src/Features/AutomaticPayments/`.

| # | Location | Trigger | Reads | Writes |
|---|---|---|---|---|
| 1 | `MollieOrderContextBridge::capture()`, `Services/MollieOrderContextBridge.php:135-291` | Hooks: `arraysubs_gateway_context_captured` @20 (64), `woocommerce_payment_complete` @20 (65), `mollie-payments-for-woocommerce_after_mandate_created` @20 (69). Also called by delegate `capturePaymentContext()` (931-942). | Order `_mollie_payment_id`, `_mollie_customer_id`, `_mollie_mandate_id`, `_mollie_payment_mode`. `GET payments/{id}` (customerId, mandateId, method, sequenceType). User meta `mollie_customer_id` (386). The resolver confirms the payment's own mandate is valid. | The full binding, with `_gateway_status` set to active or errored (229-255). It skips renewal orders (149-151) and detached subscriptions. It adopts only the mandate this payment established (204-208). |
| 2 | `MollieForceTokenization::flagSubscriptionPayment()`, `Services/MollieForceTokenization.php:38, 59-106` | Filter `mollie-payments-for-woocommerce_is_subscription_payment` | The order or cart | Nothing. It decides whether Mollie mints a mandate (`sequenceType=first`). Never for renewal orders (75-77), guests (90-92), or orders without subscription products. |
| 3 | Delegate `processRenewalPaymentUnderLock()`, 417-474 | Scheduled renewal | `_gateway_mandate_id` via `MandateRegistry::getMandateForSubscription()` (`Services/MandateRegistry.php:93-98`), falling back to `_gateway_payment_method_id`. Then the resolver. | `_gateway_mandate_id` and `_gateway_payment_method_id` when Mollie holds a different valid mandate (449-473, logged as a warning) |
| 4 | Delegate `prepareRenewalOrderMeta()`, 2214-2240 | Before the charge | | Renewal order `_mollie_mandate_id`, `_mollie_customer_id`, `_gateway_payment_method_id` |
| 5 | Delegate `interpretPayment()`, 589-618 | Charge response | The payment's `mandateId` and `method` | The subscription binding to the mandate Mollie actually used. If that write fails: order meta plus a repair job. |
| 6 | Delegate `storePaymentMeta()`, 2272-2296 | Charge or webhook | | Order `_mollie_mandate_id` |
| 7 | Delegate `captureRenewalPaymentContext()`, 956-1026 | Repair job (`HOOK_GATEWAY_META_RETRY`, queued at 2676-2734) | The delegate-charged renewal order's `_mollie_mandate_id`, `_arraysubs_mollie_payment_method`, `_mollie_payment_mode` | The subscription binding |
| 8 | Delegate `syncFromGateway()`, 1321-1378 | Admin sync | The resolver | The mandate if it differs (1350-1360). It returns `gateway_status` active or errored, which the reconciler treats as authoritative (`Services/Traits/PaymentHooksGatewaySyncTrait.php:159-169`). This is the only automatic path that moves an errored Mollie subscription back to active without a new checkout. |
| 9 | Delegate `markNeedsPaymentMethod()`, 2654-2667 | No mandate, a 404, or a non-EUR SEPA renewal | | `_gateway_status = errored` |
| 10 | Delegate `cancelRemoteBillingContext()`, 1060-1097 | Cancellation | | `_gateway_status = inactive`. It never revokes the mandate. |
| 11 | Delegate `handlePaymentMethodUpdated()`, 1914-1941 | Would bind the payload's `mandateId` | | Nothing in practice. **It cannot be reached:** `normalizeEventType()` (1624-1636) maps no Mollie event to `EVENT_PAYMENT_METHOD_UPDATED` (dispatch in `Services/WebhookRouter.php:291-309`). |
| 12 | Delegate `setupTrialPaymentMethod()`, 892-929 | Trial checkout | `_gateway_mandate_id` | |
| 13 | Delegate `formatPaymentMethodForDisplay()` (1380-1419) and `getPaymentMethodUpdateMechanism()` (1421-1446) | Customer portal | Display meta. `can_update => true` (1415). | Nothing. Returns the My Account `payment-methods` URL (1425-1427). |
| 14 | `MollieMandateRebinder::rebindSubscription()`, 314-443 | REST `/rebind` only | The resolver | The binding and display meta via plain `persist()`. No compare-and-swap. `_gateway_status` is left alone. |
| 15 | `MollieMandateRebinder::recordMandateRevocation()`, 165-197 | Admin revoke | | Clears the binding and sets `needs_payment_method` |
| 16 | `Services/Traits/PaymentHooksPaymentFiltersTrait.php:62-78` | `arraysubs_is_automatic_payment` | `_gateway_payment_method_id`, `_gateway_status` | Nothing. Treats a Mollie subscription as manual when the payment-method id is empty or the status is `errored`. |
| 17 | Same trait, 124-134 | `arraysubs_should_process_automatic_payment` | `_gateway_status` | Nothing. Refuses any non-empty status other than `active`. Rows 3 and 5 sit behind this gate (`RecurringBilling/Services/PaymentProcessor.php:72-88`). |
| 18 | `MandateRegistry::cacheMandate()`, `Services/MandateRegistry.php:69-85` | | | Its docblock calls it the Mollie path, but it has no caller. |

**Not covered anywhere:** an existing Mollie subscriber replacing their card or bank account. No ArraySubs flow mints a new mandate for an existing subscription. A renewal paid manually is deliberately a one-off payment (row 2) and is never captured (row 1, 149-151).

## 2. Which real events signal a new or different valid Mollie mandate

### 2.1 WooCommerce token events (WooCommerce 11.0.1) [verified]

| Hook | Where it fires | Arguments | Fires for Mollie? |
|---|---|---|---|
| `woocommerce_payment_token_added` | Nowhere. Only `..._added_to_order` exists. | n/a | **Never** |
| `woocommerce_add_payment_method_success` | Nowhere. The WC form handler (`includes/class-wc-form-handler.php:575-646`) calls `$gateway->add_payment_method()` (632), then only adds notices and redirects. No success action. | n/a | **Never** |
| `woocommerce_new_payment_token` | `includes/data-stores/class-wc-payment-token-data-store.php:74`, on token create | `$token_id, $token` | Only if a token is saved |
| `woocommerce_payment_token_created` | Deprecated alias of the hook above (`includes/class-wc-deprecated-action-hooks.php:39`, deprecated in 3.0.0 per :75) | same | same |
| `woocommerce_payment_token_set_default` | `includes/class-wc-payment-tokens.php:206`, inside `set_users_default()` (200-211). Called from "Make default" (`class-wc-form-handler.php:677-697`, ownership and nonce check at 686, call at 689). Also called on token create or update when the token is the default: the data store makes a user's first token the default automatically (48-53, 70-72, 118-121). | `$token_id, $token` | Only if a token exists |
| `woocommerce_payment_token_added_to_order` | `includes/abstracts/abstract-wc-order.php:1828`, in `add_payment_token()` | `$order_id, $token_id, $token, $token_ids` | Only if a gateway attaches a token |
| `woocommerce_payment_token_updated`, `_object_updated_props`, `_deleted`, `_loaded` | Data store lines 124, 123, 138, 172 | | |

How WooCommerce handles the add-payment-method page:

- It lists only gateways that support `add_payment_method` or `tokenization` (`includes/class-wc-payment-gateways.php:338-341`).
- It rejects any other gateway on submit (`class-wc-form-handler.php:620-623`).
- It removes the My Account "Payment methods" menu item when no gateway qualifies (`includes/wc-account-functions.php:123-135`).
- With no qualifying gateway, the add form shows "New payment methods can only be added during checkout…" (`templates/myaccount/form-add-payment-method.php:59-60`).

### 2.2 Mollie Payments for WooCommerce [memory unless marked]

**Tokens and gateway features:**

- Mollie does not create `WC_Payment_Token` rows. It does not declare `tokenization` or `add_payment_method`.
- Its `supports` list is `products` and `refunds`. Mandate-capable methods add the WooCommerce Subscriptions set (`subscriptions`, `subscription_cancellation`, …, `subscription_payment_method_change_customer`, `multiple_subscriptions`). ArraySubs itself relies on `supports('subscriptions')` (`MollieAvailability.php:243`, `MollieForceTokenization.php:131`) [verified].
- Mandates live only at Mollie. "Single click" card storage happens on Mollie's hosted page, keyed by the customer ID. Mollie Components card tokens are single-use payment inputs, not WC tokens.
- **Consequence:** none of the §2.1 token hooks ever fire for Mollie, and My Account > Payment methods cannot add a Mollie method.

**Meta Mollie writes:**

- User meta `mollie_customer_id`. ArraySubs reads it at `MollieOrderContextBridge.php:386` [verified].
- Order meta `_mollie_payment_id`, `_mollie_customer_id`, `_mollie_payment_mode` and `_mollie_order_id`.
- `_mollie_mandate_id` is written only inside Mollie's WooCommerce Subscriptions code. ArraySubs's own docblocks say the same (`MollieOrderContextBridge.php:9-12, 67-69`; `MandateRegistry.php:61-63`) [verified].

**Hooks (unverified here):**

- `mollie-payments-for-woocommerce_after_mandate_created` (`$payment, $order, $customerId, $mandateId`). It fires from the first-payment mandate code inside a `class_exists('WC_Subscriptions')` guard, so it does not fire on an ArraySubs-only store. ArraySubs hooks it as a fallback anyway (`MollieOrderContextBridge.php:69`).
- `mollie-payments-for-woocommerce_customer_return_payment_success` (`$order`) fires when the customer returns after a successful payment.
- `mollie-payments-for-woocommerce_payment_created` (`$payment, $order`).
- Filters ArraySubs uses or deliberately leaves alone [usage verified at `MollieForceTokenization.php:38-49`]:
  - `mollie-payments-for-woocommerce_is_subscription_payment` (`$isSubscription, $orderId`)
  - `mollie-payments-for-woocommerce_is_automatic_payment_disabled`
  - `mollie_wc_subscription_plugin_active`
- Mollie's own webhook marks the order paid with `$order->payment_complete()`, which fires `woocommerce_payment_complete`. That is the WooCommerce core signal ArraySubs already uses.

**Mollie API:**

- A mandate comes from a paid `sequenceType=first` payment (a zero amount is allowed for card and PayPal), or from the Mandates API, which covers direct debit and PayPal only. The build-out plan quotes Mollie's documentation for both points (plan lines ~410-436 [verified in the plan]).
- Classic webhooks carry only a payment or order id. I know of no webhook for mandate creation or revocation.
- A Mollie customer has no "default mandate".

### 2.3 Signals that do occur in practice, and who handles them [verified]

1. **A paid first payment on a non-renewal order with subscription products.** `woocommerce_payment_complete` triggers the bridge (row 1). It binds that order's own mandate to that order's subscriptions only. The scope is correct and this is already handled.
2. **A rotated or replaced mandate noticed at charge time.** This covers a stored mandate that returns 404 or is invalid, or Mollie charging a different mandate. Rows 3 and 5 handle it, but only while `_gateway_status` is active or empty.
3. **An admin sync** (row 8), which can also re-activate an errored subscription.
4. **A renewal paid via Mollie with a new mandate.** This does not happen. Pay-page renewals are one-off payments (row 2) and capture skips them (row 1). Renewals the delegate charges are covered by row 5.
5. **A customer changing their payment method.** No signal exists, because no such flow exists.

## 3. Recommendation

### 3.1 Why the Stripe pattern does not transfer [verified]

ArraySubs does not hook `woocommerce_payment_token_set_default` itself. The wording at `qa/stripe-issues-56-64-fix-log.md:62-63` ("replaced by `woocommerce_payment_token_set_default`") overstates what the code does.

The runtime probe shows only one callback on that hook: `WC_Stripe_Payment_Tokens::woocommerce_payment_token_set_default` (`woocommerce-gateway-stripe/includes/payment-tokens/class-wc-stripe-payment-tokens.php:60, 741-761`). It calls `set_default_payment_method` at Stripe, and Stripe then sends `customer.updated`. ArraySubs handles that event in:

- `StripeWebhookEventsTrait::handlePaymentMethodUpdated()` (359, with the `customer.updated` branch at 364-374, and `previousDefaultPaymentMethod()` at 516)
- `StripePaymentMethodsTrait::bindStripeCustomerDefaultPaymentMethod()` (244)

That code:

- re-reads the customer instead of trusting the webhook payload;
- moves only subscriptions billed to the previous default, the subscription whose billing-portal visit is still open (389), and subscriptions whose card was removed (354);
- moves nothing when this is the customer's first default;
- verifies that the new card is attached and reusable (`verifyStripePaymentMethodIsReusable()`, 812);
- writes with compare-and-swap and sets `_gateway_status = active` (`bindStripePaymentMethod()`, 441-497; `captureContext` plus `compareAndMutate` at 464-471);
- queues an overdue unpaid renewal on the new card (`queueRenewalAttemptWithNewCard()`, 515).

The changelog rule it follows: "Adding a card without making it the default changes no subscription."

This works because Stripe provides WC tokens, a customer default, and a provider event confirming the change. Mollie provides none of the three. A listener on any WooCommerce token hook, even one guarded by the `mollie_wc_gateway_` prefix, would never run in production. That would recreate the same dead code.

Do not broaden the bridge's `woocommerce_payment_complete` listener into "move all of this customer's subscriptions to any newly minted mandate". That would move subscriptions the customer never asked to change, and it would break both the Stripe rule above and the bridge's own rule at `MollieOrderContextBridge.php:204-208`.

### 3.2 Option A (recommended for #68): delete the dead path

Edit `arraysubs/src/Features/AutomaticPayments/Services/MollieMandateRebinder.php`:

- Remove lines 47-50: both `add_action` calls and their comment.
- Remove `onPaymentTokenAdded()` (253-268), `onAddPaymentMethodSuccess()` (270-282) and `rebindForCustomer()` (284-306).
- Remove `getMollieSubscriptionIds()` (445-473). Its only caller is `rebindForCustomer`.
- Rewrite the class docblock (3-21 and 30-32) to describe what remains: re-resolving one subscription's mandate through REST, and explicit revocation.

No other code, test or document references the removed methods. AGENTS.md says no backward compatibility is needed. The Mollie suite (`qa/automation/gateway-tests/mollie.php:180-235`) is unaffected.

Optional dead-code cleanup in the same pass:

- the action `arraysubs_gateway_rebind_mollie_mandate` (line 58), which nothing fires;
- `MandateRegistry::cacheMandate()`, which nothing calls;
- the duplicate `card_auto_update` key in `MollieDelegate::getUnsupportedCapabilityMessages()` (lines 288 and 290; the second silently overwrites the first).

Option A alone changes nothing a user can see, so it needs no changelog entry.

### 3.3 Option B1 (recommended follow-up, user-visible): remove the dead "Update payment method" link for Mollie

**The problem:**

- Mollie inherits `payment_method_update => true` from the defaults (`Traits/SubscriptionCapabilities.php:118`).
- So the portal shows the link (`Services/AdminDisplayHooks.php:280, 333-340`).
- The link leads to My Account > Payment methods (`MollieDelegate.php:1421-1446`), where no Mollie method can be added (§2.1).
- This is exactly what plan C2 said to avoid.

**The change:**

- Add `'payment_method_update' => false` in `declareCapabilities()` (233-275).
- Add an explanation for it in `getUnsupportedCapabilityMessages()`.
- Make `formatPaymentMethodForDisplay()` return `can_update` from the capability (1415).
- Have `getPaymentMethodUpdateMechanism()` return `success => false` with that explanation.
- Update the docs: `user-manual/markdowns/customer-portal/payment-and-shipping.md:221` (remove Mollie from the redirect list) and `user-manual/markdowns/developers/actions-and-filters.md:273`.
- Add a `fixed:` line to `arraysubs/changelog.draft.txt`.

### 3.4 Option B2 (feature, for a real payment-method change): build plan C2

1. **Create the payment.** ArraySubs creates a Mollie `sequenceType=first` payment for the subscription's Mollie customer:
   - zero amount, card or PayPal only;
   - `metadata` of `{subscription_id, purpose, site_url}`;
   - the ArraySubs webhook URL and a validated portal `redirectUrl`;
   - the pending payment id stored on the subscription.
2. **Route the webhook.** A paid first payment with that purpose must go to `EVENT_PAYMENT_METHOD_UPDATED`, not payment-succeeded.
3. **Verify.** Check `customerId` against `_gateway_customer_id` with `hash_equals`, check the metadata and the site, and check that the payment id matches the stored pending id.
4. **Bind.** Bind that exact `mandateId`, not whatever the resolver picks. Use `captureContext` plus `compareAndMutate`, expecting the old `_gateway_mandate_id`.
5. **Restore renewals.** Set `_gateway_status = active`, refresh the display meta, reset retries, clear the card-expiry marker, and queue an overdue unpaid renewal the way Stripe does.

This makes `handlePaymentMethodUpdated()` reachable and makes `rebindSubscription()` unnecessary.

### 3.5 Defects in what remains (fix these if `rebindSubscription()` and REST `/rebind` are kept) [verified]

1. **It never sets `_gateway_status = active`** (375-385). An `errored` or `needs_payment_method` subscription therefore stays on manual renewals after a "successful" rebind (rows 16-17). The early return at 362-368, taken when the stored mandate is still valid, also never re-activates.
2. **It cannot move a subscription off a mandate that is still valid.** The resolver returns the stored mandate whenever it is valid (`MollieMandateResolver.php:55-66`). When the stored mandate is invalid or empty, it adopts the customer's first valid mandate, preferring the same method. That conflicts with the bridge's rule at `MollieOrderContextBridge.php:204-208`.
3. **It writes with plain `persist()`.** There is no compare-and-swap and no lock around the remote call, so a concurrent renewal can bind first and then be overwritten.
4. **`filterRevokeResult()` uses an undefined variable** (120-124). It writes to `$failure` and returns `['message' => …]` with no `success` key.
5. **The failure result at 335-338 has no `mandate_id` key**, so it does not match the documented return shape.
6. **The comment at 52-53 describes a caller that does not exist.**

## 4. Testing on staging

### 4.1 Staging state [verified, 2026-10-20]

Commands were run from `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`.

- `wp plugin list --allow-root`:
  - Active: arraysubs 2.0.9, arraysubspro 1.2.8, woocommerce 11.0.1, woocommerce-gateway-stripe 10.9.0 and wp-mail-smtp 4.9.0.
  - Must-use: qa-kb-article-cpt and sqa-lifecycle-capture.
  - **No Mollie plugin.**
- `wp option get woocommerce_gateway_order --allow-root`: no `mollie_wc_gateway_*` entries. It lists only the Stripe family, `arraysubs_paddle`, `arraysubs_paypal`, bacs, cheque and cod.
- Database:
  - no `mollie%` options;
  - no `arraysubs_data` posts with a `_payment_gateway` starting with `mollie`;
  - `woocommerce_payment_tokens` holds only `stripe` tokens (70).

### 4.2 Read-only runtime probe

The probe walks `$GLOBALS['wp_filter'][$hook]->callbacks` for each hook, prints `class::method`, and writes nothing. It was run with `wp eval-file … --allow-root`. Output:

```
woocommerce_payment_token_added => 10 MollieMandateRebinder::onPaymentTokenAdded
woocommerce_add_payment_method_success => 10 MollieMandateRebinder::onAddPaymentMethodSuccess
woocommerce_new_payment_token => -1000 WC_Deprecated_Action_Hooks::maybe_handle_deprecated_hook
woocommerce_payment_token_created => (none)
woocommerce_payment_token_set_default => 10 WC_Stripe_Payment_Tokens::woocommerce_payment_token_set_default
woocommerce_payment_token_added_to_order => (none)
woocommerce_payment_complete => … 20 MollieOrderContextBridge::captureFromPaidOrder | 20 StripeOrderContextBridge::captureFromPaidOrder …
mollie-payments-for-woocommerce_after_mandate_created => 20 MollieOrderContextBridge::captureFromMandateCreated
mollie-payments-for-woocommerce_is_subscription_payment => 20 MollieForceTokenization::flagSubscriptionPayment
arraysubs_gateway_rebind_payment_method => 10 MollieMandateRebinder::filterRebindResult
arraysubs_gateway_rebind_mollie_mandate => 10 MollieMandateRebinder::rebindSubscription
MollieAvailability::getStatus() = missing
```

### 4.3 What can and cannot be verified

- **End-to-end Mollie testing: not possible.**
  - There is no Mollie plugin and no Mollie test API key. The build-out progress file notes Mollie sandbox credentials were never available.
  - A Mollie subscription would not even show the portal link here: with the host plugin missing, `isGatewayEnabled()` fails (`MollieDelegate.php:185-189`).
- **Option A, without Mollie:**
  - Re-run the probe; both hooks should then show `(none)`.
  - Run `php -l` on the edited file and grep for the removed method names.
  - Run `wp eval-file qa/automation/gateway-tests/mollie.php --allow-root`. It uses the fake-client seam `arraysubs_mollie_api_client` (`MollieApiClient.php:68-86`) and `ArraySubs_Fake_Mollie_Client` (`qa/automation/gateway-tests/lib.php:261`). It creates and deletes fixture subscriptions, so it needs approval in a session that may write data. It was not run here.
- **Option B1:** a probe asserting `supportsSubscriptionCapability('payment_method_update') === false` for the Mollie delegate, plus a REST `/update` call that returns 403.
- **Re-hook probe (not recommended as acceptance evidence).** It is possible inside a single `wp eval` process:
  1. `define('M4W_FILE', …)` so that `MollieAvailability::isHostPluginPresent()` passes (`MollieAvailability.php:97-102`).
  2. Inject the fake client.
  3. Create a fixture user and a Mollie subscription.
  4. Save a `WC_Payment_Token_CC` with gateway id `mollie_wc_gateway_creditcard`. This fires `woocommerce_new_payment_token` and, because it is the user's first token, `woocommerce_payment_token_set_default`.

  This proves only the ArraySubs handler. It cannot show that Mollie ever creates such a token, so it would be a false positive for a path that does not occur.
- **Checking the [memory] statements** needs the Mollie plugin source. Unpack it in an isolated scratch directory without activating it, then run:

  ```
  grep -rn "WC_Payment_Token\|'tokenization'\|'add_payment_method'\|_after_mandate_created\|_customer_return_payment_success\|_is_subscription_payment" src/
  ```
