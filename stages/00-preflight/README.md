# Stage 00 — Pre-flight & Environment Verification

**Goal:** Confirm the test environment is in a known-good state before any feature work begins. Every plugin must be activated cleanly, every required admin menu must render, server cron must be firing, and outbound mail must be reaching the inbox. Failures discovered here block all later stages.

**Prerequisites:** A fresh WordPress install with WooCommerce already configured. ArraySubs and ArraySubsPro plugin ZIPs available. SSH or hosting-panel cron access. The six test accounts listed in `../README.md` ready to be created.

**Run order:**
1. [01 — Fresh install check](01-fresh-install-check.md) — Activate WP, WooCommerce, ArraySubs, ArraySubsPro and verify versions, dependency notices, and a clean activation log.
2. [02 — Admin menu and capabilities](02-admin-menu-and-capabilities.md) — Confirm the ArraySubs menu renders for admin and shop manager and is hidden for customers.
3. [03 — Cron and Action Scheduler](03-cron-and-action-scheduler.md) — Confirm WP-Cron is disabled, the system cron fires every minute, and the Action Scheduler queue is healthy.
4. [04 — Test accounts and mail](04-test-accounts-and-mail.md) — Create the six test users and confirm WordPress + WooCommerce email delivery.

**Exit criteria:**
- All four plugins (WordPress, WooCommerce, ArraySubs, ArraySubsPro) are active with no PHP notices, dependency warnings, or admin notices on any wp-admin page.
- ArraySubs menu and its sub-menus render correctly for Administrator and Shop Manager.
- Server cron is verified to hit `wp-cron.php` at least once per minute and Action Scheduler shows zero failed actions.
- All six test users exist and a WP test mail plus a WooCommerce test order email both arrive in their inboxes.
