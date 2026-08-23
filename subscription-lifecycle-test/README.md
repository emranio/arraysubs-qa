# Subscription lifecycle full regression — SLT2

Fresh browser-first regression of the complete ArraySubs/ArraySubsPro subscription system after feature fixes and migration of automatic-payment services into the free plugin.

- **Granular lifecycle cards:** 133
- **Human execution window:** D0 `2026-08-23` through D11 `2026-09-03` — exactly 12 days
- **Read-only tail watch:** D12 `2026-09-04`
- **Allowlisted teardown:** D13 `2026-09-05`
- **Automatic gateways:** Stripe and Paddle only
- **Priority:** Stripe critical path first; Paddle supported parity second
- **Site:** `https://mirror-help.arrayhash.com`
- **Board:** `qa/subscription-lifecycle-test/kanban/`
- **Fresh evidence:** `/home/server-manager/slt-evidence/`

PayPal and Mollie are deliberately deferred because their required secrets are unavailable. They are not configured, selected, executed, mocked, or treated as blockers for this Stripe/Paddle cycle. BACS/manual-invoice checks remain internal subscription-engine controls; they are not a third automatic-gateway track.

## Reset and isolation

The prior lifecycle board notes, issue records, reports, watcher logs and active evidence were removed from this cycle. Historical external evidence is preserved read-only at `/home/server-manager/slt-evidence-archive-2026-08-22/`; it is not valid evidence for SLT2.

Use only the new namespace:

| Artifact | Namespace |
|---|---|
| Product title | `SLT2 ` |
| Product/page slug | `slt2-` |
| User login/email local part | `slt2-` |
| Coupon | `SLT2...` |
| Browser session | role plus task key |

D0 must inventory legacy `SLT`, new `SLT2`, and all non-test entities. Legacy/non-SLT2 records are read-only controls. Every created product, variation, page, user, coupon, order, subscription, scheduled action and provider object is registered by exact ID. Prefix searches are discovery only; D13 deletion uses the signed allowlist.

## Required coverage

The board contains atomic cards for all of these areas, plus final parity/audit cards that reject any missing cell:

- Simple, variable and every variation; free/core Subscription Box/bundle; grouped products/children; lifetime, finite, trial, signup-fee, renewal-price and flexible-sync products.
- Subscription-only, regular-only, subscription+regular mixed carts; quantity; same-product merge; two distinct subscriptions; box/grouped composition; Block and classic checkout.
- Stripe new/saved method, SCA signup/off-session, declines, retries, recovery, webhook replay, payment update, refund and cancellation.
- Paddle hosted success/abandon/failure, catalog sync, pending-to-webhook-paid checkout, remote renewal, method update, switch price sync, replay, refund/cancel and every supported/unsupported capability boundary.
- Initial order/subscription linkage, invoices, natural renewals 1/2+, renewal sync and segment math, early/late/skip renewal, trials, expiry, pause/on-hold, grace, recovery, pending/immediate cancellation and reactivation.
- Percent/fixed/one-time/recurring/N-cycle coupons, proration, signup/switch fees, quantity, discounts, refunds and exact renewal totals.
- Upgrade, downgrade, crossgrade, variable switch, customer/admin switch and remote Paddle price synchronization.
- Retention reasons, discount, pause, downgrade and contact-support offers; eligibility, dismissal/decline/accept, use/cooldown/history, every condition, analytics KPIs/charts/filters.
- Customer portal, member/shop access, admin screens, capabilities, REST, notes/audits, emails, scheduler locks/idempotency, logs, loading/confirmation/toast behavior and cleanup.

Task 133 publishes the final cell-level matrix. A high-level card title is never proof for a missing atomic row.

## Gateway ownership contract

ArraySubs core must own its Stripe/Paddle integration services, renewal, retry, webhook, REST, reconciliation, refund and customer-payment behavior. The official WooCommerce Stripe host remains vendor-owned. ArraySubsPro may consume core contracts but must not be required for automatic payments or register duplicate payment hooks/routes/services.

Task 117 creates an exclusive Pro-off bracket on D10 and repeats real Stripe/Paddle operations with core only, then restores the exact plugin state and checks for duplication.

## Timing contract

- Re-derive timezone, currency, tax, week start, checkout pages, plugin versions and all counts on D0. No authored ID/count is authoritative.
- WP-Cron/provider events fire naturally. Never drain an Action Scheduler hook or group.
- Renewal assertions use the live `_next_payment_date`, deterministic spread and exact action IDs. Save Mailpit baselines inside the final five-minute pre-gate window.
- Poll in bounded calls no longer than 60 seconds; do not use long blind sleeps.
- D8 is the only controlled date-meta time-travel bracket and may touch only its exact SLT2 allowlist.
- D10 is the only Pro-off bracket. D11 restores settings/state. D12 is strictly read-only.

## Execution workflow

Before QA, read the workspace `AGENTS.md`, `qa/stages/README.md`, the relevant stage README/tasks in numeric order, then this README, `calendar.md`, `plan-audit.md`, `watch-schedule.md` and the full lifecycle card.

Use Vercel `agent-browser` for every browser flow. Load `agent-browser skills get core`; load `agent-browser skills get dogfood` for exploration/bug hunts. Use isolated sessions, snapshot-and-ref actions, re-snapshot after DOM/navigation changes, capture before/after/error screenshots and close sessions when finished. WP-CLI always includes `--allow-root`. Mailpit evidence uses a trigger-specific baseline and complete delta.

Update all three tracking surfaces during execution:

1. Lifecycle task in this plan's `kanban/`.
2. Matching one-to-one lifecycle progress task in `qa/progress/kanban/`.
3. One complete regression/blocker card in `qa/issues/kanban/` for each new issue.

Always `cd` into the target board directory before `kanban-md` commands.

## Verdict policy

- `done` means every mandatory assertion passed with fresh browser plus data/action/mail/provider evidence.
- A timed future gate stays `in-progress` with exact next check time.
- A missing prerequisite or failed assertion creates/updates the shared issue card and leaves the lifecycle card `blocked` until the fix is rerun successfully.
- There is no “unverified but done,” waiver, silent skip or umbrella PASS.
- Continue safe independent cards after recording a failure; never fabricate a source fixture, provider event, action, order or result.

Every issue card includes lifecycle/progress task IDs and plan path; affected subscription/order/action/product/provider IDs or `N/A`; users/login/email/roles or `N/A`; gateway, exact URL/session/gate, reproduction, expected/actual, concrete UI/network/meta/DB/action/log/Mailpit proof and relevant counterexamples.

## Cleanup

Task 118 restores settings and cancels only evidence-complete subscriptions on D11; it deletes nothing and preserves the exact D12 tail. Task 119 signs the read-only D12 reconciliation. Task 120 may delete only signed registry IDs after every other card is done and non-SLT2 equality/ownership closure passes. If anything remains blocked, teardown stops and preserves all fixtures/evidence.
