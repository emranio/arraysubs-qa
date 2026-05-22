---
id: 61
title: 'stage-08: Retention offer modal stuck loading and cancellation error appears'
status: closed
priority: critical
created: 2026-05-20T15:13:12.969094002+02:00
updated: 2026-05-21T22:57:27.098528433+02:00
started: 2026-05-21T22:43:53.693564207+02:00
completed: 2026-05-21T22:57:27.098527481+02:00
tags:
    - qa
    - stage-08
    - retention
    - customer-portal
claimed_by: mold-glade
claimed_at: 2026-05-21T22:57:27.098528332+02:00
class: standard
---

Task 02 with retention_offers_enabled=true and Discount Offer enabled for too_expensive: On active #683, selecting Too expensive and clicking Continue opens Before You Go modal with expected heading and intro, but offer area remains Loading... after wait. A visible/assertive page error also appears: Failed to cancel subscription. Please try again. Discount card never renders, so accepting offer, discount display, notes, and analytics cannot be verified.

[[2026-05-21]] Thu 22:46
Plan: patch retention modal loadOffers error path so an offer API/XHR failure never auto-invokes cancellation; it should show an inline offer-load error and keep Keep Subscription / No thanks controls. Re-enable retention_offers_enabled and clean stale #683 cancellation meta/action for fixture. Verify REST returns discount offer for too_expensive, build arraysubs, browser-test cancellation reason flow with XHR repair, accept discount, verify active status, discounted amount, notes/logs; restore fixture if needed.

[[2026-05-21]] Thu 22:57
Fixed retention offer flow. Code: loadOffers error no longer calls skipToCancel; modal now keeps user in retention flow and shows inline load error if offer fetch fails. Default discount copy now matches QA expected title/description and supports {percent}/{cycles}. Build: arraysubs completed. Browser: #683 Too expensive -> Before You Go modal showed Stay and Save card, no cancellation error; Accept Offer reloaded active subscription with 3.99 discounted from 9.99 for next 3 renewals. DB/log: status arraysubs-active; retention discount meta present; notes #881/#882; retention logs #19 offer_shown and #20 offer_accepted.
