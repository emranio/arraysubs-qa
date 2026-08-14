# One-time coupon capture note incorrectly claims a renewal discount

- **Severity:** low
- **Status:** open; wording-only defect discovered while verifying the CPN-02 capture fix
- **QA progress task / stage:** board task `#17`, `SLT-CPN-02`, D01
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/017-sltfix5first-5-fixed-one-time-coupon-on-classic.md`

## Affected objects and user

| Field | Value |
|---|---|
| Temporary subscription / initial order | `15589` / `15588` (removed after verification) |
| Temporary renewal order | `15600` (removed after verification) |
| Product | `11927` (`SLT Daily Core`) |
| Coupon | `12067` / `sltfix5first` |
| WordPress user | ID `362`; `slt-cpnfirst`; `slt-cpnfirst@example.test`; role `customer` |
| Browser contexts | `customer-SLT-CPN-02-fix`, `admin-SLT-CPN-02-fix` |
| Routes | `/slt-classic-cart/`, `/slt-classic-checkout/`, order-received for `15588`, and ArraySubs admin subscription detail for `15589` |

## Reproduction

1. Apply subscription-enabled fixed-cart coupon `sltfix5first`, configured as
   one-time, to `SLT Daily Core` in classic checkout.
2. Complete the `$5.00` Stripe checkout.
3. Open the linked subscription in ArraySubs admin and inspect the coupon capture
   note.

## Expected result

The note should describe a one-time discount, for example:
`Discount: $5.00 off the initial order.`

## Actual result and proof

The correctly captured one-time snapshot produced this note:

```text
Coupon "sltfix5first" captured from checkout order. Duration: one-time (initial order only). Discount: $5.00 off per eligible renewal.
```

The final sentence contradicts both the one-time duration and the actual billing
behavior. Live renewal invoice `15600` was correctly generated at the full
`$10.00`, with zero fee items and no coupon codes.

Evidence:

- `/home/server-manager/slt-evidence/fixes/SLT-CPN-02/screenshots/08-after-fix-admin-subscription-15589.png`
- `/home/server-manager/slt-evidence/fixes/SLT-CPN-02/screenshots/09-after-fix-renewal-15600-full-price-no-fees.png`

## Scope notes and counterexamples

- This is a wording-only issue. One-time renewal suppression is correct.
- Recurring fixed-amount coupons should retain the existing `off per eligible renewal` wording.
- Percent coupon notes use separate wording and are not affected by this exact suffix.
