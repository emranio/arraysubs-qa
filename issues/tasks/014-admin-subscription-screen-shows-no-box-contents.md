---
id: 14
title: Admin subscription screen shows no box contents and no bundle contents / child-subscription link
status: open
priority: medium
created: 2026-08-26T14:38:33.693345565+02:00
updated: 2026-08-26T14:38:33.693345565+02:00
tags:
    - admin
    - subscription-detail
    - subscription-box
    - subscription-bundle
    - fulfilment
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s):** 33278 (Subscription Box), 33298 (Bundle parent), 33302 (Bundle child)
**Affected order ID(s):** 33277 (box), 33297 (bundle)
**Affected user(s):** WP user 1 (admin) is both the merchant and the test customer here.

**Test URLs:**
- https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/33278
- https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/33298

## Steps
1. Buy a Subscription Box with children, a freebie, a text input and an upload (order 33277 → subscription 33278).
2. Buy a Subscription Bundle containing a subscription child (order 33297 → subscriptions 33298 + 33302).
3. Open each subscription in the ArraySubs admin.

## Expected
The merchant can see what has to be fulfilled: box contents, freebie, discount, customer inputs and the uploaded file; and for a bundle, the contents plus a link to the child subscription.

## Actual
Both pages render only: Subscription, Skip & Pause, Vacation Mode, Customer Information, Billing Information, Payment Gateway, Billing/Shipping Address, Subscription Shipping, Order History, Payment Timeline and Subscription Notes.

Box 33278 — a regex over the whole rendered page for `Box contents|Gift message|Logo file` returns **false**, even though the data is stored on the subscription:
```
33278,_arraysubs_box_contents,{"children":[{"product_id":12591,"qty":2,...}],"freebies":[...]}
```
Bundle 33298 — no `Bundle contents` block and no link to #33302, even though:
```
33298,_arraysubs_bundle_contents,{...}
33298,_arraysubs_bundle_child_subscriptions,[33302]
```

## Scope notes / counterexamples
- The **customer portal** renders all of it correctly for both (`/my-account/view-subscription/33278/` and `/33302/`).
- The **WooCommerce order** screen also has the data (child line items plus `_arraysubs_box_contents` on the parent line).
Only the ArraySubs admin subscription detail is missing it.
