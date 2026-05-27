---
id: 153
title: stage-18 fixed-period access check lacks protected-content rule
status: closed
priority: medium
created: 2026-05-23T18:46:02.234741531+02:00
updated: 2026-05-24T22:24:38.222503001+02:00
started: 2026-05-24T22:20:36.870904138+02:00
completed: 2026-05-24T22:24:38.222501898+02:00
tags:
    - qa
    - stage-18
    - fixed-period
    - member-access
    - prerequisite
claimed_by: shell-quartz
claimed_at: 2026-05-24T22:24:38.2225029+02:00
class: standard
---

Stage 18 Task 12 customer portal check expects premium content access tied to member-fixed@example.com / Fixed-Period Plan to become restricted after expiration. The documented member-fixed fixture was absent, so QA created subscription #1687/product #1683; no existing member-access rule or previously unlocked URL is configured for this product. Verified portal row shows Expired, but content-access restriction cannot be meaningfully proven without a protected URL/rule tied to the Fixed-Period Plan.

[[2026-05-24]] Sun 22:21
Plan: create a real protected URL fixture for the fixed-period product. Add/update WordPress page /fixed-period-content/ with marker text, add members_access.url_rules entry requiring an active subscription to product #1683, reset member-fixed password for browser verification, then confirm expired member-fixed cannot see marker content and receives the restriction message.

[[2026-05-24]] Sun 22:24
Fixed and verified live. Created/updated page #3103 /fixed-period-content/ with marker 'FIXED PERIOD PROTECTED CONTENT - QA FIXTURE OK'. Added members_access.url_rules entry s18_url_fixed_period_content: prefix /fixed-period-content, requires active subscription product #1683, message action 'This Fixed Period content is restricted. Please subscribe to access.' Reset member-fixed@example.com user #48 password to QA-member-2026!. Browser verification as member-fixed with expired #1687: restriction message visible, marker content not visible. Screenshot: qa/artifacts/issue-153/fixed-period-content-expired-member-restricted.png.
