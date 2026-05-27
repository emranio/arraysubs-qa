---
id: 122
title: 'stage-17: Customer cancellation modal fails for non-admin customer subscriptions'
status: closed
priority: high
created: 2026-05-23T14:21:46.100499754+02:00
updated: 2026-05-24T19:40:35.657778421+02:00
started: 2026-05-24T19:11:00.298558631+02:00
completed: 2026-05-24T19:40:35.65777754+02:00
tags:
    - qa
    - stage-17
    - customer-portal
    - cancellation
    - audits
claimed_by: shell-quartz
claimed_at: 2026-05-24T19:40:35.657778321+02:00
class: standard
---

Source task: qa/stages/17-audits-and-logs/01-activity-audits.md Sub-Task 01.6.\n\nBrowser: Alumnium Chrome, customer session member1@example.com, subscription #697 at /?page_id=9&view-subscription=697.\n\nSteps:\n1. Open active subscription #697 as member1@example.com.\n2. Click Cancel Subscription.\n3. Select reason Just need a temporary break.\n4. Click Continue.\n\nObserved: page remains on cancellation modal and shows visible error: Failed to cancel subscription. Please try again. Subscription remains Active. No customer cancellation audit row can be produced from UI.\n\nExpected: cancellation succeeds or schedules end-of-period cancellation, success message appears, and Activity Audits shows Customer + Subscription row with changes link.\n\nRelated closed regressions: #47, #48.

[[2026-05-23]] Sat 22:03
Stage 20.06 final smoke confirmed again on Stripe subscription #2591 for smoke@example.com. Browser modal path: Cancel Subscription -> reason Just need a temporary break -> Continue twice => toast 'Failed to cancel subscription. Please try again' and modal stayed open. Same logged-in browser direct REST POST to /arraysubs/v1/my-subscriptions/2591/cancel with X-WP-Nonce then succeeded and scheduled EOP cancellation, proving endpoint/capability can work while modal flow reports failure. After reload page shows Pending Cancellation, cancels 24 June 2026. Smoke gate still FAIL for customer UI cancellation step because normal modal path failed.

[[2026-05-24]] Sun 08:14
User retest on 2026-05-24 confirms cancellation works for subscription #2651 owned by admin user (customer_id=1): note #2659 scheduled cancellation with reason Too expensive, then note #2661 undo. Retest on smoke subscription #2591 owned by smoke@example.com/customer_id=308 still fails in browser modal for both Just need a temporary break and Too expensive: modal remains open and alert says 'Failed to cancel subscription. Please try again.' Direct REST previously succeeded for #2591. Narrowed issue: not global cancellation; failure appears tied to normal customer/modal path or some subscription/customer context, while admin-owned customer sub works.

[[2026-05-24]] Sun 08:17
Required QA issue fields added. QA progress task/stage: #148, Stage 17 Task 01 Activity Audits, Sub-task 01.6. QA plan markdown: qa/stages/17-audits-and-logs/01-activity-audits.md. Affected failing subscriptions: #697 and #2591. #697 details: product Pro Plan #233, customer/user ID 25, login member1, email member1@example.com, roles customer + pro_member, payment_method empty, next_payment 2026-06-21 01:55:59, related order IDs not available from helper output. #2591 details: product Smoke Plan Plus #2571, customer/user ID 308, login smoke.tester, email smoke@example.com, role customer, payment_method stripe, next_payment 2026-06-23 19:54:36, related order IDs #2573 initial and #2598 renewal (later #2605 plan-switch order exists but was after first cancellation repro). Failing URLs: https://mirror-help.arrayhash.com/?page_id=9&view-subscription=697 and https://mirror-help.arrayhash.com/?page_id=9&view-subscription=2591. Browser/user context: logged-in customer sessions, member1@example.com and smoke@example.com. Steps: open subscription detail, click Cancel Subscription, select reason, click Continue. Failed reasons tested: Just need a temporary break (#697/#2591) and Too expensive (#2591 retest). Actual: modal stays open and alert/toast says 'Failed to cancel subscription. Please try again.' Expected: EOP cancellation scheduled, success feedback shown, and audit row generated. Counterexample: user verified #2651 works. #2651 details: customer/user ID 1, login admin, email admin@mirror-help.arrayhash.com, role administrator, product Basic Monthly #197, payment_method stripe, next_payment 2026-06-24 06:08:09; notes #2659 scheduled cancellation with reason Too expensive and #2661 undo. Scope note: issue appears normal-customer/modal-context specific, not global cancellation.

[[2026-05-24]] Sun 19:22
Plan: verify customer modal path with member1/#697, patch scheduled-cancel audit details so customer cancellation rows have structured changes, then retest UI cancellation and Activity Audits modal; restore #697 afterward.

[[2026-05-24]] Sun 19:40
Fix applied and verified.

Implementation: scheduled-cancellation audit notes now preserve the real actor for customer/admin initiated cancellations and include structured audit changes for _waiting_cancellation and _cancellation_scheduled_date. System/gateway cases still use system attribution.

Verification: customer browser flow as member1 on subscription #697 returned HTTP 200, showed Pending Cancellation, and did not show the previous Failed to cancel subscription error. WP-CLI REST check confirmed latest note #2975 is author=customer/member1, added_by=25, has_changes=true, and appears in author_role=customer + entity=subscription results.

Browser proof: admin Activity Audits page shows member1 / SUBSCRIPTION / Subscription #697 with changes ->. Clicking changes -> shows _waiting_cancellation false -> true and _cancellation_scheduled_date populated. Screenshots: qa/artifacts/issue-122/customer-cancel-success-final.png and qa/artifacts/issue-122/activity-audit-customer-cancel-changes-modal.png.

Cleanup: restored subscription #697 to arraysubs-active and cleared cancellation metadata after verification; member1 roles confirmed customer + pro_member.
