# SLT-REF-04 Complete email inventory: class, template, trigger, recipient, subject, settings key

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-04 — Email inventory (reference note)

Registration: `EmailManager::register_email_classes()` `arraysubs/src/Features/Emails/Services/EmailManager.php:131-158`; injected into WooCommerce via `woocommerce_email_classes` with key `arraysubs_<id>` (`:166-173`). Base class `arraysubs/src/Features/Emails/Abstracts/BaseSubscriptionEmail.php`; template base = `<plugin>/src/Features/Emails/templates/` (`:51`); recipient for customer emails = `_customer_email` meta, falling back to the `_customer_id` user's email (`:169-188`).

**Two independent enable gates:**
1. WooCommerce's own per-email checkbox (`WC_Email::is_enabled()`, checked inside `trigger()` at `BaseSubscriptionEmail.php:136`).
2. ArraySubs settings — and there are **two divergent maps**: `EmailManager::is_email_enabled()` (private, `EmailManager.php:711-740`) used by `EmailManager::trigger()`, and the global `arraysubs_is_email_enabled()` (`arraysubs/src/functions/email-helpers.php:50-80`) used only by the two `arraysubs_send_subscription_email()` callers. They do **not** contain the same keys. Anything absent from the relevant map returns `true` (enabled).

## Free-plugin customer emails

| Email id / class | Template (html / plain) | Fires on | Recipient | Default subject | Settings key (EmailManager map) |
|---|---|---|---|---|---|
| `new_subscription` — `NewSubscriptionEmail` (`Emails/NewSubscriptionEmail.php:28-37`) | `customer-new-subscription.php` / `plain/…` | status → `arraysubs-active` from pending/trial/auto-draft, or `_arraysubs_status_change_context = initial_payment` (`EmailManager.php:325-344`) | customer | `[{site_title}] Your subscription #{subscription_id} is active` | `emails.new_subscription.enabled` |
| `renewal_reminder` — `RenewalReminderEmail` (`:56-65`) | `customer-renewal-reminder.php` | scheduled action `arraysubs_send_renewal_reminder` (`EmailManager.php:123,802-842`) | customer | `[{site_title}] Your subscription #{subscription_id} renews soon` — **context-swapped**: trial → `…Your trial for {product_name} ends soon`; `_end_date` set → `…is ending soon` (`RenewalReminderEmail.php:120-161`) | `emails.renewal_upcoming.enabled` |
| `renewal_invoice` — `RenewalInvoiceEmail` (`:28-37`) | `customer-renewal-invoice.php` | `arraysubs_renewal_invoice_created` → `on_renewal_invoice_created()` (`EmailManager.php:90,495-513`). **Suppressed for automatic-payment subs with auto-renew on** (`:504-510`). Once-per-order guard meta `_arraysubs_renewal_invoice_email_sent` (`:555-573`) | customer | `[{site_title}] Invoice for subscription #{subscription_id}` | `emails.renewal_invoice.enabled` |
| `payment_successful` — `PaymentSuccessfulEmail` (`:28-37`) | `customer-payment-successful.php` | `arraysubs_renewal_payment_complete` (`EmailManager.php:102,524-546`); order guard `_arraysubs_renewal_payment_success_email_sent` | customer | `[{site_title}] Payment received for subscription #{subscription_id}` | `emails.renewal_payment_received.enabled` |
| `payment_failed` — `PaymentFailedEmail` (`:28-37`) | `customer-payment-failed.php` | `arraysubs_gateway_payment_failed` → `on_payment_failed()` (`EmailManager.php:96,661-676,614-618`); also the never-scheduled `arraysubs_send_payment_failed` action | customer | `[{site_title}] Payment failed for subscription #{subscription_id}` | `emails.payment_failed.enabled` |
| `subscription_on_hold` — `SubscriptionOnHoldEmail` (`:28-37`) | `customer-subscription-on-hold.php` | `arraysubs_data_status_changed` → `arraysubs-on-hold` (`EmailManager.php:370-372`) | customer | `[{site_title}] Your subscription #{subscription_id} is on hold` | `emails.subscription_on_hold.enabled` |
| `subscription_pending_cancellation` — `SubscriptionPendingCancellationEmail` (`:31-40`) | `customer-subscription-pending-cancellation.php` | `arraysubs_data_waiting_cancellation` (`EmailManager.php:81,409-442`); idempotent per `_cancellation_scheduled_date` via `_arraysubs_pending_cancel_email_sent_for` | customer | `[{site_title}] Subscription #{subscription_id} scheduled to cancel on {scheduled_cancel_date}` | `emails.subscription_pending_cancellation.enabled` |
| `subscription_cancelled` — `SubscriptionCancelledEmail` (`:28-37`) | `customer-subscription-cancelled.php` | status → `arraysubs-cancelled` (`EmailManager.php:374-381`) | customer | `[{site_title}] Your subscription #{subscription_id} has been cancelled` | `emails.subscription_cancelled.enabled` |
| `subscription_expired` — `SubscriptionExpiredEmail` (`:28-37`) | `customer-subscription-expired.php` | status → `arraysubs-expired` (`EmailManager.php:383-389`) | customer | `[{site_title}] Your subscription #{subscription_id} has expired` | `emails.subscription_expired.enabled` |
| `expiring_soon` — `SubscriptionExpiringSoonEmail` (`:35-44`) | **`customer-renewal-reminder.php`** (deliberately shares the reminder template) | ONLY the scheduled action `arraysubs_send_expiring_soon` — **which nothing ever schedules** (see §Bugs) | customer | `[{site_title}] Your subscription #{subscription_id} is ending soon` | `emails.expiring_soon.enabled` (+ `emails.expiring_soon.days_before` = 7) |
| `auto_downgrade` — `AutoDowngradeEmail` (`:43-55`) | `customer-auto-downgrade.php` | `arraysubs_auto_downgrade_completed` (`EmailManager.php:116,1030-1036`; fired at `PlanSwitching/Services/AutoDowngradeHandler.php:410-415`) | customer | `[{site_title}] Your subscription #{subscription_id} has been changed to {new_product_name}` | `emails.auto_downgrade.enabled` |
| `retention_discount_accepted` — `RetentionDiscountAcceptedEmail` (`:77-86`) | `customer-retention-discount-accepted.php` | `arraysubs_retention_offer_accepted` with type `discount`\|`coupon` (`EmailManager.php:120,1048-1058`) | customer | `[{site_title}] Your retention discount for {product_name} is active` | `emails.retention_discount_accepted.enabled` — **not present in settings defaults** (`settings-helpers.php:184-262`), so it silently defaults to enabled |
| `subscription_reactivated` — `SubscriptionReactivatedEmail` (`:28-37`) | `customer-subscription-reactivated.php` | `arraysubs_data_reactivated` (`EmailManager.php:112,1013-1019`) | customer | `[{site_title}] Your subscription for {product_name} has been reactivated` | **none in EmailManager map → always enabled** |
| `trial_started` — `TrialStartedEmail` (`:28-37`) | `customer-trial-started.php` | `arraysubs_trial_started`, or pending/auto-draft → `arraysubs-trial` (`EmailManager.php:106,352-368,956-983`); guard meta `_arraysubs_trial_started_email_sent` | customer | `[{site_title}] Your free trial for {product_name} has started` | **absent from EmailManager map → `emails.trial_started.enabled` has no effect** |
| `trial_converted` — `TrialConvertedEmail` (`:28-37`) | `customer-trial-converted.php` | `arraysubs_trial_converted` (`EmailManager.php:108,992-1003`; fired at `TrialConverter.php:128`) | customer | `[{site_title}] Your trial for {product_name} has converted to a paid subscription` | **absent from EmailManager map → `emails.trial_converted.enabled` has no effect** |
| `renewal_requires_verification` — `RenewalRequiresVerificationEmail` (`:27-34`) | `customer-renewal-requires-verification.php` | `arraysubs_renewal_requires_verification` (`EmailManager.php:97,184-205`; fired at `StripeDelegate.php:1977`). Gated by the **global** helper `arraysubs_is_email_enabled('renewal_requires_verification')` → `emails.renewal_requires_verification.enabled` (key absent from defaults ⇒ true) | customer | `[{site_title}] Verify your subscription renewal #{subscription_id}` | `emails.renewal_requires_verification.enabled` |
| `card_expiring` — `CustomerCardExpiringEmail` (`:27-34`) | `customer-card-expiring.php` | `arraysubs_card_expiring` (`EmailManager.php:98,215-229`). Same global-helper gate → `emails.card_expiring.enabled` (absent from defaults ⇒ true) | customer | `[{site_title}] Update the card for subscription #{subscription_id}` | `emails.card_expiring.enabled` |

## Free-plugin admin emails

Recipient = `get_admin_email_recipient()` (per-email "Recipient(s)" WC field, default `arraysubs_get_admin_email()` / `emails.admin_email` / `get_option('admin_email')`); `customer_email = false` on all four.

| Email id / class | Template | Fires on | Default subject | Settings key |
|---|---|---|---|---|
| `admin_new_subscription` — `AdminNewSubscriptionEmail` (`:28-40`) | `admin-new-subscription.php` | alongside `new_subscription` (`EmailManager.php:332,340`) | `[{site_title}] New subscription #{subscription_id} from {customer_name}` | `emails.admin_new_subscription` (flat bool) |
| `admin_payment_failed` — `AdminPaymentFailedEmail` (`:28-44`) | `admin-payment-failed.php` | alongside `payment_failed` (`EmailManager.php:617`) | `[{site_title}] Payment failed for subscription #{subscription_id}` | `emails.admin_payment_failed` |
| `admin_subscription_cancelled` — `AdminSubscriptionCancelledEmail` (`:28-43`) | `admin-subscription-cancelled.php` | status → cancelled (`EmailManager.php:380`) | `[{site_title}] Subscription #{subscription_id} cancelled by {customer_name}` | `emails.admin_cancelled` |
| `admin_subscription_pending_cancellation` — `AdminSubscriptionPendingCancellationEmail` (`:29-46`) | `admin-subscription-pending-cancellation.php` | `arraysubs_data_waiting_cancellation` (`EmailManager.php:433`) | `[{site_title}] Subscription #{subscription_id} scheduled to cancel on {scheduled_cancel_date}` | `emails.admin_pending_cancellation` |

## Pro store-credit emails

Registered separately through `woocommerce_email_classes` in `arraysubspro/src/Features/StoreCredit/Services/EmailManager.php:48-56`; each class self-registers its trigger action in its constructor. Template base is the pro plugin's own path; all four are customer emails with **no ArraySubs settings key** — the only gate is the WooCommerce per-email checkbox (`is_enabled()` checked at each class's trigger).

| Email id / class | Template (html / plain) | Trigger action | Default subject |
|---|---|---|---|
| `arraysubs_credit_added` — `CreditAddedEmail` (`Emails/CreditAddedEmail.php:56-66`) | `emails/credit-added.php` / `emails/plain/credit-added.php` | `arraysubs_customer_credit_added` (4 args) and `arraysubs_subscription_credit_added` (`:84-85`) | `[{site_title}] Store credit added to your account` |
| `arraysubs_credit_used` — `CreditUsedEmail` (`:56-66`) | `emails/credit-used.php` | `arraysubs_credits_applied_to_order` (`:84`) | `[{site_title}] Store credit used for Order #{order_id}` |
| `arraysubs_credit_expiring` — `CreditExpiringEmail` (`:49-59`) | `emails/credit-expiring.php` | `arraysubs_credit_expiring_soon` (`:80`), fired from `CreditExpiration::sendExpiringNotification()` (`Services/CreditExpiration.php:290`) via scheduled `arraysubs_send_credit_expiring` | `[{site_title}] Your store credit expires soon` |
| `arraysubs_credit_expired` — `CreditExpiredEmail` (`:42-52`) | `emails/credit-expired.php` | `arraysubs_credit_expired` (`:68`) | `[{site_title}] Your store credit has expired` |

> The whole store-credit email chain only runs when `store_credit.expiration_days > 0` — otherwise `CreditExpiration::scheduleExpirationJob()` returns before scheduling `arraysubs_expire_store_credits` (`CreditExpiration.php:64-68`), and nothing ever queues `arraysubs_send_credit_expiring`. Warning window is a hardcoded `DAYS_BEFORE_EXPIRY_WARNING = 7` (`:34`) — the `emails.credit_expiring.days_before` key the settings REST controller validates (`MainAdmin/REST/SettingsController.php:589,593`) is **never read** by the expiration service.

## CANDIDATE BUGS — sending path / template / settings mismatches

| # | Finding | Evidence |
|---|---|---|
| B1 | **`expiring_soon` can never fire automatically.** The class, template, settings block (`enabled` + `days_before: 7`) and the AS handler all exist, but **nothing anywhere schedules `arraysubs_send_expiring_soon`** — `grep -rn HOOK_SEND_EXPIRING_SOON` matches only the constant `ActionScheduler.php:128`, the maps `:1254,1286`, the query helper `:700`, and the handler registration `EmailManager.php:125`. | `EmailManager.php:125,892-917`; `ActionScheduler.php:128` |
| B2 | **`arraysubs_send_payment_failed` is a dead scheduled hook.** Handler registered (`EmailManager.php:124,881-885`) but nothing schedules it. Harmless (failure mail arrives via `arraysubs_gateway_payment_failed`), but the constant + group mapping imply a scheduled path that does not exist. | `ActionScheduler.php:122,1285` |
| B3 | **`emails.trial_ending.*` is a settings key with no email.** Defaults ship `enabled: true, days_before: 3` (`settings-helpers.php:246-251`) and both REST controllers validate it (`EasySetup/REST/SetupController.php:80,158`; `MainAdmin/REST/SettingsController.php:578,593`), but **there is no TrialEndingEmail class and no template**. The trial reminder is actually delivered by `RenewalReminderEmail`'s trial context using `emails.renewal_upcoming.days_before`, so changing `trial_ending.days_before` does nothing. | `Emails/Emails/` listing; `RenewalReminderEmail.php:141-146` |
| B4 | **`emails.subscription_activated.*` is a settings key with no email.** Present in defaults (`settings-helpers.php:190-194`) and never read by any map or class. | grep: no consumer |
| B5 | **Orphan template: `templates/plain/customer-sca-auth-required.php`** — a plain-text template with **no HTML counterpart and no class referencing it** (`grep -rn 'sca-auth-required'` → no PHP matches). Dead file, likely the predecessor of `customer-renewal-requires-verification.php`. | `arraysubs/src/Features/Emails/templates/plain/` |
| B6 | **`emails.trial_started.enabled` and `emails.trial_converted.enabled` are inert.** Both keys exist in defaults and in the *global* helper map (`email-helpers.php:66-67`), but `EmailManager::trigger()` uses its own private map which omits them (`EmailManager.php:714-731`) and returns `true` for unknown ids (`:735-737`). Turning them off in ArraySubs settings has no effect; only the WooCommerce email checkbox works. | `EmailManager.php:711-740` vs `email-helpers.php:50-80` |
| B7 | **The global helper maps keys that do not exist anywhere:** `emails.subscription_skipped.enabled`, `emails.subscription_paused.enabled`, `emails.subscription_resumed.enabled`, `emails.plan_switch.enabled` (`email-helpers.php:63-68`). There are no such emails, templates, or settings — they all resolve to `true` against a missing setting. | `email-helpers.php:63-68` |
| B8 | **`arraysubs_send_plan_switch_email` has no listener.** `PlanSwitching\Services\Hooks::onPlanSwitchCompleted()` fires it (`PlanSwitching/Services/Hooks.php:601`) and `grep -rn arraysubs_send_plan_switch_email` finds **no `add_action`** anywhere in either plugin. A manual plan switch therefore sends **no customer email at all**. | `PlanSwitching/Services/Hooks.php:601` |
| B9 | `retention_discount_accepted` is gated on `emails.retention_discount_accepted.enabled`, a key not present in the shipped defaults; it resolves to `true`. Not broken, but untestable via the settings UI. | `EmailManager.php:726` vs `settings-helpers.php:184-262` |

## Verification recipe

```bash
# capture the id BEFORE the trigger, then wait
PREV=$(/usr/local/bin/mailpit-agent latest-id)
# … trigger …
/usr/local/bin/mailpit-agent wait-new "$PREV" 60 "renews soon"   # exit 124 = timeout
/usr/local/bin/mailpit-agent text latest
```

