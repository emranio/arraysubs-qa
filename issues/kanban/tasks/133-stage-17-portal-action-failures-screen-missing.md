---
id: 133
title: 'stage-17: Portal Action Failures screen missing'
status: closed
priority: critical
created: 2026-05-23T16:07:59.169984815+02:00
updated: 2026-05-24T12:57:06.942842559+02:00
started: 2026-05-24T12:39:58.80283144+02:00
tags:
    - qa
    - stage-17
    - portal-failures
    - troubleshooting
class: standard
---

Task: stages/17-audits-and-logs/06-portal-action-failures.md\n\nSub-Task 06.4 expects an ArraySubs -> Audits beta -> Portal Action Failures screen with failed portal action rows and View / Resolve actions.\n\nObserved:\n- Browser navigation under Audits/Gateway area shows only Activity Audits, Gateway Logs, and Scheduled-Job Logs.\n- Direct route /#/audits/portal-action-failures leaves the main content blank.\n- Code search found no Portal Action Failures page/component/REST route.\n- Because the target screen is absent, Sub-Tasks 06.4-06.7 cannot be tested.\n\nExpected: Portal Action Failures troubleshooting screen should be implemented and reachable, or the QA plan should point to the correct equivalent screen.

[[2026-05-24]] Sun 12:57
Fixed and verified. Added portal action failure logging for failed customer portal REST POST actions, admin REST endpoint, Audits menu route, Portal Action Failures React screen, View details modal, and Resolve action with shared confirm/loading helpers. Verification: php -l passed on new/modified PHP files; npm run build passed in arraysubs; forced member1@example.com cancel on subscription #1668 while customer cancellation disabled returned HTTP 403 code cancellation_disabled and left subscription active; REST GET /arraysubs/v1/audits/portal-action-failures showed failure #2805; agent-browser verified row/action details; agent-browser screenshots saved in qa/artifacts/issue-133-portal-action-failures/. Resolve was tested, REST list returned total=0, failure #2805 status=resolved, and audit note #2807 has _audit_entity=subscription and _event_type=portal_action_failure_resolved. Setting restored: customer_actions.allow_cancellation=on.
