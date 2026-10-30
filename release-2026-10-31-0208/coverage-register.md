# Deduplicated release coverage register

Final snapshot: 2026-10-30T21:00:42.181994+00:00. All results reference browser evidence in lane shards; source-only expectations are retained as gaps.

| Metric | Visited/tested | Discovered/total |
| --- | ---: | ---: |
| Distinct template families | 87 | 97 |
| Live template families | 87 | 87 |
| Meaningful template modes | 141 | 160 |
| Live-discovered modes | 141 | 149 |
| Source-expected modes | 0 | 11 |
| Independent real-change save editors | 57 | 74 |
| Successful independent save editors | 57 | 74 |

Save results: {'NOT-TESTED': 17, 'PASS': 57}. A failed save counts as tested, never passed.

## Counting rules

- Final coverage snapshot; testing results reconciled. Save counts require real changed values plus reload/reopen. Product Feature/Redirect panels and Page Restriction Apply share their parent native post form, so their persistence tests do not create extra independent save editors.
- A meaningful mode is a different layout/editor definition; record IDs, queries, filters, pagination, wizard steps and lifecycle assertions are not additional pages.
- New source Audit Logging Settings page is scheduled-job mode/retention; deployed Activity entity-logging popover is a separate capability with independent autosave. Both are retained.
- Email settings/previews share one family: 29 live definitions plus one source-expected Webhooks definition, each with an independent save editor.
- One independent product editor is counted per distinct simple/variation/box/bundle workflow. Staged panels inside that workflow share its commit; this is not a count of HTTP endpoints.
- Both automatic/manual subscription layouts and simple/variable/grouped product editor modes are distinguished.
- Rule list and rule flow editor modes are distinct. Display-name saves do not cover condition/outcome/timing steps.
- Surface FAIL totals are observations, not distinct bug counts; multiple modes can reference one issue.
- PASS render can coexist with untested actions, external prerequisites or save gaps. No unconditional release pass follows from page visits.
- Legacy General policies do not verify the newly source-registered Renewal Flow layouts. Gateway/renewal failure aliases map to existing live screens; no duplicate count.
- Source /settings/emails and Shortcodes path aliases map to Woo Emails index / live guide tabs. Free-only /you-may-need and redirect landing aliases are not extra templates.
- License/secret mutation, destructive import/reset, real external provisioning and unsafe webhook registration are excluded actions; screens remain in scope.
- Unknown feature panel saves and missing integrations remain explicit gaps; the register is a reconciled known inventory, not proof all hidden permutations were found.

## Template/mode register

| ID | Family / mode | Capability | Discovery | Depth / result | Proof |
| --- | --- | --- | --- | --- | --- |
| plugins | plugins / default | Native Plugins activation baseline | live | render / PASS | evidence/plugins.png |
| modules | modules / default | Modules | live | save+reload / PASS | coverage.md; evidence/modules-off-reloaded.txt; evidence/modules-restored.txt |
| overview | overview / default | Overview | live | interaction / PASS | evidence/overview-7days.png |
| general | general / default | General settings | live | save+reload / PASS | evidence/settings-general-saved.png |
| toolkit | toolkit / default | Toolkit settings | live | render / PASS | evidence/settings-toolkit.png |
| switching | switching / default | Plan Switching settings | live | save+reload / PASS | evidence/switching-on.png |
| refund-settings | refund-settings / default | Refund settings | live | save+reload / PASS | evidence/refunds-saved.txt |
| skip-pause | skip-pause / default | Skip & Pause settings | live | save+reload / PASS | evidence/commerce-skip-result.png |
| content-gate | content-gate / default | Content Gate settings | live | save+reload / PASS | evidence/content-gate-restored.json |
| activity | activity / default | Activity Audits | live | interaction / PASS | evidence/audits-activity-customer.png |
| gateway-logs | gateway-logs / default | Gateway Logs | live | interaction / PASS | coverage.md; evidence/gateway-filter-stripe-success.txt |
| renewal-failures | renewal-failures / default | Renewal Failures | live | render / PASS | evidence/audits-renewal-failures.png |
| portal-failures | portal-failures / default | Portal Action Failures | live | render / FAIL | evidence/audits-portal-action-failures.png |
| access-conflicts | access-conflicts / default | Access Rule Conflicts | live | render / PASS | evidence/audits-access-rule-conflicts.png |
| job-logs | job-logs / default | Scheduled Job Logs | live | render / PASS | evidence/audits-scheduled-job-logs.png |
| portal-failure-detail | portal-failure-detail / default | Portal failure detail modal | live | interaction / PASS | evidence/portal-failure-detail.png |
| integrations | integrations / default | Integrations | live | render / PASS | evidence/integrations.png |
| help | help / default | Help | live | render / PASS | evidence/help.png |
| license | license / default | License status | live | render / PASS | evidence/license.png |
| easy-setup-home | easy-setup-home / default | Easy Setup / Import Export | live | render / PASS | evidence/easy-setup.png |
| easy-setup-wizard | easy-setup-wizard / default | Easy Setup ten-step wizard | live | interaction / PASS | evidence/easy-setup-final-review.png |
| shortcodes-shortcode | shortcodes-guide / shortcode | Shortcodes guide: shortcode | live | interaction / PASS | coverage.md |
| shortcodes-full-page | shortcodes-guide / full-page | Shortcodes guide: full-page | live | interaction / PASS | coverage.md |
| shortcodes-gutenberg | shortcodes-guide / gutenberg | Shortcodes guide: gutenberg | live | interaction / PASS | coverage.md |
| shortcodes-elementor | shortcodes-guide / elementor | Shortcodes guide: elementor | live | interaction / PASS | coverage.md |
| shortcodes-php | shortcodes-guide / php | Shortcodes guide: php | live | interaction / PASS | coverage.md |
| logging-settings | logging-settings / default | New Audit Logging Settings page (job mode/retention) | source-expected | NOT-TESTED / NOT-TESTED | arraysubs/src/resources/pages/Audits/LoggingSettings.jsx; inventory.md |
| logging-preferences | activity / entity-logging-popover | Activity entity-logging preferences popover | live | interaction / PASS | coverage.md; evidence/audit-logging-settings.txt; evidence/audit-logging-settings.png |
| plans | plans / default | Plans list | live | interaction / PASS | evidence/commerce-plans.png |
| plan-wizard | plan-wizard / default | Plan creation wizard | live | save+reload / PASS | evidence/commerce-wizard-review.png |
| product-simple | woo-product-editor / simple | Woo product billing editor: simple | live | save+reload / PASS | evidence/commerce-product-reloaded.png |
| product-variable | woo-product-editor / variable | Woo product billing editor: variable/variation | live | save+reload / PASS | evidence/commerce-variation-saved.png |
| product-grouped | woo-product-editor / grouped | Woo product editor: unsupported Grouped persistence | live | save+reload / FAIL | evidence/commerce-grouped-reloaded.png |
| bundle-config | bundle-config / default | Bundle configuration modal | live | save+reload / PASS | evidence/commerce-bundle-saved.png |
| box-config | box-config / default | Box configuration modal | live | save+reload / PASS | evidence/commerce-box-saved-placeholder.png |
| subscriptions-admin | subscriptions-admin / default | Admin Subscriptions list | live | interaction / PASS | evidence/commerce-subscriptions-filter.png |
| sub-create | sub-create / default | Custom subscription creation | live | save+reload / PASS | evidence/commerce-custom-sub-created.png |
| sub-detail-admin | sub-detail-admin / default | Admin subscription detail | live | interaction / PASS | evidence/commerce-admin-early-renewed.png |
| sub-edit | sub-edit / default | Admin subscription edit | live | save+reload / PASS | evidence/commerce-sub-edit-reloaded.png |
| product-public-simple | product-storefront / simple | Product storefront: simple | live | interaction / PASS | evidence/commerce-paid-product.png |
| product-public-variable | product-storefront / variable | Product storefront: variable | live | interaction / PASS | evidence/commerce-variable-gold-settled.png |
| product-public-bundle | product-storefront / bundle | Product storefront: bundle | live | render / PASS | evidence/commerce-bundle-product.png |
| product-public-box | product-storefront / box-builder | Box storefront builder | live | interaction / PASS | evidence/commerce-box-review.png |
| cart-block | cart-block / default | Block cart | live | interaction / FAIL | evidence/commerce-trial-cart.png |
| checkout-block | checkout / block | Block checkout / Stripe sandbox | live | end-to-end / PASS | evidence/commerce-receipt.png |
| checkout-classic | checkout / classic | Classic checkout / Stripe sandbox | live | end-to-end / FAIL | evidence/commerce-trial-receipt.png |
| receipt | receipt / default | Order received / receipt | live | render / PASS | evidence/commerce-credit-receipt.png |
| sub-note | sub-note / default | Private subscription Note editor | live | save+reload / PASS | commerce-coverage.md |
| woo-orders | woo-orders / default | Native Woo Orders list | live | interaction / PASS | commerce-coverage.md |
| woo-order-edit | woo-order-edit / default | Native Woo Order editor | live | render / PASS | commerce-coverage.md |
| product-features | product-features / default | Product feature configuration | live | save+reload / PASS | commerce-coverage.md; evidence/commerce-features-customer-positive.png |
| product-redirect | woo-product-editor / redirect-panel | Product Redirect panel | live | save+reload / PASS | commerce-coverage.md |
| rule-role-list | rule-role / list | Role Mapping rule list | live | interaction / PASS | premium-coverage.md |
| rule-role-editor | rule-role / editor | Role Mapping rule flow editor | live | save+reload / PASS | premium-coverage.md |
| rule-discount-list | rule-discount / list | Discount rule list | live | interaction / PASS | premium-coverage.md |
| rule-discount-editor | rule-discount / editor | Discount rule flow editor | live | save+reload / PASS | premium-coverage.md |
| rule-url-list | rule-url / list | URL rule list | live | interaction / PASS | premium-coverage.md |
| rule-url-editor | rule-url / editor | URL rule flow editor | live | save+reload / PASS | premium-coverage.md |
| rule-shop-list | rule-shop / list | Shop Access rule list | live | interaction / PASS | premium-coverage.md |
| rule-shop-editor | rule-shop / editor | Shop Access rule flow editor | live | save+reload / PASS | premium-coverage.md; evidence/shop-rule-restored.txt |
| rule-cpt-list | rule-cpt / list | CPT / Post Types rule list | live | render / PASS | premium-coverage.md |
| rule-cpt-editor | rule-cpt / editor | CPT / Post Types rule flow editor | live | save+reload / PASS | coverage.md; evidence/cpt-name-saved.txt; evidence/cpt-name-restored.txt; evidence/cpt-flow-editor.png; evidence/cpt-content-modal.png |
| rule-downloads-list | rule-downloads / list | Downloads rule list | live | render / PASS | premium-coverage.md |
| rule-downloads-editor | rule-downloads / editor | Downloads rule flow editor | live | NOT-TESTED / NOT-TESTED | premium-coverage.md |
| rule-comments-list | rule-comments / list | Comments rule list | live | render / PASS | premium-coverage.md |
| rule-comments-editor | rule-comments / editor | Comments rule flow editor | live | NOT-TESTED / NOT-TESTED | premium-coverage.md |
| rule-purchase-limit-list | rule-purchase-limit / list | Purchase Limit rule list | live | render / PASS | premium-coverage.md |
| rule-purchase-limit-editor | rule-purchase-limit / editor | Purchase Limit rule flow editor | live | NOT-TESTED / NOT-TESTED | premium-coverage.md |
| rule-login-limit-list | rule-login-limit / list | Login Limit rule list | live | render / PASS | premium-coverage.md |
| rule-login-limit-editor | rule-login-limit / editor | Login Limit rule flow editor | live | NOT-TESTED / NOT-TESTED | premium-coverage.md |
| rule-styling-list | rule-styling / list | Member Styling rule list | live | render / PASS | premium-coverage.md |
| rule-styling-editor | rule-styling / editor | Member Styling rule flow editor | live | NOT-TESTED / NOT-TESTED | premium-coverage.md |
| profile-form | profile-form / canvas | Profile Form builder | live | save+reload / PASS | premium-coverage.md; coverage.md |
| profile-field | profile-form / existing-field | Existing profile field editor | live | interaction / PASS | premium-coverage.md; coverage.md |
| profile-add | profile-form / new-field | Add profile field | live | NOT-TESTED / NOT-TESTED | premium-coverage.md; coverage.md |
| account-menu | account-builder / menu | My Account menu builder | live | interaction / PASS | premium-coverage.md; coverage.md |
| account-item | account-builder / existing-item | Existing account item editor | live | interaction / PASS | premium-coverage.md; coverage.md |
| account-custom | account-builder / custom-page | New custom account item/page | live | NOT-TESTED / NOT-TESTED | premium-coverage.md; coverage.md |
| checkout-canvas | checkout-builder / canvas | Checkout Builder canvas | live | interaction / PASS | premium-coverage.md; coverage.md |
| checkout-settings | checkout-builder / settings | Checkout Builder settings | live | save+reload / PASS | coverage.md; evidence/checkout-radius9-reloaded.txt; evidence/checkout-radius8-restored.txt |
| checkout-field | checkout-builder / billing-field | Checkout Builder Billing Address editor | live | interaction / PASS | premium-coverage.md; coverage.md |
| checkout-add-step | checkout-builder / add-step | Checkout Builder add/rename step | live | NOT-TESTED / NOT-TESTED | premium-coverage.md; coverage.md |
| cancellation-overview | cancellation-flow / overview | Cancellation Flow overview | live | render / PASS | premium-coverage.md; coverage.md |
| cancellation-wizard | cancellation-flow / wizard | Cancellation seven-step offer wizard | live | interaction / BLOCKED | premium-coverage.md; coverage.md |
| credit-list | credit-list / default | Store Credit list | live | interaction / PASS | premium-coverage.md; coverage.md |
| credit-detail | credit-detail / detail | Store Credit customer detail | live | interaction / PASS | premium-coverage.md; coverage.md |
| credit-adjust | credit-detail / inline-adjustment | Store Credit inline adjustment form | live | end-to-end / PASS | premium-coverage.md; coverage.md |
| credit-history | credit-history / default | Store Credit global history | live | interaction / PASS | premium-coverage.md; coverage.md |
| credit-settings | credit-settings / default | Store Credit settings | live | render / PASS | premium-coverage.md; coverage.md |
| feature-settings | feature-settings / default | Feature Manager settings | live | save+reload / PASS | coverage.md; evidence/feature-usage-off-reloaded.txt; evidence/feature-usage-restored.txt |
| members | members / default | Manage Members list | live | interaction / PASS | access-coverage.md |
| member-detail | member-detail / default | Manage Members detail | live | interaction / PASS | access-coverage.md; evidence/access-member-detail.png |
| member-edit | member-edit / default | Manage Members Edit Details | live | save+reload / PASS | access-coverage.md |
| native-page | native-page / default | Native WP Page editor | live | save+reload / PASS | access-coverage.md; evidence/access-rule-final-persisted.png |
| page-restriction | page-restriction / default | Per-page Access Restriction modal | live | interaction / PASS | access-coverage.md; evidence/access-rule-final-persisted.png |
| woo-customers | woo-customers / default | Native Woo Customers integration | live | interaction / PASS | access-coverage.md; evidence/access-woo-customers-filtered.png |
| forecast | forecast / default | Revenue Forecast | live | interaction / PASS | access-coverage.md; evidence/access-forecast-filter.png |
| churn | churn / default | Churn Analysis | live | interaction / PASS | access-coverage.md; evidence/access-churn-filter.png |
| retention | retention / default | Retention analytics | live | interaction / PASS | access-coverage.md; evidence/access-retention-filter.png |
| refund-report | refund-report / default | Refund analytics | live | interaction / PASS | access-coverage.md; evidence/access-refunds-filter.png |
| account-dashboard | account-dashboard / default | Customer dashboard | live | interaction / PASS | access-coverage.md |
| account-edit | account-edit / default | Customer account details / custom field | live | save+reload / PASS | access-coverage.md |
| address-list | address-list / default | Customer addresses | live | render / PASS | access-coverage.md; evidence/access-addresses.png |
| billing-edit | billing-edit / default | Customer billing address | live | save+reload / PASS | access-coverage.md |
| shipping-edit | shipping-edit / default | Customer shipping address | live | save+reload / PASS | access-coverage.md |
| account-subs | account-subs / default | Customer subscription cards | live | interaction / PASS | access-coverage.md |
| account-sub-auto | account-sub-detail / automatic | Customer subscription detail: automatic | live | end-to-end / FAIL | access-coverage.md; evidence/access-related-orders-mobile-viewport.png |
| account-sub-manual | account-sub-detail / manual-shipping | Customer subscription detail: manual physical box | live | render / PASS | access-coverage.md |
| account-order | account-order / default | Customer order detail | live | interaction / PASS | access-coverage.md; evidence/access-owner-order.png |
| account-orders | account-orders / default | Customer Orders list | live | interaction / PASS | access-coverage.md |
| account-features-empty | account-features / empty | My Features empty state | live | render / PASS | access-coverage.md; evidence/access-features.png |
| account-features-positive | account-features / positive | My Features positive values | live | interaction / PASS | access-coverage.md; commerce-coverage.md; evidence/commerce-features-customer-positive.png |
| account-credit | account-credit / default | Customer Store Credit | live | interaction / PASS | access-coverage.md; evidence/commerce-credit-after.png |
| account-downloads | account-downloads / default | Customer Downloads empty state | live | render / PASS | access-coverage.md; evidence/access-downloads.png |
| account-payments | account-payments / default | Customer payment methods empty state | live | render / PASS | access-coverage.md |
| add-payment | add-payment / default | Customer Add payment method | live | render / PASS | access-coverage.md |
| guest-login | guest-login / default | Guest account login / register gate | live | render / PASS | access-coverage.md |
| protected-post | protected-content / post | Protected native Post | live | end-to-end / FAIL | access-coverage.md; evidence/access-entitled-content.png |
| protected-page | protected-content / page | Protected native Page | live | end-to-end / FAIL | access-coverage.md; evidence/access-runpage-owner.png |
| action-scheduler | action-scheduler / default | Native Scheduled Actions scoped queue | live | interaction / PASS | commerce-coverage.md; evidence/commerce-scheduler-109453-pending.png |
| email-index | email-index / default | WooCommerce Emails index | live | render / PASS | emails-inventory.json |
| email-arraysubs_new_subscription | email-settings-preview / arraysubs_new_subscription | [ArraySubs] New Subscription | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_renewal_reminder | email-settings-preview / arraysubs_renewal_reminder | [ArraySubs] Renewal Reminder | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_renewal_invoice | email-settings-preview / arraysubs_renewal_invoice | [ArraySubs] Renewal Invoice | live | save+reload / PASS | emails-results.json; evidence/email-invoice-saved.txt; evidence/email-invoice-restored.txt |
| email-arraysubs_payment_successful | email-settings-preview / arraysubs_payment_successful | [ArraySubs] Renewal Payment Successful | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_payment_failed | email-settings-preview / arraysubs_payment_failed | [ArraySubs] Renewal Payment Failed | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_on_hold | email-settings-preview / arraysubs_subscription_on_hold | [ArraySubs] Subscription On Hold | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_pending_cancellation | email-settings-preview / arraysubs_subscription_pending_cancellation | [ArraySubs] Subscription Scheduled to Cancel | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_cancelled | email-settings-preview / arraysubs_subscription_cancelled | [ArraySubs] Subscription Cancelled | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_expired | email-settings-preview / arraysubs_subscription_expired | [ArraySubs] Subscription Expired | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_expiring_soon | email-settings-preview / arraysubs_expiring_soon | [ArraySubs] Subscription Expiring Soon | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_auto_downgrade | email-settings-preview / arraysubs_auto_downgrade | [ArraySubs] Subscription Auto-Downgraded | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_retention_discount_accepted | email-settings-preview / arraysubs_retention_discount_accepted | [ArraySubs] Retention Discount Accepted | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_reactivated | email-settings-preview / arraysubs_subscription_reactivated | [ArraySubs] Subscription Reactivated | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_skipped | email-settings-preview / arraysubs_subscription_skipped | [ArraySubs] Renewal Skipped | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_skip_undone | email-settings-preview / arraysubs_skip_undone | [ArraySubs] Renewal Skip Removed | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_paused | email-settings-preview / arraysubs_subscription_paused | [ArraySubs] Subscription Paused | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_subscription_resumed | email-settings-preview / arraysubs_subscription_resumed | [ArraySubs] Subscription Resumed | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_trial_started | email-settings-preview / arraysubs_trial_started | [ArraySubs] Trial Started | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_trial_converted | email-settings-preview / arraysubs_trial_converted | [ArraySubs] Trial Converted | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_renewal_requires_verification | email-settings-preview / arraysubs_renewal_requires_verification | [ArraySubs] Renewal Requires Verification | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_card_expiring | email-settings-preview / arraysubs_card_expiring | [ArraySubs] Card Expiring | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_admin_new_subscription | email-settings-preview / arraysubs_admin_new_subscription | [ArraySubs] New Subscription (Admin) | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_admin_payment_failed | email-settings-preview / arraysubs_admin_payment_failed | [ArraySubs] Payment Failed (Admin) | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_admin_subscription_pending_cancellation | email-settings-preview / arraysubs_admin_subscription_pending_cancellation | [ArraySubs] Subscription Scheduled to Cancel (Admin) | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubs_admin_subscription_cancelled | email-settings-preview / arraysubs_admin_subscription_cancelled | [ArraySubs] Subscription Cancelled (Admin) | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubspro_credit_added_email | email-settings-preview / arraysubspro_credit_added_email | [ArraySubsPro] Store Credit Added | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubspro_credit_used_email | email-settings-preview / arraysubspro_credit_used_email | [ArraySubsPro] Store Credit Used | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubspro_credit_expiring_email | email-settings-preview / arraysubspro_credit_expiring_email | [ArraySubsPro] Store Credit Expiring Soon | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-arraysubspro_credit_expired_email | email-settings-preview / arraysubspro_credit_expired_email | [ArraySubsPro] Store Credit Expired | live | save+reload / PASS | emails-results.json; email-save-results.json |
| email-webhook-disabled | email-settings-preview / arraysubs_admin_webhook_disabled | [ArraySubsPro] Webhook Endpoint Switched Off (Admin) | source-expected | NOT-TESTED / NOT-TESTED | arraysubspro/src/Features/Webhooks/Services/EmailManager.php; arraysubspro/src/Features/Webhooks/Support/Settings.php |
| renewal-grace | renewal-grace / default | New Renewal Flow: Grace Period | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| renewal-reminders | renewal-reminders / default | New Renewal Flow: Email Reminders | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| renewal-settings | renewal-settings / default | New Renewal Flow: Settings | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| webhooks | webhooks / default | New Webhooks endpoints | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| webhook-guide | webhook-guide / default | New Webhooks guide | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| webhook-audits | webhook-audits / default | New Webhook delivery audits | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| feature-log | feature-log / default | Subscription feature log | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| gutenberg-restriction | gutenberg-restriction / default | Gutenberg block restriction inspector | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |
| elementor-restriction | elementor-restriction / default | Elementor restriction controls | source-expected | NOT-TESTED / NOT-TESTED | inventory.md |

## Save editors and shared-form persistence panels

| ID | Editor | Counted independently | Result | Proof / limitation |
| --- | --- | --- | --- | --- |
| logging-preferences | Activity entity-logging preferences autosave | Yes | NOT-TESTED |  Separate POST autosave, not a no-save filter. Four entity toggles only rendered/opened; no persistence test. |
| general | General | Yes | PASS | evidence/settings-general-saved.png  |
| toolkit | Toolkit | Yes | NOT-TESTED |   |
| switching | Plan Switching | Yes | PASS | evidence/switching-saved.txt  |
| refund-settings | Refund | Yes | PASS | evidence/refunds-saved.txt  |
| skip-pause | Skip & Pause | Yes | PASS | mutations.md; commerce-coverage.md  |
| content-gate | Content Gate | Yes | PASS | evidence/content-gate-restored.json  |
| modules | Modules | Yes | PASS | evidence/modules-off-reloaded.txt; evidence/modules-restored.txt Only Audits optional module toggled and original true restored; direct gated/off and restored data tested. |
| logging-settings | Audit logging | Yes | NOT-TESTED |   |
| easy-setup-wizard | Easy Setup Apply Settings | Yes | NOT-TESTED |   |
| plan-wizard | Plan wizard creation | Yes | PASS | commerce-coverage.md  |
| woo-simple | Woo simple billing | Yes | PASS | commerce-coverage.md  |
| woo-variation | Woo variation billing | Yes | PASS | commerce-coverage.md  |
| bundle-config | Bundle configuration | Yes | PASS | commerce-coverage.md  |
| box-config | Box configuration | Yes | PASS | commerce-coverage.md  |
| sub-create | Custom subscription creation | Yes | PASS | commerce-coverage.md  |
| sub-edit | Subscription edit/address | Yes | PASS | commerce-coverage.md  |
| sub-note | Private subscription note | Yes | PASS | commerce-coverage.md TinyMCE real private note persisted after full reload; visible Adding… disabled state. |
| product-features | Product feature configuration panel | Shared parent form | PASS | commerce-coverage.md Persistence PASS via parent Woo Update/reload. Shared parent save excluded from independent count. Baseline empty and customer empty state restored, browser verified. |
| product-redirect | Product Redirect inline panel | Shared parent form | PASS | commerce-coverage.md Native Woo Save Draft/reload PASS; same parent product form, excluded from independent editor count. |
| rule-role | Role Mapping rule save | Yes | PASS | premium-rule-save-log.md  |
| rule-discount | Discount rule save | Yes | PASS | premium-rule-save-log.md  |
| rule-url | URL rule save | Yes | PASS | premium-rule-save-log.md  |
| rule-shop | Shop Access rule save | Yes | PASS | evidence/shop-rule-restored.txt Coordinator restored exact original display name and unchecked state. |
| rule-cpt | CPT / Post Types rule save | Yes | PASS | evidence/cpt-name-saved.txt; evidence/cpt-name-restored.txt Name changed and reloaded, exact original restored and reloaded; enabled=true unchanged. No eligibility-condition mutation. |
| rule-downloads | Downloads rule save | Yes | NOT-TESTED |   |
| rule-comments | Comments rule save | Yes | NOT-TESTED |   |
| rule-purchase-limit | Purchase Limit rule save | Yes | NOT-TESTED |   |
| rule-login-limit | Login Limit rule save | Yes | NOT-TESTED |   |
| rule-styling | Member Styling rule save | Yes | NOT-TESTED |   |
| profile-form | Profile Form configuration | Yes | PASS | evidence/profile-settings-saved.txt  |
| account-builder | My Account menu / custom page | Yes | NOT-TESTED |   |
| checkout-builder | Checkout Builder configuration | Yes | PASS | evidence/checkout-radius9-reloaded.txt; evidence/checkout-radius8-restored.txt Shared configuration editor: radius8→9 real Save with visible Saving, reload9; restore8 + reload; four canvas nodes retained. Canvas/settings not separate save editors. |
| cancellation-flow | Cancellation Flow configuration | Yes | NOT-TESTED |   |
| credit-adjust | Store Credit adjustment | Yes | PASS | evidence/premium-credit-reconciled.png  |
| credit-settings | Store Credit settings | Yes | NOT-TESTED |   |
| feature-settings | Feature Manager settings | Yes | PASS | evidence/feature-usage-off-reloaded.txt; evidence/feature-usage-restored.txt Show Usage Count in Admin true→false→true, Save/reload both directions. Other three flags true and PerSubscription unchanged; original verified. |
| member-edit | Manage Members details | Yes | PASS | access-coverage.md  |
| account-edit | Customer account details | Yes | PASS | access-coverage.md  |
| billing-edit | Customer billing address | Yes | PASS | access-coverage.md  |
| shipping-edit | Customer shipping address | Yes | PASS | access-coverage.md  |
| native-page | Native Page including restriction | Yes | PASS | access-coverage.md  |
| add-payment | Customer payment token creation | Yes | NOT-TESTED |  Stripe form rendered; token save not attempted. Restriction Apply is draft-only and not counted again. |
| email-arraysubs_new_subscription | [ArraySubs] New Subscription settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_renewal_reminder | [ArraySubs] Renewal Reminder settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_renewal_invoice | [ArraySubs] Renewal Invoice settings | Yes | PASS | evidence/email-invoice-saved.txt; evidence/email-invoice-restored.txt Subject real change + reload, preview placeholder resolution, original empty subject restored + reload verified. |
| email-arraysubs_payment_successful | [ArraySubs] Renewal Payment Successful settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_payment_failed | [ArraySubs] Renewal Payment Failed settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_on_hold | [ArraySubs] Subscription On Hold settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_pending_cancellation | [ArraySubs] Subscription Scheduled to Cancel settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_cancelled | [ArraySubs] Subscription Cancelled settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_expired | [ArraySubs] Subscription Expired settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_expiring_soon | [ArraySubs] Subscription Expiring Soon settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_auto_downgrade | [ArraySubs] Subscription Auto-Downgraded settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_retention_discount_accepted | [ArraySubs] Retention Discount Accepted settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_reactivated | [ArraySubs] Subscription Reactivated settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_skipped | [ArraySubs] Renewal Skipped settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_skip_undone | [ArraySubs] Renewal Skip Removed settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_paused | [ArraySubs] Subscription Paused settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_subscription_resumed | [ArraySubs] Subscription Resumed settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_trial_started | [ArraySubs] Trial Started settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_trial_converted | [ArraySubs] Trial Converted settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_renewal_requires_verification | [ArraySubs] Renewal Requires Verification settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_card_expiring | [ArraySubs] Card Expiring settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_admin_new_subscription | [ArraySubs] New Subscription (Admin) settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_admin_payment_failed | [ArraySubs] Payment Failed (Admin) settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_admin_subscription_pending_cancellation | [ArraySubs] Subscription Scheduled to Cancel (Admin) settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubs_admin_subscription_cancelled | [ArraySubs] Subscription Cancelled (Admin) settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubspro_credit_added_email | [ArraySubsPro] Store Credit Added settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubspro_credit_used_email | [ArraySubsPro] Store Credit Used settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubspro_credit_expiring_email | [ArraySubsPro] Store Credit Expiring Soon settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-arraysubspro_credit_expired_email | [ArraySubsPro] Store Credit Expired settings | Yes | PASS | email-save-results.json Coordinator browser roundtrip: saved=true and restored=true; original field restored after real changed subject persisted. |
| email-webhook-disabled | Webhook Endpoint Switched Off email settings | Yes | NOT-TESTED |  Source-expected independent editor; not present in deployed email list. |
| renewal-grace | New Renewal Flow: Grace Period | Yes | NOT-TESTED |  Source SettingsFormPage; new layout unverified although legacy General exposes related policy fields. |
| renewal-reminders | New Renewal Flow: Email Reminders | Yes | NOT-TESTED |  Source SettingsFormPage; new layout unverified although legacy General exposes related policy fields. |
| renewal-settings | New Renewal Flow: Settings | Yes | NOT-TESTED |  Source SettingsFormPage; new layout unverified although legacy General exposes related policy fields. |

## Exact remaining scope

Unvisited live modes: Downloads rule flow editor, Comments rule flow editor, Purchase Limit rule flow editor, Login Limit rule flow editor, Member Styling rule flow editor, Add profile field, New custom account item/page, Checkout Builder add/rename step.

Source-only unvisited modes: New Audit Logging Settings page (job mode/retention), [ArraySubsPro] Webhook Endpoint Switched Off (Admin), New Renewal Flow: Grace Period, New Renewal Flow: Email Reminders, New Renewal Flow: Settings, New Webhooks endpoints, New Webhooks guide, New Webhook delivery audits, Subscription feature log, Gutenberg block restriction inspector, Elementor restriction controls.

Untested independent save editors: Activity entity-logging preferences autosave, Toolkit, Audit logging, Easy Setup Apply Settings, Downloads rule save, Comments rule save, Purchase Limit rule save, Login Limit rule save, Member Styling rule save, My Account menu / custom page, Cancellation Flow configuration, Store Credit settings, Customer payment token creation, Webhook Endpoint Switched Off email settings, New Renewal Flow: Grace Period, New Renewal Flow: Email Reminders, New Renewal Flow: Settings.

## Explicit action outcomes (not additional pages)

| Assertion | Result | Proof / scope |
| --- | --- | --- |
| Known raw box-upload guest request (#19) | FAIL | security-findings.md; evidence/security-box-raw-network.txt; Fresh guest HTTP 200 renders synthetic 8×8 PNG. Host deny rule still missing. Signed plugin download endpoints not tested. Raw asset assertion is not an app template. |
| Pause with required reason (#153) | FAIL | commerce-coverage.md; evidence/commerce-pause-error.png; Required-reason true; no reason input; server refuses pause. State remains Active. |
| Pause optional reason then Resume | PASS | commerce-coverage.md; evidence/commerce-paused.png; Required-reason false contrast. Customer Paused then Active, next date returned with elapsed pause; admin corroborated. Final resume screenshot pending worker. |
| Cancellation wizard repeated Escape after Keep editing (#127) | BLOCKED | premium-findings.md; evidence/premium-cancel-shared-confirm.png; Suspected deployed recurrence; renderer unresponsive. Unsaved draft not persisted; recovered saved baseline. Local fixed-code alignment unverified. |
| Saved access rule: entitled / unentitled / guest | FAIL | access-findings.md; evidence/access-rule-final-persisted.png; Fresh-query A allowed and B/guest denied with no leak; canonical pages deny entitled member (#150). Run-owned page trashed. |
| Private subscription/order owner and B/guest denial | PASS | access-coverage.md; Owner correct. B denied without private details/actions; guest login gate. Download/invoice ownership gap retained. |
| Credit grant → account → checkout debit | PASS | premium-coverage.md; commerce-coverage.md; 1.00 grant, single +1 ledger, 6.31 test charge after 1 credit, single −1 debit and final 0. |
| Narrow block checkout and keyboard | PASS | commerce-coverage.md; 390×844 layout and keyboard address edit sampled; no payment initiated; cart empty/desktop restored. |
| Owned product feature save → ordinary customer My Features | PASS | commerce-coverage.md; evidence/commerce-features-customer-positive.png; A3252 sees tagged benefit for active109453/109511. No visible type labels/columns; sampled feature/type-pattern classes and data-feature-type empty, not exhaustive HTML aliases. Customer REST privacy still NOT-TESTED; no privacy defect observed. Product baseline empty restored and customer empty state reloaded; screenshots commerce-feature-restored.png and commerce-features-restored-empty.png. |
| Scheduled trial cancellation then Undo | PASS | commerce-coverage.md; evidence/commerce-scheduled-cancel.png; evidence/commerce-cancel-undone.png; CancelImmediately false, retention off. Keep dismissal preserves Trial; required reason validation; accepted Other → Trial Pending Cancellation due Nov7; Undo → Trial Nov7. Customer actions stay within subscription template, not new pages. |
| Future invoice/payment queue alignment | PASS | commerce-coverage.md; evidence/commerce-scheduler-109453-pending.png; evidence/commerce-scheduler-109475-pending.png; evidence/commerce-scheduler-109511-pending.png; Browser Pending argument searches: exactly one invoice/payment pair each for109453/109475/109511, invoice six hours earlier; no pending resume/skip for109453. Observed payment spread matches configured six-hour window; deterministic offset explanation corroborated by local source, deployed revision unverified. Due execution/retry/locks still NOT-TESTED. |
| Changed General admin editor narrow + keyboard | PASS | coverage.md; evidence/general-mobile-keyboard-restored.txt; evidence/general-mobile-keyboard.png; evidence/general-mobile-viewport.png; 390×844, document width390, mobile dropdown/footer readable. Focused AllowMixed switch, Space true→false→true locally, Save disabled after original restored; no server save. |
| Optional Audits module off → direct gating → on | PASS | coverage.md; evidence/modules-off-reloaded.txt; evidence/audits-module-off-gated.png; evidence/modules-restored.txt; evidence/audits-module-restored.png; Save/reload false; direct Activity route says Audits is switched off. Sidebar link remains visible, no hidden-menu assertion. Restore true + reload; historical RQA0208 private note retained. Both plugins active. Profile contrast, not additional page count. |
| Immediate customer cancellations and owned queue cleanup | PASS | commerce-coverage.md; commerce-mutations.md; evidence/commerce-admin-cancelled-all.png; evidence/commerce-scheduler-109453-cleared.png; CancelImmediately true/retention off: three customer cancellations109453/109475/109511 showed disabled Cancelling, then Cancelled/end date/no next payment; admin agrees. Pending109525 deleted. Pending queue searches for all four IDs empty. Orders retained without refund; no future owned billing job or financial request pending. Variable-child disposition not independently queried. Cleanup adds no template or editor counts. |

## Workflow and conditional gaps

- Due-time invoice generation/payment execution, failed-renewal retry and locks (future invoice/payment queue alignment PASS for three owned subscriptions)
- Required-reason Pause FAIL (#153); optional-reason Pause/Resume, scheduled cancellation/Undo and immediate cancellations PASS; premium retention-offer acceptance NOT-TESTED
- Rule condition/outcome/timing flows beyond saved names; empty-rule creation and login-limit on-state enforcement
- Protected downloads/invoices and cross-customer download/invoice actions
- Broader feature HTML aliases and customer-facing REST privacy beyond sampled labels/columns, class patterns and data-feature-type attributes; no privacy defect observed
- Custom/hidden account direct gating, new custom page save
- AI written generation inactive-provider prerequisite; retention integrity warning
- Pro Cart Info Editor hide/show downstream behavior (all three hide switches baseline false)
- Alternative gateways, language providers and external provisioning
- Webhook endpoint creation/delivery (source/live mismatch; no external endpoint authorized)
- Broader device/accessibility matrix beyond the sampled narrow checkout/account/changed-admin editor and keyboard paths

## Exact URLs and scenario evidence

Canonical URL templates, ownership, modes and notes are stored in `coverage-register.json`. Concrete fixture URLs, profile values, steps and outcomes remain in `coverage.md`, `commerce-coverage.md`, `premium-coverage.md`, `access-coverage.md` and `emails-results.json`. Those assertions are not added to the page denominator.
