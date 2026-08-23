# Fresh-cycle reference policy

The previous cycle's generated mechanic notes were retired because their line numbers, ownership
paths, date examples, and gateway assumptions predated the move of automatic-payment code into the
free plugin. They remain recoverable from Git history but are not valid evidence for this cycle.

D0 must revalidate runtime/code ownership from the current checkout and record only facts needed by
the active cards. The ten `SLT-REF-*` files in this directory are current-cycle source maps, not
verdicts. Start with these current locations:

- `arraysubs/src/Features/AutomaticPayments/`
- `arraysubs/src/Supports/ActionScheduler.php`
- `arraysubs/src/functions/date-calculations.php`
- `arraysubspro/src/Features/CancellationFlow/`
- `arraysubspro/src/Features/RetentionAnalytics/`
- `arraysubspro/src/Features/ProfileFields/`
- `documentations/architecture/action-scheduler-system.md`
- `documentations/architecture/payment-retry-system.md`
- `documentations/architecture/gateway-sync.md`
- `documentations/architecture/plan-switching.md`

Runtime/browser/DB/provider evidence remains authoritative for QA. Any new reference note must state
its validation date and current plugin versions and must not contain a verdict from an execution
task.
