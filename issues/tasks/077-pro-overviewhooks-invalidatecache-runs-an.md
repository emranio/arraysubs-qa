---
id: 77
title: Pro OverviewHooks::invalidateCache() runs an unescaped LIKE DELETE that scans and locks every wp_options row on each status change
status: closed
priority: low
created: 2026-10-20T16:24:13.651757589+02:00
updated: 2026-10-21T02:09:43.238272657+02:00
started: 2026-10-20T19:27:41.691365143+02:00
completed: 2026-10-21T02:09:43.238271375+02:00
tags:
    - pro
    - analytics
    - performance
    - database
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 4 of the #65–#70 fixes while investigating probe deadlocks; pre-existing, unrelated to them). Fix log: qa/issues-65-69-fix-log.md (R4-12).
Affected subscription IDs and order IDs: N/A (every order and subscription status change).
Affected WordPress user/customer: N/A.
Exact route: `arraysubspro/src/Features/Analytics/Services/OverviewHooks.php:212-221` `invalidateCache()`, hooked on `arraysubs_data_status_changed` and `woocommerce_order_status_changed` (:51-52). It runs `DELETE FROM wp_options WHERE option_name LIKE '_transient_arraysubs_overview_%' OR option_name LIKE '_transient_timeout_arraysubs_overview_%'` without esc_like(), so `_` is a wildcard and the pattern has no fixed prefix.
Reproduction: EXPLAIN the shipped DELETE on staging (MySQL 8.0.46, wp_options 1,307 rows), and run the method inside a rolled-back transaction while a second connection locks or inserts an unrelated option.
Expected: an index range scan on option_name that locks only the matching rows.
Actual: type=ALL full scan (~1,361 rows); 1,333 rows X-locked (next-key locks incl. 24 supremum records); a second connection's `SELECT ... FOR UPDATE NOWAIT` on blogdescription was refused and its option INSERT waited until the lock timeout. Escaped version: type=range, 2 rows read, 4 rows locked, 0.58 ms, nothing blocked.
Proof: `qa/fix-65-69/evidence/r4-overview-invalidate-explain.json`, `r4-overview-invalidate-locks.json`.
Scope notes: with autocommit (no production path wraps a status change in a transaction) the locks last ~5 ms per status change, growing with wp_options size; no money or data impact. Fix: `$wpdb->prepare(... LIKE %s OR option_name LIKE %s, $wpdb->esc_like('_transient_arraysubs_overview_') . '%', $wpdb->esc_like('_transient_timeout_arraysubs_overview_') . '%')`. Do not switch to delete_transient() on known keys: Pro's keys are md5-hashed and this DELETE also clears core's arraysubs_overview_v2_* cache (core Builder::flush() has no callers).

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: Pro OverviewHooks::invalidateCache() escapes its LIKE prefixes with $wpdb->esc_like() inside $wpdb->prepare(). EXPLAIN on staging: type=range on the option_name index, rows=2 (was a full table scan).
