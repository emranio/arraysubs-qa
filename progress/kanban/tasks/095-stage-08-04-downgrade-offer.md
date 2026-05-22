---
id: 95
title: 'stage-08: 04 Downgrade Offer'
status: closed
priority: high
created: 2026-05-19T22:56:11.631812877+02:00
updated: 2026-05-20T15:13:49.329000601+02:00
started: 2026-05-20T13:41:52.920813131+02:00
completed: 2026-05-20T15:13:49.328999649+02:00
tags:
    - qa
    - stage-08
claimed_by: mold-glade
claimed_at: 2026-05-20T15:13:49.329000501+02:00
class: standard
---

Source: stages/08-retention/04-downgrade-offer.md

[[2026-05-20]] Wed 15:13
QA notes: Downgrade offer depends on the same Before You Go customer modal. Discount/Too expensive flow already confirmed the retention modal heading opens, but cards remain Loading... and error Failed to cancel subscription appears (issue #61). Therefore downgrade card, plan-switch redirect/options, downgrade completion, negative no-downgrade case, and accepted analytics cannot be verified. Related Stage 07 Change Plan modal also fails to load options (issue #53), so even if retention card loaded the switch flow has a known blocker.
