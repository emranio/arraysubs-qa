# Box and bundle billing-edit QA

Progress task: #5. Scheduled/executed day: 2026-10-24, Asia/Dhaka.
Plan: `qa/subscription-plans-2026-10-24/plan.md`.
Route: `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans`.
Browser/user: real Chromium through isolated agent-browser `plans-list-final`; freshly reloaded current feature build; desktop content viewport 1280 x 577; WordPress user ID1, login admin, administrator, email N/A.
Subscription/order/customer IDs: N/A. Existing subscriptions were not edited.

Result: Both compound edit/save/reopen flows passed. One low-priority edit-mode wording issue was logged as qa/issues #99 and handed to root for correction/retest.

## Box #20072

Initial prefill: draft `Plans QA 2026-10-24 Box`, original description intact, Fixed, Month, interval 1, length 0, keep signup fees off. One step `Choose your monthly item`, one required Product element labelled `Required monthly item`, child product #5891, maximum quantity 1. No discount/freebies; custom renewal sync off.

Nested cancel check: changed interval to 2, clicked Cancel, and received `Discard Box Changes?`. Keep Editing retained interval 2. Cancel followed by Discard Changes returned to the outer wizard. Reopening Configure box restored interval 1 and the original contents. No unsaved configuration leaked into persistence.

Validation check: cleared the required element label and clicked Continue to Discounts & Freebies. The builder remained on Box Steps and displayed `Field 1 in "Choose your monthly item" needs a label.` The selected product, step, required flag and other inputs remained present. Restored the exact original label.

Successful edit: changed subscription length from 0 to 6 while retaining monthly interval 1. Continued through Discounts & Freebies and Flexible Renewal Sync, saved the nested configuration, completed the outer wizard and confirmed Save billings. The modal showed `Subscription billings saved.`

Reopen verification: a new billing-edit load and nested builder load showed Month, interval 1, length 6, original step/label/required child #5891/quantity 1 intact. The product remained Draft in the plan list. The saved configuration did not introduce a simple fixed catalog price; the list continued showing selection-based box pricing.

## Pro bundle #20074

Initial prefill: draft `Plans QA 2026-10-24 Bundle`, original description intact, Fixed, Month, interval 1, length 0, keep signup fees off. Children #5891 and #5893, quantity 1 each, unit prices $5 and $10, subtotal $15. Percentage discount 10%, discount $1.50, recurring total $13.50. Custom renewal sync off.

Invalid schedule check: changed interval to 2. The builder displayed `Products No Longer Fit`, naming both children and offering Keep Them or Remove Products. Keep Them preserved the two rows, marked them unavailable on the changed cycle and excluded them from the subtotal. Continue to Discount then blocked progression with `Some products no longer fit this billing cycle. Remove them, or set the schedule back.`

Nested cancel check: Cancel opened `Discard Bundle Changes?`. Discard Changes returned to the outer wizard with both original children and the $15 / $1.50 / $13.50 pricing summary. Reopening the builder restored interval 1 and length 0.

Successful edit: changed subscription length from 0 to 4 while retaining monthly interval 1 and all children/quantities. The Discount screen retained the original 10%; Flexible Renewal Sync remained off. Saved the nested configuration and confirmed the outer Save billings action. The modal showed `Subscription billings saved.`

Reopen verification: a new billing-edit and builder load showed monthly interval 1, length 4, child #5891 and #5893 quantity 1 each. Reopened Discount showed the persisted 10%, $15 subtotal, $1.50 discount and $13.50 recurring total. Reopened renewal sync remained off. The list still labelled the product Draft and displayed the derived discounted bundle price.

## Evidence and issue

- `compound-01-box-unsaved-interval.jpg`: edited nested box interval before cancellation.
- `compound-02-box-invalid-label.jpg`: empty required label with retained child/configuration; exact error also observed in the DOM.
- `compound-03-edit-review-create-label.jpg`: issue #99, edit-mode final heading incorrectly says Review & create.
- `compound-04-box-saved.jpg`: box billing save confirmation.
- `compound-05-box-reopened-length6.jpg` and `compound-box-reopened.txt`: persisted box length and original configuration.
- `compound-06-bundle-prefill.jpg`: original bundle configuration.
- `compound-07-bundle-incompatible-cycle.jpg`: shared incompatible-product confirmation.
- `compound-08-bundle-saved.jpg`: successful bundle save state.
- `compound-09-bundle-reopened-length4.jpg` and `compound-bundle-reopened.txt`: persisted bundle billing fields and product rows.
- `compound-network.txt`: actual browser load/save requests.
- `compound-errors.txt`: no browser JavaScript error output.
- New issue: `qa/issues/tasks/099-billing-edit-wizard-labels-the-final-step-review.md`. Root accepted the wording fix and will perform its final build/retest.

## Scope and retained state

This delegated pass tested editing the two dedicated fixtures supplied by root. It did not repeat brand-new box/bundle creation or switch Pro/modules globally. No source product or child was mutated. Fixtures #20072 and #20074 remain in place for root cleanup, with only their tested length changes (6 and 4 respectively). No product code was edited by this QA agent.
