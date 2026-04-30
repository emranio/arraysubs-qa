# Stage 03 — Task 09: Subscription Validation Rules

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Validation |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 03.01 |

## Objective
Trigger every save-time validation message documented in the Create and Configure user manual and confirm the exact error strings surface. Ensures regressions to the validation pipeline are caught early.

## Pre-conditions
- Stage 03.01 complete.
- A throwaway draft product `Validation Sandbox` is available (create it fresh in 09.1).

## Test Data
- Sandbox product name: `Validation Sandbox` (will be deleted/trashed at end).
- Required exact error strings:
  - `Subscription products must have a valid regular price greater than zero.`
  - `Billing interval must be between 1 and 12.`
  - `If different renewal price is enabled, you must set a valid renewal price.`
  - `Renewal price after period must be at least 1.`

## Sub-Tasks

### Sub-Task 09.1 — Create the sandbox product
**Steps:**
1. **Products → Add New**.
2. Name: `Validation Sandbox`.
3. Tick **Subscription** (leave Regular price empty for the next sub-task).
4. Save the screen by attempting to **Publish** (will fail in 09.2 if blank); otherwise click **Save draft**.

**Expected Result:**
- Product editor open with Subscription tab visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.2 — Regular price 0 / empty triggers the price error
**Steps:**
1. Set Regular price to `0`.
2. Open Subscription tab and confirm Billing Period `Week`, Interval `1`.
3. Click **Publish** (or **Update** if previously saved).
4. Note the admin notice / error message.

**Expected Result:**
- Save is blocked with the exact text:
  > `Subscription products must have a valid regular price greater than zero.`
- Product remains in draft / unsaved state.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.3 — Billing interval 13 triggers interval error
**Steps:**
1. Set Regular price to `19.99` (so price validation passes).
2. Open Subscription tab and set **Billing Interval** to `13`.
3. Click **Update**.

**Expected Result:**
- Save is blocked with the exact text:
  > `Billing interval must be between 1 and 12.`
- Reduce interval back to `1` to recover.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.4 — Empty Renewal Price with Different Renewal Price enabled
**Steps:**
1. With interval `1` and price `19.99` set, tick **Different Renewal Price**.
2. Leave Renewal Price empty.
3. Set Apply Renewal Price After to `3`.
4. Click **Update**.

**Expected Result:**
- Save is blocked with the exact text:
  > `If different renewal price is enabled, you must set a valid renewal price.`

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.5 — Apply Renewal Price After = 0 triggers period error
**Steps:**
1. Set Renewal Price to `29.99` (clears the prior error).
2. Set Apply Renewal Price After to `0`.
3. Click **Update**.

**Expected Result:**
- Save is blocked with the exact text:
  > `Renewal price after period must be at least 1.`

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.6 — Recovery: clean values save successfully
**Steps:**
1. Set Apply Renewal Price After to `3`.
2. Confirm Renewal Price `29.99`, Billing Interval `1`, Regular price `19.99`.
3. Click **Update**.

**Expected Result:**
- Product saves cleanly; no error notices.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.7 — Cleanup
**Steps:**
1. Move `Validation Sandbox` to Trash.
2. Empty trash if desired.

**Expected Result:**
- Sandbox removed from product list.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Capture screenshots of each validation error for traceability against the Validation Rules table in the user manual.
- Confirm error strings match exactly (capitalisation, punctuation, period at end). Any mismatch is a defect.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
