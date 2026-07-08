---
id: 188
title: 'stage-21: 01 Product Setup & Admin UI (Segment Picker)'
status: closed
priority: high
created: 2026-07-08T02:50:18.504826+06:00
updated: 2026-07-07T23:41:33.037343891+02:00
started: 2026-07-07T23:19:57.492130907+02:00
completed: 2026-07-07T23:41:33.037342779+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 35m
claimed_by: reef-maple
claimed_at: 2026-07-07T23:41:33.037343791+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/01-product-setup-and-admin-ui.md

Gate task for Stage 21. Create FRS Monthly 30 / FRS Weekly 20 / FRS Variable test products and verify the product-editor UI: section placement right after Different Renewal Price, dual-thumb picker + day bubbles + colored bar, segment toggles with partition rule (disabled segment removed, >=1 active enforced), live exclusivity hiding (Different Renewal Price / Trial / Lifetime), cycle rescaling (month<->year<->interval), meta persistence, and per-variation parity.

[[2026-07-07]] Tue 23:41
QA result: PASS after QA reset and rebuilt/licensed dist. Verified monthly product #8648 and weekly #8650 product-editor flexible renewal sync UI placement, dual-thumb picker, keyboard nudging, clamping, segment toggles, >=1 active guard, Different Renewal Price/trial/lifetime exclusivity, month/year/interval rescaling, and meta persistence. Verified variable product #8652 / Silver variation #8654 per-variation persistence at 5/20 boundaries; Gold #8656 remains flex-disabled. Browser errors: none. debug.log line count unchanged at 1696. Screenshot: qa/artifacts/stage-21-task-188-flex-ui-enabled.png
