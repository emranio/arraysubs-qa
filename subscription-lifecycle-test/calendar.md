# SLT2 execution calendar — 12-day granular cycle

D0 through D11 are the requested 12 execution days. D12 is a read-only tail watch; D13 is guarded teardown. A card's due date is its start/primary execution date. Cards with natural renewal/trial/retry/provider gates remain in progress and are revisited at the exact registered time.

## Standing order every day

1. Read the day's `watch-schedule.md` row and inspect `future-gates.tsv` before untimed work.
2. Observe due natural gates with their immutable pre-gate Mailpit/action/provider baselines.
3. Execute the cards below in listed order, respecting each card's finer dependencies and exclusive brackets.
4. Update lifecycle, shared progress and shared issue boards; close sessions; publish later gates.

Only `SLT2`/`slt2-*` registry fixtures may be mutated. Never overlap global/product/plugin settings, the same account/cart, or one subscription's timed action. Stripe is always executed before corresponding Paddle parity. PayPal/Mollie are not run.

## D0 — Sunday 2026-08-23: baseline and critical Stripe spine

Exact order: `10 SETUP-01` → `11 SETUP-02` → `131 GW-00` → `12 SETUP-03` → products `5,6,7,8` → checkouts/lifecycle `1,2,3,4,14` → publish the first future gates for `9`.

- Establish fresh environment/settings/entity/action/mail/provider baselines and SLT2 registry.
- Verify Stripe/Paddle readiness without purchase; create accounts and core simple/finite/lifetime/week-sync products.
- Execute Stripe block/classic simple checkout, lifetime/finite controls and segment-1 sync purchase.
- Start the first unattended Stripe renewal; do not force it.

## D1 — Monday 2026-08-24: products, coupons, Paddle readiness and first watch

Exact order after due gates: `13` → products/settings `20,21,22,23,25,26,27,28` → checkout/email/lifecycle `15,16,17,18,19,24`.

- Complete simple/month/daily sync, retry/Paddle products and six-coupon catalog.
- Publish the fresh Stripe/Paddle capability matrix and remote Paddle catalog IDs.
- Test guest registration, recurring/one-time coupons, signup email pair, renewal-price crossover and invoice-before-charge.

## D2 — Tuesday 2026-08-25: Paddle, trials, SCA, retry and sync arithmetic

Exact order after due gates: products `37,38,40,44` → checkouts `29,30,31,32,33` → dependent email/lifecycle `34,35,36,45,46` → timed renewals `41,42,43`.

- Complete real Paddle hosted checkout and first remote-renewal handoff.
- Execute Stripe signup/off-session SCA, $0 trials/card collection and genuine decline/retry.
- Test N-cycle coupons, UTC boundary rendering, member access, week/month proration and variation-level sync.

## D3 — Wednesday 2026-08-26: exclusive sync bracket, catalog completion and email/admin paths

Observe due gates first. Run `61` alone in its 09:00-11:00 settings bracket and restore it. Then: products `58,39,59,60` → retention foundation `121` → admin/checkout/email `47,48,49,50,51,52,53,54,55,56,57`.

- Finish signup-fee, grouped, free Subscription Box and switch-ladder fixtures.
- Verify cancellation reasons/Other/required validation.
- Test admin-created invoice scheduling, HPOS renewal links, Stripe/Paddle pay links, quantity, coupon rejection and the complete reminder/status/customization/Mailpit email set.

## D4 — Thursday 2026-08-27: box/variable products, retention and concurrency

Exact order after due gates: `71,65,72` → `63,64,66,68,69,70,74,75` → retention `73,122,123`.

- Buy the free Subscription Box and seed plan-switch accounts.
- Test admin rescheduling, second subscription, first grace transition, admin-email toggle, concurrent renewals, portal details and sync segment/exclusivity.
- Start full retention discount-cycle and pause/manual/auto-resume tests.

## D5 — Friday 2026-08-28: carts, retries, payment method, switching and Stripe matrix

Exact order after due gates: cart/product `77,78,79,80` → dunning/email/audit `67,76,81,82,83` → portal/lifecycle `84,85` → switches/sync `86,87,88` → retention `124,125` → Stripe matrix `128`.

- Test two-subscription refusal, regular guest control, variable/grouped flows and invoice rendering.
- Complete retries 2/3/cap, failure emails, dual-gateway replay, skip/undo notifications and Stripe method update.
- Test upgrade and gateway sync gating; run downgrade/contact retention conditions.
- Fill every Stripe simple/variable/box/grouped/mixed/SCA/cancel matrix cell.

## D6 — Saturday 2026-08-29: Paddle parity, mixed cart and full switch/cancel edges

Exact order after due gates: `62,89,90,91,92,93,94,95,96,97,98,99` → Paddle matrix `129`.

- Verify quantity-proration rounding, status ladder, mixed-cart renewal isolation and email rendering.
- Test early renewal, Paddle method update, crossgrade, Paddle remote-price upgrade, variable switching and pending-cancel/reactivation.
- Run calendar-overflow sync edge and all supported/unsupported Paddle product/cart/lifecycle rows.

## D7 — Sunday 2026-08-30: recovery, later renewals and cross-gateway integrity

Exact order after due gates: `100,101,102,103,104,105,106` → `130`.

- Audit list filters/delete guards, terminal grace cancellation, mid-grace recovery and unpaid-invoice portal payment.
- Compare admin/customer upgrades, switch fee, and second/third synced renewals on the grid.
- Reconcile Stripe/Paddle identity, idempotency, migration, updates, refunds/cancels and allowlists.

## D8 — Monday 2026-08-31: sole time-travel bracket and retention eligibility

Exact order after read-only natural-gate capture: exclusive targeted-action owner `112` → task `99` D8 overflow/renewal leg → `107,108,109,110,111` → retention matrix `126`.

- Run the only controlled date/action bracket first, then complete task 99's separately allowlisted month-overflow target.
- Verify expiry/reactivation/auto-downgrade, natural expiring-soon and Stripe card-expiring, plus negative email sweep.
- Test late-renew catch-up and customer downgrade.
- Only tasks 112 and task 99's declared D8 leg may perform targeted date/action mutation, sequentially, with non-SLT2 before/after equality.
- Complete every retention reason/status/product/history/dismiss/decline/accept row.

## D9 — Tuesday 2026-09-01: admin, refunds, permissions, analytics and log audit

Exact order after due gates: `113,114,115,116,127`.

- Reconcile subscription detail fields, Stripe/Paddle supported refunds, role/capability boundaries and end-window actions/orders/logs.
- Reconcile eight retention KPIs, charts, activity, filters, date boundaries and exports to exact source events.

## D10 — Wednesday 2026-09-02: core-only ownership and independent final matrix

Exclusive order: `117 OWN-01` → restore Pro → `132 GW-04` → `133 MATRIX-99`.

- Repeat real Stripe/Paddle checkout/renewal/retry/webhook/method/refund operations with Pro inactive.
- Restore plugin state and prove no duplicate hooks/routes/actions.
- Independently reconcile browser, HPOS, meta, scheduler, provider and Mailpit layers.
- Reject any missing product/cart/lifecycle/discount/switch/retention/gateway cell.

## D11 — Thursday 2026-09-03: exact restore and tail handoff

Run `118 SETUP-99A` only after all D0-D10 required evidence is captured.

- Restore D0 settings, access rules and plugin state exactly.
- Cancel only the evidence-complete cohort; delete nothing.
- Publish disjoint exhaustive cancel/keep-alive lists and D12 exact gates.

## D12 — Friday 2026-09-04: read-only watch

Run `119 WATCH-12`. No checkout, save, status change, action execution, webhook replay, provider mutation or cleanup. Sign every registered expected/negative tail row across Stripe/Paddle/UI/HPOS/actions/mail/logs.

## D13 — Saturday 2026-09-05: allowlisted teardown

Run `120 SETUP-99B` only when all other cards are done and the D12 report authorizes cleanup. Cancel/delete exact registry IDs in dependency order, prove ownership closure/non-SLT2 equality/zero orphans, then remove watcher cron.

## Completion rule

A day tracker may close only when every due execution leg passed or has a linked blocked lifecycle/issue card with an exact retry plan. The overall cycle cannot pass until all 133 cards are done. No failed or missing cell is converted into a waiver.
