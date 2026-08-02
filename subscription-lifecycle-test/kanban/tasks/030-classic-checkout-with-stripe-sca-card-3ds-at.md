---
id: 30
title: 'Classic checkout with Stripe SCA card: 3DS at signup, then requires_action on the off-session renewal'
status: todo
priority: high
created: 2026-08-02T03:43:05.505522503+02:00
updated: 2026-08-02T03:43:15.872540215+02:00
tags:
    - checkout
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h45m
depends_on:
    - 1
    - 10
    - 12
class: standard
---

> **SLT-CHK-05** · group `checkout` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · duplicate-purchase / same-account collision** — with `SLT-REN-05`

- *Problem:* Both tasks declare 'this task CREATES slt-sca / slt-sca@example.test (must not pre-exist)' and both buy SLT Daily Core with the 3DS card 4000 0027 6000 3184, then both assert the same off-session requires_action renewal, the same five _arraysubs_payment_action_* metas, the same renewal_requires_verification email and the same verification-link completion. CHK-05 is d2, REN-05 is d3. Whichever runs second aborts on its own 'no slt-sca user may exist' precondition, or - worse - proceeds and migrates CHK-05's subscription (auto_migrate_on_checkout=true, same account + same product), destroying the pending requires_action order that is the entire point of the test.
- *Required fix:* Merge. Keep SLT-CHK-05 on D2 as the sole purchaser (its 'no later than D2' constraint is the binding one - the renewal must fire, be verified, and still beat the on-hold sweep at due+24h). Fold REN-05's stronger assertions into it: the wc-stripe-confirmation=1 URL shape, the 200-with-pay-form check, the '_next_payment_date recomputed from _renewal_scheduled_date, not payment time' assertion, and the on-hold-if-late escape clause. Retire the SLT-REN-05 key or repoint it to 'verification leg of SLT-CHK-05, D3 morning'.

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-12`, `SLT-CHK-09`, `SLT-CPN-04`, `SLT-SYN-14`, `SLT-ADM-05`, `SLT-EML-06`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

---
## Objective
Buy `SLT Daily Core` on the **classic** checkout with the 3DS card `4000 0027 6000 3184`, clear the on-session challenge, then prove what the saved method does on the next off-session renewal: per REF-09 it returns `requires_action`, treated as pending not failure, so the task also completes the verification link.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (created here)
- Plugins: pro-required

## Preconditions
- SLT-CHK-01 done; `/slt-classic-checkout` works.
- **Creates account `slt-sca`**: `slt-core`, `slt-core2` and `slt-guest-d0` are already bound to SLT Daily Core and `auto_migrate_on_checkout=true` forbids repeats (C08).
- Run after 12:00 site, no later than D2. The renewal must fire by itself.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | slt-sca / slt-sca@example.test / `SltQa!2026#Pass`, Customer, billing Dhaka BD 1207 |
| Card | 4000 0027 6000 3184 (3DS), `12/34`, CVC 123 |
| Session | `cust-SLT-CHK-05` |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`. Create `slt-sca` per SETUP-03 (notification unticked, Customer, billing address); record `UID`.
2. `agent-browser --session cust-SLT-CHK-05 open ".../my-account/"` → log in as `slt-sca`; `/cart/` EMPTY; `/slt-daily-core` → **Add to cart**.
3. Open `/slt-classic-checkout`; confirm **Total $10.00**, no tax row; pick **Credit Card (Stripe)**; enter the 3DS card; **Place order**.
4. The 3DS modal is a Stripe iframe: re-`snapshot -i`, click **Complete authentication**; shot `-01-3ds.png`. On order-received record `ORDER`; shot `-02-received.png`.
5. `wait-new $PRE 180 "is active"`; `list 20`. Identify `SUB`; dump its meta to `slt-evidence/SLT-CHK-05-sub-meta.txt`; read the order row and `_gateway_*` keys.
6. Compute `k` (REF-01 §0); **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-03-pending.png`; record timestamps.
7. **Next day, after `_next_payment_date + k` passes — force nothing.** Take `PRE2`; inspect the renewal order status, `_payment_retry_attempts`, action-context meta.
8. Open `_arraysubs_payment_action_url` in the same session, complete the 3DS challenge, let payment finish; shot `-04-verify.png`.
9. Re-read `$SUB` and the renewal order; record `_completed_payments`, `_next_payment_date` and the new AS legs; append IDs to the registry; close it.

## Expected results
1. Signup: order `processing`, `10.00` USD, `stripe`, zero tax; one new `arraysubs_data` post, `arraysubs-active`, `_completed_payments=1`, `_next_payment_date=_start_date+24h`; AS legs at `+k`, `+k−6h`.
2. Saved method on both objects: `_gateway_customer_id` `^cus_`, `_gateway_payment_method_id` `^pm_`.
3. The off-session renewal returns `requires_action`: the renewal order stays **`pending`**, not `failed`; `_payment_retry_attempts` stays `0` (`handleManualPaymentPending()`).
4. Order meta `_arraysubs_payment_action_intent_id`/`_required_at`/`_url` and subscription meta `_arraysubs_payment_action_url`/`_intent_id` all set; the URL carries `wc-stripe-confirmation=1`.
5. Subscription still `arraysubs-active` here. After verification: renewal order paid, `_completed_payments=2`, `_next_payment_date` recomputed from `_renewal_scheduled_date` (start + 2 days), **not** from the payment time.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order + Processing order | signup → processing | admin / slt-sca | `New order #$ORDER`, `order has been received` | `list 20` |
| 2 | `new_subscription` + `admin_new_subscription` | → `arraysubs-active` | slt-sca / admin | `#$SUB is active`, `New subscription #$SUB` | `wait-new $PRE 180 active` |
| 3 | `renewal_requires_verification` | renewal → requires_action | slt-sca | `Verify your subscription renewal #$SUB` | `wait-new $PRE2 600 "Verify your"` |
| 4 | `payment_successful` | verification done | slt-sca | `Payment received for subscription #$SUB` | `list 20` |
| 5 | NONE EXPECTED | renewal leg | — | — | requires_action is not a failure: no `Payment failed`, no admin failure mail, no `on hold` |

## Evidence to capture
- Shots 01–04; `UID`, `SUB`, `ORDER`, renewal order id, `k`, AS timestamps; `SLT-CHK-05-sub-meta.txt`; the five action metas; `PRE`/`PRE2` + Mailpit IDs.

## Pass criteria
- [ ] 3DS completed on classic; order `processing`, $10.00, no tax; `cus_`/`pm_` stored
- [ ] Renewal order `pending` (not `failed`), `_payment_retry_attempts=0`, sub still active
- [ ] Five action-context metas present with the confirmation URL
- [ ] Verification pays it: `_completed_payments=2`, next date = start + 2 days
- [ ] Mails 1–4 present; row 5 negatives hold

## Isolation / teardown
- Creates `slt-sca`: add to the account matrix and SETUP-99B's deletion list; it buys nothing else. If verification is not done within a day the grace clock puts the sub on hold (cancel ≈ +4 days) — record it, do not mask it. Cancelled by SETUP-99A on D10.

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
