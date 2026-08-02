---
id: 67
title: 'Renewal invoice email: content, UTC+6 due date, and a pay-link that resolves to a real payable order'
status: todo
priority: high
created: 2026-08-02T03:43:08.994907162+02:00
updated: 2026-08-02T03:43:19.46063579+02:00
tags:
    - email
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 1h30m
depends_on:
    - 5
    - 52
class: standard
---

> **SLT-EML-02** · group `emails` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`, `SLT-EML-13`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`high` · same-subscription collision / ambiguous target** — with `SLT-LIFE-02`, `SLT-EML-05`, `SLT-EML-15`, `SLT-MYA-02`

- *Problem:* SLT-LIFE-02 (d6) targets 'S1 - a live arraysubs-active SLT Daily Core subscription from the SLT-CHK-* run' without naming it, and its arithmetic uses $10.00 day/1, which describes SUB_CORE (slt-core, the control spine). It consumes one cycle by paying it early, replaces both legs and shifts the anniversary. SLT-EML-05 runs on the SAME day (d6) and also consumes one SUB_CORE cycle by setting _auto_renew=off and paying the invoice manually. Two tasks eating the same cycle on the same day makes both results unreadable, and either one silently invalidates the D1-D12 watch's 'SLT Daily Core renews $10.00 unattended every afternoon' baseline that REN-01/REN-02/EML-15/ADM-06 established.
- *Required fix:* Pin SLT-LIFE-02's S1 to SLT-CHK-02's subscription (slt-core2 + SLT Daily Core, day/1, $10.00, Stripe, saved token, unsynced, no pending skip) - structurally identical to the spine and claimed by nothing else after D0. Name the subscription id explicitly in LIFE-02's Test data and preconditions, and keep its step 8 registry note ('slt-core2's cycle N was paid early on 2026-08-08') so the watch does not read the missing unattended renewal as a failure. Leave SUB_CORE to EML-05 on D6. Add a standing registry section 'control-spine reservations' naming SUB_CORE's owning tasks per day.

**`medium` · shared-per-subscription-meta vs published watch contract** — with `SLT-EML-05`, `SLT-EML-15`, `SLT-REN-02`

- *Problem:* SLT-EML-15 (d2) publishes to the registry the reconciled expected-mail set for one SLT Daily Core renewal, explicitly asserting 'zero renewal_invoice - suppressed for automatic subs with auto-renew on' and states 'this is the reference the D3-D12 watch uses to classify daily renewal mail'. SLT-EML-02 (d4) and SLT-EML-05 (d6) then each write _auto_renew=off on that very subscription for one cycle, deliberately producing an 'Invoice for subscription #SUB_CORE' email plus a manually-paid renewal on D4 and D6. The watcher, reading EML-15's table, will classify both as UNMAPPED and file them as leaks - and will also see the charge leg leave the order in a non-standard state.
- *Required fix:* EML-02 and EML-05 must each post a dated exception to the registry BEFORE flipping the meta ('SUB_CORE cycle due <date>: _auto_renew=off, one renewal_invoice + one customer-paid renewal order expected; suppression restored at <time>'), and the watch schedule rows for D4/D5 and D6/D7 must carry those exceptions as expected rather than negative. Add to both tasks a pass criterion 'the registry exception exists and was posted before the meta write' and a teardown criterion 'the next cycle after restore sends no invoice mail'.

---
## Objective
The renewal-invoice email is suppressed for automatic-payment subscriptions with auto-renew ON (`EmailManager.php:495-513`), so on Stripe it never sends. Set `_auto_renew = off` on one SLT subscription so the manual-invoice branch runs, then assert the email's four content rows (subscription id, product, amount, due date rendered in site UTC+6) and prove the "Pay for this order" link opens a genuinely payable order-pay page.

## Scope
- Gateway: Stripe test
- Checkout: block (order-pay endpoint on checkout page 8 — do NOT swap page 8)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT Daily Core bought by `slt-core` on D0 (2026-08-02 PM) → `SUB_CORE`, $10.00/day, `arraysubs-active`.
- SLT-REF-01: invoice leg `arraysubs_generate_renewal_invoice` at `D + k − 6h`; charge leg `arraysubs_process_renewal` at `D + k`. `_auto_renew` is read only by the email gate — `RenewalProcessor`/`PaymentProcessor` ignore it, so the charge still runs at `D + k`. **The pay-link is payable only inside that 6-hour window.**
- Once-per-order guard `_arraysubs_renewal_invoice_email_sent`.
- Writes ONE per-subscription meta on an SLT subscription; changes no global setting.

## Test data
| Item | Value |
|---|---|
| Product / subscription | SLT Daily Core $10.00 day/1 / `SUB_CORE` (slt-core@example.test) |
| Meta written | `_auto_renew = off` (deleted in teardown) |
| Card | `4242 4242 4242 4242`, future expiry, CVC 123 |
| Expected total | $10.00 — no tax line, no fee line |
| Subject | `[<site title>] Invoice for subscription #SUB_CORE` |

## Steps
1. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-SUB_CORE"));printf("%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));'` → k.
2. `wp post meta get SUB_CORE _next_payment_date --allow-root` → `D`; compute `D+k−6h` and `D+k` in UTC and site time.
3. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB_CORE"`; screenshot both pending legs and confirm their timestamps match step 2.
4. Gate: if the invoice leg is under 15 minutes away, stop and repeat from step 2 on the next cycle (D5). Record the decision.
5. `wp post meta update SUB_CORE _auto_renew off --allow-root`; read it back.
6. `PREV=$(mailpit-agent latest-id)`; then `mailpit-agent wait-new "$PREV" 3600 "Invoice for subscription #SUB_CORE"`. Exit 124 = failure: screenshot the AS row and subscription notes before touching anything.
7. `mailpit-agent show <id>`; `mailpit-agent html <id>`; extract the four table rows and the pay URL.
8. Date oracle: `wp eval 'echo arraysubs_format_date_local(get_post_meta(SUB_CORE,"_next_payment_date",true));' --allow-root`.
9. `agent-browser --session customer-eml02 open "https://mirror-help.arrayhash.com/my-account/"`, log in as `slt-core` / `SltQa!2026#Pass`, then open the pay URL → `snapshot -i`, screenshot.
10. Pay with 4242; snapshot the result. Then `wp post meta list SUB_CORE --keys=_next_payment_date,_completed_payments,_pending_renewal_order_id --allow-root` and check the order in wp-admin → Orders.
11. `wp post meta delete SUB_CORE _auto_renew --allow-root`; snapshot `latest-id`; on the next cycle confirm no `Invoice for subscription` mail. `agent-browser close --session customer-eml02`.

## Expected results
1. Exactly one `Invoice for subscription #SUB_CORE` mail, within 2 minutes of `D+k−6h`, to `slt-core@example.test` only.
2. Body: "A renewal order has been created for your subscription #SUB_CORE."; heading `Order #<renewal order id>`.
3. Rows: Subscription `#SUB_CORE`; Product `SLT Daily Core`; Amount `$10.00` (no tax, no fee); Due Date exactly equal to the step-8 oracle — the UTC+6 rendering of `D`. When `D`'s UTC time is ≥ 18:00 the rendered date is the NEXT calendar day; assert that shift explicitly.
4. Pay URL matches `https://mirror-help.arrayhash.com/checkout/order-pay/<order_id>/?pay_for_order=true&key=wc_order_...`, returns HTTP 200, shows total $10.00 and a payment control — not a 404, not "can no longer be paid for", no console error.
5. Payment moves the order to `processing`/`completed`; `_completed_payments` +1; `_pending_renewal_order_id` cleared; `_next_payment_date` advances exactly 1 day from `_renewal_scheduled_date`.
6. Order carries `_arraysubs_renewal_invoice_email_sent = yes`; no second invoice mail for that order.
7. After teardown the next cycle sends NO invoice email — suppression restored.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_invoice` | `arraysubs_renewal_invoice_created` at `D+k−6h` | slt-core@example.test | `Invoice for subscription #SUB_CORE` | `mailpit-agent wait-new $PREV 3600 ...` |
| 2 | `payment_successful` | manual payment, step 10 | slt-core@example.test | `Payment received for subscription #SUB_CORE` | `mailpit-agent list 50` |
| 3 | NONE EXPECTED | next cycle after teardown | — | `Invoice for subscription` | no new message |

## Evidence to capture
- `SLT-EML-02-01-pending-legs.png`, `-02-invoice-email.png`, `-03-order-pay-page.png`, `-04-order-paid.png`.
- k, `D`, both leg times; mailpit ids; renewal order id; raw pay URL; the step-8 oracle string; console/network errors.

## Pass criteria
- [ ] Invoice email sent once, at `D+k−6h`, customer only
- [ ] Four content rows exact, $10.00, no tax line
- [ ] Due date matches `arraysubs_format_date_local`; UTC+6 day-shift asserted
- [ ] Pay link 200s on a genuinely payable order
- [ ] Payment advances the subscription normally
- [ ] `_auto_renew` deleted; suppression proven restored

## Isolation / teardown
- Restore: delete `_auto_renew` on `SUB_CORE` (step 11) — mandatory, record before/after. Page 8 untouched; session closed.
- Hands the invoice mailpit id and the `_auto_renew=off` recipe to SLT-EML-05.

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
