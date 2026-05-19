# Stage 16 — Analytics & Reports

**Goal:** Verify the analytics surface area shipped by both ArraySubs and ArraySubsPro: the **Reports Hub** directory page, the **Subscription Performance** dashboard with 10 KPI cards on **WooCommerce → Analytics → Overview**, the six selectable **Performance Charts** at all five interval granularities, the **WooCommerce Analytics Extension** that adds Order Type filters and subscription revenue cards across Orders / Revenue / Products / Variations / Customers, and the **Order List Enhancements** on **WooCommerce → Orders** (Type column, Coupons column, type/coupon/subscription filters, summary panel, and the historical backfill tool). The numbers shown by each card and chart must be consistent with the subscriptions and orders created in earlier stages.

**Prerequisites:**
- Stages 14 and 15 complete.
- WooCommerce Admin (analytics) is enabled.
- ArraySubs Pro is active (charts, performance dashboard, WC analytics extension, and order list enhancements all require Pro).
- Date-traceable data exists from earlier stages: at least one Initial subscription order, several Renewal orders, one cancellation with a captured reason, one trial that converted, one plan switch (Subs Upgrade), one Store Credit purchase, and one Other (regular WC) order.
- A Shop Manager and a Customer test account exist to verify capability checks.

**Run order:**
1. [01 — Reports Hub](01-reports-hub.md) — Open ArraySubs → Reports, verify all categories and report cards, and confirm capability gating for shop manager vs customer.
2. [02 — Subscription Performance dashboard *(Pro)*](02-subscription-performance-dashboard-pro.md) — 10 KPI cards on WC Analytics Overview, each value reconciled with stage-6/8 data.
3. [03 — Performance charts *(Pro)*](03-performance-charts.md) — 6 chart tabs across day / week / month / quarter / year intervals.
4. [04 — WooCommerce Analytics extension *(Pro)*](04-woocommerce-analytics-extension-pro.md) — Order Type filter, Subscription Only, Revenue cards, Member Details quick link.
5. [05 — Order list enhancements](05-order-list-enhancements.md) — Type / Coupons columns, three filters, summary panel, backfill notice.

**Exit criteria:**
- Reports Hub renders 12 categories, all report cards visible, summary bar shows total counts, quick-nav pills scroll to each section.
- Capability check: Shop Manager sees the Reports page; Customer is blocked.
- Subscription Performance dashboard's 10 KPI cards show numerically consistent values vs. earlier stage data; Revenue at Risk is a snapshot with no period delta.
- Each chart tab loads, every interval (day, week, month, quarter, year) is selectable, the chart redraws, and tooltips show exact bar values.
- WC → Analytics → Orders shows the **Type** column with Subs Purchase / Subs Renew / Subs Upgrade / Subs Trial / Credit Purchase / Other; the quick filter and the multi-select advanced filter (Type Is / Type Is Not) both work; "Subscription products only" filter reduces results correctly; Revenue/Products/Variations/Customers reports show their documented additions.
- WC → Orders has the Type column, Coupons column, Type filter, Coupon filter, Subscription Products Only filter, and the embedded summary panel with per-type counts that update with active filters.
