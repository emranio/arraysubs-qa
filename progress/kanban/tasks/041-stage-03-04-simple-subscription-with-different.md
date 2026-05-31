---
id: 41
title: 'stage-03: 04 Simple Subscription with Different Renewal Price'
status: closed
priority: high
created: 2026-05-19T22:56:02.318724413+02:00
updated: 2026-05-22T04:45:42.975330875+02:00
started: 2026-05-20T01:09:24.162488911+02:00
completed: 2026-05-20T01:32:32.778649539+02:00
tags:
    - qa
    - stage-03
claimed_by: mold-glade
claimed_at: 2026-05-22T04:45:42.975330524+02:00
class: standard
---

Source: stages/03-products/04-simple-with-different-renewal-price.md

[[2026-05-20]] Wed 01:32
Executed with Alumnium on 2026-05-20. Created Stepped Weekly #209; admin Subscription tab persisted Week/1, Different Renewal Price checked, renewal price 29.99 after 3. Product/cart/checkout showed both 9.99 initial and 9.99 later tier, with wording 'Every week'/'After 3 renewals'. Untick collapsed renewal fields; config restored via WP-CLI. Filed #16 for missing next-charge date; recurring format/proceed-button issues covered by #12/#13. Cart cleanup attempted.

[[2026-05-22]] Fri 04:45
Issue #16 recheck: fresh Alumnium checkout for Stepped Weekly #209 shows renewal tier, Today charge USD 19.99, and Next charge 29 May, 2026 (UTC+6).
