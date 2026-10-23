# Restriction compatibility QA

Scope: preserve settings saved before the unified per-post/CPT, Gutenberg and Elementor restriction flow. Verify old login-only overrides, visibility for guests or members, fallback text/shortcodes and empty output, custom messages, administrator bypass, direct-meta condition trees, redirects, and both transitional message switches. New version 2 rules retain login prerequisites outside ALL/ANY. Reads must not rewrite records; unrelated editor saves preserve old decisions; explicit conversion adopts new rules.

Schedule: 2026-10-24, Asia/Dhaka, sequential execution in this session. Fresh progress board: board/kanban. Existing qa/issues records remain intact.

Target: actual local WordPress http://localhost:10013 serving this checkout. Use administrator 1, member 224 and disposable pages/customer. Authentication uses scoped temporary session cookies with exact-token cleanup. Browser navigation/automation uses agent-browser only, isolated sessions and snapshots/screenshots. Do not change real content settings.

1. Runtime compatibility matrix: old raw serialized formats, normalizer idempotence, no-write reads, legacy decisions and messages, version 2 ALL/ANY and explicit conversion, core/Pro behavior.
2. Real browser storefront matrix: old fixtures on all three surfaces for guest/customer/member/admin, blank visibility and custom/default messages, unchanged-save behavior.
3. Real editor authoring: open old configurations, preserve and save/reload, then explicit conversion to current rules; confirm version marker persists and new configurations use version 2.
4. Build and review: PHP syntax, production assets, file sizes, cleanup fixtures/tokens/settings, report evidence. No phpcs, Composer install, release or push.

New defects go in qa/issues with this plan/task/date, URL/browser/user/fixture IDs, reproduction and proof; fix and rerun before closing.
