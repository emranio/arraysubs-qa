# My Account and storefront boundary verification

Scope confirmed by user on 2026-10-23 after the member-details fix: check My Account/storefront pages and their endpoints for Free/Pro ownership, unavailable modules, account ownership and internal feature-type privacy. This is a fresh targeted scope, tracked as QA task #86; no old lifecycle QA tasks or watcher schedule are reused.

Site: real local WordPress at http://localhost:10013. Admin authentication: documented Local auto-login. Customer authentication: existing Login as Customer feature, in a separate agent-browser session. Existing QA customers and subscriptions will be recorded in the observations. Do not use stale customer passwords.

1. Record plugin activation state, module states, My Account menu/profile configuration, and current customer/cart context.
2. With Pro active, inspect My Account dashboard, subscriptions/detail, orders, downloads, addresses/edit-account, and available Pro account pages. Inspect representative shop/product/cart pages and read-only endpoints.
3. Temporarily deactivate only local Pro. Reload complete documents and repeat core My Account/storefront paths. Visit known Pro account URLs to inspect fallback behavior, and inspect requests/errors.
4. Audit code for saved custom endpoint slugs, disabled modules and customer scripts. Supplement browser observations with real WordPress REST permission checks that do not run lifecycle or payment mutations.
5. Restore exact activation/module/menu/profile state, log issues with required fixture/context fields, and fix confirmed in-scope defects individually with browser reverification.

Do not submit storefront orders or payments, cancel/pause/renew/switch subscriptions, or clear saved settings. Do not switch premium product modules off through their saved settings; they can legitimately change product statuses. A request-local module filter is acceptable for supplemental permission/render checks and must be labelled as such, not a browser result. Do not deploy to the remote site.

Evidence: screenshots plus snapshot/network/error observations in this directory. Code audit findings and browser coverage must be distinguished. A pass is limited to the checked scenarios; this is not full checkout/gateway lifecycle QA.

Supplemental fix verification may create and delete isolated manual-payment pending orders, customers and draft/manual subscriptions through WP-CLI. They must be identified in evidence, never sent to gateways, and removed in cleanup. Existing customer/subscription lifecycle state remains untouched.
