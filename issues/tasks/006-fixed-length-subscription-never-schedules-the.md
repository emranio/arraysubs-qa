---
id: 6
title: Fixed-length subscription never schedules the required expiring-soon action
status: closed
priority: critical
created: 2026-08-23T12:52:19.719192619+02:00
updated: 2026-10-18T13:44:51.292983+06:00
started: 2026-10-18T13:31:23.880782+06:00
completed: 2026-10-18T13:44:51.292983+06:00
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

## Source investigation — 2026-10-18

- Active QA task ID, scheduled day, and plan: N/A; no active execution plan. Historical evidence and plan references above are preserved only as history; the removed lifecycle plan was not reused.
- Current source: ArraySubs main at 525a2b6; ArraySubs Pro main at 95c3e25.
- Investigation context: source inspection only; no new browser run, scheduler execution, email send, or changes to subscriptions/orders/users. Current affected record IDs: N/A; historical IDs above remain historical evidence.
- Still present in current source: EmailManager.php:186 registers the action callback, and :2052 implements delivery and deduplication, but neither plugin schedules HOOK_SEND_EXPIRING_SOON or directly invokes the delivery method. The other hook references are query/metadata mappings and Pro audit labels.
- ActionScheduler.php:850 explicitly comments that nothing schedules this hook and omits it from unscheduleAllForSubscription(). Adding scheduling must also add teardown handling.
- Scope: the subscription expiring-soon email; the ordinary renewal reminder and Pro store-credit expiry notifications have separate scheduling paths.
- Fix direction: calculate the eligible finite-subscription end, schedule through the shared ActionScheduler with indexed arguments, reconcile lifecycle/date/settings changes, handle subscriptions already within the lead window, and cancel pending reminders on ineligible/terminal transitions. Preserve the existing sent-per-target guard. Browser/email verification remains required before closure.

Fix plan 2026-10-18: add focused core email scheduling using existing lifecycle metadata and ActionScheduler, derive the true finite expiration boundary and honor fixed-date Pro memberships, reconcile completed metadata writes and changed reminder settings, clear ineligible jobs, revalidate stale jobs at send time and reuse dedupe/locks. Verify signup inside lead window, later reminder, date/status changes, unlimited exclusion and Pro fixed dates. No billing or expiration behavior will be changed.

Fixed and verified 2026-10-18. Focused core service reconciles final subscription metadata/status writes, settings changes, a one-time existing-subscription scan and daily bounded recovery. It uses existing shared scheduling/locks and observed-delivery helper; billing/expiration code and Pro implementation are unchanged. Browser scheduler baseline for manual fixture 12104 showed only invoice/charge jobs. New expiry action 10898 completed through the real asynchronous runner and Mailpit received its email. Final fixture 12318 delivered the calculated October 22 end date and correct 4 remaining days while configured lead stayed 7 (browser Mailpit text proof). Runtime regression: 37 assertions pass with Pro loaded and the same 37 pass with Pro skipped, including failed/disabled transport, deduplication, stale date/lead rescheduling, terminal cleanup, fixed dates, annual auto-renew exclusion, free trials, month ends, DST and unchanged billing metadata. No PHPCS/lint or dependencies installed. Source files all below 3000 lines. Plan/evidence: qa/three-bug-fixes-2026-10-18/report.md; scheduled day 2026-10-18; admin/customer1, qa-bugfix20261018@example.test, administrator role; orders N/A. All four setup/browser subscriptions and runtime fixtures were deleted, fixture upload/access counters removed, original rules restored and both plugins remain active. Independent final cross-plugin review found no remaining blocker.
