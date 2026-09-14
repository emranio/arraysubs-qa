# Settings footer verification — 2026-09-14

Scope confirmed in the current user request: Profile Builder (Custom Fields and My Account Editor), Cart Info Editor, Member Styling, all nine Member Access rule tabs, and Retention Flow. Use Settings → General as the visual reference.

Site: http://localhost:10013. Admin entry: http://localhost:10013/wp-admin/?localwp_auto_login=1. Credential source: workspace AGENTS.md local testing context. Use isolated browser session `footer-admin`.

Tracking: kanban task #1. Scheduled day: 2026-09-14, during this task; no recurring watcher.

1. Inspect both plugins and existing QA issues. Snapshot the stored settings and separate Profile Builder options before mutations.
2. Implement a shared Settings action footer, saved-time responses, and discard state restoration.
3. Build required browser assets. Verify every affected route in the real browser using snapshots and screenshots.
4. For each editor: change data, discard and compare; save a reversible change, reload and verify persistence; change again and discard to the most recent save. Verify the saved-time label and Easy Setup destination. Cover nested rules, field/menu additions and removals, validation errors, toggles, and retention reason lists.
5. Check footer at desktop and narrow widths, visible saving state, and browser errors. Check core-only operation without changing the active plugin state of the user's browser.
6. Restore original options and compare against the baseline. Reload affected screens. Record evidence and results in report.md. Log discovered bugs in qa/issues with task #1, this plan, IDs or N/A, route, user context, reproduction, expected/actual result, and proof.

Preserve existing repository edits and QA issues. Do not run PHPCS or install dependencies.
