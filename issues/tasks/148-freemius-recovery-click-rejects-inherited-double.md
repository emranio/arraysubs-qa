---
id: 148
title: Freemius recovery click rejects inherited double-slash sender labels
status: blocked
priority: high
created: 2026-10-30T04:03:15.419664+06:00
updated: 2026-10-30T04:03:49.08851+06:00
tags:
    - freemius
    - email
    - customer-report
blocked: true
block_reason: Sender configuration corrected live and persisted; existing signed recovery link and provider-side error disclosure still need Freemius verification.
class: standard
---

## Scope and identities
- Active QA task ID, scheduled day, and plan path: N/A. Direct customer-reported fix requested on 2026-10-30 (Asia/Dhaka); no active QA execution plan.
- Subscription IDs, order IDs and WordPress customer/user IDs/roles: N/A. Affected recipient: info@tec-logic.es.
- Original failing route: Freemius /apps/app_id/emails/email_id/clicks.html, reached from the first abandoned-cart recovery email (cart_email_1_sent), product 33435.
- Configuration route: https://dashboard.freemius.com/#!/live/stores/17537/emails/addresses/ . Product 33435 inherits these store addresses; product email-address API returns 404 for inherited settings as documented by Freemius.

## Reproduction and proof
1. The customer abandoned Agency checkout and received the recovery email.
2. Clicking its completion link returned HTTP 400 with email_from_name_contains_invalid_chars and sender name Emran // ArrayHash.
3. Expected: the link proceeds to checkout. Actual: Freemius renders its raw error/request, including recipient and authorization fields.
4. Signed-in Edge dashboard confirmed all four store labels were Emran // ArrayHash.
- Original screenshot and raw authorization strings have deliberately not been copied into this board.

## Fix plan and completed configuration change
- Replace the double-slash labels with plain text, preserving each configured email address and DKIM configuration.
- Updated General System Address, General Support, Dont Reply and Developer Personal Assistance labels to Emran at ArrayHash.
- Freemius displayed Email was successfully updated. Reloaded the page and confirmed all four saved labels persisted.
- Proof: ../../artifacts/portal-recovery-2026-10-30/sender-labels-saved-edge.png .

## Remaining verification / provider boundary
- The saved configuration is corrected live. The original already-sent recovery URL has not been replayed; its embedded sender value may remain in the signed link. A fresh recovery email has not been sent.
- The direct clean hosted Agency URL with billing_cycle=lifetime&coupon=teclogic80 works in Edge, showing $179.80 USD before applicable tax. It avoids the failing email click tracker.
- Freemius owns the click endpoint and raw authorization disclosure; there is no implementation of either endpoint in the local portal or plugins. Their handling of already-issued malformed links and error redaction requires provider-side verification/fix.
