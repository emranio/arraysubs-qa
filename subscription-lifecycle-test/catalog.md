# SLT catalog — prerequisite task index

This deliberately short index covers the foundation, product-catalog, and flexible-sync setup tasks. It is
not a second copy of the 118 executable QA instructions. The complete task-key-to-ID map is
`automation/key-to-task-id.json`; current task bodies under `kanban/tasks/` are authoritative,
`calendar.md` controls ordering, and `plan-audit.md` preserves the history behind resolved conflicts.
Never execute an older copied task body from notes, logs, or git history.

The original all-in-one `SLT-SETUP-99` teardown was retired because it would delete fixtures before
the D12 watch completed. Only `SLT-SETUP-99A` and `SLT-SETUP-99B` are executable.

## Foundation and teardown

- [SLT-SETUP-01](kanban/tasks/010-slt-setup-01-recon-environment-create-slt-evidence.md)
- [SLT-SETUP-02](kanban/tasks/011-slt-setup-02-apply-and-record-the-four-window-wide.md)
- [SLT-SETUP-03](kanban/tasks/012-slt-setup-03-create-the-slt-account-matrix-7-slt.md)
- [SLT-SETUP-04](kanban/tasks/025-slt-setup-04-create-the-six-slt-coupons-covering.md)
- [SLT-SETUP-05](kanban/tasks/026-slt-setup-05-verify-paddle-sandbox-readiness-and.md)
- [SLT-SETUP-99A](kanban/tasks/118-slt-setup-99a-d10-settings-restore-and-partial.md)
- [SLT-SETUP-99B](kanban/tasks/119-slt-setup-99b-post-watch-teardown-on-2026-08-15.md)

## Product catalog

- [SLT-PROD-01](kanban/tasks/005-slt-prod-01-create-slt-daily-core-the-day-1.md)
- [SLT-PROD-02](kanban/tasks/037-slt-prod-02-create-slt-free-signup-daily-the-0-00.md)
- [SLT-PROD-03](kanban/tasks/038-slt-prod-03-create-slt-trial-four-day-the-trial.md)
- [SLT-PROD-04](kanban/tasks/058-slt-prod-04-create-slt-signup-fee-daily-with-a-15.md)
- [SLT-PROD-05](kanban/tasks/020-slt-prod-05-create-slt-renewal-price-step-with-a.md)
- [SLT-PROD-06](kanban/tasks/006-slt-prod-06-create-slt-fixed-three-cycles-a-day-2.md)
- [SLT-PROD-07](kanban/tasks/007-slt-prod-07-create-slt-lifetime-one-time-the-never.md)
- [SLT-PROD-08](kanban/tasks/071-slt-prod-08-create-slt-variable-daily-with-four.md)
- [SLT-PROD-09](kanban/tasks/039-slt-prod-09-create-slt-grouped-set-a-grouped.md)
- [SLT-PROD-10](kanban/tasks/059-slt-prod-10-create-slt-box-daily-pro-subscription.md)
- [SLT-PROD-11](kanban/tasks/060-slt-prod-11-create-the-four-product-plan-ladder.md)
- [SLT-PROD-12](kanban/tasks/021-slt-prod-12-create-slt-flex-month-segments-the.md)
- [SLT-PROD-13](kanban/tasks/008-slt-prod-13-create-slt-flex-week-segments-the.md)
- [SLT-PROD-14](kanban/tasks/022-slt-prod-14-create-the-two-daily-flex-sync.md)
- [SLT-PROD-15](kanban/tasks/040-slt-prod-15-create-slt-flex-variable-daily-with.md)
- [SLT-PROD-16](kanban/tasks/023-slt-prod-16-create-slt-retry-daily-and-slt-paddle.md)

## Flexible-renewal-sync setup and audits

- [SLT-SYN-01](kanban/tasks/013-slt-syn-01-audit-simple-product-flexible-renewal.md)
- [SLT-SYN-02](kanban/tasks/044-slt-syn-02-audit-variation-level-flexible-renewal.md)
- [SLT-SYN-03](kanban/tasks/027-slt-syn-03-create-the-two-sync-group-control.md)
- [SLT-SYN-04](kanban/tasks/061-slt-syn-04-prove-global-sync-to-billing-cycle-true.md)

## Execution rules

- Use the board task file, `calendar.md`, and `watch-schedule.md` together.
- Use task-keyed browser sessions and close only those sessions.
- Every WP-CLI command uses `--allow-root`.
- Never write raw gateway credentials or full payment-card numbers into evidence.
- Never run Action Scheduler by hook or group. When a task explicitly authorizes manual execution,
  use one verified action ID at a time from Tools → Scheduled Actions.
- Product findings become separate Markdown files under `issues/`; they are not added as lifecycle
  board cards and do not authorize edits to `arraysubs/` or `arraysubspro/`.
