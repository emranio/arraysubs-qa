<?php
// First run with --skip-plugins=arraysubspro, then normally. Exact fixture cleanup.
$path = __DIR__ . '/core-pro-preservation.json';
if (!file_exists($path)) {
    if (class_exists(ArraySubsPro\Features\FeatureManager\Services\CustomerFeatures::class)) {
        WP_CLI::error('Prepare this fixture in a core-only request first.');
    }
    $user = wp_create_user('developer-core-pro-' . time(), wp_generate_password(), 'developer-core-pro-' . time() . '@example.test');
    if (is_wp_error($user)) { WP_CLI::error($user->get_error_message()); }
    $product = new WC_Product_Simple();
    $product->set_name('Developer core-to-Pro preservation');
    $product->set_regular_price('17');
    $product->set_status('publish');
    $product->update_meta_data('_is_subscription', 'yes');
    $product->update_meta_data('_subscription_period', 'month');
    $product->update_meta_data('_subscription_interval', 1);
    $product->update_meta_data('_arraysubs_features', wp_json_encode([['id' => 'seats', 'name' => 'Seats', 'type' => 'number', 'value' => '9', 'enabled' => true, 'reset_period' => 'billing_cycle']]));
    $product->save();
    $sid = arraysubs_create_subscription(['customer_id' => $user, 'product_id' => $product->get_id(), 'status' => 'arraysubs-active', 'subscription_price' => 17, 'recurring_amount' => 17, 'next_payment_date' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS), 'payment_method' => 'manual']);
    if (is_wp_error($sid)) { WP_CLI::error($sid->get_error_message()); }
    $baseline = ['user_id' => $user, 'product_id' => $product->get_id(), 'subscription_id' => $sid, 'subscription_meta_hash' => hash('sha256', serialize(get_post_meta($sid))), 'product_meta_hash' => hash('sha256', serialize(get_post_meta($product->get_id()))), 'settings_hash' => hash('sha256', serialize(get_option('arraysubs_settings'))), 'snapshot_hash' => hash('sha256', serialize(ArraySubs\Developer\Subscriptions::fromId($sid)->data(['status', 'plan', 'pricing', 'schedule'])))];
    file_put_contents($path, wp_json_encode($baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    WP_CLI::success('Core-only fixture prepared.');
    return;
}
$data = json_decode(file_get_contents($path), true);
if (!empty($data['cleaned_at'])) { WP_CLI::error('Preservation fixture already tested and cleaned.'); }
$checks = [];
try {
    $checks['pro_loaded'] = class_exists(ArraySubsPro\Features\FeatureManager\Services\CustomerFeatures::class);
    $checks['subscription_identity'] = arraysubs_get_subscription($data['subscription_id'])->ID === $data['subscription_id'];
    $checks['subscription_meta_preserved'] = hash('sha256', serialize(get_post_meta($data['subscription_id']))) === $data['subscription_meta_hash'];
    $checks['product_meta_preserved'] = hash('sha256', serialize(get_post_meta($data['product_id']))) === $data['product_meta_hash'];
    $checks['settings_preserved'] = hash('sha256', serialize(get_option('arraysubs_settings'))) === $data['settings_hash'];
    $checks['domain_snapshot_preserved'] = hash('sha256', serialize(ArraySubs\Developer\Subscriptions::fromId($data['subscription_id'])->data(['status', 'plan', 'pricing', 'schedule']))) === $data['snapshot_hash'];
    foreach ($checks as $label => $passed) { if (!$passed) { throw new RuntimeException('Failed: ' . $label); } }
    WP_CLI::success(count($checks) . ' core-to-Pro preservation assertions passed.');
} finally {
    arraysubs_delete_subscription($data['subscription_id']);
    wc_get_product($data['product_id'])->delete(true);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($data['user_id']);
    $data['checks'] = $checks;
    $data['cleaned_at'] = gmdate('c');
    file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
