# Duplicate `postmeta` rows for gateway keys on a small number of subscriptions

- **Severity:** low (data defect; no behavioural impact observed because the duplicated values are identical)
- **Found:** 2026-08-01, design phase (direct DB inspection, before browser execution)
- **Status:** open — needs confirmation on freshly created SLT subscriptions
- **Originating task:** environment recon for `SLT-SETUP-01`
- **Plan file:** `qa/subscription-lifecycle-test/README.md`

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
