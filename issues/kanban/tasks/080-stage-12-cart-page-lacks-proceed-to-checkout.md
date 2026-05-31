---
id: 80
title: 'stage-12: Cart page lacks Proceed to Checkout button'
status: closed
priority: medium
created: 2026-05-23T09:42:49.52543537+02:00
updated: 2026-05-24T10:17:38.193155074+02:00
started: 2026-05-24T10:11:11.631096556+02:00
completed: 2026-05-24T10:17:38.193154082+02:00
tags:
    - qa
    - stage-12
    - checkout
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:17:38.193154964+02:00
class: standard
---

Stage 12 Task 05 Subtask 5.2 and Task 04 observed. Cart page for Standard Weekly and Credit Pack shows item rows, coupon control, express checkout frames, and totals, but no explicit Proceed to Checkout button in the accessibility tree. Header Checkout link can be used as workaround. Expected cart checkout CTA.

[[2026-05-24]] Sun 10:15
Plan: widen existing Cart Block proceed-to-checkout fallback from trial-only carts to any ArraySubs subscription checkout cart when Woo renders the proceed block empty; then verify cart page shows an explicit Proceed to checkout CTA.

[[2026-05-24]] Sun 10:17
Fixed: updated ArraySubs Cart Block proceed-to-checkout fallback to render for any non-empty cart when Woo renders the proceed block without a submit button. Browser verified qa-credit-cart cart: Standard Weekly, Store Credit Applied -9.99, estimated total /bin/bash.00, Proceed to checkout link points to ?page_id=8.
