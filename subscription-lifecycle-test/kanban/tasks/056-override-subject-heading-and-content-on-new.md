---
id: 56
title: Override subject, heading and content on New Subscription with merge tags and prove real-value rendering
status: done
priority: high
created: 2026-08-02T03:43:07.920891214+02:00
updated: 2026-08-05T21:37:49.55642195+02:00
started: 2026-08-05T17:11:13.226899067+02:00
completed: 2026-08-05T17:11:13.226899067+02:00
tags:
    - email
    - day-03
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 55
class: standard
---

> **SLT-EML-12** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the Subject / Email heading / Additional content overrides on an ArraySubs email are honoured and that every merge tag resolves to the subscription's real values, not the constructor's sample placeholders. Also quote the suite-local, already-verified evidence that `emails.<id>.subject`/`.body` in `arraysubs_settings` have no consumer — the WooCommerce per-email fields are the only working override surface.

## Scope
- Gateway: N/A (no payment taken)
- Checkout: N/A
- Account: existing (`slt-email`, harness H1)
- Plugins: free-only

## Preconditions
- SLT-EML-11 complete: `slt-email` exists (First name `SLT`); harness **H1** on `SLT Lifetime One Time` is `arraysubs-active`, `_next_payment_date` empty, no scheduled action.
- **Exclusive mutation bracket: 21:00–21:40 site on D3 (2026-08-05), after every D3 checkout has closed.** Preparation, the prior dump, and the default-subject baseline may run from 20:15 while no override is active. Before the first status transition, require the board to show no checkout/order-producing task in progress. No other task may place an order, activate a subscription, or change email settings inside the 21:00–21:40 override bracket.
- Code basis: overrides come from the WooCommerce per-email option row via `WC_Email::format_string()`; tags are the keys built in `BaseSubscriptionEmail::__construct()` (`:53-80`), repopulated live in `populate_placeholders()` (`:268-300`). `new_subscription` fires on transition to `arraysubs-active` from pending/trial/auto-draft (`EmailManager.php:325-344`).

## Test data
| Item | Value |
|---|---|
| Subscription | H1 (SLT Lifetime One Time, $49.00, `slt-email@`), section `arraysubs_new_subscription` |
| Subject override | `SLT-EML-12 {customer_first_name} :: sub {subscription_id} :: {product_name} :: {recurring_amount}` |
| Heading override | `Hello {customer_first_name}, subscription {subscription_id} is {subscription_status}` |
| Additional content | `PROBE start={start_date} next={next_payment_date} period={billing_period} pay={payment_method}` |

## Steps
1. During the 20:15–20:59 preparation window, require the precondition board check to pass, resolve registry alias `H1` into shell variable `H1`, and abort unless `[[ "$H1" =~ ^[0-9]+$ ]]`. Record whether `woocommerce_arraysubs_new_subscription_settings` exists and preserve its exact value in `/home/server-manager/slt-evidence/SLT-EML-12-prior.txt` (expected absent); the presence flag governs exact restoration in step 9. Record a preparation timestamp, but do not call the override bracket open yet.
2. Do not inspect either product source tree. Quote the already code-verified no-consumer finding from `reference/SLT-REF-04-complete-email-inventory-class-template-trigger-recipient-su.md` and save that suite-local reference excerpt to `/home/server-manager/slt-evidence/SLT-EML-12-reference-no-consumer.txt`.
3. `MP0=$(mailpit-agent latest-id)`; in `--session admin-SLT-EML-12` open `admin.php?page=arraysubs-mainadmin#/subscriptions`, search exact ID H1, open **View Details**, set **Status** = `Pending`, and save; the complete delta after MP0 must contain no message attributable to that Pending transition. There is no `post.php` fallback for this subscription post type.
4. Set H1 **Status** = `Active`; `mailpit-agent wait-new "$MP0" 180 "is active"`; save the exact customer-message id and `mailpit-agent text <matched-id>`; inspect the complete MP0 delta for the matching admin message and record the DEFAULT subject and heading verbatim.
5. At or after 21:00 site, repeat the board stop check and require no known subscription-activation action inside the next 40 minutes. In `admin-SLT-EML-12`, open `/wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_new_subscription` → `snapshot -i`; capture the **Available placeholders** desc-tip as `SLT-EML-12-01-placeholders.png`. Immediately before saving, set `OVERRIDE_SAVE_PRE=$(mailpit-agent latest-id)`, record the UTC bracket-open timestamp in `/home/server-manager/slt-evidence/SLT-EML-12-bracket.txt` and the registry, paste the three overrides, keep **Enable** ticked, Save, and capture `SLT-EML-12-02-saved.png`. Re-read the exact option and require zero setting-save-attributable mail in the bounded `OVERRIDE_SAVE_PRE` delta.
6. `MP1=$(mailpit-agent latest-id)`; set H1 `Pending`, then `Active`; `mailpit-agent wait-new "$MP1" 180 "SLT-EML-12"`; save the exact customer-message ID, inspect the complete MP1 delta for the matching admin message, and run both `mailpit-agent text <matched-id>` and `mailpit-agent html <matched-id>`. In exact session `mail-SLT-EML-12`, open the matched message in the local Mailpit UI and capture `SLT-EML-12-03-overridden.png`.
7. Transcribe subject, heading and PROBE **verbatim**; compare tag by tag against `wp post meta list "$H1" --keys=_recurring_amount,_billing_period,_start_date,_next_payment_date --allow-root`.
8. Set `CLEAR_SAVE_PRE=$(mailpit-agent latest-id)`. **Restore UI defaults.** On the same URL clear Subject, Heading and Additional content, keep Enable ticked, Save, capture `SLT-EML-12-04-cleared.png`, re-read the blank fields, and require zero setting-save-attributable mail in the bounded `CLEAR_SAVE_PRE` delta.
9. `MP2=$(mailpit-agent latest-id)`; set H1 `Pending` → `Active`; `mailpit-agent wait-new "$MP2" 180 "is active"` must return the DEFAULT subject, and the complete delta must contain exactly one unchanged admin message. Capture that matched default render in `mail-SLT-EML-12` as `SLT-EML-12-05-restored.png`; leave H1 `Active`. Restore the option's exact step-1 storage state—delete it if it was absent, otherwise restore the preserved value—and require an exact presence/value comparison. Record the UTC close timestamp in the bracket file and registry no later than 21:40 site, close only `admin-SLT-EML-12` and `mail-SLT-EML-12`, independently review the complete evidence, move the card through `review` to `done`, and ensure Review returns to zero. If the live `{next_payment_date}` or another render assertion fails, create the named standalone issue with this task/plan, H1/parent order/user IDs and login/role, exact admin/Mailpit contexts, reproduction, expected/actual, mail/meta/reference proof and the default-render counterexample; never create a kanban bug card.

**Restore-first failure rule:** after step 5 saves the override, any browser, transition, mail, or evidence failure jumps immediately to step 8 and the exact step-1 storage restoration in step 9 before diagnosis.

## Expected results
1. Step 4 subject is exactly `[mirror-help.arrayhash.com] Your subscription #<H1> is active`; setting status to Pending sends nothing (verify `mailpit-agent latest-id` at each Pending set).
2. Step 6 subject renders `SLT-EML-12 SLT :: sub <H1> :: SLT Lifetime One Time :: $49.00`; heading `Hello SLT, subscription <H1> is Active`.
3. PROBE resolves `{start_date}` to `_start_date` in site format, `{billing_period}` to the lifetime rendering of `arraysubs_format_billing_period(1,'lifetime')` and `{payment_method}` to `_payment_method_title` or `N/A`.
4. **`{next_payment_date}` probe:** H1's meta is empty, so the tag must render empty or an explicit no-value string. A fabricated date (today+30d — the constructor sample at `BaseSubscriptionEmail.php:64-67`) is a **BUG**: file `issues/SLT-EML-12-lifetime-next-payment.md`.
5. No sample values (`John`, `Sample Subscription Product`, `$29.99`, `every month`, `12345`) appear anywhere; after the live default proof, final option presence/value exactly matches step 1 (normally the temporary row is absent again).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, default subject | Steps 4 and 9 Pending→Active | slt-email@example.test | `Your subscription #<H1> is active` | `mailpit-agent wait-new "$MP0" 180` / `mailpit-agent wait-new "$MP2" 180` |
| 2 | new_subscription, overridden | Step 6 Pending→Active | slt-email@example.test | `SLT-EML-12 SLT :: sub <H1>` | `mailpit-agent wait-new "$MP1" 180 "SLT-EML-12"` |
| 3 | admin_new_subscription | Steps 4, 6, 9 | admin_email | `New subscription #<H1> from SLT Email` | complete MP0/MP1/MP2 deltas — exactly one per transition, subject unchanged |

## Evidence to capture
- Screenshots `SLT-EML-12-01-placeholders.png`, `-02-saved.png`, `-03-overridden.png`, `-04-cleared.png`, `-05-restored.png`; bracket file; exact prior/final presence-value proof; three text dumps; verbatim subject/heading/PROBE beside meta; `OVERRIDE_SAVE_PRE`, `CLEAR_SAVE_PRE`, `MP0/MP1/MP2`, and every message ID; session/review proof.

## Pass criteria
- [ ] Default subject captured before any override; every merge tag in subject, heading and PROBE resolves to H1's real values
- [ ] `{next_payment_date}` on a lifetime sub recorded; bug filed if fabricated
- [ ] Zero sample placeholder values leak; admin subject unaffected
- [ ] Override bracket opened only at the first non-default save, after the board pre-flight, and closed by 21:40 site; preparation stayed outside the bracket and timestamps were published
- [ ] Overrides cleared, default restored by a live send, suite-local no-consumer reference excerpt stored
- [ ] Exact prior option presence/value restored; task sessions closed and card reviewed to done

## Isolation / teardown
- Global setting touched: `arraysubs_new_subscription` subject/heading/additional_content, non-default only inside the recorded bracket. A non-SLT `new_subscription` mail in that bracket would carry the SLT subject — keep it short; abort if another SLT checkout task is running.
- Handed on: H1 left `arraysubs-active` for SLT-EML-13; tag list posted to the registry. Restores: exact prior option presence/value after the live blank/default proof.

## Execution notes — 2026-08-05 D03

- Preparation completed inside the authored 20:15-20:59 site window without opening the override bracket:
  - `/home/server-manager/slt-evidence/SLT-EML-12-prior.txt`
  - `/home/server-manager/slt-evidence/SLT-EML-12-reference-no-consumer.txt`
- `H1=12786`, user `366` (`slt-email@example.test`), order `12776`, product `11938`.
- Step 3 passed:
  - `MP0=3g8Vwfvn45nsiIoOg0vwCH`
  - UI `Active -> Pending` produced no mail
  - `wp post get 12786 --field=post_status --allow-root` returned `arraysubs-pending`
- Step 4 blocked the task before the 21:00 site bracket:
  - UI `Pending -> Active` emitted customer mail `6IU3cBwmT9dWpQxtFW8Hra` and admin mail `2G3O9lw9OovzM1ecZNohbb`
  - default subject/heading were captured, but `wp post get 12786 --field=post_status --allow-root` still returned `arraysubs-pending`
  - fresh browser sessions then rendered the subscriptions index with zero counts and blank detail/edit shells, so the authored browser path for steps 5-9 could not continue
- Cleanup:
  - `wp post update 12786 --post_status=arraysubs-active --allow-root`
  - cleanup itself emitted a second mail pair: customer `1gpznceQ5LZsi6NK7FTZlp`, admin `65pK2vT5zGVU7UHiyphXFF`
  - final status restored to `arraysubs-active`
- Blocker recorded: `qa/subscription-lifecycle-test/issues/SLT-EML-12-admin-status-ui-fires-active-mail-without-persisting-status.md`


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

[[2026-08-05]] Wed 16:27
Concurrency correction: first owner Active pair at 14:19:52Z/14:19:53Z was followed by root watcher Pending confirmation and pending proof at 14:20:34Z, so the apparent non-persistence was a QA race. Root then restored Active via UI, stopped at 14:22:41Z with option absent, and documented both pairs in SLT-EML-12-root-concurrency-note.txt. The product issue file is retracted. One owner will resume only the still-pending 21:00-21:40 override/default-restoration bracket.

[[2026-08-05]] Wed 16:46
Exclusive 21:00-21:40 site bracket owner confirmed. Preparation remains state-neutral; first non-default save will define bracket open.

[[2026-08-05]] Wed 17:11
Override bracket completed on 2026-08-05. Prior option state was absent and final option state is absent again. Overridden customer/admin mail IDs: 5DWxnovrH9I1024JTuTxUj / 0zWQB9v5YIdXqjYpmEHm9v. Restored-default customer/admin mail IDs: 1PVeoMecZqOQqAxlLtNshg / 6XstfWCpbfAFtSNvYCbd8t. Lifetime next-payment rendered blank, no sample placeholder values leaked into the live overridden send, and screenshots 01-05 plus bracket/reference files were captured under /home/server-manager/slt-evidence/.

[[2026-08-05]] Wed 17:16
Independent evening review: live override customer mail 5DWxnovrH9I1024JTuTxUj rendered all non-price tags correctly and lifetime next-payment blank, but recurring_amount inserted literal WooCommerce price HTML into the RFC subject instead of plain $49.00. Verdict QA COMPLETE / FAIL; issue issues/SLT-EML-12-recurring-amount-subject-renders-html.md. Bracket file proves 15:02:04Z-15:10:37Z and exact absent option restoration; H1 remains active.
