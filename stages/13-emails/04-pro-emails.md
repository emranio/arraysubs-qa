# Stage 13 — Task 04: Pro Emails — Card Expiring + SCA Authentication Required

| Key | Value |
|---|---|
| Stage | 13 — Email Notifications |
| Module | Emails — Pro (Card Expiring, SCA) |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 13.01 |

## Objective
Verify the two Pro renewal-related customer emails — **Card Expiring Notice** and **SCA Authentication Required**. Both emails are Stripe-driven (PayPal and Paddle are out of scope this cycle). Use Stripe test card `4000 0000 0000 0341` to fail a renewal, `4000 0027 6000 3184` to trigger the SCA / 3D Secure flow, and a card whose expiry is set to next month to confirm the Card Expiring email schedules and fires. Per the user manual, these emails may be flagged as "templates exist but trigger not yet wired in current release" — note the observed behaviour clearly so the QA team understands current vs planned status.

## Pre-conditions
- 13.01 complete.
- Stripe is already configured in test mode (publishable + secret keys present). No setup required by this task.
- The canonical `Standard Weekly` ($19.99/week) subscription product and a test customer (`cust1@test.local`) ready.
- Mailbox capture for `cust1@test.local`.
- Time-travel method available.

## Test Data
- Stripe test cards:
  - `4000 0000 0000 0341` — fails on renewal (declines on next charge attempt).
  - `4000 0027 6000 3184` — requires SCA / 3D Secure.
- Card-expiring scenario:
  - Use a Stripe test card whose expiry is set to `MM/YY` corresponding to the month after today (e.g., if today is 2026-04-29, set the saved card's expiry to `05/26`).

## Sub-Tasks

### Sub-Task 4.1 — Confirm template registration / wiring status
**Steps:**
1. **WC → Settings → Emails**.
2. Look for the entries:
   - `Card Expiring Notice`
   - `SCA Authentication Required`

**Expected Result:**
- Either the entries are present and **Enabled** (means trigger is wired) — proceed with rest of task.
- OR the entries are missing — meaning the emails are documented as not-yet-wired (per the user manual). In that case mark sub-tasks 4.3 / 4.4 / 4.5 as **PASS — not wired in current release** and note in Sign-off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Customer subscribes with the SCA test card
**Steps:**
1. As `cust1@test.local`, checkout `Standard Weekly` paying with Stripe test card `4000 0027 6000 3184`.
2. Complete the SCA / 3D Secure challenge that appears at checkout.
3. Confirm the subscription activates.

**Expected Result:**
- Initial purchase completes after the SCA challenge.
- A subscription is **Active** for `cust1`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Trigger SCA-required renewal
**Steps:**
1. Time-travel `cust1`'s next renewal date so the renewal runs.
2. The renewal attempts to charge the saved card. The SCA test card requires the customer to authenticate, so the gateway will return an `authentication_required` decline.
3. Inspect `cust1@test.local`'s inbox.

**Expected Result (if wired):**
- Subject contains `Authentication required` or `Action required` referencing the renewal.
- Body shows the `auth_url` link to complete authentication, payment amount, and a deadline.
- `{auth_url}`, `{payment_amount}`, `{auth_deadline}` rendered.

**Expected Result (if not wired):**
- No SCA email arrives.
- Instead, the customer Payment Failed email may fire with reason `the bank requires customer authentication (3D Secure)`.
- Note this and mark the SCA sub-tasks accordingly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Trigger Card Expiring scheduling
**Steps:**
1. Update `cust1`'s saved Stripe payment method so the card expiry is `next month / current year` (use the **Update payment method** flow on the customer portal or admin tools).
2. Check whether the plugin schedules a Card Expiring email. The expected scheduling date is typically 1–2 weeks before the expiry.
3. If the email is scheduled, time-travel to that date OR run the action-scheduler hook for card-expiring (`arraysubs_card_expiring_check` or similar) directly.
4. Inspect inbox.

**Expected Result (if wired):**
- A **Card Expiring Notice** email arrives with subject containing `Card expiring soon`.
- Body shows last 4 digits, expiry month/year, and an Update Payment link.
- `{card_last4}`, `{card_expiry}`, `{update_payment_url}` rendered.

**Expected Result (if not wired):**
- No Card Expiring email arrives (templates exist but trigger not yet wired per docs).
- Note the observation in Sign-off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Failed-renewal Payment Failed reason callout
**Steps:**
1. As `cust1`, switch the saved payment method to `4000 0000 0000 0341`.
2. Time-travel and trigger the next renewal.
3. Inspect `cust1`'s inbox.

**Expected Result:**
- The customer Payment Failed email arrives.
- The reason callout matches one of the documented categories: `card_declined`, `generic_decline`, `processing_error`, etc. Document the exact wording.
- The subscription's `_last_payment_failure_category` meta is set in the admin (verifiable via Custom Fields panel on the subscription).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Resolve the SCA renewal manually
**Steps:**
1. As `cust1`, follow the link in either the SCA email (if wired) or the Payment Failed email's Pay Now button.
2. Complete the authentication.

**Expected Result:**
- The renewal order is paid in full.
- The customer Payment Successful email fires.
- The subscription remains Active and the next payment date advances.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Disable / re-enable Pro emails (only if registered)
**Steps:**
1. If Card Expiring and SCA Authentication Required are present in WC email settings, disable each in turn, trigger their event, confirm no email arrives.
2. Re-enable them.

**Expected Result:**
- Standard per-email toggle behaviour.
- If the emails are not yet wired and not registered, mark this sub-task `N/A`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- DevTools console / `wp-content/debug.log`: zero new errors during the SCA / renewal flows.
- Confirm Stripe test mode is still ON and a real production secret key was not used.
- PayPal and Paddle integrations are out of scope this cycle — do not test or document either gateway in this task.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Card Expiring email observed: yes / no / not wired
- SCA email observed: yes / no / not wired
- Notes:
