# Developer support implementation and verification

Prepared and scheduled: 2026-10-18, Asia/Dhaka. Scope confirmed by the owner's instruction to implement both developer-support plans and test the affected plugin flows. This is a fresh focused plan, not the removed subscription lifecycle cycle.

Sources: `documentations/plans/arraysubs-developer-support-prerequisite-fixes.md` and `documentations/plans/arraysubs-developer-support-requirements.md`.

Site: http://localhost:10013 (local development checkout). Both plugins initially active; official Stripe gateway initially inactive. Browser automation uses agent-browser isolated sessions and screenshots. Local WP-CLI uses the site's existing MySQL socket, with `--allow-root`; no credentials or environment changes. Preserve all existing `qa/issues/` records. New failures go to that board with task/plan/date and fixture identity.

## Execution schedule

1. Task 1: F2 then F1, aggregation and period-aware usage. Test normalizers, limits, period boundaries, replay and concurrency; integrate Feature Manager editor/account/admin views.
2. Task 2: F4 then F3, condition normalization and variation holdings. Verify false/true, invalid/nested logic, purchase cache invalidation and source deduplication. F8 comments only.
3. Task 3: F6 then F7, append-only ledger and durable plan-switch grants. Verify deletion refusal, rollback exception, replay, no-Pro/module-off policy and balance reconciliation.
4. Task 4: F5 legacy cleanup, shared UI integration, builds and browser regression for settings/product features/account/credit history. Verify unrelated renewal listeners still registered and per-file download behavior preserved.
5. Task 5: settle shared PHP API schemas; subscription/customer/product reads and validated lifecycle methods with bounded relationships.
6. Task 6: explicit-context access and separate benefit readers, Pro adapters, feature/credit updates and lifecycle notifications.
7. Task 7: core-only and Pro matrices, browser verification, executed examples and accurate documentation/changelogs.

Tasks 1–4 precede public API implementation. Record completed checks and limitations in `report.md`; never mark untested behavior as passed. Do not publish releases, bump versions, alter licensing, send email to external people, install Composer dependencies or run phpcs. Local email tests must use the existing Mailpit sink.

## Working decisions

Use the plan's recommended deterministic longest-text/max source policy. Usage keys distinguish exact feature names. Unsupported plan-switch credit defers to renewal; renewal auto-apply off means unsupported plan-switch credit. Successful admin manual renewal payments start a feature billing cycle. These defaults were presented to the owner for steering before dependent implementation.

## Owner closeout — 2026-10-18

The owner explicitly chose to leave Stripe verification pending and skip external manual work. Local implementation and verification are complete; Task7 can close on the executed evidence in report.md. QA issue51 remains pending for a future configured Stripe run. No manual repository path is awaited. Provider and licensing-policy claims remain limited to what was actually verified; no release was published.
