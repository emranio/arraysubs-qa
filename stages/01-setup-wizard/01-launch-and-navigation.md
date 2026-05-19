# Stage 01 — Task 01: Launch and Navigation

| Key | Value |
|---|---|
| Stage | Easy Setup Wizard |
| Module | Setup Wizard — Launch & Navigation |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | 00-preflight/* |

## Objective
Confirm the Easy Setup Wizard launches from **ArraySubs → Easy Setup**, that the **Launch Setup Wizard** action opens a full-screen modal which cannot be dismissed by clicking outside or pressing Escape, that **Next**, **Back**, and **Skip with defaults** behave as documented, and that the close-confirmation dialog displays the exact warning text from the user manual.

## Pre-conditions
- Both ArraySubs and ArraySubsPro are active.
- You are logged in as Administrator.
- No wizard answers from a prior run are unsaved in the browser session — start in a new tab.

## Test Data
- Page path: **WP-Admin → ArraySubs → Easy Setup**.
- Card label: **Setup Wizard**.
- Action button: **Launch Setup Wizard**.
- Close-confirmation copy (from manual): "Closing the wizard will discard the answers from this session. Your current plugin settings will stay unchanged."
- Close-confirmation buttons: **Keep working** and **Discard wizard**.

## Sub-Tasks

### Sub-Task 1.1 — Open the Easy Setup page
**Steps:**
1. Click **ArraySubs → Easy Setup** in the wp-admin sidebar.
2. Wait for the page to fully render.
3. Look for three cards on the page: **Setup Wizard**, **Export Settings**, and **Import Settings**.

**Expected Result:**
- The page loads with no PHP notice and no JS console error.
- A **Setup Wizard** card is visible with a **Launch Setup Wizard** button.
- An **Export Settings** card and an **Import Settings** card are also visible on the same page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Launch the wizard
**Steps:**
1. Click **Launch Setup Wizard**.
2. Observe how the wizard appears.
3. Try clicking the dark area outside the wizard.
4. Press the `Escape` key.

**Expected Result:**
- The wizard opens as a full-screen modal that covers the entire viewport.
- Clicking outside the modal does **not** close it.
- Pressing Escape does **not** close it.
- Step 1 is shown by default with the title "Your Business" or equivalent header.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Navigate forward with Next
**Steps:**
1. On Step 1, select any business type (e.g., **SaaS / Digital Software**).
2. Answer the remaining required questions on Step 1 (billing cycle, plan count, trials yes/no).
3. Click **Next**.
4. Repeat — answer Step 2 and click **Next**.

**Expected Result:**
- Each click of **Next** advances to the next step.
- The step indicator (e.g., "Step 2 of 9") increments correctly.
- Required questions are validated — leaving a required field blank shows an inline error and stops you from advancing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Navigate backward with Back
**Steps:**
1. From the current step (3 or later), click **Back**.
2. Observe whether previously entered answers are still selected.
3. Click **Back** again to return to Step 1.

**Expected Result:**
- **Back** returns you to the previous step without losing answers.
- All radio selections, checkbox states, and number inputs from the prior step are still populated as you left them.
- The step indicator decrements correctly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Use Skip with defaults
**Steps:**
1. Advance to a step that has questions you have not answered yet (e.g., Step 4 — Customer Portal).
2. Without ticking any of the displayed checkboxes, click **Skip with defaults**.
3. Click **Back** to return and verify what was preselected.

**Expected Result:**
- **Skip with defaults** advances to the next step.
- When you go back, the questions on the skipped step are now populated with the business-profile defaults — not blank.
- For example, with SaaS selected on Step 1, Step 4 should show the SaaS-default plan-switching value pre-selected (per the manual: SaaS profile uses "all-direction plan switching").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Trigger and validate the close-confirmation dialog
**Steps:**
1. Look for the close (×) icon at the top corner of the modal, or whichever close affordance is provided.
2. Click it.
3. Read the confirmation dialog text carefully.
4. Click **Keep working**.
5. Click the close icon again, then click **Discard wizard**.

**Expected Result:**
- A confirmation dialog appears with the exact text: "Closing the wizard will discard the answers from this session. Your current plugin settings will stay unchanged."
- Two buttons are present: **Keep working** and **Discard wizard**.
- Clicking **Keep working** dismisses the dialog and returns to the wizard with all answers intact.
- Clicking **Discard wizard** closes the wizard and returns you to the **ArraySubs → Easy Setup** page.
- After discarding, current plugin settings remain unchanged (re-launching the wizard does not show prior answers).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Confirm Apply Settings only appears on the final step
**Steps:**
1. Re-launch the wizard.
2. Click **Skip with defaults** repeatedly until you reach Step 9 (Review & Apply).
3. Inspect the footer button row.

**Expected Result:**
- On Steps 1 through 8, the visible action buttons are **Back**, **Skip with defaults**, and **Next**.
- On Step 9, the **Next** button is replaced (or supplemented) with **Apply Settings**.
- The review screen above the buttons summarizes every previous answer organized by step.
- Each step section in the review has an **Edit** affordance.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open DevTools Console and reproduce each step — confirm no JavaScript errors are thrown when clicking Next, Back, or Skip with defaults.
- Confirm the wizard remains usable when the browser window is resized to 1024×768 (no buttons clipped off-screen).
- After **Discard wizard**, visit **ArraySubs → Settings → General** and confirm none of the prior wizard answers leaked into saved settings.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
