# Combined JavaScript catalogs — completed verification

Completed 2026-10-29 (Asia/Dhaka). Scope: one JavaScript JSON catalog per language per plugin, independent operation and actual Loco Translate compatibility. Plan: [plan.md](plan.md); manual schedule: [schedule.md](schedule.md); fresh progress tasks #1–4. Existing QA issue tasks were preserved.

## Result

- Core: 79 Spanish JSON files became `arraysubs/languages/arraysubs-es_ES.json`, containing all 3,831 original unique JavaScript messages.
- Pro: 17 became `arraysubspro/languages/arraysubspro-es_ES.json`, containing all 638 original unique messages.
- Final dictionaries match the original dictionaries exactly, including contexts and plural forms. Old hashed files and task-created Loco backup PO files are absent. PHP PO/MO/l10n.php catalogs remain available.
- Both commands share the combined compiler. Runtime loads each domain once before consuming scripts, supports lazy modules and shared core text in Pro, preserves standard language packs and sparse custom overrides, and safely handles malformed files and empty translations.
- Loco uses its native single-JSON compiler, including its two-argument cleanup callback. Clearing the final JavaScript translation removes stale JSON without changing global Loco settings.

## Verification

| Check | Result / evidence |
| --- | --- |
| Node translation suite | 60/60 passed; [generation log](evidence/generation-tests.txt). Includes shipped-only messages, partial catalogs, context/plurals, template guards, per-locale cleanup and publication rollback. |
| Isolated real WordPress/Loco PHP matrix | 154 assertions passed across core-only / core+Pro, Loco enabled / disabled, language packs, head/footer rendering, malformed custom recovery and final-JS cleanup; [runtime log](evidence/runtime-tests.txt). Temporary fixture roots and process-only configuration were used. |
| Integrated live PHP checks | Combined regression 26, existing script regression 26, native head 17 and footer 17 assertions passed after copying the final implementation into the live checkout. |
| Core browser, Pro/Loco/TranslatePress disabled | Spanish Integrations page and lazy controls translated, no Pro scripts, combined data emitted once; [observations](evidence/core-only-no-loco.json), [screenshot](evidence/core-only-no-loco.png). |
| Core+Pro browser, Loco/TranslatePress disabled | Actual WooCommerce revenue screen showed premium Spanish metrics; eight premium entry scripts shared one combined payload; [observations](evidence/pro-no-loco-revenue.json), [screenshot](evidence/pro-no-loco-revenue.png). |
| Native Loco core editor Save + Sync | Changed `Integrations` to `Integraciones QA combinado`, saved and synced against the shipped template, then verified the changed heading in the reloaded app and exactly one JSON; [editor](evidence/loco-core-saved.png), [sync](evidence/loco-core-sync.png), [applied](evidence/loco-core-applied.json). |
| Native Loco Pro editor Save + Sync | Changed `Total Subs Purchase Amount` to `Compras de suscripciones QA combinado`, saved and synced, then verified the actual premium revenue metric and one combined emission; [editor](evidence/loco-pro-saved.png), [sync](evidence/loco-pro-sync.png), [applied](evidence/loco-pro-applied.json), [screen](evidence/loco-pro-applied.png). |
| English fallback | Premium metric returned to English without Spanish leakage; [observations](evidence/english-fallback.json), [screenshot](evidence/english-fallback.png). |
| Frontend account, Loco/TranslatePress disabled | Authenticated account page and Memberships list loaded in Spanish. Core `Upgrade` and Pro `Disable Auto-Renewal` resolved in their own domains; two domains emitted once each; [observations](evidence/account-pro-no-loco.json), [screen](evidence/account-pro-no-loco.png). |
| Actual distribution preparation | Ran each existing plugin-make script against an isolated filesystem snapshot. Both outputs include the single matching JSON, PO/MO/PHP and manifest; core includes the exact tested runtime. No backup/test files in candidates; [package check](evidence/package-verification.json). |

Browser environment: actual local site `http://localhost:10013`, admin user #1 (`admin`, administrator/demo_member), isolated agent-browser sessions `combined-admin` and `combined-account`. Routes: `/wp-admin/admin.php?page=arraysubs-mainadmin#/integrations`, WooCommerce `/analytics/revenue`, each plugin's author-location Loco PO editor, and `/my-account/`. No subscription/order actions or payments were submitted. Account screenshots use the authenticated administrator's own account; no separate customer-role acceptance is claimed.

Browser error collection was empty. PHP fixture runs skipped unrelated Elementor bootstrap deprecations; real browser runs retained Elementor. The initial direct WP-CLI connection failed because Local uses a Unix socket; subsequent commands used the documented existing socket with --allow-root and no environment changes.

## Restoration and release readiness

Original translation text was restored from the pre-test PO copies and both plugins were recompiled. Final dictionaries exactly match all original translations; [catalog verification](evidence/final-catalogs.json). The original active-plugin array, site language and administrator language were restored exactly; [restoration check](evidence/restoration-check.json). Temporary Loco PO backups were moved out of the plugin trees. No business settings, carts, subscriptions or orders were intentionally modified.

Both existing changelog drafts were read back: each changed exactly one existing entry and retained every other entry. Architecture/translation documentation and the canonical catalog instruction were updated. All touched source files are below 3,000 lines; [line counts](evidence/source-line-counts.txt). Whitespace checks passed. Independent code/packaging review has no outstanding findings.

This change is ready for release packaging. No version bump, release publication, remote deployment, Composer installation or PHPCS run was performed. Existing unrelated development changes were preserved; only this task's changes were copied from isolated worktrees into the current development checkouts.
