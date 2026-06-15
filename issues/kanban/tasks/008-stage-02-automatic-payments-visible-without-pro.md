---
id: 8
title: 'stage-02: Automatic Payments visible without Pro'
status: closed
priority: high
created: 2026-05-20T01:08:39.50186001+02:00
updated: 2026-05-22T00:21:19.37678793+02:00
started: 2026-05-22T00:13:51.626665826+02:00
completed: 2026-05-22T00:21:19.376786989+02:00
tags:
    - qa
    - stage-02
    - settings
    - pro
claimed_by: mold-glade
claimed_at: 2026-05-22T00:21:19.37678782+02:00
class: standard
---

Observed on live admin with agent-browser, 2026-05-20. Deactivated ArraySubsPro via WP-CLI, reloaded ArraySubs > Settings > General from fresh wp-admin navigation. Expected Stage 02 task 01.8: Automatic Payments section disappears when Pro inactive. Actual: Automatic Payments section remains visible; Feature Manager tab disappeared, confirming Pro inactive state changed.

[[2026-05-22]] Fri 00:15
Verified against Stage 02 Task 01.8: Automatic Payments is Pro-only and must disappear when ArraySubsPro is inactive while preserving saved value for reactivation. Code inspection confirms GeneralSettings renders the Automatic Payments card unconditionally. Plan: gate that card from the React form using liveBootClasses AutomaticPayments module visibility, leave REST/default storage intact so saved automatic_payments settings are preserved, rebuild, then browser-test Pro active visible, Pro inactive hidden, Pro reactivated visible with value preserved.

[[2026-05-22]] Fri 00:21
Fixed: GeneralSettings now filters form cards by loaded module and hides Automatic Payments unless the AutomaticPayments Pro provider is in liveBootClasses. FormBuilder now strips requiresModule metadata before rendering DOM. Build passed. Browser QA: with Pro active, Automatic Payments visible; set auto-renew on and confirmed option true; deactivated Pro and reloaded, Automatic Payments hidden; reactivated Pro and reloaded, section returned with saved value preserved; restored auto-renew off. ArraySubsPro active again. debug.log size 0.
