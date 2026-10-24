# Member Access and Toolkit module switches

Confirmed scope: make Member Access & Content Gate and Toolkit switchable using the existing registry, dormant providers, shared settings and ModuleGate architecture. Verify independently with free alone and free plus Pro.

Execution schedule: 2026-10-24, Asia/Dhaka. Execute tasks 1–4 sequentially in this session. Fresh progress board: board/kanban/. Preserve existing qa/issues entries and unrelated checkout changes.

Target: real local WordPress http://localhost:10013 serving this checkout. WP-CLI uses --allow-root and the existing Local MySQL socket through /tmp/arraysubs-module-wp, without configuration or environment changes. Browser automation uses agent-browser, isolated admin and guest/customer sessions. Administrator 1 uses Local auto-login or temporary generated cookies without changing passwords.

1. Audit ownership in both plugins and implement switches, provider boundaries, routes/menu visibility, settings/import/wizard guards and retained settings. Member Access owns login limits including toolkit.multi_login_* fields; Toolkit owns toolbar/dashboard/login-page controls and impersonation. Keep core subscription billing and generic account shortcodes working.
2. Free + Pro: exercise actual Modules switches and Save; verify independent off/on/reload, Configure and unsaved/Discard behavior, hidden menus, guarded direct routes, REST/config availability, guest restrictions (per-post, Gutenberg, shortcode and Elementor), Toolkit login/admin controls, Pro session rules and preserved settings. Capture screenshots and runtime observations; verify return from existing impersonation survives Toolkit off.
3. Free only: temporarily deactivate Pro, repeat both modules independent on/off with browser and runtime checks. Confirm no Pro dependency/fatal, default content/access controls, Toolkit role picker while Member Access is off, and base subscription/core providers remain active. Restore Pro.
4. Restore exact module/settings and plugin activation state; remove only our temporary fixtures/auth data; production build, changed PHP syntax, cross-plugin review and touched-file line counts. Record results, limits and evidence. No phpcs, Composer install, version bump, release or push.

Log confirmed defects in qa/issues with this plan/task/date, subscription/order/user identities (N/A when absent), route/context, steps, expected/actual, proof and scope notes. Issue preflight preserved: open #5 #8 #12 #36 #55 #110, in-progress #27, blocked #19 #51.
