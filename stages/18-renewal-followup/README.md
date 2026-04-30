# Stage 18 — Renewal Follow-up & Time-travel

**Goal:** Validate the entire post-checkout billing engine — successful manual and automatic renewals, payment failures and the active grace period, the On-Hold transition, automatic cancellation for overdue payment, trial conversion (and auto-downgrade), different-renewal-price thresholds, skip and pause renewal interactions, renewal reminders, and fixed-end-date expiration. Each task uses a documented **time-travel** technique to fast-forward subscription dates and trigger Action Scheduler hooks on demand. The new canonical test catalog is dominated by **weekly** subscriptions (with a single Basic Monthly $29.99 outlier), which makes most renewals feasible to exercise in real wall-clock time AND makes time-travel scenarios much faster to set up. Every step is performed by a human tester clicking through `wp-admin`, the customer portal, the WooCommerce checkout, and a real test mail-catcher. Each task ends with falsifiable on-screen results (status changes, email arrivals, scheduled-action execution rows, audit entries).

**Prerequisites:**
- Stages 00–02 complete (plugins active, settings configured, environment verified).
- Stage 03 catalog with the new canonical product list:
  - **Basic Monthly** — every 1 month, $29.99 (the ONLY monthly product in this regression cycle).
  - **Standard Weekly** — every 1 week, $19.99 (used as the primary renewal-test subscription).
  - **Trial Weekly** — every 1 week, $19.99, **7-day free trial**, no auto-downgrade.
  - **Trial Weekly w/ Downgrade** — every 1 week, $19.99, 7-day free trial, **auto-downgrade target = Basic Plan (or any free product)**.
  - **Stepped Weekly** — every 1 week, $19.99 → $29.99 after 3 cycles (different renewal price configured).
  - **Lifetime Deal** — Lifetime, $199 (no renewals).
  - **Fixed-Length Weekly (6 cycles)** — every 1 week, $24.99, **subscription length = 6** (expires after 6 paid renewals).
  - **Limited 3-Cycle Weekly** — every 1 week, $19.99, **subscription length = 3** (used for Expiring Soon test).
  - **PM Tool** — Variable: Weekly $9.99 / Bi-weekly $14.99.
  - **Coffee Plan** — Variable: Daily $2.99 / Weekly $14.99 (+ $4.99 fee) / Bi-weekly $24.99.
  - **Basic Plan / Pro Plan / Enterprise Plan / Crossgrade Plus** — every 1 week, $9.99 / $19.99 / $39.99 / $19.99.
  - **Fixed-Period Plan** *(Pro)* — simple subscription with **Fixed Period Membership** end date (configurable per subscription).
- Stages 05/06 used to seed test customers:
  - `member1@example.com` — Active **Standard Weekly** subscription paid manually (BACS or cheque) for manual-renewal tests.
  - `member-stripe@example.com` — Active **Standard Weekly** subscription paid by saved Stripe card `4242 4242 4242 4242` for automatic-renewal tests.
  - `member-decline@example.com` — Active **Standard Weekly** subscription paid by Stripe card `4000 0000 0000 0341` (decline-on-renewal) for grace-period tests.
  - `member-trial@example.com` — Active **Trial** subscription on Trial Weekly (7-day trial, no auto-downgrade).
  - `member-trial-down@example.com` — Active **Trial** subscription on Trial Weekly w/ Downgrade.
  - `member-stepped@example.com` — Active subscription on Stepped Weekly (different renewal price after 3 cycles).
  - `member-limited@example.com` — Active subscription on Limited 3-Cycle Weekly (used for Expiring Soon).
  - `member-fixed@example.com` *(Pro)* — Active subscription on Fixed-Period Plan.
- ArraySubs **Pro** active for tasks marked *(Pro)*. Stage 17 reused the same test environment — confirm Stage 17 cleanup completed.
- Mail-catcher available (e.g., MailHog, WP Mail Logger, Mailpit) so the tester can read every transactional email by inbox.
- Action Scheduler reachable at **WooCommerce → Status → Scheduled Actions** with the ability to manually click **Run** on queued actions.
- **Stripe is already configured** in Test mode with webhook endpoint registered — no setup required. Only **Stripe + manual** payments are tested; PayPal and Paddle are out of scope.
- Stripe test cards in scope:
  - `4242 4242 4242 4242` — success
  - `4000 0027 6000 3184` — SCA / 3-D Secure
  - `4000 0000 0000 0341` — declines on renewal
- Settings → General Settings:
  - Active Grace Days = **3** (default)
  - On-Hold Grace Days = **7** (default)
  - Renewal Reminder Days Before = **1** for weekly subscriptions (or **3** if using a longer cadence — see task 18.11 for which the manual permits)
  - Invoice Timing = **6 hours** (default)
- Default browsers: Chrome (primary), Firefox (secondary). Use private/incognito windows for customer logins so the admin session stays intact.

**Run order:**
1. [01 — Time-travel method](01-time-travel-method.md) — Document the chosen time-travel approach (admin edit `_next_payment_date` + manual Action Scheduler run). Verify by editing one **Standard Weekly** subscription to one hour ago, running `arraysubs_generate_upcoming_renewals`, and confirming a pending renewal order is created.
2. [02 — Successful manual renewal](02-successful-manual-renewal.md) — Manual renewal path on **Standard Weekly $19.99/wk** (BACS). Time-travel `member1` to the next payment date. Verify renewal-invoice email, customer pays via BACS, Payment Successful email arrives, completed-payments counter increments, next-payment-date advances by one **week**.
3. [03 — Successful automatic renewal](03-successful-automatic-renewal-pro.md) — *(Pro)* Stripe automatic renewal on **Standard Weekly $19.99/wk** with `4242` card. Time-travel `member-stripe` to the next payment date. Stripe charges the saved card automatically, order moves to Processing/Completed, counter increments, Payment Successful email sent.
4. [04 — Failed renewal in active grace](04-failed-renewal-grace-active.md) — *(Pro)* `member-decline` on **Standard Weekly** with declining card `4000 0000 0000 0341`. Time-travel to renewal. Automatic charge fails, Payment Failed email sent, subscription stays Active for 3 grace days. Customer pays via the renewal invoice link to recover.
5. [05 — Grace → On-Hold transition](05-grace-to-on-hold-transition.md) — Same **Standard Weekly** subscription. Time-travel further past the 3-day active grace. Hourly checker moves the subscription to On-Hold; Subscription On-Hold email sent; restricted access; payment during on-hold restores Active.
6. [06 — On-Hold → Cancelled transition](06-on-hold-to-cancelled-transition.md) — Same **Standard Weekly** subscription. Time-travel past the 7-day on-hold grace. System auto-cancels with reason `overdue_payment`, sends Subscription Cancelled email, removes future scheduled actions.
7. [07 — Trial → Active conversion](07-trial-to-active-conversion.md) — `member-trial` on **Trial Weekly** (7-day trial, no auto-downgrade). Time-travel past trial end. Trigger Process Trial Conversions (daily 2:00 AM job). Status → Active, next payment date recalculated using the **weekly** billing period, Trial Converted email sent.
8. [08 — Trial auto-downgrade](08-trial-auto-downgrade.md) — `member-trial-down` on **Trial Weekly w/ Downgrade** (auto-downgrade target = Basic Plan or another free product). Time-travel past trial end. Subscription switches to the downgrade target, Auto Downgrade email sent (no Trial Converted email).
9. [09 — Different renewal price threshold](09-different-renewal-price-threshold.md) — `member-stepped` on **Stepped Weekly** ($19.99/wk × 3 cycles, then $29.99/wk). Time-travel through 6 successful weekly renewals. Renewals 1–3 charge $19.99, renewals 4–6 charge $29.99. Zero-total renewals do NOT increment the counter.
10. [10 — Skip and pause over renewal cycles](10-skip-and-pause-over-renewal-cycles.md) — `member-stripe` on **Standard Weekly** skips 2 cycles → no invoice generated for skipped dates → invoice generated 2 weeks later at the new shifted date. Then pauses 14 days → time-travel past pause end → auto-resume restores Active and recalculates next-payment-date forward by 14 days.
11. [11 — Renewal reminders & expiring soon](11-renewal-reminders-and-expiring-soon.md) — Configure reminder = **1 day before** (since interval is weekly; 3 days before is also acceptable per the user manual — pick whichever you can reliably exercise). Time-travel a Standard Weekly subscription past the reminder threshold and verify the reminder fires once. With **Limited 3-Cycle Weekly** (subscription length = 3) and ≥2 completed renewals, time-travel near the third (final) renewal → Subscription Expiring Soon email.
12. [12 — Fixed-period expiration](12-fixed-end-date-expiration-pro.md) — *(Pro)* `member-fixed` with Fixed Period Membership end date set to today + 14 days. Time-travel past the end date. Subscription expires (Expired status), Subscription Expired email sent, future actions unscheduled.

**Exit criteria:**
- All 12 task files signed PASS, or failures recorded with screenshots, browser version, and reproduction steps.
- The exact UI strings recorded verbatim during testing match the user manual:
  - Subscription statuses: `Active`, `Trial`, `On Hold`, `Cancelled`, `Expired`.
  - Cancellation reason for system-cancelled subscriptions: `overdue_payment`.
  - Customer emails captured in order: **New Subscription**, **Trial Started**, **Trial Converted** (or **Auto Downgrade**), **Renewal Reminder**, **Renewal Invoice**, **Renewal Payment Failed**, **Payment Successful**, **Subscription On-Hold**, **Subscription Cancelled**, **Subscription Expired**, **Subscription Expiring Soon**.
- Each Action Scheduler hook is observed in **WooCommerce → Status → Scheduled Actions** with the documented hook name. Verify at least these hook names fire during this stage: `arraysubs_generate_upcoming_renewals`, `arraysubs_process_renewal`, `arraysubs_check_overdue_renewals`, `arraysubs_hold_subscription`, `arraysubs_cancel_subscription`, `arraysubs_process_trial_conversions`, `arraysubs_process_trial_conversion`, `arraysubs_resume_subscription`, `arraysubs_send_renewal_reminder`, `arraysubs_send_expiring_soon`, `arraysubs_expire_subscription`, `arraysubs_process_skipped_cycle`.
- Scheduled-Job Logs (Stage 17 / Task 17.03) shows a Success row for each fired hook with the correct human-readable label.
- Time-travel approach documented in `01-time-travel-method.md` is reused unchanged by every other task in this stage. If the tester deviates, they must record the alternative approach in the Sign-off block of that task.
- All test subscriptions returned to a clean state (or explicitly noted as "left in End State X for downstream stages") at the end of the stage.
