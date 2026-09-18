# Settings and conditions that can affect the subscription portal

Investigated 2026-09-18. Follow-up to the Paddle Compass incident, QA issue 36. Active QA plan/task/day: N/A; targeted investigation. The customer's installed settings and authenticated runtime remain unavailable. These are verified mechanisms and local reproductions, not a confirmed diagnosis of that installation.

## Exact two-symptom match: Pro My Account editor

Admin location: **ArraySubs → Profile Builder → My Account** (`#/profile-builder/my-account`, page title “My Account Editor”).

All three conditions are needed:

1. The Pro My Account editor's hooks are actually loaded.
2. **Enable My Account menu items customization** is on: `arraysubs_settings.profile_builder.my_account_menu_items_enabled = true`.
3. The saved default Subscriptions item is disabled: `arraysubs_myaccount_menu_config` contains `id: subscriptions`, `type: default`, `enabled: false`.

`arraysubspro/src/Features/MyAccountEditor/Services/Hooks.php` applies menu configuration at priority 99. `applyMenuConfig()` (line 127) omits disabled configured items. `guardDisabledEndpoints()` (line 420) redirects requests for a disabled default endpoint to the account dashboard. The guard recognizes both endpoint identifiers and WooCommerce endpoint slugs and does not require a logged-in visitor.

The actual Pro hooks reproduced **both** symptoms in the local customer browser: no Subscriptions navigation item, and a request to `/my-account/subscriptions/` ended at `/my-account/` with the ordinary dashboard. The query-string form `/my-account/?subscriptions=1` was redirected too.

Two recovery controls worked independently in the fixture:

- Set the Subscriptions item's `enabled` to true: menu and portal returned.
- Turn **Enable My Account menu items customization** off: menu and portal returned while the disabled item remained in the injected saved configuration. This master switch disables customization, not the free subscription portal.

With Pro inactive and its hooks absent, the same saved disabled item had **no effect**. The local site's real saved configuration already contains a disabled Subscriptions entry; the free portal still works with that configuration.

Published free 2.0.0 did not contain the My Account editor implementation/provider. The implementation moved from core to Pro before 2.0.0. Therefore the proposed “hidden in free 2.0.0, then hidden after updating free to 2.0.4” path is not supported by the packages. Older settings can survive, but require an active consumer, such as Pro, to affect the portal. Reactivating Pro or re-enabling customization can apply previously saved visibility again. A selected Easy Setup `myaccount_builder` import can also import the master switch and menu configuration (`SetupController.php`, settings/option maps and option sanitization); a normal version bump does not itself enable a saved disabled item or invent that configuration.

## Other settings: which symptom each explains

| Setting or condition | Missing menu? | Direct URL fails or redirects? | Evidence and limits |
| --- | --- | --- | --- |
| Pro editor active, customization on, Subscriptions disabled | Yes | Yes, dashboard redirect | Both locally reproduced; strongest single configuration match |
| Old disabled menu configuration with Free only | No | No | Locally reproduced counterexample |
| Member Access → URL: enabled matching deny/redirect rule | No, by itself | Yes | Active customer redirected locally; menu remained visible |
| URL rule's subscription condition passes, but its drip schedule is not met | No, by itself | Yes | Active subscription plus a 3650-day delay reproduced dashboard redirect; menu remained |
| Member Styling: matching enabled custom CSS hides subscription navigation | Yes, visually | No, by itself | Locally reproduced; menu element had `display: none`, while direct portal showed active subscription |
| Orders missing when core builds its menu | Yes | No, by itself | Browser-reproduced separately; QA issue 37 |
| My Account page/content is gated | Can replace the whole account content | Can deny or redirect | Code-reviewed; normally broader than selectively removing Subscriptions |
| Theme/account-widget navigation or external menu/redirect callbacks | Possible | Possible | External implementation must be inspected; not diagnosed on Paddle Compass |
| Stale rewrite rules, incorrect account-page assignment or endpoint-handler interference | Not sufficient alone | Possible | Routing checks, not an explanation for menu filtering; no customer root cause established |

### Member Access → URL (Free)

The module must be enabled (`members_access.enabled`), with an enabled entry in `members_access.url_rules` whose path pattern matches the request and whose exclusions do not match. Lower priority numbers run first. The first matching rule evaluates its access conditions and schedule; failing either applies its denial action. `action: redirect` can send the visitor to the rule's `redirect_url`, or `members_access.default_redirect_url` if no explicit destination exists. A destination of My Account reproduces the reported landing page.

**An active subscription does not guarantee access under an arbitrary rule.** It can still fail a required role, product/variation, other condition, an AND combination, or the schedule. The local role-mismatch and delayed-schedule tests both used an active subscription.

For guests, `members_access.require_login` can send a matching request to login before rule evaluation. Logged-in customer impersonation does not bypass access checks. A per-post restriction takes precedence over URL rules. URL matching strips query strings, so a narrow `/my-account/subscriptions` rule does not by itself match `/my-account/?subscriptions=1`.

Source: `arraysubs/src/Features/MembersAccess/Services/UrlRestrictor.php` and `Hooks.php`. The published 2.0.4 URL restrictor is byte-identical to the locally tested implementation. Changes in the current module Hooks file concern downloads, not URL-rule registration.

### Member Styling (Free)

The module (`member_styling.enabled`) and rule (`member_styling.rules[*].enabled`) must be enabled; its conditions and schedule must qualify. A custom CSS rule such as `.woocommerce-MyAccount-navigation-link--subscriptions { display: none !important; }` hides the item without removing its PHP registration or disabling the endpoint. The same mechanism can come from theme/custom CSS outside ArraySubs.

Source: `arraysubs/src/Features/MemberStyling/Services/StyleHooks.php` and `StyleEngine.php`, both byte-identical to published 2.0.4. A CSS rule combined with a separate access/redirect rule could produce both symptoms, but that combination is not established on the customer's site.

### Orders dependency and WooCommerce settings

In WooCommerce → Settings → Advanced, a blank **Orders** account endpoint (`woocommerce_myaccount_orders_endpoint`) removes Orders before ArraySubs receives the menu. ArraySubs only inserts Subscriptions after an existing `orders` key. A theme/snippet can also remove Orders early and restore it later, leaving the final menu with Orders but without Subscriptions. That exact callback ordering was reproduced in the original investigation.

Source: `woocommerce/includes/wc-account-functions.php:94` and `arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php:175`. Hiding Orders later through the Pro editor does not alone cause this insertion failure, because the core filter has already run. The direct portal still works in the reproduced Orders case.

### Content/page restrictions and account routing

Review a restriction on the assigned My Account page itself (`_arraysubs_restrict_config`, enabled conditions and denial action), Member Access post-type rules that include that page, or a restrictive shortcode/builder wrapper around the account content. `PostRestriction::maybeRedirect()` can redirect denied singular pages; content filtering can replace their content. Admin bypass settings can make an administrator's view differ from an impersonated customer's. These are code-reviewed candidates, not locally reproduced causes of the selective two-symptom report.

Also verify WooCommerce's assigned My Account page, its account shortcode/widget/template, and external callbacks modifying `woocommerce_account_menu_items`, query variables, endpoint actions or `template_redirect`. A static custom menu can omit the new item even when the core menu filter works. WooCommerce falls back to the dashboard when no endpoint render action matches the current query variables.

## Settings that do not disable the portal

- Active subscription status, count, gateway or renewal date do not gate the core menu/endpoint registration. Ownership/status affects rows and detail access, not whether the standard navigation item is added.
- Customer cancellation, pause, skip and switching permissions control portal actions, not portal registration.
- Feature Manager's “show in My Account” setting controls its own feature display/menu, not Subscriptions. Store Credit similarly owns its own item.
- Custom profile fields and avatar settings affect profile editing, not the subscription endpoint.
- Toolkit admin-bar/admin-area/login-page controls do not selectively remove Subscriptions from a logged-in account menu. Login redirects can affect guest observations.
- The standard free core has no separate “enable subscription portal” switch or configurable Subscriptions endpoint slug. Its provider is included unconditionally; no Pro license is required for the free portal.

## Local browser evidence and cleanup

Environment: `http://localhost:10003`, WooCommerce 11.0.1, current core 2.0.4.1. Customer 525 (`qa_download_settings_second_20260917`, role customer), subscription 28240 active, order N/A (`_parent_order_id = 0`). Used the real Login as Customer flow. Settings test URLs were `/my-account/subscriptions/?portal_case=<case>`; the disabled Pro case was also checked with `?subscriptions=1`. Expected and actual outcomes are in the matrix above.

The preserved `local-settings-probe.php` temporarily substituted options only during localhost requests for users 1/525. It loaded the actual Pro editor helper and Hooks class for the Pro cases; the full Pro plugin stayed inactive. Free URL and styling cases used their real loaded services. This is a focused browser integration test of those settings and hooks, not a full Pro activation, complete archived-plugin installation, saved-admin-form test, or customer-site test.

Screenshots inspected:

- `free-saved-hidden.png`: old disabled menu setting with Free only; portal works.
- `pro-hidden-dashboard.png`: both symptoms with Pro hooks and the item disabled.
- `pro-customization-off-restored.png`: master customization switch off restores portal.
- `pro-visible-restored.png`: enabling the item restores portal.
- `url-role-redirect-menu-present.png`: role-based denial redirects; menu remains.
- `url-schedule-redirect-menu-present.png`: schedule-based denial redirects; menu remains.
- `css-hidden-endpoint-still-works.png`: CSS hides navigation; active subscription still renders.

The temporary MU plugin was removed. Its cookie was cleared, the ordinary subscription URL was verified working again, impersonation was exited and the investigation browser session closed. The before/after SHA-256 hashes of serialized saved options were identical:

```
arraysubs_settings: c4956cf588808fd84c9bf3ba998e23deb1de6b7aaf14e81d6d97d008e7babd54
arraysubs_myaccount_menu_config: 9a89083c3400215e22fa06052ef4c2e09e8ae609ef8483cd3e4fcc2f974ffff9
```

No product-code fix, saved setting change, customer-site mutation, or subscription/order/billing change was made. QA issue 36 remains open because the customer's actual configuration is unknown.
