# Cart bootstrap: browser evidence

Active QA task #90, scheduled day 2026-10-23 (Asia/Dhaka). Plan `qa/cart-bootstrap-2026-10-23/plan.md`. Actual local site `http://localhost:10013`. Only isolated `cart90-guest`, `cart90-admin` and the later owned customer session are used. Existing customer #224 and its cart are untouched.

## Baseline configuration and authorization

`state-initial.json` preserves the original eight-plugin activation array, modules/settings/menu/profile hashes, theme, visibility and configured Cart/Checkout content/IDs. Cart is page #5755 `/cart-2/`; Checkout is #5756 `/checkout-2/`; active theme is Twenty Twenty-Five. Pro is active. The original local Coming Soon setting is `yes`; root authorized temporary `no` solely for this scoped QA, followed by exact restoration. No other setting was saved.

Owned disposable fixtures supplied by root: ordinary virtual nontaxable product #19879, **QA Cart 90 Ordinary**, $25; subscription virtual nontaxable product #19881, **QA Cart 90 Subscription**, $12 monthly; disposable customer #438. No customer password or auth state is stored in QA evidence. Root owns fixture and credential cleanup.

## Confirmed baseline and corrections

- Configured `/cart-2/` emits repeated `/cart-2/undefinedwc/store/v1/cart` requests that redirect to `/cart/` with HTTP 200. Its header MiniCart DOM is intentionally hidden. No `application/json` interactivity script appears in either live DOM or a fresh same-URL response read through the browser. `before/empty-cart-runtime.json`, `empty-cart-raw-response.html`, `empty-cart-raw-script-inventory.json` and request inventory record this.
- Existing alternate `/cart/` emits `wp-script-module-data-@wordpress/interactivity` with config/state/derivedStateClosures; its header MiniCart is visible. `before/alternate-cart-runtime.json`, `alternate-cart-raw-response.html` and screenshot establish this counterexample.
- The main React Cart is functional. Empty UI renders; ordinary #19879 initially displays a loading skeleton, then a precise wait for the product name succeeds before any store-selector debug read. Its normal Store API request returns 200, cart resolution completes with no errors, and the stable UI shows quantity 1 / $25. `before/ordinary-main-cart-stable.png` is authoritative. Initial networkidle and full-page screenshot artifacts are transitional captures, not evidence that the main Cart remains stuck.
- Baseline ordinary quantity 1→2 changes the line/estimated total $25→$50. Proceed to Checkout opens the normal form only; no field, Place Order or payment was submitted. Checkout baseline has no MiniCart DOM/module and no malformed cart request. Back in Cart, removing the owned item returns empty UI/store items and total 0. Proof: `before/ordinary-quantity-two.png`, `ordinary-checkout-navigation.png`, `ordinary-checkout-runtime.json`, `ordinary-removed.png`, `ordinary-removed-runtime.json`.
- The product's existing **Purchase now** control automatically redirects the accepted add-to-cart to Checkout; this automatic navigation was observed, without submitting checkout. The browser then returned to Cart for the scoped controls.
- Main Cart bundles are present and its DOM has a React container. `wp-api-fetch-js-after` correctly installs root URL and nonce middleware. Exact inline scripts, full 88-script ordering and raw document are saved with sensitive nonce/token values redacted. Absence of `wpApiSettings` or a top-level `storeApiRoot` is not treated as evidence that normal Cart REST bootstrap failed.

The worker paused navigation at root's request after baseline removal; root then merged the reviewed source fix. Coming Soon is temporarily `no`; restoration remains required at finalization.

## Pro verification completed

`after/pro-guest/ordinary-product-drawer.png` and `ordinary-drawer-quantity-two.png` prove normal product header MiniCart remains visible, opens and changes quantity 1→2→1 with $25→$50→$25. `ordinary-cross-sell-before.png` / `ordinary-cross-sell-added-cart.png` prove Cart keeps the legitimate ProductButton and adds owned cross-sell #19887, totaling $25+$7=$32. Runtime inspection confirmed ProductButton's valid shared `/wp-json/` state while MiniCart's unused module is absent. Cross-sell add follows the site's existing automatic Checkout redirect; no form was submitted.

Authoritative settled UI snapshots, focused runtime summaries, requests and results are in `after/matrix/`, summarized by `pro-summary.json`:

| Context | Actual UI coverage | Result |
|---|---|---|
| Pro guest, ordinary #19879 | Empty Cart; product header drawer quantity 1→2→1; main Cart quantity 1→2; Checkout navigation; removal | $25→$50→$0, pass |
| Pro guest, monthly #19881 | Product drawer/main Cart quantity 1→2; monthly renewal/next-charge/duration terms; Checkout navigation; removal | $12→$24→$0, pass |
| Pro customer #438, ordinary | Already-started full ordinary flow completed; same drawer/main quantity and removal checks | $25→$50→$0, pass |
| Pro customer #438, mixed ordinary+monthly | Both products together; ordinary quantity 1→2; Checkout navigation; remove ordinary then monthly | $37→$62→$12→$0, pass |

Positive identity before customer mutation is recorded in `after/matrix/customer-identity.json`: actual currentUserId 438, account email matches root's owned fixture, display name QA Cart 90. No account form was saved. Every Pro case records zero malformed requests, no page exception or cart error, no leftover owned item and draft-order ID 0. Configured Cart's unused MiniCart module is absent; main Cart and legitimate cross-sell module remain functional.

### Browser rendering/capture limitation

After the initial successful screenshots, subsequent screenshot commands stalled. The document timeline advanced but gallery/drawer CSS animations remained at currentTime 0, leaving pointer coordinates in a transition state. Only this worker's affected session/driver processes were stopped; no global browser close was used. The root authorized fresh isolated sessions with agent-browser's supported reduced-motion preference and native focus/Enter activation. Remaining repeated cases use real snapshot/ref UI actions, settled visible text/prices and Store API outcomes; no injected CSS, JS event dispatch, mocked response or runtime patch was used. The initial pointer drawer and cross-sell screenshot proofs remain valid. Matrix records use the recovered `cart90-guest-motion` / `cart90-customer-motion` sessions. Intermediate aborted capture attempts are not treated as completed cases.

## Free verification completed

Root coordinated the actual local Pro deactivation only after the Pro cases completed. Complete fresh documents were used for every remaining Free scenario. Results, settled interactive snapshots, focused runtime data and request inventories are under `after/matrix/`, summarized in `free-summary.json`:

| Context | Actual UI coverage | Result |
|---|---|---|
| Free guest, ordinary #19879 | Empty Cart; normal product drawer quantity 1→2→1; main Cart quantity 1→2; Checkout navigation; removal | $25→$50→$0, pass |
| Free guest, monthly #19881 | Add; monthly renewal/next-charge/duration terms in Cart; removal; automatic Checkout navigation observed without submission | $12→$0, pass |
| Free customer #438, mixed ordinary+monthly | Both items; ordinary quantity 1→2; Checkout navigation; remove ordinary then monthly | $37→$62→$12→$0, pass |

All three Free contexts record zero malformed requests, page exceptions or cart errors, with draft-order ID 0. Both owned carts finish empty. Native customer ID 438 is asserted before each product mutation and recorded in every context summary. The normal product header drawer works under Free; configured Cart's unused MiniCart module remains absent. The mixed Cart keeps legitimate ProductButton functionality. No account form or order/payment submission occurred.

All own browser sessions, including the original/recovered sessions, were closed after the matrix. Only an unrelated `default` browser session was left alone.

## Exact restoration and cleanup complete

Root restored Pro and the exact original eight-plugin activation order, then restored Coming Soon to `yes`. `state-comparison.json` compares `state-initial.json` with `state-final.json`: all nine fields match, including selected visibility/theme/cart-page options, share-key hash, modules, complete ArraySubs settings hash, menu/profile hashes, exact Cart/Checkout page content and versions.

Root's `../cleanup.json` confirms ordinary/subscription/cross-sell products #19879/#19881/#19887 and disposable customer #438 removed and verified absent. The disposable credential file in `/tmp` is also verified absent. No generated order required deletion; all recorded checkout draft IDs were 0 and no Place Order/payment action occurred. Existing customer #224 was not used or changed. The privacy reviewer sanitized the saved raw/bootstrap evidence separately; after-matrix evidence stores only focused DOM/runtime fields and redacted request URLs/admin return-link labels. Browser work and restoration are complete.
