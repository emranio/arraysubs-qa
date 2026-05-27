---
id: 158
title: stage-19 Stripe refund webhook audit records zero amount
status: closed
priority: high
created: 2026-05-23T19:14:39.534458582+02:00
updated: 2026-05-24T23:01:28.282455615+02:00
started: 2026-05-24T22:56:17.4068867+02:00
completed: 2026-05-24T23:01:19.503357173+02:00
tags:
    - qa
    - stage-19
    - refunds
    - stripe
    - webhook
    - audit
class: standard
---

Task: stages/19-refunds/02-prorated-refund-on-cancellation.md

Fixture: subscription #1704, order #1701, Woo refund #1707, Stripe refund re_3TaIxpJG5OzSNVs20vuEzRna. REST processed prorated refund with reason 'QA prorated test'.

Expected: Stripe refund/order/subscription notes and webhook audit should report the actual refunded amount (1.42 in this run; expected should have been 0.00 per separate proration issue #156).

Observed: Woo order note correctly says 'Refunded 1.42 - Refund ID: re_3TaIxpJG5OzSNVs20vuEzRna - Reason: ... QA prorated test'. But a later order note and subscription note from the Stripe webhook say 'Stripe refund received via webhook: /bin/bash.00 USD' / 'External refund detected from Stripe dashboard: 0.00 USD'. wp_arraysubs_webhook_events has charge.refunded evt_3TaIxpJG5OzSNVs20zgWh33U at 2026-05-23 17:06:53, so the webhook arrived but amount extraction/audit is wrong.

Impact: refund audit trail misreports real Stripe refunds as /bin/bash.00, making merchant/customer audit evidence unreliable.

[[2026-05-23]] Sat 19:45
Stage 19 Task 04 reproduced same Stripe refund webhook amount bug after full manual refund. Order #1730 / subscription #1733: Woo refund #1741 and order note correctly show 9.99 with Stripe refund re_3TaJUTJG5OzSNVs21vhwKD56, but webhook/order/sub note says 'Stripe refund received via webhook: /bin/bash.00 USD' / 'External refund detected from Stripe dashboard: 0.00 USD'. wp_arraysubs_webhook_events recorded charge.refunded evt_3TaJUTJG5OzSNVs21VoHoxi1.

[[2026-05-23]] Sat 20:18
Stage 19 Task 06 repeat: Stripe refund webhook for order #1755/sub #1758 logged External refund detected / order note as /bin/bash.00 USD, while actual Woo/Stripe refund was 9.99 (re_3TaK0vJG5OzSNVs20Hgvoj8r).

[[2026-05-24]] Sun 23:00
Fix: Stripe refund webhook extraction now supports charge payloads with refunds.data, direct refund objects, nested data.object refund payloads, and amount_refunded fallback. If Stripe omits amount fields but a Woo order refund already exists, it infers the amount from matching/latest Woo refund instead of logging 0.00. Verification: php -l passed; FPM reloaded; WP-CLI reflection test returned 1.42 for charge.refunded refunds.data, 9.99 for direct refund object, 19.99 for nested data.object refund, and 9.99 for charge amount_refunded fallback. This fixes future webhook audit/order/subscription notes; old logged webhook rows remain historical.
