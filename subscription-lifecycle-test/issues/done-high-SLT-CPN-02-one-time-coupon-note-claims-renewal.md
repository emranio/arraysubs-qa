# One-time coupon capture note incorrectly claims a renewal discount

- **Severity:** low
- **Status:** resolved on 2026-08-14
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

## Resolution

### Confirmed root cause

Coupon capture correctly stored `one-time`, zero cycles, and the effective fixed
discount, while renewal application correctly returned unless the stored duration
was exactly `recurring`. The defect was isolated to the note builder: every fixed
discount used `off per eligible renewal` without consulting duration.

### Fix and dependency review

- Fixed recurring amounts retain `Discount: %s off per eligible renewal.`
- Fixed one-time amounts now use `Discount: %s off the initial order.`
- Percent wording, coupon snapshot metadata, the
  `arraysubs_coupon_applied_to_subscription` payload, renewal fee calculation,
  cycle counters, REST output, and Pro listeners are unchanged.
- The amount remains formatted through WooCommerce and the complete note remains
  sanitized by `arraysubs_add_subscription_note()`; no request, capability,
  nonce, or output-escaping boundary changed.

### Live regression proof

- A real classic checkout used published product `11927`, coupon `12067`, customer
  `347`, and BACS. Disposable order `26561` totaled `$5.00` from a `$10.00` line
  and `$5.00` coupon; disposable subscription `26562` captured exactly one coupon
  note.
- The admin subscription UI displayed:
  `Coupon "sltfix5first" captured from checkout order. Duration: one-time (initial order only). Discount: $5.00 off the initial order.`
- Metadata was exact: `_coupon_discount_type=one-time`, fixed amount `5`, percent
  `0`, original/remaining cycles `0/0`, and no initial-cycle-pending marker.
- Calling the renewal-discount handler against that fixture left the order
  invariant: total `$5.00`, zero fee items, and only the original checkout coupon.
- A disposable fixed `$2.00`, two-cycle recurring control retained:
  `Discount: $2.00 off per eligible renewal.`
- Browser page errors were empty and console output contained only routine
  migration/dependency-detection messages. `git diff --check` passed; PHPCS was
  intentionally skipped under the issue-fix workflow.
- Evidence:
  - `/home/server-manager/slt-evidence/HIGH-SLT-CPN-02-02-coupon-applied.png`
  - `/home/server-manager/slt-evidence/HIGH-SLT-CPN-02-03-classic-checkout-direct.png`
  - `/home/server-manager/slt-evidence/HIGH-SLT-CPN-02-05-order-received.png`
  - `/home/server-manager/slt-evidence/HIGH-SLT-CPN-02-07-admin-sub-note.png`

### Teardown

Order `26561`, subscription `26562`, its three subscription notes, its order audit
note, the temporary recurring coupon/control subscription, the rejected temporary
customer, and that customer's audit notes were deleted. Exact final checks found
zero related actions/notes/objects; coupon `12067` returned to usage count `1`
with only original user `362` in `used_by`. Existing customer `347`, product
`11927`, coupon `12067`, settings, and unrelated natural scheduler activity were
left intact.
