# Stage 17 — Task 01: Activity Audits

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Activity Audits |
| Plugin Coverage | Pro |
| Estimated Time | 35 min |
| Depends On | Stage 06 (lifecycle data must exist), Stage 02 (admin/shop-manager users created) |

## Objective
Verify the **ArraySubs → Audits [beta] → Activity Audits** screen renders the audit table with all columns, that every filter (Author Role, Entity Type, Date Range, Search, Reset, Refresh) narrows the result set correctly, and that each role of actor (admin, customer, shop manager, system) produces an audit row with the correct author badge and entity badge.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active. Without Pro, the screen shows a placeholder — abort if so and note in Sign-off.
- At least 30 existing audit entries (so pagination "Page 1 of 2" or higher appears). If fewer, regenerate by toggling any subscription status a few times before starting.
- Shop Manager user `shop_manager_qa` exists with role `shop_manager` and a known password.
- One Active subscription owned by `member1@example.com` with cancellation enabled in customer portal.

## Test Data
- Admin user: site administrator (record username: ____)
- Shop Manager user: `shop_manager_qa`
- Customer user: `member1@example.com`
- Subscription under test (ID: ____ — record before starting)
- Product under test (ID: ____ — record before starting; will be edited in Sub-Task 01.5)
- Site timezone: ____ (from **Settings → General**)

## Sub-Tasks

### Sub-Task 01.1 — Open Activity Audits and verify table columns
**Steps:**
1. In wp-admin, click **ArraySubs → Audits [beta] → Activity Audits**.
2. Confirm the URL contains the audits screen (record the URL slug for reference).
3. Read the column headers at the top of the table from left to right.
4. Confirm pagination text at the bottom of the table reads `Page 1 of N (M total)` where `M >= 30`.

**Expected Result:**
- Six columns visible in this order: **Date**, **Author**, **Type**, **Visibility**, **Entity**, **Activity**.
- Newest entries are at the top (reverse chronological).
- Each Author cell shows a colour-coded badge: System / Admin / Customer / Gateway.
- Each Type cell shows an entity badge from the set: Subscription / Member / Product / Order / Coupon / Email / Settings / Other.
- Visibility column shows either **Private** (lock icon) or **Customer** (eye icon).
- Activity column for at least one entry shows a `changes →` link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Verify the changes modal
**Steps:**
1. Locate any audit row where the Activity column shows `changes →`.
2. Click the `changes →` link.
3. Inspect the modal that opens.
4. Click the close button (X or outside the modal) to dismiss.

**Expected Result:**
- Modal opens with a heading naming the entity the change relates to (e.g., "Subscription #142").
- A two-column table inside lists each changed field with **Previous value** and **Changed value** columns.
- Empty values render as a single dash `—`.
- Modal closes cleanly without page reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Filter by Author Role
**Steps:**
1. Open the **Author Role** dropdown in the filter bar.
2. Confirm the dropdown options are exactly: **All Authors**, **System**, **Admin**, **Customer**, **Gateway**.
3. Select **Admin** and click outside (or press Enter) to apply.
4. Verify the filtered result.
5. Click **Reset** to clear filters.
6. Repeat for **System**, **Customer**, **Gateway** in turn.

**Expected Result:**
- For each selection, every visible row's Author badge matches the selected role.
- Total count in pagination updates (`(M total)` decreases compared to the unfiltered count).
- **Reset** restores the full unfiltered list.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Filter by Entity Type, Date Range, and Search
**Steps:**
1. Open the **Entity Type** dropdown. Confirm options exactly match: **All Entities**, **Subscription**, **Member**, **Product**, **Order**, **Coupon**, **Email**, **Settings**, **Other**.
2. Select **Subscription**. Confirm only Subscription badges remain.
3. Reset filters.
4. In **Date Range**, enter **From** = today's date minus 1 day, **To** = today's date. Apply.
5. Confirm only rows with a Date in that 2-day window appear.
6. Reset filters.
7. In the **Search** input, type a known subscription number (e.g., `#142` — replace with the recorded ID) and press Enter.
8. Confirm only matching rows appear (search matches note content).
9. Reset filters.

**Expected Result:**
- Each filter narrows the table independently.
- Combining filters works (try Author=Admin + Entity=Settings — record the count: ____).
- Reset returns to the full unfiltered list and pagination total returns to the original count.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Admin makes a settings change → verify audit row
**Steps:**
1. Open a new tab. Go to **ArraySubs → Settings → General Settings**.
2. Change the **Renewal Reminder Days Before** value from its current value to a different value (record old: ____, new: ____).
3. Click **Save Changes** and confirm the success toast.
4. Return to the Activity Audits tab and click the **Refresh** icon (spinning circle) at the top of the filter bar.
5. Filter by **Author = Admin** and **Entity = Settings**.
6. Find the most recent row.
7. Click `changes →` on that row.

**Expected Result:**
- A new row appears at the top with Author badge **Admin** (showing the admin's user name), Type **Settings**, and Activity describing the settings update.
- The changes modal lists the modified field with the old value as **Previous value** and the new value as **Changed value**.
- Date matches "just now" in the site timezone.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Customer cancels subscription → verify audit row
**Steps:**
1. Open an incognito browser window. Log in as `member1@example.com`.
2. Go to **My Account → Subscriptions** and open the Active subscription.
3. Click **Cancel** (immediate or end-of-period — record which: ____).
4. Confirm in the dialog.
5. Verify the success message: `Subscription cancelled successfully.` (or end-of-period equivalent).
6. Return to the admin browser. On Activity Audits, click **Refresh**.
7. Filter by **Author = Customer** and **Entity = Subscription**.

**Expected Result:**
- A new row appears at the top with Author badge **Customer** (showing `member1@example.com` or display name), Type **Subscription**, Visibility may be **Customer**.
- Activity column describes the cancellation; click `changes →` to see Status: Active → Cancelled (or `_waiting_cancellation: false → true` for end-of-period).
- Entity column contains a clickable pill linking to the subscription detail page; clicking it opens the subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Shop Manager edits a product → verify audit row
**Steps:**
1. Log out the customer session (close incognito window).
2. Open a new incognito window and log in as `shop_manager_qa`.
3. Go to **Products → All Products** and open the recorded test product.
4. Change the **Regular price** (record old: ____, new: ____).
5. Click **Update**. Confirm the "Product updated" notice.
6. Log out the shop manager and return to the admin browser.
7. On Activity Audits, click **Refresh**.
8. Filter by **Author = Admin** (shop manager is treated as Admin via `manage_woocommerce` capability) and **Entity = Product**.

**Expected Result:**
- A new row appears with Author badge **Admin** showing the username `shop_manager_qa`, Type **Product**.
- Activity describes the price change; `changes →` modal lists the price field with old vs new values.
- Entity pill links to the product edit screen.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.8 — Pagination
**Steps:**
1. Reset all filters.
2. Scroll to the pagination at the bottom of the table.
3. Click **Next**.
4. Confirm Page indicator increments to `Page 2 of N`.
5. Click **Previous** to return to Page 1.

**Expected Result:**
- 30 entries per page rendered.
- **Next** and **Previous** buttons enable/disable correctly at boundaries (Previous disabled on Page 1, Next disabled on the last page).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Reactivate the cancelled subscription from the admin so Stage 18 has a usable Active subscription. Verify a new audit row appears with Author **Admin** and Activity describing the reactivation.
- Restore the **Renewal Reminder Days Before** setting to its original value. Confirm a second Settings audit row is created with the values reversed.
- Restore the product price to its original value.
- Confirm the original audit count + 4 new rows (settings change, settings revert, customer cancel, admin reactivate, shop-manager edit, shop-manager revert) is reflected in the unfiltered total.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID used:
- Product ID used:
- Notes:
