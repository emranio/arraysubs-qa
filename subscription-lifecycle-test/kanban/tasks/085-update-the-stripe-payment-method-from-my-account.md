---
id: 85
title: Update the Stripe payment method from My Account and prove the next unassisted renewal charges the new card
status: todo
priority: critical
created: 2026-08-02T03:43:10.273603127+02:00
updated: 2026-08-02T03:43:21.224459291+02:00
tags:
    - admin
    - portal
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 1.5h
depends_on:
    - 70
    - 11
    - 5
class: standard
---

> **SLT-MYA-02** · group `admin` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-ADM-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`high` · same-subscription collision / ambiguous target** — with `SLT-LIFE-02`, `SLT-EML-05`, `SLT-EML-02`, `SLT-EML-15`

- *Problem:* SLT-LIFE-02 (d6) targets 'S1 - a live arraysubs-active SLT Daily Core subscription from the SLT-CHK-* run' without naming it, and its arithmetic uses $10.00 day/1, which describes SUB_CORE (slt-core, the control spine). It consumes one cycle by paying it early, replaces both legs and shifts the anniversary. SLT-EML-05 runs on the SAME day (d6) and also consumes one SUB_CORE cycle by setting _auto_renew=off and paying the invoice manually. Two tasks eating the same cycle on the same day makes both results unreadable, and either one silently invalidates the D1-D12 watch's 'SLT Daily Core renews $10.00 unattended every afternoon' baseline that REN-01/REN-02/EML-15/ADM-06 established.
- *Required fix:* Pin SLT-LIFE-02's S1 to SLT-CHK-02's subscription (slt-core2 + SLT Daily Core, day/1, $10.00, Stripe, saved token, unsynced, no pending skip) - structurally identical to the spine and claimed by nothing else after D0. Name the subscription id explicitly in LIFE-02's Test data and preconditions, and keep its step 8 registry note ('slt-core2's cycle N was paid early on 2026-08-08') so the watch does not read the missing unattended renewal as a failure. Leave SUB_CORE to EML-05 on D6. Add a standing registry section 'control-spine reservations' naming SUB_CORE's owning tasks per day.

---
## Objective
Add a second Stripe card from the portal, make it default, and prove which subscription inherits it and that the next unattended renewal charges it. Built around a known hazard: the setup-intent path resolves the subscription via `findSubscriptionByGatewayCustomer()` with `numberposts => 1` (`PaymentMetaNormalizer.php:171-192`), so only ONE of slt-core's can be updated.

## Scope
- Gateway: Stripe test
- Checkout: N/A (My Account payment-methods flow)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-MYA-01 done (slt-core subscription-ID table exists). Stripe `saved_cards: yes`.
- **Act 08:00-11:00 site on D5 (2026-08-07)**, before any slt-core anniversary time (all D0/D3 buys were after 12:00), so the first renewal after the change lands that same afternoon.

## Test data
| Item | Value |
|---|---|
| Account | slt-core / `SltQa!2026#Pass`, session `--session customer-MYA-02` |
| Card on file | `4242 4242 4242 4242` (visa, from the D0 checkout) |
| New card | `5555 5555 5555 4444`, exp `12/34`, CVC `123` - extra Stripe test card added by this task; behaves like 4242 but brand `mastercard`/last4 `4444` make the proof unambiguous. Record it in the registry. |

## Steps
1. Offsets: `php -r 'foreach([<IDs>] as $id){$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d %ds\n",$id,$h%21600);}'`. Record each offset and `_next_payment_date`.
2. BEFORE dump for EVERY slt-core subscription into `/home/server-manager/slt-evidence/SLT-MYA-02-pm-before.txt`: `wp post meta list <ID> --keys=_gateway_customer_id,_gateway_payment_method_id,_payment_method_brand,_payment_method_last4,_payment_method_updated_at --allow-root`. Then `mailpit-agent latest-id` -> `MB02`.
3. Open `/my-account/payment-methods/` -> log in -> `snapshot -i` -> screenshot; record the saved method (brand, last4, expiry, Default) and the buttons offered.
4. Open `/my-account/add-payment-method/`, enter the new card, submit, then **Make default** on the mastercard row; screenshot. If the site says the card is already saved, record it and STOP - never delete the existing token, detaching it breaks pending off-session renewals; mark UNVERIFIED.
5. Wait up to 5 min for `setup_intent.succeeded`, repeat the step-2 dump into `-pm-after.txt`, `diff`, and identify `SUB_TARGET` = the subscription whose `_gateway_payment_method_id` changed. If none changed, that is the finding; still run step 7.
6. Screenshot the `Payment Method:` row on `/my-account/view-subscription/<SUB_TARGET>/`. Post `SUB_TARGET`, both `pm_...` ids and the subscriptions left on the old card to the registry.
7. **Follow-up, watch day D6 = 2026-08-08 (morning check):** the renewal at `_next_payment_date + offset` on 2026-08-07 PM is the first unassisted one after the change. On SUB_TARGET's renewal order confirm `_stripe_source_id` = NEW `pm_...` and `_stripe_card_brand = mastercard`; on a control subscription confirm the OLD `pm_...` and `visa`.

## Expected results
1. The methods page initially lists one saved method (visa 4242, Default); adding succeeds cleanly and mastercard 4444 exp 12/34 becomes Default.
2. Exactly one subscription changes, gaining `_payment_method_brand=mastercard`, `_payment_method_last4=4444` and a `_payment_method_updated_at` inside this task's window; its `_last_payment_failure*` metas are gone (retry reset) and its detail page shows the new card.
3. Watch day D6 (2026-08-08): SUB_TARGET's 2026-08-07 PM renewal charged the new `pm_...` at the unchanged amount, while a control subscription's renewal that night still used the old visa `pm_...` - the limit is documented, not assumed.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Adding / defaulting a card; also the invoice leg at due+offset-6h, suppressed for auto-payment subs (`EmailManager.php:504-510`) | - | - | `latest-id` after step 4 equals `MB02`; assert no `Invoice for subscription` mail |
| 2 | payment_successful | 2026-08-07 PM renewal | slt-core | `Payment received for subscription #<SUB_TARGET>` | `mailpit-agent wait-new <id taken before due> 900 "Payment received"` |

## Evidence to capture
- Screenshots `SLT-MYA-02-01-methods-before.png`, `-02-methods-default.png`, `-03-detail-row.png`, `-04-renewal-order-meta.png`; both meta dumps and their diff; `SUB_TARGET`; both `pm_...` ids; offsets; renewal order ids with `_stripe_source_id`/`_stripe_card_brand`; Mailpit ids.

## Pass criteria
- [ ] New card added and defaulted from the portal with no errors
- [ ] Exactly one subscription's card metas updated and its retry metas cleared
- [ ] SUB_TARGET's 2026-08-07 PM renewal charged the new pm (watch day D6 = 2026-08-08)
- [ ] Control subscription still on the old pm, documented
- [ ] Every `_next_payment_date` advanced exactly one cycle; no unexpected mail; invoice mail suppressed

## Isolation / teardown
- The new card stays for the rest of the window and whichever subscription inherited it keeps renewing on it; record this in the registry so no later task files a false bug about a changed brand/last4.
- No token deleted, no setting changed. Close only `--session customer-MYA-02`.

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
