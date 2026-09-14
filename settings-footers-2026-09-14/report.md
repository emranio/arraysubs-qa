# Settings footer verification — passed

Tested on 2026-09-14 against **http://localhost:10013**, using admin user ID 1 in an isolated agent-browser session. Scope and schedule: [plan.md](plan.md), QA progress task #1.

All 14 affected editor routes now use the same footer as Settings → General: the existing save action, **Discard Changes**, **Easy Setup & Import/Export**, and the shared **Settings last saved** timestamp. The timestamp uses the WordPress date format and timezone. Primary actions and focus states retain the WordPress admin accent color.

## Implementation

- Core owns the shared `SettingsActionFooter` and saved-time state. General Settings uses the same component as the requested editors.
- Both Profile Builder tabs restore their latest saved configuration, including fields, menu order, labels, switches, avatar settings, and validation state.
- All nine Member Access rule tabs restore their latest saved rules. Rule and Member Styling saves return the stored, sanitized data used by subsequent Discard actions.
- Login Limit saves its global settings and rules sequentially because both write the same settings option. Each successful write updates the corresponding discard baseline.
- Pro's Profile Fields and My Account endpoints return the existing shared saved-time metadata. No premium implementation was duplicated in core.
- Save buttons use the shared spinner component. Save and Discard are disabled while a request is pending.

## Browser results

| Editor | Verified behavior | Result |
| --- | --- | --- |
| Profile Form | Invalid field validation; discard new field and avatar changes; save/reload avatar and custom field; remove saved field and discard; saved timestamp advances | Pass |
| My Account | Invalid custom item validation; discard added item; save/reload renamed default item; discard subsequent label changes; keyboard reorder and discard | Pass |
| Cart Info Editor | Change all three switches; discard; save/reload; discard back to latest save | Pass |
| Member Styling | Master switch, nested conditions, body classes, CSS, duplicate rules, disabled-rule save/reload, discard to saved CSS | Pass |
| Role Mapping | Add nested condition/rule, discard, save disabled rule, rename/duplicate/discard, reload | Pass |
| Discount | Same rule-state checks | Pass |
| Shop Access | Same rule-state checks | Pass |
| URL | Same rule-state checks; real offline failed-save recovery | Pass |
| Post Types | Same rule-state checks | Pass |
| Downloads | Same rule-state checks | Pass |
| Comments | Same rule-state checks | Pass |
| Purchase Limit | Same rule-state checks | Pass |
| Login Limit | Same rule-state checks; change global session count and rule together, discard both, save both and reload | Pass |
| Retention Flow | Add eighth reason; discard new reason and hidden offer state; save/reload; remove saved reason and discard; save/reload empty list; discard back to empty list | Pass |

Every editor's Easy Setup link was opened successfully. Loading indicators and disabled footer actions were observed during real saves. An intentional offline save retained its draft, left the saved timestamp unchanged, showed an error, and allowed Discard to restore the saved version. The failed draft did not persist after reconnecting and reloading.

Footer geometry and screenshots were checked at **390, 768, and 1440 pixels**, covering Settings, both Profile Builder tabs, Cart Info Editor, Member Styling, Retention Flow, and Member Access. Controls and saved-time text stay within the footer and viewport. Existing mobile header/subtitle layout problems tracked under QA issue **#26** remain outside this footer change; its issue was preserved.

The initial browser had stale pre-build lazy chunks; a fresh page load resolved that. Subsequent Member Access save/reload runs introduced no new uncaught browser errors. The CLI retains old error history, so checks compared new errors against the recorded history.

During evidence review, the first Retention row counter was found to omit non-semantic accessibility nodes. Its list assertions were replaced with direct DOM row counts and the complete Retention test was rerun successfully. Final evidence verifies eight saved rows, zero saved rows after deleting all, and seven original rows after cleanup.

## Data restoration

The original settings, saved-time option, separate profile-field/avatar/menu configuration, and rewrite rules were backed up before test saves. Rules created for testing were disabled. No test customer, subscription, order, or product was created.

The comparison before cleanup found changes only in tested settings groups; Store Credit values were unchanged (only associative-key ordering differed). The captured option values and autoload flags were restored exactly. All 14 editor routes were reloaded to confirm temporary test data was absent and the original timestamp returned. After the corrected Retention rerun, its original seven reasons were restored and checked again.

A fresh WP-CLI request after browser reloads confirmed every captured option still matched the backup byte-for-byte. Audit history naturally retains the test saves; this was a targeted settings restoration, not a whole-database rollback.

## Supporting checks and evidence

- Production assets compiled successfully; PHP syntax checks passed for all four changed controllers. No PHPCS or dependency installation was run.
- Both plugin diffs passed whitespace checks; all 21 changed source files are under 3000 lines (largest: 1310).
- Core boot and its three read-only settings routes passed with Pro skipped for one WP-CLI invocation. The site's active-plugin state was not changed. This supplements the real browser tests, not a browser test of a deactivated-Pro site.
- Existing repository changes and QA issues were preserved. No new application defect was found in the requested footer/state flows.

Evidence: [assertion log](evidence/checks.jsonl), [restoration after reload](evidence/restoration-after-reload.json), [changed settings groups](evidence/changed-settings-groups.json), [core-only checks](evidence/core-only.json), [build summary](evidence/build-summary.txt).

Screenshots: [Profile Form desktop](evidence/profile-fields-1440.png), [My Account desktop](evidence/myaccount-1440.png), [Cart desktop](evidence/cart-1440.png), [Member Styling desktop](evidence/styling-1440.png), [Member Access desktop](evidence/member-access-1440.png), [Retention restored](evidence/restored-retention-flow.png), [Retention empty list](evidence/retention-empty-list.png), [phone footer](evidence/profile-fields-390.png), [tablet footer](evidence/cart-768.png), [failed save](evidence/failed-save-preserves-draft.png).
