# SLT-REF-02 — Action Scheduler catalog procedure

Fresh-cycle guide updated 2026-08-22. Constants and live registrations are authoritative; this file
does not freeze a previous action inventory.

## Build the D0 catalog

- Read `arraysubs/src/Supports/ActionScheduler.php` for every `HOOK_*` and `GROUP_*` constant.
- Read each registering service in `arraysubs/src/Features/RecurringBilling/`,
  `arraysubs/src/Features/Emails/`, `arraysubs/src/Features/AutomaticPayments/` and the relevant Pro
  feature.
- Query the live pending/complete/failed Action Scheduler rows and callback registrations.
- Record arguments as indexed arrays and link every subscription-scoped row to the exact registry ID.
- Reconcile option/meta action pointers with the queue; no raw-string-only expectation is sufficient.

## Safety

Natural lifecycle cards never run by hook or group. The D8 bracket may execute only the exact
allowlisted action IDs named by tasks 112 and 99, invoice before charge, after a non-SLT2 snapshot.

Source: `documentations/architecture/action-scheduler-system.md`.
