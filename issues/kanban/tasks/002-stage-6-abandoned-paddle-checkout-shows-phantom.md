---
id: 2
title: 'stage-6: Abandoned Paddle checkout shows phantom connected billing authorization'
status: closed
priority: high
created: 2026-08-15T09:59:44.218062098+02:00
updated: 2026-08-15T11:40:21.503952776+02:00
started: 2026-08-15T11:16:17.663450696+02:00
completed: 2026-08-15T11:40:21.435781416+02:00
tags:
    - stage-06
    - paddle
    - checkout
    - gateway
    - subscription
    - payment-migration
class: standard
---

QA progress task: #137, stage 6. QA plan: qa/stages/06-initial-lifecycle/03-subscription-detail-screen.md and qa/stages/06-initial-lifecycle/04-customer-portal-display.md.

Affected records: subscription #27383; order #27381. User/customer: WordPress user #462, paddle_mig_cancel_070215, paddle_mig_cancel_070215@example.test, customer role.

Routes: admin order /wp-admin/admin.php?page=wc-orders&action=edit&id=27381; admin subscription /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/27383; customer /my-account/view-subscription/27383/.

Reproduction:
1. As customer #462, begin checkout for SLT Paddle Daily with Paddle.
2. Cancel/close the Paddle overlay before payment. The same checkout session later selected Stripe and then BACS without placing a second order.
3. Open admin subscription #27383 and the customer subscription detail.

Expected: An unpaid abandoned checkout with no Paddle customer, payment-method, subscription, transaction, brand, or last4 binding must not be presented as a connected Paddle billing authorization. If a provisional subscription remains, its gateway state and order payment method must not present contradictory authorization state.

Actual: order #27381 is Pending payment, unpaid, transactionless, and currently records bacs/Direct bank transfer. Subscription #27383 remains Pending with arraysubs_paddle and status pending but no remote binding IDs and zero scheduled actions. Admin nevertheless renders Payment Gateway CONNECTED, Card on File: Paddle (Paddle), Resync, and Detach. Customer portal renders Billing Authorization: Paddle (Paddle).

Proof: qa/artifacts/payment-migration-regression-20260815/audit/admin-subscription-27383-paddle-cancel-pending.png and qa/artifacts/payment-migration-regression-20260815/audit/customer-462-paddle-cancel-subscription-27383-clean.png. Clean customer session had no JS errors and no 4xx/5xx requests.

Additional evidence: webhook rows #1204 transaction.created and #1205 transaction.updated only; no paid/completed/subscription-created webhook. No payment/subscription email, no completed payment, and no scheduler actions. The persistent cart initially contained product #12112; loading the customer portal caused WooCommerce to remove it with the notice that it could no longer be purchased.

Known scope/counterexample: paid active Paddle order #27307/subscription #27309 for user #459 has actual Paddle customer/payment-method/subscription/transaction bindings, completed payment 1, pending invoice/process actions, and correctly displays Visa ending in 4242. Paddle mixed #27339/#27341 also works.

Implementation pointers: arraysubs/src/Features/AutomaticPayments/Services/AdminDisplayHooks.php maps any resolved automatic gateway to detachable unless detached and emits fallback payment display; arraysubs/src/resources/pages/SubscriptionDetail.jsx labels every non-detached/non-errored status Connected; arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/PaddleGateway.php returns Paddle (Paddle) when card metadata is absent.

Do not clean or mutate these records before engineering captures them.

[[2026-08-15]] Sat 10:01
Clarification from final data check: subscription #27383 does retain a provisional _gateway_session_id, but has no gateway customer, payment-method, Paddle subscription, completed transaction, method type, brand, or last4. Order #27381 was created at 13:41:44 UTC and modified at 13:51:57 UTC, while subscription #27383 was created and last modified at 13:41:45 UTC. This timing is consistent with later gateway selection updating the same Woo draft/pending order to bacs while leaving the provisional Paddle subscription unchanged; it is an inference from timestamps plus the documented test sequence, not a direct event log. _gateway_status is pending, yet the React card maps pending to Connected.

[[2026-08-15]] Sat 10:08
Engineering evidence capture is complete (admin/customer screenshots, exact metadata, webhook/action/mail state, timestamps, and implementation pointers). Root may now remove the disposable fixture during exact QA teardown; the issue remains open and its evidence does not depend on the live record remaining.

[[2026-08-15]] Sat 10:26
Final teardown note: the documented disposable users, orders, and subscriptions have now been removed after evidence capture. The issue remains open; retained reports, screenshots, webhook rows, Mailpit IDs, metadata snapshots, and implementation pointers are the reproduction evidence.

[[2026-08-15]] Sat 11:20
Engineering root cause and fix plan:
- Paddle must create a draft provider transaction and local Pending subscription before the overlay can open, so a closed overlay legitimately leaves only _gateway_session_id and _gateway_status=pending. That session is not a customer billing authorization and may still be reconciled if a late provider event arrives.
- The presentation bug is caused by treating any resolved automatic-gateway class as connected even when the canonical _gateway_payment_method_id is absent, plus Paddle formatter fallback text Paddle (Paddle). This also enables meaningless Resync/Detach controls.
- Fix plan: require a durable canonical remote payment-method/subscription binding before surfacing the automatic-gateway authorization card or customer Billing Authorization row; have Paddle formatting return empty without that binding; gate detach/sync presentation on the same evidence. Preserve the provisional session and webhook correlation. Mirror PHP behavior in the Pro fallback copy.
- Verification plan: focused provisional-vs-bound data probes, a new real abandoned Paddle overlay browser flow with admin and customer screenshots, then a paid legacy Paddle subscription counterexample proving its connected Visa display and actions remain intact. Build only if frontend source changes are necessary; skip lint/PHPCS per workspace instructions.

[[2026-08-15]] Sat 11:40
FIXED AND REAL-BROWSER VERIFIED on mirror-help.arrayhash.com.

Implementation:
- Core and Pro now require the canonical _gateway_payment_method_id before treating a selected automatic gateway as an established remote billing authorization. Provisional _gateway_session_id data is retained for safe late-webhook reconciliation.
- Admin and customer presentation, Resync/Detach eligibility, and the REST actions.can_sync_gateway contract are all gated on that durable binding.
- Paddle formatting returns no invented fallback when no binding exists.
- The shared React status renderer now maps active only to Connected and explicitly renders pending, paused, inactive, cancelled, unavailable, detached, errored, and unknown states. Production assets were rebuilt successfully without dependency installation.

Fresh staging reproduction:
- Customer #469, paddle-phantom-fix-20260815@example.test, customer role.
- Order #27511 is pending, unpaid, $11.00, Paddle, with no transaction ID.
- Subscription #27527 is Pending with zero completed payments and only provisional Paddle session txn_01m02c04ehrvrv7bxtpqg72832. It has no canonical payment-method, customer, Paddle subscription, completed transaction, card brand, or last4 binding.
- Provider lookup confirms that exact transaction is draft with customer_id null and subscription_id null.
- Only webhook rows #1219 transaction.created and #1220 transaction.updated were added; no paid/completed/subscription-created event. There are zero scheduler actions for #27527, no new Mailpit message beyond baseline 3N6JcM0eoDrvQYq84uTBap, and debug.log stayed at 3557 lines.
- Admin REST reports status pending, gateway_is_automatic true, has_remote_binding false, is_automatic false, can_detach false, and actions.can_sync_gateway false.
- Admin browser shows the subscription as PENDING with no Payment Gateway card, no Connected label, no Paddle (Paddle), no Resync, and no Detach. Customer browser shows normal Payment Method: Paddle but no Billing Authorization or fabricated card details.
- Both clean page reloads had no JavaScript page errors and no captured 4xx/5xx requests.

Backward-safety counterexample:
- Existing paid Paddle subscription #7809 remains active and unchanged in behavior. Its REST response reports a durable binding, can_sync_gateway true, and can_detach true. The real admin browser still shows Payment Gateway CONNECTED, Visa ending in 5556, Resync from Gateway, and Detach Gateway.

Evidence: qa/artifacts/payment-bug-fixes-20260815/issue-002/paddle-overlay-ready.png, returned-to-checkout.png, admin-abandoned-subscription-final.png, customer-abandoned-subscription-final.png, and admin-existing-paddle-7809-final.png. The temporary renewal-sync setting was restored to true with first-charge mode full before closure.
