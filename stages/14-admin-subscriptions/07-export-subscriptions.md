# Stage 14 — Task 07: Export Subscriptions (CSV and JSON)

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — Export CSV / REST JSON export |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 14-01 (status filtering works) |

## Objective
Export subscriptions in **CSV** with the **Active** status filter applied and confirm the file downloads, contains only Active rows, has all 15 documented columns, is UTF-8 encoded with a BOM (Excel-friendly), and that the filename includes the date and time. Then repeat the export in **JSON** by calling the documented REST endpoint and confirm the JSON payload structure matches the CSV row count and fields.

## Pre-conditions
- Logged in as Administrator (so the REST endpoint is authenticated via cookie).
- A spreadsheet application that opens UTF-8 CSV with a BOM (Excel, Numbers, or LibreOffice Calc).
- At least 5 Active and at least 5 Cancelled subscriptions exist (so filter difference is visible).
- A way to view the JSON endpoint output (browser address bar plus a JSON viewer extension, or `View Source`).

## Test Data
- Active count expected to match the count shown on the Active tab in the list.
- REST endpoint: `GET {site}/wp-json/arraysubs/v1/subscriptions/export?format=json`.

## Sub-Tasks

### Sub-Task 7.1 — Export CSV from the Active tab
**Steps:**
1. Open **ArraySubs → Subscriptions**.
2. Click the **Active** status tab.
3. Note the visible Active count (e.g. "Active (12)").
4. Click the **Export CSV** button.
5. Confirm a file download begins in the browser.

**Expected Result:**
- A CSV downloads automatically.
- Filename matches the pattern `subscriptions-export-YYYY-MM-DD-HHmmss.csv` (e.g. `subscriptions-export-2026-04-29-143022.csv`).
- DevTools Network tab shows a 2xx response with `Content-Type: text/csv` (or similar) and an attachment disposition.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.2 — Open the CSV and verify encoding + columns
**Steps:**
1. Open the downloaded file in Excel, Numbers, or LibreOffice Calc.
2. Inspect the header row.
3. Inspect a sample row.

**Expected Result:**
- Encoding renders accented characters correctly (no `Ã©` or replacement glyphs) — confirming UTF-8 BOM is present.
- 15 columns in this order: Subscription ID, Status, Customer Name, Customer Email, Product Name, Recurring Amount, Currency, Billing Cycle, Start Date, Next Payment Date, Last Payment Date, End Date, Total Payments, Payment Method, Created Date.
- Sample row's data lines up with what appears in the admin list for that subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.3 — Verify only Active rows are present in the CSV
**Steps:**
1. Scan the **Status** column in the CSV.
2. Count the data rows.

**Expected Result:**
- Every row has Status = **Active** (localized label).
- Total data row count equals the Active count shown on the Active tab.
- No Cancelled, Pending, On Hold, Trial, or Expired rows leak into the file.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.4 — Re-export from the All tab and confirm filter respect
**Steps:**
1. Click the **All** tab.
2. Click **Export CSV** again.
3. Compare row counts.

**Expected Result:**
- The new file contains all subscriptions regardless of status (row count equals the All tab count).
- Filename has a different timestamp.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.5 — Export JSON via REST endpoint
**Steps:**
1. While still logged in as Administrator (cookie-authenticated), open a new browser tab.
2. Visit `{site}/wp-json/arraysubs/v1/subscriptions/export?format=json`.
3. Inspect the response.

**Expected Result:**
- HTTP 200.
- `Content-Type` is `application/json`.
- The body is a valid JSON array of subscription objects.
- Each object contains the same 15 fields documented for the CSV (or their JSON-shaped equivalents — keys may use snake_case).
- Row count equals the All tab count.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.6 — Reconcile CSV and JSON for one row
**Steps:**
1. Pick one Subscription ID from the JSON output.
2. Find the same Subscription ID in the All-tab CSV from sub-task 7.4.
3. Compare Status, Customer Email, Product Name, Recurring Amount, Currency, Billing Cycle, Start Date, Next Payment Date, Total Payments.

**Expected Result:**
- Every shared field matches between CSV and JSON for that subscription.
- Date formats may differ (CSV may format for display, JSON may be ISO 8601), but the underlying values are equivalent.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.7 — Negative auth check on JSON endpoint
**Steps:**
1. Open an incognito window (no admin cookie).
2. Visit `{site}/wp-json/arraysubs/v1/subscriptions/export?format=json`.

**Expected Result:**
- The endpoint either returns 401/403 or returns an empty/limited result — never the full subscription dataset to an unauthenticated request.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open the CSV in a plain text editor and confirm the BOM bytes (`EF BB BF`) appear at the start of the file.
- Confirm any commas or quotes inside customer names or product names are escaped with double-quote wrapping in the CSV.
- Repeat sub-task 7.1 logged in as Shop Manager — the export should succeed with the same data scope (capability check is `manage_woocommerce`).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Active count exported:
- All count exported:
- JSON row count:
- Notes:
