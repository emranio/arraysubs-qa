---
id: 5
title: Classic Stripe checkout writes two payment metadata rows absent from block checkout
status: open
priority: high
created: 2026-08-23T12:52:19.644078758+02:00
updated: 2026-08-23T12:52:19.644078758+02:00
tags:
    - cycle-2
    - stage-slt-d00
    - stripe
    - checkout
    - parity
    - metadata
class: standard
---

## QA linkage

- Lifecycle task: `#2` / `SLT-CHK-02`, D00.
- Plan: `qa/kanban/tasks/002-classic-checkout-parity-same-slt-daily-core.md`.

## Affected records

- Block control: order `31601`, subscription `31602`, customer `474` / `slt2-core` / `slt2-core@example.test` / customer.
- Classic path: order `31617`, subscription `31618`, customer `483` / `slt2-core2` / `slt2-core2@example.test` / customer.
- Product: `31340`, `SLT2 Daily Core`.

## Route and reproduction

- URLs: `https://mirror-help.arrayhash.com/checkout/` and `https://mirror-help.arrayhash.com/slt2-classic-checkout/`.
- Browser contexts: isolated real `agent-browser` customer sessions for the block and classic Stripe test-card purchases.

1. Complete the documented block Stripe control checkout.
2. Complete the classic Stripe checkout with its dedicated buyer.
3. Resolve each subscription from its recorded parent order and diff all payment-method metadata.

## Expected

Block and classic checkout should persist the same canonical subscription payment-method state, except for an explicitly documented checkout-specific allowlist.

## Actual

Classic subscription `31618` additionally contains `_payment_method_updated_at` and `_payment_method_source=stripe`; both rows are absent from block subscription `31602`. The required order/subscription/gateway/renewal invariants otherwise pass.

## Proof

- Baseline: `/home/server-manager/slt-evidence/SLT-CHK-01-sub-meta.txt`.
- Classic comparison: the task `#2` subscription-meta dump and field-by-field diff.
- Browser receipts prove both purchases completed; this finding is metadata parity, not a duplicate order or duplicate charge.

## Scope and counterexample

The block checkout has a valid Stripe method and scheduled renewal without the two rows. No unsafe transaction was attempted to diagnose the discrepancy. Keep this issue open until ownership and downstream consumers of both keys are proven and parity is either fixed or explicitly specified.
