---
id: 132
title: 'stage-17: Renewal Failures troubleshooting screen missing'
status: closed
priority: critical
created: 2026-05-23T16:06:45.407350157+02:00
updated: 2026-05-24T12:38:38.029662648+02:00
started: 2026-05-24T12:17:19.960138529+02:00
tags:
    - qa
    - stage-17
    - renewal-failures
    - troubleshooting
class: standard
---

Task: stages/17-audits-and-logs/05-renewal-failures-troubleshooting.md\n\nSub-Task 05.4 expects an ArraySubs -> Audits beta -> Renewal Failures troubleshooting screen with failed renewal rows and Retry / Mark as resolved actions.\n\nObserved:\n- Browser navigation under Audits/Gateway area shows only Activity Audits, Gateway Logs, and Scheduled-Job Logs.\n- Direct route /#/audits/renewal-failures leaves the main content blank.\n- Code search found routes only for /audits/activity-audits and /audits/scheduled-job-logs, plus /settings/gateways. No Renewal Failures page/component/REST route appears to exist.\n- Because the target screen is absent, Sub-Tasks 05.4-05.7 cannot be tested.\n\nExpected: Renewal Failures troubleshooting screen should be implemented and reachable, or the QA plan should point to the correct equivalent screen.

[[2026-05-24]] Sun 12:38
Fixed and verified. Implemented Renewal Failures admin route, pro REST endpoints, unresolved failure listing, Retry and Mark Resolved controls with shared confirm/loading helpers, and resolved/fresh-failure meta cleanup. Verification: php -l passed on modified PHP files; npm run build passed in arraysubs; REST GET /arraysubs/v1/audits/renewal-failures returned rows; Alumnium verified rows #2727/#1467 and #2727 Retry/Mark Resolved actions; Playwright screenshots saved in qa/artifacts/issue-132-renewal-failures/. Mark Resolved was tested on QA fixture subscription #2727, row disappeared, _renewal_failure_resolved=yes, and audit note #2800 has _audit_entity=subscription and _event_type=renewal_failure_resolved.
