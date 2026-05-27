---
id: 171
title: 'stage-19: 05 Refund to Store Credit (Pro)'
status: closed
priority: medium
created: 2026-05-19T22:56:24.10777198+02:00
updated: 2026-05-24T23:20:28.710214403+02:00
started: 2026-05-23T08:06:53.487257344+02:00
completed: 2026-05-23T20:09:49.796308259+02:00
tags:
    - qa
    - stage-19
class: standard
---

Source: stages/19-refunds/05-refund-to-credit-pro.md

[[2026-05-23]] Sat 20:09
QA complete with issues. Fixture: cust5@example.com user #52, one-time order #1750, Plain Mug x4, total 0.00, Stripe charge ch_3TaJk9JG5OzSNVs20omxHnq6, pre-credit balance /bin/bash.00. Pro active and store_credit.enabled=1. Admin order browser verified Refund Method options: Refund via Payment Gateway and Refund as Store Credit. After selecting Store Credit and entering 40.00 + reason 'QA refund-to-credit full order', UI showed Current Balance /bin/bash.00, Balance After 0.00, Max Refundable 0.00. Processing path still uses native confirm/alert; appended evidence to existing #85 and completed equivalent server-side credit refund logic. Result: credit log #1753 source=refund amount=40 source_id=1750 note reason, customer balance 0, order _refunded_as_credit=40, WC total_refunded=0, no Stripe refund meta/webhook, order note 'Refund issued as store credit: 0.00. QA refund-to-credit full order'. Admin Store Credit Management search still showed no cust5 result; appended #74. Admin Credit History showed 0 transactions/route error despite DB log; appended #75. Customer portal Store Credit showed balance 0.00 and transaction 'QA refund-to-credit full order Refund +0.00'; customer order detail did not show credit-refund wording, issue #160. After full credit refund, Woo order refund form still allowed standard gateway/manual refund controls and showed total available 0.00, creating double-refund risk; issue #161. Mailbox/body proof for Credit Added remains blocked by #137.

[[2026-05-24]] Sun 23:10
Follow-up issue #160 fixed customer-visible store-credit refund summary on My Account order detail. Browser as cust5 verified order #1750 shows Store Credit Refund and 0.00 summary.

[[2026-05-24]] Sun 23:20
Follow-up issue #161 fixed the double-refund path after a full store-credit refund. Woo order #1750 now blocks native gateway/manual refund attempts when _refunded_as_credit consumes the order total. Backend AJAX guard returned remaining refundable /bin/bash.00; browser order edit page shows the store-credit block message and disabled Refund via Stripe / Refund manually buttons. Screenshot: qa/artifacts/issue-161/order-1750-standard-refund-blocked-after-credit.png.
