---
id: 7
title: 'stage-5: Stripe renewal reconciliation can replace an unsettled PaymentIntent'
status: closed
priority: high
created: 2026-08-15T14:08:45.141031+02:00
updated: 2026-08-15T18:10:46.396107526+02:00
started: 2026-08-15T14:09:47.341467871+02:00
completed: 2026-08-15T18:10:46.396106665+02:00
tags:
    - stage-05
    - stripe
    - renewal
    - return-url
    - idempotency
    - reconciliation
    - payment-migration
class: standard
---

QA source:
- Progress task: qa/progress task #3, stage-5.
- QA plan: qa/stages/05-checkout/11-stripe-test-card-flow.md.
- Found during the post-fix read-only audit of every Stripe PaymentIntent confirmation and reconciliation path for issue #4.

Affected records:
- No existing customer record was mutated to discover this issue: subscription/order/user IDs are N/A for the code-audit counterexamples.
- The proven exact-intent browser fixture remains subscription #27614 with renewal orders #27628/#27663/#27691; those records demonstrate the safe card/3DS path but do not manufacture the >50-intent or `requires_capture` edge state.

Route and browser context:
- Automatic renewal worker plus the signed order-pay route at https://mirror-help.arrayhash.com/checkout/order-pay/{renewal-order}/.
- Browser context: authenticated disposable Stripe customer; provider verification uses the Stripe sandbox account.

Reproduction:
1. Inspect the direct automatic-renewal PaymentIntent create-and-confirm request using stored `_payment_method_type`: a malformed or redirect-capable legacy type can reach confirmation without `return_url`.
2. Reconcile a bound PaymentIntent whose status is `requires_capture` or an unrecognized non-terminal state: the broad fallthrough clears the exact attempt and permits a replacement intent.
3. Reconcile a lost-response intent for a Stripe customer with more than 50 recent PaymentIntents where the exact metadata match is on a later page: the first-page no-match is incorrectly treated as conclusive and permits a replacement.

Expected:
- Every confirmation that might redirect includes a validated non-empty return URL; stored payment-method type is provider-validated or rejected safely.
- `requires_capture`, processing/customer-action, and unknown provider states remain bound and block replacement.
- A truncated Stripe list result is never conclusive; pagination reaches the time cutoff or the result remains pending.

Actual:
- The direct create-and-confirm request omits `return_url`.
- `resolveExistingIntent()` has a broad fallthrough that closes `requires_capture` and unknown states.
- `verifyRecentChargeForOrder()` reads only the first 50 PaymentIntents, ignores `has_more`, and returns a conclusive no-match.

Proof:
- Core: `src/Features/AutomaticPayments/Gateways/Stripe/StripeDelegate.php`, automatic create/confirm, existing-intent resolution, and recent-intent reconciliation.
- Pro update-order fallback contains the same three paths and must remain aligned.
- Exact signed on-session confirmation already passes: it validates a non-empty return URL, confirms one bound PI with deterministic idempotency, and re-reads the same PI.

Known scope and counterexamples:
- Current card-only renewal fixtures succeeded and did not duplicate charge; this issue is a fail-safe gap for legacy/malformed method types, capture states, and high-volume Stripe customers.
- No production/control subscription is to be mutated to synthesize these edge cases.
- The correction must preserve one exact intent, fail closed on ambiguous provider state, and remain aligned in core and the Pro update-order fallback.

Fix plan (2026-08-15):
- Require a validated renewal return URL on direct create-and-confirm and validate the remote PaymentMethod/customer/type before confirmation; reject redirect-incompatible/off-session-unsafe legacy state without creating an intent.
- Replace broad intent-status fallthrough with an explicit terminal-state decision. Keep `requires_capture`, processing, customer-action, confirmation, and unknown states bound/pending.
- Page Stripe PaymentIntent reconciliation through the bounded cutoff; any truncated/error page is inconclusive, never permission to create another intent.
- Exercise focused provider-stub regressions for legacy redirect type, `requires_capture`, unknown status, and a match after page 1, then rerun the real card/3DS renewal browser flow.

Adversarial compatibility/concurrency review and amended plan:
- Reconciliation must use the immutable customer and timestamp persisted on the renewal order's charge attempt, never the subscription's current Stripe customer or an arbitrary seven-day window. A referenced terminal PI is permission to replace only after exhaustive metadata/customer/order/subscription/site/amount/currency pagination proves no sibling attempt succeeded.
- Legacy reusable `src_*` credentials must use Stripe's official `source` request parameter in both direct renewal creation and exact customer-action confirmation; legacy `stripe_sepa` tokens map to provider type `sepa_debit`.
- Stored-intent resolution, terminal cleanup, and any replacement create/confirm must share one per-order charge mutex and refresh the WC order after acquiring it. Otherwise a slow worker can delete a newer worker's PI and create another charge. The lock lease must cover the bounded pagination worst case, not the old single-request timeout.
- A retiring-intent marker left by an interrupted immediate cancellation must be exact-value checked and cleared only while retiring that same terminal PI. Core-first/older-Pro rolling updates use a hook-free core Stripe cancellation delegate so the legacy provider remains the sole payment hook owner while immediate cancellation remains available.
- Genuine declined terminal-zero PIs are cancellable only after exact local/provider binding and authoritative zero received/capturable proof; the same PI must be re-read as `canceled` before local cancellation proceeds.


Resolution verification — final:
- Core and Pro fallback paths now validate the authoritative remote payment method/customer/type, supply a non-empty validated return URL on confirmation, and preserve the exact canonical attempt.
- Reconciliation uses explicit terminal decisions. requires_capture, processing/customer-action states, and unknown states stay bound and fail closed rather than permitting a replacement.
- Recent-intent reconciliation is bounded and paginated; truncated or failed provider reads are inconclusive. Attempt customer/timestamp/amount/currency/order/subscription/site bindings are exact.
- Stored-intent resolution, retirement, cancellation, and replacement are serialized by the per-order charge lock with exact-value markers. A fresh provider re-read is required before customer-action confirmation.
- The real core-only renewal was processed repeatedly before customer action without replacing its order or PaymentIntent. The normal order-pay link completed 3DS on that exact intent and produced exactly one paid/captured charge.
- No post-fix missing-return_url Stripe log entry was created; the remaining count is exactly two historical entries from the pre-fix order.
- Pro was reactivated after core-only testing. Runtime inspection showed one core Stripe/Paddle registration and one callback owner for gateway, Blocks, renewal, and webhook paths; the Pro provider remains dormant without duplicate UI or handlers.
- Browser proof: qa/artifacts/payment-bug-fixes-20260815/core-only-final/stripe-renewal-ordinary-pay-link.png, stripe-renewal-ordinary-pay-3ds-ready.png, stripe-renewal-ordinary-pay-after-complete-click.png, and qa/artifacts/payment-bug-fixes-20260815/both-active-checkout-single-gateways.png.
- Closed after live browser/provider verification and cross-plugin runtime inspection.
