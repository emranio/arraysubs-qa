# Cancellation flow editor verification

Scope confirmed 2026-10-24 Asia/Dhaka: seven-step admin editor at http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow and the corresponding customer My Account cancellation flow. This is feature validation; existing qa/issues remain intact. No prior lifecycle plan is reused.

- Check saved overview, all seven steps, retained help/alerts and settings, navigation, dynamic reason choices, validation from review, discard/keep editing, failed-save recovery, successful save and reload.
- Check fullscreen layout, mobile overflow, focus and WordPress admin accent.
- Use disposable local customer/subscription fixtures to test My Account reasons, targeted discount, pause, downgrade and support offers, retention-off cancellation, and core-only behavior where applicable.
- Preserve original settings privately, restore test settings, remove fixtures, invalidate short-lived auth sessions, and retain evidence/report here.

Authentication: short-lived WordPress-generated local cookies for administrator ID 1 and a disposable customer, each in isolated agent-browser sessions. Never store tokens or passwords in these artifacts. WP-CLI uses --allow-root and the site's Local MySQL socket. No Composer installation or phpcs.
