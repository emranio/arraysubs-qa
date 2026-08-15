# ArraySubsPro Paddle parse error aborted an admin AJAX request

- **Severity:** high
- **Date found:** 2026-08-13
- **Watch day:** D11
- **Originating test task:** `SLT-OBS-01` (card `126`, independently claimed by `salt-ledge`)
- **Plan/task file:** `qa/subscription-lifecycle-test/kanban/tasks/126-fix-every-critical-plugin-qa-issue.md`
- **Status:** closed on 2026-08-14; genuine historical deployment transient, already remediated and regression-verified

## Affected objects

| Object | Value |
|---|---|
| Subscription IDs | N/A — the recovery message does not identify a subscription |
| Order IDs | N/A — the recovery message does not identify an order |
| Product IDs | N/A |
| WP user | ID `1`, login `admin`, email `admin@mirror-help.arrayhash.com`, role `administrator` |
| Gateway | Paddle sandbox code path |
| Checkout type | N/A — failure was caught while bootstrapping an admin AJAX request |
| Non-default settings | None identified; the failure was a PHP parse error, before runtime settings could govern it |

## Route and context

- Failing route: `https://mirror-help.arrayhash.com/wp-admin/admin-ajax.php`
- Original browser/user context: card-126 `SLT-OBS-01` concurrent regression activity; the WordPress recovery message records the admin AJAX endpoint but not the originating named browser session or AJAX action.
- Verification context after recovery: `admin-SLT-D11-WATCH-H`, authenticated administrator, `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`.

## Reproduction steps

Historical reproduction requires the ArraySubsPro `1.1.2` build that was deployed at `2026-08-12 22:14:37Z`; the current checkout has already recovered.

1. On a WordPress installation matching this staging site, activate the ArraySubsPro `1.1.2` build deployed at the timestamp above.
2. Authenticate as an administrator and issue any request to `/wp-admin/admin-ajax.php`, which bootstraps active plugins before dispatching the AJAX action.
3. Observe that PHP cannot parse the Paddle gateway class and the AJAX request aborts before normal dispatch.
4. Check the PHP error log for an `E_PARSE` at the same timestamp and the administrator mailbox for WordPress's technical-issue notification.
5. Replace the invalid build with the currently recovered build and repeat a WordPress bootstrap plus an authenticated ArraySubs admin-page load; both now succeed, demonstrating that the failure was transient rather than a persistent environment outage.

The recovery message does not preserve the original AJAX `action` parameter, so the narrower card-126 operation cannot be reconstructed from that message alone.

## Expected result

ArraySubsPro must parse successfully. The admin AJAX request should reach WordPress dispatch without a plugin bootstrap fatal, and WordPress should not emit a technical-issue recovery message.

## Actual result

At `2026-08-12 22:14:37Z` (`2026-08-13 04:14:37` site-local), PHP raised `E_PARSE: syntax error, unexpected token "!"` in the ArraySubsPro Paddle gateway file at line `3595`. WordPress caught the fatal on `/wp-admin/admin-ajax.php` and emailed the site administrator.

## Proof

- Mailpit message `1rMGgdczygb8xOnxE3mnMR`, subject `[mirror-help.arrayhash.com] Your Site is Experiencing a Technical Issue`, recipient `admin@mirror-help.arrayhash.com`, date `2026-08-12T22:14:37Z`.
- The complete Mailpit body attributes the fatal to ArraySubsPro `1.1.2`, type `E_PARSE`, Paddle gateway line `3595`, with `unexpected token "!"`. The recovery-mode URL and token were deliberately excluded from this issue.
- Independent server proof: `wp-content/debug.log:2549` contains the same timestamp, file, line, error type, and token.
- Current counterexample: `wp plugin status arraysubspro --allow-root` reports ArraySubsPro `1.1.2` active, and a fresh WordPress runtime bootstrap completed successfully.
- Current UI counterexample: `/home/server-manager/slt-evidence/SLT-D11-WATCH-subscriptions-list.png` shows the authenticated ArraySubs subscription list loading after recovery; browser console contained only the normal JQMIGRATE message and no page error.

## Scope notes and counterexamples

- This incident belongs to the independently claimed card-126 `SLT-OBS-01` campaign, not to the authored D11 lifecycle tail. No published lifecycle subscription, order, or expected natural action is named by the fatal.
- The current plugin runtime and ArraySubs admin UI are healthy, so the evidence proves a transient deployment/editing window rather than a continuing outage.
- Mailpit's latest 500-message search contains one technical-issue message for this incident; no repeat was observed through the D11 early-morning cutoff.
- The debug log contains four additional Paddle gateway parse errors during the same concurrent live-edit period: two `unexpected token "!"` entries on `2026-08-13 00:41Z` and two `unexpected token "empty"` entries on `2026-08-13 13:26Z`. They are separate WP-CLI/edit-harness events, not repeats of this admin-AJAX incident, but they confirm that the gateway file was briefly exposed while incomplete edits were being made.

## Resolution and regression verification

The report is not a false positive: the matching Mailpit recovery message and server log independently prove that an invalid intermediate Paddle gateway file aborted an authenticated admin-AJAX bootstrap. The historical line number no longer identifies defective code because the gateway was substantially rewritten after the incident. The invalid intermediate file is not present in the current committed checkout.

No source patch was made for this report. Adding speculative syntax changes to valid current code, suppressing PHP fatal reporting, or catching plugin bootstrap errors would conceal deployment defects and create new security and operability risks. The correct resolution is to retain the valid recovered gateway and prove the affected bootstrap path remains healthy.

Verification completed on 2026-08-14:

1. `wp plugin status arraysubspro --allow-root` reported ArraySubsPro `1.1.2` active.
2. A fresh WordPress runtime bootstrap loaded `PaddleGateway`, found the registered `arraysubs_paddle` gateway, and returned its expected enabled sandbox configuration.
3. An authenticated administrator opened `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`; the subscription application rendered all `403` records and exposed Paddle in the gateway filter.
4. The same real browser session issued two authenticated `POST https://mirror-help.arrayhash.com/wp-admin/admin-ajax.php` requests. The recorded request completed with HTTP `200`; the page reported no browser errors.
5. The current-state screenshot is `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-OBS-01-current-admin.png`.
6. The Paddle gateway parse-error count in `wp-content/debug.log` remained exactly `5` before and after the browser regression test.
7. Mailpit's latest message ID remained `1zPxE6FmuLNdLZQPE1aist`; no new WordPress technical-issue email was emitted by the runtime or browser checks.

Security and compatibility review: no endpoint, nonce, capability, input, output, gateway contract, subscription data, scheduler behavior, or core/pro integration was changed. Fatal visibility remains intact, and both the free plugin admin application and the premium Paddle gateway boot successfully together.
