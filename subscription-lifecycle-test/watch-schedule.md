# SLT2 watch schedule — Stripe/Paddle, D0 through D12

This schedule governs cron-assisted observation around the 12-day human run. It does not authorize
fixture creation, checkout, settings changes, action execution or teardown. Human/browser work
follows `calendar.md`; the watcher reads exact registry gates and reconciles events that already
occurred naturally.

## Installed phases

Cron uses `Europe/Berlin` while the site timezone is re-read on D0. During this window the planned
host/site mapping is:

| Phase | Host time | Planned site time | Purpose |
|---|---:|---:|---|
| early-morning | 02:10 | 06:10 | Overnight actions, provider webhooks, mail and status transitions |
| late-morning | 06:10 | 10:10 | Carry gates, browser-ready preflight and prior-day reconciliation |
| afternoon | 12:10 | 16:10 | Same-day purchase/action handoffs and provider settlement |
| evening | 15:10 | 19:10 | Renewal/retry/retention/provider gates and settings-bracket closure |
| night | 17:42 | 21:42 | Final-five-minute baselines, late gates and next-day handoff |

The runner exits before D0. D0–D11 may reconcile and update tracking; D12 is read-only. D13 invokes
only the guarded teardown prompt. No phase polls blindly for more than 60 seconds.

## Runtime authority

The following fresh artifacts are authoritative:

- `evidence/fixture-registry.tsv`
- `evidence/future-gates.tsv`
- the creating lifecycle card and its exact evidence
- the matching `watch-reports/Dxx-YYYY-MM-DD.md`
- live Action Scheduler, HPOS/meta, Stripe/Paddle and Mailpit re-queries

Every future-gate row must include task key/ID, gateway, fixture alias and numeric IDs, event type,
expected/forbidden outcome, due UTC/site time, spread/provider owner, immutable Mailpit baseline
deadline, observation cutoff and evidence pointer. Authored numeric action/provider IDs are invalid.

## Checks in every phase

1. Read all due/overdue `future-gates.tsv` rows and all in-progress timed cards.
2. Capture a just-in-time Mailpit baseline only when the row's baseline deadline is reached.
3. Reconcile browser-visible status/date, HPOS order/line/meta, subscription meta/notes/access,
   exact scheduler rows/logs, Stripe/Paddle objects/events and the complete Mailpit delta.
4. Prove at-most-once order, charge, transaction, webhook effect, refund, note, action and email.
5. Classify unrelated shared-site activity and prove registered non-SLT2 controls did not drift.
6. Update the lifecycle card and matching `qa/progress/` day card. A failure creates/updates a
   complete shared `qa/issues/` kanban card and leaves the lifecycle card blocked.

Stripe is primary. Paddle receives full supported parity plus tested capability negatives. PayPal and
Mollie are never configured, selected, invoked, mocked or scored in this cycle. Manual/BACS rows are
internal invoice-engine controls only.

## Daily phase ownership

### D0 — 2026-08-23

- Early/late morning: tasks 10, 11 and 131 establish environment, settings, ownership, gateway,
  namespace, non-SLT2, Action Scheduler and Mailpit baselines before any fixture mutation.
- Afternoon: tasks 12, 5–8 create the exact account/product catalog; verify registry completeness.
- Evening/night: tasks 1–4 and 14 execute Stripe first. Task 9 publishes, but does not force, the
  first invoice/charge gates with exact `k`, action IDs and baseline deadlines.
- Before exit: require empty carts/sessions, no unregistered fixture, and a signed D01 carry list.

### D1 — 2026-08-24

- Early: verify task 9's not-yet-due or naturally completed rows strictly against their live gates.
- Late morning/afternoon: tasks 13, 20–23 and 25–28 create sync/retry/Paddle/coupon controls; task 26
  publishes Paddle remote capability/catalog readiness without inventing secrets.
- Afternoon/evening: tasks 15–19 and 24 create guest/coupon/admin/stepped/invoice cohorts.
- Night: register every D2 Stripe renewal, Paddle webhook/renewal, coupon-cycle, retry and invoice
  baseline; no hook/group drain.

### D2 — 2026-08-25

- Early: reconcile task 9 and 41 Stripe renewal chain; resolve exact order/action/mail relationships.
- Late morning/afternoon: tasks 37, 38, 40 and 44 prepare free/trial/variation fixtures; tasks 29–33
  execute Paddle hosted checkout, Stripe SCA/trials/coupons and a genuine Stripe decline.
- Evening: tasks 34–36 and 45–46 publish trial-ending, UTC-boundary, access and sync arithmetic gates.
- Night: tasks 42–43 observe Paddle renewal and Stripe off-session SCA only at their exact fresh
  provider/action gates. Preserve task 35's 23:45 site-time bracket.

### D3 — 2026-08-26

- Early: settle due D2 natural renewals, trial/retry/provider rows before any settings mutation.
- Late morning: task 61 alone owns its 09:00–11:00 global-sync bracket and must prove exact restore.
- Afternoon: tasks 58, 39, 59 and 60 complete signup-fee, grouped, free Subscription Box and plan
  ladder products; task 121 validates cancellation reasons.
- Evening/night: tasks 47–57 execute admin/order/invoice/quantity/email paths. Respect task 56's
  exclusive email override bracket and publish task 52 reminder plus D4/D5 status/mail gates.

### D4 — 2026-08-27

- Early: reconcile task 54 cancellation mail/status, task 35 midnight renewal and task 52 reminder.
- Late morning/afternoon: tasks 71, 65 and 72 create variable/box/switch cohorts, then tasks 63, 64,
  66, 68–70, 74 and 75 run in calendar order.
- Evening: tasks 73, 122 and 123 begin retention discount/pause fixtures. Register each exact
  discount cycle, 30-day schedule-control action and separate 1-day natural auto-resume gate.
- Night: hand off D5 retry, method-update, switch, box, concurrent and retention observations.

### D5 — 2026-08-28

- Early: reconcile all D4 renewal/retry/retention gates and require closed settings brackets.
- Late morning/afternoon: tasks 77–80, 67, 76 and 81–83 cover cart/grouped/variable, invoice render,
  notes, retry ladder, failure mail and exact Stripe/Paddle webhook replay.
- Afternoon/evening: tasks 84–88, 124 and 125 cover skip, Stripe method update, upgrade, sync gating,
  downgrade/support offers. Task 128 fills the complete Stripe product/cart/lifecycle matrix.
- Night: publish D6 Stripe/Paddle/mixed-cart/switch/sync and retention gates; no matrix cell may be
  recorded as pass without an exact evidence pointer.

### D6 — 2026-08-29

- Early: reconcile week/box/variable/mixed and provider rows due overnight.
- Late morning/afternoon: tasks 62 and 89–99 execute quantity, status, mixed cart, rendering,
  orphan safety, early renew, Paddle method update, crossgrade, Paddle price switch, variation switch,
  cancellation/reactivation and task 99's D6 month-segment purchase.
- Evening: task 129 completes supported Paddle parity and capability negatives.
- Night: preserve task 99's D8 target and register D7 recovery/second-renewal/cross-gateway gates.

### D7 — 2026-08-30

- Early: reconcile all D6 natural renewals, Paddle webhooks and retry/grace events.
- Late morning/afternoon: tasks 100–106 audit admin list, terminal grace, recovery, unpaid invoice,
  admin switch, switch fee and synced renewal grid.
- Evening: task 130 reconciles Stripe/Paddle identity, provider ownership, dates, replays, updates,
  refunds/cancels and cross-binding negatives.
- Night: freeze D8 non-SLT2/action/mail baselines and verify no unfinished mutation bracket remains.

### D8 — 2026-08-31

- Early: capture due natural events read-only. Do not mutate before the signed preflight.
- First exclusive bracket: task 112 snapshots every subscription, then executes only the exact
  allowlisted SUB_M and SUB_W3 invoice/charge IDs one at a time.
- Second exclusive bracket: task 99's D8 leg uses task 112's signed non-SLT2 baseline and only its
  exact SUB_S3 pair. Require empty non-SLT2 diff between brackets.
- After restore/reconciliation: tasks 107–111 and 126 cover expiry/reactivation/auto-downgrade,
  natural expiring/card reminders, negative mail, late renewal, downgrade and full retention matrix.
- Night: publish D9 analytics/refund/permission/log gates and prove no remaining date mutation.

### D9 — 2026-09-01

- Early: reconcile all D8 targeted/natural rows and action/provider/mail cardinality.
- Late morning/afternoon: tasks 113–116 cover detail UI, Stripe/Paddle supported refunds,
  capability boundaries and end-window logs/actions/orders.
- Evening: task 127 reconciles retention KPIs/charts/logs/filters/export to exact source events.
- Night: require every D0–D9 cell to have fresh evidence or a linked blocked issue before D10.

### D10 — 2026-09-02

- Early: settle all due Stripe/Paddle gates and capture plugin/hook/route/action/provider fingerprints.
- Exclusive task 117: deactivate only ArraySubsPro, repeat real core-owned Stripe/Paddle operations,
  then restore Pro immediately on any exit path and prove no duplicate registration.
- After restore: task 132 performs independent browser/HPOS/meta/action/provider/Mailpit audit; task
  133 builds the cell-level final matrix and executes/adds any missing safe row.
- Night: no ownership or matrix card closes while a mandatory row/issue is open.

### D11 — 2026-09-03

- Early: settle exact D10 carries and confirm no source evidence is still pending.
- Task 118 restores every D0 setting/plugin/rule/email state by exact presence/value and cancels only
  the signed evidence-complete cohort. It deletes nothing.
- Afternoon/night: publish disjoint cancel/keep-alive lists and every exact D12 expected/negative
  gate. Prove non-SLT2 and provider state equality.

### D12 — 2026-09-04 (read-only)

- Task 119 owns every phase. No checkout, save, status/date/meta change, action execution, replay,
  provider mutation or cleanup is permitted.
- Reconcile every retained Stripe/Paddle invoice/provider event, payment, order, status/date, action,
  note, access, mail and log through the signed cutoff.
- Repeat silence/duplicate/failed-action/restored-state/non-SLT2 checks and publish the exact D13
  allowlist plus latest safe teardown timestamp.

## D13 teardown gate — 2026-09-05

Task 120 may run only after task 119's signed report exists, every other lifecycle card is done, all
future gates are reconciled, current time is past the latest safe gate, and exact registry ownership
closure passes. Any unmet condition causes a read-only retry. Successful teardown removes only the
signed SLT2 fixtures/evidence-owned provider objects and then uninstalls this watcher.
