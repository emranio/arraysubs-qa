# Storefront template override verification

Completed 2026-10-29 (Asia/Dhaka). Fresh plan: [plan.md](plan.md); QA tasks #1–3 in this directory's kanban board. All scoped checks passed. Existing qa/issues tasks were preserved; no new product defect was found.

## Result

Core owns one shared `ArraySubs\Supports\StorefrontTemplate` resolver. Every bundled storefront PHP view in core and Pro now resolves a readable child-theme file, then parent-theme file, then the original plugin file. Includes remain in their original scope. CustomerPortal nested partials use plugin-anchored defaults, so a copied page does not require copying its sibling partials. Admin views and WooCommerce email loading retain their existing behavior.

The specific Members Access theme override precedes the existing generic theme `403.php`; the generic file still precedes the bundled denied page. WordPress continues to load restricted comments through `comments_template()` with the service's prepared context.

## Browser coverage

Real local WordPress `http://localhost:10013`, running the changed workspace sources. Vercel agent-browser CLI with isolated customer/guest sessions and Microsoft Edge. Pro was active for the first pass, absent for the core-only pass, and restored afterward. The remote test site was not deployed. See [coverage summary](evidence/coverage-summary.json).

Every copied source template had a visible `QA CHILD <path>` marker. The following 15 distinct files rendered through actual customer/guest pages:

| View | Browser evidence |
| --- | --- |
| Core subscription list, cards, nested cancellation notice | [Screenshot](evidence/child-subscriptions.png), [markers](evidence/child-subscriptions-markers.json) |
| Core subscription details and nested notice | [Screenshot](evidence/child-detail.png), [markers](evidence/child-detail-markers.json) |
| Core order subscriptions | [Screenshot](evidence/child-order.png), [markers](evidence/child-order-markers.json) |
| Core simple-product subscription terms | [Screenshot](evidence/child-simple.png), [markers](evidence/child-simple-markers.json) |
| Core variable-product terms container | [Screenshot](evidence/child-variable.png), [markers](evidence/child-variable-markers.json) |
| Core Subscription Box builder | [Open builder](evidence/child-box-open-final.png), [markers](evidence/child-box-markers.json), [closed snapshot](evidence/child-box-closed-snapshot.txt) |
| Core access-denied document | [Screenshot](evidence/child-denied.png), [markers](evidence/child-denied-markers.json) |
| Core restricted comments template | [Screenshot](evidence/child-comments-template.png), [markers](evidence/child-comments-template-markers.json) |
| Pro My Features | [Screenshot](evidence/child-features.png), [markers](evidence/child-features-markers.json) |
| Pro Store Credit | [Screenshot](evidence/child-credit.png), [markers](evidence/child-credit-markers.json) |
| Pro avatar section | [Screenshot](evidence/child-profile.png), [markers](evidence/child-profile-markers.json) |
| Pro fixed-period membership information | [Screenshot](evidence/child-fixed.png), [markers](evidence/child-fixed-markers.json) |
| Pro flexible product options | [Screenshot](evidence/child-flex-configured.png), [interaction values](evidence/child-flex-interaction.json) |

The copied box builder opened and closed normally. The copied flexible controls accepted Year and a length of 3. Links from the copied subscription card opened the correct details and order pages. No purchase, payment, cancellation, upload, or comment submission was required.

Child/parent resolution passed with different markers: [child wins while both files exist](evidence/priority-child-markers.json); [parent renders after the child is removed](evidence/parent-detail.png), still loading the independent child notice. With only the top-level child copies retained, [bundled cards and notice](evidence/nested-list-fallback.png) rendered and the [details notice](evidence/nested-detail-fallback.json) remained present.

After hiding both override directories, [browser-fallback.py](browser-fallback.py) checked 12 actual routes for expected content, absence of override markers, PHP critical-error text, and browser errors. All passed: [results](evidence/fallback-browser-results.json). This included list/details/order, Pro account pages, flexible/fixed/variable products, box, denied page, and comments. Representative fallback screenshots were visually inspected.

Both [generic theme 403](evidence/generic-403.json) and [specific namespaced override](evidence/specific-403.json) kept HTTP 403. Specific override won when both existed.

Core alone rendered the [copied detail page](evidence/core-only-detail.png), cards and table layouts without Pro. [State](evidence/core-only-state.json) confirms Pro absent; [table result](evidence/core-only-table.json) confirms the table with zero cards. The [390px detail screenshot](evidence/child-mobile-detail.png) kept details and order actions accessible. The disposable QA marker wraps separately from the unchanged product markup.

Authorization remained intact: customer 481 opening subscription 23967, owned by customer 479, received the [permission error](evidence/unauthorized-other-customer.json) before any theme template loaded. A guest opening the fixture subscription received the [login form](evidence/unauthorized-guest.json), with zero override markers.

## Supplemental checks

- [Resolver regression](resolver-regression.php): 67 assertions, zero skips. Covers precedence, standalone theme, namespaces, directory/unreadable file rejection, invalid paths, PHP include locals/`$this`, and actual copied list/nested-partial combinations. [Output](evidence/resolver-results.txt).
- [Actual WordPress integration regression](integration-regression.php): 235 assertions, 15 templates and 18 loader call sites; no skipped checks. Covers autoload, exact namespace/default paths, all classified admin views and unchanged email loaders, plugin-anchored nested defaults, real simple/variable/box service output, global restoration, and invalid detail requests. [Output](evidence/integration-results.txt).
- All 30 touched/new PHP files passed `php -l`; [syntax output](evidence/php-syntax.txt). Every source file remains below 3,000 lines; [line counts](evidence/line-counts.txt). Diff whitespace checks passed in core, Pro, manual and QA.
- The new `user-manual/markdowns/developers/template-overrides.md` is in the Developers menu and guide sequence. All 15 paths match implementation. Manual build passed for 156 pages; 1,090 rendered links/anchors checked across the affected developer pages passed.
- Both canonical changelog drafts preserve every earlier byte and append one entry for the appropriate plugin. No versions were changed.

Commands use the existing environment, without Composer installs or plugin asset rebuilds:

```sh
php qa/storefront-template-overrides-2026-10-29/resolver-regression.php
php -d mysqli.default_socket='/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock' /opt/homebrew/bin/wp --allow-root --path='/Users/emran/Local Sites/wp-test2/app/public' eval-file qa/storefront-template-overrides-2026-10-29/integration-regression.php
```

Pre-existing Elementor nullable-parameter notices appeared in WP-CLI output at `atomic-global-styles.php:410`; no related browser failure or changed-source PHP error was observed. No phpcs or expensive full-codebase validation was run.

## Fixtures and restoration

Disposable customer 481 (`qa_template_20261029`, `qa.template.20261029@example.test`, role customer), subscription 24098, order 24097, simple product 24095, and pages 24101/24102 were created for this cycle and removed. Existing published products 9624 (fixed), 6722 (variable), and 6729 (box) were visited without changing their data. Integration-only service renders also read existing box 5922. The disposable simple product was temporarily configured for full flexible billing to exercise Pro options; it was deleted afterward.

The comments fixture uses a real child-theme page template calling WordPress `comments_template()` and an ordinary saved hide-comments rule. This exercises the PHP comments view; block comments that render inline markup are not represented as extra PHP templates. The first block-all fixture correctly used the existing orphaned-notice path and was then changed to hide-comments to exercise the requested PHP view.

[Restored state](evidence/restored-state.json) verifies all seven backed-up theme/plugin/settings options, both plugins active, original Twenty Twenty-Five theme, Coming Soon=yes, removal of the child theme and parent override, and deletion of the fixture user/order/posts. Owned browser sessions and the temporary credential profile were closed/deleted; temporary credentials and private state backup were removed. Existing QA issues remain untouched. Changes are local and uncommitted; no release, push, deployment or manual publication was performed.
