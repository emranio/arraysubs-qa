# Simple quick-product settings verification

Scope: user-requested simple-product wizard parity with Subscription Type, Fixed Period Membership and Subscription Shipping. Test site: http://localhost:10013; admin entry: /wp-admin/?localwp_auto_login=1. Credentials/context: local administrator from AGENTS.md. Preserve existing QA issues and unrelated local changes.

## Implementation plan

1. Align the three subscription modes, conditional billing fields, maximum-length help, lifetime/fixed-date exclusions, and state normalization.
2. Add dedicated membership and shipping steps using shared FormBuilder controls and modal styling; expose unavailable premium options with the existing Pro treatment.
3. Match annual cutoff/month-day input, enrollment window, renewal behavior, shipping charge modes and overrides; harden matching server validation and inactive-field handling.
4. Build core assets and test real creation, editor persistence and storefront output. Review core-only availability and simple-only scope.

## Fresh test board and schedule

All cases scheduled for 2026-09-15, executed sequentially in this task; no recurring schedule.

| Task | Scope | Status |
| --- | --- | --- |
| QPO-01 | Fixed/flexible/full-flexible field states, limits, available periods, navigation | passed |
| QPO-02 | Annual/absolute membership, dates, enrollment order, expire/renew, incompatible modes | passed |
| QPO-03 | Recurring/one-time shipping, overrides, virtual, inactive values | passed |
| QPO-04 | Publish representative simple products; reopen editor and storefront | passed |
| QPO-05 | Renewal sync/trial/price restrictions, lifetime, unavailable Pro, other product types | passed |
| QPO-06 | Desktop/mobile screenshots, keyboard/focus, overflow, errors, build and file sizes | passed |

Evidence and results: report.md and screenshots/ in this directory. Any discovered regressions go in qa/issues with these task IDs, date, routes, affected IDs, reproduction and proof.
