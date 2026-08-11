# Subscription Lifecycle QA issue categories

This index groups every issue record without moving the source files. Keeping the
records in this directory preserves the links used by watch reports, task notes,
and evidence handoffs.

Classification rule:

- **QA plan issues** cover incorrect expectations, missing source fixtures,
  planning conflicts, retracted observations, and test-environment configuration
  blockers that are not product defects.
- **Critical plugin bugs** are product defects recorded as `high` or `critical`.
- **Light plugin bugs** are product defects recorded as `medium` or `low`.

New issue records must use the matching filename prefix: `qa-plan-`,
`critical-plugin-`, or `light-plugin-`.

## 1. QA plan issues (13)

- [D06 watch guarantees have no subscriptions from five missed D04 source tasks](qa-plan-SLT-CHK-08-d06-watch-guarantees-missing-d04-cohort.md)
- [D05 watch expects CPN-03 renewal cycles after the source task closed without fixtures](qa-plan-SLT-CPN-03-d05-watch-expects-missing-cycle-fixtures.md)
- [Dunning future-watch source conflicts](qa-plan-SLT-DUN-future-watch-source-conflicts.md)
- [Retracted EML-12 concurrent-transition observation](qa-plan-SLT-EML-12-admin-status-ui-fires-active-mail-without-persisting-status.md)
- [MYA-05 future follow-up source conflicts](qa-plan-SLT-MYA-05-future-followup-source-conflicts.md)
- [Members Access rule blocks the planned SLT checkout cohort](qa-plan-SLT-PROD-01-members-access-all-products-rule-blocks-slt-checkouts.md)
- [Documented Stripe retry settings do not exist](qa-plan-SLT-REF-03-documented-stripe-retry-settings-do-not-exist.md)
- [Admin-new-user email expectation is a plan defect](qa-plan-SLT-SETUP-03-admin-new-user-mail-despite-notification-checkbox-off.md)
- [SETUP-99B tail-cohort source conflicts](qa-plan-SLT-SETUP-99B-tail-cohort-source-conflicts.md)
- [Raw flex-meta diff false positive](qa-plan-SLT-SYN-01A-raw-meta-diff-order-only.md)
- [D05 lifetime-control source fixture missing](qa-plan-SLT-SYN-11-d05-lifetime-control-source-missing.md)
- [Retracted SYN-14 initial-recurring-amount expectation](qa-plan-SLT-SYN-14-initial-recurring-amount-stores-line-total.md)
- [Calendar/watch source conflicts from missed D02 fixtures](qa-plan-SLT-calendar-watch-source-conflicts-missed-d02-fixtures.md)

## 2. Critical plugin bugs (13)

- [Admin-created daily subscription schedules one month later](critical-plugin-SLT-ADM-05-admin-created-daily-subscription-arms-at-one-month.md)
- [Renewal orders lack the subscription item meta used by refund lookups](critical-plugin-SLT-ADM-06-renewal-orders-missing-arraysubs-subscription-id.md)
- [One-click add replaces the cart instead of enforcing the composition guard](critical-plugin-SLT-CHK-06-one-click-replaces-cart-and-bypasses-composition-guard.md)
- [Grouped sequential add silently replaces an existing child](critical-plugin-SLT-CHK-12-grouped-sequential-add-replaces-existing-subscription.md)
- [One-time subscription coupon is not captured](critical-plugin-SLT-CPN-02-one-time-coupon-capture-missing.md)
- [Manual invoice payment updates a different subscription's saved card](critical-plugin-SLT-EML-02-manual-payment-updates-wrong-subscription.md)
- [Paddle payment-method change is treated as a paid renewal](critical-plugin-SLT-MYA-03-payment-method-change-treated-as-zero-dollar-renewal.md)
- [Duplicate gateway post-meta rows can diverge](critical-plugin-SLT-OBS-01-duplicate-gateway-postmeta-rows.md)
- [Variable subscription draft and variations are trashed on save](critical-plugin-SLT-PROD-08-variable-subscription-draft-is-trashed-on-save.md)
- [Subscription checkout offers incompatible Alipay](critical-plugin-SLT-REN-03-subscription-checkout-offers-incompatible-alipay.md)
- [Paddle product-sync metadata is not created after save](critical-plugin-SLT-SETUP-05-paddle-product-sync-metas-not-created.md)
- [Paddle plan-switch order-pay cannot start checkout](critical-plugin-SLT-SW-05-paddle-order-pay-no-valid-items.md)
- [Variable parent persists hidden flexible-sync defaults](critical-plugin-SLT-SYN-02-variable-parent-hidden-flex-meta.md)

## 3. Light plugin bugs (24)

- [Paddle gateway filter returns zero](light-plugin-SLT-ADM-01-paddle-gateway-filter-returns-zero.md)
- [Product-title search returns zero](light-plugin-SLT-ADM-01-product-title-search-returns-zero.md)
- [Zero-result filters show first-product onboarding](light-plugin-SLT-ADM-01-zero-result-shows-first-product-onboarding.md)
- [Empty shipping address renders a stray comma](light-plugin-SLT-ADM-02-empty-shipping-address-renders-stray-comma.md)
- [One-day billing interval is rendered as `day(s)`](light-plugin-SLT-ADM-02-singular-billing-schedule-uses-day-s.md)
- [Full-refund actor is misattributed](light-plugin-SLT-ADM-08-admin-cancellation-misattributed-to-customer.md)
- [Prorated-refund control is missing](light-plugin-SLT-ADM-08-prorated-refund-control-missing.md)
- [Gateway-refund button remains at `$0.00`](light-plugin-SLT-ADM-08-refund-button-amount-stays-zero.md)
- [WooCommerce dependency warning during checkout and portal navigation](light-plugin-SLT-CHK-01-wc-blocks-data-dependency-warning.md)
- [Classic checkout omits shipping-address snapshot](light-plugin-SLT-CHK-02-classic-checkout-omits-shipping-address-meta.md)
- [Recurring amount merge tag renders HTML in email subject](light-plugin-SLT-EML-12-recurring-amount-subject-renders-html.md)
- [My Account routes emit repeated `AbortError`](light-plugin-SLT-IMP-05-my-account-routes-emit-view-transition-aborterror.md)
- [Routine Paddle webhooks are logged as unhandled warnings](light-plugin-SLT-IMP-05-paddle-routine-webhooks-logged-as-unhandled-warnings.md)
- [Disabling Skip Renewal leaves its hidden cutoff unchanged](light-plugin-SLT-LIFE-03-disabled-save-leaves-hidden-cutoff.md)
- [Paddle payment-method update leaves local card display stale](light-plugin-SLT-MYA-03-missing-local-paddle-payment-method-update-surface.md)
- [Paddle renewal order omits transaction metadata and line item](light-plugin-SLT-MYA-03-paddle-renewal-order-omits-transaction-meta-and-line-item.md)
- [Grouped multi-subscription refusal notice is missing](light-plugin-SLT-PROD-09-grouped-multi-subscription-refusal-notice-missing.md)
- [General settings save materializes unrelated defaults](light-plugin-SLT-SETUP-02-general-settings-save-materializes-unrelated-defaults.md)
- [Paddle renewal leaves last gateway transaction stale](light-plugin-SLT-SW-05-paddle-renewal-leaves-subscription-last-transaction-stale.md)
- [Paddle plan-switch preview total disagrees with order-pay](light-plugin-SLT-SW-05-preview-total-disagrees-with-order-pay.md)
- [Pending cancellation sets the cancelled date early](light-plugin-SLT-SW-10-pending-cancellation-sets-cancelled-date-early.md)
- [Customer reactivation action is missing](light-plugin-SLT-SW-10-reactivation-action-missing.md)
- [Disabled flexible-renewal segments remain visible](light-plugin-SLT-SYN-01-disabled-segments-remain-visible.md)
- [HPOS renewal-order editor issues a sample-permalink 403](light-plugin-SLT-SYN-09-order-editor-sample-permalink-403.md)
