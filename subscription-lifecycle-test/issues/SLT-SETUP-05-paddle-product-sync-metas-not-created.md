# Paddle product sync metadata is not created after a real product save

- Severity: high
- Date found: 2026-08-03
- Watch day: D01
- Originating task: `SLT-SETUP-05` (progress task ID `26`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/026-slt-setup-05-verify-paddle-sandbox-readiness-and.md`

## Affected records

- Subscription IDs: N/A
- Order IDs: N/A
- Product ID: `12112` (`SLT Paddle Daily`)
- WP user: ID `1`, login `admin`, email `admin@mirror-help.arrayhash.com`, role `administrator`
- Guest browser user: N/A (logged-out guest; no customer record created)
- Gateway: `arraysubs_paddle`, enabled in test mode
- Checkout type: block checkout
- Non-default settings: Paddle test mode `yes`; seller ID `80267`; API key, client token, and webhook secret were each present. Raw credentials were neither printed nor saved.

## Routes and context

- Product edit: `https://mirror-help.arrayhash.com/wp-admin/post.php?post=12112&action=edit`
- WooCommerce logs: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=logs`
- Current gateway log: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=logs&view=single_file&file_id=arraysubs-gateway-2026-08-03`
- Checkout control: `https://mirror-help.arrayhash.com/checkout/?add-to-cart=12112`
- Browser contexts: authenticated administrator in `admin-SLT-SETUP-05`; logged-out guest in `guest-SLT-SETUP-05`

## Reproduction

1. Verify that product `12112` is a published, simple, virtual day/1 subscription priced at USD 11.00.
2. Read `_arraysubs_gateway_paddle_product_id`, `_arraysubs_gateway_paddle_price_id`, and `_arraysubs_gateway_paddle_synced_at`; all three are initially absent.
3. Open the product edit route as administrator and click **Update** once without changing the product.
4. Wait for the visible `Product updated.` success notice.
5. Read the same three metadata keys immediately, then again after 15 seconds.
6. Open WooCommerce Logs and inspect the available log-source filter and the current `arraysubs-gateway-2026-08-03` file.

## Expected result

- Saving the Paddle product triggers catalogue synchronization.
- `_arraysubs_gateway_paddle_product_id` is populated with a `pro_...` value.
- `_arraysubs_gateway_paddle_price_id` is populated with a `pri_...` value.
- `_arraysubs_gateway_paddle_synced_at` contains a synchronization timestamp.
- A dedicated `arraysubs_paddle_sync` log records the attempt or its actionable failure.

## Actual result

- The admin save succeeds visibly, but all three Paddle sync metadata keys remain absent both immediately and 15 seconds later; `wp post meta list 12112 --keys=... --format=json --allow-root` returns `[]`.
- The WooCommerce log-source filter contains `arraysubs-gateway`, `fatal-errors`, `transactional-emails`, `wc-analytics-order-import`, `wc-updater`, `wc_logger`, and `woocommerce-gateway-stripe`, but no `arraysubs_paddle_sync` source.
- The only line in the current `arraysubs-gateway-2026-08-03` file is `2026-08-03T02:17:21+00:00 Info [Gateway: system] Cleaned up 2 old webhook events`; it is unrelated to the save and contains no catalogue-sync attempt or error.
- No raw credential or secret was exposed while verifying configuration.

## Proof

- `/home/server-manager/slt-evidence/SLT-SETUP-05-01-paddle-sync-meta.png` shows product `12112`, `SLT Paddle Daily`, and the visible `Product updated.` success notice.
- `/home/server-manager/slt-evidence/SLT-SETUP-05-06-paddle-sync-log-source-absent.png` shows the complete log-source selector without `arraysubs_paddle_sync`.
- `/home/server-manager/slt-evidence/SLT-SETUP-05-07-current-gateway-log.png` shows the current gateway log's sole cleanup line.
- Redacted readiness summary: `enabled=yes`, `test_mode=yes`, `seller_id=80267`, `api_key_present=yes`, `client_token_present=yes`, `webhook_secret_present=yes`.
- Consolidated task evidence: `/home/server-manager/slt-evidence/SLT-SETUP-05-facts.txt`.

## Scope and counterexamples

- The checkout gateway registration itself works: block checkout for both `SLT Daily Core` (`11927`) and `SLT Paddle Daily` (`12112`) visibly offered Stripe and Paddle.
- The intended negative gate also works: `SLT Flex Daily Next Cycle` (`12102`) offered Stripe but did not offer Paddle.
- Paddle's public checkout script and the task's Paddle checkout assets loaded with HTTP 200/304 responses; the browser error collection was empty. The missing metadata is therefore specifically a product catalogue-synchronization/readiness failure, not proof that the gateway radio is globally unavailable.
- No checkout was submitted. User count, ArraySubs subscription-row count, and HPOS row count remained `354`, `359`, and `549`; the Mailpit latest ID remained `42DI8ELEccd8qFsaMtyeag`.
- Investigation intentionally stopped at browser and WP-CLI evidence. Product source was not inspected or changed.

## D03 checkout counterexample — SLT-CHK-04

- QA progress task: `SLT-CHK-04`, ID `29`; plan file `kanban/tasks/029-paddle-sandbox-purchase-slt-paddle-daily-via.md`.
- Affected records: order `12629`, subscription `12639`, product `12112`, customer user `352` (`slt-paddle`, `slt-paddle@example.test`, Customer).
- Customer route/context: real block checkout at `https://mirror-help.arrayhash.com/checkout/` in isolated session `cust-SLT-CHK-04`; received and account pages were opened in the same authenticated customer context. The order-key URL was not retained in issue evidence.
- Despite the three catalogue-sync metadata fields remaining absent, Paddle was offered, the overlay loaded, the sandbox pay request returned HTTP `200`, and the webhook moved order `12629` from pending to `wc-completed` for USD `11.00`.
- The webhook created active subscription `12639` with Paddle subscription id `sub_01kz8q1025tryjfgxvn5e3v4gf`. A redacted Paddle API read returned HTTP `200`, status `active`, and `next_billed_at=2026-08-06T10:20:38.143985Z`; local `_next_payment_date=2026-08-06 10:20:38` matches at stored-second precision.
- Concrete proof: `/home/server-manager/slt-evidence/SLT-CHK-04-01-gateways.png`, `SLT-CHK-04-02-overlay.png`, `SLT-CHK-04-03-received.png`, `SLT-CHK-04-04-pending.png`, `SLT-CHK-04-05-myaccount.png`, `SLT-CHK-04-06-cart-empty.png`, and `SLT-CHK-04-facts.txt`. The retained overlay image was taken before payment data entry and contains no card number.
- Scope refinement: the original defect remains a product catalogue-synchronization and observability/readiness failure. It did **not** reproduce as a checkout blocker for this exact customer/product purchase, so no separate `SLT-CHK-04` product issue was created.
