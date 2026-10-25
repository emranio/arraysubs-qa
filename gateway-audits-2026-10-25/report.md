# Renewal Flow and Audits verification — 2026-10-25

Target: http://localhost:10013, actual local WordPress installation supplied by the user.
Context: admin #1 (administrator), isolated Chromium agent-browser gateway-admin session; separate gateway-guest session for permissions.
Active QA task / scheduled day / execution-plan path: N/A. Targeted implementation verification, not a new scheduled QA cycle.

## Result

The requested navigation and module behavior passes. Gateway Logs and Renewal Failures appear in both Audits and Renewal Flow. Renewal Flow orders its sections as Grace Period, Email Reminders, Gateway Logs, Renewal Failures, Renewal Settings. Switching Audits off hides the entire WordPress submenu entry and its page tabs while leaving both Renewal Flow destinations live. Both pages also load with Pro inactive.

Renewal failure listing, retry, resolve, permission checks and response shapes moved unchanged from the Pro Audits controller into core RecurringBilling. The existing REST paths remain shared by both destinations. The Audits switch continues to disable its activity, portal-failure, job-log and queue-health endpoints. Core and Pro changelog drafts and current module architecture documentation were updated.

## Browser checks

| Check | Result | Evidence in evidence/ |
| --- | --- | --- |
| Audits on: Gateway Logs opens with Audits tabs and selected WordPress menu | PASS | audits-gateway-on.png; audits-gateway-on-nav.json |
| Audits on: Renewal Failures opens with Audits tabs and 18 retained failure rows | PASS | audits-renewal-failures-on.png; audits-renewal-failures-on.json |
| Renewal Flow: Gateway Logs opens with its own tabs and selected menu | PASS | renewal-gateway-on.png; renewal-gateway-on-nav.json |
| Save Audits off through Modules; menu disappears even with Member Access and Automatic Payments active | PASS | modules-audits-off.txt; audits-off-menu.json |
| Audits off: Gateway Logs remains available under Renewal Flow | PASS | renewal-gateway-audits-off.png; renewal-gateway-audits-off.txt |
| Audits off: Renewal Failures loads retained rows plus disposable fixture | PASS | renewal-failures-audits-off.png; renewal-failures-audits-off-nav.json |
| Audits off: Retry confirmation and loading, followed by safe no-order response | PASS | retry-confirm-settled.png; retry-audits-off-result.png; renewal-failure-requests.txt |
| Audits off: Resolve confirmation and loading; row removed | PASS | resolve-audits-off-result.png; resolve-audits-off-result.txt; fixture-resolved-runtime.txt |
| Audits off: list GET and retry/resolve POST endpoints return 200 | PASS | renewal-failure-requests.txt |
| Audits off: activity logs, portal failures, scheduled-job logs and queue health return 404 | PASS | rest-audits-off.json |
| Direct Activity Audits route shows switched-off notice and no Audits menu | PASS | activity-audits-off-guard.png |
| Pro inactive, Audits off: both Renewal Flow pages work and Audits menu stays absent | PASS | core-only-renewal-failures.json; core-only-renewal-failures.png; core-only-gateway.txt; core-only-gateway.png |
| Guests cannot list, retry or resolve failures (401 for all three) | PASS | guest-permissions.json |
| Restore Pro and original Audits state: menu and all six audit tabs return | PASS | pro-restored.txt; modules-restored.txt; audits-restored.txt |
| Every module switch matches its original state after testing | PASS | switches-before.json and switches-after.json are identical |
| 390px mobile section selector includes both destinations and switches Gateway Logs to Renewal Failures | PASS | mobile-navigation.json; mobile-renewal-gateway.png; mobile-renewal-failures.png |
| Renewal Settings still loads Payment Retries after both troubleshooting tabs | PASS | renewal-settings.txt; final-tab-order.json |
| Uncaught JavaScript errors | NONE | browser-errors.txt |

## Fixture and restoration

Disposable subscription #21511, titled QA Renewal Flow Independence 2026-10-25, belonged to admin #1. It had failure metadata but no payment gateway, renewal order, billing dates or scheduled actions. Retry returned No failed renewal order to retry; Resolve set the administrative dismissal and preserved its active status. Order IDs remained empty. The fixture was permanently deleted after verification. No existing failure rows or orders were changed by the test actions. See fixture-created.txt, fixture-resolved-runtime.txt and fixture-cleanup.txt.

Pro was temporarily deactivated to verify core-only operation, then reactivated. Audits was restored to enabled. All ten optional-module switch states match the baseline.

## Validation

- Core production webpack build passed after the final JavaScript changes (c52dae6ef89cfd2aaa55).
- PHP syntax checks passed for all five changed PHP source files.
- Controller content matches its former Pro implementation exactly after namespace and translation-domain replacement.
- Diff whitespace checks passed. All changed product source files are below 3000 lines.
- Existing concurrent Portal Action Failures styling and changelog edits were preserved.

## Separate existing layout issue

QA issue #116: Gateway Logs clips the Last Webhook metric on 390px screens, reproduced on both its existing Audits destination and the additional Renewal Flow destination. Desktop layout and the mobile navigation selector work. Logged as a low-priority, open issue at qa/issues/tasks/116-gateway-logs-cards-clip-last-webhook-on-narrow.md with screenshots; gateway card layout was outside this navigation/module change.
