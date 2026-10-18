<?php
// Local browser fixtures only. Run once; cleanup-browser-fixtures.php restores settings.
$path = __DIR__ . '/browser-fixtures.json';
if (file_exists($path)) { WP_CLI::error('Fixtures already exist.'); }
$user = wp_create_user('developer-qa-' . time(), wp_generate_password(), 'developer-qa-' . time() . '@example.test');
if (is_wp_error($user)) { WP_CLI::error($user->get_error_message()); }
$ids = [];
foreach ([5, 10] as $value) {
    $p = new WC_Product_Simple();
    $p->set_name('Developer QA Seats ' . $value);
    $p->set_status('publish');
    $p->set_regular_price((string) $value);
    $p->update_meta_data('_is_subscription', 'yes');
    $p->update_meta_data('_subscription_period', 'month');
    $p->update_meta_data('_subscription_interval', 1);
    $p->update_meta_data('_arraysubs_features', wp_json_encode(arraysubs_sanitize_product_features([
        ['id' => 'seats', 'name' => 'Seats', 'type' => 'number', 'value' => (string) $value, 'enabled' => true, 'reset_period' => 'month'],
        ['id' => 'priority', 'name' => 'Priority', 'type' => 'toggle', 'value' => $value === 5 ? 'Yes' : 'No', 'enabled' => true],
        ['id' => 'region', 'name' => 'Region', 'type' => 'text', 'value' => $value === 5 ? 'EU' : 'US-East', 'enabled' => true],
    ])));
    $p->save();
    $sid = arraysubs_create_subscription(['customer_id' => $user, 'product_id' => $p->get_id(), 'subscription_price' => $value, 'recurring_amount' => $value, 'status' => 'arraysubs-active', 'next_payment_date' => gmdate('Y-m-d H:i:s', time() + 10 * DAY_IN_SECONDS), 'payment_method' => 'manual']);
    if (is_wp_error($sid)) { WP_CLI::error($sid->get_error_message()); }
    arraysubs_consume_feature_usage($sid, 'Seats', 2);
    $ids[] = ['product_id' => $p->get_id(), 'subscription_id' => $sid];
}
$rules = arraysubs_get_setting('members_access.download_rules', []);
$upload = wp_upload_dir();
$file = $upload['basedir'] . '/developer-support-qa.txt';
file_put_contents($file, 'Developer support download verification.');
$rule = ['id' => 'developer-support-qa', 'name' => 'Developer QA Download', 'enabled' => true, 'conditions' => ['logic' => 'AND', 'rules' => [['type' => 'has_active_subscription', 'product_ids' => array_column($ids, 'product_id')]]], 'download_limit' => 2, 'download_expiry' => 3, 'files' => [['id' => 'qa-file', 'name' => 'Developer QA File', 'url' => $upload['baseurl'] . '/developer-support-qa.txt']]];
arraysubs_update_setting('members_access.download_rules', array_merge($rules, [$rule]));
$data = ['user_id' => $user, 'items' => $ids, 'original_download_rules' => $rules, 'original_aggregation_mode' => 'per_subscription', 'file' => $file, 'download_url' => (new ArraySubs\Features\MembersAccess\Services\DownloadManager())->getFileDownloadUrl($rule, $rule['files'][0])];
file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
WP_CLI::success('Browser fixtures created for user ' . $user . ': ' . wp_json_encode($ids));
