# Stage 08 — Task 05: Contact Support Offer

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Contact Support Offer |
| Plugin Coverage | Free |
| Estimated Time | 12 min |
| Depends On | 01 — Cancellation reasons setup |

## Objective
Configure the Contact Support offer with a Support Hub URL. Trigger cancellation with an eligible reason. Click the Contact Support button on the offer card and verify the URL opens in a new browser tab, the acceptance is logged in the offer history / Retention analytics, and the subscription remains active.

## Pre-conditions
- 01 — Cancellation reasons setup PASSED.
- Subscription R4 — Active Basic Monthly belonging to `cust1@example.com`.
- Working public URL to use as the Support Hub: `https://example.com/support` (or your real support page).

## Test Data
- Support Hub URL: `https://example.com/support`
- Trigger reasons: `Technical issues`, `Missing features I need`
- Optional Button Text: `Talk to support`

## Sub-Tasks

### Sub-Task 05.1 — Configure the Contact Support offer
**Steps:**
1. As Administrator, open **ArraySubs → Retention Flow**.
2. In the Retention Offers section, find **Contact Support Offer** and set:
   - **Enable Contact Support Offer:** ON
   - **Show for these reasons:** select `technical_issues` and `missing_features`
   - **Support Hub URL:** `https://example.com/support`
   - **Button Text:** `Talk to support` (optional)
   - Leave Custom Headline / Description blank.
3. Click **Save Changes**.

**Expected Result:**
- A success toast confirms the save.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Negative case: empty URL prevents offer
**Steps:**
1. Temporarily clear the Support Hub URL field.
2. Save.
3. As `cust1@example.com`, on a spare subscription, trigger a cancellation with reason **Technical issues**.

**Expected Result:**
- The Contact Support card does **not** appear in the retention modal because the URL is required when the offer is enabled.
- Restore the URL to `https://example.com/support` and save again before continuing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Trigger cancellation with matching reason
**Steps:**
1. As `cust1@example.com`, open Subscription R4's detail page.
2. Click **Cancel Subscription**.
3. Select **Technical issues**.
4. Click **Continue**.

**Expected Result:**
- The **"Before You Go..."** modal opens.
- A Contact Support card is shown.
- The card displays the configured button text **"Talk to support"** (or default if not customised).
- Two bottom buttons: **Keep Subscription** and **No thanks, continue to cancel**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Click the support button
**Steps:**
1. Click **Talk to support** on the offer card.

**Expected Result:**
- A new browser tab opens to `https://example.com/support` (verify the host and path match).
- The original tab returns the customer to the subscription detail page (or closes the modal).
- The subscription remains **Active** — no cancellation, no pause, no other side effects.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Verify acceptance was logged
**Steps:**
1. As Administrator, open **WooCommerce → Analytics → Retention** and inspect the Activity Log filtered by Subscription R4.

**Expected Result:**
- An entry of type "offer shown" exists for the contact support offer.
- An entry of type "offer accepted" exists for the contact support offer with the timestamp matching the click.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Subscription state unchanged
**Steps:**
1. Refresh Subscription R4's detail page.

**Expected Result:**
- Status is still **Active**.
- No pending cancellation flag.
- No status changes; recurring amount unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Try the same flow with reason **Too expensive** — the Contact Support card should NOT appear (only matching reasons trigger it).
- Confirm a subscription note records the offered + accepted contact-support event with the timestamp.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
