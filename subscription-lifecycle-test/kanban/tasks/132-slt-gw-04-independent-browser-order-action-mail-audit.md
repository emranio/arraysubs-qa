---
id: 132
title: SLT-GW-04 Independent browser, portal, HPOS, scheduler and Mailpit audit
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, audit, stripe, paddle, day-10]
due: "2026-09-02"
estimate: 3h
depends_on: [117, 128, 129, 130]
class: standard
---

> **SLT-GW-04** · group `audit` · scheduled **D10** after core-only ownership

## Objective
Independently re-open representative Stripe/Paddle outcomes from the browser and reconcile them against HPOS, subscription meta, scheduler rows, gateway/audit logs and Mailpit. This catches false passes caused by trusting only one layer.

## Required samples
- Stripe: simple, mixed, variable, Subscription Box, SCA, failed/recovered, switched, retention, refunded/cancelled.
- Paddle: simple, mixed, product-type parity, remote renewal, switched/method-updated, replayed, refunded/cancelled or supported negative.

## Steps
1. Select samples only by registry alias and exact relationships; publish selection before reads.
2. For each, compare customer receipt/order/subscription views and admin detail/list/order/action screens with fresh screenshots.
3. Query HPOS/order items/meta, subscription post/meta/notes, exact actions/logs and redacted gateway transaction/webhook/audit records.
4. Reconcile Mailpit messages by exact baseline/IDs, recipients, subjects, dates, amounts and links. Reject missing/duplicate/background misattribution.
5. Recalculate totals, coupon/fee/proration/quantity, completed payments, next date and status from source rows.
6. Verify carts/session state, no browser console/network errors, correct capability/loading/confirmation UI and no native dialogs.
7. Record a layer-by-layer PASS table; any mismatch updates the owning task and creates/updates a QA issue. Mark done only when every sample agrees across all layers.

## Pass criteria
- [ ] Every required Stripe/Paddle sample reconciles across UI, HPOS, meta, actions, provider and mail
- [ ] Totals/dates/statuses/links/recipients and negative controls agree exactly
- [ ] No native dialog, missing loading state, console/network error or stale cart is ignored
