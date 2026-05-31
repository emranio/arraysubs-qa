---
id: 82
title: 'stage-12: Checkout credit negative check blocked by plan switching'
status: closed
priority: medium
created: 2026-05-23T09:53:31.028485101+02:00
updated: 2026-05-24T10:38:24.597451052+02:00
started: 2026-05-24T10:32:59.507423355+02:00
completed: 2026-05-24T10:38:24.59744999+02:00
tags:
    - qa
    - stage-12
    - store-credit
    - checkout
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:38:24.597450962+02:00
class: standard
---

Stage 12 Task 05 Subtask 5.7. After 5.2 created active Standard Weekly subscription #1135 for cust3, attempting to add Standard Weekly again produced duplicate/switch warning. Trying Basic Monthly also produced not an allowed upgrade/downgrade/crossgrade warning. This blocks the planned Allow at Checkout off negative check using the same customer unless an allowed switch target or a fresh customer/product fixture is provided. apply_at_checkout was restored after check.

[[2026-05-24]] Sun 10:33
Plan: treat as QA-plan fixture defect. Verify duplicate/plan-switch block is expected after the same customer creates an active Standard Weekly subscription. Update 12-store-credit/05-purchase-with-credit.md subtask 5.7 to use a fresh credit customer with no active Standard Weekly subscription for the Allow at Checkout off negative check. Then browser-verify with a fresh customer and restore apply_at_checkout=true.

[[2026-05-24]] Sun 10:38
Fixed/verified. Updated qa/stages/12-store-credit/05-purchase-with-credit.md so subtask 5.7 uses a fresh credit customer with no active Standard Weekly subscription; same-customer duplicate/plan-switch block is expected after 5.2. Browser verified with qa-credit-off (#311) while apply_at_checkout=false: Standard Weekly cart subtotal 9.99, no Store Credit Applied line, estimated total 9.99, checkout CTA present. Removed cart item and restored apply_at_checkout=true.
