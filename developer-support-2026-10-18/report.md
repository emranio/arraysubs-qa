# Developer support implementation verification

Executed 2026-10-18, Asia/Dhaka. Fresh plan: [plan.md](plan.md). Local site: `http://localhost:10013`. Implementation covers both requested developer-support plans in ArraySubs and ArraySubs Pro. Local implementation and verification are complete. The owner confirmed on 2026-10-18 that Stripe verification should remain pending and external manual work should be skipped for this task. No release, version bump, push, or licensing-policy change was performed.

## Implemented scope

- F1–F4: period-aware numeric metering, separate/combine/max aggregation, deterministic feature sources, variation inheritance, toggle normalization and case-insensitive condition logic.
- F5–F8: obsolete period download counter removed while preserving per-file limits; permanent credit history; ledger-backed signed plan-switch grants with the confirmed defer policy; deletion-path comments corrected.
- D1–D19: documented PHP resource contracts for subscriptions, orders, customers, products, features, explicit-context access and benefit readers, Pro adapters and Store Credit; shared validated lifecycle mutations; scalar lifecycle notifications; executable PHP template guidance.
- Both canonical changelog drafts retain their earlier entries. Current contracts start at [the API index](../../documentations/architecture/developer-api.md).

## Runtime checks

These scripts boot the real local WordPress/WooCommerce installation. Core-only checks skip Pro for that CLI request rather than changing the site's activation state. Assertion counts describe focused coverage, not a capacity or provider certification.

| Check | Both plugins | Core only |
| --- | --- | --- |
| Feature usage regression | 43 assertions | Shared core implementation |
| Customer/product/feature API | 50 | 35 |
| Actual paid-cycle integration | 32 | 26 |
| Condition evaluation | 53 | 50 |
| Explicit-context member/benefit APIs | 53 | 48 |
| Lifecycle events | 73 | 73 |
| Admin/helper updated events | 27 | 27 |
| Subscription/order APIs, HPOS | 77 | 77 |
| Subscription/order documentation examples | 15 | 15 |
| Core-created records loaded with Pro | 6 exact-state preservation assertions | Fixture created in a core-only request |
| Alternate renewal-price billing guards | 31 | 31 |
| Mixed guest/registered order history | 28 | 28 |
| Exact PHP template examples | 32 as member; 31 guest; 31 admin | 32 as member |

Feature-cycle tests use real WooCommerce paid-order hooks, zero-total processing, signed pending-switch renewal application, Pro early renewal, actual manual-payment controller, box/bundle listeners and paid replay. Failure/on-hold/proration orders and ordinary date/pause changes do not invent new feature cycles. See [feature-usage-api-report.md](feature-usage-api-report.md).

Store Credit focused checks passed 66 assertions across append-only history, both buckets, recovery, expiry and signed plan switches. Twenty independent concurrent credit requests produced exactly twenty rows and balance 20. Twenty independent usage requests produced exactly 20 usage; replay retained 20 and a new over-limit request was refused. Evidence: `feature-usage-concurrency.json`, credit scripts and `credit-fixture-cleanup.json` and `credit-history-fields-results.txt`. A draft interrupted before balance persistence is retried safely when its expected state still matches; an ambiguous intervening balance change fails closed for reconciliation. Notification reservation does not establish remote delivery.

Subscription/order coverage includes more than 50 reverse-linked subscriptions, canonical identity/guest checks, independent paging/projection, parent/box/bundle ownership, pending-switch invoice identity, expected-state conflicts, storage rollback, provider/pending guards, both renewal jobs, trial conversion, individual grace, cancellation/undo and financial-proof deletion guards. These tests use HPOS; a complete alternate WooCommerce datastore matrix is not claimed.

## Browser checks

All navigation used isolated `agent-browser` sessions with snapshots and screenshots. No customer comments, external messages or real gateway charges were submitted.

| Flow | Observed result and evidence |
| --- | --- |
| Feature settings | Highest value saves successfully. `settings-max.png`. |
| Product feature editor | Numeric reset period changed to Daily, saved through the modal and WooCommerce Update, and persisted after reload. Nonnumeric rows retain no reset. `feature-reset-editor.png`. |
| Customer features | Highest value selects 10, combine totals 15, separate mode shows both subscription allowances. Usage follows the active period, reset labels use the site timezone, and internal types are absent. `features-max.png`, `features-combine.png`, `features-per-subscription.png`. |
| Credit history | Permanent-entry note, no delete/action controls, amounts follow the store's currency position. `credit-history.png`. |
| Per-file downloads | Two allowed deliveries reduce remaining 2→1→0; the third protected URL is denied. No obsolete period-summary card. `downloads-before.png`, `download-exhausted.png`. |
| Content/comments | Member sees protected content and comment form; guest sees configured denial and no comment form. `access-member.png`, `access-guest.png`. |
| Purchase restrictions | Eligible member has Subscribe Now; guest sees configured denial and cannot purchase the protected product. `purchase-member.png`, `purchase-guest.png`. |
| Shared admin billing guards | Authenticated browser requests for all three alternate-price fields return HTTP 409 `parent_billed_subscription` and leave stored values unchanged. The existing editor permits a contact-only edit, shows disabled Saving feedback, and redirects to the saved details. `alternate-renewal-price-browser-http.json`, `alternate-renewal-price-browser-ui.json` and the matching edit/saved screenshots. |
| Unsupported plan-switch credit | Preview explains next-renewal deferral; confirmation shows a disabled Processing state and scheduled-success toast. Reload shows the pending switch while the current plan and recurring amount remain unchanged, with no untracked credit created. `switch-deferred-preview.png`, `switch-scheduled-toast.png`, `switch-deferred-scheduled.png`. |

No browser errors were reported in the admin, member or guest sessions. The download cleanup check also confirms established email, lifecycle, coupon and child-subscription renewal listeners remain registered.

## Defects found and resolved

QA issues 46–50 and 52–54 were reproduced, fixed and closed after retesting: child cancellation undo, wrong pending-switch invoice identity, HPOS include filtering, order financial-evidence deletion and misleading deferred-switch success feedback, empty-history projection validation and mixed guest/registered order history. Alternate renewal-price fields now follow every shared billing guard. Their targeted 31/31 and final main 77/77 matrices pass in both configurations; authenticated browser requests also refuse all three blocked fields while the existing editor still saves permitted contact changes with a visible loading state. Their records remain in `qa/issues/`; older issues were preserved.

## Source and asset verification

Both production asset builds completed successfully. Both translation templates were regenerated; existing unrelated translator-comment warnings remain. All changed/new PHP source passed syntax validation. Source diffs passed whitespace checks. Every changed/new counted core/Pro source file is below 3000 lines; `OrderIntegration.php` is largest at 2959. See `source-validation.json`. No phpcs, Composer install or version change was performed.

## Cleanup

A core-only fixture followed by a Pro-loaded request also preserved exact subscription/product metadata, stored settings and the subscription domain snapshot; `core-pro-preservation.json` records six passing checks and cleanup. Its initial harness used JSON values that dropped zero-fraction float types; the corrected check compares serialized-state hashes without retaining raw settings. Each automated matrix cleans its own records. Browser fixtures were removed using the exact recorded IDs, including the customer, two products/subscriptions, source order, protected post and uploaded text file. Original download/comment/shop rules, aggregation mode, plan-switch settings, credit auto-apply and WooCommerce Coming Soon were restored. Only the temporary administrator session was revoked; temporary auth files were removed and the three browser sessions closed. The final billing-guard browser session also removed its exact parent/child/product fixtures, revoked only its own administrator token and removed its auth files; every check in `alternate-renewal-price-browser-cleanup.json` is true. Recorded checks are in `browser-fixtures.json`.

## Owner-directed closeout and verification limits

1. **Official Stripe browser acceptance — pending by owner decision:** the owner explicitly chose “Leave Stripe verification pending” on 2026-10-18. The configuration limitation remains tracked in QA issue 51; it does not block closing this local implementation task. The installed official gateway is inactive; enabled is `no`, test mode is `yes`, and both test keys are absent. Redacted proof: `stripe-preflight.json`. Checkout with a reusable test method, automatic due renewal, real failure/retry/grace/recovery and callback deduplication must run against a configured test site before declaring provider readiness. Local paid-order and adapter checks do not replace it. Other live provider approval flows were not certified by this cycle.
2. **External manual work — skipped by owner decision:** the owner replied “skip it” to the missing manual-repository question on 2026-10-18. No repository path or publication work is awaited for this task. Local architecture guides and tested snippets are retained; external manual pages/navigation/screenshots were not published.
3. **Licensing claims:** staging activation policy, Personal Lifetime support/update terms and licensing behavior were not changed or verified against the licensing service. Keep those public answers pending policy confirmation. No high-volume creator capacity guarantee is made.

The local implementation task is complete within this owner-confirmed scope. Stripe and other unexecuted provider checks remain unverified; manual publication is excluded. Completion does not claim provider certification, licensing-policy confirmation or a published release.
