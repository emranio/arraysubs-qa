# Stage 16 — Task 03: Performance Charts *(Pro)*

| Key | Value |
|---|---|
| Stage | Analytics & Reports |
| Module | WooCommerce → Analytics → Overview (Subscription Charts tabbed section) |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 16-02 (KPI cards verified) |

## Objective
Below the 10 KPI cards on the WC Analytics Overview, exercise the **Subscription Charts** tabbed section. Switch through all six chart tabs (MRR, Net Subscription Growth, Churn Rate, Trial Conversion Rate, Active Subscriptions, Renewal Revenue) and test every interval granularity (day, week, month, quarter, year). Verify that the chart redraws, the bars match the count, the tooltip shows exact date and value, and the auto-selected interval matches the documented mapping based on date-range length. Note: because the test catalog is now overwhelmingly **weekly** (with a single Basic Monthly $29.99 outlier), **day** and **week** granularities are the primary views — most renewal events plot on these scales. Month / Quarter / Year granularities are still tested but will show coarser, near-flat aggregates within a typical regression run.

## Pre-conditions
- Logged in as Administrator.
- ArraySubs Pro active.
- Date range picker available with enough historical data to make charts non-empty (set up via earlier stages or by simulating dates).
- 16-02 completed — KPI values are already validated.

## Test Data
- Date ranges to test (primary focus on day / week because most subscriptions are weekly):
  - 7 days (auto-selected interval should be **Daily**) — primary view; expect 1–7 bars showing weekly renewal events distributed across days.
  - 30 days (auto-selected should be **Weekly**) — primary view; expect 4–5 weekly bars summing recurring weekly revenue (e.g., a single $19.99/wk subscription should produce ~$19.99 per weekly bar).
  - 90 days (auto-selected should be **Monthly**) — secondary view; expect ~3 monthly bars with each weekly subscription contributing ~$86.62 (19.99 × 4.333) per month plus the single Basic Monthly $29.99.
  - 400 days (auto-selected should be **Quarterly**) — secondary view; expect 4–5 quarterly bars.

## Sub-Tasks

### Sub-Task 3.1 — Locate the Subscription Charts section
**Steps:**
1. On **WC → Analytics → Overview**, scroll below the KPI cards.
2. Locate the tabbed chart section.

**Expected Result:**
- Six tabs are visible: **Monthly Recurring Revenue**, **Net Subscription Growth**, **Renewal Revenue**, **Churn Rate**, **Trial Conversion Rate**, **Active Subscriptions**.
- One tab is active by default (typically MRR).
- A chart canvas renders with bars for each interval in the current date range.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — MRR chart
**Steps:**
1. Click the **Monthly Recurring Revenue** tab.
2. Note the chart axis labels and the bars.
3. Hover one bar to view the tooltip.
4. Read the summary value above the chart.

**Expected Result:**
- Y-axis shows currency formatted values; X-axis shows interval labels.
- A bar appears for every interval in the date range.
- Hover tooltip shows `<date>` and `<currency value>`.
- Summary above chart shows the current period total and a delta percentage vs previous period.
- On the Daily view, individual weekly-renewal charge events should appear at their renewal day (e.g., a $19.99/wk subscription renewing every Tuesday produces a $19.99 bar on each Tuesday in the range).
- On the Weekly view, each weekly subscription contributes its recurring amount (~$19.99) per weekly bar.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Net Subscription Growth chart
**Steps:**
1. Click **Net Subscription Growth**.
2. Hover the most recent bar.

**Expected Result:**
- Chart renders integer values (number scale).
- A positive bar = more new subs than cancellations in that interval; a zero or negative bar = parity or net loss.
- Tooltip shows the exact integer.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Churn Rate, Trial Conversion Rate, Active Subscriptions, Renewal Revenue
**Steps:**
1. Click each remaining tab in turn: **Churn Rate**, **Trial Conversion Rate**, **Active Subscriptions**, **Renewal Revenue**.
2. For each, confirm the chart redraws, the axis units update appropriately, and a hover tooltip works.

**Expected Result:**
- Churn Rate uses percent units; lower-is-better colouring.
- Trial Conversion Rate uses percent units.
- Active Subscriptions uses integer count.
- Renewal Revenue uses currency.
- Each tab has its own summary value above the chart.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Auto-selected interval at 7 days
**Steps:**
1. Set the WC date picker to **Last 7 Days**.
2. Inspect the interval selector for the chart.

**Expected Result:**
- The interval auto-selects to **Daily**.
- The chart shows up to 7 daily bars.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Auto-selected interval at 30 / 90 / 400 days
**Steps:**
1. Set the WC date picker to **Last 30 Days** — confirm interval auto-selects to **Weekly**.
2. Set the WC date picker to **Last 90 Days** — confirm interval auto-selects to **Monthly**.
3. Set a custom range of approximately **400 days** — confirm interval auto-selects to **Quarterly**.

**Expected Result:**
- Auto-selection follows the documented mapping:
  - ≤ 7 days → Daily
  - 8–60 days → Weekly
  - 61–365 days → Monthly
  - > 365 days → Quarterly

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Manual interval override (day / week / month / quarter / year)
**Steps:**
1. With a 30-day range active, manually select **Day** in the interval selector. Confirm the chart redraws with daily bars.
2. Switch to **Week** — chart redraws with weekly bars.
3. Switch to **Month** — chart redraws with one or two monthly bars.
4. Switch to **Quarter** — chart redraws with quarterly bars.
5. Switch to **Year** — chart redraws with yearly bars (likely a single bar).

**Expected Result:**
- All five interval options are selectable.
- The chart redraws each time without errors.
- Tooltip date format reflects the chosen interval (e.g. `Apr 23` for daily, `Apr 21–27` for weekly, `April 2026` for monthly, `Q2 2026` for quarterly, `2026` for yearly).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Empty-period handling
**Steps:**
1. Set the date range to a window that you know has zero data (e.g. a range before the test store was set up).

**Expected Result:**
- Chart shows an empty state or zero-value bars across the range.
- No JavaScript error.
- Summary shows `0` or an appropriate empty placeholder.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Switch tabs rapidly — each tab change must redraw without leaving stale tooltips or stale axis labels.
- Resize the browser window to a 768px viewport — the charts must remain readable (responsive).
- Confirm zero JavaScript errors in DevTools Console while iterating across tabs and intervals.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Date ranges tested:
- Notes:
