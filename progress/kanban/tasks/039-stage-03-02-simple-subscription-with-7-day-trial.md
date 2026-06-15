---
id: 39
title: 'stage-03: 02 Simple Subscription with 7-Day Trial'
status: closed
priority: high
created: 2026-05-19T22:56:01.955448064+02:00
updated: 2026-05-22T04:30:29.167522816+02:00
started: 2026-05-20T01:09:24.160571177+02:00
completed: 2026-05-20T01:22:54.789588086+02:00
tags:
    - qa
    - stage-03
claimed_by: mold-glade
claimed_at: 2026-05-22T04:30:29.167522506+02:00
class: standard
---

Source: stages/03-products/02-simple-with-trial.md

[[2026-05-20]] Wed 01:22
Executed with agent-browser on 2026-05-20. Created Trial Weekly #202; admin Subscription tab persisted Week/1 length 0, trial 7 Day, signup 0. Product page showed trial copy and Subscribe Now, with known recurring display mismatch tracked in #12. Cart showed trial metadata and /bin/bash total, but no Proceed button (#13). Checkout showed recurring/trial//bin/bash total but no next-charge date and wrong authorization copy (#14). Cart cleanup attempted after test.

[[2026-05-22]] Fri 04:30
Issue #13 fixed: Trial Weekly block cart now shows server-rendered Proceed to checkout fallback and opens checkout in browser verification.
