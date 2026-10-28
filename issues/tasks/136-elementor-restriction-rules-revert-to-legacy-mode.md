---
id: 136
title: Elementor restriction rules revert to legacy mode when the editor loads an autosave draft
status: closed
priority: medium
created: 2026-10-28T06:07:59.90069+06:00
updated: 2026-10-28T12:35:33.199953+06:00
started: 2026-10-28T12:25:47.366831+06:00
completed: 2026-10-28T12:35:33.199953+06:00
tags:
    - bug
    - elementor
    - content-gate
class: standard
---

Found during the per-field translate icon QA (2026-10-28); pre-existing, not caused by that change.

- Active QA task ID / day / plan: N/A (ad-hoc QA for the translate icon; qa/translatepress-2026-10-28/report.md follow-up section)
- Affected subscription ID(s) / order ID(s): N/A
- Affected user: admin (ID 1, administrator)
- URL: http://localhost:10013/wp-admin/post.php?post=19967&action=elementor (Elementor 4.3.2, container c06d4f1 in legacy format)

Steps:
1. Select the container → Advanced → ArraySubs Content Restrictions → click "Convert to current rules", enable a custom message and type one.
2. Keep editing until Elementor's autosave writes revision 20047 ("Draft saved…").
3. Reload the editor (Elementor loads the autosave draft).

Expected: the draft keeps the converted format (access_version "2"): "Who can access it", Use custom message, Custom message and the Translate message controls stay visible.

Actual: the container is back in legacy mode: settings.get('arraysubs_access_version') === '1', the compatibility notice "Original access rules are preserved" shows, and the message controls are hidden, although revision 20047's _elementor_data holds access_version 2 and the message.

Cause (code reading): src/Features/ElementorIntegration/assets/editor.js ensureAccessFormat() recomputes the format from window.ArraySubsElementor.savedSettings, which RestrictionSettings::savedMap() builds from the PUBLISHED post's _elementor_data (get_post_meta(19967, '_elementor_data')), never from the autosave the editor actually loaded; isLegacyRaw({arraysubs_enable:'yes'}) is true, so settings.set('arraysubs_access_version','1',{silent:true}) overrides the draft's '2'.

Evidence: built-in browser JS after reload {accessVersion:'1', compatVisible:true, customSwitchVisible:false, messageVisible:false, savedSettingsForId:{arraysubs_enable:'yes'}, docIsAutosave:{revisions:'20047', lastEdited:'Draft saved on Oct 28, 05:42 by admin'}}; WP-CLI showed revision 20047 _elementor_data with access_version 2. Suggested fix: build savedMap() from Elementor's get_doc_or_auto_save() document for the current user, or skip the legacy downgrade when the loaded settings already carry access_version 2.

## Fix (2026-10-28)

Changed (core `arraysubs`, no build step; `editor.js` is enqueued raw):

- `src/Features/ElementorIntegration/Services/RestrictionSettings.php`: `savedMap()` now reads `_elementor_data` of the post Elementor actually loads, resolved through `\Elementor\Plugin::$instance->documents->get_doc_or_auto_save($post_id, get_current_user_id())` (the current user's newer autosave when one exists, otherwise the post; falls back to the post id without Elementor).
- `src/Features/ElementorIntegration/assets/editor.js` `ensureAccessFormat()`: a loaded element whose `arraysubs_access_version` is already >= 2 is never downgraded to legacy; a loaded `1` stays legacy; otherwise `isLegacyRaw()` decides as before, so genuinely old data is still detected.
- `changelog.draft.txt`: `fixed:` entry added.

Verification (agent-browser headed session, admin ID 1, http://localhost:10013/wp-admin/post.php?post=19967&action=elementor, Elementor 4.3.2):

1. Pre-check with the stale autosave 20047 still present: `window.ArraySubsElementor.savedSettings.c06d4f1` now mirrored the autosave (`arraysubs_enable`, `arraysubs_login_required`, `arraysubs_status`, `arraysubs_use_default_message`) instead of the published `{arraysubs_enable:'yes'}`; version resolved `2`.
2. Discarded 20047 (`wp post delete 20047 --force`, same as Elementor's discard) and reloaded: legacy baseline confirmed, `arraysubs_access_version === '1'`, notice "Original access rules are preserved" visible, message controls hidden (evidence/136-elementor-autosave-legacy/01-legacy-before-convert.png).
3. Clicked "Convert to current rules", Use custom message = Yes, typed "Members only: QA-136 autosave check". Elementor autosaved at 12:31 ("Draft saved on Oct 28, 12:31 by admin", revision 23786). WP-CLI: revision 23786 `_elementor_data` = `{"arraysubs_enable":"yes","arraysubs_access_version":"2","arraysubs_login_required":"yes","arraysubs_custom_message_enabled":"yes","arraysubs_message":"Members only: QA-136 autosave check"}` (02-converted-before-reload.png).
4. Reloaded the editor (autosave 23786 loaded): `{accessVersion:'2', compatVisible:false, headingVisible:true, customSwitchVisible:true, customSwitchChecked:true, messageVisible:true, translateVisible:true, message:'Members only: QA-136 autosave check'}`; the saved map carried the autosave's fields (03-after-reload-still-converted.png). Before the fix this reload showed `accessVersion:'1'` and hid the controls.
5. Discarded the draft (`wp post delete 23786 --force`). Page 19967 untouched: `post_modified` 2026-10-24 03:19:48, `_elementor_data` md5 2e16ac6dc374927053f45d2314b625c1 (`[{"id":"c06d4f1","elType":"container","settings":{"arraysubs_enable":"yes"},"elements":[],"isInner":false}]`), not published. Reload after discard shows the legacy baseline again, `accessVersion:'1'`, no autosave (04-after-discard-legacy-again.png).

Tests: no file under `arraysubs/workflow-scripts/tests/` references Elementor; nearest test `translation-field-api.php` passes (50 assertions). A standalone PHP check of `savedMap()` (autosave resolved for the current user, published fallback without an autosave or without Elementor, capability gate, unchanged legacy detection) and a Node check of the version decision table (9 cases) both pass. Scope note: the stale QA autosave 20047 from 2026-10-24 was discarded as part of this run; nothing else on the page changed.
