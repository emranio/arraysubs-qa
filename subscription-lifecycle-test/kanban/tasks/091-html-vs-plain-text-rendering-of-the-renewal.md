---
id: 91
title: HTML vs plain-text rendering of the renewal invoice and payment-received emails, and link resolution
status: in-progress
priority: medium
created: 2026-08-02T03:43:10.747716504+02:00
updated: 2026-08-09T06:49:26.775614267+02:00
tags:
    - email
    - day-06
due: "2026-08-08"
estimate: 1h30m
depends_on:
    - 67
    - 53
claimed_by: delta-gate
claimed_at: 2026-08-09T06:49:26.77496566+02:00
class: standard
---

> **SLT-EML-05** · group `emails` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

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
1. Resolve registry alias `SUB_CORE` into shell variable `SUB_CORE` and abort unless `[[ "$SUB_CORE" =~ ^[0-9]+$ ]]`. Compute k from that numeric ID, read `_next_payment_date`, and find the exact pending `arraysubs_generate_renewal_invoice` row on Scheduled Actions. Do not open a browser session or mutate any setting/meta if its gate is more than **5 h 30 min** away: record the action ID/GMT gate, leave the card in progress, and resume in the first phase inside that window. If fewer than **20 min** remain, there is not enough safe preparation/restoration margin; defer to the next cycle without changing any setting or meta. Proceed only in the 20 min–5 h 30 min window, which fits the runner's six-hour ceiling and preserves the final restoration margin.
2. Open `.../wp-admin/admin.php?page=wc-settings&tab=email` as `--session admin-SLT-EML-05` → `snapshot -i`; screenshot both ArraySubs rows and record both prior Email type values before changing either one. This is the only admin browser session for the current phase.
3. Click **Manage** on `[ArraySubs] Renewal Invoice`; immediately before the first non-default save, record the UTC bracket-open timestamp in the registry and evidence. Set **Email type** to `Multipart`; Save; re-snapshot to confirm it stuck.
4. Set `[ArraySubs] Renewal Payment Successful` to `Multipart`; Save; re-snapshot. If either save or subsequent test step fails, jump immediately to step 11 and restore both recorded prior values plus `_auto_renew` before collecting further evidence.
5. **Before writing the meta**, append this dated exception to `slt-catalog-registry` and save the same text to `/home/server-manager/slt-evidence/SLT-EML-05-registry-exception.txt`, rendering the resolved numeric ID: `SLT-EML-05 / SUB_CORE=<resolved numeric ID> cycle due <D>: _auto_renew=off; exactly one multipart Invoice for subscription #<resolved numeric ID> and one customer-paid renewal order are expected; suppression will be restored at <planned UTC time>.` Re-open the registry and screenshot/read back the saved exception. Only then run `wp post meta update "$SUB_CORE" _auto_renew off --allow-root` and read it back.
6. `PREV=$(mailpit-agent latest-id)`. Poll `mailpit-agent wait-new "$PREV" <chunk> "Invoice for subscription #$SUB_CORE"` with the **same baseline** in chunks no longer than **60 seconds** until the exact invoice gate plus five minutes. Exit 124 before that final deadline is only an intermediate poll timeout, not a failure; immediately repeat with the remaining bounded interval. Only absence after gate+5 min is a finding, at which point restore both email types and delete `_auto_renew` before collecting further evidence.
7. Run BOTH `mailpit-agent html <id>` and `mailpit-agent text <id>`; save each to the evidence root.
8. Open the pay URL taken from the **plain** part in `--session customer-eml05-SLT-EML-05` (as `slt-core`). Immediately before submitting 4242, set `PAY_PRE=$(mailpit-agent latest-id)`; pay once, then poll that immutable baseline in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$SUB_CORE`.
9. List only messages newer than `PAY_PRE`, identify exactly one `Payment received for subscription #$SUB_CORE` id, and run `html` and `text` for that exact id.
10. Extract every `http` URL from all four parts; open each in `--session customer-eml05-SLT-EML-05` → `snapshot -i`; record the title and any 404 / console error. Diff the fact set (subscription id, product, amount, dates, order number) between HTML and plain for each message.
11. Restore: set both Email type values back to their individually recorded prior values; Save; re-screenshot. `wp post meta delete "$SUB_CORE" _auto_renew --allow-root`; prove the meta is absent, append the actual UTC restore/bracket-close time to the registry exception/evidence file, and verify the two settings read back exactly as recorded. Close `admin-SLT-EML-05` and `customer-eml05-SLT-EML-05` after this current-cycle phase; do not retain authenticated sessions across the next natural cycle. At least five minutes before the next natural invoice gate, record `POST_RESTORE_PRE=$(mailpit-agent latest-id)` in the registry. After the corresponding charge gate, poll the immutable baseline in repeated calls no longer than 60 seconds through the 10-minute cutoff for `Payment received for subscription #$SUB_CORE`; inspect every message newer than `POST_RESTORE_PRE`, require zero `Invoice for subscription #$SUB_CORE` messages, and prove that exact payment-received message is HTML-only.
12. Independently review the saved multipart parts, URL/status inventory, restored option/meta proof, and post-restore natural-cycle delta. Any live defect goes only in `issues/SLT-EML-05-<concise-slug>.md`, never in the lifecycle board, and must include this task/stage/plan path; subscription/order/action/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and the HTML/plain/link proof. Move the card through `review` to `done` and require Review to return to zero.

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
| 1 | `renewal_invoice` multipart | invoice leg `D+k−6h`, `_auto_renew=off` | slt-core@example.test | `Invoice for subscription #SUB_CORE` | Repeated same-`$PREV` chunks ≤60 seconds through gate+5 min, then `html`+`text` |
| 2 | `payment_successful` multipart | manual payment, step 8 | slt-core@example.test | `Payment received for subscription #SUB_CORE` | immutable-baseline polls ≤60 seconds through the five-minute cutoff, then `html`/`text` |
| 3 | HTML-only `payment_successful`; no invoice | first natural cycle after restore | slt-core@example.test | `Payment received for subscription #SUB_CORE`; no `Invoice for subscription #SUB_CORE` | repeated immutable-baseline polls ≤60 seconds through the 10-minute cutoff; inspect every newer message |

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


[[2026-08-08]] Sat 06:36
D6 AM gate review: SUB_CORE resolves to numeric sub 11959. Current natural rows are invoice 15676 at 2026-08-08 09:37:52Z / 15:37:52 site and charge 15677 at 15:37:52Z / 21:37:52 site. The authored EML-05 preparation window would be 10:07:52-15:17:52 site, but card 67 / SLT-EML-02 owns invoice 15676 as its final suppression proof. EML-05 changes global email formats and the subscription auto-renew mode, so opening its bracket would invalidate that higher-priority in-progress proof. No setting, subscription meta, browser session, or mail baseline was changed. Keep this card in progress for the next valid cycle: resume D7 2026-08-09 at the 10:10 phase, prepare only in 10:07:52-15:17:52 site, and observe the 15:37:52 site invoice gate; resolve and record the new action ID after natural charge 15677 creates it.

[[2026-08-09]] Sun 06:49
D07 late-morning preparation completed inside the authored window. SUB_CORE=11959; invoice action 16119 is due 15:37:52 site and charge 16120 at 21:37:52. Prior Renewal Invoice and Renewal Payment Successful types were HTML with both option rows absent. The first non-default save opened the settings bracket at 2026-08-09T04:23:29Z; both types now read multipart. The numeric registry exception was posted/read back once before _auto_renew=off was written. PREV=5yGiRnu4Kb079ncz43EDFT. Customer session is authenticated but no pay/order action occurred. Mandatory restore deadline remains before 16:00 site, with restoration starting by 15:45 if any positive checks run late. Evidence: /home/server-manager/slt-evidence/SLT-EML-05-settings-bracket.txt, /home/server-manager/slt-evidence/SLT-EML-05-registry-exception.txt, and screenshots SLT-EML-05-01-settings-before.png / -02-multipart-set.png.

[[2026-08-09]] Sun 09:49
D07 multipart/manual-payment leg PASS; the final post-restore criterion remains due on D8, so this card stays in progress. Natural action 16119 completed once and emitted multipart invoice 2H7QObpMcorzL5x47uuWOp for relationship order 13466. PAY_PRE=2H7QObpMcorzL5x47uuWOp was captured immediately before one saved-Visa-4242 payment; order 13466 completed for $10.00 and emitted admin New order 4XYFQ8ZBDWYbRHXU5Y63VF plus multipart payment-success 3suwtzFDriAtZ35sOOVElB. Both plain parts carried the complete matching fact sets with no markup/residue/bare placeholder; normalized current HTML structures matched the recorded HTML-only baselines. The order-pay, account-subscriptions, and footer/home links returned valid HTTP 200 pages with no console errors. Subscription 11959 is active with 8 payments, next due 2026-08-10 12:39:05Z; action 16120 is canceled unattempted and replacements 16396/16397 are pending for D8 15:37:52/21:37:52 site. The bracket closed at 15:42:37 site: both exact option rows and _auto_renew are absent, both fresh UI readbacks show HTML, and the persisted registry has one open plus one restored marker. Current task sessions are closed. Evidence: /home/server-manager/slt-evidence/SLT-EML-05-D07-current-cycle.txt and the artifacts indexed within it. Next gate: capture POST_RESTORE_PRE only during 2026-08-10 15:32:52-15:37:51 site, then verify after action 16397 through 21:47:52 that the complete delta has zero invoice and exactly one HTML-only payment-success mail before review to done.
