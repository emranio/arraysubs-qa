# SLT-REF-08 — plan-switching matrix

Fresh-cycle guide updated 2026-08-22. Re-read settings and linked products before every bracket.

## Required classifications

- Upgrade, downgrade and equal-price crossgrade.
- Simple-to-simple and variation-to-variation.
- Customer and admin initiation.
- Immediate and next-renewal/deferred application where supported.
- Proration, non-zero switch fee, discounts/store-credit interaction and remote Paddle price update.

## Integrity proof

Record old/new plan snapshots, preview arithmetic, confirmation/loading UI, switch order/fee lines,
subscription product/price/schedule, pending-switch contract, provider price/transaction, next renewal
and emails. A switch must not create a second subscription unless the card explicitly tests that
negative.

Sources: `arraysubs/src/Features/PlanSwitching/`,
`arraysubspro/src/Features/EarlyRenew/`, and
`documentations/architecture/plan-switching.md`.
