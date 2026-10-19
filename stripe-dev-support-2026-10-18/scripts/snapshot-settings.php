<?php
/**
 * Snapshot every setting the staging Stripe QA run may change.
 *
 * Usage (from the WordPress root):
 *   wp eval-file <abs>/scripts/snapshot-settings.php <abs-output.json> --allow-root
 *
 * Exact-restore values are stored as base64(serialize(value)) together with a
 * sha256 of the serialized string. Options that hold credentials (Stripe gateway
 * settings, ArraySubs Stripe extras) are recorded as hashes only: this run must
 * never change them, and a hash is enough to prove they did not change.
 */

$out_file = isset($args[0]) ? (string) $args[0] : '';
if ('' === $out_file) {
    WP_CLI::error('Pass the output JSON path as the first argument.');
}

$restorable = [
    'arraysubs_settings',
    'active_plugins',
    'woocommerce_checkout_page_id',
    'woocommerce_cart_page_id',
    'woocommerce_myaccount_page_id',
    'woocommerce_enable_guest_checkout',
    'woocommerce_enable_checkout_login_reminder',
    'woocommerce_enable_signup_and_login_from_checkout',
    'woocommerce_enable_myaccount_registration',
    'woocommerce_registration_generate_password',
    'woocommerce_coming_soon',
    'woocommerce_store_pages_only',
    'woocommerce_calc_taxes',
    'woocommerce_currency',
    'woocommerce_hold_stock_minutes',
    'woocommerce_default_customer_address',
    'woocommerce_feature_custom_order_tables_enabled',
];

$hash_only = [
    'woocommerce_stripe_settings',
    'arraysubs_stripe_extras',
];

$record = [
    'captured_at_utc' => gmdate('c'),
    'site' => home_url('/'),
    'plugin_versions' => [
        'arraysubs' => defined('ARRAYSUBS_VERSION') ? ARRAYSUBS_VERSION : null,
        'arraysubspro' => defined('ARRAYSUBSPRO_VERSION') ? ARRAYSUBSPRO_VERSION : null,
        'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null,
        'stripe' => defined('WC_STRIPE_VERSION') ? WC_STRIPE_VERSION : null,
    ],
    'options' => [],
    'hash_only' => [],
    'sections' => [],
];

foreach ($restorable as $name) {
    $exists = null !== get_option($name, null);
    $value = get_option($name, null);
    $ser = serialize($value);
    $record['options'][$name] = [
        'exists' => $exists,
        'sha256' => hash('sha256', $ser),
        'b64' => base64_encode($ser),
        'readable' => $value,
    ];
}

foreach ($hash_only as $name) {
    $value = get_option($name, null);
    $record['hash_only'][$name] = [
        'exists' => null !== $value,
        'sha256' => hash('sha256', serialize($value)),
    ];
}

$settings = get_option('arraysubs_settings', []);
$settings = is_array($settings) ? $settings : [];
foreach ([
    'payment_retry' => 'retry ladder',
    'renewals' => 'grace/invoice lead/spread',
    'store_credit' => 'store credit',
    'plan_switching' => 'plan switching',
    'proration' => 'plan switching proration',
    'automatic_payments' => 'auto-renew toggle',
    'members_access' => 'member access',
    'feature_manager' => 'feature settings',
    'trials' => 'trials',
    'cancellation' => 'cancellation',
    'customer_actions' => 'customer actions',
    'pause_subscription' => 'pause',
    'skip_renewal' => 'skip',
    'multiple_subscriptions' => 'multiple subscriptions',
    'checkout' => 'checkout',
    'refunds' => 'refunds',
] as $key => $label) {
    $record['sections'][$key] = [
        'label' => $label,
        'sha256' => hash('sha256', serialize($settings[$key] ?? null)),
        'value' => $settings[$key] ?? null,
    ];
}

$record['settings_last_saved_at'] = get_option('arraysubs_settings_last_saved_at', null);

$json = wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
if (false === file_put_contents($out_file, $json . "\n")) {
    WP_CLI::error('Could not write ' . $out_file);
}
WP_CLI::success('Snapshot written: ' . $out_file . ' (arraysubs_settings sha256 ' . $record['options']['arraysubs_settings']['sha256'] . ')');
