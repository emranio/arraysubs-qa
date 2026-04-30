# Stage 01 — Task 05: Apply and Verify Settings

| Key | Value |
|---|---|
| Stage | Easy Setup Wizard |
| Module | Setup Wizard — Apply & Persistence |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | 01-setup-wizard/01-launch-and-navigation.md, 01-setup-wizard/02-business-type-defaults.md, 01-setup-wizard/04-pro-vs-free-options.md |

## Objective
Run a complete SaaS / Digital Software profile through the wizard, click **Apply Settings** on Step 9, verify the success message, and then open **ArraySubs → Settings → General** and **ArraySubs → Settings → Toolkit** to confirm at least 6 settings now match the wizard answers. Re-run the wizard with the same answers to confirm idempotency (no corruption, no doubled-up values).

## Pre-conditions
- Stage 01 Tasks 01–04 passed.
- ArraySubsPro is active.
- Logged in as Administrator.
- Take a snapshot of current `arraysubs_settings` (Settings → Easy Setup → Export Settings) before starting so you can compare diffs.

## Test Data
- Profile to use: **SaaS / Digital Software**.
- Concrete answers to set (so they are easy to verify after Apply):
  - Step 1: SaaS, **Weekly** (the predominant cycle in the test catalog), Multiple plans / tiers, trials Yes, payment required Yes, one trial per customer Yes.
  - Step 2: Strict (1 / 3) grace; tick **Allow pausing the subscription**, max pause **30 days**, max pauses **2**.
  - Step 3: **Only one subscription per customer**, auto-migrate **Yes**.
  - Step 4: Plan switching **Allow all switching directions**, proration **Prorate immediately**.
  - Step 5: Cancel **Immediately**, retention offers **Yes**, **Discount offer** ticked at **20%** for **3 cycles**.
  - Step 6: Restricted-content behavior **Redirect to a pricing or signup page**, redirect path `/pricing`, require login **Yes**.
  - Step 7: Notifications **Essential only**, renewal reminder **3 days before**.
  - Step 8: Tick **Hide Admin Bar for Customers**, **Restrict WP Dashboard Access** (redirect to **My Account page**), **Multi-Login Prevention** at **2 sessions**.

## Sub-Tasks

### Sub-Task 5.1 — Snapshot current settings (safety net)
**Steps:**
1. Go to **ArraySubs → Easy Setup**.
2. On the **Export Settings** card, click **Export Settings**.
3. Save the downloaded `arraysubs-settings-YYYY-MM-DD.json` file as `pre-wizard-snapshot.json`.

**Expected Result:**
- A JSON file downloads.
- A success toast confirms: "Settings exported successfully!".
- The file is non-empty (at least a few KB).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Run the wizard end to end with the SaaS profile
**Steps:**
1. Click **Launch Setup Wizard**.
2. On every step, set the exact answers listed in **Test Data** above. Do not leave any answer at default unless explicitly stated.
3. Advance to Step 9.
4. Confirm the review screen lists every answer correctly.

**Expected Result:**
- Each step advances cleanly with no validation error.
- The Step 9 review shows the count of answers ready to apply along with the note: "The wizard only applies the supported settings below. Advanced rules and content structures stay untouched."
- The bottom of the review shows the "Still configure manually after the wizard" section listing cancellation reasons, member access rules, profile-field definitions, etc.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Click Apply Settings and capture the success message
**Steps:**
1. On Step 9, click **Apply Settings**.
2. Watch for the success message and note its exact wording.
3. Watch for any error message that might appear in the wizard footer.

**Expected Result:**
- A success message appears confirming how many settings were configured (per manual: "A success message confirms how many settings were configured.").
- No red error message is shown.
- The wizard either closes automatically or returns you to the **ArraySubs → Easy Setup** page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Verify General Settings reflect at least 6 wizard answers
**Steps:**
1. Go to **ArraySubs → Settings → General**.
2. Compare each section against the wizard answers from Sub-Task 5.2.
3. Take note of every value that matches.

**Expected Result (verify at least 6 of these match):**
- **Multiple Subscriptions → One Subscription Per Customer** = On.
- **Multiple Subscriptions → Auto Migrate to New Subscription Upon Checkout** = On.
- **Checkout & Trials → Require Payment Method for Trials** = On.
- **Checkout & Trials → One Trial Per Customer** = On.
- **Grace Period → Days Active After Due** = 1.
- **Grace Period → Days On-Hold Before Cancel** = 3.
- **Email Reminder Schedule → Renewal Reminder (Days Before)** = 3.
- **Customer Actions → Allow Suspension (Pause)** = On (because pause was enabled in Step 2).
- **Cancellation Settings → Cancel Immediately** = On.
- **Billing → Default Billing Period (`billing_period`)** is saved as `week` (matches the Step 1 Weekly choice and the predominantly-weekly catalog).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Verify Toolkit Settings reflect the wizard answers
**Steps:**
1. Go to **ArraySubs → Settings → Toolkit**.
2. Inspect each section.

**Expected Result:**
- **Admin Bar → Hide Admin Bar for Non-Admin Users** = On.
- **Admin Dashboard Access → Restrict wp-admin Access** = On with **Redirect Unauthorized Users To** = **WooCommerce My Account page**.
- **Multi-Login Prevention → Enable Multi-Login Prevention** = On with **Default Max Sessions Per User** = 2.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — Re-run the wizard with identical answers (idempotency)
**Steps:**
1. Return to **ArraySubs → Easy Setup → Launch Setup Wizard**.
2. Walk through the same 9 steps with the same answers as in Sub-Task 5.2.
3. Click **Apply Settings**.
4. Re-open **General Settings** and **Toolkit Settings** and re-verify.

**Expected Result:**
- The second run completes with the same success message.
- All values in **General Settings** and **Toolkit Settings** still match the wizard answers — no doubled values, no reset values.
- No new admin notice or PHP error appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.7a — Re-run with Monthly billing cycle to verify month persistence
**Steps:**
1. Discard any open wizard, then **Launch Setup Wizard** again.
2. Walk through the same SaaS profile, but on Step 1 change billing cycle to **Monthly** (this is the cycle used by the **Basic Monthly $29.99** product).
3. Click **Apply Settings** on Step 9.
4. Re-open **ArraySubs → Settings → General** and inspect the billing section.

**Expected Result:**
- The Apply call succeeds with no error.
- **Billing → Default Billing Period (`billing_period`)** is now saved as `month`.
- This proves both `week` (Sub-Task 5.4) and `month` persist correctly through the wizard.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.7 — Confirm wizard does not touch unrelated settings
**Steps:**
1. Recall any setting you customized **outside** the wizard's scope before Sub-Task 5.1 (e.g., a custom **Add to Cart Text** value, or a custom email subject).
2. Re-open the relevant settings page.
3. Confirm that value is still intact.

**Expected Result:**
- Settings the wizard does not configure are unchanged after both runs.
- Per the manual: "The wizard merges with existing settings — any setting you did not configure in the wizard stays unchanged."

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open `wp-content/debug.log` and confirm no errors appeared during either Apply Settings call.
- Compare a fresh export (download a new `arraysubs-settings-YYYY-MM-DD.json` after the second run) against the first applied state — the diff should be empty or near-empty (only timestamp metadata differs).
- Confirm DevTools Network tab shows the Apply call returned HTTP 200 with a success body.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Number of settings verified:
- Notes:
