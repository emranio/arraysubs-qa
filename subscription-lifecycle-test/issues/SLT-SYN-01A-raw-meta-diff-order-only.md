# Raw flex-meta CSV diff cannot stay empty after the required delete/re-add probe

- **Severity:** low (QA plan false-positive; restored product values are correct)
- **Found:** 2026-08-02, D0 / SLT-SYN-01A
- **Status:** resolved 2026-08-02 — comparison canonicalized by meta key
- **Originating QA progress task:** board task `#13`, `SLT-SYN-01`, split pass `SLT-SYN-01A`, stage/window day D0 (`sync`)
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/013-slt-syn-01-audit-simple-product-flexible-renewal.md`

## Affected objects

| | |
|---|---|
| Subscription IDs | N/A — the pre-flight and post-flight gates both returned an empty list |
| Order IDs | N/A |
| Product ID | `11943`, `SLT Flex Week Segments` / `slt-flex-week-segments` |
| WordPress user/customer IDs | Acting user ID `1`, login `admin`, role `administrator`; no customer involved |
| Exact route | `https://mirror-help.arrayhash.com/wp-admin/post.php?post=11943&action=edit` |
| Browser/user context | `admin-SLT-SYN-01A`, WordPress administrator |

## Expected result

Step 17 requires the master flex checkbox to be disabled and saved, then re-enabled and saved. Expected result 12 says disabling deletes `_arraysubs_flex_sync_enabled`. Step 18 and pass criteria nevertheless require a raw `wp post meta list ... --format=csv` before/after diff to be byte-empty.

## Actual result

The product was semantically restored exactly, but the raw CSV line order changed. WordPress deleted the master meta row on disable and inserted a new row on re-enable, so `_arraysubs_flex_sync_enabled=yes` moved from the first data line to the last. All six key/value pairs were identical after sorting by meta key, and the UI redrew the original `1 - 2` / `3 - 5` / `6 - 7` ranges.

## Reproduction steps

1. Dump the six flex keys for product `11943` with the task's `wp post meta list ... --format=csv --allow-root` command.
2. In wp-admin, untick **Flexible Renewal Sync to Next Billing Cycle** and click **Update**.
3. Confirm `_arraysubs_flex_sync_enabled` is absent while the five boundary/active keys remain.
4. Re-tick the master checkbox, click **Update**, and dump the same six keys again.
5. Run a raw `diff -u` and observe only the enabled-row position changed.
6. Sort both data sets by meta key and re-run the diff; it is empty.

## Concrete proof

- Before: `/home/server-manager/slt-evidence/SLT-SYN-01A-week-flex-meta-before.csv`.
- Disabled: `/home/server-manager/slt-evidence/SLT-SYN-01A-week-flex-disabled.csv`.
- After: `/home/server-manager/slt-evidence/SLT-SYN-01A-week-flex-meta-after.csv`.
- Canonical comparison: `/home/server-manager/slt-evidence/SLT-SYN-01A-week-flex-semantic.diff`, size `0` bytes.
- Restored UI: `/home/server-manager/slt-evidence/SLT-SYN-01-06-week-reenabled.png`.
- Both subscription gates returned `[]`; there was no live subscription to observe the transient state.

## Scope notes and counterexamples

- This is not a product-state bug. Exactly the six documented flex keys exist after restore and every value matches the baseline.
- The raw ordering change is the expected database consequence of the same test's required delete/re-add behavior.
- A value-aware or key-sorted comparison is empty and is the correct restore assertion.

## Suggested resolution

Change the task to sort rows by `meta_key` (or compare a keyed JSON object) before diffing. Preserve the raw dumps as evidence, but do not require insertion order to remain stable after a key is deliberately deleted and recreated.

## Resolution and verification

- Updated `SLT-SYN-01` to retain raw CSV only as diagnostic evidence and to use key-sorted JSON for the pass
  assertion on every affected product.
- The existing product `11943` proof demonstrates the correction: the raw diff contains only the moved
  `_arraysubs_flex_sync_enabled=yes` row, while
  `/home/server-manager/slt-evidence/SLT-SYN-01A-week-flex-semantic.diff` is exactly `0` bytes.
- The live UI and all six stored key/value pairs were already verified after re-enable; no product code
  change is warranted.
