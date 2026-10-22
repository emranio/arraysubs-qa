---
id: 88
title: Variation-specific product features never render after selecting a variation
status: closed
priority: medium
created: 2026-10-23T02:22:37.998349+06:00
updated: 2026-10-23T02:41:09.633276+06:00
started: 2026-10-23T02:23:19.858494+06:00
completed: 2026-10-23T02:41:09.620378+06:00
tags:
    - bug
    - storefront
    - feature-manager
    - variations
class: standard
---

Source-confirmed during active QA #86 (2026-10-23), plan qa/storefront-account-boundaries-2026-10-23/plan.md; scheduled day 2026-10-23 targeted manual run.
Affected product/variation: existing product #6722 Demo Membership Tier, variations #6723/#6724 with enabled Library Access, Monthly Downloads, Masterclasses, Team Seats and Support Level features. Subscriptions/orders: N/A (product rendering). Customer/WordPress user: N/A (guest-visible product); browser will use isolated guest/customer on local http://localhost:10013.
Exact URL: http://localhost:10013/product/demo-%c2%b7-membership-tier/ .
Reproduction: with Pro Feature Manager live, open a variable product with variation-level feature overrides and select a variation. Expected: selected variation shows its enabled features; inherited features render appropriately, reset clears/restores the correct list. Actual source: FeatureManager/Services/Hooks.php:454 returns without rendering anything when any variation has its own features, and no corresponding frontend variation payload/handler exists. Browser confirmation pending; do not claim a completed visual repro until captured.
Scope notes: simple products already render feature lists; internal text/number/toggle metadata must remain private. Any new public variation payload may contain safe rendered feature markup only, no raw configuration or type-derived classes/data attributes.
Plan before implementation: inspect existing feature retrieval/sanitization and adjacent variation JS patterns; add a stable feature wrapper plus public-safe variation markup, listen to Woo variation selection/reset with existing jQuery or suitable vanilla event mechanisms, preserve simple-product rendering and module gating, verify both variants/inheritance/reset/Pro absent in browser. No checkout/order placement required. Keep all source files below 3000 lines and leave changelog drafts for lead to append.

[[2026-10-23]] Fri 02:25
Implementation started in /tmp/arraysubs-variable-features on codex/storefront-variable-features (claim bang-sural). Root replaced malformed product #6722 for browser QA with isolated product #19371 and variations Basic #19373, Pro #19375, Inherited #19377 at /product/qa-boundary-variation-features-20261023/. Fixture details: qa/storefront-account-boundaries-2026-10-23/created-fixtures.json. Source plan: extract storefront renderer; reuse core enabled/inherited feature reader; public variation payload contains escaped HTML only; scope JS select/reset handling to the correct product panel; share existing list styles. No build/site/draft changes by this worker.

[[2026-10-23]] Fri 02:28
Source implementation complete; verification before integration: 15 isolated PHP checks passed for explicit overrides, parent inheritance, disabled own lists, public markup privacy, escaped text, ordering, simple empty state, unrelated-parent rejection, and display-setting-off registration. 10 isolated DOM checks passed for select/change/inherited/empty/reset/unmatched/missing data and two-product isolation. Harnesses: /tmp/arraysubs-variable-features-check.php and /tmp/arraysubs-variable-features-check.cjs (no WordPress bootstrap, database writes, network, or site mutation). All six source files are below 3000 lines. No lint/PHPCS, build, deployment, fixture mutation, or draft edits performed. Browser acceptance remains pending lead integration; task stays in-progress.

[[2026-10-23]] Fri 02:29
Source committed as 599385202f2db1367b11ecf8601c296850197a84 (codex/storefront-variable-features; clean worktree /tmp/arraysubs-variable-features). Six source files: Hooks.php storefront extraction; new ProductFeatures service; new featureManagerFrontend.js; shared product-list SCSS partial and existing admin import; Pro webpack entry. Peer source review by endpoint_audit found no actionable privacy/inheritance/disabled-row/reset issue. QA_access captured actual pre-fix fixture #19371 Basic/Pro/Inherited selections with no feature list in qa/storefront-account-boundaries-2026-10-23/browser/pro/variation-{basic,pro,inherited}-before.{png,json}. Lead will integrate/build and run browser acceptance. Task intentionally stays in-progress until that verification.

[[2026-10-23]] Fri 02:41
Browser acceptance confirmed by lead/qa_access on the real local product page after correcting ONLY disposable fixture metadata to the canonical name/type/value schema. Basic #19373 shows Basic download credits 10; Pro #19375 shows Pro download credits 20; Inherited #19377 shows Parent library credits 5; Reset hides the stable wrapper for this mixed-override product. Public variation payload contains only escaped arraysubs_features_html, and type-derived attribute scan was empty. Evidence: qa/storefront-account-boundaries-2026-10-23/browser/after/variation-basic-valid.{png,json}, variation-pro-valid.{png,json}, variation-inherited-valid.{png,json}, and variation-reset.{png,json}. Source commit599385202f2db1367b11ecf8601c296850197a84 is merged and built by lead. Important evidence limit: original before screenshots and first blank after payload came from malformed label-only fixture rows, so those images do not independently prove the bug for valid-schema products. The confirmed pre-fix source gap (early return plus no frontend renderer/payload) is the defect evidence; corrected valid-schema browser passes verify the implemented behavior. Existing product #6722 was not modified. Closing after successful integration/browser verification.
