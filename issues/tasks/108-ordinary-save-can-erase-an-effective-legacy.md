---
id: 108
title: Ordinary save can erase an effective legacy fallback restriction
status: closed
priority: critical
created: 2026-10-24T04:04:25.680107+06:00
updated: 2026-10-24T04:08:45.107227+06:00
started: 2026-10-24T04:08:45.107225+06:00
completed: 2026-10-24T04:08:45.107225+06:00
tags:
    - bug
    - security
    - restrictions
    - compatibility
class: standard
---

Active QA task: final access audit task #1; scheduled 2026-10-24 (Asia/Dhaka).
Plan: qa/restriction-final-access-audit-2026-10-24/plan.md.
Affected subscription/order IDs: N/A.
Affected post: isolated disposable page used for proof and deleted; root browser fixture20190 reserved for verification.
Affected users: guest0; member224 login demo260927_220 roles customer/demo_member/demo_premium; administrator1 login admin roles administrator/demo_member; root disposable customer455.
Exact context: actual WordPress http://localhost:10013, per-post configurator reads and ordinary saves, classic callback and POST /wp/v2/pages/<fixture> REST route.
Reproduction: store _arraysubs_restrict_config as disabled original restrict configuration; separately store _arraysubs_restrict_enabled=1 and _arraysubs_restrict_conditions JSON with a nonmatching user_role condition. This represents an older integration writing direct-meta restrictions after a disabled configurator record exists. Read PostRestrictionConfig::get, then save that unchanged returned configuration.
Expected: the editor reflects the effective direct-meta restriction already enforced by ContentGating, preserving the current audience on an ordinary save; active canonical configurations retain priority.
Actual confirmed before fix: {before_allowed:false,loaded_enabled:false,after_unchanged_save_allowed:true} for a guest. The inactive canonical record hides the effective fallback tree in the editor, and unchanged save deletes the legacy keys and opens the content. This is a pre-existing two-store inconsistency and a confirmed ordinary-save access leak.
Scope/counterexamples: existing direct-meta data without a canonical record was already adopted correctly; an active canonical record must still win; variation inheritance from an active parent must retain its current priority; valid empty fallback trees retain local public-override behavior.
Fix plan: get() returns active canonical configuration first, otherwise adopts a genuinely effective valid legacy fallback tree without any read writes. Preserve fallback custom message and absence of admin bypass; keep active variation-parent inheritance ahead of a dormant child fallback. Verify actual read/classic/cached/REST saves and root browser audience before closure.

Fix now exposes effective fallback when canonical is inactive; no recursive parent fallback promotion. PostRestrictionConfig::getCanonical and Gate preserve original active-parent-canonical > child-direct-meta > global precedence. Exact unchanged cached original payload is protected; explicit current UI Off remains available. 401/401 actual-WP checks pass with Pro and 401/401 core-only, including classic/REST saves, empty/invalid/disabled fallback, active canonical priority and conflicting child/parent legacy trees. Root browser fixture20190 seeded with disabled canonical plus denying direct-meta tree; all actors expected denied before and after unchanged Apply/save. Waiting for root browser proof before closure.

Root actual-browser verification passed all four actors before and after opening the configurator, unchanged Apply, and Save. The dialog correctly shows the effective legacy fallback as enabled; a WP read after opening confirmed the original raw canonical enabled=false had not been written. After unchanged save the canonical record is enabled=true with legacy.surface=meta and the exact denying role tree retained. Proof: qa/restriction-final-access-audit-2026-10-24/evidence/author_post-fallback-before-audience.json, author_post-fallback-saved-audience.json, perpost-fallback-saved-meta.json and perpost-effective-fallback-dialog.png. Backend matrix is now401/401 with Pro and401/401 core-only, including original variation precedence and exact cached payload/deliberate-Off cases. Closed after browser verification.
