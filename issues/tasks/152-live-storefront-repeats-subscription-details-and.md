---
id: 152
title: Live storefront repeats subscription details and puts trial explanations in amount columns
status: open
priority: medium
created: 2026-10-31T02:18:43.073161+06:00
updated: 2026-10-31T02:18:43.073161+06:00
tags:
    - release-qa
    - storefront
    - copy
    - trial
    - checkout
class: standard
---

# Commerce findings and gaps

## C1 — Verbose Early Renew and cancellation panel copy persists on live subscription

- Severity: low/nonblocking. Active QA task: progress#3, scheduled2026-10-31, plan qa/release-2026-10-31-0208/plan.md.
- Affected subscription109453; initial order109452; product109433.
- Affected customer: newly auto-created customer rqa0208-a@example.invalid, role customer; numericID3252 (admin subscription row).
- Exact URL: https://mirror-help.arrayhash.com/my-account/as-view-subscription/109453/ . Browser session rqa0208-commerce-customer-a, ProfileA, both plugins active.
- Reproduction: complete owned7.31/month virtual Stripe sandbox purchase; open linked subscription and inspect Renewals and Manage Your Subscription panels.
- Expected: short subscription facts once; only Renew Early button outside its confirmation, no reassurance paragraphs (Clean Storefront Copy and current unreleased draft).
- Actual: Renewals contains a three-line paragraph explaining the next/current billing dates, and cancellation box contains a reassurance line before Cancel Subscription.
- Proof: evidence/commerce-subscription.png visually inspected; evidence/commerce-subscription.txt records actual paragraph and renewal dates. Repro video N/A (visible-on-load copy).
- Scope: nonblocking presentation on standard paid recurring subscription; not evidence of incorrect billing. Deployed versions confirmed by coordinator2.1.0/1.3.0, deployment/source alignment unverified, so may indicate stale deployed assets/template. No change to existing customer/state required.

## C2 — Trial cart total column contains trial explanation and repeated details

- Severity: medium/nonblocking copy regression. Active QA task progress#3, scheduled2026-10-31, plan qa/release-2026-10-31-0208/plan.md.
- Product109466 RQA0208 Trial Shipping; subscription/order IDs N/A (cart before trial purchase). Existing paid sub109453/order109452 unrelated to line under test.
- Customer3252 rqa0208-a@example.invalid, role customer. Browserrqa0208-commerce-customer-a ProfileB.
- Exact URL https://mirror-help.arrayhash.com/cart/ . Both plugins active.
- Steps: configure owned product7.31/month,7day trial,2signup fee,nonvirtual recurring shipping3initial/1renewal; save+reload; Subscribe Now; open Cart.
- Expected: Total column amount only, trial/shipping/signup facts each once left; short label/value rows.
- Actual: line Total reads "$0.00 today (free for7days)"; left Today's charge contains full equation "$2.00 signup fee +$3.00 shipping and other charges =$5.00" alongside separate Signup fee and Shipping rows. Totals initial shipping label remains "ArraySubs initial shipping:...".
- Proof evidence/commerce-trial-cart.png visually inspected and commerce-trial-cart.txt. No raw payment entered for this fixture. Repro videoN/A visible-on-load presentation.
- Scope: paid nontrial line had numeric-only Total; trial+shipping contrast exposes extra total-column copy. Billing arithmetic5.00 is correct. Deployment alignment unverified; matches older presentation despite current draft promising cleaner copy.

### C2 additional classic checkout proof

Exact URL https://mirror-help.arrayhash.com/classic-checkout-qa/; same user3252/product109466/ProfileB, prior to submission. Screenshot commerce-classic-trial-checkout.png and snapshot commerce-classic-trial-checkout.txt. Product line repeats Every month in its name and quantity, another schedule/trial/fee blurb, then detail rows; Subtotal cell is entirely explanatory text "Free for7days Then$7.31 every1month+$2.00 signup fee due today". Five summary rows after Total repeat After Trial,Signup Fee,Free Trial,Today's Charge,First Charge already under product. Shipping note below Shipment repeats shipping row. This confirms Clean Storefront Copy violations in both classic and block checkout/cart.


Coordinator scope: all observed live template behavior; deployment alignment blocker#151 applies. Correct arithmetic does not excuse number-column and duplicate-copy violations.
