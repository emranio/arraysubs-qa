# Duplicate `postmeta` rows for gateway keys on a small number of subscriptions

- **Severity:** high (confirmed race on a fresh Stripe checkout; duplicated rows can later diverge)
- **Found:** 2026-08-01, design phase (direct DB inspection, before browser execution)
- **Status:** confirmed on fresh SLT checkout; documented here as a non-blocking product defect
- **Originating task:** environment recon for `SLT-SETUP-01`
- **Plan file:** `qa/subscription-lifecycle-test/README.md`

## Task / stage / plan

- QA progress task: `#10` / `SLT-SETUP-01`
- Stage: `D0`
- Plan path: `qa/subscription-lifecycle-test/README.md`

## Affected objects

| | |
|---|---|
| Subscription IDs | confirmed on `9092`; a handful of others (see counts below). All pre-existing, **non-SLT** |
| Order IDs | N/A |
| Product IDs | N/A |
| WP user IDs | N/A |
| Gateway | Stripe (the inspected subscription stores `cus_UqVM9Vikk2fVZ8`) |
| Checkout type | unknown — these predate this plan |
| Non-default settings | none |

## Affected user / customer context

- WordPress user ID(s): `N/A` for the historical non-SLT aggregate rows discussed at the top of this issue
- Fresh SLT confirmation customer: user `347`, login/email `slt-core` / `slt-core@example.test`, role `customer`
- Acting WordPress user for the D0 fresh-checkout confirmation: `admin` / `administrator`

## Expected result

One `wp_postmeta` row per gateway meta key per subscription. `AbstractArraySubsGateway::persistGatewayMeta()`
(`arraysubspro/src/Features/AutomaticPayments/Abstracts/AbstractArraySubsGateway.php:744-767`) uses
`update_post_meta()` for all thirteen allowed gateway keys, which is single-row by contract.

## Actual result

Subscription 9092 carries two rows for each of several gateway keys, with identical values:

```
$ wp post meta list 9092 --fields=meta_key,meta_value --allow-root
_payment_gateway        stripe
_payment_gateway        stripe
_gateway_status         active
_gateway_status         active
_gateway_customer_id    cus_UqVM9Vikk2fVZ8
_gateway_customer_id    cus_UqVM9Vikk2fVZ8
_payment_method_last4   4242
_payment_method_last4   4242
```

Site-wide extent (rows vs distinct subscriptions carrying the key):

| meta_key | rows | subscriptions | duplicates |
|---|---|---|---|
| `_payment_gateway` | 63 | 62 | 1 |
| `_gateway_status` | 40 | 38 | 2 |
| `_gateway_customer_id` | 23 | 20 | 3 |
| `_payment_method_last4` | 26 | 23 | 3 |

So this affects a small number of subscriptions, not all of them.

## Why it matters even though values match

`update_post_meta()` on a key that already has duplicate rows updates only the **first** row. Once a key is
duplicated, every later write leaves the stale second row behind, so the values can silently diverge — after
which `get_post_meta($id, $key, true)` returns whichever row comes first, non-deterministically with respect
to intent. A subscription could then report the wrong gateway status or the wrong stored card.

## Reproduction steps

1. `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`
2. `wp post meta list 9092 --fields=meta_key,meta_value --allow-root | grep _gateway`
3. Observe two rows per key.

## Scope notes

- `persistGatewayMeta()` itself is **not** the culprit — it uses `update_post_meta()` correctly. The duplicate
  writer was not located during design-phase static analysis; candidates are a direct `add_post_meta()` call,
  a `$wpdb` insert, or an import/migration path.
- The most recent `post_date` among subscriptions carrying these keys is `2026-07-10`, which suggests
  historical residue rather than an actively firing bug — but that is not conclusive, since the aggregate is
  over all rows of the key, not only the duplicated ones.
- **The question this plan actually answers:** do freshly created SLT subscriptions accumulate duplicate
  gateway meta? Every SLT checkout task should dump `wp post meta list <sub_id> | grep _gateway` and compare
  the row count against the distinct key count. If a fresh subscription duplicates, this is upgraded to high
  severity and gets its own issue with the writer identified.

## Suggested resolution

Locate the duplicate writer, then de-duplicate existing rows with a one-shot maintenance routine. Because the
workspace requires no backward compatibility, a cleanup that keeps the newest row per key is acceptable.

## Tracking disposition — 2026-08-02

This defect is tracked only in this issue file. The lifecycle-board card that briefly duplicated this report
was removed after the QA scope was clarified: product defects must not become lifecycle-board cards, must
not block execution of later QA tasks, and no further product-code edits are authorized. A provisional
serialization change had already been applied before that clarification, but concurrent-capture verification
was interrupted; this issue therefore remains a confirmed observation rather than a verified fix.

## Fresh SLT confirmation — 2026-08-02

| | |
|---|---|
| Originating QA progress task | Board task `#1`, `SLT-CHK-01`, D0; plan file `qa/subscription-lifecycle-test/kanban/tasks/001-block-checkout-happy-path-slt-core-buys-slt-daily.md` |
| Subscription / order | Subscription `11959`; parent order `11949` |
| Product | `11927`, SLT Daily Core |
| Customer | User ID `347`, `slt-core`, `slt-core@example.test`, role `customer` |
| Gateway / checkout | Stripe test `4242`; WooCommerce block checkout |
| Browser context / URL | `cust-SLT-CHK-01`; `https://mirror-help.arrayhash.com/checkout/order-received/11949/` and `/my-account/view-subscription/11959/` |

The fresh subscription was created once and immediately carried two identical rows for each of these keys:

| Key | Meta IDs | Value |
|---|---|---|
| `_payment_method_expiry_month` | `86205`, `86206` | `12` |
| `_last_gateway_transaction_id` | `86208`, `86209` | `ch_3TzyfXJG5OzSNVs20EXeBjGn` |

All other captured Stripe fields had one row. The adjacent/interleaved meta IDs, together with the synchronous
paid-order capture and official Stripe webhook capture paths, are consistent with concurrent
`update_post_meta()` calls both observing an absent key before either insert became visible.

### Reproduction and proof

1. Complete one new Stripe block checkout for a customer with no SLT Daily Core subscription.
2. Confirm exactly one new subscription was created and open its customer detail page.
3. Query `wp_postmeta` grouped by key for that subscription immediately after checkout.
4. Observe the two duplicate pairs above even though checkout, order linkage, card display, and scheduling all succeeded.

- Order-received screenshot: `/home/server-manager/slt-evidence/SLT-CHK-01-04-received.png`.
- Customer detail screenshot: `/home/server-manager/slt-evidence/SLT-CHK-01-06-myaccount.png`.
- No counterexample exists on this fresh record: both duplicated pairs were created during the initial capture;
  brand, last four, expiry year, customer ID, and payment-method ID remained single-row.

## Counterexample — SLT-CHK-02 classic checkout — 2026-08-02

The immediately following independent Stripe checkout, classic surface, created subscription `11991` and
order `11990` for user `357` (`slt-core2`). A grouped query found **zero duplicate meta keys** on `11991`.
Its effective gateway values matched the control record's invariant values. This counterexample narrows the
finding to a timing-sensitive capture race rather than every Stripe checkout; it does not invalidate the fresh
duplicate proof on `11959`.
