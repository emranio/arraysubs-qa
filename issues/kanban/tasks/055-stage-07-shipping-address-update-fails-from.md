---
id: 55
title: 'stage-07: Shipping address update fails from customer portal'
status: closed
priority: critical
created: 2026-05-20T15:00:46.948232142+02:00
updated: 2026-05-21T22:43:46.698545193+02:00
started: 2026-05-21T22:40:12.475401836+02:00
completed: 2026-05-21T22:43:46.698544091+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - shipping
claimed_by: mold-glade
claimed_at: 2026-05-21T22:43:46.698545092+02:00
class: standard
---

Task 10.6 on active physical subscription #643 (>7 days before renewal): Update Shipping Address modal opens with required/optional fields prefilled. Changing Street Address to add Apt 5 and clicking Save Address returns visible error: Failed to update shipping address. Please try again. WP-CLI check shows _shipping_address unchanged.

[[2026-05-21]] Thu 22:41
Plan: verify #643 fixture and stage 07 task 10.6; test REST update as cust1; if endpoint fails, patch CustomerController/ShippingController/AddressManager; if endpoint is healthy, browser-test with required Alumnium XHR repair, verify meta/notes and restore fixture; close as fixed/stale if browser passes.

[[2026-05-21]] Thu 22:43
Verified/fixed: REST update as cust1 returned 200 and updated _shipping_address. Browser QA with required Alumnium XHR repair on #643: modal opened prefilled, Street Address changed Apt 5 -> Apt 6, Save Address succeeded, modal closed, page showed Apt 6, latest visible note recorded address line 1 change. DB confirmed _shipping_address Apt 6 and customer note #879. Restored fixture _shipping_address to original 77 Stage Seven St after verification. No code patch needed; original failure was stale/Alumnium XHR-procedure artifact.
