---
id: 91
title: HTML vs plain-text rendering of the renewal invoice and payment-received emails, and link resolution
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-06
due: "2026-08-29"
estimate: 1h30m
depends_on:
    - 67
    - 53
claimed_by: delta-gate
class: standard
---

> **SLT-EML-05** · group `emails` · scheduled **D06** (2026-08-29)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
The plugin ships a `plain/` counterpart for every template, but WooCommerce sends HTML by default so the plain variants are never exercised. Switch `[ArraySubs] Renewal Invoice` and `[ArraySubs] Renewal Payment Successful` to **Multipart**, drive one manual-invoice renewal cycle on SLT2 Daily Core so both send, then verify HTML and plain carry the same facts, that plain has no markup, and that every action link resolves rather than 404s.

## Scope
- Gateway: Stripe test
- Checkout: block (order-pay endpoint only — never swap checkout page 8)
- Account: existing (`slt2-core`)
- Plugins: both

## Preconditions
- SLT-EML-02 done: it established the `_auto_renew = off` recipe that un-suppresses the invoice email, and restored the meta. SLT-EML-03 done: its Stripe `payment_successful` mailpit id is the HTML baseline. `SUB_CORE` is still `arraysubs-active`.
- **Declared deviation:** writes the WooCommerce per-email `Email type` option on two ArraySubs emails; both restored in step 11. Record prior values first. No ArraySubs setting is touched.
- Templates: `customer-renewal-invoice.php`, `customer-payment-successful.php` and their `plain/` counterparts.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUB_CORE` / SLT2 Daily Core, $10.00 |
| Emails switched | `[ArraySubs] Renewal Invoice`, `[ArraySubs] Renewal Payment Successful` |
| Email type | `Multipart` (prior value recorded, restored) |
| Links | order-pay URL; `/my-account/subscriptions/`; the WooCommerce footer link |
| Card | `4242 4242 4242 4242` |

## Steps
1. Resolve registry alias `SUB_CORE` into shell variable `SUB_CORE` and abort unless `[[ "$SUB_CORE" =~ ^[0-9]+$ ]]`. Compute k from that numeric ID, read `_next_payment_date`, and find the exact pending `arraysubs_generate_renewal_invoice` row on Scheduled Actions. Do not open a browser session or mutate any setting/meta if its gate is more than **5 h 30 min** away: record the action ID/GMT gate, leave the card in progress, and resume in the first phase inside that window. If fewer than **20 min** remain, there is not enough safe preparation/restoration margin; defer to the next cycle without changing any setting or meta. Proceed only in the 20 min–5 h 30 min window, which fits the runner's six-hour ceiling and preserves the final restoration margin.
2. Open `.../wp-admin/admin.php?page=wc-settings&tab=email` as `--session admin-SLT-EML-05` → `snapshot -i`; screenshot both ArraySubs rows and record both prior Email type values before changing either one. This is the only admin browser session for the current phase.
3. Click **Manage** on `[ArraySubs] Renewal Invoice`; immediately before the first non-default save, record the UTC bracket-open timestamp in the registry and evidence. Set **Email type** to `Multipart`; Save; re-snapshot to confirm it stuck.
4. Set `[ArraySubs] Renewal Payment Successful` to `Multipart`; Save; re-snapshot. If either save or subsequent test step fails, jump immediately to step 11 and restore both recorded prior values plus `_auto_renew` before collecting further evidence.
5. **Before writing the meta**, append this dated exception to `slt2-catalog-registry` and save the same text to `/home/server-manager/slt-evidence/SLT-EML-05-registry-exception.txt`, rendering the resolved numeric ID: `SLT-EML-05 / SUB_CORE=<resolved numeric ID> cycle due <D>: _auto_renew=off; exactly one multipart Invoice for subscription #<resolved numeric ID> and one customer-paid renewal order are expected; suppression will be restored at <planned UTC time>.` Re-open the registry and screenshot/read back the saved exception. Only then run `wp post meta update "$SUB_CORE" _auto_renew off --allow-root` and read it back.
6. `PREV=$(mailpit-agent latest-id)`. Poll `mailpit-agent wait-new "$PREV" <chunk> "Invoice for subscription #$SUB_CORE"` with the **same baseline** in chunks no longer than **60 seconds** until the exact invoice gate plus five minutes. Exit 124 before that final deadline is only an intermediate poll timeout, not a failure; immediately repeat with the remaining bounded interval. Only absence after gate+5 min is a finding, at which point restore both email types and delete `_auto_renew` before collecting further evidence.
7. Run BOTH `mailpit-agent html <id>` and `mailpit-agent text <id>`; save each to the evidence root.
8. Open the pay URL taken from the **plain** part in `--session customer-eml05-SLT-EML-05` (as `slt2-core`). Immediately before submitting 4242, set `PAY_PRE=$(mailpit-agent latest-id)`; pay once, then poll that immutable baseline in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$SUB_CORE`.
9. List only messages newer than `PAY_PRE`, identify exactly one `Payment received for subscription #$SUB_CORE` id, and run `html` and `text` for that exact id.
10. Extract every `http` URL from all four parts; open each in `--session customer-eml05-SLT-EML-05` → `snapshot -i`; record the title and any 404 / console error. Diff the fact set (subscription id, product, amount, dates, order number) between HTML and plain for each message.
11. Restore: set both Email type values back to their individually recorded prior values; Save; re-screenshot. `wp post meta delete "$SUB_CORE" _auto_renew --allow-root`; prove the meta is absent, append the actual UTC restore/bracket-close time to the registry exception/evidence file, and verify the two settings read back exactly as recorded. Close `admin-SLT-EML-05` and `customer-eml05-SLT-EML-05` after this current-cycle phase; do not retain authenticated sessions across the next natural cycle. At least five minutes before the next natural invoice gate, record `POST_RESTORE_PRE=$(mailpit-agent latest-id)` in the registry. After the corresponding charge gate, poll the immutable baseline in repeated calls no longer than 60 seconds through the 10-minute cutoff for `Payment received for subscription #$SUB_CORE`; inspect every message newer than `POST_RESTORE_PRE`, require zero `Invoice for subscription #$SUB_CORE` messages, and prove that exact payment-received message is HTML-only.
12. Independently review the saved multipart parts, URL/status inventory, restored option/meta proof, and post-restore natural-cycle delta. Any live defect goes only in `qa/issues/` kanban card named `SLT-EML-05-<concise-slug>`, and track it on the mandatory `qa/issues/` kanban board, and must include this task/stage/plan path; subscription/order/action/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and the HTML/plain/link proof. Move the card through `review` to `done` and require Review to return to zero.

## Expected results
1. Both messages arrive as `multipart/alternative` with a non-empty `text/plain` AND `text/html` part.
2. Plain invoice contains the `=-=-=-=` rule, `Hi <first name>,`, `A renewal order has been created for your subscription #SUB_CORE.`, `Order #<n>`, `Subscription: #SUB_CORE`, `Product: SLT2 Daily Core`, `Amount: $10.00`, `Due Date: <UTC+6 date>`, `Pay for this order: <url>` — and no `<` tag or `&amp;` leftovers.
3. Plain payment received contains `We have received your payment for subscription #SUB_CORE`, Product, `Amount Paid: $10.00`, a Payment Method line and `Next Payment Date: <UTC+6 date>`.
4. Every fact in step 10 matches between the HTML and plain part; record any difference beyond entity stripping.
5. HTML parts match the SLT-EML-02 / SLT-EML-03 baselines — Multipart changed nothing in the HTML rendering.
6. Every extracted URL returns HTTP 200: order-pay renders the payable order (or a valid order-received page post-payment); `/my-account/subscriptions/` lists `SUB_CORE`; the footer link resolves to the site home. No 404, no PHP notice, no console error.
7. Any plain template rendering an empty section, a doubled rule or a bare `%s` is a finding — file `qa/issues/` kanban card named `SLT-EML-05-<slug>`.
8. After step 11 both Email type options read their pre-task values.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_invoice` multipart | invoice leg `D+k−6h`, `_auto_renew=off` | slt2-core@example.test | `Invoice for subscription #SUB_CORE` | Repeated same-`$PREV` chunks ≤60 seconds through gate+5 min, then `html`+`text` |
| 2 | `payment_successful` multipart | manual payment, step 8 | slt2-core@example.test | `Payment received for subscription #SUB_CORE` | immutable-baseline polls ≤60 seconds through the five-minute cutoff, then `html`/`text` |
| 3 | HTML-only `payment_successful`; no invoice | first natural cycle after restore | slt2-core@example.test | `Payment received for subscription #SUB_CORE`; no `Invoice for subscription #SUB_CORE` | repeated immutable-baseline polls ≤60 seconds through the 10-minute cutoff; inspect every newer message |

## Evidence to capture
- `SLT-EML-05-01-settings-before.png`, `-02-multipart-set.png`, raw `-03-invoice.html`, `-04-invoice-plain.txt`, raw `-05-received.html`, `-06-received-plain.txt`, `-07-links.png`, `-08-restored.png`; mailpit ids; the URL list with status and title; prior/restored Email type values. Raw message files are data evidence, not browser screenshots.

## Pass criteria
- [ ] Both messages multipart, non-empty plain and HTML parts
- [ ] Plain parts carry the full fact set, zero markup
- [ ] HTML and plain fact sets agree for both emails
- [ ] Every link in all four parts resolves 200, no console error
- [ ] HTML unchanged from the SLT-EML-02 / SLT-EML-03 baselines
- [ ] Dated registry exception exists and was posted/read back before `_auto_renew` was written
- [ ] Both Email type options restored; `_auto_renew` deleted with the restore time recorded; the next cycle sends no invoice mail
- [ ] Current and post-restore phases close their exact sessions; independent review reaches `done` with Review empty

## Isolation / teardown
- Restores (step 11): the two per-email `Email type` options and deletion of `_auto_renew` on `SUB_CORE`; record prior/after values per isolation rule 7.
- One `SUB_CORE` renewal cycle is consumed and paid, leaving the subscription healthy and on schedule.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
