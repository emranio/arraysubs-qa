---
id: 155
title: Offboarding queues oversized emoji feedback rejected by portal
status: closed
priority: low
created: 2026-11-05T00:46:19.583915+06:00
updated: 2026-11-05T00:50:19.59416+06:00
started: 2026-11-05T00:46:25.234856+06:00
completed: 2026-11-05T00:50:19.59416+06:00
claimed_by: oscine-urman
claimed_at: 2026-11-05T00:50:19.59416+06:00
class: standard
---

QA task #3; scheduled 2026-11-05 Asia/Dhaka; plan qa/offboarding-2026-11-05/plan.md. Subscription/order IDs: N/A. User: local WordPress administrator admin (ID 1). Route: http://localhost:10013/wp-admin/admin-ajax.php?action=arraysubs_offboarding_help. Browser context: authenticated admin; issue is a handcrafted oversized POST, not ordinary textarea input. Reproduction: submit more than 2000 emoji in other_reason with other selected and a valid nonce. PHP mb_substr permits 4000 Unicode code points; portal bounds 4000 UTF-16 units, so 3000 emoji becomes 6000 units and is rejected. Expected: normalize to valid contract before outbox; actual: receipt can remain queued with a permanent 400 response. Concrete evidence: source PluginOffboarding::payload uses mb_substr(0,4000), portal contract boundedText uses JS string.length; contract regression tests verify emoji bound at 2000. Normal UI counterexample: textarea maxLength=4000 prevents excess and ordinary Bengali feedback was browser submitted with HTTP201. Fix plan: align PHP to UTF-16 units without requiring mbstring, regression-test Unicode boundaries and verify browser flow after change.

Fixed by UTF-8 bounded traversal counting supplementary characters as two UTF-16 units, without mbstring dependency. Before-fix regression reproduced 6000 units from 3000 emoji; 8/8 regression checks now pass normally and with conversion functions disabled. Real authenticated browser fetch through WordPress AJAX then real local portal stored 3999 UTF-16 units with HTTP201; same event retry returned HTTP200 duplicate:true. Event 7b142d61-ceed-46d1-80f5-408c71971f61. UI ordinary Bengali feedback also saved and shown in actual portal admin detail. Supporting logs copied into run evidence before completion.
