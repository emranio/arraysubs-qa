---
id: 104
title: Legacy per-post boolean normalization changes the allowed audience
status: closed
priority: critical
created: 2026-10-24T03:56:28.10693+06:00
updated: 2026-10-24T03:59:36.78825+06:00
started: 2026-10-24T03:59:36.78825+06:00
completed: 2026-10-24T03:59:36.78825+06:00
tags:
    - bug
    - security
    - restrictions
    - compatibility
class: standard
---

Active QA task: final access audit task #1; scheduled 2026-10-24 (Asia/Dhaka).
Plan: qa/restriction-final-access-audit-2026-10-24/plan.md.
Affected subscription IDs/order IDs: N/A (configuration-only; no billing changes).
Affected posts: disposable published page created and deleted by runtime-backend.php; no permanent content changed.
Affected users: guest 0; member 224, login demo260927_220, roles customer/demo_member/demo_premium; administrator 1, login admin, roles administrator/demo_member.
Exact route/context: http://localhost:10013; WP-CLI actual WordPress runtime, PostRestrictionConfig::get/save, classic saveClassicMetaBox callback and POST /wp/v2/pages/<disposable ID> via WP_REST_Request. Runtime proof qa/restriction-final-access-audit-2026-10-24/evidence/runtime-backend-before.txt.
Reproduction: store old _arraysubs_restrict_config JSON with enabled:"false", mode:"restrict", roles:["qa_final_missing_role"], showToAdmins:false. Compare trusted git HEAD classes against current classes. Also test loginRequired:"false" alone, and showToAdmins:null with a nonmatching role. Read, submit canonical classic-editor JSON, submit cached original JSON, and save through the authenticated posts REST route.
Expected: the original nonempty-string PHP boolean semantics and explicit null values retain the old audience throughout opening and ordinary saves.
Actual: enabled:"false" changes from an active denying gate to an inactive public gate for all users; loginRequired:"false" changes from login-only to public for guests; showToAdmins:null becomes true and allows a previously denied administrator. 24 differential failures, including one tightening case for a truthy string admin bypass, across all read/save stages.
Scope/counterexamples: ordinary boolean true/false settings, regular ANY/ALL rules, visibility and nested direct-meta trees matched the trusted HEAD audience. Imported/cached old scalar encodings expose this compatibility gap; no browser editor was required to change the audience.
Fix plan: normalize old per-post flags using the original !empty contract before canonical defaults, preserve that meaning in the version-1 descriptor, and keep version-2 boolean handling separate. Re-run HEAD differential, classic and REST saves, plus previous compatibility matrix.

Backend fix verified against trusted HEAD classes: all original boolean/audience cases now match before and after classic, cached-client and REST saves. Evidence: qa/restriction-final-access-audit-2026-10-24/evidence/runtime-backend-pro.txt and runtime-backend-core.txt, each 293/293 passed. Root is performing real-browser guest/member/admin verification before closure.

Root real-browser verification completed on 2026-10-24 against actual localhost:10013: all 12 checks passed across guest/customer/member/admin for old enabled-string, login-string and explicit-null-admin configurations. Proof: qa/restriction-final-access-audit-2026-10-24/evidence/post-enabled-string-audience.json, post-login-string-audience.json, post-admin-null-audience.json plus screenshots/snapshots. Combined with 293/293 trusted-HEAD differential and actual classic/REST-save runtime checks in both Pro/core. Closed after browser verification.
