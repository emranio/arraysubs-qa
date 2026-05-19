# Stage 01 — Task 03: Conditional Questions

| Key | Value |
|---|---|
| Stage | Easy Setup Wizard |
| Module | Setup Wizard — Conditional Logic |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | 01-setup-wizard/01-launch-and-navigation.md |

## Objective
Confirm conditional questions in the wizard show and hide correctly based on parent answers. Specifically: trial-payment and trial-limit questions hide when "Do you offer free trials = No"; max-skips and skip-day-window questions only appear when skip is enabled; max-pause-duration only when pause is enabled; switching-direction only when multiple plans are selected; and conditional questions on later steps re-evaluate when an earlier answer is changed.

## Pre-conditions
- Stage 01 Tasks 01 and 02 passed.
- Both plugins active.
- Logged in as Administrator.
- Use **Discard wizard** between scenarios so prior answers do not bleed into the new run.

## Test Data
- Step 1 trial trigger question: **Do you offer free trials?** with options **Yes** / **No**.
- Step 1 plan-count question: **How many subscription plans do you offer?** with options **One plan** / **Multiple plans / tiers**.
- Step 2 skip checkbox: **Allow skipping the next renewal**.
- Step 2 pause checkbox: **Allow pausing the subscription**.
- Step 6 access-control trigger: **Do you need subscription-based content restriction?** (only shown for non-implied business types).
- Step 8 Store Credit trigger: **Store Credit System** checkbox.

## Sub-Tasks

### Sub-Task 3.1 — Trial questions hide when trials = No
**Steps:**
1. Launch the wizard and choose **Other / Custom** on Step 1 (so no trial defaults are forced).
2. Set **Do you offer free trials?** to **No**.
3. Look at the surrounding questions on Step 1.
4. Now switch the answer to **Yes** and look again.

**Expected Result:**
- When **No** is selected: the questions **Should a payment method be required for a free trial?** and **Limit free trials to one per customer?** are hidden.
- When **Yes** is selected: both questions become visible.
- Switching back to **No** hides them again immediately, with no page reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Custom billing-cycle questions only with "Custom"
**Steps:**
1. On Step 1, set **What is your primary billing cycle?** to **Weekly** (the most common cycle in the regression catalog).
2. Note the visible questions.
3. Change to **Monthly** and confirm the same hidden behaviour applies (Monthly is still a valid choice for the **Basic Monthly $29.99** product).
4. Change to **Custom**.

**Expected Result:**
- With **Weekly** or **Monthly** selected: the **Every how many billing periods?** number field and the **Which period should that custom interval use?** select are hidden.
- With **Custom** selected: both fields become visible.
- The number field accepts values 1–365 (try entering `0` and confirm it is rejected; try `366` and confirm it is rejected).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Skip-related questions only when skip is enabled
**Steps:**
1. Discard, relaunch. Pick **Other / Custom** on Step 1.
2. Advance to Step 2.
3. With both **Allow skipping the next renewal** and **Allow pausing the subscription** unticked, observe the visible questions.
4. Tick **Allow skipping the next renewal**.

**Expected Result:**
- When skip is unticked: **Maximum consecutive skips allowed** and **How many days before renewal can a skip still be requested?** are hidden.
- When skip is ticked: both selects become visible with their default options (1 / 2 / 3 / 5 for max skips; Any time / 2 / 5 / 7 for window).
- Unticking skip again hides the two questions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Pause-related questions only when pause is enabled
**Steps:**
1. Stay on Step 2.
2. With pause unticked, observe.
3. Tick **Allow pausing the subscription**.

**Expected Result:**
- **Maximum pause duration** and **Maximum pauses per subscription** are hidden when pause is unticked.
- Both selects become visible when pause is ticked.
- Pause-duration options are **14**, **30**, **60**, **90 days**.
- Pauses-per-subscription options are **1**, **2**, **3**, **5**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Plan-switching questions only with multiple plans
**Steps:**
1. Discard, relaunch.
2. On Step 1 set **How many subscription plans do you offer?** to **One plan**.
3. Skip with defaults to Step 4 (Customer Portal & Self-Service).
4. Inspect the visible questions.
5. Go back to Step 1, change to **Multiple plans / tiers**, advance to Step 4 again.

**Expected Result:**
- With **One plan**: **Should customers be able to switch plans from the portal?** and the proration question are hidden on Step 4.
- With **Multiple plans / tiers**: both questions appear; the plan-switching radio shows options **Allow all switching directions** / **Only upgrades and downgrades** / **Only upgrades** / **Disable plan switching**.
- The proration question is hidden when switching is set to **Disable plan switching**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Retention-offer follow-ups only when offers are enabled
**Steps:**
1. Discard, relaunch. Pick any business type on Step 1, advance to Step 5.
2. Set **Show retention offers during the cancellation flow?** to **No**.
3. Now set it to **Yes**.
4. Tick the **Discount offer** checkbox.

**Expected Result:**
- When offers are **No**: the offer-checkbox group is hidden.
- When offers are **Yes**: the four offer checkboxes appear (**Discount offer**, **Pause offer**, **Downgrade offer**, **Contact support**).
- When **Discount offer** is ticked: **Retention discount percentage** (10 / 20 / 30 / 50%) and **How many billing cycles should that discount last?** (1 / 2 / 3 / 6) become visible.
- Unticking the discount offer hides those two follow-ups.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Auto-migrate question only when one-per-customer is selected
**Steps:**
1. Discard, relaunch.
2. On Step 3, set **Can customers have multiple active subscriptions?** to **Allow multiple subscriptions**.
3. Observe the surrounding questions.
4. Change to **Only one subscription per customer**.

**Expected Result:**
- With **Allow multiple subscriptions**: the **Should checkout auto-migrate an existing subscription?** question is hidden.
- With **Only one subscription per customer**: the auto-migrate radio appears with options **Yes — automatically replace the old subscription** and **No — block checkout until they cancel first**.
- The **Allow subscription and non-subscription products in the same cart?** question is hidden when one-per-customer is selected.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Changing earlier answers re-evaluates conditionals on later steps
**Steps:**
1. Discard, relaunch. Pick **Other / Custom**, set trials = **Yes**, plan count = **Multiple plans / tiers**.
2. Advance to Step 9 (Review).
3. Click **Edit** next to Step 1 and change trials to **No** and plan count to **One plan**.
4. Click **Next** to advance back to Step 9.

**Expected Result:**
- The trial-payment and trial-limit answers from Step 1 are removed from the review (they are no longer asked).
- The plan-switching answer from Step 4 is removed from the review (it is no longer asked).
- Per the manual: "Previously answered conditional questions that are now hidden are not sent to the server."

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- DevTools Console must stay clean while toggling each conditional — no JS errors when fields appear or disappear.
- Tab order should remain logical when fields hide and show; tab through Step 1 and confirm focus does not jump into hidden fields.
- After each scenario, click **Discard wizard** to avoid bleeding answers into the next sub-task.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
