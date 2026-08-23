---
id: 124
title: SLT-RET-04 Downgrade retention offer target, no-target, proration and renewal conditions
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, downgrade, stripe, paddle, day-05]
due: "2026-08-28"
estimate: 2h15m
depends_on: [60, 72, 121]
class: standard
---

> **SLT-RET-04** · group `retention` · starts **D05**, completes after target renewal

## Objective
Verify downgrade retention offers across linked target, missing target, invalid/equal/higher target, reason/product/status eligibility, immediate vs next-renewal application, proration, remote gateway sync and one-use history.

## Steps
1. Snapshot settings and ladder links. Configure a Peer→Basic downgrade offer for matching reasons; verify editor search/select, saved target and storefront copy.
2. Require no offer for absent target, trashed/unpublished target, same product, higher/equal non-downgrade, incompatible interval/currency, trial/on-hold/pending-cancel/cancelled source and reason mismatch.
3. On active Stripe source accept once. Require standard plan-switch confirmation/loading, correct classification, amount/credit/proration, pending/immediate state per config, cancellation cleared and offer history/note/audit.
4. Verify current-cycle access/amount and next renewal use the correct source/target plan. Resolve exact switch and renewal orders/actions; no duplicate subscription or lost relationship.
5. Re-enter cancel flow: used offer is not shown. Decline/X/Escape on a second source consumes nothing.
6. Repeat on Paddle with compatible day/1 products. Require same remote subscription updates to target product/price before its next billed event and charges target price once; if capability excludes the case, require clear hidden/refusal and no mutation.
7. Test quantity, coupon/retention-discount coexistence and target deletion after scheduling; require safe refusal/rollback and no orphan actions.
8. Restore settings/links exactly and reconcile UI/meta/orders/actions/remote/mail.

## Pass criteria
- [ ] Linked target accepted through standard switch flow with correct math/schedule/access
- [ ] Every no-target/ineligible/invalid target case is safely hidden/refused
- [ ] One-use/dismissal and coupon/quantity/target-deletion edges pass
- [ ] Stripe and supported Paddle remote renewal use target price exactly once

Failures create/update the mandatory `qa/issues/` card and keep the task blocked.
