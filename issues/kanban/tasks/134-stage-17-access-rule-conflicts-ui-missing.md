---
id: 134
title: 'stage-17: Access-Rule Conflicts UI missing'
status: closed
priority: critical
created: 2026-05-23T16:09:31.030804589+02:00
updated: 2026-05-24T13:11:53.218611935+02:00
started: 2026-05-24T12:57:26.709777888+02:00
tags:
    - qa
    - stage-17
    - access-rules
    - troubleshooting
class: standard
---

Task: stages/17-audits-and-logs/07-access-rule-conflicts.md\n\nSub-Task 07.4 expects an Access-Rule Conflicts UI under Audits beta or Member Access -> Conflicts.\n\nObserved:\n- Direct route /#/audits/access-rule-conflicts leaves the main content blank.\n- Direct route /#/members-access/conflicts also leaves the main content blank.\n- Code search found no Access-Rule Conflicts page/component/REST route.\n- Because the target UI is absent, Sub-Tasks 07.4 and 07.7 cannot be tested, and creating conflict rules would leave state without the expected resolution UI.\n\nExpected: conflict-detection/resolution UI should be implemented and reachable, or the QA plan should point to the correct equivalent screen.

[[2026-05-24]] Sun 13:11
Fixed and verified. Added core Member Access conflict detection for URL rules overlapping per-post overrides, REST endpoints at /members-access/conflicts and disable-url-rule, menu/routes for /audits/access-rule-conflicts and /members-access/conflicts, and an admin UI showing priority order, both rule definitions, specificity, winner, and Disable URL Rule resolution. Verification: php -l passed; npm run build passed; QA fixture created /premium/article-1 with per-post Pro Plan override and URL rule Block /premium prefix; REST detected conflict id 75ef11264afbb50506075fbb083a5aab with winner Per-post restriction wins; Alumnium verified UI; Playwright screenshots saved in qa/artifacts/issue-134-access-rule-conflicts/. Disable URL Rule was tested, conflicts returned total=0, URL rule disabled, and audit note #2815 recorded the resolution. Cleanup complete: temporary URL rule removed and article #2811 per-post override meta deleted.
