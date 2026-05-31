---
id: 96
title: 'stage-08: 05 Contact Support Offer'
status: closed
priority: high
created: 2026-05-19T22:56:11.742964475+02:00
updated: 2026-05-20T15:13:49.742699411+02:00
started: 2026-05-20T13:41:52.923805526+02:00
completed: 2026-05-20T15:13:49.74268847+02:00
tags:
    - qa
    - stage-08
claimed_by: mold-glade
claimed_at: 2026-05-20T15:13:49.742689633+02:00
class: standard
---

Source: stages/08-retention/05-contact-support-offer.md

[[2026-05-20]] Wed 15:13
QA notes: Contact Support offer configured scope uses technical_issues/missing_features, which are visible in the stale customer reason list. However shared retention offer modal is already blocked: Before You Go opens but offer card area stays Loading... and page shows Failed to cancel subscription. Please try again (issue #61). Thus Talk to support button/new-tab behavior, acceptance logging, and unchanged subscription verification cannot be completed. Empty-URL negative case is also blocked by same offer-render step.
