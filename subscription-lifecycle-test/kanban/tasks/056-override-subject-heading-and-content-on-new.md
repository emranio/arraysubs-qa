---
id: 56
title: Override subject, heading and content on New Subscription with merge tags and prove real-value rendering
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-03
due: "2026-08-26"
estimate: 1h 30m
depends_on:
    - 55
class: standard
---

> **SLT-EML-12** · group `emails` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the Subject / Email heading / Additional content overrides on an ArraySubs email are honoured and that every merge tag resolves to the subscription's real values, not sample placeholders. Revalidate both the ArraySubs settings fields and WooCommerce per-email fields on this build; do not assume which surface is authoritative from the previous run.

## Scope
- Gateway: N/A (no payment taken)
- Checkout: N/A
- Account: existing (`slt2-email`, harness H1)
- Plugins: free-only

## Preconditions
- SLT-EML-11 complete: `slt2-email` exists (First name `SLT`); harness **H1** on `SLT2 Lifetime One Time` is `arraysubs-active`, `_next_payment_date` empty, no scheduled action.
- **Exclusive mutation bracket: 21:00–21:40 site on D3 (2026-08-26), after every D3 checkout has closed.** Preparation, the prior dump, and the default-subject baseline may run from 20:15 while no override is active. Before the first status transition, require the board to show no checkout/order-producing task in progress. No other task may place an order, activate a subscription, or change email settings inside the 21:00–21:40 override bracket.
- Code basis: overrides come from the WooCommerce per-email option row via `WC_Email::format_string()`; tags are the keys built in `BaseSubscriptionEmail::__construct()` (`:53-80`), repopulated live in `populate_placeholders()` (`:268-300`). `new_subscription` fires on transition to `arraysubs-active` from pending/trial/auto-draft (`EmailManager.php:325-344`).

## Test data
| Item | Value |
|---|---|
| Subscription | H1 (SLT2 Lifetime One Time, $49.00, `slt2-email@`), section `arraysubs_new_subscription` |
| Subject override | `SLT-EML-12 {customer_first_name} :: sub {subscription_id} :: {product_name} :: {recurring_amount}` |
| Heading override | `Hello {customer_first_name}, subscription {subscription_id} is {subscription_status}` |
| Additional content | `PROBE start={start_date} next={next_payment_date} period={billing_period} pay={payment_method}` |

## Steps
1. During the 20:15–20:59 preparation window, require the precondition board check to pass, resolve registry alias `H1` into shell variable `H1`, and abort unless `[[ "$H1" =~ ^[0-9]+$ ]]`. Record whether `woocommerce_arraysubs_new_subscription_settings` exists and preserve its exact value in `/home/server-manager/slt-evidence/SLT-EML-12-prior.txt` (expected absent); the presence flag governs exact restoration in step 9. Record a preparation timestamp, but do not call the override bracket open yet.
2. Record the current values exposed by both settings surfaces. Save a redacted before-state to `/home/server-manager/slt-evidence/SLT-EML-12-settings-before.txt`; the live message generated below determines whether each saved field is consumed.
3. `MP0=$(mailpit-agent latest-id)`; in `--session admin-SLT-EML-12` open `admin.php?page=arraysubs-mainadmin#/subscriptions`, search exact ID H1, open **View Details**, set **Status** = `Pending`, and save; the complete delta after MP0 must contain no message attributable to that Pending transition. There is no `post.php` fallback for this subscription post type.
4. Set H1 **Status** = `Active`; `mailpit-agent wait-new "$MP0" 180 "is active"`; save the exact customer-message id and `mailpit-agent text <matched-id>`; inspect the complete MP0 delta for the matching admin message and record the DEFAULT subject and heading verbatim.
5. At or after 21:00 site, repeat the board stop check and require no known subscription-activation action inside the next 40 minutes. In `admin-SLT-EML-12`, open `/wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_new_subscription` → `snapshot -i`; capture the **Available placeholders** desc-tip as `SLT-EML-12-01-placeholders.png`. Immediately before saving, set `OVERRIDE_SAVE_PRE=$(mailpit-agent latest-id)`, record the UTC bracket-open timestamp in `/home/server-manager/slt-evidence/SLT-EML-12-bracket.txt` and the registry, paste the three overrides, keep **Enable** ticked, Save, and capture `SLT-EML-12-02-saved.png`. Re-read the exact option and require zero setting-save-attributable mail in the bounded `OVERRIDE_SAVE_PRE` delta.
6. `MP1=$(mailpit-agent latest-id)`; set H1 `Pending`, then `Active`; `mailpit-agent wait-new "$MP1" 180 "SLT-EML-12"`; save the exact customer-message ID, inspect the complete MP1 delta for the matching admin message, and run both `mailpit-agent text <matched-id>` and `mailpit-agent html <matched-id>`. In exact session `mail-SLT-EML-12`, open the matched message in the local Mailpit UI and capture `SLT-EML-12-03-overridden.png`.
7. Transcribe subject, heading and PROBE **verbatim**; compare tag by tag against `wp post meta list "$H1" --keys=_recurring_amount,_billing_period,_start_date,_next_payment_date --allow-root`.
8. Set `CLEAR_SAVE_PRE=$(mailpit-agent latest-id)`. **Restore UI defaults.** On the same URL clear Subject, Heading and Additional content, keep Enable ticked, Save, capture `SLT-EML-12-04-cleared.png`, re-read the blank fields, and require zero setting-save-attributable mail in the bounded `CLEAR_SAVE_PRE` delta.
9. `MP2=$(mailpit-agent latest-id)`; set H1 `Pending` → `Active`; `mailpit-agent wait-new "$MP2" 180 "is active"` must return the DEFAULT subject, and the complete delta must contain exactly one unchanged admin message. Capture that matched default render in `mail-SLT-EML-12` as `SLT-EML-12-05-restored.png`; leave H1 `Active`. Restore the option's exact step-1 storage state—delete it if it was absent, otherwise restore the preserved value—and require an exact presence/value comparison. Record the UTC close timestamp in the bracket file and registry no later than 21:40 site, close only `admin-SLT-EML-12` and `mail-SLT-EML-12`, independently review the complete evidence, move the card through `review` to `done`, and ensure Review returns to zero. If the live `{next_payment_date}` or another render assertion fails, create the named dedicated issue with this task/plan, H1/parent order/user IDs and login/role, exact admin/Mailpit contexts, reproduction, expected/actual, mail/meta/reference proof and the default-render counterexample; create or update the mandatory `qa/issues/` kanban card.

**Restore-first failure rule:** after step 5 saves the override, any browser, transition, mail, or evidence failure jumps immediately to step 8 and the exact step-1 storage restoration in step 9 before diagnosis.

## Expected results
1. Step 4 subject is exactly `[mirror-help.arrayhash.com] Your subscription #<H1> is active`; setting status to Pending sends nothing (verify `mailpit-agent latest-id` at each Pending set).
2. Step 6 subject renders `SLT-EML-12 SLT :: sub <H1> :: SLT2 Lifetime One Time :: $49.00`; heading `Hello SLT, subscription <H1> is Active`.
3. PROBE resolves `{start_date}` to `_start_date` in site format, `{billing_period}` to the lifetime rendering of `arraysubs_format_billing_period(1,'lifetime')` and `{payment_method}` to `_payment_method_title` or `N/A`.
4. **`{next_payment_date}` probe:** H1's meta is empty, so the tag must render empty or an explicit no-value string. A fabricated date (today+30d — the constructor sample at `BaseSubscriptionEmail.php:64-67`) is a **BUG**: file `qa/issues/` kanban card named `SLT-EML-12-lifetime-next-payment`.
5. No sample values (`John`, `Sample Subscription Product`, `$29.99`, `every month`, `12345`) appear anywhere; after the live default proof, final option presence/value exactly matches step 1 (normally the temporary row is absent again).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, default subject | Steps 4 and 9 Pending→Active | slt2-email@example.test | `Your subscription #<H1> is active` | `mailpit-agent wait-new "$MP0" 180` / `mailpit-agent wait-new "$MP2" 180` |
| 2 | new_subscription, overridden | Step 6 Pending→Active | slt2-email@example.test | `SLT-EML-12 SLT :: sub <H1>` | `mailpit-agent wait-new "$MP1" 180 "SLT-EML-12"` |
| 3 | admin_new_subscription | Steps 4, 6, 9 | admin_email | `New subscription #<H1> from SLT2 Email` | complete MP0/MP1/MP2 deltas — exactly one per transition, subject unchanged |

## Evidence to capture
- Screenshots `SLT-EML-12-01-placeholders.png`, `-02-saved.png`, `-03-overridden.png`, `-04-cleared.png`, `-05-restored.png`; bracket file; exact prior/final presence-value proof; three text dumps; verbatim subject/heading/PROBE beside meta; `OVERRIDE_SAVE_PRE`, `CLEAR_SAVE_PRE`, `MP0/MP1/MP2`, and every message ID; session/review proof.

## Pass criteria
- [ ] Default subject captured before any override; every merge tag in subject, heading and PROBE resolves to H1's real values
- [ ] `{next_payment_date}` on a lifetime sub recorded; bug filed if fabricated
- [ ] Zero sample placeholder values leak; admin subject unaffected
- [ ] Override bracket opened only at the first non-default save, after the board pre-flight, and closed by 21:40 site; preparation stayed outside the bracket and timestamps were published
- [ ] Overrides cleared, default restored by a live send, and both settings surfaces mapped from fresh evidence
- [ ] Exact prior option presence/value restored; task sessions closed and card reviewed to done

## Isolation / teardown
- Global setting touched: `arraysubs_new_subscription` subject/heading/additional_content, non-default only inside the recorded bracket. A non-SLT `new_subscription` mail in that bracket would carry the SLT2 subject — keep it short; abort if another SLT2 checkout task is running.
- Handed on: H1 left `arraysubs-active` for SLT-EML-13; tag list posted to the registry. Restores: exact prior option presence/value after the live blank/default proof.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
