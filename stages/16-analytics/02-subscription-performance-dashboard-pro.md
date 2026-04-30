# Stage 16 — Task 02: Subscription Performance Dashboard *(Pro)*

| Key | Value |
|---|---|
| Stage | Analytics & Reports |
| Module | WooCommerce → Analytics → Overview (Pro additions) |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | Stages 06–08 (data) and 14 (subscriptions); 16-01 |

## Objective
Open **WooCommerce → Analytics → Overview** with ArraySubs Pro active. Confirm the Subscription Performance Dashboard renders the **10 KPI cards** in a responsive grid above the standard WC dashboard. Reconcile each card's value with the actual subscriptions and orders created in earlier stages (Active Subscriptions count, MRR, New Subs, Churned Subs, Churn Rate, Trial Conversions, Renewal Revenue, Revenue at Risk, ARPU, Retention Saves). Per the user manual, **MRR is normalised across all billing intervals (daily / weekly / monthly / yearly) into a single monthly figure** — so the dashboard math must reconcile against weekly subscriptions (the majority of the test catalog) plus the single Basic Monthly subscription. Confirm period-over-period delta percentages render and that **Revenue at Risk** has no delta (snapshot metric per the manual).

## Pre-conditions
- Logged in as Administrator.
- ArraySubs Pro active.
- Stages 06–14 complete: at least one Active subscription, one Trial that has converted, one Cancelled subscription with a captured reason, one On Hold subscription (revenue at risk), one Renewal order processed, one Retention Offer accepted.
- WooCommerce Admin date-range picker is set to a period that includes the test data (e.g. **Last month** if data was created in the last 30 days).

## Test Data
- Expected counts (from earlier stages — fill in the actuals during execution):
  - Active subscriptions: `____`
  - New Subscriptions in period: `____`
  - Churned Subscriptions in period: `____`
  - Trials ended in period: `____`
  - Trial conversions: `____`
  - Renewal orders in period: `____`
  - On-hold subscriptions: `____`
  - Active/Trial subs with end-of-period cancellation pending: `____`

## Sub-Tasks

### Sub-Task 2.1 — Open the Overview page and locate the 10 KPI cards
**Steps:**
1. Navigate to **WooCommerce → Analytics → Overview** (or **WooCommerce → Analytics** and the Overview/Home tab).
2. Wait for the page to fully load.
3. Locate the Subscription Performance section above the standard WC dashboard sections.

**Expected Result:**
- 10 KPI cards render in a responsive grid.
- Each card shows: a metric title, the current value, a percentage delta vs. previous period, and a colour-coded trend indicator (green when good, red when bad — direction depends on metric).
- Cards do NOT overlap or wrap awkwardly on a 1280px viewport.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Verify each card title and format
**Steps:**
1. Read the title and value format of every card.

**Expected Result:**

| Card | Format |
|---|---|
| Active Subscriptions | Number |
| Monthly Recurring Revenue (MRR) | Currency |
| New Subscriptions | Number |
| Churned Subscriptions | Number |
| Churn Rate | Percent |
| Trial Conversions | Percent |
| Renewal Revenue | Currency |
| Revenue at Risk | Currency |
| Avg Revenue Per User (ARPU) | Currency |
| Retention Saves | Number |

- Currency format matches the WooCommerce store currency (symbol + decimals).
- Percentage cards display a `%` symbol.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Reconcile Active Subscriptions count
**Steps:**
1. Note the value in the **Active Subscriptions** card.
2. Open **ArraySubs → Subscriptions → Active** tab and note the count there.

**Expected Result:**
- The card value matches the Active tab count for subscriptions whose `_start_date` is on or before the period end (per the manual).
- If the period end is "today", and all your active subscriptions started before today, the two values are equal.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Reconcile MRR (normalised across daily / weekly / monthly intervals)
**Steps:**
1. Note the **MRR** card value.
2. Open the All-tab CSV export from task 14-07.
3. For each Active and Trial subscription in the export, normalize the recurring amount to monthly using:
   - Daily: `Amount × (30.44 / interval)`
   - Weekly: `Amount × (4.333 / interval)` (≈ 30.44 / 7)
   - Monthly: `Amount / interval`
   - Yearly: `Amount / (interval × 12)`
4. Sum.

**Expected Result:**
- Manual sum equals the dashboard MRR (within rounding to 2 decimals).
- If the dashboard MRR is materially higher, confirm Trial subscriptions are included (per the manual) — that's correct.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4a — Spot-check normalisation for a $19.99/wk subscription
**Steps:**
1. Pick one Active **Standard Weekly $19.99/wk** subscription from the Subscriptions list (or any $19.99 weekly subscription seeded by Stage 06).
2. Compute its expected MRR contribution: `$19.99 × 4.333 ≈ $86.62`.
3. From the dashboard MRR figure, mentally subtract the contributions of every other Active/Trial subscription (using the same per-interval formula in 2.4) and verify the residual matches `$86.62 ± $0.05`.

**Expected Result:**
- The $19.99/wk subscription contributes approximately **$86.62** to MRR.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4b — Spot-check the single Basic Monthly $29.99 contribution
**Steps:**
1. Locate the Active **Basic Monthly $29.99/mo** subscription (the only monthly product in the catalog).
2. Confirm its expected MRR contribution = `$29.99` (already monthly — no normalisation needed; `$29.99 / 1 = $29.99`).
3. Verify the residual after subtracting all weekly contributions from the dashboard MRR matches `$29.99 ± $0.01`.

**Expected Result:**
- Basic Monthly $29.99 contributes **$29.99** directly to MRR.
- Combined with weekly subscriptions, the dashboard MRR figure reconciles cleanly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Reconcile New / Churned / Churn Rate / Trial Conversions
**Steps:**
1. Note the **New Subscriptions** card.
2. Filter the All-tab CSV by Created Date within the selected period — count.
3. Note the **Churned Subscriptions** card.
4. Filter the CSV by Status = `Cancelled` and End Date within the selected period — count.
5. Note the **Churn Rate** card.
6. Compute manually: `Churned ÷ (Active at period start + New in period) × 100`.
7. Note the **Trial Conversions** card.
8. Compute manually: `Trials ended in period that are now Active ÷ Total trials ended × 100`.

**Expected Result:**
- New Subscriptions matches.
- Churned Subscriptions matches.
- Churn Rate matches manual calc within 1 percentage point.
- Trial Conversions matches manual calc.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Reconcile Renewal Revenue and ARPU
**Steps:**
1. Note the **Renewal Revenue** card.
2. Sum the totals of every WC order with type `Subs Renew` in the selected period (use task 16-04 once it's available, or query WC → Orders manually).
3. Note the **ARPU** card.
4. Compute manually: `MRR ÷ unique active customers in the period`.

**Expected Result:**
- Renewal Revenue matches the manual sum.
- ARPU matches the manual division within rounding.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Reconcile Revenue at Risk and Retention Saves
**Steps:**
1. Note the **Revenue at Risk** card. Confirm the delta (vs previous period) is shown as `0%` or no-delta — it is a real-time snapshot.
2. Manually sum the normalized monthly amounts of: every On-Hold subscription PLUS every Active/Trial subscription with `_waiting_cancellation = true`.
3. Note the **Retention Saves** card.
4. Count retention offers accepted in the selected period (visible in WC → Analytics → Retention activity log).

**Expected Result:**
- Revenue at Risk matches the manual sum.
- Revenue at Risk delta is `0%` (or omitted) — never a real period comparison.
- Retention Saves matches the count of accepted offers in period.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Card navigation
**Steps:**
1. Click the **Active Subscriptions** card — confirm it navigates to the Orders report filtered by Subs Purchase + Subs Trial.
2. Click the **MRR** card — confirm it navigates to the Revenue report.
3. Click the **Churn Rate** card — confirm it navigates to Retention Analytics.
4. Click the **Revenue at Risk** card.

**Expected Result:**
- Active, MRR, New Subs, Churned, Churn Rate, Trial Conversions, Renewal Revenue, ARPU, and Retention Saves all navigate to their documented destinations.
- Revenue at Risk has NO link (it's a snapshot — clicking does nothing or shows a tooltip).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Change the date range picker (e.g. switch from Last Month to Last 7 Days) and confirm cards update with new values within a few seconds.
- Trigger a cache invalidation event by manually changing one subscription's status from Active → On Hold via the dropdown (task 14-08 already covers this). Reload the Overview page. Confirm the Active count and Revenue at Risk update.
- Confirm zero console errors and zero failed REST calls in DevTools Network.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Date range used:
- Notes:
