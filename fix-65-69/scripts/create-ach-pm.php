<?php
/**
 * Create a test-mode ACH (us_bank_account) PaymentMethod with an off-session mandate for a
 * fixture Stripe customer, verified with Stripe's test microdeposit amounts.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/create-ach-pm.php <cus_id> <test_account_number> --allow-root
 * Test mode only. Prints ids and statuses, never keys.
 */
if (! class_exists('WC_Stripe_Mode') || ! WC_Stripe_Mode::is_test()) {
    WP_CLI::error('Stripe is not in test mode.');
}
$customer = (string) ($args[0] ?? '');
$account  = (string) ($args[1] ?? '');
if (! preg_match('/^cus_[A-Za-z0-9]+$/', $customer) || ! preg_match('/^0\d{8,11}$/', $account)) {
    WP_CLI::error('Usage: create-ach-pm.php <cus_id> <test_account_number>');
}
$si = WC_Stripe_API::request([
    'customer'             => $customer,
    'usage'                => 'off_session',
    'confirm'              => 'true',
    'payment_method_types' => ['us_bank_account'],
    'payment_method_data'  => [
        'type'            => 'us_bank_account',
        'us_bank_account' => ['routing_number' => '110000000', 'account_number' => $account, 'account_holder_type' => 'individual'],
        'billing_details' => ['name' => 'Sqa Fix67 Ach', 'email' => 'sqa-fix67-ach@example.com'],
    ],
    'payment_method_options' => ['us_bank_account' => ['verification_method' => 'microdeposits']],
    'mandate_data'         => ['customer_acceptance' => ['type' => 'offline']],
    'metadata'             => ['sqa' => 'fix-65-69 #67'],
], 'setup_intents');
if (isset($si->error)) {
    WP_CLI::error('setup_intents: ' . ($si->error->code ?? '') . ' ' . ($si->error->message ?? ''));
}
echo 'setup_intent ', $si->id, ' status=', $si->status, ' next_action=', $si->next_action->type ?? '-', "\n";
if ('requires_action' === $si->status) {
    $si = WC_Stripe_API::request(['amounts' => [32, 45]], 'setup_intents/' . $si->id . '/verify_microdeposits');
    if (isset($si->error)) {
        WP_CLI::error('verify_microdeposits: ' . ($si->error->code ?? '') . ' ' . ($si->error->message ?? ''));
    }
    echo 'verified status=', $si->status, "\n";
}
echo 'payment_method=', is_string($si->payment_method ?? null) ? $si->payment_method : ($si->payment_method->id ?? '-'), ' mandate=', is_string($si->mandate ?? null) ? $si->mandate : '-', "\n";
