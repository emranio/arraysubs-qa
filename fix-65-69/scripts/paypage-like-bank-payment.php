<?php
/**
 * Test mode only: pay a fixture renewal order the way WooCommerce Stripe's Pay page does,
 * with a saved test ACH method, so Stripe's own webhooks and WooCommerce Stripe's handlers
 * take it from there (processing → on-hold). Used where the Pay page's test bank settles at once.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/paypage-like-bank-payment.php <order_id> <pm_id> <mandate_id> --allow-root
 * Prints ids and statuses only (no order key, no secrets).
 */
if (! class_exists('WC_Stripe_Mode') || ! WC_Stripe_Mode::is_test()) {
    WP_CLI::error('Stripe is not in test mode.');
}
$order   = wc_get_order((int) ($args[0] ?? 0));
$pm      = (string) ($args[1] ?? '');
$mandate = (string) ($args[2] ?? '');
if (! $order || ! preg_match('/^pm_/', $pm) || ! preg_match('/^mandate_/', $mandate)) {
    WP_CLI::error('Usage: paypage-like-bank-payment.php <order_id> <pm_id> <mandate_id>');
}
$customer = (string) ($order->get_meta('_stripe_customer_id', true) ?: get_post_meta((int) $order->get_meta('_subscription_id', true), '_gateway_customer_id', true));
$pi = WC_Stripe_API::request([
    'amount'               => WC_Stripe_Helper::get_stripe_amount($order->get_total(), $order->get_currency()),
    'currency'             => strtolower($order->get_currency()),
    'customer'             => $customer,
    'payment_method'       => $pm,
    'payment_method_types' => ['us_bank_account'],
    'mandate'              => $mandate,
    'confirm'              => 'true',
    'metadata'             => ['order_id' => $order->get_order_number(), 'site_url' => esc_url(get_option('siteurl')), 'payment_type' => 'single', 'sqa' => 'fix-65-69 #66 pay-page-like'],
], 'payment_intents');
if (isset($pi->error)) {
    WP_CLI::error('payment_intents: ' . ($pi->error->code ?? '') . ' ' . ($pi->error->message ?? ''));
}
$order->update_meta_data('_stripe_intent_id', $pi->id);
$order->save();
echo 'payment_intent ', $pi->id, ' status=', $pi->status, ' latest_charge=', is_string($pi->latest_charge ?? null) ? $pi->latest_charge : '-', "\n";
