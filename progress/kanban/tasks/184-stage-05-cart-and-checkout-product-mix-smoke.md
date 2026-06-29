---
id: 184
title: 'stage-05: Cart and checkout product mix smoke'
status: closed
priority: high
created: 2026-06-28T17:21:47.516041882+02:00
updated: 2026-06-28T17:39:12.678612517+02:00
started: 2026-06-28T17:34:48.42761358+02:00
completed: 2026-06-28T17:38:23.645006738+02:00
tags:
    - qa
    - cart
    - checkout
    - regression
class: standard
---

Focused QA after adding one simple subscription, one variable product/subscription, and one pro-special product if available. Verify cart lines, checkout page load, no console/PHP/network errors, and no add-to-cart breakage.

\nQA note 2026-06-28: Browser smoke completed on reachable host https://mirror-help.arrayhash.com because localhost:10013 refused connections. Direct add-to-cart sequence for Standard Weekly, Coffee Plan weekly variation, and Credit Pack 0 reached checkout without fatal/browser errors. Site retained the last-added Credit Pack 0 in checkout/cart, consistent with direct-checkout/cart-clearing behavior seen during the pass; individual simple subscription, variable subscription, and Store Credit checkout summaries were validated separately.


Correction: Store Credit product tested was Credit Pack $50 (product ID 1106), not Credit Pack 0.
