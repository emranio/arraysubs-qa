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

## Resolution — 2026-08-14

### Investigation and root cause

- Reproduced the report on current WordPress `7.0.2` and WooCommerce `10.9.4` with renewal order `13170`. The initial heartbeat submitted a blank `wp_autosave` for order `13170`; WordPress returned `success:false`, after which core `post.js` sent `action=sample-permalink&post_id=13170` without a nonce and correctly received `403 / -1`.
- Reproduced the identical chain on unrelated HPOS order `21180`, proving this was a real WooCommerce/WordPress HPOS integration defect rather than renewal logic, Stripe data, or an ArraySubs-generated request.
- WooCommerce's HPOS editor deliberately enqueues core `post.js` for third-party compatibility and `autosave.js` for its lost-connection UI. The rendered page is `form#order`, not `form#post`; it exposes `#post_ID` for compatibility but has no `#title`, `#edit-slug-box`, or `#samplepermalinknonce`. Those partial post-form semantics caused both requests.
- Core and Pro contain no sample-permalink request implementation. The nonce rejection itself was correct and was not weakened.

### Fix and dependency/security review

- Added `arraysubs/src/Features/SubscriptionAdmin/Services/HposOrderEditorHooks.php` in shared core. On a validated HPOS order edit request only, it suspends WordPress's unsupported local/server post autosave engines and detaches only `after-autosave.update-post-slug` after `autosave.js` loads.
- The guard keeps WooCommerce's `post`, `autosave`, and `heartbeat` scripts enqueued. Order locking, the lost-connection notice/listeners, meta boxes, third-party script availability, and all order controls therefore remain present.
- The server gate requires the exact HPOS order editor hook/screen/action, sanitizes the numeric order ID and action, resolves a real `WC_Order`, and repeats WooCommerce-compatible edit/manage capability checks. It adds no endpoint, output data, persistence, or authorization bypass.
- The fix covers all HPOS orders because the invalid form/script combination is screen-wide, while classic post/product editors are excluded. No Pro code or core/Pro contract changed.

### Live regression proof

- Renewal orders `13170` and `13273` each loaded with zero `403` requests and no `sample-permalink` request. Their sole admin POST was the expected `200` heartbeat containing only `wc-refresh-order-lock=<exact order ID>`; no `wp_autosave` payload was present.
- Unrelated HPOS order `21180` passed the same control, confirming the screen-wide defect is closed.
- On order `13170`, `post.js`, `autosave.js`, and `heartbeat.js` remained loaded, `form#order` and the lost-connection notice remained present, and `form#post` remained absent.
- Product `11943` provided the negative-scope control: its classic editor retained `form#post`, title, slug markup, sample-permalink nonce, and both `after-autosave.update-post-slug` and `after-autosave.edit-post` handlers.
- Stable scalar hashes covering order status, totals, refunded totals, currency, customer/payment/transaction data, created/modified dates, renewal linkage, line items, and note count were identical before and after the final browser run:
  - `13170`: `bbf7f0c22c68df0960fe2678d3a5cd352943f856fdceb0f511f0261e0d40ead8`
  - `13273`: `5639e7224cc9741b97f7a42353dc62346bc8dbd2cae7b355c8c85240d0ffbd15`
  - `21180`: `fdd1f0a491b5410d47842c7237966b5ae4b424ee1b293f572e4fc867bc436530`
- Browser errors were empty and the console contained only existing `JQMIGRATE` informational messages. Source whitespace validation passed with `git diff --check`; PHPCS was intentionally skipped per the QA issue-fix workflow.

### Evidence and cleanup

- Current pre-fix reproduction: `/home/server-manager/slt-evidence/HIGH-SLT-SYN-09-current-order-13170.png`
- Fixed renewal orders: `/home/server-manager/slt-evidence/HIGH-SLT-SYN-09-after-order-13170.png`, `/home/server-manager/slt-evidence/HIGH-SLT-SYN-09-final-order-13273.png`
- Fixed unrelated HPOS control: `/home/server-manager/slt-evidence/HIGH-SLT-SYN-09-after-unrelated-order-21180.png`
- Unaffected classic product editor: `/home/server-manager/slt-evidence/HIGH-SLT-SYN-09-product-editor-control.png`
- The isolated admin sessions were closed. Testing was read-only, the exact data hashes remained unchanged, and no disposable records required cleanup.
