---
id: 43
title: WooPayments redirects ArraySubs subscription account endpoints to dashboard
status: closed
priority: high
created: 2026-09-19T18:28:10.585874+06:00
updated: 2026-09-19T18:38:57.063745+06:00
tags:
    - customer-portal
    - woocommerce-payments
    - bug
class: standard
---

Active QA task ID / scheduled day / plan: N/A (targeted live incident, 2026-09-19).
Site/route: https://thequillsacademy.com/my-account/as-subscriptions/; authenticated agent-browser support session and guest comparison.
Affected user: WordPress ID 3, login arrayhashsupport, administrator support account. The Memberships menu badge shows 1; exact subscription ID pending. Orders: N/A.
Reproduction: sign in, click Memberships (renamed Subscriptions); correct as-subscriptions URL returns HTTP 302 to /my-account/. Reproduces whether subscriptions exist, also as guest. Expected: list/empty state at the subscription endpoint, preserving custom label.
Concrete proof: temporary admin-only wp_redirect trace found WC_Payments_Subscriptions_Disabler::maybe_redirect_account_endpoints -> redirect in woocommerce-payments/includes/subscriptions/class-wc-payments-subscriptions-disabler.php:661. Request vars contained pagename=my-account, as-subscriptions and subscriptions. WooPayments 11.1.0; WooCommerce 11.1.1; ArraySubs 2.0.6 / Pro 1.2.5. Temporary diagnostic was removed and original Pro Hooks.php restored.
Scope: local customer 526 / qa_download_settings_nonmember_20260917 with zero subscriptions successfully opened renamed Memberships using current code without WooPayments. Live Subscriptions is enabled; member-access URL rules are empty. Menu rename/subscription count are not the cause. WooPayments also blocks detail URLs in pre_get_posts when a non-empty subscription ID is present.
Fix plan: in core CustomerPortal, narrowly detach only WooPayments subscription-disabler redirect callbacks on main My Account requests for ArraySubs as-subscriptions/as-view-subscription endpoints, before its priority-1 pre_get_posts callback. Preserve all other WooPayments restrictions and payment behavior. Verify source scope plus actual live list/detail and Orders navigation. Preserve live labels/settings; remove all diagnostics.

Resolution (2026-09-19): Implemented and deployed the core compatibility fix to the live site after confirming its original source exactly matched local HEAD. The protection runs at pre_get_posts priority 0 and removes only the two WC_Payments_Subscriptions_Disabler redirect callbacks for actual ArraySubs account URLs. Unprefixed WooPayments routes, mixed endpoints, checkout/payment-method requests, secondary queries, and unrelated hooks retain their guards. Local WP integration using the actual live WooPayments 11.1.0 disabler class passed 36 checks; existing core/Pro menu integration passed 47 checks. No PHPCS run.

Live browser verification: clicked Memberships from Dashboard and stayed at /my-account/as-subscriptions/ showing Complete Learning Membership subscription #2017 (Pending), owned by support user ID 3. Clicked View Details and stayed at /my-account/as-view-subscription/2017/ with its details. Orders opened /my-account/orders/. Page /as-subscriptions/2/ displayed You have no subscriptions yet. without redirect; ?as-subscriptions=1 displayed the membership. Separate logged-out session remained on as-subscriptions and displayed Login. No order/payment/subscription actions were performed; affected order IDs N/A. Source readback exactly matches local changed core; temporary diagnostic Pro Hooks.php restored byte-for-byte to its original.

Evidence: qa/quills-redirect-2026-09-19/before.png, after-list.png, after-detail.png, empty-page.png, guest-login.png; compatibility-check.php is the read-only WP integration check (supply the WooPayments disabler source path if plugin absent). Local edited core is 623 lines and Pro controller 417 lines; diff checks passed.
