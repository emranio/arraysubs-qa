# Commerce findings

All findings belong to release QA task 3 (C4 also task 5), scheduled 2026-10-31, plan `qa/release-2026-10-31-0208/plan.md`. Both ArraySubs 2.1.0 and ArraySubsPro 1.3.0 were active on the live mirror. Deployment alignment with this workspace was not established. No product code was changed.

## C1 — Verbose renewal and cancellation copy

- Severity: low, nonblocking presentation regression.
- Affected records: subscription 109453, initial order 109452, product 109433; customer 3252, rqa0208-a@example.invalid, role customer.
- Exact URL/context: https://mirror-help.arrayhash.com/my-account/as-view-subscription/109453/ ; isolated customer session rqa0208-commerce-customer-a, standard paid profile A.
- Steps: complete the owned $7.31/month virtual Stripe TEST purchase; open its subscription; inspect Renewals and Manage Your Subscription.
- Expected: short facts once and action buttons, following the Clean Storefront Copy rule and the current unreleased draft.
- Actual: the Renewals panel contains a paragraph explaining current/next billing dates; cancellation includes a reassurance sentence before the button.
- Proof: visually inspected `evidence/commerce-subscription.png` and snapshot `commerce-subscription.txt`. Video N/A: static copy visible on load.
- Scope/counterexample: payment and dates were correct. This is presentation, with an unverified source/deployment alignment caveat.

## C2 — Trial cart and classic checkout repeat subscription details

- Severity: medium, nonblocking copy regression.
- Affected records: product 109466; cart-stage subscription/order IDs N/A. Subsequent successful purchase produced order 109474 and subscription 109475.
- Customer/context: 3252, rqa0208-a@example.invalid, role customer; isolated customer session, profile B with a seven-day trial, $2 signup fee, $3 initial/$1 recurring shipping, nonvirtual $7.31 monthly product.
- Exact URLs: https://mirror-help.arrayhash.com/cart/ and https://mirror-help.arrayhash.com/classic-checkout-qa/ .
- Steps: save/reload the above owned product; Subscribe; open block cart, then classic checkout; inspect the product line, amount columns, and totals.
- Expected: amount columns contain numbers only; each trial/fee/shipping/schedule fact appears once as a short label/value row under the product.
- Actual: block cart Total says “$0.00 today (free for 7 days)”. The left side repeats signup/shipping in an equation as well as separate rows. Classic checkout repeats the schedule in the product/quantity area, uses explanatory trial/fee text in the Subtotal cell, and adds five summary rows after Total that repeat product facts. A shipping note repeats the shipping row.
- Proof: visually inspected `evidence/commerce-trial-cart.png` and `commerce-classic-trial-checkout.png`, with matching snapshots. Video N/A: static presentation.
- Scope/counterexample: the paid nontrial cart had a numeric Total. Trial arithmetic was correct: $2 fee + $3 shipping = $5; the single Stripe TEST purchase succeeded. No billing defect claimed. Deployment alignment remains unverified. A missing cart CTA was seen once but not independently reproduced, so it is not a confirmed defect.

## C3 — Existing issue #8 partially reconfirmed in Grouped editor

- Existing issue: `qa/issues/tasks/008-subscription-meta-is-saved-onto-grouped-and.md`. Preserve the prior deliberate no-fix decision and issue status.
- Affected records: owned product 109521, RQA0208 Grouped Meta Draft. Subscription/order/customer N/A; actor WordPress user 1 (admin, administrator), isolated admin session.
- Exact URL: https://mirror-help.arrayhash.com/wp-admin/post.php?post=109521&action=edit .
- Steps: duplicate owned simple subscription product 109433 to a draft; select Grouped product; Save Draft; inspect the server-reloaded product type and `_is_subscription` control; switch to Simple without saving.
- Expected: an unsupported Grouped product does not retain the subscription flag.
- Actual: Grouped type persisted after reload, while DOM state was `{type: grouped, isSubscription: true}`. Switching back to Simple revealed checked Subscription [AS]. The unsaved Simple switch was discarded.
- Proof: visually inspected `evidence/commerce-grouped-reloaded.png/.txt` and `commerce-grouped-switched-simple.png`.
- Scope/counterexample: the Plans list correctly excluded the Grouped draft (`commerce-grouped-plan-row.png`). No DB/meta/helper assertion or storefront purchase was verified; External/Affiliate mode was not retested.

## C4 — Required-reason Pause cannot be completed by the customer

- Severity: high functional defect; coordinator recorded issue #153 as a release blocker.
- Affected records: subscription 109453; existing initial order 109452 and early renewal 109485. Failed Pause attempts made no financial change.
- Customer/context: 3252, rqa0208-a@example.invalid, role customer; isolated customer session. Coordinator admin 1 enabled Pause, customer pause/resume, max 30 days, max two pauses, cooldown zero, required reason true; Skip remained off.
- Exact URL: https://mirror-help.arrayhash.com/my-account/as-view-subscription/109453/ .
- Steps: open Pause; cancel the days prompt and verify unchanged Active state; reopen, enter one day, Continue; inspect confirmation and accept once. Repeat once within 90 seconds.
- Expected: a reason field with local required validation, then a successful pause after valid input.
- Actual: the days prompt goes directly to confirmation with no reason field. Accept shows a visible disabled Processing state, then “A pause reason is required.” The customer has no control that can satisfy the backend requirement. Cancel leaves Active state and the next-payment date unchanged.
- Proof: visually inspected `evidence/commerce-pause-confirm-no-reason.png`, `commerce-pause-error.png/.txt`, `commerce-pause-repro-error.png`, and video `commerce-pause-repro.webm`. Coordinator `evidence/pause-enabled.txt` confirms the required policy.
- Scope/counterexample: with Require Reason false, the same one-day Pause succeeded, customer/admin showed Paused, and Resume returned Active with dates adjusted by actual elapsed pause. Baseline Pause off hid the button. Backend validation is working; the customer UI cannot supply its required input. No request remains pending.

## Observations that were not product bugs

- Stripe card fields initialized slowly and were absent from the accessibility snapshot, but rendered visually and accepted the documented TEST card. All four intended sandbox orders settled once; persistent blank-card issue #4 was not reproduced.
- Empty-string CLI fill initially failed to clear billing company. Explicit End/Backspace clearing was verified empty before Save, and a fresh reopened form proved restoration (`commerce-company-restored.png`). The renewal order correctly retains its historical billing snapshot.
