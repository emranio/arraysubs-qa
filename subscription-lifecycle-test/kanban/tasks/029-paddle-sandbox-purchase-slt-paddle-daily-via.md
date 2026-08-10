---
id: 29
title: 'Paddle sandbox purchase: SLT Paddle Daily via overlay, webhook-paid order, next_billed_at override'
status: done
priority: high
created: 2026-08-02T03:43:05.360878004+02:00
updated: 2026-08-05T17:55:28.785423219+02:00
started: 2026-08-05T12:35:23.741480579+02:00
completed: 2026-08-05T17:55:28.785422428+02:00
tags:
    - checkout
    - day-02
due: "2026-08-04"
estimate: 1h30m
depends_on:
    - 23
    - 26
class: standard
---

> **SLT-CHK-04** · group `checkout` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Full Paddle sandbox purchase: `slt-paddle` buys `SLT Paddle Daily` ($11.00) through the block checkout and Paddle overlay. Paddle owns the schedule, so this proves the two-phase order (pending → paid by webhook), the Paddle meta, and how `next_billed_at` overrides the local date.

## Scope
- Gateway: Paddle sandbox
- Checkout: block
- Account: existing
- Plugins: pro-required

## Preconditions
- SLT-PROD-16 (`SLT Paddle Daily`) + SLT-SETUP-05 (Paddle readiness) done. Read `issues/SLT-SETUP-05-paddle-product-sync-metas-not-created.md`: the gateway radio was offered, but product `12112` still had no Paddle `pro_...`, `pri_...`, or sync-timestamp metadata after a real save. Do not treat `done` as `passed`, do not retry product saves, and do not repair credentials or product code. This task still performs the real checkout attempt to measure the customer-facing impact.
- `renewals.sync_to_billing_cycle` OFF and the product has no `_arraysubs_flex_sync_enabled`, else `maybeHideUnsupportedRenewalSyncGateways()` hides Paddle. Re-read that meta first.
- `slt-paddle` is Paddle-only. Run after 12:00 site, never inside D3's SYN-04 bracket.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily, day/1, $11.00 |
| Account | slt-paddle / `SltQa!2026#Pass` |
| Card | Paddle sandbox 4242 4242 4242 4242, future expiry |
| Sessions | `cust-SLT-CHK-04`, `admin-SLT-CHK-04` |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`.
2. `agent-browser --session cust-SLT-CHK-04 open ".../my-account/"` → log in as `slt-paddle`; `/cart/` must be EMPTY; open `/product/slt-paddle-daily/` → **Add to cart**.
3. Open `/checkout/` → `snapshot -i`; confirm **Total $11.00**, no tax row; shot `-01-gateways.png` — **Paddle must be listed**.
4. Select Paddle, **Place Order**. The overlay is a cross-origin iframe: re-`snapshot -i` once loaded, fill email/card/country, submit; shot `-02-overlay.png`.
4a. **Known-finding continuation:** if Place Order cannot initialize the Paddle overlay or cannot reach an order-received page because the missing catalogue IDs prevent checkout, stop card entry, capture the exact UI text plus console/network evidence, prove the cart cleanup and unchanged order/subscription counts, close only `cust-SLT-CHK-04` and `admin-SLT-CHK-04`, and complete this execution task as `FAIL`. Add the checkout-impact proof to a standalone `issues/SLT-CHK-04-*.md` file that references the SETUP-05 finding; do not create a kanban bug card, do not fabricate `ORDER`/`SUB_PAD`, and do not block later non-Paddle work. Every downstream task that requires `SUB_PAD` must record that dependency as `UNVERIFIED` with this issue path rather than buying a substitute or mutating product/source state.
5. On order-received record `ORDER`; shot `-03-received.png`; capture the pending notice verbatim and any console error.
6. Read `wp_wc_orders` for `$ORDER` at once; record the status **before** the webhook. Poll up to 5 min until it leaves `pending`; then `mailpit-agent wait-new "$PRE" 300 "is active"`, save the exact matched id, and reconcile the complete PRE delta by exact order/subscription id and `To:`.
7. Read the subscription linkage with `LINK_JSON=$(wp post meta get "$ORDER" _subscription_ids --format=json --allow-root)` and resolve exactly one numeric ID through a strict `jq -e` guard; this HPOS runtime does not expose the legacy linkage through `WC_Order::get_meta()`. Cross-check one `arraysubs_data` row with `_parent_order_id=$ORDER` and `SUBCOUNT_BEFORE+1`, then publish the ID under canonical alias **`SUB_PAD`**. Dump its meta to `/home/server-manager/slt-evidence/SLT-CHK-04-sub-meta.txt`; record `_next_payment_date` as `NPD`. Using the redacted Paddle API procedure from SLT-SETUP-05, fetch only this remote subscription's `id`, `status`, and `next_billed_at` without printing/saving credentials or management URLs; record the remote timestamp beside NPD.
8. Compute `k` (REF-01 §0). In isolated `admin-SLT-CHK-04`, open **Tools → Scheduled Actions** → Pending for `SUB_PAD`; shot `-04-pending.png`; record both GMT timestamps against `NPD+k`/`NPD+k−6h`.
9. In the customer session open My Account → subscription detail; shot `-05-myaccount.png`; record whether **Renew Early** shows. Append `SUB_PAD`/`ORDER` to the registry. Reopen `/cart/`, prove it is EMPTY and the persistent-cart user meta is empty, capture `SLT-CHK-04-06-cart-empty.png`, then close only `cust-SLT-CHK-04` and `admin-SLT-CHK-04`.
10. Publish this watch handoff to `SLT-REN-04`: renewal #1 is expected to be Paddle-driven; the local `arraysubs_process_renewal` leg should be a no-op noting "awaiting automatic charge from Paddle". `SLT-REN-04`, not this purchase card, owns the next-morning observation, so this card closes after steps 1–9 and the handoff are complete.

## Expected results
1. Paddle is offered for this cart; total `11.00` USD, zero tax items; right after Place Order the order is `pending` and the received page shows the Paddle notice.
2. After the webhook the order is paid (`processing`/`completed`) with `_paddle_transaction_id` + `_last_gateway_transaction_id`.
3. One new `arraysubs_data` post, `arraysubs-active`, `_payment_method=arraysubs_paddle`, `_recurring_amount=11`, `_currency=USD`, `_completed_payments=1`, `_parent_order_id=$ORDER`, `_subscription_ids=[$SUB]` on the order.
4. `_gateway_paddle_subscription_id` is non-empty and `_gateway_paddle_payout_amount` is set. The generic `_gateway_customer_id` / `_gateway_payment_method_id` fields carry Paddle-shaped `ctm_...` / `sub_...` identifiers on this runtime; require that neither contains a Stripe-shaped `cus_...` / `pm_...` value.
5. `NPD` equals Paddle's `next_billed_at`, not necessarily `_start_date+24h` — record both and the difference.
6. **Known-fragile:** `syncNextPaymentDate()` writes meta only, so the AS legs may not match `NPD+k`/`NPD+k−6h`. Record the delta; a mismatch is an observation, not an auto-failure.
7. **Renew Early is hidden** despite `allow_early_renew=true`, because Paddle declares `early_renewal:false`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | webhook pays order | admin | `New order #$ORDER` | complete PRE delta, exact ORDER + admin `To:` |
| 2 | WC paid-order status mail | webhook pays the virtual-only order | slt-paddle | record whether the webhook leaves `processing` (`order has been received`) or auto-completes (`is on its way`) | same complete PRE delta; subject must match observed paid status |
| 3 | `new_subscription` | → `arraysubs-active` | slt-paddle | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 300 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | complete PRE delta, exact SUB + admin `To:` |
| 5 | NONE EXPECTED | signup | — | — | no `Invoice for subscription`, no `Verify your subscription renewal` (Paddle `sca:false`), no `Payment failed` |

## Evidence to capture
- Shots 01–06; `SUB`, `ORDER`, `k`, `NPD`, `next_billed_at`, AS timestamps + delta; pre/post-webhook order rows; `SLT-CHK-04-sub-meta.txt`; final cart and persistent-cart proof.

## Pass criteria
- [x] Paddle offered; overlay completed; $11.00 USD, no tax
- [x] Order seen `pending` first, then paid by webhook with Paddle transaction meta
- [x] One `arraysubs-active` sub, `_payment_method=arraysubs_paddle`, Paddle sub id set
- [x] `NPD` recorded against `next_billed_at`; AS-leg delta recorded
- [x] Renew Early hidden, generic gateway identifiers are Paddle-shaped rather than Stripe-shaped; mails 1–4 present; row 5 holds
- [x] Same-session cart and persistent-cart meta empty after checkout; both exact task sessions closed
- [x] Known failure branch not needed: the overlay and order succeeded despite the missing catalogue IDs, and the counterexample was appended to the existing standalone SETUP-05 issue

## Isolation / teardown
- `SLT Paddle Daily` and `slt-paddle` stay Paddle-only all window. `SUB_PAD` is the sole subscription for that account/product pair and the canonical hand-off to `SLT-REN-04`, `SLT-EML-03`, `SLT-ADM-07`, and `SLT-MYA-03`; none of those tasks may buy it again. It stays live for the watch and is cancelled by SETUP-99A on D10 — that also cancels the Paddle-side subscription, so never cancel early. Nothing global changed.

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

## D03 execution result — 2026-08-05

**PASS.** In isolated customer session `cust-SLT-CHK-04`, the real block checkout offered Paddle at exactly USD `11.00` with no tax. Its cross-origin overlay loaded despite the known missing local catalogue metadata. The retained overlay screenshot was captured before any payment fields were populated; no evidence contains the full test card. The received page first showed the explicit processing notice for pending order `12629` and pending subscription `12639`; Paddle's webhook then completed the order and activated the subscription.

The immediate count delta was exactly one order and one subscription (`562 -> 563`, `368 -> 369`). Exact order link metadata resolves only `12639`; the exact owner/product query for user `352` and product `12112` also resolves only `12639`. Order `12629` is `wc-completed`, USD `11.00`, `arraysubs_paddle`, with matching `_paddle_transaction_id` and `_last_gateway_transaction_id` values. Subscription `12639` is `arraysubs-active`, recurring USD `11`, one completed payment, and carries Paddle subscription id `sub_01kz8q1025tryjfgxvn5e3v4gf`, payout `1100 USD`, a Paddle-shaped `ctm_...` customer id, and Paddle-shaped `sub_...` generic payment-method id.

Local `NPD=2026-08-06 10:20:38Z`; the redacted remote API returned status `active` and `next_billed_at=2026-08-06T10:20:38.143985Z`, an exact stored-second match. With `k=370s`, final pending action `14853` is at `NPD+k-6h=2026-08-06 04:26:48Z` and action `14854` is at `NPD+k=2026-08-06 10:26:48Z`; the synchronized originals `14851`/`14852` are canceled. My Account showed Active, Paddle, masked Visa ending `4242`, full lifecycle/payment notes, and no Renew Early control.

The complete consecutive Mailpit delta after `56kcLytDylTWndyI4kEeYS` is customer completed-order `1r5TIKAYhhbbjwMtLBAxOZ`, admin new-order `3ulpIttY4ztfpXmAeRea4o`, customer active-subscription `2q7ZRgzwykMkBKlw2ZgsdQ`, and admin new-subscription `50GJjz3ekgXoIfje5d6UwY`; invoice, verification/SCA, and payment-failed negatives hold. Browser errors were empty; Store API/Paddle/receipt calls succeeded. The final browser cart and persistent cart were empty. Both exact task sessions were closed after publication.

Evidence: `/home/server-manager/slt-evidence/SLT-CHK-04-facts.txt`, `SLT-CHK-04-sub-meta.txt`, and `SLT-CHK-04-01-gateways.png` through `SLT-CHK-04-06-cart-empty.png`. The successful purchase was appended as a scope counterexample to `issues/SLT-SETUP-05-paddle-product-sync-metas-not-created.md`; no new product issue was found and no product implementation was accessed or changed. Canonical handoff: `SUB_PAD=12639`, `ORDER=12629`; `SLT-REN-04` owns tomorrow's Paddle-driven renewal observation.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: capture PAD_REN_PRE 2026-08-06 04:21:48Z-04:26:47Z (10:21:48-10:26:47 site) before invoice action 14853, then preserve it through the Paddle remote/local renewal evidence.

[[2026-08-05]] Wed 16:41
D4/D5 Paddle follow-up: immutable invoice baseline window 2026-08-06 04:21:48Z-04:26:47Z; actions 14853 at 04:26:48Z and 14854 at 10:26:48Z; remote next_billed_at 10:20:38Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4/D5 Paddle: baseline 2026-08-06 04:21:48Z-04:26:47Z; actions 14853/14854; remote gate 10:20:38Z.

[[2026-08-05]] Wed 17:44
D4 Paddle follow-up: baseline 2026-08-06 04:21:48Z–04:26:47Z; invoice 14853, remote billing 10:20:38Z, local charge 14854 at 10:26:48Z.
