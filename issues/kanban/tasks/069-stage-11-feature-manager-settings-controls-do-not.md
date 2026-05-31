---
id: 69
title: 'stage-11: Feature Manager settings controls do not toggle from browser clicks'
status: closed
priority: high
created: 2026-05-23T08:41:48.801188024+02:00
updated: 2026-05-24T09:04:20.50698557+02:00
started: 2026-05-24T08:39:53.607285198+02:00
completed: 2026-05-24T09:04:20.506978367+02:00
tags:
    - qa
    - stage-11
    - feature-manager
    - settings
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:04:20.50698544+02:00
class: standard
---

Stage 11 Task 02/03. In ArraySubs → Settings → Feature Manager, browser clicking secondary controls did not change their checked state: Show on Product Page stayed checked after repeated click+Save, and Feature Aggregation Mode Combine stayed unchecked after clicking the Combine radio+Save. WP option confirmed unchanged (, ). Enable Feature Manager and the My Account title field did save, so the settings endpoint works for some fields. Expected: clicking checkboxes/radios updates UI state and persists after Save Settings. Browser: Alumnium admin on /wp-admin/admin.php?page=arraysubs-mainadmin#/settings/feature-manager.

[[2026-05-24]] Sun 09:04
Fix verified 2026-05-24 by shell-quartz. Root cause: .arraysubs-bottom-fixed-actions fixed bar intercepted clicks over lower settings controls in the Alumnium viewport; elementFromPoint over Show on Product Page returned the Save Settings wrapper. Fix: action bar shell now uses pointer-events:none and direct children pointer-events:auto, so empty overlay no longer blocks form controls. Also updated shared FormBuilder Switch to visible button role=switch and safe switch/radio ids, mirrored in pro form-builder. Built arraysubs and arraysubspro assets. Browser verification: Show on Product Page switch toggled off from click, Combine radio selected from click, Save Settings showed success; WP option showed show_on_product_page=false and aggregation_mode=combine. Restored settings via browser to show_on_product_page=true and aggregation_mode=per_subscription; WP option verified restored.
