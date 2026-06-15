---
id: 13
title: 'stage-03: Cart missing proceed-to-checkout button for trial item'
status: closed
priority: medium
created: 2026-05-20T01:22:41.715882801+02:00
updated: 2026-05-22T04:30:24.100797649+02:00
started: 2026-05-22T04:21:54.96322795+02:00
completed: 2026-05-22T04:30:24.100796767+02:00
tags:
    - qa
    - stage-03
    - cart
    - trial
claimed_by: mold-glade
claimed_at: 2026-05-22T04:30:24.100797559+02:00
class: standard
---

Observed with agent-browser on 2026-05-20 after adding Trial Weekly #202 to cart. Cart showed item, trial metadata, /bin/bash estimated total, and no signup fee line. Expected Stage 03 task 02.4 tester path includes Proceed to checkout button. Actual: Proceed to checkout not visible; used header Checkout link to continue.

[[2026-05-22]] Fri 04:28
Plan: reproduce current block-cart Trial Weekly state, inspect cart page block configuration and checkout hooks, add a server-rendered proceed-to-checkout fallback inside the Woo proceed-to-checkout block only for trial-only subscription carts where Blocks fails to render the button, then verify fresh cart browser flow reaches checkout.

[[2026-05-22]] Fri 04:30
Fixed: added a server-rendered Proceed to checkout fallback inside the Woo Cart Block proceed-to-checkout block for trial-only subscription carts where the Blocks frontend omits the button. Verified fresh agent-browser flow: add Trial Weekly to cart, cart line and trial metadata load, visible Proceed to checkout link points to checkout, clicking it opens Checkout page.
