# Portal filter and pagination browser results

## Summary

PASS on the isolated real portal at http://127.0.0.1:3016/admin/offboarding/.
The run-owned administrator signed in through the normal browser form. All
search, date, clear, and pagination actions used the rendered controls.
Screenshots were inspected alongside snapshots and DOM observations.

The isolated schema contains 28 explicitly labelled synthetic API fixtures for
`https://offboard-pagination.example.test/`, plus the five actual WordPress
receipts for `http://localhost:10013/`. Those five receipts remain untouched.
No synthetic records were sent to production.

## Results

| Check | Observed result | Evidence |
| --- | --- | --- |
| Email search | Exactly 28 records: 19 deactivations and 9 contact requests | `portal-pagination-page1.json`, `.txt`, `.png` |
| Next page | Page 1 shows 25 rows; clicking Next shows rows 26-28, preserves the email filter, and removes Next | `portal-pagination-page2.json`, `.txt`, `.png` |
| Previous page | Clicking Previous returns to page 1 and preserves the email filter | `portal-pagination-consistency.json` |
| Record consistency | The two pages contain 28 distinct IDs with zero overlap | `portal-pagination-consistency.json` |
| Exact UUID search | UUID `c1548dd8-62ea-4362-a53e-a10e4d5e0572` finds one expected ArraySubs deactivation | `portal-filter-exact-id.txt`, `.png` |
| No results | An unmatched string shows zero counts and "No offboarding records match these filters." | `portal-filter-no-results.txt`, `.png` |
| Inclusive UTC date range | From and through `2026-11-04` include all 28 matching receipts | `portal-filter-date-match.json`, `.txt`, `.png` |
| Excluded date range | From and through `2026-11-03` show zero matching receipts | `portal-filter-date-excluded.json`, `.txt`, `.png` |
| Clear filters | Clear removes every query/filter value and restores all 33 records | `portal-filter-clear-after-date.json` |
| Browser errors | No uncaught page errors reported in this lane | `portal-filter-browser-errors.txt` |

The date controls explicitly use UTC receipt dates. November 4 UTC was November
5 in the user's Asia/Dhaka timezone during this run.

## State and cleanup

Only the owned `offboard-portal-filter` browser session was closed. The isolated
portal process and schema remain available for the coordinator. Synthetic
fixture IDs are in `/tmp/offboard-pagination-seed.json`; they are confined to the
run schema and will be removed with its normal cleanup. Production portal data,
real users, and the five original WordPress receipts were not modified.
