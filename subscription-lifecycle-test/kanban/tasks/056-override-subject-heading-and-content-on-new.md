---
id: 56
title: Override subject, heading and content on New Subscription with merge tags and prove real-value rendering
status: todo
priority: high
created: 2026-08-02T03:43:07.920891214+02:00
updated: 2026-08-02T03:43:18.325832629+02:00
tags:
    - email
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 55
class: standard
---

> **SLT-EML-12** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-CHK-09`, `SLT-CPN-04`, `SLT-SYN-14`, `SLT-CHK-05`, `SLT-ADM-05`, `SLT-EML-06`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-13`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Prove the Subject / Email heading / Additional content overrides on an ArraySubs email are honoured and that every merge tag resolves to the subscription's real values, not the constructor's sample placeholders. Also record, with grep evidence, that `emails.<id>.subject`/`.body` in `arraysubs_settings` have no consumer — the WooCommerce per-email fields are the only working override surface.

## Scope
- Gateway: N/A (no payment taken)
- Checkout: N/A
- Account: existing (`slt-email`, harness H1)
- Plugins: free-only

## Preconditions
- SLT-EML-11 complete: `slt-email` exists (First name `SLT`); harness **H1** on `SLT Lifetime One Time` is `arraysubs-active`, `_next_payment_date` empty, no scheduled action. SLT-SYN-04 holds an exclusive 09:00–11:00 bracket on D3 — **run after 12:00**.
- Code basis: overrides come from the WooCommerce per-email option row via `WC_Email::format_string()`; tags are the keys built in `BaseSubscriptionEmail::__construct()` (`:53-80`), repopulated live in `populate_placeholders()` (`:268-300`). `new_subscription` fires on transition to `arraysubs-active` from pending/trial/auto-draft (`EmailManager.php:325-344`).

## Test data
| Item | Value |
|---|---|
| Subscription | H1 (SLT Lifetime One Time, $49.00, `slt-email@`), section `arraysubs_new_subscription` |
| Subject override | `SLT-EML-12 {customer_first_name} :: sub {subscription_id} :: {product_name} :: {recurring_amount}` |
| Heading override | `Hello {customer_first_name}, subscription {subscription_id} is {subscription_status}` |
| Additional content | `PROBE start={start_date} next={next_payment_date} period={billing_period} pay={payment_method}` |

## Steps
1. Dump `woocommerce_arraysubs_new_subscription_settings` to `/home/server-manager/slt-evidence/SLT-EML-12-prior.txt` (expect "Could not get").
2. From the plugins root run `grep -rn "emails\..*\.subject\|emails\..*\.body" arraysubs/src/Features/Emails/ arraysubs/src/functions/email-helpers.php`; save the empty result to `-no-consumer.txt`.
3. `MP0=$(mailpit-agent latest-id)`; `--session admin` open `page=arraysubs-mainadmin#/subscriptions`, open H1, **Status** = `Pending`, save (fallback `post.php?post=H1&action=edit`); `latest-id` must not move.
4. Set H1 **Status** = `Active`; `wait-new $MP0 180 "is active"`; `text latest`; record the DEFAULT subject and heading verbatim.
5. Open `/wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_new_subscription` → `snapshot -i`; screenshot the **Available placeholders** desc-tip on Subject; paste the three overrides, keep **Enable** ticked, Save. Screenshot; record UTC open.
6. `MP1=$(mailpit-agent latest-id)`; set H1 `Pending`, then `Active`; `wait-new $MP1 180 "SLT-EML-12"`; `text/html latest`; screenshot the render.
7. Transcribe subject, heading and PROBE **verbatim**; compare tag by tag against `wp post meta list H1 --keys=_recurring_amount,_billing_period,_start_date,_next_payment_date --allow-root`.
8. **Restore.** Same URL: clear Subject, Heading and Additional content, keep Enable ticked, Save. Screenshot; record UTC close.
9. `MP2=$(mailpit-agent latest-id)`; set H1 `Pending` → `Active`; `wait-new $MP2 180 "is active"` must return the DEFAULT subject. Leave H1 `Active`; dump the row to `-after.txt`.

## Expected results
1. Step 4 subject is exactly `[mirror-help.arrayhash.com] Your subscription #<H1> is active`; setting status to Pending sends nothing (verify `latest-id` at each Pending set).
2. Step 6 subject renders `SLT-EML-12 SLT :: sub <H1> :: SLT Lifetime One Time :: $49.00`; heading `Hello SLT, subscription <H1> is Active`.
3. PROBE resolves `{start_date}` to `_start_date` in site format, `{billing_period}` to the lifetime rendering of `arraysubs_format_billing_period(1,'lifetime')` and `{payment_method}` to `_payment_method_title` or `N/A`.
4. **`{next_payment_date}` probe:** H1's meta is empty, so the tag must render empty or an explicit no-value string. A fabricated date (today+30d — the constructor sample at `BaseSubscriptionEmail.php:64-67`) is a **BUG**: file `issues/SLT-EML-12-lifetime-next-payment.md`.
5. No sample values (`John`, `Sample Subscription Product`, `$29.99`, `every month`, `12345`) appear anywhere; after step 9 the default subject is back and the row holds `enabled=yes` with blank subject/heading.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, default subject | Steps 4 and 9 Pending→Active | slt-email@example.test | `Your subscription #<H1> is active` | `wait-new $MP0 180` / `wait-new $MP2 180` |
| 2 | new_subscription, overridden | Step 6 Pending→Active | slt-email@example.test | `SLT-EML-12 SLT :: sub <H1>` | `wait-new $MP1 180 "SLT-EML-12"` |
| 3 | admin_new_subscription | Steps 4, 6, 9 | admin_email | `New subscription #<H1> from SLT Email` | `list 20` — count 3, subject unchanged |

## Evidence to capture
- Screenshots `SLT-EML-12-01-placeholders.png`, `-02-saved.png`, `-03-overridden.png`, `-04-cleared.png`, `-05-restored.png`; the three text dumps; verbatim subject/heading/PROBE beside the meta values; `MP0/MP1/MP2` and message ids.

## Pass criteria
- [ ] Default subject captured before any override; every merge tag in subject, heading and PROBE resolves to H1's real values
- [ ] `{next_payment_date}` on a lifetime sub recorded; bug filed if fabricated
- [ ] Zero sample placeholder values leak; admin subject unaffected
- [ ] Overrides cleared, default restored by a live send, no-consumer grep stored

## Isolation / teardown
- Global setting touched: `arraysubs_new_subscription` subject/heading/additional_content, non-default only inside the recorded bracket. A non-SLT `new_subscription` mail in that bracket would carry the SLT subject — keep it short; abort if another SLT checkout task is running.
- Handed on: H1 left `arraysubs-active` for SLT-EML-13; tag list posted to the registry. Restores: all three fields cleared in step 8.


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
