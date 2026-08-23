---
id: 125
title: SLT-RET-05 Contact-support retention offer URL, logging and no-mutation conditions
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, support, day-05]
due: "2026-08-28"
estimate: 1h15m
depends_on: [121]
class: standard
---

> **SLT-RET-05** · group `retention` · scheduled **D05**

## Objective
Verify the contact-support offer for valid/invalid URL, reason eligibility, new-tab safety, analytics logging, dismissal and absolute subscription-state immutability.

## Steps
1. Snapshot retention settings; enable Contact Support with a safe task-owned URL and eligible reasons. Verify label/body/CTA save/reload and URL sanitization.
2. Matching reason shows one card; mismatch, disabled offer and empty URL hide it. Test malformed/javascript/cross-origin values and require safe rejection/sanitization.
3. Click CTA. Require the configured destination opens in a new tab with safe rel attributes; original cancel flow and subscription remain intact.
4. Verify one offer-view/click/contact analytics event and note/audit only if the documented contract calls for it. No offer acceptance, status/date/action/order/charge/meta/mail mutation.
5. X/Escape/Back/decline continue-cancelling paths log only their intended event and do not consume future eligibility.
6. Test Stripe and Paddle subscriptions; behavior is gateway-neutral and must match.
7. Restore settings exactly and prove empty diffs for subscription/order/action/mail state.

## Pass criteria
- [ ] Valid URL opens safely in new tab; empty/invalid URL is hidden/refused
- [ ] Reason/enablement/dismissal conditions pass on Stripe and Paddle
- [ ] Analytics are accurate and subscription/billing state is byte-for-byte unchanged

Failures create/update the mandatory `qa/issues/` card and block completion.
