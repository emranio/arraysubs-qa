# Pricing display follow-up — 2026-10-24

Scope: remove the duplicate cycle line, eliminate spacing between pricing detail rows, use `+ amount signup fee`, and match Plans pricing details in the WooCommerce product list, including trial text. This is targeted verification of the requested follow-up, not a new QA cycle.

Context: local site `http://localhost:10013`, administrator `admin` (user 1), isolated agent-browser session `plans-pricing`, desktop viewport 1600×1000 and narrow viewport 390×844. Subscription IDs and order IDs: N/A. Existing customer and subscription data were not changed.

Routes: `/wp-admin/admin.php?page=arraysubs-mainadmin#/plans` and `/wp-admin/edit.php?post_type=product`. Search both lists for `Pricing QA` to isolate the temporary draft fixtures during verification.

Browser results:

- Simple subscription #20483: regular 12.00$, sale 9.00$, `/ 2 months` shown once, `7 days free trial`, `+ 7.00$ signup fee`. Both admin lists showed the same amounts and detail text.
- Variable subscription #20485, variations #20487/#20489: 24.00$–59.00$ `/ month` shown once, shared `7 days free trial` and `+ 7.00$ signup fee` in both lists.
- Variable subscription #20491, variations #20493/#20495: differing month/year schedules had no misleading shared cycle suffix; both lists showed `Billing varies by variation`, `Trial varies by variation`, and `+ 7.00$ signup fee (some variations)`.
- Existing examples #5916, #5908, #5888, #5893, #5900 and #5905 retained monthly pricing, sale pricing, trial/signup details, box selection pricing, and bundle discounted pricing. The ordinary `QPC QA 20260910 Credit` product had no subscription details added; its row retained the WooCommerce sale price.
- Existing lifetime plan #6396 retained `Lifetime Deal` and `+ 5.00$ signup fee`. Existing different-renewal-price details remained visible.
- Pricing detail rows had computed margin 0px and line-height 16.8px; Plans' pricing container had gap 0px. WooCommerce's desktop Price column measured 185px and displayed the simple/sale/shared-variable price and cycle together.
- Narrow Plans layout: document width 390px at a 390px viewport, local table viewport 338px with scroll width 1120px, pricing gap 0px. No page-level horizontal overflow. No browser errors were reported.

Supplemental verification: all three fixtures rendered through the Plans catalog with Pro skipped in WP-CLI. PHP syntax checks passed for the shared formatter, subscription-product hooks and Plans catalog. Production build passed. All touched source files remain below 3000 lines.

Cleanup: all seven temporary product/variation records were permanently removed. The catalog returned to the original 35 plans; Pro remained active. No persistent site configuration was changed.

Screenshots: [Plans pricing](15-pricing-plans.jpg) and [WooCommerce pricing](16-pricing-woocommerce.jpg).
