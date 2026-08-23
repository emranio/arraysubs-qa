---
id: 123
title: SLT-RET-03 Pause retention offer, limits, access, billing shift and auto-resume
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, pause, stripe, day-04]
due: "2026-08-27"
estimate: 2h
depends_on: [36, 60, 121]
class: standard
---

> **SLT-RET-03** · group `retention` · scheduled **D04-D06**

## Objective
Verify the pause retention offer for all configured conditions: 30-day/default duration, custom limits, access behavior, shifted billing, one auto-resume action, manual resume, repeat/cooldown rules and cancellation-flow dismissal.

## Steps
1. Snapshot settings; enable pause offer with 30 days, eligible reasons/plans/statuses, customer pause permission, max/cooldown and access policy. Verify admin validation for 0, negative, over-max and malformed durations.
2. On a dedicated active Stripe subscription verify reason-match presentation/copy; no-match, ineligible status/product and already-used controls hide the offer.
3. X/Escape/decline changes nothing. Accept once and require loading/toast, paused status, original/paused/resume dates, offer history, note/audit and exactly one auto-resume action.
4. Require invoice/charge/reminder legs suppressed while paused, no renewal order/charge/mail at the old due gate, and portal/admin status/dates agree.
5. Verify member/shop/feature access exactly follows configured pause policy and returns on resume.
6. Test manual Resume confirmation/loading: one reactivation, shifted next date according to pause duration, exactly one invoice/charge pair, one email and no stale auto-resume action.
7. On a second isolated fixture, use a supported 1-day pause inside its own restore bracket and allow natural auto-resume during this 12-day run. The primary fixture still verifies the configured 30-day/default action timestamp. Require one transition/action/email, a correctly shifted schedule and idempotent replay/sweep behavior.
8. Test maximum uses/cooldown, pause while on-hold/pending-cancel/cancelled/trial, and second pause request. Require clear refusal and no partial mutation.
9. Verify Paddle presentation only where supported; unsupported remote pause must be hidden/rejected with no state drift.
10. Restore settings exactly and reconcile actions/orders/mail/access. Mark done only when every condition passes.

## Pass criteria
- [ ] Offer eligibility, validation, decline/dismiss and use/cooldown limits pass
- [ ] Pause suppresses billing and enforces access policy with one resume action
- [ ] Manual and automatic resume restore correct shifted schedule exactly once
- [ ] Invalid statuses and Paddle capability negatives mutate nothing

Failures create/update the mandatory `qa/issues/` card and block completion.
