# Orders-independent subscription navigation fix

Implemented and browser-verified 2026-09-18, QA issue 37. Active QA task/day/plan: N/A; targeted fix authorized by the user. Customer incident 36 remains unconfirmed and open.

## Change

`arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php:175` now inserts Subscriptions unconditionally. Orders selects the usual position only. Without Orders, the item is inserted before Log out; if neither item exists, it is appended. The priority-10 hook is unchanged, so Pro's intentional visibility/order customization still runs afterward. No Pro code or permalink handling changed.

## Real browser verification

Site: `http://localhost:10003`, core 2.0.4.1 with the patch, WooCommerce 11.0.1, Pro inactive. Customer 525 (`qa_download_settings_second_20260917`, role customer) was impersonated through the actual Login as Customer UI. Subscription 28240 is active; order N/A (`_parent_order_id = 0`).

The temporary localhost-only MU fixture in `local-menu-probe.php` injected the same priority-5 removal and priority-20 restoration used to reproduce the original bug. It changed request data only. The Pro case loaded the actual editor helper/Hooks class with request-only visibility options; it did not activate the full addon.

| Exact route | Expected / observed result | Screenshot |
| --- | --- | --- |
| `/my-account/subscriptions/` | Subscriptions remains after Orders; active subscription renders | `normal.png` |
| `/my-account/subscriptions/?menu_fix_case=no-orders` | Orders absent; Subscriptions appears before Log out; active subscription renders | `no-orders.png` |
| `/my-account/subscriptions/?menu_fix_case=orders-restored` | Orders removed at priority 5 then restored at 20; Subscriptions remains before Log out; active subscription renders | `orders-restored.png` |
| `/my-account/subscriptions/?menu_fix_case=no-anchors` | Orders and Log out absent; Subscriptions appended; active subscription renders | `no-anchors.png` |
| `/my-account/?menu_fix_case=pro-hidden` | Orders absent and Subscriptions intentionally disabled by the Pro editor; Subscriptions remains hidden | `pro-hidden.png` |

All five screenshots were inspected. After fixture removal, the ordinary direct URL was checked again: menu and active subscription remained visible. Impersonation was exited and the isolated browser session closed. The browser recorded one `AbortError: Transition was skipped` during navigation; the tested menu and portal states rendered successfully. No claim of a console-error-free site is made.

## Cleanup and checks

Temporary MU file removed. Before/after SHA-256 hashes of serialized options matched:

```
arraysubs_settings=c4956cf588808fd84c9bf3ba998e23deb1de6b7aaf14e81d6d97d008e7babd54
arraysubs_myaccount_menu_config=9a89083c3400215e22fa06052ef4c2e09e8ae609ef8483cd3e4fcc2f974ffff9
rewrite_rules=5ebbf2e7d1848812f1c8b5e579c09c90100b291ad2d22f1f4c37f40cfa3fabfd
```

`git diff --check` passed. No PHPCS, lint, dependency installation, version bump, commit, deployment, customer-site change, or subscription/order/billing mutation was performed. This closes the reproduced navigation bug only; it does not establish or resolve the customer's separate direct-URL redirect cause.
