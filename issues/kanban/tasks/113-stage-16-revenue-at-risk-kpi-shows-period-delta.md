---
id: 113
title: 'stage-16: Revenue at Risk KPI shows period delta'
status: closed
priority: medium
created: 2026-05-23T13:08:20.697452396+02:00
updated: 2026-05-24T17:49:54.252598603+02:00
started: 2026-05-24T17:46:46.890450159+02:00
completed: 2026-05-24T17:49:54.252597551+02:00
tags:
    - qa
    - stage-16
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:49:54.252598513+02:00
class: standard
---

Original task: stage-16 task 02 Subscription Performance Dashboard, Sub-Task 2.7.\n\nObserved: Revenue at Risk card shows ,943.35 and '+100% vs previous period'.\n\nExpected: Revenue at Risk is a real-time snapshot metric and should show no delta, or 0%, never a period comparison.\n\nEvidence: Browser accessibility tree on WC Analytics Overview shows Revenue at Risk ,943.35 with '+100% vs previous period'. REST response sets prev_value=0, and frontend generic delta code renders +100%.

[[2026-05-24]] Sun 17:49
Fixed Revenue at Risk snapshot display: card definition now sets showDelta=false and StatCard omits the previous-period delta for snapshot metrics. Verified WooCommerce Analytics Overview: Revenue at Risk card shows value only (,974.9) with no '+100% vs previous period'. Screenshot qa/artifacts/issue-113/revenue-at-risk-no-delta.png. npm run build passed after correcting JSX.
