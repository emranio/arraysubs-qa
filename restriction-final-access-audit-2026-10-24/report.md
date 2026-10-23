# Final restriction access audit — 2026-10-24

Result: five confirmed issues were fixed and verified. No remaining access leak or ordinary-save audience change was found in the tested cases.

Scope: Gutenberg blocks, Elementor elements, and per-post/CPT restrictions, with particular attention to old settings, ordinary editor saves, and Match Any. Tests used the actual local WordPress site at `http://localhost:10013` serving this checkout. The fresh plan is [plan.md](plan.md).

## Findings and fixes

| Issue | Finding | Resulting behavior |
| --- | --- | --- |
| [104](../issues/tasks/104-legacy-per-post-boolean-normalization-changes-the.md) | Normalizing imported old per-post boolean values could change the allowed audience. | Original boolean interpretation is retained for legacy records, including after Apply/Save. |
| [105](../issues/tasks/105-unsupported-subscription-statuses-can-unlock-match.md) | Unknown/wildcard statuses could produce an unrestricted subscription query and satisfy Match Any. | Unsupported statuses cannot grant access; valid and registered custom subscription statuses remain supported. |
| [106](../issues/tasks/106-elementor-unrelated-heading-save-can-silently-turn.md) | Elementor could prune the only format marker during an unrelated Heading save and reinterpret a current rule as legacy login-only. | Every saved element receives the correct version before defaults are pruned, including unopened panels and nested containers. |
| [107](../issues/tasks/107-old-gutenberg-raw-attributes-bypass-their-original.md) | Gutenberg schema defaults, imported JSON types, and condition normalization could change original access decisions. | Original schema interpretation and exact condition inputs are retained. Authorized editor reads normalize response data without writing stored content. |
| [108](../issues/tasks/108-ordinary-save-can-erase-an-effective-legacy.md) | An inactive canonical per-post config could hide an effective older direct-meta rule; saving could erase the active restriction. | The editor loads and retains the effective old rule, preserving variation/parent precedence and intentional disabling. |

Ordinary opening and saving retains version 1 behavior. Adopting current semantics requires the explicit review/conversion action. For current rules, login is a prerequisite outside ALL/ANY, so logging in cannot satisfy Match Any. Administrator bypass remains separately controlled.

The invalid-status change is an intentional access-leak fix: malformed filters now deny access instead of broadening a subscription query. It is not an editor-save side effect.

## Completed automated and runtime checks

| Coverage | Result | Evidence |
| --- | --- | --- |
| Shared backend/per-post differential, including original HEAD behavior, classic/REST/cached payloads, nested legacy trees and variation precedence | 401/401 with Pro; 401/401 core only | `evidence/runtime-backend-{pro,core}.txt` |
| Gutenberg original HEAD schema/gate differential, 41 legacy cases and four visitor roles | 331/331 with Pro; 331/331 core only | `evidence/gutenberg-head-server-differential*.json` |
| Actual WordPress block parsing, serialization and editor normalization | 379/379 | `evidence/gutenberg-official-parser-roundtrips.json` |
| Authorized editor REST/preload reads, posts/revisions/patterns/templates/widgets, nested requests, parser cleanup and unchanged database hashes | 37/37 | `evidence/gutenberg-edit-rest-readonly.json` |
| Native Elementor rendering, original HEAD comparisons, nesting, duplicate IDs, output buffers and warmed-cache isolation | 433/433 | `evidence/elementor-native-runtime.txt` |
| Full legacy compatibility regression | 589/589 with Pro; 589/589 core only | `evidence/legacy-regression-final-{pro,core}.txt` |
| Unified conditions and default/custom message regression | 332/332 with Pro; 332/332 core only | `evidence/unified-regression-final-{pro,core}.txt` |

Core-only checks used WP-CLI's per-process plugin exclusion; the installed plugin activation list was not toggled for these runs. Runtime assertions supplement the real browser tests below.

## Browser verification

The root matrix passed 164/164 visitor checks across 41 four-actor matrices (`evidence/browser-audience-summary.json`), separately from the 112 Elementor checks below.

All browser interactions used agent-browser with separate guest, nonmember, qualifying member, and administrator sessions. Authoring checks compare the same fixture before and after real editor open, ordinary save, and reload. The administrator override was explicitly off where needed to detect accidental bypass.

Elementor completed 14 Heading-only round trips with the restriction panel unopened, with 112 passing before/after visitor checks. Cases include old ANY, legacy login-only, guest visibility, explicit off values, inverse custom-message flags, unusual version values and a nested child restriction. Switching Use custom message off and reopening also retained the choice and original text. See [Elementor details](evidence/elementor-final-audit.md).

Per-post checks include Gutenberg's per-content dialog, WooCommerce's classic product editor, title-only saves, unchanged Apply/Save, imported boolean values and the effective direct-meta fallback. Cross-surface checks also cover invalid/wildcard statuses, current ANY with no matching clause, current ANY with one matching clause, old login-only behavior and explicit administrator bypass.

Gutenberg's ordinary native ANY record preserved its member-only audience after both a title-only save and a body edit/save/reload. The body text was edited through WordPress's Code editor after the browser iframe-selection tool stalled; only paragraph text changed. The original condition contract remained intact. An old guest-only visibility rule also retained the same audience after title and body edits, Save and reload: the guest saw protected content, while all three logged-in roles saw the original fallback message. Evidence: `evidence/author_gutenberg-{baseline,title-save-final,body-save-final,visibility-baseline,visibility-saved}-audience.json` and `evidence/gutenberg-visibility-fallback-check.json`.

## Build and source verification

The core production build passed (`evidence/production-build.log`). Nine relevant PHP source files passed syntax checks. Scoped diff whitespace checks passed. All 83 currently modified plugin source files are below 3000 lines (maximum 2448); generated and third-party files are excluded. Evidence: `evidence/php-syntax.json` and `evidence/modified-source-line-counts.json`.

The existing core changelog draft was updated with the legacy-save preservation and invalid-status fixes. Both plugin drafts were read back; unrelated entries were retained. No version bump, release, commit or deployment was performed by this audit.

## Test infrastructure observations

Late screenshot commands stalled across otherwise functional browser sessions. Those sessions were restarted, and affected final actor checks were rerun sequentially with full browser snapshots saved. Earlier screenshots remain available for visual evidence; no screenshot timeout was counted as a product pass. One overlapping root test launch was discarded and both matrices rerun sequentially. No product access failure was inferred from the browser capture issue.

## Cleanup and final status

All seven disposable page/product fixtures and test customer455 were deleted. The original shared denial message, WooCommerce visibility setting and plugin activation list were restored and verified. The three exact temporary authentication tokens were revoked without clearing other sessions; all private state/token files and the settings backup were removed. Evidence: `evidence/cleanup-auth.json` and `evidence/cleanup.json`. All audit-owned browser sessions were closed; unrelated Plans sessions were retained.

Issues104–108 are closed. All four tasks on the fresh audit board are complete. The dedicated Gutenberg report is [gutenberg-report.md](gutenberg-report.md). Original content was not edited, and no further known issue remains within this audit scope.
