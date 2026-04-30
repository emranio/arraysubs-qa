# Stage 20 — Edge Cases & Final Regression

**Goal:** Stress the system at its boundaries and confirm that the cumulative test history of stages 00–19 has not left the install in a bad state. Stage 20 covers what happens when one plugin (Free or Pro) is deactivated while data is in flight, when WP-Cron is stopped and recovered, when concurrent customer/admin actions race, when subscription volume is high (200+), and finally a 30-minute happy-path smoke pass that proves the simplest user journey is still unbroken.

**Prerequisites:**
- Stages 00–19 completed in order (or at least 00–06 + 14 + 19 — the smoke and regression tasks here assume a working install with real subscriptions).
- ArraySubs and ArraySubsPro both active at the start of Stage 20 (specific tasks deactivate one or the other intentionally).
- WP-CLI accessible on the host (or SSH access) so we can edit `wp-config.php` and trigger Action Scheduler runs from the command line.
- Stripe in test mode (or your equivalent gateway sandbox) configured with at least one real auto-renewing subscription so that 01 can prove Pro deactivation doesn't break the renewal path.
- A way to send mail (mail-trap or smtp log) for verifying notifications.
- At least 200 subscriptions available for Task 05 — generated either by Pro's bulk admin tool or by a one-off WP-CLI/SQL seed script (record which method you used).

**Run order:**
1. [01 — Pro deactivation while running](01-pro-deactivation-while-running.md) — Pro is deactivated mid-flight; Free continues to function.
2. [02 — Core plugin deactivation](02-core-plugin-deactivation.md) — Core ArraySubs deactivated; verify graceful disappearance and no data loss on reactivation.
3. [03 — Cron stoppage and recovery](03-cron-stoppage-and-recovery.md) — Disable cron, let actions pile up, re-enable and verify the hourly safety job catches up.
4. [04 — Concurrent actions](04-concurrent-actions.md) — Race conditions: two customer browsers, two admin browsers.
5. [05 — Performance with many subscriptions](05-performance-with-many-subscriptions.md) — Subscription list page with 200+ rows; pagination, filter, search timing.
6. [06 — Final regression smoke](06-final-regression-smoke.md) — 30-minute end-to-end smoke pass.

**Exit criteria:**
- Pro can be deactivated and reactivated without data loss; Free behaves correctly while Pro is off.
- Core can be deactivated and reactivated without data loss; admin menu disappears entirely while off.
- Cron stoppage does not lose any subscription state; the hourly Generate Upcoming Renewals job catches up missed invoices on resume.
- Concurrent actions are either serialised correctly (only one wins) or fail cleanly with a user-visible error — never produce duplicate subscriptions or inconsistent state.
- The Subscriptions list page loads within 3 seconds and search returns within 2 seconds with 200+ subscriptions present, with no PHP notices and no memory exhaustion.
- The Stage 20 smoke test passes end-to-end, proving the prior 19 stages have not corrupted state.
