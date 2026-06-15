---
id: 128
title: 'stage-17: Stripe card missing capability tags'
status: closed
priority: medium
created: 2026-05-23T16:03:06.839998814+02:00
updated: 2026-05-24T20:10:32.622592118+02:00
started: 2026-05-24T20:05:03.774593626+02:00
completed: 2026-05-24T20:10:32.622591146+02:00
tags:
    - qa
    - stage-17
    - gateway-health
    - stripe
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:10:32.622592008+02:00
class: standard
---

Task: stages/17-audits-and-logs/04-gateway-health-dashboard.md\n\nSub-Task 04.3 expects the expanded Stripe card to show capability pills, including at least subscription plus trial/pause/refunds/card auto update.\n\nObserved in browser on Gateway Logs:\n- Stripe card expands and shows description, official webhook URL, secondary webhook URL/status, secondary last webhook, and WooCommerce Settings link.\n- No Capabilities section or capability pills are visible in the expanded Stripe card.\n\nExpected: capability tags should be shown for Stripe.

[[2026-05-24]] Sun 20:06
Plan: keep the existing gateway-health REST payload, normalize boolean capability maps in the React dashboard, render enabled capabilities as user-facing pills with Stripe-friendly labels, rebuild arraysubs assets, then verify the expanded Stripe card in browser.

[[2026-05-24]] Sun 20:10
Fix applied: GatewayHealthDashboard now normalizes boolean capability maps from REST and renders enabled capabilities as human-readable pills. Rebuilt arraysubs assets with npm run build.

Verification: agent-browser opened Gateway Logs/settings gateway dashboard, expanded Stripe, and confirmed the Capabilities section renders pills including Subscription, Trial, Refunds, and Card Auto Update, alongside the webhook URLs. Screenshot: qa/artifacts/issue-128/stripe-capability-tags.png.
