---
id: 6
title: Fixed-length subscription never schedules the required expiring-soon action
status: open
priority: critical
created: 2026-08-23T12:52:19.719192619+02:00
updated: 2026-08-23T12:52:19.719192619+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - emails
    - finite-length
    - stripe
class: standard
---

## QA linkage

- Lifecycle task: `#4` / `SLT-LIFE-04`, D00 purchase with D02/D04 natural follow-ups.
- Plan: `qa/kanban/tasks/004-slt-fixed-three-cycles-to-its-natural-end-two.md`.
- Calendar/watch: `qa/calendar.md` and `qa/watch-schedule.md`.

## Affected records

- Subscription `31645`, status `arraysubs-active`.
- Initial order `31644`, completed, Stripe, `$7.00`; renewal orders `N/A` at discovery time.
- Customer `474`, `slt2-core` / `slt2-core@example.test`, role `customer`.
- Product `31347`, `SLT2 Fixed Three Cycles`, day/2, length 3.

## Reproduction

1. Buy product `31347` through the real block checkout using Stripe.
2. Resolve subscription `31645` from receipt order `31644`.
3. Open `https://mirror-help.arrayhash.com/wp-admin/tools.php?page=action-scheduler&status=pending&s=31645` as administrator.
4. Search pending/history rows and subscription metadata for `arraysubs_send_expiring_soon` and its scheduling/deduplication state.

## Expected

With the configured seven-day expiring-soon lead, this short fixed-length subscription is already inside the notification window at signup. It must queue one expiring-soon action or a documented immediate equivalent and persist deduplication state.

## Actual

Only renewal invoice action `29612` at `2026-08-25 07:58:23 UTC` and renewal charge action `29613` at `2026-08-25 13:58:23 UTC` exist. There is no expiring-soon action, `_expiring_soon_action_id`, or sent/dedupe state.

## Proof

- Admin scheduler screenshot: `/home/server-manager/slt-evidence/SLT-LIFE-04-02-pending-d0.png`.
- Customer receipt: `/home/server-manager/slt-evidence/SLT-LIFE-04-01b-received.png`.
- Checkout: `/home/server-manager/slt-evidence/SLT-LIFE-04-01-checkout.png`.
- Meta shows day/2, length 3, completed payments 1, the next-payment date, and action IDs `29612`/`29613`; expiring-soon fields are absent.
- Code inspection corroborates the live result: the handler is registered, but the expiring-soon hook is not scheduled.

## Scope and counterexamples

The initial Stripe charge, activation, checkout mail, and both natural renewal legs are present. No action was forced, drained, replayed, or hand-scheduled. Paddle, PayPal, and Mollie were not used. This remains open as a finite-subscription lifecycle/email defect.
