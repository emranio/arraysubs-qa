# Cart compatibility fix and browser verification

Task #90, immediate manual QA on 2026-10-23 Asia/Dhaka. Plan: [plan.md](plan.md). This targeted cycle does not reuse a former lifecycle board or watcher.

## Confirmed problem and correction

On the configured Cart page, WooCommerce renders its intentionally hidden, disabled Mini-Cart fallback and skips shared interactivity state. WordPress nevertheless loads the Mini-Cart module declared by the block metadata. That module imports the cart store and requests `/cart-2/undefinedwc/store/v1/cart`, then retries. The installed WooCommerce 11.1.2 package passed official checksum verification.

Core commit `8a03618` adds `SubscriptionCheckout/Services/MiniCartCompatibility.php`. Its public block-render filter runs after WordPress' metadata enqueue and dequeues only the unused Mini-Cart module on Cart/Checkout when no interactive directive is present. It preserves the returned markup, active storefront drawers, other cart-store users and both literal/JSON interactive namespaces. It requires Free only; no Pro change or asset build is needed. No WooCommerce, WordPress or theme source was patched.

The originally reported main Cart skeleton was transitional. Before the fix, real empty and populated Cart views both settled successfully after waiting for actual content; quantity 1→2 updated USD 25→50 and removal returned empty. A screenshot taken at `networkidle` was insufficient to judge React hydration. The malformed Mini-Cart requests coexisted with the working main Cart; this fix does not claim to repair a separate persistent Cart-loading failure.

Source evidence: [independent review](source-review.md), [Woo/WordPress causal trace](woocommerce-source-audit.md), [package verification](woocommerce-checksums.txt). Browser baseline is in `browser/before/`, including sanitized API bootstrap, raw document/state inventories, requests and screenshots.

## Acceptance evidence

The browser owner is recording actual UI tests in `browser/after/` with isolated guest/customer sessions, snapshot/ref actions and screenshots. The test products are ordinary USD 25, cross-sell USD 7 and monthly subscription USD 12. The cross-sell deliberately retains a legitimate ProductButton cart-store import and shared `restUrl` to check that the correction is narrow.

| Configuration/context | Ordinary cart/drawer/cross-sell | Monthly subscription | State |
| --- | --- | --- | --- |
| Pro, guest | Drawer/cart 25→50→0; cross-sell total 32 | Cart/drawer 12→24→0, monthly renewal terms | Passed |
| Pro, disposable customer 438 | Ordinary 25→50→0; mixed cart 37→62→12→0 | Included in mixed cart, monthly terms retained | Passed |
| Free, guest | Drawer/cart 25→50→0 | Cart 12/month with renewal terms, removed to 0 | Passed |
| Free, disposable customer 438 | Mixed cart 37→62→12→0 | Included in mixed cart, monthly terms retained | Passed |

All seven recorded browser cases passed. Their per-stage snapshots and runtime/request records show zero malformed Cart URLs, page exceptions or Cart errors, correct item quantities/totals, successful navigation to Checkout and removal to empty. The configured Cart's hidden fallback has no Mini-Cart module; normal product-page drawers retain their module/shared state. The cross-sell ProductButton module and valid shared API root remain present on Cart. The earlier pointer screenshots prove adding the cross-sell yields USD 32 on Cart and Checkout; that early click does not have its own adjacent JSON/network trace. The later matrix independently checks its legitimate module/state path. Free monthly quantity2 was not repeated; Pro monthly12→24 and Free ordinary/mixed quantity changes were verified.

The browser owner positively confirmed customer 438, fixture-matching email and display name before customer mutations. See `browser/after/matrix/customer-identity.json`, `pro-summary.json`, `free-summary.json` and [independent browser review](independent-browser-review.md). No payment or full pointer/visual coverage is claimed for the later keyboard-based repeated flows.

Supplemental request-local render checks passed 7/7 with Pro and 7/7 after the actual Free toggle: hidden/removed Mini-Cart output on Cart/Checkout remains identical; the hidden fallback's module is removed; both interactive namespace formats and normal product Mini-Cart remain active. These checks use public Woo page-conditional filters because CLI boot caches URL detection. They supplement actual browser URL tests rather than replace them. See `verify-render-contract.php` and `render-contract-{pro,free}.json`. The Free run records `pro_active=false`; `browser/state-free.json` has the original activation list minus Pro, in the original order.

Browser capture stalled after the first screenshots: animation clocks stopped at zero while the document timeline continued, leaving translated drawer controls unclickable by pointer. The browser owner recovered only this task's sessions and used supported reduced-motion emulation plus native focus/Enter/Space actions for the repeated flows. There were no injected CSS/runtime changes. Saved screenshots already show the normal product drawer, quantity50, settled32 Cart and Checkout; remaining repeated states use actual UI snapshots, DOM/store summaries and request logs. This tool limitation is separate from the malformed-request defect and did not result in a product source change.

## Cleanup and final state

Completed. Both final carts were empty and all Cart90 browser sessions were closed; unrelated browser sessions were preserved. Products 19879/19881/19887 and disposable customer 438 were permanently removed and verified absent. Its WooCommerce customer session was deleted. Temporary fixture/credential scripts were removed. No checkout form was submitted, all recorded draft IDs were 0 and owned-order discovery found no orders to delete. See `cleanup.json` and `owned-orders.json`.

The original eight active plugins and their exact order were restored, and Coming Soon returned to `yes`. The final configuration snapshot exactly matches the initial snapshot, including theme, Cart/Checkout IDs/content, visibility/share-key hash, module switches, ArraySubs settings, My Account menu/profile configuration and versions. See `restoration.json`, `browser/state-{initial,final}.json` and `browser/state-comparison.json`.

The core draft has one completed Mini-Cart fix entry; Pro needs no duplicate entry. The shipped PHP file has 56 lines, with no generated bundle changes. Existing concurrent source/draft changes were preserved. No Composer install, PHPCS, version bump, release, push or deployment was performed. Browser acceptance uses the actual local Twenty Twenty-Five block Cart/Checkout; the source review covers other public hook contexts without claiming an additional classic-theme browser run. Evidence was sanitized as recorded in [privacy-note.md](privacy-note.md).
