# My Account rewrite/permalink history audit

Follow-up to the Paddle Compass report, 2026-09-18. Active QA plan/task/day: N/A. Customer subscription, order and user IDs: unavailable; this follow-up inspected source/history and did not access customer records.

## Finding

Recent rewrite-flush work exists, principally in Pro. No new core subscription rewrite-flush change was found between published free 2.0.0 and 2.0.4. The earlier package comparison recorded identical hashes for the complete CustomerPortal module, including MyAccountHooks. Core's current flush block also has the same behavior.

The most relevant behavioral change was bundled with an older **Pro My Account editor flush fix**: disabled default tabs began redirecting to the dashboard. That is a separate visibility/access policy, not a failure to regenerate permalink rules. It reproduces both customer symptoms when Pro's editor is active, menu customization is enabled, and Subscriptions is disabled; see the previous local browser evidence in `settings-checks/findings.md`. We have not established that the customer runs Pro or has this configuration.

## Changes traced

| Date / repository commit | Change | Relationship to Subscriptions |
| --- | --- | --- |
| 2026-08-18, Pro `a7269f8` | Activation and deactivation delete the stored `rewrite_rules` option and stale editor flush flag, so a later request regenerates routes with the correct active plugins | Regenerates the global route set; does not remove the core subscription menu or unregister its endpoints |
| 2026-08-30, Pro `078e605` | Replace persistent `arraysubs_myaccount_editor_flush_pending` lock with a request-scoped static guard; clear stale flag | Fixes a flag that could permanently suppress later editor flushes after an interrupted request |
| Same August 30 commit | Register newly saved custom endpoints before scheduling the shutdown flush; also hook first-time option creation | Fixes new custom tabs missing from the regenerated rules when configuration is saved after `init` |
| Same August 30 commit | Add `guardDisabledEndpoints()` | **Relevant two-symptom behavior:** disabled default tabs are hidden and redirect to the dashboard; previously hiding alone left their URL reachable |
| 2026-09-03, Pro `0facbc5` | Defer editor flush during settings import and trigger it after successful import commit | Ensures newly imported custom endpoints are registered before the rebuild; this requires an explicit import, not just a plugin update |
| 2026-09-17, Pro `e9e14b7` | Always register `features`, add its WooCommerce query variable, and perform a one-time soft flush using marker `registered-v2` | Fixes **My Features** routing when Feature Manager/menu display is disabled. The rebuild is global and should retain registered core subscription endpoints |
| 2026-09-17, Pro `a074bd8` | Customize account page headings to match saved menu labels | No rewrite-flush change |
| 2026-09-17, core `10ab589` | Remove Support navigation/link handling | No subscription endpoint-registration or flush change |

These are verified source-commit dates, not claimed publication dates of Pro ZIP releases. The August 30 source header was Pro 1.1.3; the September 17 Features change was made while its source header was 1.2.2. A header alone does not establish when a packaged release shipped.

## Why the new Features flush should preserve Subscriptions

- Core's `CustomerPortal/Services/MyAccountHooks.php:36` registers `subscriptions` and `view-subscription` on `init`, default priority 10, independently of subscriptions, Pro or menu visibility.
- Pro's corrected Features service schedules its soft flush at `init` priority 99, after endpoint registration.
- WordPress's actual local `wp-includes/class-wp-rewrite.php:1873` postpones a flush requested before `wp_loaded` until `wp_loaded`. It then refreshes the route set from registered rewrite structures/endpoints; it does not rebuild only the caller's feature.
- A soft flush avoids the `.htaccess`/IIS rules write. None of these functions removes the `woocommerce_account_menu_items` callback or edits the menu configuration.

Consequently, the source does not support a normal Features flush wiping out the subscriptions route while core is correctly loaded. An external filter or request that lacks core registration could produce a different route set, but that is not demonstrated on the customer's site.

## Existing core limitation

Core's initial flush is guarded by `arraysubs_flush_rewrite_rules === 'done'` (`MyAccountHooks.php:57`). That marker is not tied to the installed plugin version, is not reset by core activation code, and has no route-integrity check. Once it is `done`, the plugin's initial-flush path will not repair a nonempty stale rule set merely because core was updated. This behavior predates the reported update; the marker was renamed to its current name in January 2026 and the underlying once-only design is older.

Deleting the whole `rewrite_rules` option is different: WordPress's `wp_rewrite_rules()` regenerates an empty/missing rule set itself, regardless of the ArraySubs marker. Pro activation/deactivation uses that deletion path. Thus the core marker does not prevent WordPress's lazy rebuild.

Core also has `routePrettyEndpointFallback()` (`MyAccountHooks.php:105`), introduced May 27 in `fc98694` and present in both published releases. When WordPress receives an appropriate request under the configured My Account page path, this maps `subscriptions` or `view-subscription` into the account query variables even when ordinary rewrites did not. It cannot help a request that never reaches WordPress, an incorrect configured account path, or a later callback that redirects/removes those variables.

Stale rewrite rules therefore remain a routing diagnostic, but **do not explain a missing PHP navigation item**. The previous public observation that `/my-account/?subscriptions=1` also returns to the dashboard further weakens a pretty-permalink-only explanation; that observation was made as a guest, not as the affected customer.

## Practical interpretation

1. If Pro is active, first inspect the saved Subscriptions visibility. A successful rewrite flush cannot override the disabled-endpoint redirect guard.
2. Saving WordPress Settings → Permalinks once without changing the structure is a reasonable repair/check for stale routes. It is not a confirmed fix for this incident and should not be described as restoring a menu removed by a filter or saved setting.
3. If routing still fails, inspect the affected request's registered endpoints, matched rule/query variables and redirect callback. Compare pretty and query-string endpoint forms while logged in as the affected customer.
4. Keep the independently reproduced Orders-dependent menu bug (QA 37) separate: it can hide Subscriptions even with good routing.

## Verification limits and state

This follow-up verified Git patches in both repositories, the earlier published-package hash evidence, all ArraySubs rewrite/flush call sites, the Pro import/save paths, and WordPress's rebuild/defer implementation. No new browser pass is claimed: the same local site at `http://localhost:10003` is currently stopped (`ERR_CONNECTION_REFUSED`), and its MySQL socket is absent. The existing Pro hide-and-redirect browser reproduction remains the evidence for that behavior. The attempted browser session was closed.

No plugins were activated/deactivated, no permalink flush was run against the local database or customer site, and no code or saved settings were changed. This is not a complete archived-plugin upgrade simulation. The customer incident remains unconfirmed/open.
