# Paddle Compass: missing customer subscription portal

Investigated 2026-09-18 (Asia/Dhaka). Customer report: 2026-09-17T18:51:27.435Z. Scope: investigation of one reported incident, not a new scheduled QA cycle. Active QA task, scheduled day and plan: N/A.

## Conclusion

The evidence points toward site-specific handling of the subscription menu/endpoints. The exact callback, setting or component responsible on Paddle Compass is **not confirmed** because authenticated access, installed-plugin details and active snippets are unavailable.

Tracking: QA issue **36** records the unconfirmed customer incident and remains open. QA issue **37**, the independently reproduced Orders-dependent menu bug, was subsequently fixed at the user's request and browser-verified: [fix verification](menu-fix/verification.md).

Follow-up settings audit: [settings and conditions](settings-checks/findings.md). The actual Pro editor hooks with customization enabled and Subscriptions disabled reproduced both the missing menu and dashboard redirect in a local customer browser. Disabling customization or enabling the item restored both. Free URL-access rules reproduced only the redirect; Member Styling CSS reproduced only the hidden menu. These mechanisms do not establish the customer's configuration.

Follow-up permalink history audit: [rewrite/flush changes](rewrite-audit.md). Recent Pro changes repair custom-tab and My Features rewrites; no changed core subscription flush was found between the reported free releases. The August 30 editor flush-fix commit also introduced disabled-default-endpoint dashboard redirects. Core's old once-only flush marker does not validate stale rules on upgrades, but that limitation does not itself remove navigation and core includes a pretty-endpoint fallback. A new browser pass was unavailable because the local site was stopped during this follow-up.

The portal is a free core feature, loaded without a Pro license or an enable-portal switch. Subscription status does not control whether its menu item is added. Two active subscriptions therefore do not establish that account routing and menu filters are working.

There is no direct Customer Portal code change between the published 2.0.0 and 2.0.4 packages. This rules out a change in those files, not every possible interaction elsewhere in the update or on the customer's site. A full 2.0.0-to-2.0.4 site/database upgrade was not simulated.

## Release inspection

Downloaded the actual published packages:

- https://downloads.wordpress.org/plugin/arraysubs.2.0.0.zip
- https://downloads.wordpress.org/plugin/arraysubs.2.0.4.zip

All six files in `src/Features/CustomerPortal/` are byte-identical between these ZIPs, including the provider, both services, customer REST controller and both templates. The shared `AbstractLoader.php` and Login as User `Impersonator.php` are also identical. Hashes are saved in `release-hashes.json`.

The boot list unconditionally includes `Features\CustomerPortal\Provider`. In `MyAccountHooks`, the constructor registers the menu filter, WordPress endpoints, WooCommerce query variables, endpoint render callbacks and pretty-URL fallback. None is conditioned on having an active subscription, a payment gateway or a Pro license.

In both published releases, `addMenuItems()` adds **Support** and **Subscriptions** in the same callback. Support is inserted before Log out; Subscriptions is inserted after Orders. If the customer's Support link points to `https://support.arrayhash.com/`, that is strong evidence this callback ran. The reported menu then favors later removal/replacement, or an Orders item that was absent when this callback ran. The Support link destination has not been verified in the customer's authenticated session.

The current development version, 2.0.4.1, has removed this Support link and changed portal presentation. Those changes must not be confused with the published 2.0.4 code.

WooCommerce's published 11.1.0 `includes/wc-account-functions.php` was also compared with the locally installed 11.0.1 file: identical. This is a narrow comparison, not a complete compatibility certification for WooCommerce 11.1.0.

## Public observations on Paddle Compass

No customer login, private API access or site mutation was performed.

| Request | Observed result as a guest |
| --- | --- |
| `/my-account/subscriptions/` | HTTP 302 to `/my-account/`, then login form |
| `/my-account/?subscriptions=1` | Browser ends at `/my-account/`, then login form |
| `/my-account/view-subscription/` | Browser ends at `/my-account/`, then login form |
| `/my-account/orders/` | HTTP 200; browser retains `/my-account/orders/`, with login form |

The subscriptions redirect reports `X-Redirect-By: WordPress`, `Cache-Control: no-cache, must-revalidate, max-age=0, private`, and `X-Proxy-Cache: BYPASS`. Sanitized headers are in `public-http-evidence.json`. This particular response was not served from SiteGround's page cache. It does not rule out other caching or a distinct logged-in behavior.

Redirecting the query-string form too makes a rewrite-only explanation less convincing. Public guest behavior is supporting evidence only: a login restriction could legitimately redirect guests, and we cannot attribute the customer's logged-in redirect from these observations alone.

The account page loads ArraySubs portal assets. The public REST index advertises the core customer subscription action routes. Together these show core portal components are present; asset loading alone cannot prove every endpoint hook is registered because a separate service also enqueues those assets.

The public REST index includes `code-snippets/v1`, plus SiteGround optimizer/security namespaces. Kadence theme assets are present. These are investigation leads, not proof of culpability. No snippet contents were accessed. The index did not advertise ArraySubs Pro's `/myaccount-editor/...` routes, reducing the evidence for an active Pro account editor without definitively proving Pro is absent.

## Local browser verification

The workspace's running site was discovered at `http://localhost:10003`, confirmed by Local's configuration and WordPress `siteurl`. The older documented port 10013 was unavailable and now belongs to another Local project.

Environment: WordPress local site; WooCommerce 11.0.1; core development version 2.0.4.1; Pro inactive. Existing customer 525, login `qa_download_settings_second_20260917`, role `customer`; active subscription 28240. This fixture was not created or modified for this investigation. Parent order: N/A; `_parent_order_id` is 0. No order action was performed.

Used the actual **Login as QA Download Settings Second** UI, then opened the account pages in an isolated browser session. For version-specific checks, a temporary localhost-only MU plugin preloaded the unmodified published release's `MyAccountHooks.php`; that class renders the templates from its corresponding release archive. The rest of the runtime remained the current local core. This is a focused release-portal integration check, not a complete installation of either archived plugin version.

| Case | Result | Evidence |
| --- | --- | --- |
| Published 2.0.4 portal class/templates | Subscriptions 1 menu; active subscription 28240 and renewal date render | `screenshots/released-204-customer.png` |
| Published 2.0.0 portal class/templates | Same successful menu/list behavior | `screenshots/released-200-customer.png` |
| Priority-5 filter removes Orders; priority-20 filter restores Orders | Exact reported menu sequence, with Support but no Subscriptions | `screenshots/orders-restored-no-subscriptions.png` |
| Same filter sequence, direct subscriptions URL | Menu still missing, but active subscription list renders correctly | `screenshots/menu-missing-endpoint-still-works.png` |

Screenshots were inspected. The temporary probe source is preserved as `portal-probe.php` for reproducibility. Its runtime installation was removed after testing. No settings, subscriptions, orders or billing schedules were deliberately changed. No customer-site writes, plugin release, commits, lint, PHPCS or dependency installation were performed.

## Confirmed core weakness, separate from the unconfirmed incident cause

`arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php:175` only inserts Subscriptions inside `if ($key === 'orders')`. If another filter removes Orders before priority 10, Subscriptions disappears too. Another filter can restore Orders later, so seeing Orders in the final menu does not exclude this path.

This flaw exists in both published versions and was present in development source at the time of investigation. The user subsequently authorized fixing it: development source now inserts Subscriptions even when Orders is absent, before logout or at the end, while preserving intentional later menu customization. The fix passed local browser verification. This bug is **not sufficient to explain the reported direct-URL failure**, as the browser counterexample proves. No customer-site deployment was performed.

## Pro behavior reviewed

`arraysubspro/src/Features/MyAccountEditor/Services/Hooks.php:127` applies saved menu visibility at priority 99. A saved default item with `id=subscriptions` and `enabled=false` is omitted. `guardDisabledEndpoints()` at line 420 redirects disabled endpoint requests back to the account dashboard.

This configuration can explain both symptoms when the Pro editor is actually booted and menu customization is enabled. It was subsequently reproduced with the actual Pro hooks in a local browser; see the follow-up settings audit for the method and limitations. It is not a customer-site diagnosis. The local site even contains an old disabled Subscriptions menu entry, but with Pro inactive the free portal works; a leftover option alone does not hide it.

## Next evidence that would resolve the incident

Follow-up hypothesis checked: could a formerly free Profile Builder setting survive the 2.0.0 → 2.0.4 update and keep Subscriptions hidden? Profile Fields and My Account Editor moved from core to Pro in commit `261682d` on 2026-08-18, before 2.0.0. The actual published 2.0.0 ZIP contains neither implementation nor its boot provider. An older saved `arraysubs_myaccount_menu_config` option can survive upgrades, but free 2.0.4 does not apply its visibility rules. The local site's existing disabled Subscriptions entry with Pro inactive is a concrete counterexample: its free portal is visible. The hypothesis is viable only if code applying that saved setting remains active, such as Pro's editor. Pro's redirect for disabled default endpoints was added in commit `078e605` on 2026-08-30; the older free editor hid the menu but did not contain this redirect guard. No claim is made that the customer used either historical configuration.

1. Obtain WooCommerce → Status → Get system report, to identify the active plugins, versions and account-template overrides. Ask whether ArraySubs Pro is installed and active.
2. Inspect enabled Code Snippets and account/menu customization for `woocommerce_account_menu_items`, `subscriptions`, `view-subscription`, `template_redirect`, and redirects to My Account. A snippet written for another subscription plugin could use a subscription lookup that does not recognize ArraySubs records, but this specific scenario remains hypothetical.
3. If Pro is active, check My Account Page Builder's Subscriptions visibility and `arraysubs_myaccount_menu_config`. Do not infer that a free customer needs to buy Pro or enable a paid feature.
4. With an authorized staging copy, trace the menu before/after each registered callback and capture the redirect callback/backtrace for the logged-in affected customer. Verify the endpoint query variables and render hooks in that same request. This identifies the owner rather than guessing from plugin names.

Saving permalinks can repair stale rewrite rules, but cannot by itself make a PHP menu filter add a missing item. It should not be presented as a confirmed fix for this two-symptom report. There is no evidence here requiring subscription recreation, cancellation or database restoration.

## Suggested customer reply (draft, not sent)

Hi Tonya,

Thank you for the detailed report. The subscription portal is included in the free plugin and should appear automatically; there is no separate portal setting you need to enable. An active subscription is not required for the menu item itself to appear.

We compared the published 2.0.0 and 2.0.4 portal code and verified the 2.0.4 portal code with an active customer account. We also reproduced both symptoms when the Pro My Account editor has Subscriptions disabled, but have not confirmed what is responsible on your installation. Your site's subscription URLs appear to be redirected, so we need to check its account-menu and endpoint handling.

Could you send the report from WooCommerce → Status → Get system report, and let us know whether any active Code Snippets customize My Account, hide subscription tabs or redirect subscription pages? If you have ArraySubs Pro active, please also check whether Subscriptions is enabled in its My Account Page Builder.

Your report indicates that the subscription records and billing data remain present. Please leave those records in place while we identify what is hiding the portal.
