---
id: 71
title: 'stage-12: Store Credit settings toggles do not persist from browser clicks'
status: closed
priority: high
created: 2026-05-23T09:08:03.348345713+02:00
updated: 2026-05-24T09:14:33.995386816+02:00
started: 2026-05-24T09:08:44.761905877+02:00
completed: 2026-05-24T09:14:33.995385523+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:14:33.995386735+02:00
class: standard
---

Stage 12 Task 01 Subtasks 1.2 and 1.4. Store Credit Settings page loads and saves numeric fields, but browser clicks on secondary switches did not persist. Allow at Checkout stayed false after click + Save Settings; Enable Credit Purchases stayed false after click + Save Settings. WP-CLI confirmed arraysubs_settings.store_credit.apply_at_checkout=false and enable_purchase=false. Enable Store Credit System and Auto-Apply to Renewals persisted; numeric fields persisted. Similar UI-toggle behavior was seen earlier in Feature Manager issue #69.

[[2026-05-24]] Sun 09:14
Fix verified 2026-05-24 by shell-quartz. Shared fixes from #69 apply: bottom fixed action bar no longer intercepts lower controls; FormBuilder switches are now visible button role=switch controls. Alumnium browser on #/store-credit/settings: Allow at Checkout and Enable Credit Purchases toggled off via browser clicks and Save Settings persisted apply_at_checkout=false, enable_purchase=false in arraysubs_settings. Then toggled both back on via browser clicks, Save Settings success appeared, and WP option verified final Stage 12 values: enabled=true, auto_apply_to_renewals=true, apply_at_checkout=true, min_order_amount=5, expiration_days=365, enable_purchase=true, min/max/default purchase=10/500/50.
