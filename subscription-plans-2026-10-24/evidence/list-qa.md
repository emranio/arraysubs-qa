# Subscription Plans list QA result

Result: PASS for progress task #1. Executed 2026-10-24, Asia/Dhaka.

Plan: `qa/subscription-plans-2026-10-24/plan.md`.
Scheduled day: 2026-10-24.
Route: `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans`.
Browser: agent-browser 0.27.3 with real Chromium; isolated `plans-list-qa`, recovered `plans-list-recover`, and final `plans-list-final` sessions. Viewport: 1280 x 577 content area.
User: WordPress ID 1, login `admin`, local administrator; email N/A. Subscription/order/customer IDs: N/A. No product was created, edited, duplicated, trashed or deleted by this test task.

## Verified behavior

- WordPress submenu order starts Home, Plans, Subscriptions, followed by the existing menu order. Plans has `tabIndex=0`, receives focus and opens `#/plans` with Enter. Page title is Subscription Plans. Home and Subscriptions destinations load correctly, and browser Back returns through the expected routes.
- At the initial fixture baseline there were 38 plans: 28 published and 10 draft. The two pages contained 20 and 18 unique parent product IDs, with no duplicate IDs or variation children. Next, previous, first and last pagination controls returned the expected page populations.
- Initial type filters returned simple 27, variable 3, box 5, and bundle 3, totalling 38. Variable fixture #20066, box #20072 and bundle #20074 appeared in their correct filters. Variation IDs #20068/#20070 and ordinary control product #20064 were absent from the full plan population.
- Status filters returned the published/draft populations and useful empty states for pending review, private, scheduled and trash. The root QA session created simple fixture #20077 while these tests ran, so later totals correctly became All 39, Published 29, Draft 10. This is expected concurrent fixture activity rather than inconsistent counting.
- Search for `Plans QA 2026-10-24` returned four fixtures after #20077 existed. Combining that search with Variable returned only #20066. An impossible query and ordinary control ID `20064` returned `No subscription plans found.` Clearing the field with the keyboard restored the list and correct count.
- Name sorting changed between descending and ascending names. Date sorting changed between newest and oldest dates. Browser network observations confirmed corresponding `orderby=title&order=desc/asc` and `orderby=date&order=desc/asc` requests returning HTTP 200.
- Product rows display image, linked name, product ID, product type, publication status, SKU, stock, price/billing, categories, tags, featured control, date and row actions. Published products offer View; drafts offer Preview. The regular control and non-parent variation records are excluded.
- Product #9624 shows two distinct edit actions. Edit subscription billings opened the shared modal prefilled with title/description `test` and price 13; it was closed without changing any field. Edit product opened `post.php?post=9624&action=edit` in a new tab and showed WooCommerce's Edit product page for `test`; no Update action was taken. The tab was closed afterward.
- No Quick Edit action appears on Subscription Plans. The final DOM scan found zero matching links/buttons. No browser JavaScript errors were reported. Console output contained existing development notices, jQuery deprecation notices and WooCommerce editor `useSelect` warnings rather than a Plans exception.

## Concrete population evidence

Initial page 1 IDs: 20074, 20072, 20066, 9624, 6632, 6466, 6398, 6396, 6394, 6392, 6390, 6387, 6351, 6179, 5922, 5905, 5990, 5916, 5908, 5900.

Initial page 2 IDs: 5893, 5891, 5888, 6714, 6715, 6716, 6717, 6718, 6719, 6720, 6721, 6722, 6728, 6729, 6712, 6711, 6713, 6710.

## Evidence files

- `01-plans-list.png`: existing initial full-page screenshot, visually inspected before judging layout.
- `list-02-billing-modal.jpg`: existing simple product billing modal with prefilled values.
- `list-03-woocommerce-editor.jpg`: separate Edit product destination for #9624.
- `list-04-final-top.jpg`: final visible Plans page at All 39 / Published 29 / Draft 10.
- `list-05-final-full.jpg`: full list screenshot after checks.
- `list-controls.jsonl`: actual browser DOM results for pagination, type/status filters, combined search, empty states, sorting and keyboard clear.
- `list-network.txt`: real browser requests, including successful Plans sorting/count and quick-product load requests.
- `list-errors.txt`: empty browser error output.

## Tool observations and scope notes

PNG screenshot commands stalled the original agent-browser daemon queue. `doctor --offline --quick` reported a healthy install; reconnecting through the same isolated Chrome CDP port restored access, and a fresh final session completed the remaining checks. JPEG screenshots succeeded immediately and were inspected. This did not establish a feature defect.

The CLI's `fill @ref ""` left the search value unchanged; setting the field to `x` and pressing Backspace cleared it and restored results. Early JSONL entries named search-clear/sort-name/sort-date after the unsuccessful CLI clear reflect that retained query and are not claimed as successful sorting evidence. The later `final-sort-*` records contain the successful fresh-session sorting checks; their first/second ordering is documented by the actual names and request parameters, not the provisional ascending/descending case labels.

CLI automatic scrolling once placed a status link beneath the shared sticky header; scrolling to the top before clicking allowed normal status interaction. Existing unrelated issues #27 (completion-card typography) and #26 (shared mobile-header overflow) were preserved. This task did not perform mobile, mutation, premium configuration save, failure/access or core-only checks; those remain assigned to the other feature progress tasks.

New feature issue IDs: none.
