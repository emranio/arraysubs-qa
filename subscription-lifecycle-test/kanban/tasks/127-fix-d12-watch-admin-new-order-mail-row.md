---
id: 127
title: Fix D12-WATCH admin new-order mail row
status: done
priority: high
created: 2026-08-14T12:41:29.19665985+02:00
updated: 2026-08-14T13:14:29.226870884+02:00
started: 2026-08-14T12:41:34.033252613+02:00
completed: 2026-08-14T13:14:29.144679767+02:00
tags:
    - bug
    - d12-watch
class: standard
---

Investigate subscription-lifecycle-test/issues/done-D12-WATCH-mail-row-omits-admin-new-order.md against its originating watch task, Mailpit evidence, email code, and live staging behavior. Fix confirmed product defects, verify end to end, update the report, then close it.

[[2026-08-14]] Fri 13:04
Investigation confirmed a QA-oracle defect and exposed a pending cross-feature renewal-email regression. The authoritative watch/reference contracts, D12 report, D13 teardown gate, EmailManager filter split, and authenticated cancelled credit-only classifier are updated. Live order/subscription/Mailpit proof passed; renewal/ordinary/unsigned-switch recipient matrices passed; disposable signed credit-only fixture 26289/26292/26294 suppressed all intended mail, rejected raw-meta spoofing, left Mailpit unchanged, and was fully removed.

[[2026-08-14]] Fri 13:14
Independent final review: PASS. No actionable D12 regression found. Renewal admin New-order remains enabled; the five customer renewal mails remain suppressed; broader credit-only suppression stays behind the authenticated signed-contract boundary, including terminal cancellation. Static references, Mailpit pair, live data, browser relationship proof, and D13 dependency gates align.
