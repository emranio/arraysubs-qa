# Source inventory (supporting evidence only)

Local revisions: ArraySubs `65083a2`, Pro `cbc348b` (rechecked current HEAD after initial observation; workspace revisions changed during run). Remote deployment alignment unverified. Local WP-CLI cannot connect to database. Live coordinator observed versions 2.1.0 / 1.3.0.

62 registered route spellings/modes (aliases need live deduplication; disabled/free-only entries do not automatically count in-scope).

- `/overview`
- `/you-may-need`
- `/plans`
- `/subscriptions`
- `/subscriptions/form`
- `/manage-members`
- `/store-credit`
- `/store-credit/history`
- `/store-credit/settings`
- `/cancellation-flow`
- `/renewal-flow/grace-period`
- `/renewal-flow/email-reminders`
- `/renewal-flow/gateway-logs`
- `/renewal-flow/renewal-failures`
- `/renewal-flow/settings`
- `/members-access`
- `/members-access/discount-rules`
- `/members-access/downloads-rules`
- `/members-access/ecommerce-rules`
- `/members-access/url-rules`
- `/members-access/cpt-rules`
- `/members-access/comment-rules`
- `/members-access/purchase-limit-rules`
- `/members-access/login-limit`
- `/member-styling`
- `/checkout-builder`
- `/profile-builder/profile-form`
- `/profile-builder/my-account`
- `/shortcodes`
- `/shortcodes/full-page`
- `/shortcodes/gutenberg`
- `/shortcodes/elementor`
- `/shortcodes/php`
- `/integrations`
- `/webhooks`
- `/webhooks/how-to`
- `/settings/general`
- `/settings/toolkit`
- `/settings/plan-switching`
- `/settings/refunds`
- `/settings/skip-pause`
- `/settings/feature-manager`
- `/settings/content-gate`
- `/settings/emails`
- `/audits/activity-audits`
- `/settings/gateways`
- `/audits/renewal-failures`
- `/audits/portal-action-failures`
- `/audits/access-rule-conflicts`
- `/audits/scheduled-job-logs`
- `/audits/webhooks`
- `/audits/logging-settings`
- `/license`
- `/easy-setup`
- `/help`
- `/modules`
- `/store-credit/:userId?`
- `/manage-members/:userId?`
- `/renewal-flow`
- `/subscriptions/edit/:id`
- `/subscriptions/detail/:id`
- `/subscriptions/feature-log`

## Native/editor/customer additions

WooCommerce product editor: simple, variable/variation, fixed/flexible subscription options, box and bundle configuration; subscription custom create/edit/detail; member/credit detail; plan wizard; email template dialogs; checkout layout/editor/settings; cancellation fullscreen wizard; profile fields/menu items/custom pages; access-rule dialogs; per-content, Gutenberg and Elementor restrictions. Customer endpoints register subscriptions/view-subscription (core), features/store-credit (Pro), plus configured account endpoints. Need live discovery of current links before navigation.

## Risk priorities

1. Known unresolved #55 duplicate block checkout orders/charges (one observed occurrence, uncertain source); safe single-submit corroboration only.
2. #19 box raw uploads need host deny rule; private signed links are plugin fixed but host issue remains.
3. #8 grouped/external products persist subscription meta; #12 priceless subscription renders $0/month.
4. Recent PDF invoice visibility/attachments, account/mobile order actions, translation opt-ins, module route gating and new Webhooks. Local Webhooks registrations are absent from coordinator-observed live menu/modules; deployment discrepancy, not a proven code regression.
5. Premium Checkout Builder/address-group warning, wizard stale modal, narrow list/gateway cards.

## Existing nonclosed issues (statuses preserved)

- [036-paddle-compass-reports-missing-subscriptions-menu.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/036-paddle-compass-reports-missing-subscriptions-menu.md): open — Paddle Compass reports missing Subscriptions menu and account endpoint redirect
- [012-priceless-subscription-product-renders-0-00-month.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/012-priceless-subscription-product-renders-0-00-month.md): open — Priceless subscription product renders $0.00 / month on the storefront
- [137-quick-product-wizard-modal-stays-mounted-across.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/137-quick-product-wizard-modal-stays-mounted-across.md): open — Quick product wizard modal stays mounted across SPA route changes and blocks the next page
- [139-subscription-create-form-warns-uncontrolled-input.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/139-subscription-create-form-warns-uncontrolled-input.md): open — 'Subscription create form warns: uncontrolled input becomes controlled (billing First Name / Address 1)'
- [138-checkout-builder-address-group-field-editor-warns.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/138-checkout-builder-address-group-field-editor-warns.md): open — 'Checkout Builder address-group field editor warns: setState during render (onSave inside setFieldsConfig updater)'
- [019-subscription-box-uploads-are-publicly-readable-by.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/019-subscription-box-uploads-are-publicly-readable-by.md): blocked — Subscription Box uploads are publicly readable by URL (PDF/CSV allowed)
- [148-freemius-recovery-click-rejects-inherited-double.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/148-freemius-recovery-click-rejects-inherited-double.md): blocked — Freemius recovery click rejects inherited double-slash sender labels
- [051-developer-support-stripe-browser-verification.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/051-developer-support-stripe-browser-verification.md): blocked — Developer support Stripe browser verification needs test configuration
- [008-subscription-meta-is-saved-onto-grouped-and.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/008-subscription-meta-is-saved-onto-grouped-and.md): open — Subscription meta is saved onto Grouped and External products
- [055-block-checkout-once-produced-two-concurrent-orders.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/055-block-checkout-once-produced-two-concurrent-orders.md): open — Block checkout once produced two concurrent orders and charges
- [149-edge-embedded-freemius-checkout-stalls-for-default.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/149-edge-embedded-freemius-checkout-stalls-for-default.md): open — Edge embedded Freemius checkout stalls for default and custom coupons
- [110-admin-list-tables-overflow-the-mobile-page-in.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/110-admin-list-tables-overflow-the-mobile-page-in.md): open — Admin list tables overflow the mobile page in Subscriptions and Credit History
- [005-classic-stripe-checkout-writes-two-payment.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/005-classic-stripe-checkout-writes-two-payment.md): open — Classic Stripe checkout writes two payment metadata rows absent from block checkout
- [116-gateway-logs-cards-clip-last-webhook-on-narrow.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/116-gateway-logs-cards-clip-last-webhook-on-narrow.md): open — Gateway Logs cards clip Last Webhook on narrow mobile screens
- [027-quick-product-completion-card-descriptions-run.md](/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/issues/tasks/027-quick-product-completion-card-descriptions-run.md): in-progress — Quick product completion card descriptions run into titles
