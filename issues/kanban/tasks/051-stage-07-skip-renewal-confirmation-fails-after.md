---
id: 51
title: 'stage-07: Skip renewal confirmation fails after valid cycle count'
status: closed
priority: critical
created: 2026-05-20T14:51:58.016669807+02:00
updated: 2026-05-21T22:25:01.01400438+02:00
started: 2026-05-21T22:10:25.463006168+02:00
completed: 2026-05-21T22:25:01.014003518+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - skip-pause
claimed_by: mold-glade
claimed_at: 2026-05-21T22:25:01.01400427+02:00
class: standard
---

Stage 07 Task 06. Enabled skip_renewal settings via arraysubs_settings: enabled=true, customer_can_skip=true, max_cycles=3. On active cust1 subscription #633, Manage Your Subscription appeared with Skip Next Renewal and Pause Subscription. Clicking Skip Next Renewal opened modal with number input min 1 max 3. Entering 2 and continuing showed confirmation dialog; clicking Skip resulted in alert "Failed to skip renewal. Please try again." WP-CLI shows no skip meta written (_skip_cycles_count=0, _skip_cycles_remaining=0) and next payment stayed 2026-06-20 12:30:46. Expected: success notice "Successfully skipped 2 renewal cycle(s).", Skipping 2 cycle(s) indicator, Undo Skip button, and next payment shifted forward two cycles.

[[2026-05-21]] Thu 22:11
Plan: verify #633 state/settings and reproduce Stage 07 Task 06 in browser; inspect skip REST + SkipManager failure path; if backend rejects, patch manager/controller; if UI closes/hides state before AJAX, move skip/undo AJAX into shared confirm onConfirm with modal spinner and rely on modal accessibility fix from #50; verify success text, skip indicator, next-payment shift, renewal actions, then Undo Skip cleanup.

[[2026-05-21]] Thu 22:25
Fixed: customer portal confirm/prompt helper now hides background from accessibility while modal is open and resolves only after fade/restore, preventing prompt->confirm race. Skip and Undo Skip now run AJAX inside confirmDialog onConfirm so the modal confirm button shows Processing and errors stay inline. Also hid Pause while a skip is active. Verified in browser on #633: skip 2 cycles -> Active, Next Payment 2026-08-20, Skipping 2 cycle(s), Undo Skip visible; renewal actions moved to Aug 20. Verified Undo Skip -> Next Payment 2026-06-20, Skip Next Renewal and Pause Subscription visible, skip indicator gone; renewal actions restored. Verified active-skip mutual exclusion with 1-cycle skip: notice 'Pause is unavailable while a renewal skip is active.' and no Pause Subscription button. Fixture cleaned back to original next payment; skip counters 0.
