# Member Access custom-message verification

Date and execution schedule: 2026-10-24, Asia/Dhaka. Run tasks 1–4 sequentially in this session.

Target: http://localhost:10013, the real local WordPress site serving this checkout. No remote deployment is part of this feature. Preserve existing `qa/issues/` entries and unrelated workspace edits.

Scope: Shop Access, URL Rules, global Content Rules, Comment Rules, and Purchase Limits on the Member Access admin pages. Per-post restriction editors, Elementor and Gutenberg configuration are deferred.

1. Verify shared resolution, sanitization and REST authorization. Cover switch off with retained text, on with text, empty, rich-editor blank markup, formatting, links, and global/built-in fallback. Confirm per-post adapters keep their current messages.
2. Browser admin: each tab displays a switch before its editor; off hides the editor, on enables it; save/reload preserves the switch and message; toggling preserves text; Discard restores both. Check keyboard activation and WordPress accent styling.
3. Browser storefront: real denied visitors see custom text only when enabled and nonempty; otherwise the shared default. Cover all five rule types, changes to the shared default, and qualifying access. Include Purchase Limit placeholder rendering and cart validation.
4. Repeat key core-only frontend/admin and runtime checks; restore the original plugins and settings; check browser errors, production build, changed-file syntax and line counts. Save evidence and a report, then close browser sessions.

Board: `board/kanban/`. Newly discovered bugs belong in the existing `qa/issues/` board with plan/task/date, fixture identity, URL, context, reproduction, expected/actual and evidence.

Authentication: use a short-lived WordPress-generated local cookie for existing administrator ID 1, without resetting their password. Guest browsers have separate isolated sessions. Do not save authentication secrets in QA artifacts.
