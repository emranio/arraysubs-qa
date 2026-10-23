# Subscription Plans feature QA

Date: 2026-10-24. Time zone: Asia/Dhaka. This is a fresh, feature-scoped development verification cycle requested by the user. It does not reuse the removed subscription-lifecycle plan or create a recurring automation.

## Scope and environment

- Local site: `http://localhost:10013`.
- Admin entry: `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans`.
- Product editor: `http://localhost:10013/wp-admin/post.php?post=PRODUCT_ID&action=edit`.
- Products list: `http://localhost:10013/wp-admin/edit.php?post_type=product`.
- Products: ArraySubs core and ArraySubs Pro. Verify licensed premium behavior where the local environment supports it, followed by core-only behavior with the prior plugin state restored.
- Required result: a Plans menu before Subscriptions; a Subscription Plans page listing subscription products with useful WooCommerce-like product details, filtering, sorting, pagination and actions; shared creation/billing-edit modal; separate Edit product destination; no Quick Edit action.
- Use the existing authenticated local administrator session or the credential source documented for this local site. Never substitute live-site credentials for local credentials without evidence that they apply.
- Progress board: `qa/subscription-plans-2026-10-24/progress/kanban/`.
- Execution schedule: `qa/subscription-plans-2026-10-24/schedule.md`.
- Evidence directory: `qa/subscription-plans-2026-10-24/evidence/`.

## Browser and evidence rules

Use the installed Vercel agent-browser CLI. Load `agent-browser skills get core` before browser work and `agent-browser skills get dogfood` for exploratory checks. Navigate with `open`, inspect with `snapshot -i`, act through fresh refs, and re-snapshot after DOM changes. Keep admin, customer and guest contexts in isolated named sessions. Capture screenshots before judging layout or interaction behavior.

Each task must record the execution date, route, browser/session and viewport, affected product and variation IDs, relevant user ID/login/role, exact actions, expected and actual results, and screenshot/DOM/console/network proof where useful. Subscription and order IDs are N/A unless a test creates or affects them. Record persisted product values when they resolve ambiguity. Browser verification is mandatory; WP-CLI or REST evidence may supplement it. Use `wp --allow-root` for required WP-CLI commands and do not add QA environment variables.

Move one progress task to in-progress before executing it. Append outcomes and evidence to its existing body, then move it to done only when its scope passed. Use review/blocked evidence when a case cannot be completed. Before every progress-board kanban command, change into `qa/subscription-plans-2026-10-24/progress/kanban/`. Do not run QA tracking commands from the plugins root.

## Test inventory

1. Navigation and product listing: menu label/order/focus/route; page title; subscription-only membership; expected WooCommerce-like detail columns; status and product-type filters; search, sorting, pagination and empty states.
2. Simple creation: Add new plan launches shared creation modal; required-field and price validation; create and publish one plan; confirmation and loading state; single submission; success destination links; list refresh.
3. Simple billing editing: correct existing values; edit/save/reopen; billing interval/period/length, trial/signup and renewal price; lifetime normalization; premium toggles and disabled-state preservation; unrelated product data/status/scheduled sale preservation; cancel/discard behavior.
4. Variable billing editing: variable parent representation; variation selection; per-variation prefill/save/reopen; sibling, attributes, parent type, pricing and publication state preservation; expected existing WooCommerce variable-creation guide.
5. Compound premium plans: subscription box and Pro bundle configuration prefill/save/reopen; contents, billing/sync/discounts and derived prices; allowed and invalid schedules/contents; existing item preservation.
6. Product row and bulk actions: Edit subscription billings versus Edit product; view/preview; duplicate if provided; featured, trash, restore and delete where offered; status/bulk selections; confirmation/loading/feedback; no Quick Edit anywhere on this page.
7. Failure, access and core-only behavior: invalid/missing product; stale or denied requests; failed saves retain values and permit retry; duplicate clicks; capabilities and guest/customer denial; unavailable module behavior; core-only listing/create/edit without Pro errors; restore original environment state.
8. Responsive and final regression: desktop and 390 x 844 mobile; keyboard navigation, modal focus/escape/scroll; useful table access; admin scheme accents; console/network errors; clean fixtures; revisit Plans, Subscriptions, Home and WooCommerce Products.

## Fixtures and preservation

Use clearly named feature fixtures with prefix `Plans QA 2026-10-24`. Record every created ID, original values for any pre-existing object edited, and original plugin/module state. Prefer dedicated fixtures over customer or production-like records. Generate enough dedicated plans for meaningful pagination when existing records do not provide it. Cleanup only fixtures created for this cycle after collecting evidence; restore original pre-existing values and plugin/module state.

Do not install Composer dependencies or run expensive validation as part of this QA plan. Feature verification should use the built environment and real browser flows.

## Existing issues and new defects

The existing `qa/issues/` board was inspected before this cycle. Preserve all marked issues. In particular, unrelated issue #27 (`qa/issues/tasks/027-quick-product-completion-card-descriptions-run.md`, in-progress) concerns completion-card typography, and #26 (`qa/issues/tasks/026-shared-admin-header-overflows-the-mobile-viewport.md`, open) concerns the shared mobile header. Their existing reports remain authoritative; do not silently close, overwrite or treat them as new Plans regressions.

Log new feature defects in the existing `qa/issues/` board, running kanban commands from `qa/issues/`. Every new issue must include this plan path, progress task ID and scheduled day; product/variation IDs and subscription/order IDs or N/A; user/customer IDs, login/email and roles or N/A; exact URL, session, steps, expected/actual behavior, concrete proof, and scope/counterexamples. Add the issue ID to the corresponding progress task, fix within authorized feature scope, and repeat the real browser case before closing it.

## Completion criteria

All eight progress tasks have concrete execution results, with passed cases distinguished from blocked/unavailable cases. Creation and billing editing persist correctly for supported product types, list actions work, no new feature regressions remain, original state is restored, and fixture cleanup is recorded. The user-facing report must state any unverified premium/core-only cases or remaining known issues accurately.
