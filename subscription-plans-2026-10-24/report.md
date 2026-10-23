# Subscription Plans verification report

Current behavior is documented in the [native editor follow-up](native-editor-follow-up.md): existing-plan editing now uses WooCommerce, replacing the modal tested in this historical report.

The creation wizard's subscription-type priority and clearer renewal-sync notices are verified in the [plan type follow-up](plan-type-follow-up.md).

Completed October 24, 2026, Asia/Dhaka, against the real local development site `http://localhost:10013`. The new Plans menu is directly before Subscriptions and opens the Subscription Plans page at `#/plans`.

## Result

All scoped checks passed after repairing three issues found during this cycle: edit-mode review wording (#99), failed async confirmations closing rather than retaining a retryable error (#102), and mobile toolbar/table layout (#103). Those issues were retested in the browser and closed in the existing `qa/issues/` board. Existing unrelated issues, including #26 and #27, remain preserved.

The page lists subscription products with image, name/ID/type/status, SKU, stock, price and billing, categories, tags, featured status and date. Search, product-type/status filters, Name/Date sorting, pagination, row actions and bulk actions work. There is no Quick Edit action. Edit subscription billings opens the shared saved-plan modal; Edit product opens the WooCommerce editor. Add new plan opens the shared creation modal restricted to subscription product types.

## Coverage and proof

| Area | Verified result | Evidence |
| --- | --- | --- |
| Navigation and list | Plans before Subscriptions; keyboard route/back; subscription-only parent rows; all four types; status filters, search, empty states, sort reversal and pagination; correct edit destinations | [List report](evidence/list-qa.md), `list-controls.jsonl`, `list-02..05` screenshots |
| Simple creation | Real eight-step creation, required-field validation, confirmation/loading, one published result, success links and list refresh | Dedicated product20077, progress task2 |
| Simple billing editing | Saved/reopened prices, interval/length, trial/signup and shipping. Kept SKU, stock17, status, visibility, sold-individually, scheduled-sale dates and short description | `03-simple-updated.jpg`, fixture baseline and progress task3 |
| Variable billing | Chose variation20068, changed price11→16 and interval1→2; reopened16. Sibling20070 stayed22/interval1, attributes/parent type/draft status stayed intact; inherited one-time shipping remained inherited | `04-variation-price.jpg`, progress task4 |
| Variable creation | Existing guided flow links to WooCommerce Add new product for attributes/variations; wizard guide and destination verified. This remains the established creation behavior | Final real-browser snapshot, progress task8 |
| Box and bundle | Loaded, changed, saved and reopened each configuration. Contents, required items, discount, derived bundle price and draft status preserved. Invalid labels/cycles blocked; nested keep/discard worked | [Compound report](evidence/compound-qa.md), `compound-01..09` screenshots |
| Row and bulk actions | Draft duplication with unique SKUs, featured toggle, row trash/restore; selected two fixture copies for bulk trash/restore/permanent deletion; original retained | Progress task6; fixture copies20102/20119 removed |
| Errors and permissions | Real stale409 refused overwrite and retained values. Two failed retries kept the confirmation open with visible error; cancel/reopen then save succeeded. Guest browser request returned401 | `06-stale-save-retained.jpg`, `07-stale-retry-stays-open.jpg`, progress task7 |
| Core-only | Temporarily deactivated Pro, asserted core menu, edited20077 to38 while preserving stored premium shipping, created Core Only20179 through the real wizard, proved `pro:false`, restored Pro | [Core browser log](evidence/core-browser.txt) |
| Mobile/accessibility | At390x844, page width390; toolbar fits; table scrolls independently to700. Modal350x804 stays within viewport; footer visible, focus inside, Tab/Shift-Tab and Escape work | `10..13-mobile` screenshots, progress task8 |
| Existing UI regression | Subscriptions still loads20 rows and649 total subscriptions; no visible errors. Final desktop Plans displays original35 plans | `14-final-desktop-plans.jpg`, final browser observations |

## Automated and source checks

- **104 registered WordPress REST/data assertions passed**:56 with Pro and48 in an isolated core-only process. These cover invalid data/type changes, stale versions, authentication and per-product permissions, editing without publishing capability, optional metadata cleanup, lifetime normalization, scheduled-sale preservation, and unavailable-Pro metadata retention. [Detailed results](evidence/rest-data-report.md).
- **4 focused React tests passed** for core and Pro shared confirmation helpers: repeated failures then successful retry, and cancellation/new-dialog error reset. Run from `arraysubs`: `node_modules/.bin/jest --config jest.plans.config.cjs --runInBand`.
- Both plugin production builds passed. All touched PHP files passed syntax checks, all touched code files are below3000 lines, and both plugin diffs pass whitespace checks. PHPCS was not run.
- Browser testing used Vercel agent-browser and actual WordPress pages. REST/data assertions supplement the UI testing. A PNG capture/CLI queue stall was recovered with a separate CDP session; JPEG screenshots then completed normally. It was not treated as an application failure.

## Cleanup and restored state

All dedicated products and temporary users from this cycle were removed. Root fixtures20064,20066,20068,20070,20072,20074,20077 and20179 were deleted; copies20102/20119 were already permanently deleted through the UI. Supplemental fixtures/users were independently cleaned by their runner. No existing customer subscriptions or orders were created or edited.

The final catalog contains the original **35 plans**, ArraySubs Pro is active, and every module switch equals the recorded initial state. [Cleanup proof](evidence/cleanup.json). No unresolved feature defect remains in this cycle.
