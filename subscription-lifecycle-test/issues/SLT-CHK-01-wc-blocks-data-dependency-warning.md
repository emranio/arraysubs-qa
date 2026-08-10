# WooCommerce dependency warning during block checkout and portal navigation

- **Severity:** low
- **Status:** open; documented, non-blocking
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
