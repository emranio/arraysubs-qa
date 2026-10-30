# ArraySubs + Pro release QA — 2026-10-31

Coverage snapshot: **FINAL**, 2026-10-30T21:00:42.181994+00:00. Coordinator owns the final release verdict.

Visited 87/97 distinct template families and 141/160 meaningful modes. Real-change save/reload tested 57/74 independent editors (57 PASS). Source-only expectations and unvisited live modes remain in the denominator.

One shared run: local 02:08:13–03:08:13 Asia/Dhaka; testing stops 03:01:13. No product code changes or deployments.

- [Final report](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/report.md>)

## Coverage and plan

- [Plan and exact A/B/C profiles](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/plan.md>)
- [Deduplicated coverage/count register](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/coverage-register.md>)
- [Machine-readable template/mode/save register](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/coverage-register.json>)
- [Coordinator coverage](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/coverage.md>)
- [Commerce/lifecycle coverage](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/commerce-coverage.md>)
- [Premium/rule/builder coverage](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/premium-coverage.md>)
- [Account/access/analytics coverage](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/access-coverage.md>)
- [Email discovery/previews (later saves in the register)](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/emails-coverage.md>)
- [Email save/reload/restoration roundtrip results](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/email-save-results.json>)
- [Source route inventory and alignment limitations](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/inventory.md>)

## Findings and restoration

- [Commerce findings](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/commerce-findings.md>)
- [Access/account findings](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/access-findings.md>)
- [Premium findings](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/premium-findings.md>)
- [Known raw-upload security risk](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/security-findings.md>)
- [Dismissed preview-loading observation](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/email-findings.md>)
- [Coordinator mutation/restoration ledger](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/mutations.md>)
- [Commerce mutation/restoration ledger](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/commerce-mutations.md>)
- [Access mutation/restoration ledger](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/access-mutations.md>)
- [Premium mutation/restoration ledger](</Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/release-2026-10-31-0208/premium-mutations.md>)

Restoration and cleanup are reconciled in the coordinator report and ledgers. Three owned subscriptions are Cancelled with no pending billing jobs; test customer/orders and designated evidence drafts are retained. Variable children109544/109546 disposition was not independently queried. All run browser sessions are closed.
