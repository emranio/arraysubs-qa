# Stage 08 — Task 07: Retention Analytics Dashboard

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Analytics Dashboard |
| Plugin Coverage | Pro (per task scope; the retention analytics page itself is documented as Free in the manual but the deeper Pro analytics breakdowns are scope-flagged Pro for this regression) |
| Estimated Time | 25 min |
| Depends On | 02 — Discount, 03 — Pause, 04 — Downgrade, 05 — Contact support, 06 — Eligibility (so the dashboard has data) |

## Objective
Open **WooCommerce → Analytics → Retention** and verify the dashboard renders the eight summary KPI cards with non-zero values from the Stage 08 test runs, the three charts (Churn Reasons, Retention Offer, Trend), and the Activity Logs table with working filters by event type, date range, and product.

## Pre-conditions
- Tasks 02 through 06 PASSED so the database contains: at least 1 cancellation, multiple offers shown, multiple offers accepted (discount, pause, downgrade, contact support), and at least 1 offer declined.
- Date range for the dashboard set to **Today** or **This week** so the recent test events are included.

## Test Data
- Expected to see at least these subscriptions in the activity log: R1, R2, R3, R4, R5, R6 (and the negative R3-neg from Task 04).
- Expected event types: `cancellation_scheduled`, `cancellation_completed`, `cancellation_undo`, `offer_shown`, `offer_accepted`, `offer_declined` (record exact labels observed).

## Sub-Tasks

### Sub-Task 07.1 — Open the analytics page
**Steps:**
1. As Administrator, navigate to **WooCommerce → Analytics → Retention** in the WordPress admin sidebar.

**Expected Result:**
- The page loads without console errors.
- A date range picker is visible at the top.
- Four sections render below: Summary Cards, Charts, Activity Logs.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Verify the eight summary cards
**Steps:**
1. Set the date range to **This week** (or wide enough to include all Stage 08 events).
2. Read each of the eight summary cards.

**Expected Result:**
- All eight cards render with the labels: **Total Cancellations**, **Churn Rate**, **Avg. Age at Cancel**, **Avg. Payments Before Cancel**, **Offers Shown**, **Offers Accepted**, **Offer Success Rate**, **Retained Revenue**.
- **Total Cancellations** > 0 (events from Stage 07 Task 03 and Stage 08 onwards).
- **Offers Shown** > 0 (Tasks 02–06 each shown at least one offer).
- **Offers Accepted** > 0.
- **Offer Success Rate** is a sane percentage (Accepted / Shown × 100).
- **Retained Revenue** has a non-zero monetary value reflecting the Discount, Pause, and Downgrade saves.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Churn Reasons chart
**Steps:**
1. Scroll to the Churn Reasons pie chart.
2. Hover each slice to reveal tooltips.

**Expected Result:**
- The pie chart shows segments for each cancellation reason exercised in this stage (e.g. Too expensive, Just need a temporary break, Technical issues).
- Tooltip on hover reveals the reason label and the count.
- A legend below or beside the chart matches the segments.
- No `not_provided` slice exceeds the others (because Require Reason is on).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — Retention Offer chart
**Steps:**
1. Scroll to the Retention Offer pie chart.

**Expected Result:**
- The chart shows the breakdown of accepted vs declined offers.
- Hover tooltip reveals counts.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Trend chart
**Steps:**
1. Scroll to the Trend line chart.
2. Hover datapoints across the timeline.

**Expected Result:**
- The chart plots multiple series: cancellations, offers shown, offers accepted, new subscriptions.
- Hovering datapoints reveals dated tooltips.
- The series matches the actions performed during this stage (a spike in offers shown today, etc.).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Activity log filters by event type
**Steps:**
1. Scroll to the Activity Logs table.
2. Apply the filter **Event type → Offer Accepted**.

**Expected Result:**
- The table only shows rows whose event type is offer accepted.
- Each row links back to the subscription and shows the offer type and timestamp.
- Pagination controls work: clicking next/previous loads more rows without page errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.7 — Activity log filter by date range
**Steps:**
1. Change the date range to **Yesterday** (a date with no Stage 08 events).
2. Observe the table.
3. Change back to **Today** / **This week**.

**Expected Result:**
- With Yesterday selected, the activity log is empty (or shows only events from that day if any).
- With Today selected, all Stage 08 events appear again.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.8 — Activity log filter by product
**Steps:**
1. Apply the **Product** filter and select **Pro Monthly** (used in Subscription R3 / Task 04).

**Expected Result:**
- The activity log is filtered to events on Pro Monthly subscriptions only (R3 entries appear).
- Removing the filter restores the full list.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Compare the **Total Cancellations** card to the count of cancellations performed in Stage 07 Task 03 + any in Stage 08 — values should align.
- Confirm the **Offer Success Rate** matches `(Offers Accepted / Offers Shown) × 100` rounded to one decimal.
- Verify the page does not show any "Pro upgrade" upsell when ArraySubs Pro is active (this is a Pro-only verification).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
