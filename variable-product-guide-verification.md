# Variable subscription product guide verification

Date: 2026-09-14. Scope: targeted verification of the requested card and guide; no new formal QA cycle or schedule.
Site: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/overview
Context: isolated agent-browser session `variable-guide-admin`, authenticated local administrator.

## Results

- Variable Subscription Product appears directly after Simple, with its own radio selection, icon, and description. Premium and prerequisite cards retain their locked states.
- Next opens Step 2 of 2 with seven vertically connected, numbered instructions. The guide heading receives focus.
- Instructions cover the product editor, Variable product, Subscription [AS], Attributes, Used for variations, variation generation, per-variation prices and billing, and saving/publishing.
- The documentation link targets the requested variable-products anchor and opens in a new tab.
- Add new product was clicked in the browser. It opened the correct local WooCommerce product editor in a separate tab and preserved the guide in the original tab.
- Desktop 1440 × 1000 and mobile 390 × 844 screenshots were inspected. Mobile content scrolls independently, the footer remains visible, and the wizard body has no horizontal overflow.
- Previous returns to the type picker with Variable still selected. Switching to Simple and clicking Next opens Product details at Step 2 of 7.
- Product-editor verification used draft product 6162, “Variable Guide Browser Check 2026-09-14”. Selected Variable product, checked Subscription [AS], saved Plan attributes Monthly / Annual with Used for variations, and generated variations 6163 / 6165.
- Expanded both variations, configured prices and billing, saved changes, and reloaded. Browser field values persisted: 6163 = price 29, month, interval 1, trial 7 days; 6165 = price 249, year, interval 1, no trial.
- Temporary product 6162 was moved to Trash after verification. No product was published and no subscription or order was created.
- A direct variable quick-create request returned HTTP 400 with the instruction to use the WooCommerce editor, verifying it cannot fall through to creating an empty simple product.
- Browser JavaScript errors: none. Core production build succeeded, hash `0316e050e1dba80a3480`. Diff whitespace checks passed. All touched source files are below 3000 lines.
- Core owns the new option and guide. The Pro adapter was reviewed: it updates its own options by value and preserves the new core option. No Pro source changes were needed.

## Screenshots

- `/tmp/variable-product-picker-desktop.png`
- `/tmp/variable-product-guide-desktop.png`
- `/tmp/variable-product-guide-mobile-top.png`
- `/tmp/variable-product-guide-mobile-bottom.png`
- `/tmp/variable-product-billing-saved.png`

Existing QA issue records were preserved. No new product defect was found in the requested flow.
