---
id: 85
title: Update the Stripe payment method from My Account and prove the next unassisted renewal charges the new card
status: done
priority: critical
created: 2026-08-02T03:43:10.273603127+02:00
updated: 2026-08-08T02:16:43.96394195+02:00
started: 2026-08-08T02:16:43.963941059+02:00
completed: 2026-08-08T02:16:43.963941059+02:00
tags:
    - admin
    - portal
    - day-05
due: "2026-08-07"
estimate: 1.5h
depends_on:
    - 70
    - 11
    - 5
class: standard
---

> **SLT-MYA-02** · group `admin` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Add a second Stripe card from the portal, make it default, and prove which subscription inherits it and that the next unattended renewal charges it. Built around a known hazard: the setup-intent path resolves the subscription via `findSubscriptionByGatewayCustomer()` with `numberposts => 1` (`PaymentMetaNormalizer.php:171-192`), so only ONE of slt-core's can be updated.

## Scope
- Gateway: Stripe test
- Checkout: N/A (My Account payment-methods flow)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-MYA-01 done (slt-core subscription-ID table exists). Stripe `saved_cards: yes`.
- **Act 08:00-11:00 site on D5 (2026-08-07)**, before any slt-core anniversary time (all D0/D3 buys were after 12:00), so the first renewal after the change lands that same afternoon.

## Test data
| Item | Value |
|---|---|
| Account | slt-core / `SltQa!2026#Pass`, session `--session customer-MYA-02-SLT-MYA-02` |
| Card on file | `4242 4242 4242 4242` (visa, from the D0 checkout) |
| New card | `5555 5555 5555 4444`, exp `12/34`, CVC `123` - extra Stripe test card added by this task; behaves like 4242 but brand `mastercard`/last4 `4444` make the proof unambiguous. Record it in the registry. |

## Steps
1. Resolve numeric `USER_ID` and the complete exact registry-owned slt-core subscription ID set; require each owner relationship and no duplicate. Compute each offset with the README argv command, record exact `_next_payment_date` plus invoice/charge action IDs/gates, and choose one old-card `CONTROL_SUB` whose first post-change natural charge can be observed.
2. Dump the same payment/retry keys for every numeric ID into `/home/server-manager/slt-evidence/SLT-MYA-02-pm-before.txt` in stable ID/key order. Save exact note/order sets and set `MB02=$(mailpit-agent latest-id)` immediately before adding the card.
3. Open `/my-account/payment-methods/` -> log in -> `snapshot -i` -> screenshot; record the saved method (brand, last4, expiry, Default) and the buttons offered.
4. Open `/my-account/add-payment-method/`, enter the new card only inside hosted fields without capturing it, submit, then **Make default** on Mastercard 4444 and capture the safe post-submit state as `SLT-MYA-02-02-methods-default.png`. If already saved, never detach/delete it: record fixture contamination, complete read-only diffs, close the session, and close the card through review as `UNVERIFIED` rather than stopping mid-card.
5. Poll for the setup-intent/audit change in intervals of at most 60 seconds for five minutes; dump stable after-state to `-pm-after.txt`, diff, and require exactly one changed numeric `SUB_TARGET`. If none or more than one changed, record the finding and do not invent a target/baseline, but continue every safe read-only check.
6. When exact `SUB_TARGET` exists, capture its portal Payment Method row as `SLT-MYA-02-03-detail-row.png`; publish target/control, old/new `pm_` IDs, exact charge action IDs/gates, and both `gate−5m` deadlines. Reconcile the complete `MB02` delta with zero task-attributable mail, close only `customer-MYA-02-SLT-MYA-02`, and leave the card `in-progress` for the natural charges.
7. For each target/control charge, take its owner baseline only inside `[exact gate−300s, gate)`, never earlier and never force. Poll in ≤60-second calls through the 10-minute cutoff. Resolve each renewal order by exact subscription/cycle plus reverse meta; require target `_stripe_source_id` = new `pm_` and brand Mastercard, control old `pm_`/Visa, exact unchanged amounts, and complete owner-filtered mail deltas. Capture exact order meta as `SLT-MYA-02-04-renewal-order-meta.png` in `admin-SLT-MYA-02-RENEW`, then close it.
8. If any assertion fails, create a standalone `issues/SLT-MYA-02-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, subscription/order/action/payment-method IDs, user ID/login/email/role, exact URLs/sessions/gates, reproduction, expected/actual, UI/meta/audit/Mailpit/screenshot proof, and the old-card control. Never expose full card data. After the D6 read or UNVERIFIED branch, independently review all evidence, close exact sessions, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. The methods page initially lists one saved method (visa 4242, Default); adding succeeds cleanly and mastercard 4444 exp 12/34 becomes Default.
2. Exactly one subscription changes, gaining `_payment_method_brand=mastercard`, `_payment_method_last4=4444` and a `_payment_method_updated_at` inside this task's window; its `_last_payment_failure*` metas are gone (retry reset) and its detail page shows the new card.
3. Watch day D6 (2026-08-08): SUB_TARGET's 2026-08-07 PM renewal charged the new `pm_...` at the unchanged amount, while a control subscription's renewal that night still used the old visa `pm_...` - the limit is documented, not assumed.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Adding / defaulting a card; also the invoice leg at due+offset-6h, suppressed for auto-payment subs (`EmailManager.php:504-510`) | - | - | Complete delta after `MB02`; zero task-attributable or matching invoice mail, while unrelated/background mail is allowed and classified |
| 2 | payment_successful | 2026-08-07 PM renewal | slt-core | exact numeric target/control IDs | final-five-minute owner baselines, repeated ≤60-second waits, exact matches and complete deltas |

## Evidence to capture
- Screenshots `SLT-MYA-02-01-methods-before.png`, `-02-methods-default.png`, `-03-detail-row.png`, `-04-renewal-order-meta.png`; both meta dumps and their diff; `SUB_TARGET`; both `pm_...` ids; offsets; renewal order ids with `_stripe_source_id`/`_stripe_card_brand`; `RENEW_PRE`; matched and full-delta Mailpit ids.

## Pass criteria
- [ ] New card added and defaulted from the portal with no errors
- [ ] Exactly one subscription's card metas updated and its retry metas cleared
- [ ] SUB_TARGET's 2026-08-07 PM renewal charged the new pm (watch day D6 = 2026-08-08)
- [ ] Control subscription still on the old pm, documented
- [ ] Every `_next_payment_date` advanced exactly one cycle; no unexpected mail; invoice mail suppressed
- [ ] Exact target/control renewals and phase sessions reconciled; standalone findings only; final evidence reviewed to done

## Isolation / teardown
- The new card stays for the rest of the window and whichever subscription inherited it keeps renewing on it; record this in the registry so no later task files a false bug about a changed brand/last4.
- No token deleted, no setting changed. Close only the exact customer and renewal-admin task sessions.

---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 20:35
Planning correction on 2026-08-06: task 70 / SLT-MYA-01 was closed UNVERIFIED, so its authored portal evidence table does not exist as originally planned. If this card is executed on a later valid day, it must rely on the live slt-core subscription portfolio re-check already captured in the D5 preflight pack, not on nonexistent task-70 signoff evidence.

[[2026-08-06]] Thu 21:30
Additional live D5 preflight on Thursday, August 6, 2026:
- saved payment tokens for user `347` currently equal exactly one Stripe token:
  - token `17`, default `yes`, brand `visa`, last4 `4242`, expiry `12/2034`
- no Mastercard `4444` token exists yet, so task 85 starts without saved-card contamination
- current renewable Stripe subscriptions for `slt-core` all point at the same old-card source:
  - `11959`: `_gateway_payment_method_id=pm_1TzyfSJG5OzSNVs21dxjlcMi`, brand `visa`, last4 `4242`, next `2026-08-07 12:39:05Z`
  - `12234`: `_gateway_payment_method_id=pm_1TzyfSJG5OzSNVs21dxjlcMi`, brand `visa`, last4 `4242`, next `2026-08-07 07:50:13Z`
  - `12655`: `_gateway_payment_method_id=pm_1TzyfSJG5OzSNVs21dxjlcMi`, brand `visa`, last4 `4242`, next `2026-08-07 10:44:10Z`
- exact future charge gates for the Friday, August 7, 2026 observation window:
  - `12655` charge `2026-08-07 11:19:21Z` / site `2026-08-07 17:19:21`
  - `12234` charge `2026-08-07 11:43:36Z` / site `2026-08-07 17:43:36`
  - `11959` charge `2026-08-07 15:37:52Z` / site `2026-08-07 21:37:52`
Execution note for the future D5 run: do not pre-name `SUB_TARGET` from this preflight alone. Run the portal add/default flow first, diff the three live renewable subscriptions, treat the sole Mastercard `4444` change as `SUB_TARGET`, and use the earliest unchanged active renewable subscription as `CONTROL_SUB`.

[[2026-08-07]] Fri 02:31
D05 early-morning claim: read-only preparation completed with no site mutation. Exact interactive gate remains 2026-08-07 08:00-11:00 site. Current candidate charge actions: 15254 / sub 12655 at 17:19:21 site, 15267 / sub 12234 at 17:43:36, and 15328 / sub 11959 at 21:37:52. SUB_TARGET and CONTROL_SUB remain intentionally unresolved until the post-card diff.

[[2026-08-07]] Fri 04:49
D05 08:00 portal leg PASS at 2026-08-07 08:28:56 site: user 347 added token 31 / Mastercard 4444 exp 12/2034 and made it account default; old Visa token 17 retained. Exact five-sub diff selected sole SUB_TARGET 12655 with new pm_1U1dWhJG5OzSNVs2QhWrz7vj; CONTROL_SUB 12234 remains on old pm_1TzyfSJG5OzSNVs21dxjlcMi / Visa 4242. Target action 15254 gates at 17:19:21 site (baseline 17:14:21-17:19:20); control action 15267 gates at 17:43:36 (baseline 17:38:36-17:43:35). MB02 1fEVaKxuFnCCLwCxNzeZ7G stayed unchanged; no mail. Evidence: /home/server-manager/slt-evidence/SLT-MYA-02-pm-before.txt, -pm-after.txt, -pm-diff.txt, screenshots 01/02/03/05. Card intentionally remains in-progress for both natural charges and D6 review; no action forced.

[[2026-08-07]] Fri 07:46
D05 late-morning invoice-leg follow-up PASS (read-only natural observation). Target baseline 11:14:26 site; action 15253 due 11:19:21, WP-Cron 11:20:06-11:20:14; exact pending order 13047, cycle 3,  Stripe, new pm/Mastercard 4444 retained, no invoice mail/guard. Control baseline 11:39:11 site; action 15266 due 11:43:36, WP-Cron 11:44:04-11:44:10; exact pending order 13050, cycle 5, 0 Stripe, old pm/Visa 4242 retained, no invoice mail/guard. Relationship and reverse-meta cardinality both one; Mailpit remained 1fEVaKxuFnCCLwCxNzeZ7G. Card remains in-progress for target charge 17:19:21 (baseline 17:14:21-17:19:20), control charge 17:43:36 (baseline 17:38:36-17:43:35), then D6 review. Evidence: /home/server-manager/slt-evidence/D05-2026-08-07-late-morning-verification.txt.

[[2026-08-07]] Fri 15:43
D05 evening read-only follow-up: persisted target order 13047 completed $9.00 using the new Mastercard-4444 provider source; control order 13050 completed $20.00 using old Visa-4242 source. Exact actions 15254/15267 completed via WP Cron; exact mail pairs reconciled. Required final-five-minute owner baselines were missed and are UNVERIFIED, not reconstructed. Browser evidence: /home/server-manager/slt-evidence/SLT-MYA-02-04-renewal-order-meta.png and -04b-control-renewal-order.png; reconciliation: /home/server-manager/slt-evidence/SLT-MYA-02-D05-evening-renewals.txt. Later SLT-EML-02 payment exposed cross-subscription regression; issue: issues/SLT-EML-02-manual-payment-updates-wrong-subscription.md. Preserve current state. Future authored final read/review: D06 2026-08-08 06:10 site, before next invoice legs; then review->done.

[[2026-08-08]] Sat 02:16
D06 06:15 site final read/self-review complete. Overall UNVERIFIED because the two authored final-five-minute D05 charge baselines were missed and cannot be reconstructed; persisted target/control behavior passed. Evidence: /home/server-manager/slt-evidence/SLT-MYA-02-D06-final-review.txt. Existing cross-task finding: issues/SLT-EML-02-manual-payment-updates-wrong-subscription.md. No follow-up remains; exact sessions were already closed and no state was changed.
