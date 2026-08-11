# One-time subscription coupon is not captured on the subscription

- **Severity:** high
- **Status:** open; documented, does not block the natural full-price renewal watch
- **QA progress task / stage:** board task `#17`, `SLT-CPN-02`, D01
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/017-sltfix5first-5-fixed-one-time-coupon-on-classic.md`

## Affected objects and user

| Field | Value |
|---|---|
| Subscription ID | `12332` (`arraysubs-active`) |
| Parent order ID | `12331` (`wc-completed`, USD `5.00`) |
| Product ID | `11927` (`SLT Daily Core`) |
| Coupon ID / code | `12067` / `SLTFIX5FIRST` |
| Pending actions | invoice `14106` at `2026-08-04 09:51:37 UTC`; charge `14107` at `2026-08-04 15:51:37 UTC` |
| WordPress user | ID `362`; `slt-cpnfirst`; `slt-cpnfirst@example.test`; role `customer` |
| Browser context | isolated agent-browser session `customer-SLT-CPN-02` |
| Routes | `https://mirror-help.arrayhash.com/slt-classic-cart/`, `https://mirror-help.arrayhash.com/slt-classic-checkout/`, and order-received route for order `12331` |

## Reproduction

1. Configure coupon `SLTFIX5FIRST` as WooCommerce `fixed_cart`, amount `5`, `_arraysubs_apply_to_subscriptions=yes`, `_arraysubs_discount_duration=one-time`, cycles `0`, and expiry `2026-08-15`.
2. As fresh customer `slt-cpnfirst`, add only product `11927` to the classic cart.
3. Apply `SLTFIX5FIRST`; verify subtotal `$10.00`, discount `-$5.00`, and total `$5.00` in both the classic cart and checkout.
4. Complete Stripe test payment once and resolve subscription `12332` only from order `12331`'s exact `_subscription_ids=[12332]` relationship.
5. Inspect subscription postmeta and its `arraysubs_sub_note` rows.

## Expected result

The paid order remains `$5.00`, and the subscription captures the one-time coupon provenance:

- `_applied_coupon_id=12067`
- `_coupon_code=sltfix5first`
- `_coupon_discount_type=one-time`
- `_coupon_discount_amount=5.00`
- `_coupon_discount_percent=0`
- `_coupon_wc_discount_type=fixed_cart`
- `_coupon_original_cycles=0`
- `_coupon_remaining_cycles=0`
- `_coupon_count_initial=no`
- `_coupon_initial_cycle_pending` absent
- capture note beginning `Coupon "sltfix5first" captured from checkout order.`

The one-time type should then suppress recurring-discount fees on renewals.

## Actual result and proof

The checkout discount itself worked: exact order `12331` is completed for USD `5.00`, belongs to customer `362`, contains product `11927`, and reports coupon code `sltfix5first`. The receipt shows discount `-$5.00`, total `$5.00`, and sole related subscription `12332`.

However, the exact database guards return:

```text
SUB12332_COUPON_META_COUNT=0
SUB12332_COUPON_NOTE_COUNT=0
```

Subscription `12332` has none of `_applied_coupon_id` or `_coupon_*`. Its note set contains creation, Stripe webhook, activation, signup-email, and payment-success records but no coupon-capture note. `_coupon_initial_cycle_pending` is also absent, but only because the complete capture set is absent.

Evidence:

- `/home/server-manager/slt-evidence/SLT-CPN-02-01-coupon-settings.png`
- `/home/server-manager/slt-evidence/SLT-CPN-02-02-classic-cart-5.00.png`
- `/home/server-manager/slt-evidence/SLT-CPN-02-03-checkout-review.png`
- `/home/server-manager/slt-evidence/SLT-CPN-02-04-order-received.png`

## Scope notes and counterexample

- This is a capture/provenance failure, not a failed first discount: order `12331` charged the correct `$5.00`.
- The natural renewal watch remains valid. With no captured recurring coupon state, renewal `#1` should still charge the expected full `$10.00`; that outcome does not repair the missing one-time metadata.
- Recurring-coupon subscription `12318` is the direct working counterexample from the same site, product, date, gateway, and block-checkout window. It contains `_applied_coupon_id=12064`, `_coupon_code=sltpct20rec`, the complete recurring coupon meta set, and the exact capture note.
- The plan's anticipated misleading `per eligible renewal` wording was not observed because no one-time capture note was created at all.
