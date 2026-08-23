---
id: 85
title: Update the Stripe payment method from My Account and prove the next unassisted renewal charges the new card
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-05
due: "2026-08-28"
estimate: 1.5h
depends_on:
    - 70
    - 11
    - 5
class: standard
---

> **SLT-MYA-02** · group `admin` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Add a second Stripe card from the portal, make it default, and prove which subscription inherits it and that the next unattended renewal charges it. Built around a known hazard: the setup-intent path resolves the subscription via `findSubscriptionByGatewayCustomer()` with `numberposts => 1` (`PaymentMetaNormalizer.php:171-192`), so only ONE of slt2-core's can be updated.

## Scope
- Gateway: Stripe test
- Checkout: N/A (My Account payment-methods flow)
- Account: existing (`slt2-core`)
- Plugins: both

## Preconditions
- SLT-MYA-01 done (slt2-core subscription-ID table exists). Stripe `saved_cards: yes`.
- **Act 08:00-11:00 site on D5 (2026-08-28)**, before any slt2-core anniversary time (all D0/D3 buys were after 12:00), so the first renewal after the change lands that same afternoon.

## Test data
| Item | Value |
|---|---|
| Account | slt2-core / `SltQa!2026#Pass`, session `--session customer-MYA-02-SLT-MYA-02` |
| Card on file | `4242 4242 4242 4242` (visa, from the D0 checkout) |
| New card | `5555 5555 5555 4444`, exp `12/34`, CVC `123` - extra Stripe test card added by this task; behaves like 4242 but brand `mastercard`/last4 `4444` make the proof unambiguous. Record it in the registry. |

## Steps
1. Resolve numeric `USER_ID` and the complete exact registry-owned slt2-core subscription ID set; require each owner relationship and no duplicate. Compute each offset with the README argv command, record exact `_next_payment_date` plus invoice/charge action IDs/gates, and choose one old-card `CONTROL_SUB` whose first post-change natural charge can be observed.
2. Dump the same payment/retry keys for every numeric ID into `/home/server-manager/slt-evidence/SLT-MYA-02-pm-before.txt` in stable ID/key order. Save exact note/order sets and set `MB02=$(mailpit-agent latest-id)` immediately before adding the card.
3. Open `/my-account/payment-methods/` -> log in -> `snapshot -i` -> screenshot; record the saved method (brand, last4, expiry, Default) and the buttons offered.
4. Open `/my-account/add-payment-method/`, enter the new card only inside hosted fields without capturing it, submit, then **Make default** on Mastercard 4444 and capture the safe post-submit state as `SLT-MYA-02-02-methods-default.png`. If already saved, never detach/delete it: create/update the fixture-contamination issue and leave the card blocked until a clean dedicated method can be tested.
5. Poll for the setup-intent/audit change in intervals of at most 60 seconds for five minutes; dump stable after-state to `-pm-after.txt`, diff, and require exactly one changed numeric `SUB_TARGET`. If none or more than one changed, record the finding and do not invent a target/baseline, but continue every safe read-only check.
6. When exact `SUB_TARGET` exists, capture its portal Payment Method row as `SLT-MYA-02-03-detail-row.png`; publish target/control, old/new `pm_` IDs, exact charge action IDs/gates, and both `gate−5m` deadlines. Reconcile the complete `MB02` delta with zero task-attributable mail, close only `customer-MYA-02-SLT-MYA-02`, and leave the card `in-progress` for the natural charges.
7. For each target/control charge, take its owner baseline only inside `[exact gate−300s, gate)`, never earlier and never force. Poll in ≤60-second calls through the 10-minute cutoff. Resolve each renewal order by exact subscription/cycle plus reverse meta; require target `_stripe_source_id` = new `pm_` and brand Mastercard, control old `pm_`/Visa, exact unchanged amounts, and complete owner-filtered mail deltas. Capture exact order meta as `SLT-MYA-02-04-renewal-order-meta.png` in `admin-SLT-MYA-02-RENEW`, then close it.
8. If any assertion fails, create/update the mandatory `qa/issues/` kanban card with task/stage/plan, subscription/order/action/payment-method IDs, user context, exact URLs/sessions/gates, reproduction, expected/actual and proof. Never expose full card data. Close sessions and mark done only after the clean update and next renewal pass.

## Expected results
1. The methods page initially lists one saved method (visa 4242, Default); adding succeeds cleanly and mastercard 4444 exp 12/34 becomes Default.
2. Exactly one subscription changes, gaining `_payment_method_brand=mastercard`, `_payment_method_last4=4444` and a `_payment_method_updated_at` inside this task's window; its `_last_payment_failure*` metas are gone (retry reset) and its detail page shows the new card.
3. Watch day D6 (2026-08-29): SUB_TARGET's 2026-08-28 PM renewal charged the new `pm_...` at the unchanged amount, while a control subscription's renewal that night still used the old visa `pm_...` - the limit is documented, not assumed.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Adding / defaulting a card; also the invoice leg at due+offset-6h, suppressed for auto-payment subs (`EmailManager.php:504-510`) | - | - | Complete delta after `MB02`; zero task-attributable or matching invoice mail, while unrelated/background mail is allowed and classified |
| 2 | payment_successful | 2026-08-28 PM renewal | slt2-core | exact numeric target/control IDs | final-five-minute owner baselines, repeated ≤60-second waits, exact matches and complete deltas |

## Evidence to capture
- Screenshots `SLT-MYA-02-01-methods-before.png`, `-02-methods-default.png`, `-03-detail-row.png`, `-04-renewal-order-meta.png`; both meta dumps and their diff; `SUB_TARGET`; both `pm_...` ids; offsets; renewal order ids with `_stripe_source_id`/`_stripe_card_brand`; `RENEW_PRE`; matched and full-delta Mailpit ids.

## Pass criteria
- [ ] New card added and defaulted from the portal with no errors
- [ ] Exactly one subscription's card metas updated and its retry metas cleared
- [ ] SUB_TARGET's 2026-08-28 PM renewal charged the new pm (watch day D6 = 2026-08-29)
- [ ] Control subscription still on the old pm, documented
- [ ] Every `_next_payment_date` advanced exactly one cycle; no unexpected mail; invoice mail suppressed
- [ ] Exact target/control renewals and phase sessions reconciled; QA issue cards only; final evidence reviewed to done

## Isolation / teardown
- The new card stays for the rest of the window and whichever subscription inherited it keeps renewing on it; record this in the registry so no later task files a false bug about a changed brand/last4.
- No token deleted, no setting changed. Close only the exact customer and renewal-admin task sessions.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
