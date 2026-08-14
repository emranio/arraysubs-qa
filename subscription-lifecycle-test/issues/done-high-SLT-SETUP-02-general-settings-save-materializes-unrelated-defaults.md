# Saving General settings materializes unrelated defaults in `arraysubs_settings`

- **Severity:** medium (configuration persistence regression; no behavioural change observed because the inserted values equal current UI defaults)
- **Found:** 2026-08-02, D0
- **Status:** resolved and browser-verified 2026-08-02
- **Originating QA progress task:** board task `#11`, `SLT-SETUP-02`, stage/window day D0 (`foundation`)
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/011-slt-setup-02-apply-and-record-the-four-window-wide.md`

## Affected objects

| | |
|---|---|
| Subscription IDs | N/A |
| Order IDs | N/A |
| Product IDs | N/A |
| WordPress user | ID `1`, login `admin`, email `admin@mirror-help.arrayhash.com`, role `administrator` |
| Gateway | N/A |
| Checkout type | N/A |
| Browser/user context | `admin-SLT-SETUP-02`, Headless Chrome 149.0.0.0, WordPress administrator |
| Exact route | `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general` |
| Non-default settings in play | Intended changes only: renewal sync ON to OFF; early renew OFF to ON; reactivation OFF to ON. The later Skip and Pause save enabled pause and customer pause as separately planned. |

## Expected result

Saving the General page after changing only `renewals.sync_to_billing_cycle`,
`customer_actions.allow_early_renew`, and `customer_actions.allow_reactivation` changes only those three
stored values. All unrelated keys remain byte-identical to the D0 baseline, including keys that were absent.

## Actual result

The save persisted six unrelated UI defaults that were absent from the D0 option blob:

| Path | Before | After |
|---|---:|---:|
| `audits.job_log_mode` | absent / `null` | `"all"` |
| `audits.job_log_retention_days` | absent / `null` | `30` |
| `emails.renewal_reminder_rate_limit` | absent / `null` | `0` |
| `renewals.queue_concurrent_batches` | absent / `null` | `2` |
| `renewals.spread_window_hours` | absent / `null` | `6` |
| `renewals.sweep_batch_size` | absent / `null` | `50` |

The three intended General-page values and two intended Skip and Pause values also changed correctly. No
browser errors, failed requests, or error-level console messages accompanied the save.

## Reproduction steps

1. From the WordPress root, save the untouched option blob with
   `wp option get arraysubs_settings --format=json --allow-root`.
2. As administrator, open the exact General settings route above.
3. Turn **Sync Renewals to Next Billing Cycle** off, **Allow Reactivation** on, and **Allow Early Renew** on.
4. Do not touch the Audit Logs, Renewal Performance, or reminder-rate fields.
5. Click **Save Settings**.
6. Fetch `arraysubs_settings` again and compare scalar paths against the before blob.
7. Observe the six unrelated paths above were inserted together with the three intended changes.

## Concrete proof

- Before blob: `/home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json`
- After blob before isolation cleanup: `/home/server-manager/slt-evidence/SLT-SETUP-02-arraysubs_settings-before-cleanup.json`
- Exact scalar-path diff: `/home/server-manager/slt-evidence/SLT-SETUP-02-settings-diff.jsonl`
- Browser screenshot after the General save: `/home/server-manager/slt-evidence/SLT-SETUP-02-01-general-after.png`
- Snapshot observation: only the three intended switches were changed; the unrelated controls retained their displayed defaults.
- Browser errors: none. Console contained only JQMIGRATE and existing FormBuilder debug logs.

## Scope notes and counterexamples

- The values inserted are the same values the UI already displayed, so no immediate scheduling or email
  behaviour changed in this run. The defect is that merely saving unrelated fields changes the persisted
  contract and makes byte-identical import/export and settings-diff QA fail.
- The D0 baseline already stored the pause duration/reason fields, and the Skip and Pause save did not add
  additional unrelated scalar paths beyond the planned pause booleans.
- The six unintended paths were removed with targeted `wp option patch delete` commands after evidence was
  captured so the lifecycle window could continue on the promised isolated baseline. The issue is therefore
  reproducible from the saved before/after evidence even though the live option was cleaned up.

## Suggested resolution

Submit only dirty fields, or merge submitted values without materializing untouched UI defaults. Add a
regression test that compares the option blob before and after toggling one General-page setting and asserts
that unrelated absent keys stay absent.

## Resolution and verification

- `arraysubs/src/resources/pages/Settings/GeneralSettings.jsx` now compares the submitted flat form values
  with the values originally loaded and sends only changed keys to the existing partial-save REST contract.
- Rebuilt the production assets with `npm run build`.
- Removed the six QA-created default keys again, toggled **Allow Early Renew** off and saved through the real
  admin page, then queried the raw `arraysubs_settings` option. Only
  `customer_actions.allow_early_renew=false` changed; all six unrelated keys remained absent.
- Toggled **Allow Early Renew** on and saved again. The intended window setting returned to `true`, all six
  unrelated keys remained absent, the success toast appeared, and the browser reported no errors.
- Browser evidence: `/home/server-manager/slt-evidence/SLT-ISSUE-120-partial-save.png`.

## Fresh closure verification — 2026-08-14

- Re-read the originating task, raw D0 evidence contract, current General form, shared settings helpers, REST sanitizer, recursive merge, and Pro-owned settings extensions. The fix is already committed and the report's unresolved filename was stale; no additional product edit was warranted.
- The frontend dirty-value comparison prevents runtime defaults returned by `GET /settings/data` from entering an untouched save. The REST controller independently sanitizes a partial payload, filters generated siblings back to the exact submitted shape, and recursively merges that shape into the raw option. This preserves validation and addon sanitizers without materializing absent siblings.
- The current raw option supplied the exact original regression state: all six reported keys were absent while the General UI displayed their effective defaults. The pre-test option hash was `1de46b4b09c69bf6cd24a0c8ae2cd9788055f7131038acd3ed49088bca80bdef`.
- Through the real administrator page, changed only **Allow Early Renew** from off to on. The captured successful `POST /wp-json/arraysubs/v1/settings/save` body was exactly `{"settings":{"customer_actions":{"allow_early_renew":true}}}`.
- The raw post-save diff contained exactly `customer_actions.allow_early_renew`; all six absent keys remained absent. The success toast rendered and the browser error buffer was empty.
- Changed only **Allow Early Renew** back to off. The successful restore request body was exactly `{"settings":{"customer_actions":{"allow_early_renew":false}}}`. The final option was byte-for-byte/serialization-identical to the backup with the same SHA-256 and zero changed paths.
- Mailpit stayed at `0dC3gOmvPwldM6icq2bIbO`. The two exact settings audit notes created by the on/off proof (`26598`, `26599`) and the temporary backup option were removed after strict identity checks; no other notes or settings were deleted.
- Browser evidence: `/home/server-manager/slt-evidence/HIGH-SLT-SETUP-02-before.png`, `/home/server-manager/slt-evidence/HIGH-SLT-SETUP-02-one-field-save.png`, and `/home/server-manager/slt-evidence/HIGH-SLT-SETUP-02-restored.png`.
- Per the QA issue-fix workflow, PHPCS and lint commands were not run.
