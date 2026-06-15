---
id: 67
title: 'stage-11: Feature Manager invalid-row cancel leaves blank feature'
status: closed
priority: medium
created: 2026-05-23T08:12:09.427247877+02:00
updated: 2026-05-24T08:30:51.959338444+02:00
started: 2026-05-24T08:21:52.8254984+02:00
completed: 2026-05-24T08:30:51.959337252+02:00
tags:
    - qa
    - stage-11
    - feature-manager
    - product-admin
claimed_by: shell-quartz
claimed_at: 2026-05-24T08:30:51.959338354+02:00
class: standard
---

Stage 11 Task 01 Sub-Task 1.3. In Pro Plan Feature Manager modal, clicked Add Feature, left Feature Name blank, set Type Number and Value 1, clicked row Save. Inline validation appeared: "Feature title is required." Expected. Then clicked row Cancel. Actual: blank row stayed in read mode with Type TEXT, enabled checkmark, no feature name/value; row count did not restore. Expected: cancelling invalid new row removes/restores it so row count returns to existing features. Browser: agent-browser admin on /wp-admin/post.php?post=233&action=edit.

[[2026-05-24]] Sun 08:30
Fix verified 2026-05-24 by shell-quartz. Patched arraysubspro FeatureEditor new-row cancel path so invalid unsaved rows are removed and row order reindexed. Rebuilt assets with npm run build. agent-browser: /wp-admin/post.php?post=233&action=edit, Feature Manager modal, Add Feature, blank name + Number + value 1, row Save showed Feature title is required, row Cancel restored 7 rows (Seats, API Calls, Storage (GB), Priority Support, Custom Domain, Plan Tier, Hidden Beta Flag) with no blank row.
