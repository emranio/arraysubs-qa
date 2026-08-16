# Stage 16 — Task 06: AI Reports (Pro)

| Key | Value |
|---|---|
| Stage | Analytics & Reports |
| Module | WooCommerce Analytics → AI Churn Analysis / AI Revenue Forecast |
| Plugin Coverage | ArraySubsPro |
| Estimated Time | 20 min |
| Depends On | Stage 16 Task 01; live subscription and order data |

## Objective

Verify that both AI report implementations are owned entirely by ArraySubsPro, render useful deterministic store metrics even without an AI connector, and disappear cleanly when Pro is inactive while the Free Reports Hub continues to advertise them with Pro badges.

## Pre-conditions

- ArraySubs and ArraySubsPro are active.
- At least one live subscription and one completed subscription order exist.
- Administrator session available.

## Sub-Tasks

### Sub-Task 6.1 — Open AI Churn Analysis

1. Navigate to **WooCommerce → Analytics → Churn Analysis**.
2. Wait for the report to finish loading.
3. Inspect the browser console and failed network requests.

**Expected Result:**

- The URL uses `page=wc-admin&path=/analytics/arraysubs-ai-churn`.
- The page shows AI Churn Analysis, filter controls, KPI cards, charts, and the churn-risk table or a valid no-data state.
- If no AI connector is configured, a user-facing setup notice appears while deterministic metrics still render.
- No JavaScript error, PHP error, 404, or 500 response occurs.

### Sub-Task 6.2 — Open AI Revenue Forecast

1. Navigate to **WooCommerce → Analytics → Revenue Forecast**.
2. Change the projection horizon once.
3. Inspect the browser console and failed network requests.

**Expected Result:**

- The URL uses `page=wc-admin&path=/analytics/arraysubs-ai-forecast`.
- Current MRR/ARR, collected revenue history, subscriber movement, billing mix, and projection controls render or show a valid no-data state.
- Changing the horizon updates the deterministic projection surface without a page error.
- No JavaScript error, PHP error, 404, or 500 response occurs.

### Sub-Task 6.3 — Verify Paused analytics semantics

Before deactivating Pro, pause one billing subscription and refresh both reports.

**Expected Result:**

- Churn Analysis keeps the subscription in its live population, shows status **Paused**, and increments the separate Paused KPI without incrementing On Hold.
- Revenue Forecast excludes the Paused subscription from current billing MRR and active subscriber count because no charge is due while paused.
- After Resume, the Paused KPI returns to zero and the subscription returns to current MRR/subscriber count.

### Sub-Task 6.4 — Verify Pro-only ownership

1. Deactivate ArraySubsPro while leaving ArraySubs active.
2. Reload WooCommerce Analytics.
3. Check the analytics menu and visit both AI report URLs directly.
4. Open **ArraySubs → Reports**.

**Expected Result:**

- Churn Analysis and Revenue Forecast are absent from the WooCommerce Analytics menu.
- Direct report URLs no longer mount an AI report or load AI report assets.
- The Free Reports Hub still renders the AI Reports catalog section, and the section plus all six cards carry Pro badges.
- ArraySubs core remains operational with no fatal error.

### Sub-Task 6.5 — Reactivate Pro

1. Reactivate ArraySubsPro.
2. Reload both AI report URLs.

**Expected Result:**

- Both analytics menu entries and both pages return immediately.
- Existing cached AI output, if any, remains intact.
- No duplicate menu entries, duplicate requests, or duplicate React roots appear.

## Sign-off

- Tester:
- Date:
- Browser & version:
- AI connector state:
- Notes:
