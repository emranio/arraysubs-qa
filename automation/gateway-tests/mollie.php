<?php

/**
 * Mollie delegate tests.
 *
 * Run: wp eval-file qa/automation/gateway-tests/mollie.php --allow-root
 *
 * @package ArraySubs\QA
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/lib.php';

use ArraySubs\Features\AutomaticPayments\Gateways\Mollie\MollieDelegate;
use ArraySubs\Features\AutomaticPayments\Services\MollieApiClient;
use ArraySubs\Features\AutomaticPayments\Services\MollieMandateResolver;
use ArraySubs\Features\AutomaticPayments\Services\MollieMandateRebinder;
use ArraySubs\Features\AutomaticPayments\Services\MollieTrialAvailability;
use ArraySubs\Features\AutomaticPayments\Services\CardExpiryNotifier;

$T = ArraySubs_Gateway_Test_Runner::class;

/**
 * Point the Mollie client factory at a scripted client.
 *
 * @param array $routes Scripted routes keyed by "METHOD path-prefix".
 * @return ArraySubs_Fake_Mollie_Client
 */
function arraysubs_mollie_test_client(array $routes): ArraySubs_Fake_Mollie_Client
{
    $client = new ArraySubs_Fake_Mollie_Client($routes);

    remove_all_filters('arraysubs_mollie_api_client');
    add_filter('arraysubs_mollie_api_client', static fn () => $client);

    return $client;
}

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · mandate resolution never guesses');

// A stored mandate that is still valid is used as-is.
$client = arraysubs_mollie_test_client([
    'GET customers/cst_1/mandates/mdt_1' => [
        'id'      => 'mdt_1',
        'status'  => 'valid',
        'method'  => 'creditcard',
        'details' => ['cardLabel' => 'Visa', 'cardNumber' => '4242', 'cardExpiryDate' => '2030-11-30'],
    ],
]);
$resolved = MollieMandateResolver::resolve($client, 'cst_1', 'mdt_1', 'creditcard');
$T::same('valid', $resolved['status'], 'a valid stored mandate resolves directly');
$T::same('mdt_1', $resolved['mandate_id'], 'and keeps its id');

// A 404 on the stored mandate falls through to the list — Mollie rotates ids
// when an iDEAL first payment produces a SEPA mandate.
$client = arraysubs_mollie_test_client([
    'GET customers/cst_1/mandates/mdt_old' => new WP_Error('mollie_api_error', 'not found', ['status' => 404]),
    'GET customers/cst_1/mandates'         => [
        '_embedded' => ['mandates' => [
            ['id' => 'mdt_new', 'status' => 'valid', 'method' => 'directdebit', 'details' => ['consumerAccount' => 'NL91ABNA0417164300']],
        ]],
    ],
]);
$resolved = MollieMandateResolver::resolve($client, 'cst_1', 'mdt_old');
$T::same('mdt_new', $resolved['mandate_id'], 'a rotated mandate is found from the customer list');

// THE critical one: a transport failure must NOT be reported as "no mandate",
// which would stop renewals on a perfectly good subscription.
$client = arraysubs_mollie_test_client([
    'GET customers/cst_1/mandates/mdt_1' => new WP_Error('http_request_failed', 'timeout'),
]);
$resolved = MollieMandateResolver::resolve($client, 'cst_1', 'mdt_1');
$T::same('error', $resolved['status'], 'an unreachable Mollie is an error, never "no mandate"');

// A 500 on the stored mandate is equally not proof of absence.
$client = arraysubs_mollie_test_client([
    'GET customers/cst_1/mandates/mdt_1' => new WP_Error('mollie_api_error', 'server error', ['status' => 500]),
]);
$T::same('error', MollieMandateResolver::resolve($client, 'cst_1', 'mdt_1')['status'], 'a 5xx is not read as absence');

// An invalid mandate with nothing usable in the list is a real "none".
$client = arraysubs_mollie_test_client([
    'GET customers/cst_1/mandates/mdt_1' => ['id' => 'mdt_1', 'status' => 'invalid'],
    'GET customers/cst_1/mandates'       => ['_embedded' => ['mandates' => [
        ['id' => 'mdt_2', 'status' => 'invalid', 'method' => 'creditcard'],
    ]]],
]);
$T::same('none', MollieMandateResolver::resolve($client, 'cst_1', 'mdt_1')['status'], 'no valid mandate anywhere reports none');

// The preferred method wins when several mandates are valid.
$client = arraysubs_mollie_test_client([
    'GET customers/cst_1/mandates' => ['_embedded' => ['mandates' => [
        ['id' => 'mdt_dd', 'status' => 'valid', 'method' => 'directdebit', 'details' => []],
        ['id' => 'mdt_cc', 'status' => 'valid', 'method' => 'creditcard', 'details' => []],
    ]]],
]);
$T::same('mdt_cc', MollieMandateResolver::resolve($client, 'cst_1', '', 'creditcard')['mandate_id'], 'the preferred method is chosen');
$T::same('mdt_dd', MollieMandateResolver::resolve($client, 'cst_1', '', '')['mandate_id'], 'without a preference the first valid mandate is used');

// No customer at all.
$T::same('none', MollieMandateResolver::resolve($client, '', '')['status'], 'no Mollie customer resolves to none');

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · card details are read for the expiry warning (C3)');

$described = MollieMandateResolver::describe(
    ['cardLabel' => 'Mastercard', 'cardNumber' => '1234', 'cardExpiryDate' => '2031-04-30'],
    'creditcard'
);
$T::same('1234', $described['last4'], 'the card last four is read');
$T::same('04', $described['expiry_month'], 'the expiry month is read');
$T::same('2031', $described['expiry_year'], 'the expiry year is read');

$sepa = MollieMandateResolver::describe(['consumerAccount' => 'NL91ABNA0417164300'], 'directdebit');
$T::same('sepa', $sepa['brand'], 'a SEPA mandate is described as sepa');
$T::same('4300', $sepa['last4'], 'and shows the account tail');
$T::same('', $sepa['expiry_month'], 'a bank mandate has no expiry to warn about');

// The notifier must turn that into a real deadline.
$T::assert(
    false !== CardExpiryNotifier::resolveExpiryTimestamp(4, 2031),
    'the notifier accepts the parsed expiry'
);
$T::same(30, CardExpiryNotifier::resolveStage(25), 'a card 25 days out gets the 30-day warning');
$T::same(7, CardExpiryNotifier::resolveStage(3), 'a card 3 days out gets the 7-day warning');
$T::same(0, CardExpiryNotifier::resolveStage(60), 'a card 60 days out gets no warning yet');

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · per-method trials (C1)');

$T::assert(
    ! MollieTrialAvailability::methodSupportsTrial('mollie_wc_gateway_directdebit'),
    'SEPA Direct Debit cannot start a trial'
);
$T::assert(
    ! MollieTrialAvailability::methodSupportsTrial('mollie_wc_gateway_ideal'),
    'iDEAL cannot start a trial'
);
$T::assert(
    ! MollieTrialAvailability::methodSupportsTrial('mollie_wc_gateway_bancontact'),
    'Bancontact cannot start a trial'
);

// The zero-amount list is filterable, but only a *live, enabled, mandate-capable*
// gateway can qualify — a name on the list is not enough.
add_filter('arraysubs_mollie_zero_amount_trial_methods', static fn () => ['mollie_wc_gateway_creditcard', 'mollie_wc_gateway_paypal']);
$capable = MollieTrialAvailability::getTrialCapableGatewayIds();
$T::assert(
    is_array($capable),
    'the trial-capable list is resolved from live gateway objects'
);
$T::assert(
    ! in_array('mollie_wc_gateway_directdebit', $capable, true),
    'a method that cannot take a zero-amount payment never appears'
);
remove_all_filters('arraysubs_mollie_zero_amount_trial_methods');

// A trial-incapable method must not report a completed trial setup: no mandate
// exists, so no renewal could ever be charged.
$trial_sub = arraysubs_test_make_subscription(['_trial_length' => 7, '_trial_period' => 'day']);
$trial_order = wc_create_order();
$trial_order->set_payment_method('mollie_wc_gateway_directdebit');
$trial_order->save();

$delegate = new MollieDelegate(false);
$setup = $delegate->setupTrialPaymentMethod($trial_order, $trial_sub, []);
$T::assert(empty($setup['success']), 'a trial on a mandate-less Mollie method is refused, not reported as set up');

// With a mandate already in hand, the setup is genuinely complete.
update_post_meta($trial_sub, '_gateway_mandate_id', 'mdt_existing');
$setup = $delegate->setupTrialPaymentMethod($trial_order, $trial_sub, []);
$T::assert(! empty($setup['success']), 'an existing mandate makes trial setup complete');

$trial_order->delete(true);
arraysubs_test_delete_subscription($trial_sub);

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · payment-method rebinding (C2)');

$sub = arraysubs_test_make_subscription([
    '_payment_gateway'      => 'mollie',
    '_gateway_customer_id'  => 'cst_reb',
    '_gateway_mandate_id'   => 'mdt_old',
    '_payment_method_type'  => 'creditcard',
    '_customer_id'          => 1,
]);

$rebinder = new MollieMandateRebinder();

// A newly minted mandate is bound to the subscription.
arraysubs_mollie_test_client([
    'GET customers/cst_reb/mandates/mdt_old' => new WP_Error('mollie_api_error', 'gone', ['status' => 404]),
    'GET customers/cst_reb/mandates'         => ['_embedded' => ['mandates' => [
        [
            'id'      => 'mdt_fresh',
            'status'  => 'valid',
            'method'  => 'creditcard',
            'details' => ['cardLabel' => 'Visa', 'cardNumber' => '9999', 'cardExpiryDate' => '2032-02-29'],
        ],
    ]]],
]);
$result = $rebinder->rebindSubscription($sub);
$T::assert(! empty($result['success']), 'rebinding succeeds against a fresh mandate');
$T::same('mdt_fresh', get_post_meta($sub, '_gateway_mandate_id', true), 'the new mandate is bound');
$T::same('9999', get_post_meta($sub, '_payment_method_last4', true), 'the new card details are stored');
$T::same('2032', get_post_meta($sub, '_payment_method_expiry_year', true), 'so a future expiry warning is about the new card');

// Rebinding again with the same mandate is a no-op success, not a false change.
arraysubs_mollie_test_client([
    'GET customers/cst_reb/mandates/mdt_fresh' => [
        'id' => 'mdt_fresh', 'status' => 'valid', 'method' => 'creditcard',
        'details' => ['cardLabel' => 'Visa', 'cardNumber' => '9999', 'cardExpiryDate' => '2032-02-29'],
    ],
]);
$result = $rebinder->rebindSubscription($sub);
$T::assert(! empty($result['success']), 'rebinding an unchanged mandate reports success');

// An unreachable Mollie must NOT clear the binding.
arraysubs_mollie_test_client([
    'GET customers/cst_reb/mandates/mdt_fresh' => new WP_Error('http_request_failed', 'timeout'),
]);
$result = $rebinder->rebindSubscription($sub);
$T::assert(empty($result['success']), 'an unreachable Mollie fails the rebind');
$T::same('mdt_fresh', get_post_meta($sub, '_gateway_mandate_id', true), 'and leaves the working mandate in place');

// A subscription with no Mollie customer cannot be rebound.
$no_customer = arraysubs_test_make_subscription(['_payment_gateway' => 'mollie']);
$T::assert(empty($rebinder->rebindSubscription($no_customer)['success']), 'a subscription with no Mollie customer refuses');
arraysubs_test_delete_subscription($no_customer);

// The shared filter only answers for Mollie subscriptions.
$stripe_sub = arraysubs_test_make_subscription(['_payment_gateway' => 'stripe']);
$T::same(null, $rebinder->filterRebindResult(null, $stripe_sub), 'the Mollie rebinder ignores another gateway\'s subscription');
arraysubs_test_delete_subscription($stripe_sub);

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · mandate revocation is explicit and confirmed (C4)');

$shared_a = arraysubs_test_make_subscription([
    '_payment_gateway'     => 'mollie',
    '_gateway_customer_id' => 'cst_shared',
    '_gateway_mandate_id'  => 'mdt_shared',
]);
$shared_b = arraysubs_test_make_subscription([
    '_payment_gateway'     => 'mollie',
    '_gateway_customer_id' => 'cst_shared',
    '_gateway_mandate_id'  => 'mdt_shared',
]);

// Unconfirmed: reports what it would affect and revokes nothing.
arraysubs_mollie_test_client([]);
$preview = $rebinder->filterRevokeResult(null, $shared_a, false);
$T::assert(! empty($preview['requires_confirmation']), 'an unconfirmed revocation asks for confirmation');
$T::assert(
    in_array($shared_b, (array) ($preview['affected_subscriptions'] ?? []), true),
    'and names the other subscription the mandate backs'
);
$T::same('mdt_shared', get_post_meta($shared_a, '_gateway_mandate_id', true), 'nothing is revoked yet');

// Confirmed: revoked, and every affected subscription is told.
$client = arraysubs_mollie_test_client([
    'DELETE customers/cst_shared/mandates/mdt_shared' => [],
]);
$done = $rebinder->filterRevokeResult(null, $shared_a, true);
$T::assert(! empty($done['success']), 'a confirmed revocation succeeds');
$T::same(1, $client->countCalls('DELETE'), 'Mollie is asked to revoke exactly once');
$T::same('', (string) get_post_meta($shared_a, '_gateway_mandate_id', true), 'the named subscription loses its binding');
$T::same('', (string) get_post_meta($shared_b, '_gateway_mandate_id', true), 'and so does the one sharing the mandate');
$T::same('needs_payment_method', get_post_meta($shared_a, '_gateway_status', true), 'the status says a payment method is needed');

// Already gone at Mollie is the outcome we wanted.
update_post_meta($shared_a, '_gateway_mandate_id', 'mdt_gone');
arraysubs_mollie_test_client([
    'DELETE customers/cst_shared/mandates/mdt_gone' => new WP_Error('mollie_api_error', 'not found', ['status' => 404]),
]);
$T::assert(! empty($rebinder->filterRevokeResult(null, $shared_a, true)['success']), 'an already-revoked mandate reports success');

// A real API failure must not clear the binding.
update_post_meta($shared_a, '_gateway_mandate_id', 'mdt_live');
arraysubs_mollie_test_client([
    'DELETE customers/cst_shared/mandates/mdt_live' => new WP_Error('mollie_api_error', 'server error', ['status' => 500]),
]);
$failed = $rebinder->filterRevokeResult(null, $shared_a, true);
$T::assert(empty($failed['success']), 'a failed revocation is reported as failure');
$T::same('mdt_live', get_post_meta($shared_a, '_gateway_mandate_id', true), 'and the mandate stays bound');

arraysubs_test_delete_subscription($shared_a);
arraysubs_test_delete_subscription($shared_b);

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · sync reports only what it can prove (D3)');

$sync_sub = arraysubs_test_make_subscription([
    '_payment_gateway'     => 'mollie',
    '_gateway_customer_id' => 'cst_sync',
    '_gateway_mandate_id'  => 'mdt_sync',
    '_payment_method_type' => 'creditcard',
]);

arraysubs_mollie_test_client([
    'GET customers/cst_sync/mandates/mdt_sync' => [
        'id' => 'mdt_sync', 'status' => 'valid', 'method' => 'creditcard',
        'details' => ['cardLabel' => 'Visa', 'cardNumber' => '4242', 'cardExpiryDate' => '2030-06-30'],
    ],
    'GET payments' => ['_embedded' => ['payments' => []]],
]);
$state = $delegate->syncFromGateway($sync_sub);
$T::assert(! empty($state['success']), 'a sync against a valid mandate succeeds');
$T::same('active', $state['gateway_status'], 'a valid mandate is reported as active — that is evidence, not a guess');
$T::same('', $state['next_payment_date'], 'Mollie reports no billing date');
$T::assert(
    array_key_exists('next_payment_date_authoritative', $state) && false === $state['next_payment_date_authoritative'],
    'and marks the date as not authoritative so the reconciler leaves the local one alone'
);

// An unreachable Mollie must fail the sync rather than report a status.
arraysubs_mollie_test_client([
    'GET customers/cst_sync/mandates/mdt_sync' => new WP_Error('http_request_failed', 'timeout'),
]);
$state = $delegate->syncFromGateway($sync_sub);
$T::assert(empty($state['success']), 'an unreachable Mollie fails the sync instead of inventing a status');

arraysubs_test_delete_subscription($sync_sub);

// ───────────────────────────────────────────────────────────────────────────
$T::group('Mollie · capability honesty');

$T::assert(! $delegate->ownsBillingClock(), 'ArraySubs owns the billing clock for Mollie');
$T::assert($delegate->supportsSubscriptionCapability('trials'), 'trials are claimed, per-method');
$T::assert($delegate->supportsSubscriptionCapability('payment_method_update'), 'payment-method update is claimed and now has a mechanism');
$T::assert($delegate->supportsSubscriptionCapability('card_expiry_notice'), 'card expiry is claimed and has a mechanism');
$T::assert($delegate->supportsSubscriptionCapability('cancel_at_period_end'), 'cancel at period end is a local decision and is claimed');
$T::assert(! $delegate->supportsSubscriptionCapability('card_auto_update'), 'card auto-update stays unclaimed (open question)');
$T::assert(! $delegate->supportsSubscriptionCapability('remote_billing_date'), 'Mollie has no remote billing date to move');
$T::assert(! $delegate->supportsSubscriptionCapability('pause'), 'Mollie has nothing remote to pause');
$T::assert($delegate->getRetryConfig()['enabled'], 'ArraySubs runs dunning for Mollie');
$T::assert(count($delegate->getRetryConfig()['intervals']) >= 1, 'and publishes a per-attempt ladder');

$messages = $delegate->getUnsupportedCapabilityMessages();
$caps = $delegate->getSubscriptionCapabilities();
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
remove_all_filters('arraysubs_mollie_api_client');

$T::finish();
