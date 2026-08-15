---
id: 132
title: Migrate payment gateways and automatic payments to ArraySubs core
status: done
priority: critical
created: 2026-08-15T03:15:25.921940501+02:00
updated: 2026-08-15T04:49:58.388291541+02:00
started: 2026-08-15T04:49:35.678192961+02:00
completed: 2026-08-15T04:49:35.678192961+02:00
tags:
    - payments
    - migration
    - compatibility
    - regression
class: standard
---

Move Stripe, Mollie, PayPal, Paddle, gateway REST/webhook services, automatic renewals, retries, reconciliation, customer payment UI, and related assets from ArraySubsPro into ArraySubs. Preserve all persisted gateway IDs, settings, tokens, remote bindings, webhook routes, scheduled hooks/groups, and mixed-version upgrade safety for existing customers. Validate both plugins and run real staging browser/WP-CLI/Mailpit regression coverage. Source request: 2026-08-15.

QA completion — 2026-08-15

Implementation:
- ArraySubs 1.8.12 now owns Stripe, Paddle, PayPal, Mollie, gateway REST/webhook services, automatic renewal processing, Gateway Health, customer auto-renew/payment UI, and related build entries/assets.
- ArraySubsPro 1.1.3 defers to core while retaining a dormant one-release fallback for old-core/new-Pro update ordering. Legacy Pro payment class names alias to core classes, and Pro deactivation no longer removes core-owned payment jobs.
- Preserved gateway IDs, option names, post meta, REST routes, webhook schema, asset handles/runtime globals, Action Scheduler hooks/groups/arguments, and old integration type checks.

Verification:
- Production builds passed for both plugins. All changed/migrated PHP passed php -l; changed JS passed node --check; targeted Stripe SCSS stylelint and git diff --check passed. No native browser dialogs remain in moved frontends. PHPCS intentionally skipped under workspace instructions; repository-wide JS lint was unavailable because the pre-existing eslint-config-react-app dependency is missing.
- Staging browser QA passed with both plugins active, core only, and both restored. Gateway Health, Stripe settings, customer portal, auto-renew confirmation/loading UI, REST authorization, and browser console were verified.
- Real core-only Stripe E2E passed: disposable checkout -> active subscription -> exact invoice action -> exact off-session renewal action -> completed renewal order -> next-cycle actions -> Mailpit payment email. Fixture posts/orders/user were removed; zero fixture actions remain pending.
- Existing continuity passed: 13 Stripe and 1 Paddle subscriptions remain active. A replay against the pre-migration SQL snapshot found every non-scheduler metadata value byte-for-byte unchanged, including gateway customer and saved payment-method bindings. Only renewal/invoice/reminder scheduler pointer IDs changed as actions were regenerated. Pending hook/group/count/argument fingerprints remain identical; duplicate gateway metadata remains zero.
- Gateway options, hashes, autoload flags, schema 1.0.2, and webhook column/index fingerprints are unchanged. Final webhook count is 363 after four Stripe QA events and two old Paddle events pruned.
- Paddle resolver/config/job continuity was verified without executing a destructive live remote renewal. PayPal is disabled/unconfigured and Mollie is not installed on staging, so those paths were verified at registry, routing, authorization, and disabled-state level rather than with a live external charge.

Result: PASS. Both plugins active at ArraySubs 1.8.12 / ArraySubsPro 1.1.3; no new migration runtime errors in the post-test log.
