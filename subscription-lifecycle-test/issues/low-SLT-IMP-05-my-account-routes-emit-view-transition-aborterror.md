# My Account routes emit a repeated view-transition AbortError

- Severity: low
- Date found: 2026-08-11
- Watch day: D09
- Originating task: `SLT-IMP-05` (kanban task `116`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/116-end-of-window-log-sweep-failed-action-audit-and.md`

## Affected records and context

- Subscription ID: `11959` (SLT Daily Core)
- Product ID: `11927` (SLT Daily Core)
- Parent order ID: `11949`; latest relationship renewal inspected during D09: `13610`
- WordPress user: ID `347`, login `slt-core`, email `slt-core@example.test`, role `customer`
- Gateway: Stripe test
- Checkout type: N/A; this is a logged-in My Account navigation sweep
- Non-default settings: none introduced by `SLT-IMP-05`; the task was read-only
- Browser session: `customer-sweep-SLT-IMP-05`
- Routes:
  - `https://mirror-help.arrayhash.com/my-account/`
  - `https://mirror-help.arrayhash.com/my-account/subscriptions/`
  - `https://mirror-help.arrayhash.com/my-account/view-subscription/11959/`
  - `https://mirror-help.arrayhash.com/my-account/orders/`
  - `https://mirror-help.arrayhash.com/my-account/payment-methods/`

## Reproduction

1. Sign in to the shared staging site as `slt-core` in an isolated browser session.
2. Open `/my-account/` and wait for the page to settle.
3. Read the browser's captured page-error collection.
4. Repeat for `/my-account/subscriptions/`, `/my-account/view-subscription/11959/`,
   `/my-account/orders/`, and `/my-account/payment-methods/`.
5. Repeat one navigation with a cache-busting query and allow an additional three seconds
   after render.
6. Inspect network failures and HTTP statuses separately from the page-error collection.

## Expected result

All five customer routes render without a browser/page error and without a 4xx/5xx response,
as required by the SLT-IMP-05 console-sweep criterion.

## Actual result

All five routes render their expected content and return no 4xx/5xx, but each route records
the same page error exactly once:

`AbortError: Transition was skipped` (`url=null`, `line=0`, `column=0`)

The error also reproduced after a cache-busting navigation and an additional settle delay.

## Concrete proof

- `/home/server-manager/slt-evidence/SLT-IMP-05-08-console-portal.txt` records all five
  routes, rendered body checks, the exact error, console classification, and network result.
- Rendered body character counts were `697`, `938`, `3642`, `1113`, and `634` respectively;
  the pages were not blank or HTTP error documents.
- No route produced an HTTP 4xx/5xx response or failed resource request.
- The console contained only the informational JQMIGRATE entry; the `AbortError` was emitted
  through the browser page-error channel with no source URL or line number.

## Scope notes and counterexamples

- The same error occurs on all five customer account routes, including core WooCommerce
  account pages and the subscription detail. This evidence does not localize the cause to
  ArraySubs; the missing source URL and broad route scope are consistent with a site-level
  view-transition/navigation layer.
- The seven administrator routes swept in the same task had zero browser errors, zero
  warning/error console entries, and zero 4xx/5xx. Evidence:
  `/home/server-manager/slt-evidence/SLT-IMP-05-07-console-admin.txt`.
- Subscription `11959`, its orders, and all account data remained readable and unchanged;
  no functional navigation failure or state divergence was observed. Severity is low because
  the demonstrated impact is diagnostic noise and potential client-side instability.
- No card data, credential, customer token, or provider identifier is included in the evidence.

