# Stage 14 — Admin Subscription Management

**Goal:** Verify that the ArraySubs admin can browse, search, filter, create, edit, and audit subscriptions through the four core admin screens — All Subscriptions list, Create Subscription form, Edit Subscription form, and the Subscription Detail dashboard. Confirm every conditional detail card renders correctly when its data is present, the notes panel mixes manual and system entries chronologically, the related orders/refunds tab is accurate, CSV and JSON exports are correct, and that status changes via dropdown vs. the Cancel Subscription button trigger the documented side effects (and reveal the audit-trail gap).

**Prerequisites:**
- Stages 00–10 complete: plugins activated, settings configured, products published, cart rules in place, checkout flow validated, initial lifecycle subscriptions created, customer portal verified, retention configured, member access live, and the profile builder configured.
- Several subscriptions already exist in different statuses (Active, Trial, On Hold, Pending, Cancelled, Expired) — these are produced by earlier stages and are the data backbone for the analytics stage that follows.
- At least one subscription has a coupon applied, one has a scheduled (end-of-period) cancellation pending, one is on an automatic gateway with a card on file (Pro), one uses a Checkout Builder field (Pro), and one product requires shipping (Pro).
- An Administrator and a Shop Manager test account exist.

**Run order:**
1. [01 — All subscriptions list and filters](01-all-subscriptions-list-and-filters.md) — Columns, status tabs, search, badge colours.
2. [02 — Create subscription from admin](02-create-subscription-from-admin.md) — Add New form, every field, save, verify pending state.
3. [03 — Edit subscription fields](03-edit-subscription-fields.md) — Editable fields only, status change dropdown, confirmation modals.
4. [04 — Subscription detail cards](04-subscription-detail-cards.md) — Cancellation, Skip & Pause, Coupon, Payment Gateway *(Pro)*, Checkout Builder *(Pro)*, Shipping *(Pro)*.
5. [05 — Subscription notes](05-subscription-notes.md) — Manual admin note interleaved with system notes.
6. [06 — Related orders and refunds](06-related-orders-and-refunds.md) — Order History card, refund sub-rows, Total Refunded badge.
7. [07 — Export subscriptions](07-export-subscriptions.md) — CSV with Active filter, then JSON via REST.
8. [08 — Status change side effects](08-status-change-side-effects.md) — Dropdown vs. Cancel button, audit-trail gap documented.

**Exit criteria:**
- The All Subscriptions list shows the documented columns, badge colours, status tabs, search, and Add New / Export CSV buttons in the header.
- A subscription created from Add New saves with all chosen values and lands as Pending.
- The Edit screen exposes only invoice email, billing address, shipping address, and the inline status change control.
- All conditional detail cards (Cancellation, Coupon, Payment Gateway, Checkout Builder fields, Subscription Shipping) render the documented fields when their data exists.
- A manual admin note appears at the top of the notes panel and is interleaved chronologically with system notes such as "Subscription created" and "Renewal invoice generated".
- The Related Orders card lists Initial and Renewal orders with refund sub-rows, and the cumulative Total Refunded badge matches.
- CSV export with the Active filter downloads only active rows; JSON export via the REST endpoint returns the same shape.
- The audit-trail gap between status-dropdown cancellation and the Cancel Subscription button (no reason captured by the dropdown) is reproduced and documented in task 08.
