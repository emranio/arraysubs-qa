# Stage 17 — Task 02: Audit Logging Settings

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Activity Audits → Logging Settings popover |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | Task 17.01 (Activity Audits screen confirmed working) |

## Objective
Verify that the **Logging Settings** popover (gear icon next to the filter bar on Activity Audits) correctly toggles each of the four optional entity logs — **Product**, **Coupon**, **Email**, **Settings** — and that disabling a toggle suppresses new audit rows for that entity, while enabling the toggle restores logging immediately. Also confirm that Subscription, Member, Order, and System entries are always recorded regardless of toggle state.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- Activity Audits screen at **ArraySubs → Audits [beta] → Activity Audits** is reachable.
- A test product and a test coupon code exist (record below).
- Stripe gateway test mode is connected, so triggering an Email log entry is possible by sending a test email.

## Test Data
- Test product (ID: ____)
- Test coupon code: `QA-AUDIT-COUPON` (create if not present, simple percent discount, 10%)
- Setting under test: **Renewal Reminder Days Before** (under General Settings)
- Email under test: trigger a Renewal Reminder by manually re-sending it from a subscription detail page (or use **WooCommerce → Settings → Emails** preview)

## Sub-Tasks

### Sub-Task 02.1 — Open Logging Settings popover
**Steps:**
1. Go to **ArraySubs → Audits [beta] → Activity Audits**.
2. Click the **gear icon** next to the filter bar.
3. Inspect the popover.

**Expected Result:**
- Popover lists four toggles in this order with descriptions:
  - **Product** — "Log product-related changes on subscriptions" — Enabled by default
  - **Coupon** — "Log coupon applications and discount tracking" — Enabled by default
  - **Email** — "Log email send events" — Enabled by default
  - **Settings** — "Log plugin settings changes" — Enabled by default
- An info note states that Subscription, Member, Order, and System-level entries cannot be disabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Disable Product logging, edit a product, verify NO row
**Steps:**
1. In the Logging Settings popover, toggle **Product** OFF. Wait for the success toast/save indicator.
2. Close the popover.
3. Note the current "(M total)" count (Product entity unfiltered): ____
4. In a new tab, go to **Products → Edit** the test product.
5. Change the price by $0.01, click **Update**.
6. Return to Activity Audits, click **Refresh**, filter by **Entity = Product**.
7. Inspect for any new row with timestamp newer than the price change.

**Expected Result:**
- No new Product audit row appears for the edit performed while logging was disabled.
- The Product filter shows the same count as before the edit.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Re-enable Product logging, edit again, verify row appears
**Steps:**
1. Open Logging Settings popover.
2. Toggle **Product** back ON. Wait for save.
3. Close popover.
4. Edit the test product again — change the price back to original. Click **Update**.
5. Refresh Activity Audits and filter by **Entity = Product**.

**Expected Result:**
- A new Product audit row appears with the price field change in the `changes →` modal.
- The previous edit (made while disabled) is still NOT present — disabling does not retro-log.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Disable Coupon logging, apply a coupon, verify NO row
**Steps:**
1. Toggle **Coupon** OFF in Logging Settings.
2. Open an incognito window. As `member1@example.com`, add the test product to cart.
3. At checkout, apply the `QA-AUDIT-COUPON` code. Confirm the discount appears in the order summary.
4. Do NOT complete the order — abandon the cart.
5. In admin, refresh Activity Audits and filter by **Entity = Coupon**.

**Expected Result:**
- No new Coupon audit row appears for the application performed while logging was disabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Re-enable Coupon logging, apply again, verify row appears
**Steps:**
1. Toggle **Coupon** ON.
2. Repeat the cart + apply coupon flow from Sub-Task 02.4.
3. This time, complete the checkout (use Stripe test card `4242 4242 4242 4242` or any Manual gateway).
4. Refresh Activity Audits and filter by **Entity = Coupon**.

**Expected Result:**
- A new Coupon audit row appears with the coupon code referenced.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Disable Email logging, trigger an email, verify NO row
**Steps:**
1. Toggle **Email** OFF in Logging Settings.
2. Open the test subscription's admin detail page.
3. Trigger a customer-facing email (e.g., from the Actions or Status panel, send a "Renewal Reminder" email manually, or change status from Active → On Hold which sends Subscription On-Hold).
4. Refresh Activity Audits and filter by **Entity = Email**.

**Expected Result:**
- No new Email audit row appears for the email send performed while logging was disabled.
- (Note: the email itself is still sent. Logging is independent of delivery.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Re-enable Email logging, trigger again, verify row appears
**Steps:**
1. Toggle **Email** ON.
2. Restore subscription from On Hold → Active (which sends "Subscription Reactivated" email).
3. Refresh Activity Audits and filter by **Entity = Email**.

**Expected Result:**
- A new Email audit row appears naming the email type that was sent.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.8 — Disable Settings logging, change a setting, verify NO row; then re-enable
**Steps:**
1. Toggle **Settings** OFF in Logging Settings.
2. Go to **ArraySubs → Settings → General Settings** and change **Active Grace Days** from current value to a different value. Save.
3. Refresh Activity Audits and filter by **Entity = Settings**.
4. Confirm no new row.
5. Toggle **Settings** ON.
6. Change **Active Grace Days** back to original. Save.
7. Refresh and filter by **Entity = Settings**.

**Expected Result:**
- No row appears for the change while Settings logging was disabled.
- A new Settings row appears for the change made after re-enabling, with `changes →` modal showing the field that changed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm Subscription and Order audit entries continued to be created throughout this task (filter by Entity = Subscription and confirm the customer's coupon-purchase order created a Subscription audit row even while Product/Coupon/Email were disabled).
- Re-verify all four toggles are returned to their original ON state at the end of the task.
- Confirm the disable-then-edit-then-enable pattern does NOT retroactively log any of the suppressed events.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test product ID:
- Test coupon code:
- Notes:
