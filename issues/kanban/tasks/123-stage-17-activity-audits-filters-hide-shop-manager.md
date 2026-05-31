---
id: 123
title: 'stage-17: Activity Audits filters hide shop-manager product update'
status: closed
priority: high
created: 2026-05-23T14:29:49.369371303+02:00
updated: 2026-05-24T19:43:50.428994078+02:00
started: 2026-05-24T19:41:09.394787732+02:00
completed: 2026-05-24T19:43:50.428993066+02:00
tags:
    - qa
    - stage-17
    - audits
    - filters
    - product
    - shop-manager
claimed_by: shell-quartz
claimed_at: 2026-05-24T19:43:50.428993978+02:00
class: standard
---

Source task: qa/stages/17-audits-and-logs/01-activity-audits.md Sub-Task 01.7.\n\nFixture: shop_manager_qa edited product #197 Basic Monthly regular price 29.99 -> 30.99 in wp-admin. WooCommerce save succeeded. DB note #1339 created with post_author 40, _added_by=40, _audit_entity=product, _audit_changes regular price 9.99 -> 0.99.\n\nObserved in browser:\n- Search #197 shows the row: May 23, 2026 6:24 PM, author Shop Manager QA, Type PRODUCT, Product #197, changes ->.\n- Author=Admin + Entity=Product shows No audit logs found for selected filters.\n- Entity=Product with All Authors omits note #1339 until searching #197.\n\nExpected: Author=Admin filter should include shop_manager_qa per QA plan, and Entity=Product filter should include the Product #197 updated row.\n\nCode clue: arraysubspro AuditController filters entity via post_content LIKE patterns and ignores _audit_entity; pattern list includes Product updated but not Product #... updated. Admin role filter uses getAdminUserIds(), likely excluding shop_manager users with manage_woocommerce.

[[2026-05-24]] Sun 19:43
Plan: verify the existing audit role/entity filter implementation against the recorded product note, patch AuditController only if Admin or Product filters still miss note #1339/#197, then prove the row and changes modal in browser.

Verification result: no code change needed. Current AuditController includes shop_manager users in admin-role filtering and entity filtering prefers stored _audit_entity. WP-CLI REST author_role=admin + entity=product returned total=31 and included product update notes #1343, #1340, #1339 for Product #197 by Shop Manager QA, all role=admin/entity=product/has_changes=true.

Browser proof: Playwright selected Author=Admin and Entity=Product on Activity Audits. The table showed Shop Manager QA / PRODUCT / Product #197 with changes ->. Clicking changes -> opened the modal with Previous Value / Changed Value and regular price $29.99 -> $30.99. Screenshots: qa/artifacts/issue-123/admin-product-filter-row.png and qa/artifacts/issue-123/admin-product-filter-changes-modal.png.
