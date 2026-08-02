---
id: 91
title: HTML vs plain-text rendering of the renewal invoice and payment-received emails, and link resolution
status: todo
priority: medium
created: 2026-08-02T03:43:10.747716504+02:00
updated: 2026-08-02T03:43:21.865454225+02:00
tags:
    - email
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 1h30m
depends_on:
    - 67
    - 53
class: standard
---

> **SLT-EML-05** · group `emails` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-10`, `SLT-EML-12`, `SLT-EML-13`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`high` · same-subscription collision / ambiguous target** — with `SLT-LIFE-02`, `SLT-EML-02`, `SLT-EML-15`, `SLT-MYA-02`

- *Problem:* SLT-LIFE-02 (d6) targets 'S1 - a live arraysubs-active SLT Daily Core subscription from the SLT-CHK-* run' without naming it, and its arithmetic uses $10.00 day/1, which describes SUB_CORE (slt-core, the control spine). It consumes one cycle by paying it early, replaces both legs and shifts the anniversary. SLT-EML-05 runs on the SAME day (d6) and also consumes one SUB_CORE cycle by setting _auto_renew=off and paying the invoice manually. Two tasks eating the same cycle on the same day makes both results unreadable, and either one silently invalidates the D1-D12 watch's 'SLT Daily Core renews $10.00 unattended every afternoon' baseline that REN-01/REN-02/EML-15/ADM-06 established.
- *Required fix:* Pin SLT-LIFE-02's S1 to SLT-CHK-02's subscription (slt-core2 + SLT Daily Core, day/1, $10.00, Stripe, saved token, unsynced, no pending skip) - structurally identical to the spine and claimed by nothing else after D0. Name the subscription id explicitly in LIFE-02's Test data and preconditions, and keep its step 8 registry note ('slt-core2's cycle N was paid early on 2026-08-08') so the watch does not read the missing unattended renewal as a failure. Leave SUB_CORE to EML-05 on D6. Add a standing registry section 'control-spine reservations' naming SUB_CORE's owning tasks per day.

**`medium` · shared-per-subscription-meta vs published watch contract** — with `SLT-EML-02`, `SLT-EML-15`, `SLT-REN-02`

- *Problem:* SLT-EML-15 (d2) publishes to the registry the reconciled expected-mail set for one SLT Daily Core renewal, explicitly asserting 'zero renewal_invoice - suppressed for automatic subs with auto-renew on' and states 'this is the reference the D3-D12 watch uses to classify daily renewal mail'. SLT-EML-02 (d4) and SLT-EML-05 (d6) then each write _auto_renew=off on that very subscription for one cycle, deliberately producing an 'Invoice for subscription #SUB_CORE' email plus a manually-paid renewal on D4 and D6. The watcher, reading EML-15's table, will classify both as UNMAPPED and file them as leaks - and will also see the charge leg leave the order in a non-standard state.
- *Required fix:* EML-02 and EML-05 must each post a dated exception to the registry BEFORE flipping the meta ('SUB_CORE cycle due <date>: _auto_renew=off, one renewal_invoice + one customer-paid renewal order expected; suppression restored at <time>'), and the watch schedule rows for D4/D5 and D6/D7 must carry those exceptions as expected rather than negative. Add to both tasks a pass criterion 'the registry exception exists and was posted before the meta write' and a teardown criterion 'the next cycle after restore sends no invoice mail'.

---
## Objective
The plugin ships a `plain/` counterpart for every template, but WooCommerce sends HTML by default so the plain variants are never exercised. Switch `[ArraySubs] Renewal Invoice` and `[ArraySubs] Renewal Payment Successful` to **Multipart**, drive one manual-invoice renewal cycle on SLT Daily Core so both send, then verify HTML and plain carry the same facts, that plain has no markup, and that every action link resolves rather than 404s.

## Scope
- Gateway: Stripe test
- Checkout: block (order-pay endpoint only — never swap checkout page 8)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-EML-02 done: it established the `_auto_renew = off` recipe that un-suppresses the invoice email, and restored the meta. SLT-EML-03 done: its Stripe `payment_successful` mailpit id is the HTML baseline. `SUB_CORE` is still `arraysubs-active`.
- **Declared deviation:** writes the WooCommerce per-email `Email type` option on two ArraySubs emails; both restored in step 11. Record prior values first. No ArraySubs setting is touched.
- Templates: `customer-renewal-invoice.php`, `customer-payment-successful.php` and their `plain/` counterparts.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUB_CORE` / SLT Daily Core, $10.00 |
| Emails switched | `[ArraySubs] Renewal Invoice`, `[ArraySubs] Renewal Payment Successful` |
| Email type | `Multipart` (prior value recorded, restored) |
| Links | order-pay URL; `/my-account/subscriptions/`; the WooCommerce footer link |
| Card | `4242 4242 4242 4242` |

## Steps
1. Open `.../wp-admin/admin.php?page=wc-settings&tab=email` as `--session admin` → `snapshot -i`; screenshot both ArraySubs rows and their type.
2. Click **Manage** on `[ArraySubs] Renewal Invoice`; record the **Email type**; set `Multipart`; Save; re-snapshot to confirm it stuck.
3. Repeat step 2 for `[ArraySubs] Renewal Payment Successful`.
4. Compute k for `SUB_CORE`, read `_next_payment_date`, find the pending `arraysubs_generate_renewal_invoice` row on Scheduled Actions. If the invoice leg is under 15 min away, wait for the next cycle.
5. `wp post meta update SUB_CORE _auto_renew off --allow-root`.
6. `PREV=$(mailpit-agent latest-id)`; `mailpit-agent wait-new "$PREV" 3600 "Invoice for subscription #SUB_CORE"`.
7. Run BOTH `mailpit-agent html <id>` and `text <id>`; save each to the evidence root.
8. Open the pay URL taken from the **plain** part in `--session customer-eml05` (as `slt-core`) and pay with 4242 — this produces the payment-received mail.
9. `mailpit-agent list 50` → the `Payment received for subscription #SUB_CORE` id; run `html` and `text`.
10. Extract every `http` URL from all four parts; open each in `--session customer-eml05` → `snapshot -i`; record the title and any 404 / console error. Diff the fact set (subscription id, product, amount, dates, order number) between HTML and plain for each message.
11. Restore: set both Email type values back; Save; re-screenshot. `wp post meta delete SUB_CORE _auto_renew --allow-root`; close the session. On the next cycle confirm no invoice mail and that the next payment-received mail is HTML-only.

## Expected results
1. Both messages arrive as `multipart/alternative` with a non-empty `text/plain` AND `text/html` part.
2. Plain invoice contains the `=-=-=-=` rule, `Hi <first name>,`, `A renewal order has been created for your subscription #SUB_CORE.`, `Order #<n>`, `Subscription: #SUB_CORE`, `Product: SLT Daily Core`, `Amount: $10.00`, `Due Date: <UTC+6 date>`, `Pay for this order: <url>` — and no `<` tag or `&amp;` leftovers.
3. Plain payment received contains `We have received your payment for subscription #SUB_CORE`, Product, `Amount Paid: $10.00`, a Payment Method line and `Next Payment Date: <UTC+6 date>`.
4. Every fact in step 10 matches between the HTML and plain part; record any difference beyond entity stripping.
5. HTML parts match the SLT-EML-02 / SLT-EML-03 baselines — Multipart changed nothing in the HTML rendering.
6. Every extracted URL returns HTTP 200: order-pay renders the payable order (or a valid order-received page post-payment); `/my-account/subscriptions/` lists `SUB_CORE`; the footer link resolves to the site home. No 404, no PHP notice, no console error.
7. Any plain template rendering an empty section, a doubled rule or a bare `%s` is a finding — file `issues/SLT-EML-05-<slug>.md`.
8. After step 11 both Email type options read their pre-task values.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_invoice` multipart | invoice leg `D+k−6h`, `_auto_renew=off` | slt-core@example.test | `Invoice for subscription #SUB_CORE` | `wait-new`, then `html`+`text` |
| 2 | `payment_successful` multipart | manual payment, step 8 | slt-core@example.test | `Payment received for subscription #SUB_CORE` | `html`/`text` |
| 3 | NONE EXPECTED after restore | next cycle | — | `Invoice for subscription` | suppression back once `_auto_renew` is deleted |

## Evidence to capture
- `SLT-EML-05-01-settings-before.png`, `-02-multipart-set.png`, `-03-invoice-html.png`, `-04-invoice-plain.txt`, `-05-received-html.png`, `-06-received-plain.txt`, `-07-links.png`, `-08-restored.png`; mailpit ids; the URL list with status and title; prior/restored Email type values.

## Pass criteria
- [ ] Both messages multipart, non-empty plain and HTML parts
- [ ] Plain parts carry the full fact set, zero markup
- [ ] HTML and plain fact sets agree for both emails
- [ ] Every link in all four parts resolves 200, no console error
- [ ] HTML unchanged from the SLT-EML-02 / SLT-EML-03 baselines
- [ ] Both Email type options restored; `_auto_renew` deleted

## Isolation / teardown
- Restores (step 11): the two per-email `Email type` options and deletion of `_auto_renew` on `SUB_CORE`; record prior/after values per isolation rule 7.
- One `SUB_CORE` renewal cycle is consumed and paid, leaving the subscription healthy and on schedule.

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
