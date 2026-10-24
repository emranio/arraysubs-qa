# Home report consolidation verification

Date: 2026-10-24, Asia/Dhaka. Scope: this development change only; no recurring automation.
Environment: http://localhost:10013. Admin: documented LocalWP admin auto-login. Browser: isolated agent-browser session `report-home-admin`.

Reports inventory: Subscription Performance, Retention Analytics, AI Churn Analysis, AI Revenue Forecast, Orders Analytics, Revenue Analytics, Products Analytics, Variations Analytics, Customers Analytics, Orders, Subscriptions, Member Insights, Store Credit Balances, Credit Transaction History, Activity Audit Trail, Gateway Health & Webhooks, Scheduled Job Logs.

Verify every destination has a relevant linked Home card. New cards follow Action Center and Renewal Outlook. Distinguish MRR, store sales, subscription collections, current balances, lifetime customer/history counts and selected-period records. Compare rendered metrics to the existing report/service data. Test 7d/30d/90d/month and refresh. Check disabled modules, Pro unavailable, empty/error sources, restored original settings. Inspect desktop, tablet, mobile screenshots, overflow, keyboard-accessible report links, admin accent inheritance and console errors. Remove Reports navigation/route/style and replace Analytics Configure URL.

Existing qa/issues tasks were inspected; preserve unrelated issues. Any newly found unresolved defect belongs in qa/issues with plan, task/day, entity/user IDs or N/A, exact route/context, steps, expected/actual and evidence. No fixtures or customer data edits are planned. Save original plugin and module state before toggles and restore after verification.
