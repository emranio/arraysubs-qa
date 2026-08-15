# SLT-SETUP-99B live settings transiently diverged from the restored 99A baseline

## Scope

- Task: 119, `SLT-SETUP-99B`
- Severity: critical teardown blocker
- Date: 2026-08-15
- Watch day: D13
- Stage: night prerequisite and baseline-restoration recheck, before `M0`, tail cancellation, action cancellation, allowlist export, or deletion
- Plan path: `kanban/tasks/119-slt-setup-99b-post-watch-teardown-on-2026-08-15.md`
- Site: `https://mirror-help.arrayhash.com`
- Supplied-facts timestamp: `2026-08-15 21:42:01` site / `15:42:01` UTC
- Read-only verification: `2026-08-15 21:52:51-21:54:58` site / `15:52:51-15:54:58` UTC
- Runtime check: read-only WP-CLI from the WordPress root with `--allow-root`
- Browser route/session: N/A; this finding is an exact runtime-option comparison, not a UI assertion
- Affected subscription/order/product/user IDs: N/A
- Gateway and checkout type: N/A

## Verdict

RESOLVED EXTERNALLY AFTER OBSERVATION. The preserved `SLT-SETUP-99A` proof requires `renewals.sync_to_billing_cycle=true`, and the original and evening D13 reads still matched it. Two fresh night reads returned `false`, so the live baseline-restoration assertion failed during the recorded window. A later exact read at `2026-08-15 22:11:03` site returned `true` again.

This runner did not infer which concurrent owner changed or restored the shared option and did not mutate it. The transient mismatch is no longer a current blocker, but it confirms that every safe retry must begin with a fresh exact settings read.

## Expected

The live scoped ArraySubs settings object matches the 99A restored baseline:

    {"customer_actions":{"allow_early_renew":false,"allow_reactivation":false},"pause_subscription":{"customer_can_pause":false,"enabled":false},"renewals":{"sync_first_charge_mode":"full","sync_to_billing_cycle":true}}

Authoritative preserved proof: `/home/server-manager/slt-evidence/SLT-SETUP-99A-settings-restore-proof.txt`.

## Actual

The night read returned:

    {"customer_actions":{"allow_early_renew":false,"allow_reactivation":false},"pause_subscription":{"customer_can_pause":false,"enabled":false},"renewals":{"sync_first_charge_mode":"full","sync_to_billing_cycle":false}}

An independent second exact-value read at `21:54:58` site again returned:

    sync_to_billing_cycle=false

The other five scoped settings still matched the restored object. The mismatch is confined to `renewals.sync_to_billing_cycle` in this bounded comparison.

## Reproduction

1. From the WordPress root, read `arraysubs_settings` with WP-CLI and `--allow-root`.
2. Extract only `customer_actions.allow_early_renew`, `customer_actions.allow_reactivation`, `pause_subscription.customer_can_pause`, `pause_subscription.enabled`, `renewals.sync_first_charge_mode`, and `renewals.sync_to_billing_cycle`.
3. Compare that object with `/home/server-manager/slt-evidence/SLT-SETUP-99A-settings-restore-proof.txt`.
4. Repeat the exact `renewals.sync_to_billing_cycle` read to rule out a transient output error.

## Concrete proof and safety outcome

- The 99A proof records `sync_to_billing_cycle=true` and an empty post-restore comparison.
- `watch-reports/D13-2026-08-15.md` records that the original and evening D13 reads still matched the restored object.
- Night reads at `21:52:51` and `21:54:58` site returned `false`.
- Final reads at `22:11:03` and `22:16:31` site returned `true` without any task-119 write.
- Evidence transcript: `/home/server-manager/slt-evidence/SLT-SETUP-99B-D13-night-blocker-refresh.txt`.
- No setting was changed by this runner.
- `M0` and `M1` remain unset.
- No subscription/action was cancelled or run; no artifact/user/message/evidence was deleted or reassigned.
- The setting-specific blocker cleared externally, but task 119 must remain out of review/done until every independent teardown blocker has cleared.

## Scope notes

- This is a shared-site state blocker, not an attribution finding. No concurrent task or product defect is blamed without evidence.
- Independent blockers remain: task 138 is `todo`; 343 unallowlisted subscription notes fail ownership closure; and external subscription `27527` references teardown product `12112`. The transient `27828` relationship was also removed externally before the final read.
