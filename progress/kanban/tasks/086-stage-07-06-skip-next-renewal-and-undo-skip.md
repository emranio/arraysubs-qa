---
id: 86
title: 'stage-07: 06 Skip Next Renewal and Undo Skip'
status: closed
priority: high
created: 2026-05-19T22:56:10.154627767+02:00
updated: 2026-05-20T14:52:11.508318189+02:00
started: 2026-05-20T13:41:52.886419928+02:00
completed: 2026-05-20T14:52:11.508317258+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-20T14:52:11.508318089+02:00
class: standard
---

Source: stages/07-customer-portal/06-skip-next-renewal.md

[[2026-05-20]] Wed 14:52
QA notes (2026-05-20, Chrome headless via agent-browser):
- Enabled skip_renewal and pause_subscription settings via WP-CLI because they were absent/null at Stage 7 start. On #633, Manage Your Subscription appeared with Skip Next Renewal and Pause Subscription. Original next payment: 20 June 2026 6:30 PM (UTC+6) / meta 2026-06-20 12:30:46.
- Skip prompt opened: heading Skip Renewal, number input min 1/max 3/default 1, Cancel and Continue buttons. Entered 2, Continue opened confirmation dialog stating skip 2 cycles and subscription remains active.
- Clicking Skip failed with alert "Failed to skip renewal. Please try again." WP-CLI confirmed _skip_cycles_count=0, _skip_cycles_remaining=0, no original next-payment meta, and next payment unchanged. Logged issue #51.
- Because skip failed, could not verify Skipping 2 cycle(s) indicator, shifted next payment, Undo Skip, or skip/pause mutual exclusion.
Result: FAIL.
