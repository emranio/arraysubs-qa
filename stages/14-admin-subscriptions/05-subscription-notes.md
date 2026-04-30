# Stage 14 — Task 05: Subscription Notes

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — Subscription Notes panel |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 14-04 (a subscription with system events on file) |

## Objective
Verify the **Subscription Notes** panel at the bottom of the detail page mixes manual admin notes with system-generated notes (e.g. "Subscription created", "Renewal invoice generated", "Status changed to On Hold"). Add a private admin note and a customer-visible admin note, confirm both appear at the top of the chronological list, and confirm system notes interleave correctly when a new event fires.

## Pre-conditions
- Logged in as Administrator.
- Use a subscription that has at least these system notes already on file: a "Subscription created" note, a status-change note, and a "Renewal invoice created" note (use a subscription from Stage 06 that has progressed through one renewal).
- Optionally: a customer test account is available for verifying the customer-visible note in My Account.

## Test Data
- Private note text: `Updated invoice email per support ticket #5432.`
- Customer-visible note text: `Thanks for renewing — we've shipped your kit today.`

## Sub-Tasks

### Sub-Task 5.1 — Inspect existing system notes
**Steps:**
1. Open the target subscription's detail page.
2. Scroll to the **Subscription Notes** panel at the bottom.
3. Read each note from top to bottom.

**Expected Result:**
- Notes are sorted newest first.
- Each note shows: content, an author badge (System / Admin / Customer / Gateway), date, and a Private/Customer type indicator.
- At minimum these system notes are present: a "Subscription created"-type entry, a status-change entry, and a "Renewal invoice created"-type entry.
- System notes carry the **System** badge with a bot icon.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Add a private admin note
**Steps:**
1. In the **Add Note** form below the list, click into the rich text editor.
2. Type `Updated invoice email per support ticket #5432.`
3. Use the editor's **Bold** button on the words `support ticket #5432`.
4. Set the type selector to **Private**.
5. Click **Add Note**.

**Expected Result:**
- The note appears at the very top of the list immediately after submission.
- The author badge is **Admin** (shield icon).
- The type indicator reads **Private** (lock icon).
- The bolded portion renders as bold.
- A timestamp matches the current time.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Add a customer-visible note
**Steps:**
1. Click into the editor again.
2. Type `Thanks for renewing — we've shipped your kit today.`
3. Set the type selector to **Customer**.
4. Click **Add Note**.

**Expected Result:**
- The new note becomes the topmost note (newest first).
- Type indicator reads **Customer** (user icon).
- The previous Private note moves to position 2.
- Optional verification: log in as the test customer at `/my-account/` and confirm the Customer-typed note is visible in their subscription view; the Private note must NOT be visible there.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Trigger a system note and confirm interleaving
**Steps:**
1. Open the **Edit** screen for the same subscription.
2. Change a small editable field (e.g. update the billing City to a new value).
3. Save and return to the detail page.
4. Reload the page and re-inspect the notes panel.

**Expected Result:**
- A new System-badged note has been inserted at the top of the list documenting the admin edit (e.g. structured change "Billing City changed from X to Y").
- The two manual admin notes from sub-tasks 5.2 and 5.3 are now positions 2 and 3.
- All older system notes remain in their original chronological positions.
- The list is still sorted strictly newest-first.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Delete a manual admin note
**Steps:**
1. Locate the Private note added in 5.2.
2. Click the delete icon on that note.
3. Confirm the deletion if prompted.

**Expected Result:**
- The note disappears from the list immediately.
- No undo is offered (per the manual: "no undo").
- The Customer note and the system note from 5.4 are still present.
- DevTools Network tab shows a successful 2xx delete request.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — System note authoring is locked
**Steps:**
1. Hover any system-generated note.
2. Try to find an "edit" action.

**Expected Result:**
- System notes cannot be edited (per FAQ: system notes are not editable).
- A delete icon may be available, but doing so removes part of the audit trail.
- Do not delete the system note in this step.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-open the subscription in another browser tab — the list reflects the same ordering and counts.
- Open a brand-new subscription that has no events yet — confirm the panel shows whatever empty-state message the manual describes (or simply an empty list with the Add Note form available).
- Confirm zero JavaScript console errors while typing in the rich text editor.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID used:
- Notes:
