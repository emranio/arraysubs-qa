---
id: 69
title: Checkout summary Today's Charge ignores initial-payment-only coupons and tax
status: closed
priority: high
created: 2026-10-20T08:33:56.992003361+02:00
updated: 2026-10-21T01:33:16.69875087+02:00
started: 2026-10-20T09:30:42.658806313+02:00
completed: 2026-10-21T01:33:16.698749307+02:00
tags:
    - checkout
    - coupons
    - tax
    - summary
    - customer-report
class: standard
---

Active QA task ID and scheduled day: N/A (customer report by email, received 2026-10-20; no active QA plan). Fix log: qa/issues-65-69-fix-log.md.
Affected subscription IDs and order IDs: N/A (storefront cart/checkout display before an order exists).
Affected WordPress user/customer: N/A (any shopper; reported from a customer's store).
Exact route: /cart/ and /checkout/ with a subscription product and an ArraySubs coupon limited to the initial payment.
Reporter environment: WooCommerce + ArraySubs; product EUR 209.00 excluding tax; VAT 21%; 10% coupon set in ArraySubs to apply to the initial payment only; no trial, no signup fee.
Reproduction (from the report):
1. Add the EUR 209.00 (excl. tax) subscription product to the cart.
2. Apply the 10% initial-payment-only coupon.
3. Open checkout and read the order and subscription summary.
Expected: "Today's Charge" equals the amount due today (EUR 227.60 = (209.00 - 20.90) x 1.21), including the initial-payment discount and tax. The recurring line stays EUR 209.00 per year (coupon applies to the first payment only).
Actual (reported): Subtotal 209.00, Coupon -20.90, VAT 39.50, Total 227.60, Recurring 209.00 every year, Today's Charge 209.00, Next Charge = normal recurring amount. The charged amount is correct; only the summary line is wrong.
Proof: reporter's code reading: SubscriptionProducts/Services/Hooks.php getSubscriptionTodayChargeSummary() uses $data['price'] * $quantity when no renewal-sync context applies (getSubscriptionCartItemData(), ~line 1477); SubscriptionCheckout/Services/Traits/CheckoutDisplayTrait.php displaySubscriptionSummaryInTable() falls back to $recurring_total for $first_payment_recurring. Both use the catalogue price, not the discounted, taxed cart line.
Scope notes: to be confirmed on staging: classic + block checkout, cart page, tax-inclusive vs exclusive display, coupons that also apply to renewals, trials/signup fees, sync proration, quantity > 1, multiple subscription lines.

[[2026-10-21]] Wed 01:33

Closed 2026-10-20 (UTC) after fix + live browser verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md; investigation qa/fix-65-69/evidence/inv-69-todays-charge.md.

Root cause (confirmed): three copies of the summary computed Today's charge from the product's own price (`$data['price'] × qty` or the sync amount, plus the raw signup fee) instead of the calculated cart line, so coupons, tax and fee tax were always missing; the classic checkout table duplicated the logic; a third copy was a dead view.
Fix 2026-10-20 (uncommitted): new `src/functions/cart-charge-helpers.php` splits today's cart total across the cart lines (line after coupons + tax, shares of ArraySubs-weighted fees, chosen shipping by package quantity, remainder; shares add up to the cart total) and formats renewal amounts per the cart tax display; new `SubscriptionProducts/Services/CartSummary.php` builds the Renewals/Recurring, Signup fee, Today's charge and Next charge rows for the cart item details (classic + block cart, checkout, mini-cart, Pro order-email snapshot) and the classic checkout table (rows named by product when there are several subscriptions). Today's charge shows "(incl. VAT)" when the cart lists tax separately. Signup fees are marked (`arraysubs_charge_kind=signup_fee`, core signup + Box; Pro Bundle) so the "first + signup = total" breakdown adds up. The plan-switch "Due today" item row and table heading were dropped (Today's charge carries it). Dead view `checkout-subscription-summary.php` deleted.
Verified live 2026-10-20: no tax (guest, 2571 + `qa-audit-coupon`): block checkout, classic checkout table (Recurring $20.00, Today's Charge $18.00), block cart and classic cart all $18.00 = Total (was $20.00); order email snapshot "Today's charge: $18.00" (order 69818). Tax window (21% NL VAT, prices excl. tax, restored after): reporter's case on fixture 69815 (209.00/year + 10% initial-only coupon) → Subtotal $209.00, Coupon −$20.90, VAT $39.50, Total $227.60, Recurring $209.00 every 1 year, **Today's Charge $227.60 (incl. VAT)** on classic and block checkout; display incl → Recurring $252.89 (incl. VAT), Today's Charge $227.60; 206 + `welcome15` → $26.60 (incl. VAT) after review round 2 (first shown as "$20.56 + $6.04 signup = $26.60 (incl. VAT)"). In-memory simulation of 10 more cases (two lines, mixed cart, sync prorate, trial+fee, qty 2, prices incl.) all add up to the cart total.
Review rounds 1-3 (uncommitted, memory-only cart probes; details in the fix log): line shares rounded like WooCommerce (L6); no totals calculation from inside a running one (L7); the plan + signup breakdown only when those two make up the whole amount, otherwise the total alone (shipping, other fees, store credit, signup fee with tax listed separately); so the 206 + `welcome15` case now reads "$26.60 (incl. VAT)" instead of the breakdown above (R2-8/R2-9). A discount larger than its line leaves the line at "Free today" and the rest comes off the other lines the same discount covers (R2-10/R3-6). Checkout-migration (plan switch) lines show no trial or renewal-sync wording and the switch's next renewal date (R3-7); tolerance is half a minor unit (R3-10). The reporter's case still shows $227.60 (incl. VAT). Filed separately: #72 (order-side attribution of a negative share).
Review round 4 (memory-only cart probes): a fully absorbed line is no longer re-clamped by a float residue, so the Today's charge rows add up to the order total in every case (R4-5); a plan switch at checkout to a plan with a free trial shows no trial wording in the cart's price, subtotal and checkout quantity cells or the block cart, and no First billing cycle note (R4-6/R4-7). Filed separately: #73 (per-cart member discount counts a free-trial line at its regular price), #76 (migration cart can skip payment).
Review round 5: the signup fee in the cart price/subtotal cells, mini-cart and checkout quantity cell follows the cart's tax display like the order summary (N8). No regressions in the round-4 cart changes (116 real-cart cases, 60,000 fuzzed carts).

Review rounds 6-8: the mini-cart signup fee row follows the tax display and the classic mini-cart is priced before it renders (R6-C7/C8); member pricing in the cart checked live (#74).
