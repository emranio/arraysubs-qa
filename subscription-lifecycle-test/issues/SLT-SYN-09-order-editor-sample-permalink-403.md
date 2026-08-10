# HPOS renewal-order editor sends a failing sample-permalink request

- Severity: low
- Date found: 2026-08-09
- Watch day: D07
- Originating task: `SLT-SYN-09` / card `#106`
- Plan file: `kanban/tasks/106-renewal-execution-after-a-synced-first-charge.md`

## Affected records

- Subscriptions: `12039` and `12172`
- Renewal orders: `13170` and `13273`
- Products: `11943` (`SLT Flex Week Segments`) and `12099` (`SLT Flex Daily Two Seg`)
- Parent orders: `12029` and `12162`

## Affected users

- WP user `350`: login `slt-flex`, email `slt-flex@example.test`, role Customer
- WP user `354`: login `slt-flex2`, email `slt-flex2@example.test`, role Customer

## Gateway, checkout, and settings context

- Gateway: Stripe test
- Checkout type: N/A; these are natural unattended renewal orders inspected in wp-admin
- Non-default settings: none. The frozen suite baseline remained in force and no settings bracket was opened.

## Routes and browser context

- `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13170`
- `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13273`
- Browser context: administrator in isolated session `admin-SLT-SYN-09-D7`

## Reproduction steps

1. Log in to wp-admin as the documented administrator in an isolated browser session.
2. Open the HPOS editor for completed SLT Stripe renewal order `13170`.
3. Record browser network traffic without clicking an edit or update control.
4. Repeat with completed renewal order `13273`.
5. Filter the captured requests for HTTP 4xx responses and inspect the failing request bodies.

## Expected result

Opening a completed HPOS order read-only should not emit a failing auxiliary request. Any background permalink request should either be omitted for HPOS orders or complete successfully.

## Actual result

- Each order page emitted `POST /wp-admin/admin-ajax.php` with `action=sample-permalink` and the numeric HPOS order ID.
- Both requests returned `403 Forbidden` with body `-1`.
- The page itself continued to render, so no user-visible lifecycle failure was observed.

## Concrete proof

- Sanitized request records: `/home/server-manager/slt-evidence/SLT-SYN-09-order-editor-network.txt`
- D07 lifecycle/browser log: `/home/server-manager/slt-evidence/SLT-SYN-09-D07-read.txt`
- Rendered order screenshots:
  - `/home/server-manager/slt-evidence/SLT-SYN-09-02-orders-SUB_W1-2.png`
  - `/home/server-manager/slt-evidence/SLT-SYN-09-02-orders-SUB_2SEG-3.png`
- Captured network request IDs were `403.541` for order `13170` and `417.542` for order `13273`; both were POSTs to the exact `admin-ajax.php` route and both returned HTTP `403` / body `-1`.

## Scope notes and counterexamples

- The renewal behavior itself passed: orders `13170` and `13273` are completed for `$14.00` and `$9.00`, their subscription line items render, reverse linkage is correct, and their natural actions completed via WP Cron.
- The exact order documents, ArraySubs subscription screens, and Scheduled Actions pages loaded; no lifecycle request and no 5xx response failed.
- Browser errors were empty and the console contained only informational JQMIGRATE messages.
- This is scoped as incidental WordPress/WooCommerce HPOS admin behavior with no observed customer or billing impact, not an ArraySubs renewal failure.
- No order, subscription, action, setting, product, user, or non-SLT object was changed.
