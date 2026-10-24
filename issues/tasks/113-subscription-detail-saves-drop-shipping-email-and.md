---
id: 113
title: Subscription detail saves drop shipping email and phone
status: closed
priority: high
created: 2026-10-25T02:03:46.873885+06:00
updated: 2026-10-25T02:05:09.628027+06:00
started: 2026-10-25T02:04:02.55555+06:00
completed: 2026-10-25T02:05:09.628027+06:00
tags:
    - bug
    - subscriptions
class: standard
---

Active QA task ID and scheduled day: N/A. Plan markdown path: N/A; found during local subscription-detail UI verification on 2026-10-25.
Affected subscription: 8326. Affected order IDs: N/A.
Affected customer: WordPress user 224, login demo260927_220, email demo.260927.220@example.test, roles customer, demo_member, demo_premium. Acting user: admin (ID 1, administrator).

Exact URLs:
http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/edit/8326
http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/8326

Browser context: isolated agent-browser arraysubs-admin session, authenticated local WordPress administrator.

Reproduction:
1. Use a subscription whose stored shipping address includes email and phone.
2. Open Edit Subscription and change only Billing Company.
3. Save Changes and inspect the resulting Subscription Notes and shipping address.

Expected: unrelated shipping email and phone remain unchanged.
Actual: shipping email demo.260927.220@example.test and phone +1 202 555 0319 were cleared. The browser audit note explicitly records Shipping Email and Shipping Phone changing from those values to an em dash after the billing-company save. Screenshot captured at /tmp/arraysubs-addresses-main-desktop.png.

Scope/counterexample: SubscriptionMutation merges partial address updates with the existing audit snapshot, but SubscriptionAuditTrait::sanitizeShippingAddressPayload drops email and phone before persistence and audit comparison. The billing sanitizer retains both; the separate shipping-address endpoint also merges existing fields.

Fix plan:
1. Retain sanitized email and phone in the shared shipping-address payload sanitizer.
2. Restore the test subscription's original shipping contact values.
3. Save an unrelated billing-company edit through the real browser, confirm shipping contacts survive, then restore Billing Company.
4. Record verification evidence and close this issue.

Implemented: sanitizeShippingAddressPayload now retains sanitized email and phone, matching billing-address normalization.

Verification on 2026-10-25:
- Restored subscription 8326 shipping email demo.260927.220@example.test and phone +1 202 555 0319.
- Through the actual admin browser, saved Billing Company as Shipping contact check; both shipping contact fields remained unchanged.
- Through the browser, restored Billing Company to its original empty value; both shipping contact fields remained unchanged.
- The latest browser audit notes show only the intended Billing Company changes. Subscription 8326 remains active.
- No browser JavaScript errors were reported.
- No active QA plan or scheduled day applies.

Evidence:
[Before: contact-clearing audit](../evidence/113-shipping-contacts/before-audit.png)
[After: browser save audit](../evidence/113-shipping-contacts/after-audit.png)
[REST corroboration after browser save and fixture restoration](../evidence/113-shipping-contacts/after-browser-save.json)
