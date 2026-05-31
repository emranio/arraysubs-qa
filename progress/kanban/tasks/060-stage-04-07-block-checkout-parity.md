---
id: 60
title: 'stage-04: 07 Block Checkout Parity'
status: closed
priority: high
created: 2026-05-19T22:56:06.660044819+02:00
updated: 2026-05-20T11:29:02.438975979+02:00
started: 2026-05-20T10:28:05.92748531+02:00
completed: 2026-05-20T11:29:02.438974537+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T11:29:02.438975869+02:00
class: standard
---

Source: stages/04-cart-rules/07-block-checkout-parity.md

[[2026-05-20]] Wed 11:29
QA done. Confirmed Woo block pages: Cart page #7 uses <!-- wp:woocommerce/cart --> and Checkout page #8 uses <!-- wp:woocommerce/checkout -->. The Stage 04 cart-rule checks were executed in the block cart/checkout UI and produced the same behavior as earlier checks: mixed cart issue #22, multiple subscription issue #23, one-per-product issue #24, different-cycle issue #25. Defaults restored. Trial Weekly block checkout summary showed recurring 9.99 every week, 7 days free trial, due today/total /bin/bash.00. Next charge date was not visible, aligning with existing checkout summary defect #14/#20.
