# Stage 17 — Task 06: Portal Action Failures

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Portal Action Failures |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | Task 17.01, Stage 07 (customer portal verified) |

## Objective
Force a portal action error — by disabling **Allow Customers to Cancel** in settings then having a customer attempt the cancel action — and verify that the failure is logged to the **Portal Action Failures** troubleshooting screen with the correct error code, HTTP status, and customer/subscription identification. Use the screen to view and resolve the failure.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- Customer `member1@example.com` has an Active subscription.
- Customer can log in to **My Account → Subscriptions**.
- Browser dev tools available (we will inspect the Network tab to confirm the REST endpoint response).

## Test Data
- Test customer: `member1@example.com`
- Subscription ID: ____
- Setting under test: **ArraySubs → Settings → General Settings → Customer Action Toggles → Allow Customers to Cancel**
- Original value of toggle: ____ (expected: ON before test starts)

## Sub-Tasks

### Sub-Task 06.1 — Disable customer cancellation in settings
**Steps:**
1. Go to **ArraySubs → Settings → General Settings**.
2. Scroll to **Customer Action Toggles**.
3. Toggle **Allow Customers to Cancel** OFF.
4. Click **Save Changes**.
5. Confirm the success toast.

**Expected Result:**
- Setting saved. A Settings audit row may also appear in Activity Audits (cross-check optional).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Confirm the Cancel button is hidden in the customer portal
**Steps:**
1. Open an incognito browser. Log in as `member1@example.com`.
2. Go to **My Account → Subscriptions**.
3. Open the Active subscription.
4. Look for the **Cancel** button.

**Expected Result:**
- The Cancel button is NOT present (because cancellation is disabled in settings).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Force the cancel endpoint via DevTools (simulate UI bypass)
**Steps:**
1. Still logged in as `member1@example.com` in incognito, open browser DevTools → **Console** tab.
2. Find the WordPress REST nonce on the page (open DevTools → **Application/Storage → Cookies** for the wp_rest nonce, OR check the page source for `wp.apiFetch.use` data — record nonce value: ____).
3. In the Console, paste a fetch call to attempt cancellation (replace SUBSCRIPTION_ID and NONCE):
   ```js
   fetch('/wp-json/arraysubs/v1/subscriptions/SUBSCRIPTION_ID/cancel', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': 'NONCE' },
     body: JSON.stringify({ reason: 'test' })
   }).then(r => r.json().then(j => console.log(r.status, j)));
   ```
4. Press Enter and read the response.

**Expected Result:**
- Response HTTP status is **400** (or **403**) and the JSON body contains an error code matching `cancellation_disabled` (or similar) with a message such as "Customer cancellation is not available for this subscription."
- The subscription's status is unchanged (still Active).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — Open the Portal Action Failures troubleshooting screen
**Steps:**
1. Switch back to the admin browser.
2. Navigate to **ArraySubs → Audits [beta] → Portal Action Failures** (record exact path: ____).
3. Inspect the most recent row.

**Expected Result:**
- A row exists with:
  - **Action**: Cancel (or `cancel`)
  - **Customer**: `member1@example.com`
  - **Subscription**: clickable link to the subscription `#NNN`
  - **Error code**: `cancellation_disabled` (or similar)
  - **HTTP status**: `400` (or `403`)
  - **Timestamp**: matches "just now" in site timezone
  - Action buttons: **View** (or details modal) and **Resolve**

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — View the failure detail
**Steps:**
1. Click **View** (or the row itself) to open the detail modal/drawer.
2. Inspect the contents.

**Expected Result:**
- Detail panel shows:
  - Full error message (matching the REST response message string)
  - Stack/context info if any (request body, action arguments)
  - Suggested resolution: e.g., "Re-enable Allow Customers to Cancel in General Settings."

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Resolve the failure (re-enable + resolve)
**Steps:**
1. Re-open **ArraySubs → Settings → General Settings**, toggle **Allow Customers to Cancel** back ON, save.
2. Return to Portal Action Failures.
3. On the failure row, click **Resolve** (or "Mark as resolved").
4. Confirm the dialog.

**Expected Result:**
- The row is removed from the active failures list (or marked Resolved with a green badge).
- An Activity Audits row records the admin resolution (Author = Admin, Entity = Subscription/Other).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.7 — Confirm cancellation works again
**Steps:**
1. In incognito, refresh `member1@example.com`'s **My Account → Subscriptions** page.
2. Confirm the **Cancel** button is now visible on the Active subscription.
3. Do NOT actually click cancel — Stage 18 needs this subscription Active.

**Expected Result:**
- Cancel button appears (no further failure created).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the Activity Audits screen logged the admin's two settings changes (disable + enable) under Author = Admin, Entity = Settings.
- Confirm no extra subscription rows were inadvertently affected — `member3@example.com`'s subscription should be untouched.
- Verify the Portal Action Failures screen now shows zero active failures.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Error code captured:
- HTTP status captured:
- Notes:
