---
id: 52
title: Validate credit history fields even when its bucket is empty
status: closed
priority: medium
created: 2026-10-18T17:25:15.794032+06:00
updated: 2026-10-18T17:26:01.317171+06:00
started: 2026-10-18T17:26:01.317171+06:00
completed: 2026-10-18T17:26:01.317171+06:00
tags:
    - bug
    - developer-api
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.
Site/route: http://localhost:10013/. Trusted PHP StoreCredit::history() via WP-CLI after wp_loaded; no browser/REST route. Pro active, HPOS enabled.
Affected subscription/order IDs: N/A.
Affected WordPress user/customer: N/A exact temporary credit-fields-* customer login and generated example.test email, role customer, removed in finally. Actor: system CLI; this read does not require an actor mutation.

Reproduction:
1. Resolve StoreCredit::fromCustomerId() for a registered customer with an empty ledger.
2. Call history(['fields' => ['unknown']]) or history(['fields' => [null]]).
Expected: invalid_field regardless of ledger contents; valid fields retain the empty collection envelope.
Actual before fix: source validation called Fields::select() only inside the item loop. An empty history skipped that loop and returned a successful envelope. Nonempty histories already rejected the same projection.
Proof: DeveloperAccess::request('history') placed projection validation solely inside foreach ($history['items']); the empty array cannot execute it. A dedicated real WP regression now exercises both invalid requests and a valid empty projection. No pre-fix runtime output was captured; this issue records the confirmed source path and post-fix runtime evidence separately.
Known scope: affects only invalid field selection on empty Store Credit history; data() and populated history already validate fields. No balances were mutated.
Fix plan: expose TransactionLogger::FIELDS and validate selected fields through the shared Fields helper before querying the ledger. Preserve the existing per-item projection and pagination contract. Run the focused fixture-cleanup-safe regression before closing.

[[2026-10-18]] Sun 17:26
Verified 2026-10-18: shared TransactionLogger::FIELDS validation now runs before reading the ledger. Real WP focused regression passed 3/3 (unknown field, non-string field, valid empty envelope), with exact temporary customer removed in finally. Evidence: qa/developer-support-2026-10-18/check-credit-history-fields.php and credit-history-fields-results.txt. Root subscription/order matrices separately pass 77/77 with Pro and 77/77 with Pro skipped on HPOS, fixtures_cleaned=true.
