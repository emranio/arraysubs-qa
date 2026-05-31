---
id: 72
title: 'stage-12: Store Credit settings missing required guidance text'
status: closed
priority: medium
created: 2026-05-23T09:10:49.707427372+02:00
updated: 2026-05-24T09:19:06.583442349+02:00
started: 2026-05-24T09:14:44.440145802+02:00
completed: 2026-05-24T09:19:06.583441538+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:19:06.583442259+02:00
class: standard
---

Stage 12 Task 01 Subtasks 1.3 and 1.4. Store Credit Settings page persists fields, but expected guidance text is missing. Expiration help only says credits expire after N days / 0 means never; it does not mention 7-day-before warning behavior or 3:00 AM daily job timing. Credit Purchase section only says customers can purchase store credits via WooCommerce orders; no note/hint/link about needing to create a Store Credit product is displayed.

[[2026-05-24]] Sun 09:18
Fix verified: Store Credit Settings now shows expiration guidance with 7-day expiring-soon warning and daily 3:00 AM expiration job timing. Credit Purchase guidance now tells admin to create a Store Credit product before customers buy credits. Browser verified on /wp-admin/admin.php?page=arraysubs-mainadmin#/store-credit/settings.
