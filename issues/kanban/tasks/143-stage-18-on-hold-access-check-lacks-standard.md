---
id: 143
title: 'stage-18: on-hold access check lacks Standard Weekly rule'
status: closed
priority: medium
created: 2026-05-23T17:10:06.521962805+02:00
updated: 2026-05-24T21:32:48.107391572+02:00
started: 2026-05-24T21:24:36.76224477+02:00
completed: 2026-05-24T21:32:48.10739028+02:00
tags:
    - qa
    - stage-18
    - member-access
    - prerequisite
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:32:48.107391472+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/05-grace-to-on-hold-transition.md\n\nTask 18.05 expects member-decline@example.com on Standard Weekly to have previously unlocked member content so On-Hold restriction can be verified. Current members_access settings only include:\n- URL rule Premium Content URL gate for /premium-content requiring active subscription to product #233 Pro Plan.\n- Role mapping rule also targets product #233 Pro Plan.\n- No URL/CPT/download/ecommerce rule targets Standard Weekly product #200 or member-decline@example.com's subscription.\n\nImpact: accessing /premium-content as member-decline would be denied regardless of On-Hold because the user never qualifies for the Pro Plan rule. This does not prove On-Hold status caused restriction. Need a Standard Weekly-specific access fixture or task should use Pro Plan subscription for the access subcheck.

[[2026-05-24]] Sun 21:32
Fix/verification: added durable Standard Weekly access fixture for Stage 18. Created page #3060 at /standard-weekly-content/ with marker 'STANDARD WEEKLY PROTECTED CONTENT - QA FIXTURE OK'. Restored permalink structure to /%postname%/ per QA baseline and flushed rewrites so path-based URL rules hit real pages. Added members_access.url_rules entry s18_url_standard_weekly_content: prefix /standard-weekly-content, priority 5, condition has_active_subscription product #200 Standard Weekly, action message. Reset QA passwords for member-stripe user #42 and member-decline user #43 to QA-member-2026! for browser verification. agent-browser verified active member-stripe / subscription #1436 sees protected content; on-hold member-decline / subscription #1467 sees restriction message and protected marker absent. agent-browser screenshots: qa/artifacts/issue-143/active-standard-weekly-content-visible.png and qa/artifacts/issue-143/on-hold-standard-weekly-restricted.png.
