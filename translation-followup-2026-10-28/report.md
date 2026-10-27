# Loco Translate Spanish catalog fix

Date: 2026-10-28. Site: http://localhost:10013. Issue #135.
Final scope: leave the existing ArraySubs translation UI unchanged; install
complete Spanish catalogs and fix the shared npm translation command.

## Verified result

| Plugin | Loco Spanish progress | Strings | Pending | Script JSON catalogs |
| --- | --- | --- | --- | --- |
| ArraySubs | 100% | 7,375 | 0 | 77 |
| ArraySubs Pro | 100% | 1,945 | 0 | 29 |

Both catalogs open in the actual Loco editor. Searching and selecting
“Subscribe Now” shows “Suscríbete ahora” in both domains. PO, MO and current-path
JavaScript JSON files are installed in each plugin's languages directory.
All template entries have structurally valid complete translations, and all
referenced compiled JavaScript paths exist. Pro's template identifies 1.3.0.

Before the fix the directories contained only POT templates. Earlier small
integration/test catalogs were under the ignored translation cache, not installed
in the plugins. Loco's “No translations found” message was therefore accurate.

## Script fixes

- Reject wrong-domain templates and missing compiled script references before AI calls.
- Explain the exact number of protected brand occurrences in repair requests,
  addressing the repeated-brand failures encountered during the full run.
- Keep custom/staging output reports separate from the installed-catalog report.
- Clearly distinguish full installation, sample-only, dry-run and custom output,
  including the exact output folder and how to open an installed PO in Loco.
- Preserve the existing model, translation policy and cache identities.

The shared command remains `npm run plugin:translate`, run inside either plugin.
Spanish remains the default; no other language was generated in this cycle.

## Validation

`npm run test:translate`: 51 tests passed, zero failures, with no live AI calls
in the test suite. This includes real CLI file installation from a reviewed PO,
stale/wrong-domain preflight, repeated-brand repair and report-isolation tests.

The fixed real command was rerun in both plugins. It reused all 7,375/1,945
translations, installed every output format successfully, and reported zero
additional SDK attempts. PHP Gettext returned the Spanish subscribe and add-plan
labels; the actual Spanish bundle product displayed “Suscríbete ahora”.

The existing admin UI, source settings, user locale and product configuration are
unchanged. Temporary default-label UI experiments were reverted and their test
override cleared. No purchase, payment, final cancellation or email was submitted.
Generated language files and translation memory remain ignored by Git under the
existing repository policy; they are installed locally and included when the
plugin is packaged from this populated languages directory.

## Browser proof

- loco-before.png: original missing-catalog message.
- loco-core-spanish.png and loco-pro-spanish.png: both Loco overview results.
- loco-core-editor.png and loco-pro-editor.png: editable source/Spanish text.
