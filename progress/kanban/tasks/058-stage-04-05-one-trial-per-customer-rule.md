---
id: 58
title: 'stage-04: 05 One Trial Per Customer Rule'
status: closed
priority: high
created: 2026-05-19T22:56:06.476314965+02:00
updated: 2026-05-20T11:13:28.413461782+02:00
started: 2026-05-20T10:28:05.924304969+02:00
completed: 2026-05-20T11:13:28.413460961+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T11:13:28.413461702+02:00
class: standard
---

Source: stages/04-cart-rules/05-one-trial-per-customer.md

[[2026-05-20]] Wed 11:13
QA done with seeded cancelled-trial state. cust1 is user #5 / cust1@test.local; password reset for QA to QA-cust1-2026!. Created cancelled Trial Weekly subscription #306 with _trial_length=7 to represent prior completed/cancelled trial. One-trial setting enabled: logged-in cust1 add-to-cart for Trial Weekly blocked with exact expected text and cart empty. Standard Weekly no-trial add succeeded. Guest Trial Weekly add succeeded with /bin/bash.00 cart. Negative control with one-trial setting disabled: cust1 could add Trial Weekly despite prior trial. Setting restored enabled. Note: checkout purchase itself was not repeated here; prior trial state was seeded for cart-rule verification.
