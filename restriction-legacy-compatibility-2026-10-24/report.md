# Restriction legacy compatibility QA — 2026-10-24

Result: passed on the local WordPress installation serving this checkout (`http://localhost:10013`). Existing per-post/CPT, Gutenberg, and Elementor settings retain their original access decisions and messages. No bulk database rewrite is required. Opening content does not migrate records; unrelated saves preserve the previous contract. Authors must explicitly review and apply the current rules to convert them.

## Behavior preserved

- Original login-only overrides still ignore dormant subscription, role, purchase, and feature conditions.
- Original logged-in/logged-out visibility, administrator bypass settings, fallback shortcodes, and intentionally empty Gutenberg/Elementor visibility output remain intact.
- Old custom messages without a switch remain custom. Both transitional inverse and positive message flags are honored; explicit settings take priority.
- Historical direct-meta nested condition trees remain exact and editable through a preservation/review flow. Conversion requires rebuilding flat conditions explicitly.
- Cached old editor payloads with original mode fields retain the old contract when saved. New configurations carry an explicit version 2 marker and use login outside ALL/ANY.
- Existing public shortcodes retain login-only semantics by default. Newly versioned integrations pass `access_version=2` for the prerequisite behavior.

Unmarked saved records can be ambiguous because Gutenberg omitted default-valued fields. Older semantics take priority when no explicit new marker or distinguishing message field exists. New and converted records serialize their version rather than depending on a default that could be omitted.

## Verification

| Check | Pro | Core only |
| --- | ---: | ---: |
| Actual WordPress compatibility matrix, including native Elementor render and raw serialized Gutenberg | 589/589 | 589/589 |
| Current rule behavior regression matrix | 332/332 | 332/332 |
| Browser storefront assertions, three surfaces and four identities | 288/288 | 120/120 |

Total: **2,250 runtime and storefront assertions passed**, plus real authoring/save/reload checks and 44 focused JavaScript compatibility checks. Runtime core isolation used WP-CLI `--skip-plugins=arraysubspro`; the browser core pass temporarily deactivated Pro and restored its original activation position immediately afterward.

Real editor checks covered preserving old login-only settings and rich text, canceling conversion review, explicit conversion and persisted version markers, exact nested direct-meta tree preservation/rebuilding, guests-only visibility and fallbacks, and newly inserted empty Gutenberg blocks. See [React authoring details](react-authoring-report.md) and [Elementor authoring details](evidence/elementor-authoring.md).

The complete [production build](evidence/full-build.txt) passed at 03:47:49 UTC. [PHP syntax](evidence/php-syntax.txt), diff whitespace, and [source line counts](evidence/source-line-counts.txt) passed. No touched source file exceeds 3000 lines. Storefront browser error logs were empty. Existing Elementor PHP nullable-parameter deprecation output is unrelated to restriction behavior.

## Defects fixed during verification

- QA issue100: actual Elementor display filtering removed access conditions from unversioned transitional settings, exposing protected content. Runtime now preserves actually saved fields across all formats while retaining resolved dynamic values. Both native rendering tests and the real guest browser passed after the fix. [Before](evidence/transitional-custom-guest-elementor-failure.png), [after](evidence/transitional-custom-guest-elementor-fixed.png).
- QA issue101: Elementor conversion updated the model but left the visible custom-message switch stale. Native control refresh now keeps the switch, model, and editor visibility aligned; publish/reload verified. Both issues are closed in the existing issues board.

No phpcs, Composer installation, release, version assignment, push, or remote deployment was performed for this task. Unrelated concurrent Subscription Plans work and QA issues were preserved.

## Cleanup

Cleanup passed: all six disposable pages and customer440 removed; original shared default, shop visibility and active plugin order restored; three scoped authentication tokens invalidated; private token/state/backup files removed. All owned browser sessions closed; unrelated Plans sessions remained open. Core and Pro load successfully after restoration. See [cleanup readback](evidence/cleanup.json).
