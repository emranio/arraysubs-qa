# REST and data preservation supplement

Execution day: 2026-10-24, Asia/Dhaka. Active progress task: **#9**, claimed by `warsaw-lucrify`. Plan: `qa/subscription-plans-2026-10-24/plan.md`. Scheduled supplement to the browser verification performed under tasks #1–#8.

**Result: 104 checks passed, 0 failed** — 56 with Pro loaded and 48 with only core loaded. No product code changes were made by this supplemental test task.

## Method and limits

The durable runner is `../scripts/rest-data-supplement.php`. It creates dedicated simple-product fixtures and temporary users, then calls **the registered WordPress REST routes through `rest_do_request()`**. The same route registration, permission callbacks, controller validation and persistence paths execute as browser requests. This is supplemental REST/data coverage; it does not claim HTTP cookie/nonce authentication, browser layout, modal behavior or gateway checkout verification. Those belong to the browser tasks.

All commands used `/tmp/plans-wp`, which runs WP-CLI with `--allow-root`, the existing Local site's MySQL socket and `--skip-plugins=elementor,elementor-pro`. Elementor skipping isolates unrelated plugin bootstrap failures. The core-only invocation additionally skipped `arraysubspro` for that one CLI process. No global plugin activation/module settings were changed.

```sh
/tmp/plans-wp eval-file 'qa/subscription-plans-2026-10-24/scripts/rest-data-supplement.php' pro
/tmp/plans-wp --skip-plugins=elementor,elementor-pro,arraysubspro eval-file 'qa/subscription-plans-2026-10-24/scripts/rest-data-supplement.php' core
```

Detailed machine-readable observations: `rest-data-pro.json` and `rest-data-core.json` in this directory.

## Passed coverage

| Area | Expected and observed |
| --- | --- |
| Existing plan read/catalog | Existing private simple plan loads saved prices, billing values and a version; appears in the subscription-only catalog. |
| Invalid updates | Wrong type, array-shaped title/description, empty title, zero regular price, negative or excessive sale price, malformed/nested settings, invalid interval/period, missing version, malformed JSON and oversized body are rejected with 400/409. Before/after snapshots confirm no changes. |
| Valid update and concurrency | Valid interval/trial/signup updates persist; the previous version then receives 409 and cannot overwrite newer values. |
| Unrelated data preservation | Status, slug, SKU, managed-stock flag/quantity/status/backorders, weight/dimensions, tax status, sold-individually, purchase note, short description, reviews, menu order, catalog visibility, custom attributes and unrelated custom metadata remain unchanged. Future sale start/end dates, regular/sale prices and current active price also remain unchanged. |
| Nonplans/missing products | Both billing GET and POST reject ordinary non-subscription products; missing product GET rejects; row actions reject nonplans and unknown action names. |
| Anonymous/customer permission | Plans, counts, options, billing reads/updates and featured mutation all return 401/403 in anonymous/customer contexts. |
| Editor without publish capability | A temporary editor with `manage_woocommerce` and product edit capabilities, but explicit `publish_products=false`, can list, read options, read billing and update an existing plan. Product creation receives 403. |
| Pro optional billing fields | Different renewal price, fixed period membership, flexible mode/renewal sync and physical shipping overrides enable successfully. Disabling the settings, or changing to virtual for shipping, removes their owned inactive metadata. |
| Lifetime | Saving lifetime normalizes interval to 1, length/trial to 0. With Pro loaded, incompatible different-renewal-price, fixed-end and sync metadata clears. |
| Core-only | Reads, edits, validation and permissions pass with Pro skipped. A core billing edit preserves deliberately populated unavailable Pro metadata. |
| Trash | Trash action succeeds; trashed-plan billing GET/POST return 404; restore succeeds. |

## Context, fixtures and cleanup

Site: `http://localhost:10013`. Routes: `/wp-json/arraysubs/v1/plans`, `/plans/counts`, `/plans/PRODUCT_ID/actions`, `/quick-products/options`, `/quick-products/PRODUCT_ID` and `/quick-products` under that namespace. Browser/session/viewport: N/A for this CLI supplement. WordPress administrator: user **1**, login `admin`, administrator. Subscription IDs and order IDs: **N/A**.

| Run | Dedicated plan | Nonplan | Temporary customer/editor users | Cleanup |
| --- | --- | --- | --- | --- |
| Initial Pro harness run | 20086 | 20088 | 441 / 442 | All removed. The first runner stored assertions in a separate PHP global scope, so its summary had no counted checks; that harness bookkeeping was corrected and the entire suite rerun. No product failure occurred. |
| Final Pro | 20093 | 20095 | 443 / 444 | All removed. |
| Final core-only | 20104 | 20106 | 445 / 446 | All removed. |

Every fixture name begins `Plans QA 2026-10-24 REST supplement`. Final JSON evidence records exact temporary customer/editor logins and roles. A final independent WP-CLI read confirmed that all six product IDs and all six user IDs above are deleted. Root browser fixture IDs **20064, 20066, 20068, 20070, 20072, 20074 and 20077 were not modified**. Existing QA issues were preserved; no new reproducible defect was found in this supplemental suite.
