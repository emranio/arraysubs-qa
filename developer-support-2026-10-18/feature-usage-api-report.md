# Feature metering, customer/product API verification

Executed 2026-10-18, Asia/Dhaka, on the local WordPress checkout at `http://localhost:10013`. Fresh plan: `qa/developer-support-2026-10-18/plan.md`, Tasks 1 and 5–7. Existing QA issues were preserved. Tests use the site's existing local MySQL socket through PHP/WP-CLI with `--allow-root`; no Composer install or phpcs was run. The shared final source check validates PHP syntax separately.

## Results

| Check | Result |
| --- | --- |
| `feature-usage-regression.php` | 43 assertions passed |
| `customer-feature-product-api-regression.php`, both plugins | 50 assertions passed |
| Same API script, `--skip-plugins=arraysubspro` | 35 assertions passed |
| `feature-cycle-integration.php`, both plugins | 32 assertions passed |
| Same payment matrix, `--skip-plugins=arraysubspro` | 26 assertions passed |
| Twenty parallel independent WP-CLI increments | 20 succeeded, resulting usage exactly 20 of 20 |
| Replay of one concurrent operation ID | Success, usage remains 20 |
| New increment at exhausted quota | `feature_usage_limit_exceeded`, usage remains 20 |
| File-size checks | Every owned changed PHP file below 3000 lines; OrderIntegration remains below the limit |

Concurrency evidence: `feature-usage-concurrency.json`. Three bounded retries use jitter to avoid synchronized retry waves; a held lock still returns the documented busy error after those retries.

Metering tests cover the canonical mode vocabulary, max source/Unlimited, combine allocation and earliest reset, forced second-write transaction rollback, exact-name key collisions, overage privilege, zero floor, decimal allowance precision, tiny-amount quota enforcement, operation replay across periods, current-plan carryover, missing features, read-only rollovers, local midnight, a 23-hour DST day, WordPress week start, billing-cycle date edits and post-release WP_Post notifications.

The payment matrix uses actual WooCommerce order creation/payment hooks rather than reflection or replacement gateway results. It covers scheduled paid renewal, zero-total PaymentProcessor, Pro EarlyRenewManager followed by real order payment, signed pending-switch renewal application, failed/on-hold renewal orders, a proration order excluded from generic paid fan-out, pause/date changes, and the actual ManualController. Core box and Pro bundle listeners mirror both ordinary and manual paid cycles. The ordinary paid renewal retains both invoice/payment jobs and its original scheduled date anchor. Replaying the paid fan-out does not reset newly recorded usage. No email delivery claim is made by this matrix.

Customer/product API tests cover registered and guest identity, exact account-email lookup (including wildcard characters), account/billing/history separation, pre-save validation, conflicting email, profile schema/query pagination/ambiguity/types/choices/invalid dates, upload redaction without token writes, forced mixed profile/account persistence rollback, explicit unavailable module behavior, core-only reads/metering, local canonical product URL validation, custom variation attribute text containing spaces, independent variation paging and private variation visibility. Feature configuration tests cover existing-only updates, expected state, validation and module-off refusal.

All created test customers, products, subscriptions, orders and scheduled subscription jobs were removed by test cleanup. Profile configuration, timezone and week-start settings were restored. Pro was skipped per CLI invocation rather than deactivated on the site. Browser UI verification belongs to the shared cycle's browser evidence/report; these scripts complement it and do not replace that requirement.

Contract reference: `documentations/architecture/developer-customer-product-features.md`.
