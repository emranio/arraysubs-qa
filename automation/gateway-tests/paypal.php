<?php

/**
 * PayPal gateway tests.
 *
 * Run: wp eval-file qa/automation/gateway-tests/paypal.php --allow-root
 *
 * Covers the four things §10 of the build-out plan asks of every item, as far as
 * they can be covered without a live provider: the happy path, failure injection
 * (a call that succeeds remotely but whose response is lost), idempotency, and
 * amount reconciliation.
 *
 * @package ArraySubs\QA
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/lib.php';

use ArraySubs\Features\AutomaticPayments\Gateways\PayPal\PayPalGateway;
use ArraySubs\Features\AutomaticPayments\Gateways\PayPal\PayPalPendingPlanSwitch;
use ArraySubs\Features\AutomaticPayments\Gateways\PayPal\PayPalApiClient;

$T = ArraySubs_Gateway_Test_Runner::class;

/**
 * Build a PayPal gateway wired to a scripted client.
 *
 * @param array $responses Scripted responses.
 * @return array{gateway: PayPalGateway, client: ArraySubs_Fake_PayPal_Client}
 */
function arraysubs_paypal_test_gateway(array $responses): array
{
    $client = new ArraySubs_Fake_PayPal_Client($responses);

    remove_all_filters('arraysubs_paypal_api_client');
    add_filter('arraysubs_paypal_api_client', static fn () => $client);

    $gateway = new PayPalGateway();

    return ['gateway' => $gateway, 'client' => $client];
}

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · refund idempotency (audit P0.3)');

$real = new PayPalApiClient('id', 'secret', 'sandbox');
$no_key = $real->refundSale('SALE-1', ['amount' => ['total' => '5.00', 'currency' => 'USD']]);
$T::assert(
    is_wp_error($no_key) && 'paypal_refund_key_missing' === $no_key->get_error_code(),
    'refundSale() refuses a missing per-attempt key'
);
$T::assert(
    is_wp_error($real->refundCapture('CAP-1', [])),
    'refundCapture() refuses a missing per-attempt key'
);

// Two identical partial refunds must produce two DIFFERENT keys, or PayPal
// dedupes the second into the first and one refund is recorded as two.
$order = wc_create_order();
$order->set_total(50.00);
$order->set_payment_method('arraysubs_paypal');
$order->set_transaction_id('SALE-TEST-1');
$order->save();

$refund_a = wc_create_refund(['amount' => 5.00, 'order_id' => $order->get_id(), 'refund_payment' => false]);
$refund_b = wc_create_refund(['amount' => 5.00, 'order_id' => $order->get_id(), 'refund_payment' => false]);

$paypal = arraysubs_paypal_test_gateway(['refundSale' => ['id' => 'REF-1']]);
$build = new ReflectionMethod($paypal['gateway'], 'buildRefundRequestId');
$build->setAccessible(true);

$key_seen = [];
foreach ([$refund_a, $refund_b] as $refund) {
    $prop = new ReflectionProperty($paypal['gateway'], 'pending_refund_id');
    $prop->setAccessible(true);
    $prop->setValue($paypal['gateway'], (int) $refund->get_id());
    $key_seen[] = $build->invoke($paypal['gateway'], $order);
}

$T::assert(
    $key_seen[0] !== $key_seen[1] && '' !== $key_seen[0] && '' !== $key_seen[1],
    'two identical partial refunds get different idempotency keys',
    implode(' vs ', $key_seen)
);

$order->delete(true);

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · pause (A1)');

$sub = arraysubs_test_make_subscription(['_gateway_paypal_subscription_id' => 'I-PAUSE1']);

// Happy path: ACTIVE → suspend → verified SUSPENDED.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription'     => ['__sequence' => [
        ['status' => 'ACTIVE'],
        ['status' => 'SUSPENDED'],
    ]],
    'suspendSubscription' => [],
]);
$result = $paypal['gateway']->pauseRemoteBillingContext($sub, ['reason' => 'vacation']);
$T::assert(! empty($result['success']) && 'paused' === $result['outcome'], 'pause succeeds when PayPal confirms SUSPENDED');
$T::same('paused', get_post_meta($sub, '_gateway_status', true), 'gateway status recorded as paused');
$T::same(1, $paypal['client']->countCalls('suspendSubscription'), 'suspend called exactly once');

// Already suspended: reported as success without calling suspend again.
update_post_meta($sub, '_gateway_status', 'active');
$paypal = arraysubs_paypal_test_gateway(['getSubscription' => ['status' => 'SUSPENDED']]);
$result = $paypal['gateway']->pauseRemoteBillingContext($sub);
$T::assert(! empty($result['success']) && 'already_paused' === $result['outcome'], 'an already-suspended subscription reports already_paused');
$T::same(0, $paypal['client']->countCalls('suspendSubscription'), 'no second suspend call is made');

// Failure injection: suspend errors, but PayPal really did suspend. The re-read
// decides, so this must be a success — not a failure that leaves the customer
// billed while ArraySubs thinks the pause failed.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription'     => ['__sequence' => [
        ['status' => 'ACTIVE'],
        ['status' => 'SUSPENDED'],
    ]],
    'suspendSubscription' => new WP_Error('paypal_api_error', 'UNPROCESSABLE_ENTITY'),
]);
$result = $paypal['gateway']->pauseRemoteBillingContext($sub);
$T::assert(! empty($result['success']), 'a lost suspend response resolves from PayPal\'s actual status');

// Failure injection: suspend errors and PayPal is still ACTIVE → must fail and
// must NOT pause locally.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription'     => ['__sequence' => [
        ['status' => 'ACTIVE'],
        ['status' => 'ACTIVE'],
    ]],
    'suspendSubscription' => new WP_Error('paypal_api_error', 'INTERNAL'),
]);
$result = $paypal['gateway']->pauseRemoteBillingContext($sub);
$T::assert(empty($result['success']), 'a genuinely failed suspend is reported as failure');

// Unknown outcome: PayPal unreachable entirely → parked, never a status.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription' => new WP_Error('http_request_failed', 'timeout'),
]);
$result = $paypal['gateway']->pauseRemoteBillingContext($sub);
$T::assert(empty($result['success']) && 'unknown' === $result['outcome'], 'an unreachable PayPal parks the pause as unknown');

// Cancelled subscriptions cannot be paused.
$paypal = arraysubs_paypal_test_gateway(['getSubscription' => ['status' => 'CANCELLED']]);
$result = $paypal['gateway']->pauseRemoteBillingContext($sub);
$T::assert(empty($result['success']), 'a cancelled subscription refuses to pause');

// A subscription with no PayPal binding must refuse, not silently succeed —
// that silent success was the original live money bug.
$orphan = arraysubs_test_make_subscription();
$paypal = arraysubs_paypal_test_gateway([]);
$result = $paypal['gateway']->pauseRemoteBillingContext($orphan);
$T::assert(empty($result['success']), 'a subscription with no PayPal agreement refuses to pause');
arraysubs_test_delete_subscription($orphan);

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · resume (A1)');

$paypal = arraysubs_paypal_test_gateway([
    'getSubscription'      => ['__sequence' => [
        ['status' => 'SUSPENDED'],
        ['status' => 'ACTIVE'],
        ['status' => 'ACTIVE', 'billing_info' => ['next_billing_time' => '2027-01-15T10:00:00Z']],
    ]],
    'activateSubscription' => [],
]);
$result = $paypal['gateway']->resumeRemoteBillingContext($sub);
$T::assert(! empty($result['success']) && 'resumed' === $result['outcome'], 'resume succeeds when PayPal confirms ACTIVE');

// Activate accepted but still suspended → must not resume locally.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription'      => ['__sequence' => [
        ['status' => 'SUSPENDED'],
        ['status' => 'SUSPENDED'],
    ]],
    'activateSubscription' => [],
]);
$result = $paypal['gateway']->resumeRemoteBillingContext($sub);
$T::assert(empty($result['success']) && 'unknown' === $result['outcome'], 'an unconfirmed resume is not committed locally');

// A cancelled agreement cannot be reactivated at all.
$paypal = arraysubs_paypal_test_gateway(['getSubscription' => ['status' => 'CANCELLED']]);
$result = $paypal['gateway']->resumeRemoteBillingContext($sub);
$T::assert(empty($result['success']), 'a cancelled agreement refuses to resume');

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · plan switch approval state machine (A3)');

PayPalPendingPlanSwitch::clear($sub);

$product = wc_get_products(['limit' => 1]);
$product_id = $product ? (int) $product[0]->get_id() : 0;

$switch_context = [
    'subscription_id'           => $sub,
    'billing_gateway'           => 'arraysubs_paypal',
    'requires_gateway_approval' => true,
    'mode'                      => 'deferred',
    'quantity'                  => 1,
    'currency'                  => 'USD',
    'new_product_id'            => $product_id,
    'old_product_id'            => $product_id,
    'new_plan'                  => [
        'product_id'       => $product_id,
        'variation_id'     => 0,
        'price'            => '30.00',
        'billing_period'   => 'month',
        'billing_interval' => 1,
    ],
    'proration' => [],
];

update_post_meta($sub, '_payment_gateway', 'arraysubs_paypal');
$subscription_post = get_post($sub);

// An immediate switch must be refused: PayPal cannot prorate.
$paypal = arraysubs_paypal_test_gateway([]);
$immediate = $paypal['gateway']->preparePayPalPlanSwitchMutation(
    true,
    $subscription_post,
    array_merge($switch_context, ['mode' => 'immediate_no_payment'])
);
$T::assert(
    is_wp_error($immediate) && 'paypal_plan_switch_immediate_unsupported' === $immediate->get_error_code(),
    'an immediate switch is refused with a specific reason'
);

// Card-funded: no approve link, PayPal shows the new plan → commit allowed.
$paypal = arraysubs_paypal_test_gateway([
    'createProduct'       => ['id' => 'PROD-1'],
    'createPlan'          => ['id' => 'P-TARGET'],
    'reviseSubscription'  => ['plan_id' => 'P-TARGET', 'links' => []],
    'getSubscription'     => ['status' => 'ACTIVE', 'plan_id' => 'P-TARGET'],
]);
$card_funded = $paypal['gateway']->preparePayPalPlanSwitchMutation(true, $subscription_post, $switch_context);
$T::assert($card_funded === true, 'a card-funded revise that PayPal confirms allows the local switch');
$T::same('P-TARGET', get_post_meta($sub, '_gateway_paypal_plan_id', true), 'the confirmed plan id is recorded');
$T::assert(! PayPalPendingPlanSwitch::isPending($sub), 'no pending marker is left behind');

// Wallet-funded: an approve link means pending, and nothing may be committed.
//
// The target price differs from the card-funded case on purpose: plans are cached
// per pricing signature on the product, so reusing the same terms would correctly
// reuse the plan already created above and this test would be asserting against
// the wrong id.
delete_post_meta($sub, '_gateway_paypal_plan_id');
$wallet_context = $switch_context;
$wallet_context['new_plan']['price'] = '41.00';

$paypal = arraysubs_paypal_test_gateway([
    'createProduct'      => ['id' => 'PROD-1'],
    'createPlan'         => ['id' => 'P-TARGET2'],
    'reviseSubscription' => [
        'plan_id' => 'P-TARGET2',
        'links'   => [['rel' => 'approve', 'href' => 'https://www.paypal.com/approve?ba_token=BA-9']],
    ],
    'getSubscription'    => ['status' => 'ACTIVE', 'plan_id' => 'P-OLD'],
]);
$wallet = $paypal['gateway']->preparePayPalPlanSwitchMutation(true, $subscription_post, $wallet_context);
$T::assert(
    is_wp_error($wallet) && 'paypal_plan_switch_requires_approval' === $wallet->get_error_code(),
    'a wallet-funded revise returns requires_approval rather than committing'
);
$data = is_wp_error($wallet) ? (array) $wallet->get_error_data() : [];
$T::assert(! empty($data['approval_url']), 'the approval URL is carried back to the caller');
$T::assert(PayPalPendingPlanSwitch::isPending($sub), 'a pending marker is stored');

$pending_target = (string) (PayPalPendingPlanSwitch::get($sub)['target_plan_id'] ?? '');
$T::same('P-TARGET2', $pending_target, 'the marker holds the newly created target plan');

// A second attempt while one is pending must not start another revise.
$paypal = arraysubs_paypal_test_gateway([]);
$second = $paypal['gateway']->preparePayPalPlanSwitchMutation(true, $subscription_post, $wallet_context);
$T::assert(
    is_wp_error($second) && 'paypal_plan_switch_awaiting_approval' === $second->get_error_code(),
    'a second switch attempt is refused while one awaits approval'
);
$T::same(0, $paypal['client']->countCalls('reviseSubscription'), 'no second revise is sent');

// An UPDATED event for a DIFFERENT plan must not complete the pending switch.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription' => ['status' => 'ACTIVE', 'plan_id' => 'P-SOMETHING-ELSE'],
]);
$wrong = $paypal['gateway']->handleBillingSubscriptionUpdated([
    'subscription_id' => $sub,
    'payload'         => ['plan_id' => 'P-SOMETHING-ELSE'],
]);
$T::assert(! empty($wrong['success']), 'an unrelated update event is acknowledged');
$T::assert(PayPalPendingPlanSwitch::isPending($sub), 'and does not complete the pending switch');

// The right plan does complete it.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscription' => ['status' => 'ACTIVE', 'plan_id' => $pending_target, 'billing_info' => ['next_billing_time' => '2027-02-01T00:00:00Z']],
]);
$right = $paypal['gateway']->handleBillingSubscriptionUpdated([
    'subscription_id' => $sub,
    'payload'         => ['plan_id' => $pending_target],
]);
$T::assert(! empty($right['success']), 'the confirming update event completes the switch');
$T::same($pending_target, get_post_meta($sub, '_gateway_paypal_plan_id', true), 'the switched plan id is recorded');
$T::assert(! PayPalPendingPlanSwitch::isPending($sub), 'the pending marker is cleared');

// Expiry: an unapproved marker is abandoned and nothing local changed.
PayPalPendingPlanSwitch::persist($sub, [
    'remote_subscription_id' => 'I-PAUSE1',
    'target_plan_id'         => 'P-NEVER',
    'new_product_id'         => $product_id,
    'quantity'               => 1,
    'approval_url'           => 'https://www.paypal.com/approve?ba_token=BA-X',
    'requires_approval'      => true,
]);
$marker = PayPalPendingPlanSwitch::get($sub);
$marker['expires_at_gmt'] = gmdate('Y-m-d H:i:s', time() - 60);
update_post_meta($sub, PayPalPendingPlanSwitch::META, wp_json_encode($marker));

$paypal = arraysubs_paypal_test_gateway(['getSubscription' => ['status' => 'ACTIVE', 'plan_id' => $pending_target]]);
$paypal['gateway']->expirePayPalPendingPlanSwitch($sub);
$T::assert([] === PayPalPendingPlanSwitch::get($sub), 'an unapproved pending switch expires and is cleared');
$T::same($pending_target, get_post_meta($sub, '_gateway_paypal_plan_id', true), 'the subscription stays on its current plan');

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · retention amount (A5)');

$paypal = arraysubs_paypal_test_gateway([
    'createProduct'      => ['id' => 'PROD-1'],
    'createPlan'         => ['id' => 'P-CHEAPER'],
    'reviseSubscription' => ['plan_id' => 'P-CHEAPER', 'links' => []],
    'getSubscription'    => ['status' => 'ACTIVE', 'plan_id' => 'P-CHEAPER'],
]);
update_post_meta($sub, '_product_id', $product_id);
$amount = $paypal['gateway']->updateNextRenewalAmount($sub, 15.00, ['reason' => 'retention_offer']);
$T::assert(! empty($amount['success']), 'a cheaper plan is applied when PayPal confirms it');
$T::same('next_cycle', $amount['effective'], 'PayPal reports the discount as next-cycle, never immediate');

// Requires approval → refused, and the approval URL is passed up so the
// retention flow can explain rather than promise a discount it did not apply.
$paypal = arraysubs_paypal_test_gateway([
    'createProduct'      => ['id' => 'PROD-1'],
    'createPlan'         => ['id' => 'P-CHEAPER2'],
    'reviseSubscription' => ['links' => [['rel' => 'approve', 'href' => 'https://www.paypal.com/approve?x=1']]],
    'getSubscription'    => ['status' => 'ACTIVE', 'plan_id' => 'P-CHEAPER'],
]);
$amount = $paypal['gateway']->updateNextRenewalAmount($sub, 12.00);
$T::assert(empty($amount['success']) && ! empty($amount['approval_url']), 'an approval-gated discount is refused with its URL');

// A zero or negative amount is refused outright.
$T::assert(empty($paypal['gateway']->updateNextRenewalAmount($sub, 0.0)['success']), 'a zero renewal amount is refused');

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · reconciliation and amount matching (A8)');

$renewal = wc_create_order();
$renewal->set_total(20.00);
$renewal->set_currency('USD');
$renewal->update_meta_data('_is_renewal_order', 'yes');
$renewal->update_meta_data('_subscription_id', $sub);
$renewal->save();

// Exact amount and currency → found and charged.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscriptionTransactions' => [
        'transactions' => [[
            'id'     => 'SALE-MATCH',
            'status' => 'COMPLETED',
            'time'   => gmdate('Y-m-d\TH:i:s\Z'),
            'amount_with_breakdown' => ['gross_amount' => ['value' => '20.00', 'currency_code' => 'USD']],
        ]],
    ],
]);
$verify = $paypal['gateway']->verifyRecentChargeForOrder($sub, $renewal);
$T::assert(! empty($verify['charged']) && ! empty($verify['conclusive']), 'an exact-amount completed transaction is found');
$T::same('SALE-MATCH', $verify['transaction_id'], 'its transaction id is returned');

// A different amount is a different cycle, not this order's charge.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscriptionTransactions' => [
        'transactions' => [[
            'id'     => 'SALE-OTHER',
            'status' => 'COMPLETED',
            'time'   => gmdate('Y-m-d\TH:i:s\Z'),
            'amount_with_breakdown' => ['gross_amount' => ['value' => '25.00', 'currency_code' => 'USD']],
        ]],
    ],
]);
$verify = $paypal['gateway']->verifyRecentChargeForOrder($sub, $renewal);
$T::assert(empty($verify['charged']) && ! empty($verify['conclusive']), 'a mismatched amount is conclusively not this charge');

// Right amount, wrong currency.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscriptionTransactions' => [
        'transactions' => [[
            'id'     => 'SALE-EUR',
            'status' => 'COMPLETED',
            'time'   => gmdate('Y-m-d\TH:i:s\Z'),
            'amount_with_breakdown' => ['gross_amount' => ['value' => '20.00', 'currency_code' => 'EUR']],
        ]],
    ],
]);
$verify = $paypal['gateway']->verifyRecentChargeForOrder($sub, $renewal);
$T::assert(empty($verify['charged']), 'a matching amount in another currency is not accepted');

// A pending transaction is not a charge.
$paypal = arraysubs_paypal_test_gateway([
    'getSubscriptionTransactions' => [
        'transactions' => [[
            'id'     => 'SALE-PENDING',
            'status' => 'PENDING',
            'time'   => gmdate('Y-m-d\TH:i:s\Z'),
            'amount_with_breakdown' => ['gross_amount' => ['value' => '20.00', 'currency_code' => 'USD']],
        ]],
    ],
]);
$verify = $paypal['gateway']->verifyRecentChargeForOrder($sub, $renewal);
$T::assert(empty($verify['charged']), 'a non-completed transaction is not treated as a charge');

// THE critical one: an unreachable PayPal must never read as "not charged".
$paypal = arraysubs_paypal_test_gateway([
    'getSubscriptionTransactions' => new WP_Error('http_request_failed', 'timeout'),
]);
$verify = $paypal['gateway']->verifyRecentChargeForOrder($sub, $renewal);
$T::assert(
    empty($verify['conclusive']) && empty($verify['charged']),
    'an unreachable PayPal is inconclusive, never a false "not charged"'
);

// A malformed body is also inconclusive, not an empty history.
$paypal = arraysubs_paypal_test_gateway(['getSubscriptionTransactions' => ['unexpected' => true]]);
$history = $paypal['gateway']->getRemoteChargeHistory($sub);
$T::assert(empty($history['conclusive']), 'an unrecognised transaction list is inconclusive');

// A transaction already claimed by another order is not this order's charge.
$other = wc_create_order();
$other->set_transaction_id('SALE-CLAIMED');
$other->save();
$paypal = arraysubs_paypal_test_gateway([
    'getSubscriptionTransactions' => [
        'transactions' => [[
            'id'     => 'SALE-CLAIMED',
            'status' => 'COMPLETED',
            'time'   => gmdate('Y-m-d\TH:i:s\Z'),
            'amount_with_breakdown' => ['gross_amount' => ['value' => '20.00', 'currency_code' => 'USD']],
        ]],
    ],
]);
$verify = $paypal['gateway']->verifyRecentChargeForOrder($sub, $renewal);
$T::assert(empty($verify['charged']), 'a transaction already recorded on another order is skipped');
$other->delete(true);

// Renewal orders enter the reconciliation sweep.
$fresh = wc_create_order();
$fresh->set_total(20.00);
$fresh->update_meta_data('_is_renewal_order', 'yes');
$fresh->save();
$paypal = arraysubs_paypal_test_gateway([]);
$paypal['gateway']->processRenewalPayment($sub, $fresh);
$fresh = wc_get_order($fresh->get_id());
$T::assert(
    '' !== (string) $fresh->get_meta('_arraysubs_pending_deadline', true),
    'a PayPal renewal order is enrolled in the reconciliation sweep'
);
$fresh->delete(true);
$renewal->delete(true);

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · card expiry capture (Q1) and recurring shipping (Q5)');

$expiry = new ReflectionMethod(PayPalGateway::class, 'parsePayPalCardExpiry');
$expiry->setAccessible(true);
$gw = new PayPalGateway();

$T::same(['month' => '7', 'year' => '2029'], $expiry->invoke($gw, '2029-07'), 'PayPal YYYY-MM expiry is parsed');
$T::same(['month' => '', 'year' => ''], $expiry->invoke($gw, '2029-13'), 'an impossible month is rejected');
$T::same(['month' => '', 'year' => ''], $expiry->invoke($gw, ''), 'a wallet-funded subscription yields no expiry');
$T::same(['month' => '', 'year' => ''], $expiry->invoke($gw, '07/29'), 'a differently formatted value is rejected');

$ship = new ReflectionMethod(PayPalGateway::class, 'resolvePayPalRecurringShipping');
$ship->setAccessible(true);

$ship_order = wc_create_order();
$ship_order->set_currency('USD');
$ship_order->set_billing_country('US');
$ship_order->set_shipping_country('US');
$ship_order->set_shipping_address_1('1 Test Street');
$ship_order->set_shipping_city('Testville');
$ship_order->set_shipping_postcode('12345');
$ship_order->save();

$ship_sub = arraysubs_test_make_subscription([
    '_arraysubs_needs_shipping'          => 'yes',
    '_arraysubs_shipping_type'           => 'recurring',
    '_arraysubs_renewal_shipping_total'  => '7.50',
]);
$resolved = $ship->invoke($gw, $ship_order, $ship_sub);
$T::same(7.5, $resolved['amount'], 'recurring shipping resolves from the renewal shipping total');
$T::assert(! empty($resolved['address']), 'a shipping address is supplied so PayPal does not re-ask');

// One-time shipping must not repeat every cycle.
update_post_meta($ship_sub, '_arraysubs_shipping_type', 'one-time');
$T::same(0.0, $ship->invoke($gw, $ship_order, $ship_sub)['amount'], 'one-time shipping is not charged every cycle');

// No shipping at all.
update_post_meta($ship_sub, '_arraysubs_shipping_type', 'recurring');
update_post_meta($ship_sub, '_arraysubs_needs_shipping', 'no');
$T::same(0.0, $ship->invoke($gw, $ship_order, $ship_sub)['amount'], 'a subscription that needs no shipping charges none');

// Falls back to the initial shipping total when no renewal override exists.
update_post_meta($ship_sub, '_arraysubs_needs_shipping', 'yes');
delete_post_meta($ship_sub, '_arraysubs_renewal_shipping_total');
update_post_meta($ship_sub, '_arraysubs_shipping_total', '4.25');
$T::same(4.25, $ship->invoke($gw, $ship_order, $ship_sub)['amount'], 'the initial shipping total is the fallback');

arraysubs_test_delete_subscription($ship_sub);
$ship_order->delete(true);

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · cancellation outcomes');

$cancel_sub = arraysubs_test_make_subscription(['_gateway_paypal_subscription_id' => 'I-CANCEL1']);

$paypal = arraysubs_paypal_test_gateway(['cancelSubscription' => []]);
$result = $paypal['gateway']->cancelRemoteBillingContext($cancel_sub, ['reason' => 'customer request']);
$T::assert(! empty($result['success']) && 'cancelled' === $result['outcome'], 'a confirmed cancellation reports cancelled');

// Already cancelled at PayPal: a failed call plus a terminal status is success.
$paypal = arraysubs_paypal_test_gateway([
    'cancelSubscription' => new WP_Error('paypal_api_error', 'already cancelled'),
    'getSubscription'    => ['status' => 'CANCELLED'],
]);
$result = $paypal['gateway']->cancelRemoteBillingContext($cancel_sub);
$T::assert(! empty($result['success']) && 'already_absent' === $result['outcome'], 'an already-cancelled agreement reports already_absent');

// Unreachable PayPal → must fail, so the retry queue keeps trying.
$paypal = arraysubs_paypal_test_gateway([
    'cancelSubscription' => new WP_Error('http_request_failed', 'timeout'),
    'getSubscription'    => new WP_Error('http_request_failed', 'timeout'),
]);
$result = $paypal['gateway']->cancelRemoteBillingContext($cancel_sub);
$T::assert(empty($result['success']), 'an unverifiable cancellation is reported as failure');

// A changed binding must abort rather than cancel the wrong agreement.
$paypal = arraysubs_paypal_test_gateway(['cancelSubscription' => []]);
$result = $paypal['gateway']->cancelRemoteBillingContext($cancel_sub, ['expected_remote_subscription_id' => 'I-DIFFERENT']);
$T::assert(empty($result['success']) && 'binding_changed' === ($result['error_code'] ?? ''), 'a changed binding aborts the cancellation');
$T::same(0, $paypal['client']->countCalls('cancelSubscription'), 'and sends nothing to PayPal');

arraysubs_test_delete_subscription($cancel_sub);

// ───────────────────────────────────────────────────────────────────────────
$T::group('PayPal · capability honesty');

$gw = new PayPalGateway();
$T::assert($gw->ownsBillingClock(), 'PayPal owns the billing clock');
$T::assert(! $gw->supportsSubscriptionCapability('early_renewal'), 'early renewal stays unavailable');
$T::assert(! $gw->supportsSubscriptionCapability('remote_billing_date'), 'PayPal cannot move its billing date');
$T::assert(! $gw->supportsSubscriptionCapability('recurring_coupons'), 'a coupon that recurs for N cycles is not claimed');
$T::assert(! $gw->supportsSubscriptionCapability('mixed_cart'), 'mixed carts are not claimed');
$T::assert($gw->supportsSubscriptionCapability('pause'), 'pause is claimed and implemented');
$T::assert($gw->supportsSubscriptionCapability('shipping'), 'recurring shipping is claimed and implemented');
$T::assert($gw->supportsSubscriptionCapability('card_expiry_notice'), 'card expiry is claimed and implemented');
$T::assert(! $gw->getRetryConfig()['enabled'], 'PayPal publishes local retries as disabled');

$messages = $gw->getUnsupportedCapabilityMessages();
$caps = $gw->getSubscriptionCapabilities();
$contradictions = [];
foreach ($messages as $capability => $message) {
    if (! empty($caps[$capability])) {
        $contradictions[] = $capability;
    }
}
$T::assert(
    [] === $contradictions,
    'no "why not" message contradicts a capability that is on',
    implode(', ', $contradictions)
);

arraysubs_test_delete_subscription($sub);
remove_all_filters('arraysubs_paypal_api_client');

$T::finish();
