# SLT-REF-06 — flexible renewal-sync matrix

Fresh-cycle guide updated 2026-08-22. Flexible Sync is premium behavior; Stripe/Paddle gateway
ownership remains in ArraySubs core.

## Axes

- Simple and variation-level configuration.
- Global sync versus per-product override and exclusivity.
- Full, prorate and next-cycle modes.
- Week, month and day/3 boundaries; disabled segments and overflow days.
- Quantity rounding, renewal totals, calendar-aware advancement and gateway capability refusal.

## Fresh calendar anchors

- Re-read site timezone and `start_of_week` on D0.
- If week start is Saturday, the active D0 week is 2026-08-22 through 2026-08-29.
- The month probe uses August 2026 with boundaries 1-24 / 25-27 / 28-end.

## Current sources

- `arraysubs/src/Supports/RenewalSegmentPlan.php`
- `arraysubs/src/Features/SubscriptionProducts/Services/RenewalSegmentRuntime.php`
- the current Flexible Renewal Sync feature under `arraysubspro/src/Features/`
- `arraysubs/src/Features/SubscriptionCheckout/Services/Traits/`

Every expected amount/date is recomputed from the saved plan and live helper output before checkout.
