---
id: 26
title: SLT-SETUP-05 Verify Paddle sandbox readiness and record the two-gateway capability matrix
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - day-01
due: "2026-08-24"
estimate: 1h
depends_on:
    - 5
    - 11
    - 22
    - 23
class: standard
---

> **SLT-SETUP-05** · group `foundation` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove that, with global renewal sync now off, Paddle is actually selectable at checkout, that `PaddleProductSync` pushed the SLT2 Paddle product into the Paddle sandbox catalogue, and publish the capability matrix that tells every later author which gateway can legitimately be used for which behaviour.

## Scope
- Gateway: both
- Checkout: block
- Account: N/A
- Plugins: core-owned Stripe/Paddle gateway path; Pro may be active only for unrelated premium features

## Preconditions
- SLT-SETUP-02 complete (global sync OFF — without it Paddle is hidden for every sync-eligible subscription product).
- SLT-PROD-01 complete (`SLT2 Daily Core`) and SLT-PROD-14 complete (`SLT2 Flex Daily Next Cycle`); quote both registry entries before the cart probes.
- SLT-PROD-16 complete (`SLT2 Paddle Daily` and `SLT2 Retry Daily` exist).
- Verified gateway config: Stripe enabled, `testmode: yes`, UPE/accordion, saved cards on; Paddle id `arraysubs_paddle`, enabled, `test_mode: yes`, sandbox api_key/client_token/seller_id/webhook_secret set.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Paddle Daily ($11.00, day/1), SLT2 Daily Core ($10.00, day/1) |
| Account | N/A (guest browse only, no order placed) |
| Coupon | N/A |
| Card | N/A — do NOT complete a purchase in this task |
| Amounts | none charged |

## Steps
1. Record `PRE=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)`. Re-save `SLT2 Paddle Daily` once to trigger the sync: `agent-browser --session admin-SLT-SETUP-05 open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT2 Paddle Daily ID>&action=edit"` -> click **Update** -> re-snapshot.
2. Verify the sync metas landed: `wp post meta list <ID> --keys=_arraysubs_gateway_paddle_product_id,_arraysubs_gateway_paddle_price_id,_arraysubs_gateway_paddle_synced_at --allow-root`.
3. If the price id is empty, inspect gateway configuration only through a redacted WP-CLI summary: print `enabled`, `test_mode`, and `seller_id`, plus `yes/no` presence flags for `api_key`, `client_token`, and `webhook_secret`; **never print or save raw credentials**. Then inspect the WooCommerce log source `arraysubs_paddle_sync` at `/wp-admin/admin.php?page=wc-status&tab=logs`, redacting any token or secret before evidence capture. Record the failure rather than fixing gateway credentials.
4. Add `SLT2 Daily Core` to a clean guest cart via the helper link and open block checkout: `agent-browser --session guest-SLT-SETUP-05 open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT2 Daily Core ID>"` -> `agent-browser --session guest-SLT-SETUP-05 snapshot -i`.
5. Read the payment-method accordion from the snapshot and record which gateways are offered. This is the direct proof of the SLT-SETUP-02 rationale.
6. Empty the cart, then repeat step 4 with `SLT2 Paddle Daily`.
7. Empty the cart, then repeat with `SLT2 Flex Daily Next Cycle` (from SLT-PROD-14) — this one IS sync-eligible via the per-product pro override, so Paddle must be hidden here.
8. Do not place any order. `agent-browser --session guest-SLT-SETUP-05 open "https://mirror-help.arrayhash.com/cart/"` and remove all items; close only `guest-SLT-SETUP-05` and `admin-SLT-SETUP-05` by explicit name.
9. Append the capability matrix (Expected results 5) to `slt2-catalog-registry`.

## Expected results
1. `SLT2 Paddle Daily` has non-empty `_arraysubs_gateway_paddle_product_id` (`pro_...`) and `_arraysubs_gateway_paddle_price_id` (`pri_...`) plus a `_arraysubs_gateway_paddle_synced_at` timestamp.
2. Block checkout with `SLT2 Daily Core` offers BOTH `Stripe` and `Paddle` (proves the baseline change achieved its purpose).
3. Block checkout with `SLT2 Paddle Daily` offers both gateways.
4. Block checkout with `SLT2 Flex Daily Next Cycle` offers Stripe but NOT Paddle — `arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false and `maybeHideUnsupportedRenewalSyncGateways()` removes it while a sync-eligible item is in the cart.
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
- [ ] Paddle product id and price id present on SLT2 Paddle Daily
- [ ] Stripe AND Paddle both offered for SLT2 Daily Core
- [ ] Paddle hidden for SLT2 Flex Daily Next Cycle
- [ ] Capability matrix recorded in the registry
- [ ] No order/subscription/customer created; zero mail

## Isolation / teardown
- State handoff: the capability matrix is binding. No SLT2 task may schedule Paddle for early renew, SCA, flexible-renewal-sync, or a mixed-cycle multi-subscription cart; those combinations are gateway-unsupported by design and must be filed as expected negatives, not bugs.
- Restores: cart emptied; nothing else changed. Paddle catalogue objects created in the sandbox are left in place (sandbox-only, no cleanup path from WP).

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
