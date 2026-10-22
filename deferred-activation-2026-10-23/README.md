# Deferred initial activation — targeted regression

Date: 2026-10-23, Asia/Dhaka. Active QA plan/task/scheduled day: N/A; this is targeted implementation regression, not a new broad QA cycle. Existing `qa/issues/` records were inspected and preserved.

## Environment and results

- Real local WordPress/WooCommerce: `http://localhost:10013`; HPOS enabled.
- Core plus Pro: **46/46 passed**, [output](core-plus-pro.log).
- Core only, using request-local `--skip-plugins=arraysubspro`: **44/44 passed**, [output](core-only.log). The site's persistent plugin activation was not changed.
- All regression subscriptions, orders, payment tokens, product and customer fixtures were removed. Persistent ArraySubs settings and active-plugin options were unchanged.
- Fixture-operation email interception captured 60 messages in the final Pro run and 53 in the core-only run. These fixture emails were not delivered.
- No PHPCS, Composer installation, expensive test suite, remote deployment or release operation was performed.

## Coverage

`regression.php` runs actual persisted WooCommerce orders through the initial paid-order hook and uses the public developer subscription API for approval. It verifies:

1. Unfiltered paid orders retain ordinary activation/trial behavior and one invoice/charge pair.
2. A paid initial order can leave a non-trial subscription pending/on hold while preserving payment counts, payment date, reusable token, history-related initial linkage, gateway capture and payment-success event.
3. Deferred subscriptions have no activation/trial event, renewal charge, renewal invoice or renewal reminder job. The deferred action receives current `WP_Post`/`WC_Order` objects after status/payment persistence.
4. Context contains persisted billing/shipping addresses, order notes, payment method/title/transaction and WooCommerce Additional Checkout Fields. Namespaced IDs, negative numbers, zero, false-like saved values and arrays are retained; arbitrary private order meta is excluded.
5. Pro contributes saved `_arraysubs_cf_*` checkout fields; core context works with Pro skipped.
6. Processing-to-completed transitions and replay after removing only the order-level processed marker do not duplicate initial payment or deferral effects.
7. Manual pending/on-hold approval clears the deferral marker, emits activation once and queues both invoice/charge jobs. Original billing dates remain unchanged. Past-due jobs retain their original due-based timestamp and are immediately runnable by Action Scheduler.
8. Renewal processing and the overdue cancellation batch cannot treat a deferred on-hold subscription as an ordinary failed renewal. The cancellation-batch test temporarily constrains its SQL selection to the exact fixture ID; no global lifecycle sweep is run.
9. Trials, simulated provider-owned billing clocks and invalid callback return values retain the default initial status. Paid renewal orders bypass the initial-payment status filter and advance normally.
10. Manual approval refuses an elapsed fixed calendar end; existing on-hold fixed-end expiration still works. The deferred marker survives expiration and prevents later expired-to-active approval from bypassing that fixed end.
11. With Pro loaded, bundle child statuses finish mirroring before the parent's deferred/activated action fires.
12. A foreign subscription mutation lock blocks both ordinary paid-order fan-out and the explicit post-paid activation entrypoint. Payment counts, filter calls and the order-level processed flag remain untouched while a replay is queued. After releasing that exact fixture lock, the actual replay handler records and defers the paid subscription exactly once.

## Limits

The payment fixtures use manual `bacs` orders and a disposable locally stored payment token. Provider ownership is simulated using the existing billing-clock filter. **No real Stripe checkout, Stripe charge, remote provider pause or off-session renewal charge is claimed by these results.** The checks establish local hook/accounting/scheduling behavior; they are not a replacement for a live gateway end-to-end test.

The coordinating agent separately completed real browser manual-status verification: subscription 18946 was changed from Pending to Active through the existing editor's Save and confirmation dialog, with success shown. A subsequent [read-only snapshot](browser-evidence.json) confirmed its initial order 18945 remained Completed, the deferred marker was absent, the paid count remained 1, the next-payment date stayed `2026-11-21 19:59:01` UTC, and both invoice and charge jobs were scheduled. Browser actor: disposable administrator 376. This README's numeric test totals refer only to the integration harness.

Browser screenshots: [Pending](pending-before.png), [activation confirmation](activation-confirmation.png), [Active](active-after.png), and [order still Completed](order-still-completed.png). After evidence capture, the authorized [cleanup](browser-cleanup.json) verified deletion of the disposable subscription, order, product, customer, administrator, runtime MU helper and private credential file. The evidence and screenshots remain.

## Reproduction

The system PHP default socket connects to a different MySQL server. Use the Local site's socket explicitly; no additional environment variables are required:

```bash
php -d 'mysqli.default_socket=/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock' \
  /opt/homebrew/bin/wp --allow-root \
  --path='/Users/emran/Local Sites/wp-test2/app/public' \
  eval-file '/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/deferred-activation-2026-10-23/regression.php'
```

For core only, add `--skip-plugins=arraysubspro` before `eval-file`. Every regression fixture is owned by the current run and cleaned in `finally`.

## Disposable browser fixture

`browser-fixture.php` creates one completed manual-payment order, one pending deferred subscription, a private product, a customer and a disposable administrator. Its `private/browser-fixture.json` credential source is mode `0600`, inside an ignored mode-`0700` directory. Passwords must not be copied into reports or logs.

A temporary MU helper suppresses email only during that disposable administrator's requests or when sent to the exact fixture customer address. The coordinating agent owns the browser session and must finish before cleanup.

Run the same WP-CLI command above with `browser-fixture.php` in place of `regression.php` to create. Append `cleanup` after the fixture filename to remove its subscription, order, product, customer, administrator, runtime MU helper and credential record. Cleanup verifies that those records and files are gone.
