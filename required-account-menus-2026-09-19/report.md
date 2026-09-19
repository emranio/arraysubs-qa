# Required account menus and prefixed endpoints — verification

Completed locally on 2026-09-19 at http://localhost:10003, core 2.0.5 and Pro 1.2.4. Plan: [plan.md](plan.md), issue 41. Customer 525 / qa_download_settings_second_20260917 / customer role; active subscription 28240; order N/A. Admin and customer used isolated agent-browser sessions. Nothing was deployed to either customer site.

Current requirement and correction: required items may be renamed and reordered. Only hiding/removal is locked. The earlier rename restriction and its historical control screenshots are superseded by RM-12 below.

## What caused the reproduced missing builder row

The builder discovers default items by calling WooCommerce's filtered account menu while temporarily bypassing its own customization filter. Other plugins' filters still run. If the stored configuration has no Subscriptions row and another filter removes Subscriptions from that default menu, there is no row for the builder to display. The actual browser reproduction is saved in [removed.png](../artifacts/menu-discovery-20260919/removed.png), with its REST result in [removed.json](../artifacts/menu-discovery-20260919/removed.json).

Counterexamples matter: missing saved Subscriptions alone gets appended from defaults; a saved row remains visible in the builder even when filtered defaults lack it. A renamed row can display Support while retaining its subscriptions ID. These were local request fixtures. They establish possible mechanisms, not which filter or setting exists on thequillsacademy.com or paddlecompass.com.

Core inserts at priority 10, Pro inserts its optional Store Credit/Features tabs at 15, and the editor applies configuration at 99. The new final repair runs after ordinary menu filters, including maximum-priority filters registered earlier. Pro's optional injectors now restore required anchors before inserting their own tabs. Default discovery also preserves the previous bypass state during nested calls.

## Implemented behavior

- Orders and Subscriptions are mandatory, with drag handles and expandable custom-label controls. The chosen labels apply to navigation and page headings. The editor provides no hide or remove control for either. Saved hidden required rows are forced enabled without discarding custom labels; disabled-route guards ignore them.
- Missing required rows are restored in both the builder and customer navigation, retaining the configured order. This works with Pro active, Pro inactive, and customization disabled.
- ArraySubs public account endpoints are `as-subscriptions`, `as-view-subscription`, `as-store-credit`, and `as-features`. Internal WooCommerce logical keys and render hooks stay consistent.
- Custom endpoints automatically gain one `as-` prefix on blur/save and when existing configuration is read. Required/built-in aliases and duplicate canonical slugs are rejected so custom tabs cannot shadow them.
- WooCommerce's own Orders and other built-in URLs keep their existing names. A blank or conflicting Orders endpoint setting falls back to `orders`.
- New rewrite markers trigger a soft refresh after endpoint registration. Pretty routes and public/logical query variables both resolve. Old unprefixed ArraySubs URL aliases are not retained, following the workspace's no-backward-compatibility requirement.

The protection covers WooCommerce menu filtering. Arbitrary PHP that unregisters our hooks afterward, a replacement account template, or CSS hiding links is outside what a menu filter can guarantee.

## Actual browser results

| Case | Observed result / proof |
| --- | --- |
| Initial required controls (superseded by RM-12) | Orders/Subscriptions initially showed REQUIRED and only a drag handle. The missing rename control was corrected in RM-12. [Historical screenshot](required-controls.png). |
| Reordering | Dragged Subscriptions first, saved and reopened; customer menu used that order. Restored original order afterward. [Screenshot](reordered-controls.png). |
| Old hidden/renamed rows | Request-only fixture set both required items disabled and renamed. Builder restored required labels/controls; customer subscription URL rendered the active subscription. [Screenshot](hidden-builder.png). |
| Late external removal | Request-only filters at 20, 1000 and PHP_INT_MAX removed both required keys. Actual builder/customer requests restored each once in saved order. [Builder](removed-items-restored-builder.png), [customer](removed-items-restored-customer.png). |
| Missing saved rows | Request fixture omitted both required rows and removed them through filters. Builder rediscovered both. [Screenshot](missing-builder.png). |
| Earlier removal and extra Pro menus | Priority-11 removal with Features enabled in the request still produced required menus, Store Credit and Features; `as-features` rendered Usages. [Screenshot](prefixed-features-early-removal.png). |
| Blank Orders setting | Request-only blank WooCommerce Orders endpoint still opened Orders at `/my-account/orders/`. This customer has no orders; Orders heading and Browse products rendered. [Screenshot](blank-orders.png). |
| Pro deactivation/reactivation | Actually deactivated Pro in Plugins UI; core menus and active subscription remained with removal filters enabled. Actually reactivated Pro and confirmed the saved order returned. [Free screenshot](free-with-removal-filter.png). |
| Built-in prefixed pages | Opened subscription list, clicked detail 28240, and opened Store Credit and Features. Detail also rendered through `?as-view-subscription=28240`. [Detail](prefixed-detail.png). Browser GET checks returned 200 and correct WooCommerce endpoint detection for public query keys. |
| Custom tab | Created QA Prefix Menu linked to Sample Page. Automatic slug was `as-qa-prefix-menu`; manually typing an already-prefixed slug retained a single prefix. Saved/reopened, then clicked its customer link and observed Sample Page content. [Screenshot](custom-prefixed-content.png). |
| Reserved custom slug | Entered `subscriptions`; blur normalized it to `as-subscriptions`. Save showed “This endpoint is already used by WooCommerce or another plugin. Pick a different slug.” [Screenshot](custom-reserved-validation.png). |
| Disabling | Disabled/saved the custom tab; it disappeared while both required tabs and the portal remained. Its URL showed dashboard content. Disabled/saved the whole menu customizer; required tabs and portal still worked in default order. |
| Cleanup verification | Removed the test tab through the UI, restored customization and original order, removed temporary MU hooks, and reopened the builder/portal. Clicked Orders successfully after scrolling clear of the impersonation bar. [Editor](restored-editor.png), [customer](restored-customer.png). |

Fixtures in [local-menu-probe.php](local-menu-probe.php) only activate for localhost:10003 with an explicit request header; their runtime MU copy was removed. They never wrote the simulated configurations. The earlier discovery fixture was also removed. Screenshots and snapshots were inspected; sticky admin/impersonation bars can overlap content in full-page captures, so click tests used scrolling where needed.

## Source checks and build

- [35 focused checks](pro-source-integration-results.txt) passed against the actual plugin PHP and WordPress hook implementation, with in-memory fixtures. Covers read/save enforcement, nested discovery, REST metadata, hidden flags, empty configurations, reserved IDs/aliases, duplicate normalized slugs, and custom query registration without recursion. [Reproduction script](pro-source-integration.php).
- Independent review exercised 480 saved-order/removal combinations of the shared required-menu helper; all restored the expected order and labels.
- Core production asset build passed (webpack, 2026-09-19 17:03 local). `git diff --check` passed in both plugins. No PHPCS, lint, or dependency installation was run.
- Every modified product source file is below 3,000 lines; largest is MyAccountEditor.jsx at 891 lines.
- Admin browser error list was empty. Customer browser recorded one `AbortError: Transition was skipped` without a source URL; subsequent pages and all required checks rendered successfully. WP-CLI also emitted an existing Elementor nullable-parameter deprecation outside the edited plugins.

## Restored state

[restoration.json](restoration.json) records exact matches for starting active plugins, raw saved menu configuration, and ArraySubs settings. Pro is active, customization is on, and original order is Account details, Dashboard, Subscriptions, Orders, Store Credit, Usages, Addresses, Log out, Downloads, Payment methods. All original rows remain enabled. No test custom tab remains.

Subscription 28240 still rendered Active with the same renewal date; its complete metadata hash matches the 2026-09-18 baseline. No subscription actions were performed. WooCommerce Orders remains `orders`, permalink structure remains `/%postname%/`. Rewrite rules changed intentionally to the prefixed routes; settings-save timestamps advanced through actual UI saves. Both browser sessions were closed after exiting customer impersonation.

## Follow-up: default position (RM-11)

User requested Subscriptions immediately after Dashboard by default, or first if Dashboard is absent. The shared core helper now applies that placement whenever no custom order takes precedence. It rechecks placement after filters, including when a later filter removes Dashboard or adds a leading menu item. Saved custom ordering continues to win.

Verified in the actual local browser as logged-in admin (WordPress user ID 1, administrator role; subscription/order IDs N/A for these navigation tests): empty-config request fixture produced Dashboard then Subscriptions in both storefront and builder; a priority-1000 filter removing Dashboard and prepending Support still left Subscriptions first in both; a request fixture placing Subscriptions last in saved configuration retained that order. Screenshots inspected: [default](default-after-dashboard.png), [Dashboard absent](default-without-dashboard.png), [custom order preserved](default-custom-preserved.png). [Fixture source](default-order-probe.php) never writes settings; its runtime MU copy was removed and the browser session closed. Final unmodified requests restored the original saved order. Before/after menu, settings and active-plugin hashes matched restoration.json exactly.

The focused source suite now passes 39 checks, including late Dashboard removal, a leading third-party item, existing default placement, and an empty menu. Both plugin diff checks passed. Only the shared PHP helper changed in product code for this follow-up; no asset rebuild was necessary.

## Correction: required items remain renameable (RM-12)

The previous implementation incorrectly locked naming as well as visibility. Restored the same expandable Custom Label input used by other default menus. Required rows retain REQUIRED badges, drag handles, and expand/collapse controls; they have no visibility toggle or removal button. Removed forced canonical-label assignment from config reads, sanitization, REST responses and title lookup. Core still enforces required presence, but preserves existing labels and lets Pro restore saved names when a late filter removed the item. Default discovery bypasses saved labels so original-label placeholders and empty-name fallback remain correct.

Actual browser test: admin ID 1 saved Subscriptions as My Memberships and Orders as Purchase History, reloaded the editor, and confirmed both persisted with REQUIRED controls. Customer 525, using the real Login as Customer flow, opened `/my-account/as-subscriptions/` and clicked the renamed Orders link at `/my-account/orders/`. Both H1 headings and browser titles used the saved labels; subscription 28240 remained Active. A localhost request fixture removing both items at 20, 1000 and maximum priority restored both menu entries with their saved names. Clearing labels using keyboard input and saving restored default navigation labels and the Orders page heading. Original labels were then restored by actual UI saves.

Inspected screenshots: [editable required controls](required-rename-controls.png), [renamed Subscriptions page](renamed-subscriptions-page.png), [renamed Orders page](renamed-orders-page.png). Source suite: 47 checks passed, including forced enabled flags with custom names, restored labels after removal, both page title hooks, customization disabled, blank labels, and core label preservation without Pro customization. Core production build passed at 17:44:20; both plugin diff checks passed. No PHPCS/lint or dependency installation. Admin browser error list was empty.

Cleanup at 17:49:42: raw menu, settings, active-plugin and subscription metadata hashes exactly matched restoration.json. No subscription actions were performed. Runtime rename fixture removed, customer impersonation exited, both browser sessions closed. This correction preserves the default Subscriptions position from RM-11 and the as- URL scope.
