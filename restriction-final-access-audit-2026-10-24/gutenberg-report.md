# Gutenberg final access audit

Task #2; scheduled 2026-10-24; owner `unflat-gallein`. Plan: [plan.md](plan.md). Author fixture20191; admin1, customer455, member224 and anonymous guest. No users, Pro activation state or other author fixtures changed.

The original ordinary ANY audience stays unchanged through actual WordPress title-only save, body save and reload: member224 allowed, guest/customer455/admin1 denied. The native fixture deliberately omitted mode, enabled, loginRequired, useDefaultMessage and accessVersion, exactly as the original block serializer could. Each save persisted version1, false login-only override, exact original condition strings, custom message and nested paragraph. See `evidence/gutenberg-after-title-save.txt`, `gutenberg-after-body-save.txt`, body editor snapshots and root four-actor audience files.

Legacy guests-only visibility was opened in the real editor, then title/body edited and saved through WordPress's Code editor textarea using snapshot refs. This edits the actual post editor, not a REST/API substitute. It retained logged_out, false login-only override and the exact fallback. Root's baseline browser check confirmed only the guest saw protected content. Final saved four-actor browser check also passed: guest only sees the protected body; customer455/member224/admin1 see the exact QA FINAL FALLBACK. Evidence: `author_gutenberg-visibility-saved-audience.json` and `gutenberg-visibility-fallback-check.json`.

## Bugs fixed

Issue107 covered two confirmed leaks: invalid imported loginRequired numeric2 bypassed the old schema to create a login-only override; an unknown old enabled:false attribute with explicit old mode disabled the gate. Original schema validation, defaults and PHP truthiness now run before legacy normalization. Browser retests denied admin1 in the first case and guest in the second, displaying the custom denial and excluding protected body. Before-fix screenshots and recorded after-fix browser observations are under `evidence/gutenberg-issue107-browser-retest.json`.

Original case, CSV values, negative IDs and guided plan-feature values are retained as eleven fixed shortcode condition strings in the version1 descriptor. This prevents normalization from granting a match that the original renderer rejected. The whitelist cannot replace messages, login/admin flags, redirects, callback/tag or version. Explicit reviewed conversion discards the complete descriptor.

## Verification

- 331 original HEAD schema+renderer comparisons passed with Pro, and331 passed with Pro skipped, across41 cases/four actors. Includes native ANY all-false/one-match/all-match, invalid and accepted boolean scalars, float1.0 versus integer1, uppercase/CSV roles, CSV status/products, negative IDs and guests visibility.
- 379 assertions passed against the actual installed WordPress blocks package plus27 dependencies: eleven original native serialized fixtures, three parse/save rounds with body edit, nested group, actual editor initialization, default pruning and persistent version markers.
- 37 real internal REST assertions passed: post edit, initial editor preload, synced pattern, template, template part and block widget; literal float1.0 remains a false original override while int1 remains true. Post/template/widget source data stayed unchanged on reads. View responses remain unversioned; unauthorized edit is denied; normalization is idempotent; unrelated content is byte-identical. Widget raw/encoded/hash remain consistent. Temporary fixtures and widget/sidebar options were restored.
- Scoped parser integration delegates any configured parser, invokes the permission-checked core callback once, preserves nested edit/view context through after-callback filters, restores outer scope after a caught nested exception, and cleans up in finally. Independent source/test review found no remaining blocker.
- Full core production build passed at04:12:04 (`/tmp/arraysubs-final-gutenberg-build.log`, copied by root to evidence). Scoped whitespace checks and PHP syntax passed; touched source files are all below3000 lines.

Reproducible harnesses: `gutenberg-head-differential.php`, `gutenberg-official-parser-roundtrip.cjs`, and `gutenberg-rest-readonly-test.php`. JSON evidence is in `evidence/gutenberg-head-server-differential*.json`, `gutenberg-official-parser-roundtrips.json`, `gutenberg-edit-rest-readonly.json`.

## Limits and cleanup

Final-cycle screenshot capture stalled across root and isolated browser sessions, including a blank-page diagnostic. Later snapshots and full browser text were preserved; no after-fix screenshot or final visual-layout pass is claimed. Earlier real screenshots remain available. Selecting the iframe block through List View also stalled, so body authoring used the real WordPress Code editor. Existing unrelated Elementor deprecation output did not fail checks.

Dedicated author fixture20191 is left in its final guests-only saved state for root's cleanup (final four-actor check passed). No private auth-state copy was created by this agent; root-provided auth file was read only. Root owns fixture deletion, account/session-token cleanup and the overall final report.
