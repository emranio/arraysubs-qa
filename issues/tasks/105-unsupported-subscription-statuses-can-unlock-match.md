---
id: 105
title: Unsupported subscription statuses can unlock Match Any access
status: closed
priority: critical
created: 2026-10-24T03:57:34.912067+06:00
updated: 2026-10-24T03:59:36.820529+06:00
started: 2026-10-24T03:59:36.820528+06:00
completed: 2026-10-24T03:59:36.820528+06:00
tags:
    - bug
    - security
    - restrictions
class: standard
---

Active QA task: final access audit task #1; scheduled 2026-10-24 (Asia/Dhaka).
Plan: qa/restriction-final-access-audit-2026-10-24/plan.md.
Affected subscriptions: member224 subscription8326; administrator1 subscriptions6567,6537,6543,6549,6554. Affected orders: N/A.
Affected users: member224 login demo260927_220 roles customer/demo_member/demo_premium; administrator1 login admin roles administrator/demo_member; guest0 used as a counterexample.
Exact test context: actual WordPress at http://localhost:10013, WP-CLI eval-file qa/restriction-final-access-audit-2026-10-24/runtime-backend.php; ConditionEvaluator::evaluate with a subscription_status clause and nonmatching user_role clause under logic=or.
Reproduction: use statuses:["qa-final-unknown-status"] or ["any"], or case-invalid ["ARRAYSUBS-ACTIVE"]; evaluate member224/admin1 against the rule. No content or settings updates are needed.
Expected: unsupported/wildcard status values cannot satisfy an access condition; mixed known/unknown values may only match their valid explicit statuses.
Actual: the unsupported status reaches WP_Query, which discards the invalid status filter or treats any as a wildcard. Existing subscriptions satisfy the status clause and unlock Match Any despite the role failing. Six independent failures in evidence/runtime-backend-before.txt, also present in trusted HEAD baseline behavior.
Scope/counterexamples: guests are denied; a normal nonexistent subscribed-product condition remains false; valid known subscription status aliases work. This is a pre-existing fail-open status query, not a migration-intent change.
Fix plan: require an array of valid explicit core statuses or registered ArraySubs-prefixed custom statuses, reject raw WordPress wildcard values, and return false if no valid statuses remain before querying. Preserve valid custom statuses and mixed-list narrowing. Re-run direct evaluator, persisted per-post ANY, direct-meta and core/Pro runtime cases.

Backend now rejects unsupported/wildcard status entries and malformed scalar/null lists before querying; valid aliases, mixed valid lists and a registered custom ArraySubs status are covered. Each final actual-WP Pro/core run passes 293/293. Root is performing real-browser invalid-status ANY verification before closure. The legitimate old-settings differential excludes deliberately tightened malformed-status rules.

Root real-browser verification completed on 2026-10-24: all 24 invalid-status/wildcard-status checks passed across per-post, Gutenberg and Elementor for guest/customer/member/admin. Proof: qa/restriction-final-access-audit-2026-10-24/evidence/{post,gutenberg,elementor}-{invalid-status,wildcard-status}-audience.json plus browser screenshots/snapshots. Combined with fail-closed scalar/null/mixed/custom status runtime checks, 293/293 with Pro and core. Closed after browser verification.
