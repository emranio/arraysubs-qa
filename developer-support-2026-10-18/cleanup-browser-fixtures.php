<?php
// Remove only this cycle's recorded fixtures and restore its saved settings.
$path = __DIR__ . '/browser-fixtures.json';
$data = json_decode(file_get_contents($path), true);
if (!empty($data['cleaned_at'])) { WP_CLI::error('Browser fixtures already cleaned.'); }
$user = get_userdata($data['user_id']);
if (!$user || !str_starts_with($user->user_login, 'developer-qa-')) {
    WP_CLI::error('Recorded fixture identity does not match.');
}
foreach (['download', 'comment', 'ecommerce'] as $kind) {
    arraysubs_update_setting('members_access.' . $kind . '_rules', $data['original_' . $kind . '_rules']);
}
arraysubs_update_setting('feature_manager.aggregation_mode', $data['original_aggregation_mode']);
if (array_key_exists('original_plan_switching', $data)) {
    arraysubs_update_setting('plan_switching', $data['original_plan_switching']);
    arraysubs_update_setting('store_credit.auto_apply_to_renewals', $data['original_auto_apply']);
}
// Recorded before browser QA: WooCommerce Coming Soon was enabled site-wide.
update_option('woocommerce_coming_soon', 'yes');
foreach ($data['items'] as $item) {
    if ((int) get_post_meta($item['subscription_id'], '_customer_id', true) !== $data['user_id']) {
        WP_CLI::error('Subscription fixture owner changed; stop cleanup.');
    }
    arraysubs_delete_subscription($item['subscription_id']);
    $product = wc_get_product($item['product_id']);
    if ($product && str_starts_with($product->get_name(), 'Developer QA Seats ')) { $product->delete(true); }
}
if (!empty($data['switch_order_id'])) {
    $order = wc_get_order($data['switch_order_id']);
    if ($order && $order->get_customer_id() === $data['user_id']) { $order->delete(true); }
}
if (!empty($data['access_post_id'])) { wp_delete_post($data['access_post_id'], true); }
if (is_file($data['file']) && basename($data['file']) === 'developer-support-qa.txt') { unlink($data['file']); }
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user($data['user_id']);
$token_path = '/tmp/arraysubs-developer-browser-token';
if (is_file($token_path)) { WP_Session_Tokens::get_instance(1)->destroy(file_get_contents($token_path)); }
foreach (['browser-auth.php', 'browser-state.json', 'browser-token', 'customer-auth.php', 'customer-state.json'] as $suffix) {
    $temporary = '/tmp/arraysubs-developer-' . $suffix;
    if (is_file($temporary)) { unlink($temporary); }
}
$checks = ['user_removed' => !get_userdata($data['user_id']), 'coming_soon_restored' => get_option('woocommerce_coming_soon') === 'yes', 'aggregation_restored' => arraysubs_get_feature_manager_aggregation_mode() === $data['original_aggregation_mode']];
foreach (['download', 'comment', 'ecommerce'] as $kind) {
    $checks[$kind . '_rules_restored'] = arraysubs_get_setting('members_access.' . $kind . '_rules') === $data['original_' . $kind . '_rules'];
}
foreach ($data['items'] as $item) {
    $checks['subscription_' . $item['subscription_id'] . '_removed'] = !get_post($item['subscription_id']);
    $checks['product_' . $item['product_id'] . '_removed'] = !wc_get_product($item['product_id']);
}
if (in_array(false, $checks, true)) { WP_CLI::error('Cleanup verification failed: ' . wp_json_encode($checks)); }
$data['cleaned_at'] = gmdate('c');
$data['cleanup_checks'] = $checks;
file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
WP_CLI::success('Browser fixtures removed; original settings and isolated sessions restored.');
