---
id: 58
title: 'stage-07: Trial and On-Hold action matrix lacks shipping-eligible fixtures'
status: closed
priority: medium
created: 2026-05-20T15:05:24.084305425+02:00
updated: 2026-05-22T05:34:41.417842671+02:00
started: 2026-05-22T05:31:20.244818417+02:00
completed: 2026-05-22T05:34:41.417841699+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - fixture
    - shipping
claimed_by: mold-glade
claimed_at: 2026-05-22T05:34:41.417842571+02:00
class: standard
---

Task 11 expects Update Shipping Address visibility for Trial and On-Hold eligible statuses. Current cust1 fixtures #663 Trial and #653 On Hold do not render a Shipping Address section/update control, so the shipping column for those statuses could not be verified as written. Active #643 and cutoff #683 did verify shipping behavior.

[[2026-05-22]] Fri 05:32
Plan: verify Task 11 matrix and current #663/#653 shipping meta; seed both fixtures with the same shipping eligibility data already used by active shipping fixtures (#643/#683): _arraysubs_needs_shipping=yes, recurring flat-rate method, totals, and address. Keep statuses/next payment unchanged; browser verify Trial #663 and On-Hold #653 both show Shipping Address + Update Shipping Address.

[[2026-05-22]] Fri 05:34
Fixed fixture gap: seeded #663 Trial and #653 On-Hold with recurring flat-rate shipping meta and address while preserving statuses/next payment. Alumnium verified #663 shows Cancel/Skip/Pause + Shipping Address + Update Shipping Address; #653 shows Resume Now + Shipping Address + Update Shipping Address.
