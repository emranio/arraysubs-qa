# Stage 13 — Task 03: Store Credit Emails (Pro)

| Key | Value |
|---|---|
| Stage | 13 — Email Notifications |
| Module | Emails — Store Credit (Pro) |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 12, 13.01 |

## Objective
Confirm the four Pro Store Credit emails (`Store Credit Added`, `Store Credit Used`, `Store Credit Expiring`, `Store Credit Expired`) appear in WooCommerce email settings, support per-email enable/disable, allow subject/heading/additional-content customization, and fully render their unique placeholder set (`{credit_amount}`, `{new_balance}`, `{customer_name}`, `{credit_source}`, `{credit_used}`, `{remaining_balance}`, `{order_id}`, `{order_url}`, `{expiring_amount}`, `{expires_at}`, `{days_remaining}`, `{shop_url}`, `{expired_amount}`, `{my_account_url}`, `{site_title}`, etc.).

## Pre-conditions
- Stage 12 complete (these emails were already partially exercised during Store Credit testing).
- ArraySubsPro is active.
- `cust3@test.local` available with a non-zero credit balance.
- Mailbox capture for `cust3@test.local` reachable.

## Test Data
- Customization values to apply in 3.3:
  - Credit Added → Subject `[QA] {customer_name} just got {credit_amount} in credit (balance now {new_balance})` / Heading `Hey {customer_name}, you just got {credit_amount}!` / Additional content `This is QA-customised content for the Credit Added email.`

## Sub-Tasks

### Sub-Task 3.1 — Confirm all four emails are registered
**Steps:**
1. **WC → Settings → Emails**.
2. Scroll the list and locate:
   - `Store Credit Added`
   - `Store Credit Used`
   - `Store Credit Expiring`
   - `Store Credit Expired`

**Expected Result:**
- All four entries are present.
- All four show **Enabled** status by default.
- Each has a **Manage** button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Inspect default subject + heading for each email
**Steps:**
1. Click **Manage** on each of the four emails in turn.
2. Note the default Subject and Email Heading values.

**Expected Result:**
- Credit Added: Subject `[{site_title}] Store credit added to your account` / Heading `Store credit has been added to your account!`.
- Credit Used: Subject `[{site_title}] Store credit used for Order #{order_id}` / Heading `Store credit applied to your order`.
- Credit Expiring: Subject `[{site_title}] Your store credit expires soon` / Heading `Your store credit is expiring soon!`.
- Credit Expired: Subject `[{site_title}] Your store credit has expired` / Heading `Your store credit has expired`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Customize Credit Added
**Steps:**
1. **WC → Settings → Emails → Store Credit Added → Manage**.
2. Replace Subject with `[QA] {customer_name} just got {credit_amount} in credit (balance now {new_balance})`.
3. Replace Email Heading with `Hey {customer_name}, you just got {credit_amount}!`.
4. Set Additional Content to `This is QA-customised content for the Credit Added email.`.
5. Save.
6. As admin, **Manage Credits** → search `cust3` → add `$10` with note `Customization test`.
7. Open the email in the customer inbox.

**Expected Result:**
- The email subject reads `[QA] cust3 just got $10.00 in credit (balance now $<balance>)`.
- The heading reads `Hey cust3, you just got $10.00!`.
- The body contains the additional content paragraph.
- Restore the defaults after this check (or at the end of the task).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Per-email enable/disable toggle
**Steps:**
1. **WC → Settings → Emails → Store Credit Used → Manage**.
2. Untick **Enable this email notification** and save.
3. As `cust3`, place a small subscription order so credit is applied (or trigger a renewal that auto-applies credit).
4. Inspect inbox.
5. Re-enable Credit Used and trigger another credit-using event.

**Expected Result:**
- With the toggle Off, no Credit Used email is delivered.
- Credit is still consumed (balance still drops correctly).
- Credit Added emails continue to fire — confirming the toggle is per-email.
- Re-enabling restores delivery.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Placeholder set on Credit Used
**Steps:**
1. After 3.4 re-enables Credit Used, inspect a recently received Credit Used email.

**Expected Result:**
- All of these are rendered in the body:
  - `{customer_name}` → `cust3`
  - `{credit_used}` → e.g., `$25.00`
  - `{remaining_balance}` → e.g., `$X.XX`
  - `{order_id}` → numeric / `#`-prefixed order number
  - `{order_url}` → clickable link to the order
  - `{my_account_url}` → clickable link
  - `{site_title}` → site name
- No raw `{...}` tokens remain.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Placeholder set on Credit Expiring
**Steps:**
1. Re-use the 12.08 expiring scenario or create a fresh one: time-travel a credit's `expires_at` to T-7 days, run the expiration job.
2. Inspect the Credit Expiring email.

**Expected Result:**
- Body renders `{customer_name}`, `{expiring_amount}`, `{expires_at}`, `{days_remaining}`, `{shop_url}`, `{my_account_url}`, `{site_title}`.
- Days remaining shows `7 days` (or pluralized correctly for other values).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Placeholder set on Credit Expired
**Steps:**
1. Continue from 3.6 — advance the same credit past expiry and run the expiration job.
2. Inspect the Credit Expired email.

**Expected Result:**
- Body renders `{customer_name}`, `{expired_amount}`, `{shop_url}`, `{my_account_url}`, `{site_title}`.
- The amount is shown (often strikethrough or grayed out).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Email type (HTML vs Plain text)
**Steps:**
1. **WC → Settings → Emails → Store Credit Added → Manage**.
2. Set **Email type** = **Plain text** and save.
3. Trigger another Credit Added event.
4. Inspect the email — it should be plain text only.
5. Restore Email type to **HTML** afterward.

**Expected Result:**
- Plain-text version of the email arrives — no `<table>`, `<button>`, or HTML formatting.
- All placeholder tokens still render correctly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Restore default subject, heading, and additional content for Credit Added and Email type for whichever email you customised.
- DevTools / debug.log: no errors during email sends.
- Confirm a different customer with no credit balance does NOT receive any of these emails for unrelated events.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Customizations restored: yes / no
- Notes:
