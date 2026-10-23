# Subscription Plans navigation follow-up — 2026-10-24

Targeted verification of the requested UI changes; no new QA cycle or board. Local administrator `admin` (user 1), isolated agent-browser session `plans-navigation`, desktop viewport 1600×1000. Subscription/order IDs: N/A.

- `/wp-admin/admin.php?page=arraysubs-mainadmin#/plans`: WordPress submenu and shared page header both read `Subscription Plans`; browser title reads `Subscription Plans - ArraySubs`.
- Product #9624 showed `Simple subscription` and `Published` as compact pills (computed border radius 999px). Type tint follows the WordPress accent; Published uses semantic green. Draft examples used neutral pills.
- Clicking View for #9624 opened `/product/test-3/` in a new tab, with the Subscription Plans list retained in the original tab. The anchor has `target="_blank"` and `rel="noopener noreferrer"`. Preview uses the same attributes.
- Temporary draft product #20589 (`Plans navigation QA`) was opened through the product name and through Edit product. Both links carried `arraysubs_from=plans`, and the editor header action read `Back to plans` with the exact list URL above.
- Saving the temporary draft through the real WooCommerce Save Draft button displayed `Product draft updated`, preserved the origin, and retained Back to plans. Clicking it returned to the populated list.
- Opening the same product through WooCommerce's normal product list (`/wp-admin/edit.php?post_type=product&s=Plans+navigation+QA`) used an editor URL without the marker, displayed `Add new product`, linked to `/wp-admin/post-new.php?post_type=product`, and had no hidden origin field.
- The Plans creator's completion Edit product link carries the same origin marker; production compilation verified this conditional JSX path. Product creation was not repeated in this follow-up.

Production build and the three changed PHP files' syntax checks passed. No browser errors were reported. All touched source files remain under 3000 lines. Temporary product #20589 was permanently deleted after the browser closed; existing products, customers, orders and subscriptions were not saved or changed.

Screenshots: [Subscription Plans pills](17-subscription-plans-pills.jpg), [product return action](18-product-back-to-plans.jpg).
