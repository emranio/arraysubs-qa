---
id: 29
title: Quick-product selected radio inner circle is not reliably centered
status: closed
priority: low
created: 2026-09-14T03:23:40.279426+06:00
updated: 2026-09-14T03:25:27.242624+06:00
started: 2026-09-14T03:25:27.245032+06:00
completed: 2026-09-14T03:25:27.245032+06:00
tags:
    - bug
    - admin-ui
    - quick-product
class: standard
---

Active QA task ID and scheduled day: N/A (targeted user-reported UI fix). Plan markdown path: N/A.
Affected subscription/order/product IDs: N/A (product-type selection only).
Affected WordPress user/customer: admin, administrator; numeric ID/email N/A.
Exact URL: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/overview
Browser context: agent-browser quick-radio, local authenticated administrator, 1334x886 desktop and 390x844 mobile.
Reproduction: Open + Subscription Product. Inspect the inner dot of the selected Simple subscription product radio.
Expected: Inner circle remains centered within the outer circle at desktop and mobile widths.
Actual: User reports an off-center inner dot. The quick-product control inherits WordPress floated pseudo-element positioning and size-dependent margins (8px dot with 3px computed margins on desktop; 9px dot with 7px margins on mobile).
Proof: User browser marker at input inside .arraysubs-quick-product__type.is-selected; /tmp/quick-radio-before.png; /tmp/quick-radio-before-detail.png; /tmp/quick-radio-before-mobile.png.
Scope/counterexamples: Scoped to the product-type picker; unchecked radios have no dot. Other form controls are not in scope.
Fix plan: Explicitly center the checked pseudo-element relative to its input, remove inherited float/margins, rebuild core, and verify both selection states and keyboard navigation in the real browser at both widths.
Repro video: N/A (static visual issue).

Resolution: Scoped the rule to quick-product radio inputs and positioned the checked pseudo-element at 50%/50% with translate(-50%, -50%), clearing inherited float and margins. Existing sizes, admin-scheme colors, focus indicators, and native radio behavior remain intact.
Verification: Core production build passed. Real browser screenshots inspected at 1334x886 and 390x844. The selected dot's computed center delta is [0, 0] at both sizes (16px/8px desktop input/dot; 25px/9px mobile). Simple subscription and Subscription box selection both render correctly; clicking switches the selected option, ArrowLeft returns selection to Simple subscription, and exactly one input stays checked. Browser errors: none. No product or site data was saved.
After screenshots: /tmp/quick-radio-fixed-desktop.png; /tmp/quick-radio-fixed-detail.png; /tmp/quick-radio-fixed-box-desktop.png; /tmp/quick-radio-fixed-mobile.png; /tmp/quick-radio-fixed-box-mobile.png.
Core/Pro scope: One core SCSS component changed. Pro consumes the same quick-product picker; no Pro source edits needed. Other radio styles unchanged. Source line count: 256. Diff whitespace check passed.
