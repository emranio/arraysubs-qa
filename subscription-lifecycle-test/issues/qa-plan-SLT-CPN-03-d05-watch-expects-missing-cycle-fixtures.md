# D05 watch expects CPN-03 renewal cycles after the source task closed without fixtures

- Severity: high
- Date found: 2026-08-07
- Watch day: D05
- Originating task: `SLT-CPN-03` / card `#32`
- Plan file: `kanban/tasks/032-sltrec3-vs-sltrec3noinit-on-block-checkout-n-cycle.md`

## Affected records

- Subscription IDs: N/A — neither authored subscription exists.
- Order IDs: N/A — neither authored parent or renewal order exists.
- Product ID: `11927` (`SLT Daily Core`), the valid intended source product; it is not itself defective.
- WP users: N/A — intended logins `slt-cpncyc` / `slt-cpncyc@example.test` and `slt-cpncyc2` / `slt-cpncyc2@example.test` do not exist.
- Roles: intended `customer`; no live user records exist.
- Gateway: Stripe test.
- Checkout type: block checkout.
- Non-default settings: the frozen QA-window baseline (global sync off and the standing customer-action flags); no CPN-03-specific settings bracket ever opened.

## Context

- Detection context: automated D05 early-morning watch, read-only facts/WordPress CLI; no browser session was opened because the source users and subscriptions are absent.
- Intended source routes: `/wp-admin/user-new.php`, `/product/slt-daily-core/`, and `/checkout/` in sessions `admin-SLT-CPN-03`, `customer-a-SLT-CPN-03`, and `customer-b-SLT-CPN-03`.

## Reproduction steps

1. Read the appended execution note in `kanban/tasks/032-sltrec3-vs-sltrec3noinit-on-block-checkout-n-cycle.md`.
2. Observe that card `#32` closed `UNVERIFIED (missed D02 execution window)` and explicitly records that neither aligned 18:00–19:00 D02 checkout occurred.
3. From the WordPress root, query exact user logins `slt-cpncyc` and `slt-cpncyc2`, then query `arraysubs_data` subscriptions owned by those exact users.
4. Observe explicit counts `CPN03_users=0` and `CPN03_subscriptions=0` in `/home/server-manager/slt-evidence/D05-2026-08-07-early-morning-verification.txt`.
5. Read the D05 row of `watch-schedule.md`; it still unconditionally requires both CPN-03 renewal #2 results and the first subscription's coupon-exhaustion state/note.

## Expected result

The D05 watch schedule should condition the two CPN-03 renewal assertions on live source fixtures, or the source task should have produced and registered two relationship-resolved subscriptions in its authored D02 window.

## Actual result

The source task is complete as `UNVERIFIED`, both users and both subscriptions are absent, but the D05 watch row still presents their renewal outcomes as guaranteed. There can be no action, renewal order, coupon meta transition, exhaustion note, or Mailpit message to observe.

## Concrete proof

- Task closeout: `kanban/tasks/032-sltrec3-vs-sltrec3noinit-on-block-checkout-n-cycle.md`.
- Frozen D05 snapshot: `automation/logs/D05-2026-08-07-early-morning-facts.txt` contains no CPN-03 subscription/action/order.
- Current zero-count transcript: `/home/server-manager/slt-evidence/D05-2026-08-07-early-morning-verification.txt`.
- UI screenshots and Mailpit IDs: N/A because the authored source checkouts never occurred; inventing either would violate the lifecycle plan.

## Scope notes and counterexamples

- This is a QA-plan/source-execution finding, not evidence of a coupon-renewal product defect.
- Other live coupon subscriptions are healthy counterexamples: `12318` renewed for `$8.00` in order `12926`, and `12332` renewed for `$10.00` in order `12918`, each with its exact two-message order/payment pair.
- No substitute checkout, date mutation, or forced scheduler action is permitted.
