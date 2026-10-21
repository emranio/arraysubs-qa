---
id: 76
title: Checkout migration to a plan with a free trial can make needs_payment() false while the cart total is above zero
status: closed
priority: medium
created: 2026-10-20T16:24:13.583206663+02:00
updated: 2026-10-21T02:09:43.097744476+02:00
started: 2026-10-20T19:27:41.628873206+02:00
completed: 2026-10-21T02:09:43.097742253+02:00
tags:
    - checkout
    - plan-switching
    - migration
    - trials
    - revenue-leak
    - mollie
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 4 of the #65–#70 fixes; pre-existing, out of their scope). Fix log: qa/issues-65-69-fix-log.md (R4-12).
Affected subscription IDs and order IDs: subscription 27494 (customer 468, Basic Monthly) used read-only in a memory-only probe; no cart saved, no order created.
Affected WordPress user/customer: customer 468 (probe context only), role customer.
Exact route: checkout with one subscription per customer + auto-migrate at checkout + Plan Switching upgrades on, and ArraySubs → Settings → General → Checkout & Trials → Require Payment Method for Trials (`trials.require_payment_method`) off. `TrialCheckoutTrait::isTrialOnlyCart()` uses the raw `trial_length > 0` check, while the line is a checkout migration that grants no trial (CartSummary::hasTrial() is false; the line is priced at the plan-change amount by CheckoutMigrationTrait at priority 30).
Reproduction (real code, memory-only probe, settings set in memory):
1. Customer 468 with active sub 27494 adds Pro Monthly 495 given a 7-day trial and no signup fee.
2. Cart line #495 is the migration (upgrade) at $7.22; cart TOTAL $7.22.
Expected: WC()->cart->needs_payment() is true (a $7.22 charge).
Actual: needs_payment() = false with require payment method off, so checkout would take process_order_without_payment() for a $7.22 order. With require payment method on, or without the trial, it is true.
Proof: `qa/fix-65-69/evidence/r4-migration-needs-payment.txt` NP1 (`CartSummary::hasTrial=false`, `cart total=7.22 | WC()->cart->needs_payment()=false`), NP2/NP3 controls (true).
Scope notes: narrow config (one subscription per customer, auto-migrate and plan switching are off on staging; Require Payment Method for Trials is on). isTrialOnlyCart() also drives maybeSkipOrderPaymentForTrial() (`woocommerce_order_needs_payment`, TrialCheckoutTrait.php:64-79), so the order-level check that the Store API (block checkout) uses is affected as well. Fix direction: use `CartSummary::hasTrial($data, $cart_item)` in isTrialOnlyCart(), as the cart display now does. Adjacent, low: the trial-history and trial-quantity validation also refuse a migration upgrade for customers with trial history although the switch grants no trial. Related: #75.

[[2026-10-20]] Tue 18:45
Same family, found by review round 5 (R5-5, confirmed by an independent skeptic, memory-only probe with a simulated Mollie host): MollieTrialAvailability::cartHasTrial() (arraysubs/src/Features/AutomaticPayments/Services/MollieTrialAvailability.php:197, raw `trial_length > 0`, only caller filterTrialMethods() :143) treats a paid checkout migration to a trial plan as a trial cart, so with Mollie active it removes every Mollie method except creditcard/PayPal (iDEAL, Bancontact, SEPA DD) from a paid plan switch (K1: TOTAL 12.15, needs_payment true, only creditcard and paypal left; K4: no Mollie card/PayPal → every Mollie method removed; K3 migration without trial and K2 real trial correct). Fix it together with isTrialOnlyCart(): use CartSummary::hasTrial($data, $cart_item) and skip the get_post_meta fallback for CartSummary::isMigration($cart_item). Fixing cartHasTrial() alone is not enough: with the raw isTrialOnlyCart() a migration to a trial plan on a trial subscription with require-payment-method on gives TOTAL 0.00 but needs_payment true (Z1), and the raw Mollie filter is then what limits it to methods that can take a 0.00 first payment. Evidence: qa/fix-65-69/evidence/r5-mollie-trial-filter-migration.txt (K1–K7, Z1–Z3).

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: the trial-only cart and order needs_payment checks and Mollie's trial method filter ignore checkout-migration lines (a plan switch starts no trial); One Trial Per Customer no longer blocks a switch. Verified by probes (NP1 needs_payment true with a 7.05 total; Mollie keeps iDEAL, Bancontact and SEPA Direct Debit for a paid switch).
