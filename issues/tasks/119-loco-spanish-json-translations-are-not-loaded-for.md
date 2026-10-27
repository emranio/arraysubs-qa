---
id: 119
title: Loco Spanish JSON translations are not loaded for lazy ArraySubs admin pages
status: closed
priority: high
created: 2026-10-28T00:58:02.041741+06:00
updated: 2026-10-28T01:35:51.143847+06:00
started: 2026-10-28T01:12:16.031666+06:00
completed: 2026-10-28T01:35:51.143847+06:00
tags:
    - bug
    - i18n
    - loco-translate
    - admin
class: standard
---

Fixed and verified. Core now loads entrypoint and lazy-chunk translations through the standard WordPress catalog APIs, including shared core text compiled into Pro. Fresh Loco, bundled, and system catalogs pass in the browser; core also passes without Pro. Both plugins' static PHP/JavaScript translation coverage, pluralization, request-locale formatting, and email defaults were audited and repaired. The historical investigation and rebuild evidence below are retained; see the final fix verification for current results.

## Context

- Active QA task ID, scheduled day, and plan path: N/A. This is the user's focused local translation investigation, not a new scheduled QA cycle.
- Affected subscription IDs and order IDs: N/A; no subscription or order was created or changed.
- WordPress user: ID 1, login `admin`, role `administrator`. User locale temporarily set from empty/default to `es_ES` and restored after testing. Customer IDs: N/A.
- Site/admin route: `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans`.
- Loco route: `http://localhost:10013/wp-admin/admin.php?path=languages%2Floco%2Fplugins%2Farraysubs-es_ES.po&bundle=arraysubs%2Farraysubs.php&domain=arraysubs&page=loco-plugin&action=file-edit`.
- Browser context: isolated `agent-browser --session arraysubs-loco-investigation`, authenticated as admin. WordPress 7.1.3, ArraySubs 2.1.0, ArraySubsPro 1.3.0 active, Loco Translate 2.8.9 installed and activated for this investigation.

## Reproduction

1. In Loco Translate, open ArraySubs, add Spanish (Spain), and select the Custom location `wp-content/languages/loco/plugins/arraysubs-es_ES.po`.
2. Translate `Subscription Plans` to `Planes de suscripción` and `Add new plan` to `Añadir nuevo plan`. Save. Loco reports `OK: PO file saved and MO/JSON files compiled`.
3. Set only admin user 1's locale to `es_ES`; leave the site language unchanged. Open the ArraySubs Plans route.
4. Observe translated PHP navigation/page title but an English `Add new plan` action. Browser `wp.i18n.__("Add new plan", "arraysubs")` returns English and `wp.i18n.hasTranslation("Add new plan", undefined, "arraysubs")` is false.
5. To isolate stale template references, replace `public/build/7536.9f011b82.chunk.js` with the existing `public/build/7536.021eccd6.chunk.js` in the temporary test PO only. Reopen the Loco editor, change the action translation to `Añadir un plan`, and save again. Do not edit the product POT.
6. Confirm Loco generates `arraysubs-es_ES-e44d259ef56e0b45860767fbb791599b.json`, whose `source` is the current compiled chunk and whose locale data includes both Spanish translations. Reload Plans and let its table finish loading.
7. The action remains English. `hasTranslation` is false and the rendered page has no `script[id*=translations]` tags. The PHP label remains Spanish.

Expected: Spanish translations saved through Loco load before the relevant JavaScript executes, including strings in lazy chunks.

Actual: Loco compiles the JSON, but the runtime does not load the chunk catalog. Merely correcting the stale file reference does not resolve it.

## Evidence

All paths below are relative to `qa/issues/`:

- `evidence/loco-script-translations/saved-menu-label.png`
- `evidence/loco-script-translations/saved-button-original-ref.png`
- `evidence/loco-script-translations/saved-button-current-ref.png`
- `evidence/loco-script-translations/runtime-current-ref.png` (fully loaded table; Spanish navigation/title and English action)
- `evidence/loco-script-translations/generated-current-chunk.json`
- `evidence/loco-script-translations/generated-jsx-source.json`
- `evidence/loco-script-translations/runtime.json`
- `evidence/loco-script-translations/inspection.json`

## Original pre-rebuild findings and scope

- `arraysubs/src/Features/MainAdmin/Services/Hooks.php` registers translations for `arraysubs-mainadmin`, whose script is `public/build/admin/mainadmin.js`.
- `arraysubs/src/resources/Main.jsx` dynamically imports `pages/SubscriptionPlans/index.jsx`; webpack loads its hashed chunk outside the WordPress script registry. There is no implementation to load the corresponding chunk JSON before execution.
- The current core POT is also stale: 44 of its 68 distinct compiled JavaScript references point to files no longer present. The original Plans chunk is one example. Loco skips nonexistent script references, so the initial save generated only a catalog keyed to the source JSX path, which is not the runtime script path.
- `arraysubspro/languages/arraysubspro.pot` is dated 2026-10-18 and identifies version 1.2.8 although the installed plugin is 1.3.0. It has source JS/JSX references but no compiled `public/build/*.js` references. This is a separate catalog-generation concern to investigate across Pro entrypoints; Pro translation failure was not browser-tested here.
- Both plugins already use WordPress text domains and `wp_set_script_translations`. This is not evidence that JSON is a nonstandard format, nor that every interface or every multilingual plugin fails.
- Counterexample: PHP menu translations from the same PO/MO work in this browser context. The translated page title is not proof that the JavaScript catalog loaded; the shared layout uses menu-derived text.
- Scope of browser reproduction is the core Plans action with both plugins active. Core-only, storefront, other locales, other translation plugins, and a customer's actual Spanish catalog remain untested.

## Suggested fix plan

1. Generate current translation references from final production assets and ensure they map to the actual scripts shipped by each plugin.
2. Integrate lazy chunk translations with the existing WordPress translation mechanism so each required catalog is loaded before its strings are evaluated. Preserve separate core and Pro domains and translation-plugin overrides.
3. Verify an ordinary registered entrypoint and a lazy page through Loco, then verify core without Pro and a premium script with Pro. Include a production rebuild because chunk names change.

## Cleanup

Loco Translate remains installed and active as requested. Temporary Spanish PO/MO/JSON/PHP-cache/backup files were removed from the runtime language directory after preserving evidence. Admin locale was restored to its original empty/default value. No product source, POT, versions, release changelogs, site language, subscription data, or orders were changed.

## Standards checked

- WordPress JavaScript i18n: https://developer.wordpress.org/block-editor/how-to-guides/internationalization/
- WP-CLI JSON generation: https://developer.wordpress.org/cli/commands/i18n/make-json/
- Loco JSON behavior and filename/reference rules: https://localise.biz/wordpress/plugin/manual/json
- WooCommerce translation workflow: https://woocommerce.com/document/woocommerce-localization/
- WPML compatibility scope for settings/dynamic strings: https://wpml.org/documentation/support/achieving-wpml-compatibility-for-your-themes-and-plugins/

## Rebuild and fresh-template recheck — 2026-10-28

User scope: build both plugins, regenerate both POT files, and recheck the earlier findings. Existing issue #119 remains open for the core lazy-script loading defect.

### Builds and templates

- Core and Pro `npm run build` both completed successfully with no build warnings.
- Ran `npm run make-pot -- --allow-root` in each plugin after its production build. Both succeeded.
- Core template: version 2.1.0, 7,081 strings, 37,777 lines, 70 distinct compiled JS references, zero missing referenced compiled files.
- Pro template: version 1.3.0, 1,780 strings, 8,956 lines, 11 distinct compiled JS references, zero missing referenced compiled files.
- The previous 44 missing core references and Pro 1.2.8 template header are resolved by regeneration.
- POT extraction reports non-fatal string-authoring warnings (111 core, 38 Pro), including missing or conflicting translator comments. Full logs are preserved; these warnings did not prevent generation.
- Added an entry about refreshed translation templates to each plugin's existing changelog draft. No versions or runtime source files changed.

### Browser reproduction with fresh catalogs

Context and affected IDs are unchanged from above: local site, isolated admin browser, WordPress user 1/admin/administrator with temporary es_ES locale; subscriptions/orders/customer IDs N/A; active QA plan and scheduled day N/A.

1. Created a new core Spanish PO from the freshly regenerated template through Loco. Translated `Subscription Plans` to `Planes de suscripción` and `Add new plan` to `Añadir nuevo plan`; saved through Loco.
2. Without manually editing any file reference, Loco generated JSON with source `public/build/7536.021eccd6.chunk.js`, containing both Spanish entries. This confirms the refreshed template fixes the stale-reference generation problem.
3. Opened `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans` and waited for all 35 items to load. The PHP-derived menu/title was Spanish, but the action stayed `Add new plan`.
4. Runtime `wp.i18n.hasTranslation("Add new plan", undefined, "arraysubs")` remained false; translating the string returned English. No script translation tags were rendered. The core lazy-page defect is still reproduced.
5. As a comparison, created a temporary Pro Spanish PO from the regenerated Pro template, translating PHP `Revenue Forecast` and JavaScript `Next 6 months`. Finalized and saved the JS translation as `Próximos 6 meses` in the Loco editor.
6. Loco generated a catalog for the actual `public/build/admin/ai-insights.js` entrypoint. At `http://localhost:10013/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Farraysubs-ai-forecast`, the PHP Analytics menu label and JS Horizon option translated correctly. Selected the six-month option and observed `Próximos 6 meses` in the control.
7. Pro runtime `hasTranslation` was true and `arraysubspro-ai-insights-js-translations` existed. No AI forecast generation was triggered. This verifies this regular Pro entrypoint only, not all Pro interfaces. The WooCommerce parent shell also exposed a permission-message node while the custom report rendered; its behavior was outside this translation check.

Expected/actual conclusion: current templates and ordinary registered-script translation work. The core lazy chunk's catalog still needs to be loaded before its JavaScript evaluates.

### New evidence

All files are under `qa/issues/evidence/loco-script-translations/rebuild-recheck/`:

- `core-build.log`, `pro-build.log`, `core-pot.log`, `pro-pot.log`
- `catalog-inspection.json`
- `core-loco-saved.png`, `core-runtime.png`, `core-runtime.json`
- `pro-loco-saved.png`, `pro-runtime.png`, `pro-runtime.json`
- Copies of the generated core and Pro Spanish JSON catalogs.

### Cleanup after recheck

Restored admin user 1's locale to its original empty/default value and removed only the temporary core and Pro Spanish sample catalogs and backups after copying JSON evidence. Site language was unchanged. Loco Translate remains active. Both production builds and regenerated POT files remain in place.

[[2026-10-28]] Wed 01:12
Fix plan: repair shared translation loading for entrypoints and lazy chunks using standard WordPress APIs; audit PHP and JavaScript strings/domains/placeholders in both plugins; make POT generation reproducible with current production references; build both plugins; verify Loco admin and storefront translations, bundled and system catalog locations, core without Pro, and Pro active. Multilingual file generation/automation is explicitly deferred to the user’s later task.

[[2026-10-28]] Wed 01:20
Implemented shared runtime catalog preloading and build manifest plus source-aware POT reference mapping. Core/Pro JS and PHP audits are complete or in final review; new functional checks cover form validation, pluralization, request locale, and email locale switches. Starting coordinated production builds before integration and browser verification.

## Final fix and verification — 2026-10-28

### Scope and implementation

User authorized all translation readiness fixes in both plugins. The future workflow to generate multiple languages was explicitly deferred and was not created.

- Added shared core `Supports/Translations`, with Pro registering its domain through `arraysubs_translation_plugins`.
- Loads entrypoint and all descendant chunk catalogs before code evaluates, using standard `load_script_textdomain` lookups and filters. Supports Loco Custom, bundled plugin languages, and WordPress system catalogs, as well as core-domain strings compiled into Pro.
- Repairs missing wp-i18n dependencies with native head/footer group metadata. Final catalog validation rejects malformed JSON and safely encodes inline translation data.
- The shared webpack manifest records the actual entry/chunk/module graph. POT generation extracts original PHP/JS/JSX text and translator comments, then maps JavaScript references to the current compiled scripts. Shared core text in Pro gets a core-owned relative reference.
- Audited 326 core and 140 Pro JS/JSX files for static visible text, attributes, domains, dynamic gettext keys, interpolation, plurals, form-library defaults, and request locale. Fixed confirmed gaps, including English-text-dependent checkout detection.
- PHP audit checked 5,078 core and 1,675 Pro gettext calls and fixed confirmed missing UI/REST/gateway-error/job-log/default-reason translations. Stable identifiers, diagnostic protocol data, and custom merchant text remain data.
- Lazy email defaults and a shared option fallback trait prevent stale language reuse across 25 core and four Pro email classes, preserving saved merchant overrides and explicitly blank footers.
- Both changelog drafts updated; no versions bumped.

### Integration and automated verification

- Core implementation merged to main as `8532f16`; Pro as `6d5a403`.
- Both final production builds passed.
- Both POT commands passed. Core: 7,296 messages; Pro: 1,924 messages. All JavaScript references exist. There are no missing-placeholder-comment warnings; remaining extractor notices retain differing source translator comments for shared messages.
- 14 real WordPress catalog assertions passed, including entry/chunk merging, contexts, plurals, malformed JSON, safe encoding, deduplication, bundled handle overrides, shared Pro/core catalogs, and dependency repair.
- 17 head and 17 footer native WP_Scripts rendering assertions passed with correct order/groups and no loader warnings.
- 42 PHP behavior assertions and 559 email default/merchant-override assertions passed.
- Focused JS checks used the installed WordPress i18n runtime and form library for Spanish validation messages, positional placeholders, Russian plural forms, selected request locale, and checkout behavior with Spanish text.
- All changed source files are below 3,000 lines. No PHPCS or dependency installation was performed.

### Real browser verification

Same local site/admin account as the original reproduction. Active QA calendar/day/plan: N/A, focused issue fix. User ID 1 / admin / administrator. No orders or subscriptions were created, paid, cancelled, paused, or otherwise changed.

1. Created small Spanish test POs from the final templates and saved them in Loco Custom. Loco generated the current compiled-file JSON, including a core catalog for Pro's subscription-bundle script.
2. Core Plans route: heading `Planes de suscripción`, action `Añadir un plan`, shared bulk control `Acciones en lote`; `hasTranslation` true and no browser errors.
3. Pro AI Forecast route: Horizon displayed `Próximos seis meses`; dates/numbers followed the Spanish request locale. No AI generation was triggered.
4. Product editor `post.php?post=5905&action=edit`: shared core `Subscription Type` translated as `Tipo de suscripción`, including in the Pro script's own inline catalog.
5. Customer route `/my-account/as-view-subscription/21648/`: opened Pause Subscription input dialog and observed translated `Cancelar`, then dismissed it. Subscription stayed `arraysubs-active`. Read-only subscription 6537 stayed `arraysubs-on-hold`; 23425 was also viewed read-only. Order IDs affected: N/A.
6. Repeated the core Plans test with Pro inactive: Spanish action still displayed; zero Pro scripts loaded.
7. With Loco inactive, repeated core using bundled language files: PHP and lazy JavaScript translations passed.
8. With Loco inactive, repeated core, Pro, and shared core-in-Pro using `wp-content/languages/plugins`: all catalog cases passed.
9. Actual WooCommerce NewSubscriptionEmail instance with a real Spanish MO switched heading Spanish → English → Spanish on the same instance. No email was sent.

All new screenshots, generated-catalog copies, runtime JSON, logs, final catalog inspection, email result, and restored-state evidence are in `qa/issues/evidence/loco-script-translations/fix-verification/`. Screenshots were visually inspected; partial Spanish samples intentionally cover only selected test messages.

### Cleanup and final state

Spanish WordPress core language files were temporarily installed so WordPress accepted the storefront locale, then uninstalled. Temporary plugin POs/MOs/JSON/PHP caches/backups were removed from Custom, bundled, and system test locations. Original empty/default admin and site languages, exact active-plugin order, and active Pro/Loco state were restored. Browser recheck returned the ordinary English interface with no Spanish sample catalog. Test sessions closed. Built assets and final POT files remain available locally.

Subtasks #120, #121, and #122 are closed after integration and verification. This issue is resolved; multi-language file generation remains the user's separate future task.
