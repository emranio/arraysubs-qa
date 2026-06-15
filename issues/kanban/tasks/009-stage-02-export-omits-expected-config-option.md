---
id: 9
title: 'stage-02: Export omits expected config option groups'
status: closed
priority: medium
created: 2026-05-20T01:08:39.519039871+02:00
updated: 2026-05-22T04:13:57.612610824+02:00
started: 2026-05-22T04:09:11.4647266+02:00
completed: 2026-05-22T04:13:57.612609963+02:00
tags:
    - qa
    - stage-02
    - import-export
claimed_by: mold-glade
claimed_at: 2026-05-22T04:13:57.612610744+02:00
class: standard
---

Observed via Easy Setup export endpoint, 2026-05-20. Stage 02 task 03.1 expects options to include arraysubs_settings, arraysubs_profile_fields_config, arraysubs_avatar_settings, arraysubs_myaccount_menu_config, and wc_email_settings. Actual baseline-pro-active.json contained only arraysubs_settings because other options were absent/null. Need export to include empty defaults or QA expectation should allow absent empty groups.

[[2026-05-22]] Fri 04:13
Plan: keep export schema stable by always emitting the expected option groups with current/default values, then verify via REST and browser-authenticated fetch. Fixed SetupController export to include arraysubs_settings, arraysubs_profile_fields_config, arraysubs_avatar_settings, arraysubs_myaccount_menu_config, and wc_email_settings even when standalone options/email settings are absent. Verified WP-CLI REST export keys and agent-browser Easy Setup browser fetch output include all five groups.
