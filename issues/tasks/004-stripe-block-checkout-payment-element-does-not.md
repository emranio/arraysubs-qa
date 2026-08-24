---
id: 4
title: 'Stripe block checkout: Payment Element does not remain usable for card entry'
status: closed
priority: critical
created: 2026-08-23T12:52:19.480339094+02:00
updated: 2026-08-23T12:52:19.549708634+02:00
started: 2026-08-23T12:52:19.549707823+02:00
completed: 2026-08-23T12:52:19.549707823+02:00
tags:
    - cycle-2
    - stage-slt-d00
    - stripe
    - checkout
    - regression
    - blocks-stripe-spine
class: standard
---

## QA linkage

- Lifecycle task: `#1` / `SLT-CHK-01`, D00.
- Plan: `qa/kanban/tasks/001-block-checkout-happy-path-slt-core-buys-slt-daily.md`.

## Affected records

- Initial failed attempt: subscription/order IDs `N/A`; payment was not submitted.
- Customer: WordPress user `474`, `slt2-core` / `slt2-core@example.test`, role `customer`.
- Product: `31340`, `SLT2 Daily Core`.

## Route and reproduction

- URL: `https://mirror-help.arrayhash.com/checkout/`.
- Browser context: isolated customer `agent-browser` session on the real block checkout.
- Reproduction: load the registered product in an empty cart, select Stripe, and attempt to enter the documented Stripe test card in the Payment Element.

## Expected

The Stripe Payment Element remains mounted and usable through card entry and checkout submission.

## Actual

On the original attempt the Payment Element did not remain usable, so the Stripe D00 purchase spine stopped before any order, subscription, or charge was created.

## Resolution and proof

The same owner task was rerun through the real browser checkout and completed successfully, producing Stripe order `31601` and active subscription `31602` for the exact customer/product. The receipt, subscription linkage, scheduler rows, and provider payment were reconciled in task `#1`; the issue was closed after the mandatory rerun passed.

## Scope

No failed-attempt order or charge exists. Classic checkout parity is tracked separately by lifecycle task `#2` and issue `#5`.
