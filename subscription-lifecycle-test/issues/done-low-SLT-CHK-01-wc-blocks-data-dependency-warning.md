# WooCommerce dependency warning during block checkout and portal navigation

- **Severity:** low
- **Status:** resolved as a WooCommerce debug-detector false positive; no ArraySubs code defect
- **QA progress task / stage:** board task `#1`, `SLT-CHK-01`, D0
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/001-block-checkout-happy-path-slt-core-buys-slt-daily.md`

## Affected objects and user

| Field | Value |
|---|---|
| Subscription ID | `11959` |
| Order ID | `11949` |
| Product ID | `11927` (`SLT Daily Core`) |
| WordPress user | ID `347`; `slt-core`; `slt-core@example.test`; role `customer` |
| Browser context | agent-browser session `cust-SLT-CHK-01` |
| Routes | `https://mirror-help.arrayhash.com/checkout/` and `https://mirror-help.arrayhash.com/my-account/view-subscription/11959/` |

## Reproduction

1. Log in as `slt-core` in an isolated browser session.
2. Complete the WooCommerce block checkout for `SLT Daily Core` with Stripe test card `4242`.
3. Open the resulting subscription detail page.
4. Read the browser console.

## Expected result

Scripts that access WooCommerce block globals declare their dependencies, and the browser console remains free of dependency-detection warnings.

## Actual result and proof

The console reports the following warning twice during the flow:

```text
[WooCommerce] An inline or unknown script accessed wc.wcBlocksData without proper dependency declaration. This script should declare "wc-blocks-data-store" as a dependency.
```

The checkout Store API request still returned HTTP `200`, the order-received page loaded, and the customer portal rendered the active subscription. Screenshots are:

- `/home/server-manager/slt-evidence/SLT-CHK-01-04-received.png`
- `/home/server-manager/slt-evidence/SLT-CHK-01-06-myaccount.png`

## Scope notes and counterexamples

- This is a console warning, not an uncaught exception or failed request, so it does not block the functional `SLT-CHK-01` pass.
- Exact script ownership is not established by WooCommerce's message; it labels the caller as inline or unknown.
- The same session shows the final checkout request as `POST /wp-json/wc/store/v1/checkout?...` HTTP `200` and the subscription page HTTP `200`.

## Additional occurrence — SLT-CHK-14 (2026-08-02)

The same warning reproduced twice in isolated session `core-CHK14` while buying the lifetime product. Affected objects are subscription `12003`, order `12002`, product `11938`, and WordPress user ID `347` (`slt-core`, customer). Routes included `https://mirror-help.arrayhash.com/checkout/` and `https://mirror-help.arrayhash.com/my-account/view-subscription/12003/`. The checkout still completed for `$49.00`, the lifetime subscription activated, and both customer/admin subscription views rendered correctly. This confirms the finding is not specific to recurring day-based products while remaining functionally non-blocking.

## Additional occurrence — SLT-LIFE-04 (2026-08-02)

The warning reproduced twice again in isolated session `life04` during the block checkout for the finite day/2 product. Affected objects are subscription `12017`, order `12016`, product `11933`, and WordPress user ID `347` (`slt-core`, customer). The Store API checkout completed, order `12016` reached `wc-completed`, and the correct invoice/charge pair was scheduled. This is a third functional counterexample confirming the warning is repeatable across standard recurring and lifetime checkouts but remains non-blocking.

## Additional occurrence — SLT-SYN-05 (2026-08-02)

The warning reproduced twice in isolated session `slt05cust` during the flexible-sync segment-1 block checkout. Affected objects are subscription `12039`, order `12029`, product `11943`, and WordPress user ID `350` (`slt-flex`, customer). The Store API checkout returned HTTP `200`, the order completed for `$14.00`, and the exact three-action sync schedule was installed. This fourth occurrence shows the warning is also independent of whether the Pro flexible-sync checkout path is active.

## Resolution — 2026-08-14

### Investigation and ownership

- A fresh authenticated load of `/my-account/view-subscription/11959/` rendered the subscription and produced neither the warning nor WooCommerce's dependency-detector startup message. This matches `WooCommerce\Blocks\DependencyDetection::TRACKED_BLOCKS`, which only enables the detector on cart, checkout, and mini-cart pages. The earlier portal observation was therefore a warning retained in the session console from a preceding tracked-block page, not a portal-script access.
- The warning was reproduced on a populated block cart containing product `11927`, without submitting checkout or creating data. A separate populated block checkout rendered normally without the warning.
- The live dependency registry records `arraysubs-store-api-cart-subscription` with the transitive `wc-blocks-data-store` dependency. Its source reads only `window.wc.blocksCheckout`; the PHP registration explicitly depends on `wc-blocks-checkout`, whose dependency graph supplies the data store. ArraySubsPro's Paddle block source reads only `wcBlocksRegistry`, `wcSettings`, and `sanitize`, and declares those exact handles.
- In a fresh browser context, every ArraySubs, ArraySubsPro, and WooCommerce Stripe JavaScript asset was blocked before the first cart navigation. The warning still occurred. Blocking only WooCommerce core's `assets/client/blocks/cart-frontend.js` removed the warning (and, as expected, prevented the block cart from mounting). This isolates the observed access to WooCommerce core rather than either ArraySubs plugin.
- WooCommerce's detector obtains the caller from an `Error` stack inside nested `window.wc` proxies. The default Chrome stack limit is 10 frames. A captured failing stack used the remaining frames entirely on repeated detector `Object.i [as get]` proxy calls and never reached the actual WooCommerce bundle, causing the detector to classify the caller as `inline or unknown`. Setting `Error.stackTraceLimit = 100` before navigation allowed the detector to reach and exempt the registered WooCommerce caller, and the warning disappeared. WooCommerce's own `cart-frontend.asset.php` already declares `wc-blocks-data-store`.

### Decision and safety review

This is a false positive in WooCommerce `10.9.4`'s debug-only dependency detector, not a missing ArraySubs dependency. No product or vendor code was changed. Adding an unrelated direct dependency to ArraySubs would not repair the warning, while patching installed WooCommerce vendor code would create an unsupported local fork. The finding has no request, authorization, customer-data, nonce, output-escaping, or payment-integrity impact.

### Verification and cleanup

- Normal block cart rendering remains functional with the correct `$10.00` first charge, `$10.00 every 1 day` renewal line, and `16 August, 2026 (UTC+6)` next charge. Evidence: `/home/server-manager/slt-evidence/FIX-LOW-SLT-CHK-01-cart-control.png`.
- The diagnostic cart was restored to the exact empty state through the UI. Evidence: `/home/server-manager/slt-evidence/FIX-LOW-SLT-CHK-01-cart-restored-empty.png`.
- No checkout submission, order, subscription, payment, email, setting, plugin activation, or persistent data mutation occurred.
