# React legacy restriction authoring QA — 2026-10-24

Result: passed. The actual WordPress editors preserved previous access rules on unrelated saves, converted only after an explicit review/apply action, and retained custom messages. No product defect was found during this authoring pass.

Environment: `http://localhost:10013`, built ArraySubs assets, real Gutenberg document sidebar and per-post modal. Vercel agent-browser isolated sessions `legacy-react` and `legacy-react-guest`; private supplied admin auth state. All editor mutations used snapshot references and keyboard input. WP-CLI was used only for dedicated fixture setup and readback of persisted values. No API-only replacement for browser authoring was used.

Owned fixtures: page 20082 (per-post) and page 20083 (Gutenberg). Parent QA run owns cleanup. The parent store matrix pages and global settings were not changed. Both browser sessions are closed.

## Previous login-only settings

Both fixtures began with unversioned old `mode=restrict`, `loginRequired=true`, a nonexistent role and product, OR logic, admin bypass disabled, and `<p>QA LEGACY CUSTOM <strong>saved message</strong></p>`.

- Per-post editor showed Previous access rules and explained that the original login-only rule ignores the other saved conditions. Who can access it and Match Logic were hidden until conversion review. Use custom message was on and the original HTML was retained.
- Applied the per-post modal without changing access settings, then saved through WordPress. Readback contained `accessVersion=1`, `legacy.loginOnly=true`, the original role/product, OR logic, and exact custom HTML.
- Reloaded the editor: previous access summary and custom HTML were still present.
- Opened Review current access rules. The local draft began with login only and empty dormant fields. Keep previous access rules dismissed the local draft without conversion.
- Reviewed again, chose Use current access rules, applied the modal, then saved. Readback contained `accessVersion=2`, login on, empty dormant fields, retained OR/admin/message settings, and no legacy or mode fields. Reload displayed the current grouped controls.
- Gutenberg showed the same previous-rule summary and inferred custom message. Changed the nested paragraph through real keyboard editing and saved. Serialized block attributes explicitly contained `accessVersion=1`, the full legacy descriptor, original dormant role/product, and custom HTML. Title-only saves also left the original unversioned body untouched before the nested body edit.
- Explicit Gutenberg review/conversion/save serialized `accessVersion=2`; legacy/mode and dormant fields were removed. Login-only access and custom HTML remained. Reload showed the current controls without invalid-block warnings.

Evidence: `react-legacy-post-{initial.txt,preserved.png,unchanged-save.txt,conversion-review.png,converted-save.txt,converted-reload.png}` and corresponding `react-legacy-block-*` files in [evidence](evidence/).

## New empty Gutenberg block

Inserted a new Restricted Content block using the actual Block Inserter. It immediately showed the current controls, no previous-rule notice, login off, and custom message off. Saved and reloaded the page. The new empty block explicitly serialized `accessVersion=2`, `enabled=true`, and `useDefaultMessage=true`; the version marker was not lost as a default-valued attribute.

Evidence: [new block UI](evidence/react-new-block-current-format.png), [saved block source](evidence/react-new-block-current-format-save.txt).

## Original nested direct-meta condition tree

Reused only owned page 20082 with the historical three-meta-key format and a nested OR/AND tree: `(nonexistent role AND spend >= 999999) OR administrator`.

- Per-post editor displayed Previous access rules and a keyboard-operable Saved condition tree disclosure. Expanded it and inspected the full nested tree.
- Applied/saved without converting. Persisted configuration retained `accessVersion=1`, `surface=meta`, `loginRequired=false`, exact nested OR/AND structure, and original message. The current login prerequisite did not rewrite this tree.
- Review clearly warned that the original tree cannot be copied into flat fields and must be rebuilt. Empty reviewed fields did not silently flatten the tree.
- Manually chose Administrator in the local draft, then explicitly chose Use current access rules, applied, and saved. Only this explicit conversion produced version 2 with the selected flat role rule and required login.

Evidence: [saved tree](evidence/react-legacy-tree-preserved.png), [unchanged tree after browser save](evidence/react-legacy-tree-unchanged-save.txt), [conversion warning](evidence/react-legacy-tree-conversion-warning.png), [explicit rebuilt conversion](evidence/react-legacy-tree-explicit-converted.txt).

## Original guests-only visibility and fallback

Reused only owned page 20083 with an unversioned old visibility block: `visShow=logged_out`, admin bypass off, and `<p>QA VISIBILITY FALLBACK preserved</p>`.

- Editor described Logged-out visitors only and preserved the original fallback in a disclosure. Current Who/Match controls and the misleading standard custom-message editor were absent.
- Edited the nested paragraph through real keyboard input and saved. Serialized attributes retained version 1, guests-only visibility, exact fallback HTML, and original visibility fields.
- Real guest browser saw the protected body `QA GUESTS ONLY preserved — saved in browser`. Logged-in admin, with bypass off, saw the exact visibility fallback instead.
- Conversion review explicitly warned that the current rules do not offer guests-only access and that the draft starts with logged-in users. Chose Keep previous access rules. The review disappeared, Save remained disabled, and the original visibility/fallback stayed stored.

Evidence: [original visibility](evidence/react-legacy-visibility-preserved.png), [saved attributes](evidence/react-legacy-visibility-unchanged-save.txt), [guest allowed](evidence/react-legacy-visibility-guest-allowed.png), [admin fallback](evidence/react-legacy-visibility-admin-fallback.png), [conversion warning](evidence/react-legacy-visibility-conversion-warning.png).

## Focused checks and limits

44 focused JavaScript compatibility assertions passed before browser authoring. These covered old/new classification, explicit marker precedence, old-mode precedence over transitional flags, custom-message inference, exact arbitrary-tree retention, unchanged-save normalization, and explicit conversion starting points. Eight JS/JSX modules and block JSON parsed successfully. Every touched source file remained below 3000 lines.

No uncaught browser errors were reported. Existing Elementor/WordPress PHP deprecation output appears after some WP-CLI readbacks and is not an access regression. Parent QA separately covers the full Pro/core frontend access matrix and final build.
