# Combined script catalog verification

Scope confirmed 2026-10-29 (Asia/Dhaka): consolidate JavaScript translations to one JSON per language per plugin; test independently of Loco Translate and through its actual editor Save/Sync workflow.

Environment: actual local WordPress http://localhost:10013 (localwp_auto_login admin entry); WP-CLI --allow-root using the existing Local database socket. Current development sources and generated Spanish catalogs are the baseline. Root owns all site/plugin/locale changes and restores them after testing. No remote deployment or external messages.

Execution schedule: immediate manual run on 2026-10-29, ordered tasks on this fresh progress board.

1. Implement and verify combined generation, replacement/cleanup, contexts, plurals, untranslated values, cross-plugin references and source validation.
2. Implement shared runtime and native Loco compile integration; retain WordPress filters/language packs and preload before entry/lazy module execution.
3. Browser verification: core alone with Loco disabled, core + Pro with Loco disabled, core + Pro with Loco enabled, Loco Save/Sync + actual changed translation visible in core and premium UI; reload and lazy route coverage; untranslated/English fallback; verify exactly one JSON per locale/plugin and restore original test text/settings/plugins/user locale.

Evidence and final report live here. Existing qa/issues tasks are preserved; new reproducible defects must include this plan/task/day, IDs or N/A, exact URL/context, steps, expected/actual result, evidence and counterexamples.
