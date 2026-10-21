---
id: 68
title: Mollie mandate rebinder listens to a WooCommerce action that never fires
status: closed
priority: low
created: 2026-10-19T23:53:16.144609925+02:00
updated: 2026-10-20T09:10:57.343694614+02:00
started: 2026-10-20T09:06:09.045932929+02:00
completed: 2026-10-20T09:10:57.343689294+02:00
tags:
    - mollie
    - payment-method
    - dead-code
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: N/A (found during the #56-#64 fix work, 2026-10-19, while removing the same dead Stripe listener). Fix log qa/stripe-issues-56-64-fix-log.md.
Affected subscription IDs and order IDs: N/A.
Affected WordPress user/customer: N/A.
Exact route: code only.
Reproduction: arraysubs/src/Features/AutomaticPayments/Services/MollieMandateRebinder.php:49 hooks `woocommerce_payment_token_added`. WooCommerce core never fires that action (grep of woocommerce/includes and woocommerce/src finds only `woocommerce_payment_token_added_to_order`, abstract-wc-order.php:1828). The Stripe delegate's listener on the same action was removed on 2026-10-19 for this reason.
Expected: the rebinder runs when a Mollie customer saves a new payment method, or the dead listener is removed.
Actual: onPaymentTokenAdded() never runs.
Proof: grep results above.
Scope notes: Mollie was not tested in this run; check what the rebinder is meant to cover before changing it.

Closed 2026-10-20 (UTC) after fix + probe verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md; investigation qa/fix-65-69/evidence/inv-68-mollie-rebinder.md.

Fix 2026-10-20 (uncommitted): Mollie for WooCommerce saves no WooCommerce payment tokens and cannot add a payment method from My Account, so no WooCommerce event can signal a Mollie card change. Removed both dead listeners (`woocommerce_payment_token_added`, `woocommerce_add_payment_method_success`), the handlers only they reached (`onPaymentTokenAdded`, `onAddPaymentMethodSuccess`, `rebindForCustomer`, `getMollieSubscriptionIds`) and the never-fired `arraysubs_gateway_rebind_mollie_mandate` action. Mollie now declares `payment_method_update` = false, so the portal no longer shows an Update payment method link that led to a page where no Mollie method can be added (display `can_update` false; update mechanism refuses with the reason). Remaining rebinder fixes: a successful rebind (new or unchanged valid mandate) sets `_gateway_status` back to `active` when it was `errored`/`needs_payment_method` (never for `inactive`); revoke without a Mollie API key no longer reads an undefined `$failure`; the no-client rebind result carries `mandate_id`. Files: core `Services/MollieMandateRebinder.php`, `Gateways/Mollie/MollieDelegate.php`. Docs: user-manual `customer-portal/payment-and-shipping.md`, `automatic-payments/mollie.md`, `developers/actions-and-filters.md`.
Verified 2026-10-20 by probe `qa/fix-65-69/scripts/fix68-probe.php` → `qa/fix-65-69/evidence/fix68-probe.json`: no callbacks left on the two WooCommerce hooks; Mollie `payment_method_update` false; revoke/rebind without a key return clean failures with no PHP warning; with the QA suite's fake Mollie client a temporary draft subscription (69712, deleted with its notes, no scheduled actions) was rebound to a new valid mandate and moved errored → active, an unchanged valid mandate also restored active, an inactive one stayed inactive. Browser verification is not possible on this staging site: the Mollie plugin is not installed and no Mollie gateway exists.
