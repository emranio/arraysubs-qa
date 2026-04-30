# Stage 08 — Task 04: Downgrade Offer

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Downgrade Offer |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 01 — Cancellation reasons setup, Stage 03 Task 10 (linked downgrade products configured) |

## Objective
Configure a downgrade offer (which requires linked downgrade products to function). Cancel with an eligible reason and verify the offer card. Accepting the offer must redirect the customer into the standard plan-switching flow with downgrade options listed.

## Pre-conditions
- 01 — Cancellation reasons setup PASSED.
- Stage 03 Task 10: Pro Monthly product has Basic Monthly linked as a **downgrade** product.
- Subscription R3 — Active Pro Monthly belonging to `cust1@example.com` (so it has a downgrade target available).
- Trigger reasons used here: `too_expensive` and `missing_features`.

## Test Data
- Trigger reasons: select both `Too expensive` and `Missing features I need`
- Custom Description (optional): leave default

## Sub-Tasks

### Sub-Task 04.1 — Configure the downgrade offer
**Steps:**
1. As Administrator, open **ArraySubs → Retention Flow**.
2. In the Retention Offers section, find **Downgrade Offer** and set:
   - **Enable Downgrade Offer:** ON
   - **Show for these reasons:** select `too_expensive` and `missing_features`
   - Leave headline and description blank to use defaults.
3. Click **Save Changes**.

**Expected Result:**
- A success toast confirms the save.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Disable conflicting offers temporarily
**Steps:**
1. Temporarily disable the Discount Offer (so the discount card doesn't compete with the downgrade card on the same Too expensive reason — makes the test focused).
2. Save and refresh.

**Expected Result:**
- Discount Offer toggle is OFF; Pause Offer untouched.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Trigger cancellation
**Steps:**
1. As `cust1@example.com`, open Subscription R3's detail page (Pro Monthly).
2. Click **Cancel Subscription**.
3. Select **Too expensive**.
4. Click **Continue**.

**Expected Result:**
- The **"Before You Go..."** modal opens.
- A downgrade offer card is shown.
- The card displays the default downgrade headline / description text and an **Accept** (or "View options") button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Accept and enter plan switch flow
**Steps:**
1. Click **Accept** on the downgrade card.

**Expected Result:**
- The retention modal closes and the plan switching modal opens (or the customer is redirected to the plan switching flow).
- The available downgrade product (Basic Monthly) is listed with its pricing details.
- A **View Details** link is available for the downgrade product.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Complete the downgrade
**Steps:**
1. Select **Basic Monthly** as the downgrade target.
2. Review the proration preview.
3. Click **Confirm Plan Change**.
4. Complete checkout if a charge is required (use Stripe test card if applicable).

**Expected Result:**
- The plan switch completes successfully.
- Subscription R3 now reflects **Basic Monthly** with the lower recurring amount.
- The original cancellation does not proceed — subscription remains Active on the new lower plan.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Negative case: no downgrade products
**Steps:**
1. As Administrator, find a Pro product that has no downgrade products linked (or temporarily remove the linked downgrade from the product).
2. Subscribe `cust1@example.com` to that product (or use a known subscription that fits) — call it Subscription R3-neg.
3. Trigger the cancellation flow with Too expensive.

**Expected Result:**
- Even though Downgrade Offer is enabled and the reason matches, the downgrade card does **not** appear because there are no linked downgrade products to present.
- If the Discount and Pause cards are also off / not matching, the modal should fall back to showing no offers — at which point the cancellation proceeds straight through.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.7 — Re-enable Discount Offer for downstream tasks
**Steps:**
1. As Administrator, re-enable the Discount Offer (restoring 20% / 3 cycles for Too expensive).
2. Save.

**Expected Result:**
- Discount Offer toggle is ON again, ready for Task 06 eligibility checks.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the WooCommerce switch order (if any) was created and paid; the order note "Existing subscription updated from checkout migration" should be present.
- Confirm a Retention activity log entry of type "offer accepted" with offer type **downgrade** for Subscription R3.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
