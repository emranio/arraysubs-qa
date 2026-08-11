---
id: 26
title: SLT-SETUP-05 Verify Paddle sandbox readiness and record the two-gateway capability matrix
status: done
priority: high
created: 2026-08-02T03:43:05.1550051+02:00
updated: 2026-08-05T21:37:49.34591883+02:00
started: 2026-08-03T07:52:23.691218976+02:00
completed: 2026-08-03T07:52:23.691218976+02:00
tags:
    - setup
    - day-01
due: "2026-08-03"
estimate: 1h
depends_on:
    - 5
    - 11
    - 22
    - 23
class: standard
---

> **SLT-SETUP-05** · group `foundation` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove that, with global renewal sync now off, Paddle is actually selectable at checkout, that `PaddleProductSync` pushed the SLT Paddle product into the Paddle sandbox catalogue, and publish the capability matrix that tells every later author which gateway can legitimately be used for which behaviour.

## Scope
- Gateway: both
- Checkout: block
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-02 complete (global sync OFF — without it Paddle is hidden for every sync-eligible subscription product).
- SLT-PROD-01 complete (`SLT Daily Core`) and SLT-PROD-14 complete (`SLT Flex Daily Next Cycle`); quote both registry entries before the cart probes.
- SLT-PROD-16 complete (`SLT Paddle Daily` and `SLT Retry Daily` exist).
- Verified gateway config: Stripe enabled, `testmode: yes`, UPE/accordion, saved cards on; Paddle id `arraysubs_paddle`, enabled, `test_mode: yes`, sandbox api_key/client_token/seller_id/webhook_secret set.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily ($11.00, day/1), SLT Daily Core ($10.00, day/1) |
| Account | N/A (guest browse only, no order placed) |
| Coupon | N/A |
| Card | N/A — do NOT complete a purchase in this task |
| Amounts | none charged |

## Steps
1. Record `PRE=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)`. Re-save `SLT Paddle Daily` once to trigger the sync: `agent-browser --session admin-SLT-SETUP-05 open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Paddle Daily ID>&action=edit"` -> click **Update** -> re-snapshot.
2. Verify the sync metas landed: `wp post meta list <ID> --keys=_arraysubs_gateway_paddle_product_id,_arraysubs_gateway_paddle_price_id,_arraysubs_gateway_paddle_synced_at --allow-root`.
3. If the price id is empty, inspect gateway configuration only through a redacted WP-CLI summary: print `enabled`, `test_mode`, and `seller_id`, plus `yes/no` presence flags for `api_key`, `client_token`, and `webhook_secret`; **never print or save raw credentials**. Then inspect the WooCommerce log source `arraysubs_paddle_sync` at `/wp-admin/admin.php?page=wc-status&tab=logs`, redacting any token or secret before evidence capture. Record the failure rather than fixing gateway credentials.
4. Add `SLT Daily Core` to a clean guest cart via the helper link and open block checkout: `agent-browser --session guest-SLT-SETUP-05 open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Daily Core ID>"` -> `agent-browser --session guest-SLT-SETUP-05 snapshot -i`.
5. Read the payment-method accordion from the snapshot and record which gateways are offered. This is the direct proof of the SLT-SETUP-02 rationale.
6. Empty the cart, then repeat step 4 with `SLT Paddle Daily`.
7. Empty the cart, then repeat with `SLT Flex Daily Next Cycle` (from SLT-PROD-14) — this one IS sync-eligible via the per-product pro override, so Paddle must be hidden here.
8. Do not place any order. `agent-browser --session guest-SLT-SETUP-05 open "https://mirror-help.arrayhash.com/cart/"` and remove all items; close only `guest-SLT-SETUP-05` and `admin-SLT-SETUP-05` by explicit name.
9. Append the capability matrix (Expected results 5) to `slt-catalog-registry`.

## Expected results
1. `SLT Paddle Daily` has non-empty `_arraysubs_gateway_paddle_product_id` (`pro_...`) and `_arraysubs_gateway_paddle_price_id` (`pri_...`) plus a `_arraysubs_gateway_paddle_synced_at` timestamp.
2. Block checkout with `SLT Daily Core` offers BOTH `Stripe` and `Paddle` (proves the baseline change achieved its purpose).
3. Block checkout with `SLT Paddle Daily` offers both gateways.
4. Block checkout with `SLT Flex Daily Next Cycle` offers Stripe but NOT Paddle — `arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false and `maybeHideUnsupportedRenewalSyncGateways()` removes it while a sync-eligible item is in the cart.
5. The recorded matrix reads: Stripe — automatic payments yes, SCA yes, renewal sync yes, early renew yes, trials yes, recurring coupon discounts yes. Paddle — automatic payments yes, `sca: false`, renewal sync NO (gateway hidden on sync carts), `early_renewal: false` (Paddle owns `next_billed_at`, so the Renew Early button stays hidden even with the baseline toggle on), trials yes, `different_billing_cycles: false` (never put two different-cycle Paddle subscriptions in one cart), `retention_amount_update: false`.
6. Cart is empty at the end; no order, no subscription, no customer created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task (no order placed) | — | — | Inspect the complete delta after `$PRE`; require zero message attributable to this task and classify unrelated/background mail by its actual owner |

## Evidence to capture
- Screenshots: `SLT-SETUP-05-01-paddle-sync-meta.png`, `SLT-SETUP-05-02-checkout-daily-core-gateways.png`, `SLT-SETUP-05-03-checkout-paddle-daily-gateways.png`, `SLT-SETUP-05-04-checkout-flex-nextcycle-gateways.png`.
- `wp post meta list` output for the three Paddle metas.
- Redacted gateway-readiness summary; sanitized `arraysubs_paddle_sync` log lines; console/network errors from the Paddle overlay script.

## Pass criteria
- [ ] Paddle product id and price id present on SLT Paddle Daily
- [ ] Stripe AND Paddle both offered for SLT Daily Core
- [ ] Paddle hidden for SLT Flex Daily Next Cycle
- [ ] Capability matrix recorded in the registry
- [ ] No order/subscription/customer created; zero mail

## Isolation / teardown
- State handoff: the capability matrix is binding. No SLT task may schedule Paddle for early renew, SCA, flexible-renewal-sync, or a mixed-cycle multi-subscription cart; those combinations are gateway-unsupported by design and must be filed as expected negatives, not bugs.
- Restores: cart emptied; nothing else changed. Paddle catalogue objects created in the sandbox are left in place (sandbox-only, no cleanup path from WP).

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

[[2026-08-03]] Mon 07:51
## D01 execution — FAIL

Paddle Daily product 12112 was re-saved exactly once through wp-admin and displayed Product updated, but the required Paddle product ID, price ID, and synced-at metadata remained absent immediately and after 15 seconds. Redacted settings confirmed the gateway is enabled in test mode with seller ID present and all three credential-presence flags yes. WooCommerce exposed no arraysubs_paddle_sync log source, and the current arraysubs-gateway log held only an unrelated webhook-cleanup line. Product finding: issues/critical-plugin-SLT-SETUP-05-paddle-product-sync-metas-not-created.md.

The gateway matrix itself passed: Daily Core 11927 and Paddle Daily 12112 each offered Stripe and Paddle; Flex Daily Next Cycle 12102 offered Stripe and excluded Paddle. No order was placed. Cart was emptied; user/subscription/HPOS counts remained 354/359/549; Mailpit baseline and final ID remained 42DI8ELEccd8qFsaMtyeag. Registry page 11847 contains exactly one CAPABILITY MATRIX (SLT-SETUP-05) block. Evidence: /home/server-manager/slt-evidence/SLT-SETUP-05-facts.txt and screenshots 01 through 07. Both named sessions were closed. Product source was not inspected or changed.

[[2026-08-03]] Mon 07:52
## Independent review

Re-read the authored task and the standalone issue; all required issue fields are present. Verified all seven PNGs and the facts file are non-empty, the registry marker occurs exactly once, live three-key meta output is still empty, final users/arraysubs_data/HPOS counts are 354/359/549, and Mailpit latest ID is unchanged. Screenshots independently prove both positive gateway rows and the sync-cart Paddle exclusion. Teardown is complete and no Review work remains for this task.
