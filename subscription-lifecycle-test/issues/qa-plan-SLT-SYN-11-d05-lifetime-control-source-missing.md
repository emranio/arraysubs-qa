# D05 lifetime-control guarantee has no SLT-SYN-11 source fixture

- Severity: high
- Date found: 2026-08-07
- Watch day: D05
- Originating task: `SLT-SYN-11` / card `#75`
- Plan file: `kanban/tasks/075-flexible-sync-exclusivity-negatives-renewal-price.md`

## Affected records

- Subscription IDs: N/A — the authored Excl Lifetime Probe subscription was never created.
- Order IDs: N/A.
- Product IDs: N/A — all three intended probe products are absent.
- WP user: N/A — intended `slt-excl` / `slt-excl@example.test` does not exist.
- Role: intended `customer`; no live user record exists.
- Gateway: Stripe test.
- Checkout type: block checkout.
- Non-default settings: global sync off under the frozen QA-window baseline; the task's product-meta force-set and Shop Access append never occurred.

## Context

- Detection context: automated D05 early-morning watch, read-only facts/WordPress CLI; no browser session was opened because there is no source product, user, or subscription.
- Intended routes: `/wp-admin/user-new.php`, `/wp-admin/post-new.php?post_type=product`, `/product/slt-excl-lifetime-probe/`, and `/checkout/` in `admin-SLT-SYN-11` / `customer-SLT-SYN-11`.

## Reproduction steps

1. Read the appended closeout note in `kanban/tasks/075-flexible-sync-exclusivity-negatives-renewal-price.md`.
2. Observe that card `#75` closed `UNVERIFIED` after its D04 same-day window was missed, without creating `slt-excl` or any of the three probes.
3. Query the exact `slt-excl` login, the three exact probe slugs/titles, and subscriptions owned by that login.
4. Observe `SYN11_user=0`, `SYN11_products=0`, and `SYN11_subscriptions=0` in `/home/server-manager/slt-evidence/D05-2026-08-07-early-morning-verification.txt`.
5. Read the D05 unconditional lifetime-control check in `watch-schedule.md`, which requires `SLT Excl Lifetime Probe` to have empty schedule meta and zero renewal actions/orders/mail from D4 onward.

## Expected result

The lifetime negative should be scored only after a relationship-resolved SLT-SYN-11 probe exists, or the watch schedule should explicitly classify it as source-absent after the missed D04 task.

## Actual result

No product, user, subscription, order, or scheduler row exists, yet the D05 schedule treats the probe as an unconditional control. Absence of activity cannot prove lifetime scheduling behavior when the test object itself is absent.

## Concrete proof

- Task closeout: `kanban/tasks/075-flexible-sync-exclusivity-negatives-renewal-price.md`.
- Frozen D05 snapshot: `automation/logs/D05-2026-08-07-early-morning-facts.txt`.
- Current zero-count transcript: `/home/server-manager/slt-evidence/D05-2026-08-07-early-morning-verification.txt`.
- Screenshots, Mailpit IDs, action IDs, and order IDs: N/A because the source task never created the probe; no evidence was fabricated.

## Scope notes and counterexamples

- This is a QA-plan/source-execution finding, not evidence that lifetime subscriptions schedule renewals.
- Live lifetime counterexamples pass: primary control `12003` and H1 `12786` are active with empty next/end dates and zero ArraySubs renewal actions/orders/new renewal mail.
- The missing Renewal Price and Trial probes are also absent, but this issue is scoped to the D05 unconditional lifetime-control assertion.
- No late substitute product, checkout, date mutation, or forced scheduler action is permitted.
