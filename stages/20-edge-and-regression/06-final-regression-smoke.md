# Stage 20 — Task 06: Final Regression Smoke Pass

| Key | Value |
|---|---|
| Stage | 20 — Edge Cases & Final Regression |
| Module | Smoke Test |
| Plugin Coverage | Both |
| Estimated Time | 30 min |
| Depends On | All prior tasks across stages 00–20 |

## Objective
A 30-minute end-to-end smoke pass that exercises the simplest happy path through the entire ArraySubs / ArraySubsPro feature set, AFTER all the heavy regression testing of stages 00–19 and 20.01–20.05. The point is to prove that none of the previous tests left the system in a corrupted state, and that the most common, day-to-day customer + admin journey still works flawlessly. This is the gate task: if 06 passes, the release ships.

The smoke pass exercises: setup wizard reachable, one product creation, one purchase, one renewal, one cancellation, one refund, and one plan switch.

## Pre-conditions
- All Stage 19 and Stage 20.01–05 tasks complete.
- ArraySubs and ArraySubsPro both active.
- Stripe sandbox (or whichever gateway you used throughout) is still configured and reachable.
- A fresh test customer email available (e.g. `smoke@example.com`) so this run does not collide with prior test data.
- Admin browser and customer browser ready, with a mail-trap / inbox to read emails.

## Test Data
- New product to create: name `Smoke Plan`, price `$10/month`, no trial.
- Plan-switch target product: must exist (e.g. `Smoke Plan Plus` at `$20/month` linked to `Smoke Plan` via upgrade group). If not pre-existing, create as part of step 2.
- Test card: Stripe `4242 4242 4242 4242`, any future expiry, any CVC.

## Smoke Checklist

This task uses a numbered checklist instead of formal sub-tasks. Tick each box as the step completes.

### A. Setup Wizard Reachable
- [ ] Step A1: As admin, navigate to **ArraySubs → Setup Wizard** (or the wizard URL). Confirm the page loads with no PHP errors. (We do not re-run the wizard — just confirm it is reachable as a sanity check.)

### B. Product Creation
- [ ] Step B1: Go to **Products → Add New**. Title: `Smoke Plan`. Type: Simple subscription. Price: `$10`. Period: `month`. Save / Publish.
- [ ] Step B2: Confirm product visible at the public shop URL with correct price and "every month" wording.
- [ ] Step B3: Confirm the linked product `Smoke Plan Plus` exists at `$20/month` and is linked to `Smoke Plan` via upgrade group (create if missing).

### C. Purchase (One Customer)
- [ ] Step C1: As `smoke@example.com`, add `Smoke Plan` to cart and check out using the test card.
- [ ] Step C2: Place order; confirm order completion screen renders.
- [ ] Step C3: Confirm in admin that a new subscription was created with status **Active**, parent order Completed, next payment date one month from today.
- [ ] Step C4: Confirm `smoke@example.com` received: Order Confirmation email, New Subscription email.

### D. Renewal
- [ ] Step D1: As admin, edit the subscription's **Next Payment Date** to be ~5 minutes from now (so we can trigger a renewal quickly).
- [ ] Step D2: Wait for or manually trigger **Generate Upcoming Renewals** in **Tools → Scheduled Actions**.
- [ ] Step D3: Confirm a pending renewal order is created on Stripe and a Renewal Invoice email is sent.
- [ ] Step D4: Wait for or manually run the per-subscription renewal-payment action so the gateway charges the saved card.
- [ ] Step D5: Confirm the renewal order moves to Completed/Processing, subscription's completed-payments counter increments to 2, next payment date advances by another month, and a Payment Successful email is sent.

### E. Cancellation
- [ ] Step E1: As `smoke@example.com`, go to `/my-account/subscriptions/`, click into the subscription, click **Cancel Subscription**.
- [ ] Step E2: Pick reason `Just need a temporary break`. Confirm.
- [ ] Step E3: Depending on the **Cancel Immediately** setting at this point in the test run, the subscription is either Cancelled now or scheduled for end-of-period. Record which.
- [ ] Step E4: Confirm the customer Subscription Cancelled email arrived (or scheduled-cancellation note appears, if EOP).
- [ ] Step E5: As admin, reactivate the subscription via the status dropdown so we have something to refund/switch in the next steps. Confirm Subscription Reactivated email is sent.

### F. Refund
- [ ] Step F1: With **ArraySubs → Settings → Refunds → Refund on Cancellation = Allow Immediate Refund**, **Auto Gateway Refund Enabled**, open the most recent paid order for this subscription in WooCommerce.
- [ ] Step F2: Click **Refund**, refund the full order amount (`$10.00`), reason `Smoke test refund`.
- [ ] Step F3: Confirm Stripe shows the refund.
- [ ] Step F4: Confirm the subscription auto-cancels and a Subscription Cancelled email is sent.
- [ ] Step F5: Reactivate the subscription again from admin status dropdown (we need it Active for step G).

### G. Plan Switch
- [ ] Step G1: As `smoke@example.com`, click into the subscription and choose **Switch Plan**.
- [ ] Step G2: Pick `Smoke Plan Plus` and choose **Apply Immediately** (or whichever switch type the plugin defaults to that issues a switch right now).
- [ ] Step G3: Pay any prorated charge with the saved card.
- [ ] Step G4: Confirm the subscription's product / price now reflects `Smoke Plan Plus` at `$20/month`. Confirm a Plan Switched email or order note documents the change.

### H. End-of-test Verification
- [ ] Step H1: Open the Subscriptions list and confirm the smoke subscription is present, status Active, on `Smoke Plan Plus`, with the renewal counter and next payment date all sensible.
- [ ] Step H2: Open `wp-content/debug.log` and confirm no PHP fatal/notice/warning was logged from ArraySubs during the entire smoke run.
- [ ] Step H3: Inspect **Tools → Scheduled Actions → Failed** tab. Confirm no `arraysubs_*` actions in Failed for the smoke window.
- [ ] Step H4: Open the customer's `smoke@example.com` mailbox and review the email sequence. Confirm: Order Confirmation, New Subscription, Renewal Invoice, Payment Successful, Subscription Cancelled, Subscription Reactivated (×2), Subscription Cancelled (after refund), Subscription Reactivated, Plan Switched. Order may vary depending on test run; the point is that emails are arriving and not silently dropped.

## Pass Criteria

This entire task is a single PASS / FAIL gate.

**Pass Criteria:** [ ] PASS [ ] FAIL

The release passes only if every checkbox above is ticked AND the H-block confirms no errors. If any single step fails, the release does not ship until the cause is identified.

## Regression / Cross-checks
- After the smoke test passes, archive the QA run summary (link this file's checklist plus all Stage 00–20 sign-offs) in the project's QA log / release notes.
- Note any flaky behaviour observed during the run, even if the boxes were ticked — flakiness is a smell that warrants investigation before release.
- Do **not** clean up the smoke test data immediately. Keep it for post-release diagnosis if a regression is reported in the wild.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Overall release verdict (PASS / FAIL):
- Notes:
