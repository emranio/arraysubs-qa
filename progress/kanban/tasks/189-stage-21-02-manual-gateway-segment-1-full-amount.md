---
id: 189
title: 'stage-21: 02 Manual Gateway — Segment 1 Full-Amount Checkout'
status: open
priority: high
created: 2026-07-08T02:50:18.534475+06:00
updated: 2026-07-08T02:51:11.801367+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 25m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/02-manual-full-segment-checkout.md

Complete Direct-bank-transfer checkout with purchase day in segment 1. Required conditions: Today's charge = full $30 ('full first charge' row), next charge = next cycle boundary, subscription meta _renewal_sync_enabled=yes / mode=full / first_full_renewal=boundary, activation after payment keeps the synced date, renewal jobs scheduled at boundary (invoice ~6h before).
