# D10 watch requires an SLT-SYN-12 renewal although the source task created no fixture

- Severity: medium
- Date found: 2026-08-12
- Watch day: D10
- Originating test task: `SLT-SYN-12` (kanban card `88`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/088-gateway-gating-for-flexible-sync-paddle-hidden.md`
- Conflicting watch plan: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/watch-schedule.md`

## Affected records

- Subscription IDs: N/A — neither authored probe subscription was created
- Order IDs: N/A — neither authored probe order was created
- Product ID: N/A — `SLT Flex Gateway Probe` / `slt-flex-gateway-probe` was not published
- Intended WP users: `352`, login `slt-paddle`, email `slt-paddle@example.test`, role `customer`; and `354`, login `slt-flex2`, email `slt-flex2@example.test`, role `customer`
- Gateway: Paddle sandbox control and Stripe test target
- Checkout type: block and classic were authored, but neither probe checkout ran
- Non-default settings in play: none. Task 88 closed before publishing the product, changing Shop Access, enabling probe flex meta, adding to cart, or checking out.

## Routes and browser contexts

- Intended product URL: `https://mirror-help.arrayhash.com/product/slt-flex-gateway-probe/` (does not exist because the product was never created)
- Intended classic checkout: `https://mirror-help.arrayhash.com/slt-classic-checkout`
- Intended block checkout: `https://mirror-help.arrayhash.com/checkout/`
- Intended admin subscription route: N/A — no numeric subscription exists
- Browser/user context: no D10 browser session was opened. Task 88's D5 closeout explicitly records that no task mutation or checkout began.

## Reproduction

1. Open kanban task `88` and read its final `2026-08-07 23:05` execution note.
2. Observe that the task closed `UNVERIFIED` because only 55 minutes remained for a two-hour sequence, and that the probe slug, product, orders, subscriptions, and actions were never created.
3. Read the D10 row of `watch-schedule.md`.
4. Observe that it calls the `SLT-SYN-12` Stripe-leg midnight renewal an unconditional D10 event.
5. Read the D10 facts and the published D10 registry/SETUP-99A partition. Confirm there is no numeric probe product, parent order, Stripe probe subscription, Paddle probe subscription, or corresponding renewal action.
6. Apply the calendar's source-outcome contract: a task closed without a numeric source cannot seed a future renewal and no late substitute may be created.

## Expected result

The D10 watch row should make both `SLT-SYN-12` renewal branches conditional on task 88 having published relationship-resolved numeric fixtures, or explicitly mark them `UNVERIFIED (source absent)` for this run.

## Actual result

The D10 row describes the Stripe-boundary renewal as guaranteed even though task 88 created no fixture. A literal watcher could falsely report a product failure for an event that was impossible to schedule.

## Proof

- `kanban/tasks/088-gateway-gating-for-flexible-sync-paddle-hidden.md`: the final note states that the probe slug remained absent and no product, Shop Access rule, flex meta, cart, checkout, order, subscription, action, user, or mail mutation was started.
- `watch-schedule.md` D10 row: names the `SLT-SYN-12` Stripe-leg renewal without a source qualifier.
- `calendar.md` source-outcome contract: a task closed `UNVERIFIED (no source fixture)` does not seed future renewals, mail, retries, transitions, negatives, or teardown members.
- `automation/logs/D10-2026-08-12-night-facts.txt`: contains no `SLT Flex Gateway Probe` relationship or task-88 renewal action.
- `/home/server-manager/slt-evidence/D05-night-source-block-and-window-close.txt`: contemporaneous closeout proof for the missed D5 execution window.
- `/home/server-manager/slt-evidence/SLT-SETUP-99A-registry-D10.md`: the published D10 cohort has no task-88 probe fixture.

## Scope and counterexamples

- This is a QA-plan/oracle defect, not a product runtime defect. No product renewal was attempted or missed because no source subscription existed.
- The same source-availability rule correctly makes Box Daily, `SLT-SW-09`, `SLT-SYN-11`, `SLT-SYN-13`, and conditional `SUB_W` branches `UNVERIFIED` when their source tasks published no numeric fixture.
- Numeric `SUB_2SEG=12172` is the counterexample: its source relationship existed, natural action `16167` completed, order `13788` settled for USD 9.00, and exact success mail `27f8WhwIZNAajlXibBSsoF` arrived. That event can validly receive a PASS verdict.

## Resolution (2026-08-14)

Disposition: confirmed QA dependency/oracle defect. No product-code change was appropriate because the source task did not execute its runtime scenario.

- Current database revalidation found zero products with slug `slt-flex-gateway-probe` or title `SLT Flex Gateway Probe`, zero matching order items, zero subscriptions related to such a product, and zero task/probe scheduler rows. The D10 registry and facts remain free of task-88 numeric fixtures.
- A fresh unauthenticated browser request to the authored product URL returned the real storefront `Page not found` view with an empty cart. Screenshot: `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-SYN-12-source-absent.png`; browser error collection was empty.
- Core/pro code review confirmed the intended scenario is meaningful but unexecuted: core classifies Stripe/manual gateways as renewal-sync capable and Paddle as unsupported, filters checkout gateways, and validates classic/Store API submissions; Pro flexible-sync logic refines only an already-applicable core sync context and therefore cannot create a subscription without the missing checkout.
- `watch-schedule.md` now lists task `88` in the authoritative source-availability table. Its D7 reminder expectation and D10 Stripe/Paddle renewal expectation require relationship-resolved numeric task-88 fixtures and otherwise stay `UNVERIFIED (source absent)`.
- `calendar.md` now includes task `88` in its source-outcome overlay, marks the D10 event conditional, and conditionally includes both probes in the post-D10 teardown handoff.
- Task `119` now includes either `SLT-SYN-12` probe in its cancellation/deletion cohort only when task 88 published a numeric fixture; its current-evidence correction explicitly records that both probes are absent. This prevents both false renewal failures and unsafe teardown guesses.
- The valid `SUB_2SEG=12172` D10 relationship remains the counterexample and was not reclassified. The correction changes only QA orchestration documents; no WordPress data, gateway setting, cart, order, subscription, scheduler row, or mail state was mutated.
