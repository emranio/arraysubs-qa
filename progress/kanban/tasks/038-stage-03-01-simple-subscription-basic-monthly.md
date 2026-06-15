---
id: 38
title: 'stage-03: 01 Simple Subscription (Basic Monthly)'
status: closed
priority: high
created: 2026-05-19T22:56:01.722077991+02:00
updated: 2026-05-22T04:21:50.013625594+02:00
started: 2026-05-20T01:09:24.159420472+02:00
completed: 2026-05-20T01:18:18.308272077+02:00
tags:
    - qa
    - stage-03
claimed_by: mold-glade
claimed_at: 2026-05-22T04:21:50.013625344+02:00
class: standard
---

Source: stages/03-products/01-simple-subscription-basic.md

[[2026-05-20]] Wed 01:18
Executed with agent-browser on 2026-05-20. Created Basic Monthly #197 through Woo product editor; admin Subscription tab persisted Month/1/0, trial 0, signup 0. Storefront query URL opened product, but admin permalink opened blog and price formatting did not match expected; issues #11 and #12 filed. Created Standard Weekly #200 and verified admin Subscription tab Week/1/0, trial 0, signup 0; storefront Subscribe Now visible, display issue appended to #12.

[[2026-05-22]] Fri 04:21
Issue #12 fixed: Basic Monthly and Standard Weekly storefront/catalog price display now use compact billing suffixes; duplicate Every-period and default product-page shipping notice removed for plain simple subscriptions.
