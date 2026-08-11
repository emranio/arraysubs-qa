---
id: 94
title: Update the Paddle payment method and prove the next Paddle-driven renewal uses it, plus the missing local update surface
status: done
priority: high
created: 2026-08-02T03:43:10.950263974+02:00
updated: 2026-08-09T02:37:33.606362325+02:00
started: 2026-08-09T02:37:33.606361534+02:00
completed: 2026-08-09T02:37:33.606361534+02:00
tags:
    - admin
    - portal
    - day-06
due: "2026-08-08"
estimate: 1.5h
depends_on:
    - 70
    - 26
    - 23
    - 29
class: standard
---

> **SLT-MYA-03** · group `admin` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Establish what "update payment method" means for a Paddle subscription here, then prove the next unassisted renewal charges the new card. Code-verified: ArraySubs renders no Paddle update control (the portal only links to `/my-account/payment-methods/`) and `PaddleGateway::handlePaymentMethodUpdated()` (`:1479-1503`) only syncs `next_billed_at` and status, writing no card metas. The local-surface half is EXPLORATORY - document behaviour, do not assert a spec.

## Scope
- Gateway: Paddle sandbox
- Checkout: N/A (portal + Paddle-hosted update page)
- Account: existing (`slt-paddle`)
- Plugins: pro-required

## Preconditions
- `SLT Paddle Daily` bought by `slt-paddle` on D2 after 12:00; canonical registry alias `SUB_PAD` is `arraysubs-active` with `_gateway_paddle_subscription_id` set.
- C51 fallback: if CHK-04 published `SUB_PAD unavailable`, do not create a substitute or call a management URL for another subscription. Re-read and cite the standalone CHK-04 issue, mark this task `UNVERIFIED (no source subscription)`, complete its review, and move it to `done` without opening a nonexistent portal/API target.
- SLT-SETUP-05's matrix is binding: `sca:false`, `early_renewal:false`, no renewal sync - those absences are not bugs. Renewals are Paddle-driven; the local `arraysubs_process_renewal` leg is a no-op returning `pending` (`:598-629`) and must never be force-run.
- **Act 08:00-11:00 site on D6 (2026-08-08)**, before the afternoon `next_billed_at` anniversary, so the next Paddle charge lands that same afternoon.

## Test data
| Item | Value |
|---|---|
| Account | slt-paddle / `SltQa!2026#Pass`, session `--session customer-MYA-03-SLT-MYA-03` |
| Product | SLT Paddle Daily, $11.00 every day |
| New card | a Paddle sandbox test-card fixture whose last4 differs from the one on file — record only the fixture label and last4, never the full PAN |
| API key | Capture non-interactively from `woocommerce_arraysubs_paddle_settings` with WP-CLI plus `jq`, as specified in the steps; never echo or save it |

## Steps
1. `MB03=$(mailpit-agent latest-id)`. Record `SUB_PAD`, its Paddle subscription id, `_next_payment_date` and crc32 offset.
2. BEFORE dump -> `/home/server-manager/slt-evidence/SLT-MYA-03-before.txt`: `wp post meta list <SUB_PAD> --keys=_payment_method_title,_gateway_payment_method_id,_payment_method_brand,_payment_method_last4,_payment_method_updated_at --allow-root`.
3. Open `/my-account/payment-methods/` -> log in -> screenshot. **Exploratory:** record whether any saved method exists, whether Add payment method is offered, and which gateway it targets.
4. Open `/my-account/view-subscription/<SUB_PAD>/`; screenshot the `Payment Method:` row and `Subscription Actions`. `Renew Early` must be absent; record any Paddle "update card" link (expected: none).
5. From the WP root capture the secret without printing it: `PADDLE_API_KEY="$(wp option get woocommerce_arraysubs_paddle_settings --format=json --allow-root | jq -r '.api_key // empty')"`; require `test -n "$PADDLE_API_KEY"`. With that variable, request the subscription and read `data.management_urls.update_payment_method` into another shell variable without echoing it. Save only a redacted response with `management_urls` removed. If the API key or URL is absent, stop at UNVERIFIED.
6. Open that URL in the same customer session, enter the new sandbox card, and submit. Never capture an image while hosted card fields are populated; capture only the safe post-submit success/return state as `SLT-MYA-03-03-update-page.png`, recording the fixture label and last4 only.
7. Within 5 min repeat the step-2 dump into `-after.txt` and diff; in isolated `admin-SLT-MYA-03` open the exact numeric subscription and read its notes for `Paddle subscription updated - state synchronized.` Inspect the complete Mailpit delta after MB03 and require no message attributable to this update event; unrelated shared-site mail does not fail the task.
8. Re-poll the API; record `payment_method_details` last4 and `next_billed_at` and compare with local `_next_payment_date`. Save only those non-secret fields, then `unset PADDLE_API_KEY`. `syncNextPaymentDate()` writes the meta but does not reschedule the AS legs (`:2305`) - record any misalignment. Publish the exact remote charge gate and `gate−300s` deadline, then close `customer-MYA-03-SLT-MYA-03` and `admin-SLT-MYA-03`; do not retain authenticated sessions across the remote settlement. At the final scheduled watch phase inside `[next_billed_at−300s, next_billed_at)`, save `PAD_CHARGE_PRE=$(mailpit-agent latest-id)` to the registry and `/home/server-manager/slt-evidence/SLT-MYA-03-charge-pre.txt`.
9. **Follow-up, watch day D7 = 2026-08-09 (morning check):** poll immutable `PAD_CHARGE_PRE` in repeated calls no longer than 60 seconds through the 10-minute cutoff for `Payment received for subscription #$SUB_PAD`, save/show the exact match, and classify the complete delta after `PAD_CHARGE_PRE`. Resolve the paid renewal order from the exact Paddle transaction/subscription relationship, require its reverse subscription meta, and never select by recency. In fresh `admin-SLT-MYA-03-R1`, verify the 2026-08-08 PM order is paid for $11.00 with `_is_renewal_order=yes` and that exact `_paddle_transaction_id`; verify the sanitized sandbox transaction shows the NEW last4 and `_payment_retry_attempts` remains 0. Close the R1 session, independently review both phases, create `issues/light-plugin-SLT-MYA-03-missing-local-paddle-payment-method-update-surface.md` if live proof confirms the absent/stale local update surface, then move the card through `review` to `done` with Review empty. Any issue file must include task/stage/plan path; customer/subscription/order/transaction/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/API/meta/note/Mailpit proof, with no secret or full card number.

## Expected results
1. `/my-account/payment-methods/` offers no Paddle saved method and no Paddle add-card path: there is no local Paddle update surface. The detail page shows `Payment Method:` from `_payment_method_title`, links only to that page, and has no `Renew Early`.
2. The Paddle-hosted page accepts the new card and the subscription gains the note `Paddle subscription updated - state synchronized.`
3. **`_payment_method_brand`, `_payment_method_last4`, `_gateway_payment_method_id` and `_payment_method_updated_at` are UNCHANGED locally**, so the portal still shows stale card details.
4. `_next_payment_date` matches `next_billed_at`, `_gateway_status` maps `active->active`, the post status is unchanged.
5. Watch day D7 (2026-08-09): the 2026-08-08 PM charge produced a paid $11.00 renewal order on the new card and `_payment_retry_attempts` is 0 - Paddle never reaches `scheduleNextRetry()`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Card update, `subscription.updated`, and card-expiring (never emitted, `card_expiry_notice:false`) | - | - | complete MB03 delta contains no message attributable to SUB_PAD/update; specifically no `Update the card` mail |
| 2 | payment_successful | `transaction.completed`, 2026-08-08 PM | slt-paddle | `Payment received for subscription #<SUB_PAD>` | immutable-baseline polls ≤60 seconds through the 10-minute cutoff; save/show exact match and full delta |

## Evidence to capture
- Screenshots `SLT-MYA-03-01-methods-empty.png`, `-02-detail-row.png`, `-03-update-page.png`, `-04-notes.png`; both meta dumps and their diff; sanitized API field summaries; test-card fixture label + last4 only; renewal order id; `PAD_CHARGE_PRE`; matched and full-delta Mailpit ids.

## Pass criteria
- [ ] Local Paddle update surface documented (expected: none) with screenshots
- [ ] Paddle page accepted the new card, sync note appeared, local card metas unchanged
- [ ] `_next_payment_date` reconciled to `next_billed_at`, any AS misalignment recorded
- [ ] The 2026-08-08 PM charge used the new card and produced a paid $11.00 renewal order (watch day D7 = 2026-08-09), no retry scheduled, no unexpected mail
- [ ] D6 and D7 sessions closed; confirmed local-surface defect is a standalone issue file; independent review reaches `done` with Review empty

## Isolation / teardown
- Touches only `slt-paddle` and its own sandbox subscription; sandbox objects are left in place. Close only the exact customer/admin phase sessions named in the steps.
- If the update page is unreachable or the webhook never arrives, record UNVERIFIED with both API payloads; never hand-edit gateway or card metas to fake the result.

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


[[2026-08-08]] Sat 08:21
D6 08:00-11:00 leg executed at 08:01-08:19 site. MB03=6B2jxkahZfmlae0je2Rmah; SUB_PAD=12639, customer 352, product 12112, parent 12629, Paddle sub sub_01kz8q1025tryjfgxvn5e3v4gf. Before: local NPD 2026-08-08 10:20:38Z, card Visa 4242, actions 15615/15616 pending. Local methods page had no saved row and its Add page exposed only the non-Paddle card form; exact detail unexpectedly did expose a stale Visa 4242 Card on File row plus Update payment method control, with no Renew Early. Paddle accepted the documented valid Visa debit sandbox fixture ending 5556 via the temporary management URL; transaction txn_01kzfj2d3mtsbasp6r8s8zmrw8 is completed, origin subscription_payment_method_change, USD 0, last4 5556. FAIL: the webhook was misclassified as a renewal, creating completed zero-item $0 order 13214, mail 4E4IXtfwjCT1FJZSE9B1mk, advancing local NPD to 2026-08-09 10:20:38Z, canceling 15615/15616, and creating 15890/15891 for D7 while remote next_billed_at remains 2026-08-08T10:20:38.143985Z. Local card meta stayed stale 4242 and _payment_method_updated_at absent. Issue: issues/critical-plugin-SLT-MYA-03-payment-method-change-treated-as-zero-dollar-renewal.md. Evidence: /home/server-manager/slt-evidence/SLT-MYA-03-before.txt, -after.txt, -paddle-api-before.json, -paddle-api-after.json, screenshots 01/02/03/04. No populated-card image or management token retained; both exact sessions closed and API variables unset. Keep in progress. Next hard gate: at the 16:10 phase, revalidate the remote value and save PAD_CHARGE_PRE inside 2026-08-08 16:15:38-16:20:37 site before remote next_billed_at 16:20:38.143985; D7 morning then proves the real $11 settlement/new last4 and closes the task.


[[2026-08-08]] Sat 08:24
Mail-delta correction after full cursor-index reconciliation: MB03 -> current contains exactly two update-attributable messages, admin new-order 2AmbBF9Gr3ahDaee9jMKcH for empty $0 order 13214 and customer payment-success 4E4IXtfwjCT1FJZSE9B1mk for $0.00. Evidence/report/issue were corrected; no background mail is in the delta.

[[2026-08-08]] Sat 12:20
D6 afternoon hard gate completed: remote subscription revalidated active with next_billed_at 2026-08-08T10:20:38.143985Z; PAD_CHARGE_PRE=7eIiwGyhpr2alnqiBICks7 captured 2026-08-08 10:16:06Z / 16:16:05 site and published to registry page 11847. Evidence: /home/server-manager/slt-evidence/SLT-MYA-03-charge-pre.txt. No action forced. Keep in progress; exact next gate is D7 2026-08-09 morning settlement/order/mail reconciliation after the natural remote charge.

[[2026-08-08]] Sat 17:09
Watcher-only persisted checkpoint after the remote gate: relationship-exact order 13249 completed for `$11.00` at 10:21:14Z and sub 12639 advanced to completed-payments 4 / local next 2026-08-10 10:20:38Z. Exact attributable mails are admin order `3k11t5ipr2GSH5xTNFE483` and customer payment `57lxgHohFqevl55xhVeR3P`. Historical actions 15890/15891 are canceled unattempted; current pair 16005/16006 is pending for D8. Evidence: `/home/server-manager/slt-evidence/SLT-MYA-03-D06-natural-charge.txt`. This does not consume step 9 early: the task remains in progress for D7 sanitized remote transaction/new-last4, retry, and full immutable-cursor reconciliation. Exact earliest resume is the D7 morning phase at 2026-08-09 06:10 site.

[[2026-08-09]] Sun 02:29
D7 step 9 executed 2026-08-09 06:16-06:23 site; the completed D6 update leg was not restarted. Strict verdict: FAIL. Exact Paddle transaction txn_01kzge82gz7pyqne0x1prnmf6a was a single completed subscription_recurring capture for $11.00 on the new Visa ending 5556 and resolves uniquely to completed order 13249 / subscription 12639. Expected customer mail 57lxgHohFqevl55xhVeR3P and admin mail 3k11t5ipr2GSH5xTNFE483 were the only attributable messages after immutable PAD_CHARGE_PRE=7eIiwGyhpr2alnqiBICks7; effective retry remained 0. FAIL: the D6 extra schedule advance persisted (remote next_billed_at 2026-08-09T10:20:38.143985Z versus local next/action pair on D8), the local surface remained stale at Visa 4242, and renewal order 13249 omitted both authored Paddle transaction meta and all order-item rows. Evidence: /home/server-manager/slt-evidence/SLT-MYA-03-D07-followup.txt and /home/server-manager/slt-evidence/SLT-MYA-03-D07-order-13249.png. Issues: issues/critical-plugin-SLT-MYA-03-payment-method-change-treated-as-zero-dollar-renewal.md (D07 addendum), issues/light-plugin-SLT-MYA-03-missing-local-paddle-payment-method-update-surface.md, and issues/light-plugin-SLT-MYA-03-paddle-renewal-order-omits-transaction-meta-and-line-item.md. No action forced, no state mutated, secrets/card PAN sanitized, exact D7 browser session closed.
