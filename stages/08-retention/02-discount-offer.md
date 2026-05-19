# Stage 08 — Task 02: Discount Offer

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Discount Offer |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 01 — Cancellation reasons setup |

## Objective
Configure a 20% discount for 3 cycles targeted at the **Too expensive** reason. As a customer, start to cancel a subscription with that reason and verify the **"Before You Go..."** retention modal appears with the discount card. Accept it. Verify the subscription detail page now shows the original price struck through, the discounted recurring amount, and the cycles remaining.

## Pre-conditions
- 01 — Cancellation reasons setup PASSED.
- Subscription R1 — Active Basic Monthly belonging to `cust1@example.com`, paid via a manual or Stripe gateway (so discount renewal is fully supported per manual).
- The subscription must NOT have any prior accepted retention discount in its offer history.

## Test Data
- Discount Percentage: `20`
- Number of Billing Cycles: `3`
- Trigger reason: `Too expensive`
- Original Recurring Amount on Basic Monthly: `$29.99`
- Expected discounted amount: `$23.99`

## Sub-Tasks

### Sub-Task 02.1 — Configure the discount offer
**Steps:**
1. As Administrator, open **ArraySubs → Retention Flow**.
2. Toggle **Enable Retention Offers** ON if not already.
3. In the Retention Offers section, find **Discount Offer** and set:
   - **Enable Discount Offer:** ON
   - **Show for these reasons:** select only `too_expensive` (Too expensive)
   - **Discount Percentage:** `20`
   - **Number of Billing Cycles:** `3`
   - Leave Custom Headline and Custom Description blank.
4. Click **Save Changes**.

**Expected Result:**
- A success toast confirms the save.
- After refresh, the values persist.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Trigger cancellation with matching reason
**Steps:**
1. As `cust1@example.com`, open Subscription R1's detail page.
2. Click **Cancel Subscription**.
3. Select reason **Too expensive**.
4. Click **Continue**.

**Expected Result:**
- A second modal appears with heading exactly: **"Before You Go..."**.
- The intro line reads: **"We'd hate to see you leave! Here are some options that might help:"**.
- One offer card is shown — the discount card.
- The card displays the default headline **"Stay and Save!"** and the description: **"We'll give you 20% off for the next 3 billing cycles."**
- An **Accept** button (or equivalent) is on the card.
- Two bottom buttons: **Keep Subscription** and **No thanks, continue to cancel**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Accept the discount
**Steps:**
1. Click the **Accept** button on the discount card.

**Expected Result:**
- The retention modal closes.
- A success notice confirms the discount was applied (record exact wording).
- The subscription remains **Active**.
- Any pending cancellation (if EOP mode) is cleared.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Discount displayed on detail page
**Steps:**
1. Refresh Subscription R1's detail page.
2. Locate the Recurring Amount row.

**Expected Result:**
- The original recurring amount **$29.99** is displayed with strikethrough formatting.
- The new discounted amount **$23.99** is displayed prominently next to or beneath it.
- Below the amount, a line reads: **"Discounted from $29.99 for the next 3 renewal(s)."** (or equivalent indicating 3 cycles remaining).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Discount displayed in My Subscriptions list
**Steps:**
1. Navigate back to **My Account → Subscriptions**.
2. Find Subscription R1's row.

**Expected Result:**
- The Total column shows the discounted amount **$23.99** (not the base price), with the billing schedule on a second line.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Admin verification
**Steps:**
1. As Administrator, open Subscription R1.
2. Inspect the notes timeline and the offer history meta (if exposed) or the Activity Log.

**Expected Result:**
- A subscription note records the accepted discount offer with: 20%, 3 cycles, the trigger reason **Too expensive**.
- An entry exists in **WooCommerce → Analytics → Retention** activity logs for both "offer shown" and "offer accepted" events for Subscription R1.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Custom messaging override (optional but recommended)
**Steps:**
1. As Administrator, edit the Discount Offer settings:
   - Custom Headline: `Wait! Save 20% Now`
   - Custom Description: `Stay with us and we'll cut your bill by {percent}% for {cycles} cycles.`
2. Save.
3. As another customer with a different subscription, run the same flow with Too expensive.

**Expected Result:**
- The retention modal now displays the custom headline `Wait! Save 20% Now`.
- The description renders the placeholders as `Stay with us and we'll cut your bill by 20% for 3 cycles.`

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify the next renewal of Subscription R1 (when it processes) charges $23.99 — track this in Stage 09 if scheduled there, or simulate via "Process now" on the renewal Action Scheduler entry.
- Confirm a second discount offer cannot be applied to R1 — see Task 06 for the eligibility check.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
