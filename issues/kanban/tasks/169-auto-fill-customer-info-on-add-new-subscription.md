---
id: 169
title: Auto-fill customer info on Add New Subscription form
status: closed
priority: high
created: 2026-05-29T07:23:12.836480904+02:00
updated: 2026-05-29T07:34:27.353821315+02:00
started: 2026-05-29T07:23:28.296766756+02:00
completed: 2026-05-29T07:34:27.353820594+02:00
tags:
    - bug
class: standard
---

On Add New Subscription form (admin.php?page=arraysubs-mainadmin#/subscriptions/form), selecting a customer should auto-fill their info (address, email, etc). Currently not working. Fix and verify in browser.

[[2026-05-29]] Fri 07:34
Fixed. Root cause: customer Select field had no onChange handler, so picking a customer never fetched their profile. Added GET arraysubs/v1/subscriptions/customer/{id} (SubscriptionController::getCustomerProfile) returning WC_Customer billing+shipping. Wired handleCustomerChange in SubscriptionForm.jsx to prefill invoice_email + billing/shipping fields. Also fixed response unwrap (content key, not data). Rebuilt assets. Verified in browser via Playwright spec subscription-customer-autofill.spec.js — all fields populate.
