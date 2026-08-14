# ArraySubsPro Paddle parse error aborted an admin AJAX request

- **Severity:** high
- **Date found:** 2026-08-13
- **Watch day:** D11
- **Originating test task:** `SLT-OBS-01` (card `126`, independently claimed by `salt-ledge`)
- **Plan/task file:** `qa/subscription-lifecycle-test/kanban/tasks/126-fix-every-critical-plugin-qa-issue.md`
- **Status:** transient and currently recovered; the historical fatal is independently proven

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
- A distinct later fatal in the debug log originated from a WP-CLI reflection/eval harness and is excluded from this admin-AJAX incident.

