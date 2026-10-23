# Elementor final access audit — 2026-10-24

Active task 3; plan ../plan.md (fresh final access audit). Disposable author page 20192 only. Guest, nonmember455, member224 and administrator1 used isolated final-elementor-* browser sessions. No original content, global settings or plugin activation changed.

## Confirmed and fixed issue 106

An ordinary Heading-only Publish, without opening any restriction controls, could drop the sole current-format evidence (an explicitly empty message flag). With login required and failing ANY conditions, the saved rule then became legacy login-only and exposed protected content to every logged-in visitor. Before/after actor output and the raw saved settings are retained in elementor-transitional-before.json, elementor-transitional-after-bug.json, elementor-transitional-saved-after-bug.txt and elementor-unopened-leak-member.png.

The editor now prepares every container before ordinary saves, including nested containers, document loads and added/reset child collections. It captures actually saved fields, identifies the same format as PHP before assigning a canonical marker, and preserves explicit off flags during Elementor default pruning. Initialization leaves the editor unchanged until an actual author edit. Imported numeric prefixes, negative markers and PHP's ASCII whitespace parsing retain their previous interpretation. The inverse transitional message choice is projected into the current positive Use custom message control, with explicit off choices preserved on subsequent saves.

## Actual browser authoring and storefront results

All 14 Heading-only round trips passed; 112 actor page views before/after ordinary save retained exactly the same protected-content decision and denial output. Every interaction used agent-browser snapshots and current refs. Only the Heading Title and Publish controls were used; the restriction panel stayed unopened for these cases:

| Saved rule | Audience before and after |
| --- | --- |
| Transitional positive custom-message off, login required, failing ANY | Nobody |
| Transitional inverse default-message off, login required, failing ANY | Nobody |
| Original ANY, login absent, all conditions fail | Nobody |
| Original ANY, login absent, member role passes | Member224 |
| Original ANY, login explicitly off, all conditions fail | Nobody |
| Original ANY, login explicitly off, member role passes | Member224 |
| Original guest-only visibility, empty fallback | Guest; logged-in output stays empty |
| Original guest-only visibility, custom fallback | Guest; logged-in output keeps fallback |
| Original login-only with ignored failing ANY clauses | All logged-in actors |
| Imported marker -1 with explicit current off flag | Nobody |
| Imported marker 2garbage | Nobody |
| Imported NBSP-prefixed marker, guest-only visibility | Guest |
| Imported numeric 2^63 marker (PHP float-to-int wrap), guest-only visibility | Guest |
| Public ancestor with original member-only ANY child | Member224 |

Detailed per-actor booleans are in elementor-unopened-author-results.json. Each case has raw before/after data, initial model serialization and actor snapshots. The fixed member denial is visibly confirmed in elementor-unopened-fixed-member.png. A browser authentication state expired during a fresh-session restart; root refreshed that state, and the remaining cases passed. This was setup rather than a product failure.

Separately, opened the inverse-only transitional rule: Use custom message correctly began ON and the retained editor was visible. Switched OFF through the native control, published, reloaded and reopened the section. The switch stayed OFF, the editor stayed hidden, retained custom HTML remained stored, and member frontend output was QA FINAL DEFAULT. Evidence: elementor-inverse-toggle-off-saved.txt, elementor-inverse-off-reloaded-model.json, elementor-inverse-off-reloaded.txt and elementor-inverse-off-reloaded.png.

## Independent real native rendering / cache results

433/433 assertions passed using actual Elementor Element_Base::print_element and Document::print_elements. Original legacy decisions and denied output were compared against the actual pre-change HEAD Elementor adapter, loaded under a distinct class name. Coverage includes ordinary ANY with absent/off/true login, one passing/all failing clauses, absent/empty/restrict modes, guest/logged-in visibility and exact empty/plain/expanded shortcode fallback. Additional cases preserve scalar CSV and mixed-case roles/statuses, scalar/array negative product IDs, purchased CSV and variation CSV.

Nested actual renders cover public ancestor/denied child, denied ancestor/allowed child, login/member ancestors and duplicate saved IDs. Output buffers remained balanced, and later public output was not swallowed. A disposable document's cache was warmed with private HTML before adding a nested gate; the actual document renderer ignored that cache and independently evaluated member, guest, nonmember, admin, member and guest in sequence. Visitor output never became readable shared HTML, and source settings stayed unchanged. The scratch post was deleted in finally. Evidence: elementor-native-runtime.txt; executable helper elementor-native-audit.php and original-elementor-hooks.php (HEAD snapshot).

## Validation and cleanup

JavaScript syntax and scoped diff whitespace checks pass. All owned source files remain below 3000 lines: editor.js421, editor.css70, Hooks.php849, RestrictionSettings.php69, CompatibilityNotice.php18, LoginSwitcher.php20. No build, phpcs or Composer run was needed for this direct editor asset. Root owns final cross-plugin/core-only checks and changelog updates. All final-elementor browser sessions are closed after this report. Page20192 remains in the root fixture manifest for root cleanup; no extra scratch post remains.

## Bounded independent Gutenberg compatibility review

Reviewed the declared final LegacyAttributes.php, EditorContentCompatibility.php, Gutenberg Hooks.php, restrictionCompatibility.js and LegacyAccessNotice.jsx without editing them. No blocking findings. The original eleven shortcode condition strings are captured before shared config sanitization, reattached to authorized editor response data, and merged only on version1 rendering. The whitelist excludes message, redirect, login, administrator and version overrides. Scalar CSV, case and signed product IDs remain in the preserved original contract. Explicit conversion clears the entire legacy descriptor and old mode/visibility fields; version2 rendering ignores any retained old descriptor.

The REST filters normalize authorized edit-context responses/preloads only, check revision parent capabilities, and restrict template/widget fallback routes to edit_theme_options. They do not update posts, options or metadata. Nested block parsing retains inner content and recursively updates only restriction attributes; block widget raw/signed response representations stay consistent.

This review identified one additional version-coercion variant in the separate direct Elementor helper: PHP casts an imported numeric2^63 float to PHP_INT_MIN, whereas the earlier JS parser treated it as a positive current marker. Issue106 was briefly reopened. Adopted the same signed64 numeric wrapping as the shared helper, then verified the actual guest-only visibility Heading-save round trip for all four actors. Evidence elementor-numeric-overflow-before.txt proves the wrong-marker access change; elementor-visibility_imported_overflow-* and the appended actor results prove the fix. Own actor cookies were refreshed from the already tracked tokens after prior sessions had been closed; no new authentication tokens were created.

## Final REST parser wrapper review

Independently reviewed the later EditorContentCompatibility/EditorBlockParser addition without editing those source files or running a duplicate matrix. Confirmed in the actual WordPress REST server source that route permission checks precede rest_dispatch_request, and a non-null wrapper result prevents a second handler invocation. The adapter is limited to authenticated core content-controller GET/HEAD edit reads. It retains the configured parser, skips delegating to itself, and restores the prior delegate, temporary filter and active-scope count in finally.

Review identified and the owning agent fixed two draft ordering defects: nested adapters could delegate recursively to EditorBlockParser itself; and removing a successful nested request in dispatch finally exposed the outer edit scope too early during inner view after-callback filters. Final code keeps successful scopes until the exact after-callback removal and removes abandoned scopes when callbacks throw. Reviewed the owning agent's actual REST evidence and matching assertions:37/37 passed, including nested edit/view, nested-view after-callback parser context, custom parser delegation, caught nested exceptions, final filter cleanup, raw/signed widget consistency and unchanged source hashes. Evidence gutenberg-edit-rest-readonly.json. No remaining blocking finding in the changed wrapper scope.
