---
id: 73
title: 'stage-12: Store Credit master switch does not hide module/product type'
status: closed
priority: high
created: 2026-05-23T09:10:49.769126789+02:00
updated: 2026-05-24T09:28:03.314150414+02:00
started: 2026-05-24T09:19:20.895196275+02:00
completed: 2026-05-24T09:28:03.314149332+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:28:03.314150293+02:00
class: standard
---

Stage 12 Task 01 Subtask 1.6. With arraysubs_settings.store_credit.enabled=false, ArraySubs admin still shows Store Credit submenu links: Manage Credits, Credit History, Settings. Products > Add New Product data dropdown still includes Store Credit. Expected master switch off to hide/inactivate Manage Credits + Credit History and hide Store Credit product type. Code confirms product_type_selector always adds arraysubs_store_credit and menu visibility is module-based, not setting-based.

[[2026-05-24]] Sun 09:27
Fix verified: Store Credit master switch now hides Manage Credits and Credit History tabs after save/reload while keeping Settings reachable. With enabled=false, Products > Add New product type dropdown no longer lists Store Credit. Re-enabled store_credit.enabled=true and enable_purchase=true, verified Manage Credits/Credit History/Settings return and Store Credit product type appears again. Browser verified on ArraySubs Store Credit settings and WooCommerce Add New Product.
