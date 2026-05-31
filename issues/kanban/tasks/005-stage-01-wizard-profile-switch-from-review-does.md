---
id: 5
title: 'Stage 01: Wizard profile switch from review does not update business type'
status: closed
priority: high
created: 2026-05-20T00:27:27.69245785+02:00
updated: 2026-05-21T23:23:32.374725542+02:00
started: 2026-05-21T23:12:41.341712769+02:00
completed: 2026-05-21T23:23:32.374724229+02:00
tags:
    - qa
    - bug
    - stage-01
claimed_by: mold-glade
claimed_at: 2026-05-21T23:23:32.374725441+02:00
class: standard
---

During stage-01 Task 02, from Review screen for Physical Subscription Box, clicked Edit on Your Business, selected Membership / Community and Multiple plans / tiers, then skipped to Review. Review still showed Physical Subscription Box defaults: business type Physical Subscription Box, billing flexibility skip+pause, multiple subscriptions allowed, Store Credit features. Expected Membership / Community defaults to replace prior profile. Browser: Chromium 148 headless via Alumnium.

[[2026-05-21]] Thu 23:13
Verified Stage 01 Task 02 and issue body. Code cause found in useWizardState: skipCurrentStep closes over activeProfile from the previous render. When business_type is changed from review and Skip with defaults is clicked quickly, React can process the skip updater with stale activeProfile from the old business profile and write old defaults back into Step 1 answers. Plan: patch skipCurrentStep to derive profile defaults inside the functional setAnswersState updater from the latest queued previous.business_type, and recalculate visible questions from that same latest answer set. Then build arraysubs assets and browser-test the exact review -> Edit Your Business -> Membership / Community -> Multiple plans / tiers -> skip to Review flow.

[[2026-05-21]] Thu 23:23
Fixed and verified. Code: skipCurrentStep now derives profile defaults from latest queued answers inside the setAnswersState functional updater and recalculates visible questions from that same answer set; this prevents stale old-profile defaults when switching profile from Review then quickly using Skip with defaults. Build: npm run build completed. Browser QA: launched Easy Setup, created Physical Subscription Box review, edited Your Business, selected Membership / Community and Multiple plans / tiers, skipped to Review; final Review showed Membership / Community, Multiple plans / tiers, pause only, one subscription per customer, access redirect/login, and Membership additional features including Feature Manager and Multi-Login Prevention. debug.log remained 0 bytes. Lint/phpcs skipped per instruction.
