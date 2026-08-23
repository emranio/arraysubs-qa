---
id: 68
title: Disable all four admin emails, prove admin silence with customer mail unaffected, restore and re-prove
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-04
due: "2026-08-27"
estimate: 1h 30m
depends_on:
    - 55
    - 56
class: standard
---

> **SLT-EML-13** · group `emails` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the admin-email gate is independent of the customer gate: disable all four ArraySubs admin emails, fire a transition that normally sends one customer and one admin message, show the customer one still arrives and the admin one does not, then restore and re-prove. The OFF bracket is time-boxed and recorded because the toggles are site-wide.

## Scope
- Gateway: N/A (no payment taken)
- Checkout: N/A
- Account: existing (`slt2-email`, harness H1)
- Plugins: free-only

## Preconditions
- SLT-EML-11 and -12 complete; H1 is `arraysubs-active` with the `arraysubs_new_subscription` overrides cleared.
- Four admin emails (SLT-REF-04): sections `arraysubs_admin_new_subscription`, `_admin_payment_failed`, `_admin_subscription_cancelled`, `_admin_subscription_pending_cancellation`; all `customer_email = false`, recipient from **Recipient(s)** defaulting to `get_option('admin_email')` (`BaseSubscriptionEmail.php:202-209`). `admin_new_subscription` fires alongside `new_subscription` (`EmailManager.php:332,340`), so one Pending→Active exercises both gates.
- **Mutation bracket:** the four admin emails are OFF only **08:00–08:20 site on D4 (2026-08-27)**, first task of the day — Retry Daily's retry and on-hold land that afternoon and `admin_payment_failed` must not be swallowed. The prior dump, board/queue preflight, and baseline Pending→Active proof may run before 08:00 while every admin email remains ON. No unrelated checkout, product save, or status change may start before 08:30.
- The SPA has no `/settings/emails` route and `emails.admin_*` is writable only by Easy Setup, which rewrites the whole blob. **Do not run Easy Setup.**

## Test data
| Item | Value |
|---|---|
| Subscription | H1 (SLT2 Lifetime One Time, `slt2-email@example.test`) |
| Admin subject | `[mirror-help.arrayhash.com] New subscription #{subscription_id} from {customer_name}` |

## Steps
1. Before 08:00, require the board to show no SLT2 checkout/order-producing task in progress. For each of the four `woocommerce_arraysubs_admin_*_settings` options, preserve exact existence plus value and `admin_email` in `/home/server-manager/slt-evidence/SLT-EML-13-priors.txt`; the per-option presence flags govern exact restoration in step 9. In `admin-SLT-EML-13`, capture Tools → Scheduled Actions (Pending, next 2 h) as `SLT-EML-13-01-pending-clear.png`; abort if any renewal/retry/overdue/cancel action is due. Record a preparation timestamp, but do not call the OFF bracket open yet.
2. `MP0=$(mailpit-agent latest-id)`; in `--session admin-SLT-EML-13` open `admin.php?page=arraysubs-mainadmin#/subscriptions`, search exact ID H1, open **View Details**, set **Status** = `Pending` and save, then set `Active` and save.
3. `mailpit-agent wait-new "$MP0" 180 "New subscription #<H1>"` must succeed; inspect the complete MP0 delta and require exactly the customer `is active` message and the admin message for exact H1. Record both ids and the admin recipient; classify unrelated shared-site mail.
4. At or after 08:00, repeat the board/queue stop checks, set `OFF_SAVE_PRE=$(mailpit-agent latest-id)`, **open the OFF bracket**, and record the UTC open time in `/home/server-manager/slt-evidence/SLT-EML-13-bracket.txt` and the registry. At `?page=wc-settings&tab=email`, capture all four Enabled rows as `SLT-EML-13-02-list-before.png`; then open each exact section, untick **Enable this email notification**, Save, and capture `SLT-EML-13-03-disabled-new-subscription.png` through `-06-disabled-pending-cancellation.png`. Do **not** touch Recipient(s), Subject, or Heading. Re-read all four disabled values and require zero setting-save-attributable mail in the complete `OFF_SAVE_PRE` delta.
5. `MP1=$(mailpit-agent latest-id)`; set H1 `Pending`, then `Active`.
6. `mailpit-agent wait-new "$MP1" 180 "New subscription #<H1>"` — **must exit 124**; then `mailpit-agent wait-new "$MP1" 180 "is active"` — **must succeed**. Inspect the complete MP1 delta: exactly one H1-attributable ID, to slt2-email@, none to admin_email; classify unrelated mail. In `mail-SLT-EML-13`, capture the exact filtered local Mailpit state as `SLT-EML-13-07-silence.png`.
7. Set `ON_SAVE_PRE=$(mailpit-agent latest-id)`. **Restore UI-enabled state:** re-tick **Enable** on all four sections and Save, re-read all four enabled values, and require zero setting-save-attributable mail in the complete `ON_SAVE_PRE` delta. Do not close the bracket yet because exact prior storage restoration remains mandatory.
8. `MP2=$(mailpit-agent latest-id)`; set H1 `Pending` → `Active`; `mailpit-agent wait-new "$MP2" 180 "New subscription #<H1>"` must succeed and the complete delta must contain the matching customer message. Leave H1 Active and capture the restored pair in `mail-SLT-EML-13` as `SLT-EML-13-08-restored.png`.
9. Restore each option's exact step-1 storage state—delete it if it was absent, otherwise restore its preserved value—then write `/home/server-manager/slt-evidence/SLT-EML-13-after.txt` and require an exact per-option presence/value comparison. Record the UTC bracket close in the file/registry no later than 08:20 site. Close only `admin-SLT-EML-13` and `mail-SLT-EML-13`, independently review the complete evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any live gate failure becomes a dedicated issue with this task/plan, H1/parent order/user IDs and login/role, exact admin/Mailpit contexts, reproduction, expected/actual, UI/option/mail proof, and the baseline/restored transition counterexamples; create or update the mandatory `qa/issues/` kanban card.

**Restore-first failure rule:** once step 4 disables any admin email, every browser, transition, mail, timing, or evidence failure jumps immediately to step 7 and then the exact step-1 storage restoration in step 9 before diagnosis.

## Expected results
1. Baseline: one Pending→Active yields exactly two messages — customer `Your subscription #<H1> is active` and admin `New subscription #<H1> from SLT2 Email`, the latter To `get_option('admin_email')`.
2. With all four disabled the same transition yields exactly **one** message (the customer one); the admin `mailpit-agent wait-new` exits 124 and no admin-addressed message exists in the bracket.
3. The customer email's subject, body and recipient are unchanged by the admin toggles; after restore both messages return unchanged.
4. The live ON proof uses `enabled => yes`, then final per-option presence/value exactly matches step 1; all four `arraysubs_settings.emails.admin_*` booleans remain `true`, proving the WooCommerce checkbox is a second, independent gate without accepted residue.
5. Positive proof of the other three admin emails comes from their owners: `admin_subscription_pending_cancellation` from `SLT-EML-07` on **D3 = 2026-08-26**, `admin_payment_failed` on **D4 = 2026-08-27**, and `admin_subscription_cancelled` when `SLT-DUN-04` observes the D7 terminal cancellation on **2026-08-30** (reconciled by the D8 morning watch on **2026-08-31**).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | Steps 2 and 8, ON | slt2-email@ / admin_email | `is active` / `New subscription #<H1>` | `mailpit-agent wait-new "$MP0" 180`, `mailpit-agent wait-new "$MP2" 180` |
| 2 | NONE EXPECTED — admin_new_subscription | Step 5, admin OFF | — | — | `mailpit-agent wait-new "$MP1" 180 "New subscription #<H1>"` exits **124** |
| 3 | new_subscription | Step 5, admin OFF | slt2-email@example.test | `is active` | `mailpit-agent wait-new "$MP1" 180 "is active"` succeeds |

## Evidence to capture
- Screenshots `SLT-EML-13-01-pending-clear.png`, `-02-list-before.png`, named `-03` through `-06` disabled sections, `-07-silence.png`, `-08-restored.png`; bracket file; exact priors/after presence-value diff; `OFF_SAVE_PRE`, `ON_SAVE_PRE`, `MP0/MP1/MP2`, delivered IDs/To headers; session/review proof.

## Pass criteria
- [ ] Baseline transition produces both a customer and an admin message
- [ ] All four disabled via the WooCommerce checkbox only (Easy Setup never opened)
- [ ] Admin message absent while OFF (exit 124); zero admin-addressed mail in the bracket
- [ ] Board/queue pre-flights and the baseline proof ran while all admin emails were ON; the OFF bracket opened only at step 4, closed by 08:20 site, and its timestamps were published
- [ ] Customer message still delivered while admin emails are OFF
- [ ] All four restored and re-proved live; `emails.admin_*` booleans untouched
- [ ] Bracket under 20 min with UTC recorded; watch days for the other three noted
- [ ] Exact prior storage restored on every path; task sessions closed and card reviewed to done

## Isolation / teardown
- Global settings touched: the `enabled` flag on four `woocommerce_arraysubs_admin_*_settings` rows, OFF only inside the recorded bracket. Any admin notification for a non-SLT2 subscription in that window is lost — hence the short, pre-checked bracket away from the Retry Daily afternoon events.
- Handed on: H1 left `arraysubs-active`; bracket timestamps posted to the registry. Restores: live enabled state proved, then all four options returned to their exact prior presence/value.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
