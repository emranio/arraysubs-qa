# Pro admin loader browser verification

Date: 2026-11-16 (Asia/Dhaka)
Site: http://localhost:10013
Browser context: authenticated admin, isolated agent-browser session `loaders-pro`, viewport 1280 × 577.
Scope: ArraySubsPro-owned Retention, Refunds, AI Churn, AI Revenue Forecast, and Overview extensions inside WooCommerce Analytics.

## Method

Used agent-browser snapshot/ref interactions and inspected the saved screenshots. A reviewed browser init script delayed delivery of actual ArraySubs fetch responses by 12 seconds on initial navigation, then 5 seconds for pagination/retry. It called the original browser fetch with the original arguments and returned the original Response; no API response data was mocked. The browser session alone contained the delay wrapper. Offline mode was used once to exercise a real network failure, then restored before retry.

## Results

| Surface | Observed loading state | Completion | Evidence |
| --- | --- | --- | --- |
| Retention report and independently fetched activity log | Two WordPress spinner elements overlap at exactly the same center; visually one spinner, no visible loading label | Both requests HTTP200, loaders removed | `pro-retention-final-loading.png`, `pro-retention-final-loading.json`, `pro-retention-ready.png` |
| Activity Log Next page | One centered spinner; Previous/Next retain their ordinary labels and are disabled | HTTP200, page 2 renders, spinner removed | `pro-retention-pagination-loading.png`, `pro-retention-pagination-ready.png`, matching JSON |
| Activity Log network failure and Retry | Error shows with no stuck loader. Retry shows one centered spinner, disabled ordinary Retry label | Offline failure recovered to HTTP200 and real rows | `pro-retention-network-error.png`, `pro-retention-retry-loading.png`, `pro-retention-retry-ready.png`, matching JSON |
| Refunds | One centered WordPress spinner, no visible loading label | Initial request returned a transient HTTP503 data-changed message; Retry returned HTTP200 and rendered the report | `pro-refunds-loading.png`, `pro-refunds-ready.png` (initial error), `pro-refunds-retry-loading.png`, `pro-refunds-retry-result.png`, matching JSON |
| AI Churn | One centered WordPress spinner, hidden report content during data load | HTTP200, report rendered and spinner removed | `pro-churn-loading.png`, `pro-churn-ready.png`, matching JSON |
| AI Churn refilter | One centered spinner after Segment changed to Active | Search field retained `Demo`, scope became `active`, no remaining loader | `pro-churn-refilter-loading.png`, `pro-churn-refilter-ready.png`, `pro-churn-refilter-ready.json` |
| AI Revenue Forecast | One centered WordPress spinner, hidden report content during data load | HTTP200, report rendered and spinner removed | `pro-forecast-loading.png`, `pro-forecast-ready.png`, matching JSON |
| Overview subscription statistics and charts | Two independent spinner elements overlap at exactly the same center, visually one | Both requests HTTP200, loaders removed | `pro-overview-loading.png`, `pro-overview-ready.png`, matching JSON |

Every measured ArraySubs spinner is 20 × 20 pixels, centered at (720, 304.5). This is the exact center of the admin content viewport after the 160-pixel WordPress sidebar and 32-pixel admin toolbar: ((160 + 1280) / 2, (32 + 577) / 2). Screenshot inspection confirms the visible loader has no text.

A duplicate Activity Log pagination button spinner was found during testing (`pro-retention-pagination-before-fix.png`), fixed in source, rebuilt by the parent agent, and rechecked successfully. Search/sort state preservation in the AI report subtree was corrected before browser verification; the search persistence case above passed.

## Diagnostics and boundaries

- No uncaught JavaScript errors on the successful report flows; `pro-*-errors.txt` files are empty. The browser console has existing WordPress/WooCommerce/jQuery deprecation warnings.
- Refunds' initial HTTP503 was an actual server response reporting concurrent data changes. The final Retry succeeded without changing report data or server settings.
- Overview also contains WooCommerce-owned performance skeletons and data-status indicators. Those native WooCommerce elements are outside the ArraySubs plugin changes; only ArraySubs-owned indicators were standardized.
- Initial AI DOM samples may include hidden WooCommerce loading text. The screenshots were inspected and contain no visible loading label; the core loader's status text remains screen-reader-only.
- Standalone report route rendering, page loading, pagination, refiltering, and error recovery were covered. AI generation actions were not invoked; they are mutation/action flows with separate feedback.
- No plugins were activated/deactivated and no report/customer/subscription data was edited in this session.

## Cleanup

Offline mode was restored to off. The isolated browser session was closed after verification, removing its runtime delay wrapper. Temporary authentication state was removed from `/tmp`. No test instrumentation was written into plugin source.
