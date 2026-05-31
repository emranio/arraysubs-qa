---
id: 15
title: 'stage-03: Signup fee label and next charge summary mismatch'
status: closed
priority: high
created: 2026-05-20T01:26:27.605683619+02:00
updated: 2026-05-22T00:42:51.258909283+02:00
started: 2026-05-22T00:38:03.973739639+02:00
completed: 2026-05-22T00:42:51.25890801+02:00
tags:
    - qa
    - stage-03
    - cart
    - checkout
    - signup-fee
claimed_by: mold-glade
claimed_at: 2026-05-22T00:42:51.258909183+02:00
class: standard
---

Observed with Alumnium on 2026-05-20 for Signup Fee Weekly #206. Product/admin fields persisted; product page showed '+ .99 signup fee'. Cart/checkout total was 4.98 and renewal amount 9.99. Expected separate fee line labeled exactly 'Subscription Signup Fee' and checkout next charge date roughly 1 week out. Actual label rendered as 'Signup fee: .99 (one-time)' in metadata/summary; next charge date not visible. Cart Proceed to checkout also not visible, same pattern as issue #13.

[[2026-05-22]] Fri 00:38
Verified Stage 03 Task 03 spec. Expected cart/checkout fee row label exactly 'Subscription Signup Fee' with .99, checkout today's charge 9.99 + .99 signup = 4.98, and next charge about one week out. Code review: signup fee fee row already uses exact label via addSignupFeeToCart(), but cart/checkout item metadata uses 'Signup fee' and block checkout may surface only that label. Plan: reproduce in browser for product #206, then align visible metadata/summary label/copy without changing fee calculation; keep checkout next-charge summary from issue #14; verify cart and checkout with Alumnium, then close or block.

[[2026-05-22]] Fri 00:42
Browser QA for Signup Fee Weekly #206 now passes core issue. Cart page shows item recurring row, Signup fee metadata, Today's charge 9.99 + .99 signup = 4.98, Next charge 29 May, 2026 (UTC+6), exact totals fee row 'Subscription Signup Fee .99', and Estimated total 4.98. Checkout order summary shows same plus exact 'Subscription Signup Fee .99' and Total 4.98. Payment methods visible. No new code patch needed beyond issue #14 summary patch plus existing fee-row code. Cart missing proceed-to-checkout button remains a duplicate of issue #13, still open for separate fix.
