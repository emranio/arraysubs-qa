<?php
/** Disposable browser fixture. Run via WP-CLI eval-file; pass cleanup to remove. */
defined('ABSPATH') || exit;

use ArraySubs\Supports\ActionScheduler as Jobs;

$private = __DIR__ . '/private';
$record = $private . '/browser-fixture.json';
$mu_file = WP_CONTENT_DIR . '/mu-plugins/arraysubs-deferred-browser-fixture.php';
$mode = $args[0] ?? 'create';
$suppress_mail = static fn() => true;

if ($mode === 'snapshot') {
    $fixture = json_decode(file_get_contents($record), true, 512, JSON_THROW_ON_ERROR);
    $id = (int) $fixture['subscription_id'];
    $order = wc_get_order((int) $fixture['order_id']);
    $evidence = [
        'captured_at_utc' => gmdate('c'),
        'site_url' => site_url(),
        'browser_actor' => ['user_id' => (int) $fixture['admin_id'], 'role' => 'administrator'],
        'subscription_id' => $id,
        'subscription_status' => get_post_status($id),
        'order_id' => $order->get_id(),
        'order_status' => $order->get_status(),
        'deferred_marker_present' => (bool) get_post_meta($id, '_arraysubs_deferred_activation', true),
        'initial_payment_order' => (int) get_post_meta($id, '_arraysubs_initial_payment_processed_order', true),
        'completed_payments' => (int) get_post_meta($id, '_completed_payments', true),
        'original_next_payment_date' => $fixture['next_payment_date'],
        'current_next_payment_date' => get_post_meta($id, '_next_payment_date', true),
        'billing_date_unchanged' => $fixture['next_payment_date'] === get_post_meta($id, '_next_payment_date', true),
        'renewal_charge_scheduled' => Jobs::isScheduled(Jobs::HOOK_PROCESS_RENEWAL, [$id], Jobs::GROUP_RENEWALS),
        'renewal_invoice_scheduled' => Jobs::isScheduled(Jobs::HOOK_GENERATE_RENEWAL_INVOICE, [$id], Jobs::GROUP_BILLING),
        'charge_timestamp' => Jobs::getNextScheduledTime(Jobs::HOOK_PROCESS_RENEWAL, [$id], Jobs::GROUP_RENEWALS),
        'invoice_timestamp' => Jobs::getNextScheduledTime(Jobs::HOOK_GENERATE_RENEWAL_INVOICE, [$id], Jobs::GROUP_BILLING),
    ];
    file_put_contents(__DIR__ . '/browser-evidence.json', wp_json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    echo wp_json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    return;
}

if ($mode === 'cleanup') {
    if (!is_file($record)) {
        WP_CLI::error('Browser fixture record does not exist.');
    }
    $fixture = json_decode(file_get_contents($record), true, 512, JSON_THROW_ON_ERROR);
    add_filter('pre_wp_mail', $suppress_mail, PHP_INT_MAX);
    wp_set_current_user((int) $fixture['admin_id']);
    Jobs::unscheduleAllForSubscription((int) $fixture['subscription_id']);
    arraysubs_delete_subscription((int) $fixture['subscription_id'], true);
    $order = wc_get_order((int) $fixture['order_id']);
    if ($order) {
        $order->delete(true);
    }
    $product = wc_get_product((int) $fixture['product_id']);
    if ($product) {
        $product->delete(true);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_set_current_user(0);
    wp_delete_user((int) $fixture['customer_id']);
    wp_delete_user((int) $fixture['admin_id']);
    if (is_file($mu_file) && strpos(file_get_contents($mu_file), 'deferred-browser-fixture-owner:' . $fixture['run']) !== false) {
        unlink($mu_file);
    }
    unlink($record);
    remove_filter('pre_wp_mail', $suppress_mail, PHP_INT_MAX);
    echo wp_json_encode([
        'subscription_removed' => !get_post((int) $fixture['subscription_id']),
        'order_removed' => !wc_get_order((int) $fixture['order_id']),
        'product_removed' => !get_post((int) $fixture['product_id']),
        'customer_removed' => !get_userdata((int) $fixture['customer_id']),
        'admin_removed' => !get_userdata((int) $fixture['admin_id']),
        'mu_fixture_removed' => !is_file($mu_file),
        'credentials_removed' => !is_file($record),
    ], JSON_PRETTY_PRINT) . PHP_EOL;
    return;
}

if (is_file($record) || is_file($mu_file)) {
    WP_CLI::error('Existing browser fixture must be cleaned up first.');
}
if (!is_dir($private)) {
    mkdir($private, 0700, true);
}
chmod($private, 0700);
$run = substr(wp_generate_uuid4(), 0, 8);
$password = wp_generate_password(32);
$customer_email = 'deferred-browser-' . $run . '@example.test';
add_filter('pre_wp_mail', $suppress_mail, PHP_INT_MAX);
$admin_id = wp_insert_user([
    'user_login' => 'deferred-browser-admin-' . $run,
    'user_email' => 'deferred-browser-admin-' . $run . '@example.test',
    'user_pass' => $password,
    'role' => 'administrator',
]);
$customer_id = wp_insert_user([
    'user_login' => 'deferred-browser-customer-' . $run,
    'user_email' => $customer_email,
    'user_pass' => wp_generate_password(32),
    'role' => 'customer',
]);
if (is_wp_error($admin_id) || is_wp_error($customer_id)) {
    WP_CLI::error('Unable to create disposable browser users.');
}
wp_set_current_user($admin_id);
$product = new WC_Product_Simple();
$product->set_name('Deferred activation browser fixture ' . $run);
$product->set_regular_price('10');
$product->set_status('private');
$product->set_virtual(true);
$product_id = $product->save();
$order = wc_create_order(['customer_id' => $customer_id]);
$order->set_billing_email($customer_email);
$order->set_billing_first_name('Deferred');
$order->set_billing_last_name('Browser Fixture');
$order->set_payment_method('bacs');
$order->set_payment_method_title('Manual fixture payment');
$order->set_total('10');
$order->update_meta_data('_wc_other/tec-logic/imei', '000123456789012');
$order->save();
$subscription_id = wp_insert_post([
    'post_type' => 'arraysubs_data',
    'post_status' => 'arraysubs-pending',
    'post_title' => 'Awaiting IMEI approval — browser fixture ' . $run,
    'meta_input' => [
        '_customer_id' => $customer_id,
        '_customer_email' => $customer_email,
        '_invoice_email' => $customer_email,
        '_product_id' => $product_id,
        '_variation_id' => 0,
        '_quantity' => 1,
        '_currency' => get_woocommerce_currency(),
        '_subscription_price' => 10,
        '_recurring_amount' => 10,
        '_billing_period' => 'month',
        '_billing_interval' => 1,
        '_subscription_length' => 0,
        '_trial_length' => 0,
        '_trial_period' => 'day',
        '_start_date' => gmdate('Y-m-d H:i:s'),
        '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS),
        '_parent_order_id' => $order->get_id(),
        '_order_ids' => wp_json_encode([$order->get_id()]),
        '_completed_payments' => 0,
        '_payment_method' => 'bacs',
        '_payment_method_title' => 'Manual fixture payment',
    ],
], true);
if (is_wp_error($subscription_id)) {
    WP_CLI::error($subscription_id->get_error_message());
}
$defer = static fn($status, $subscription) => $subscription->ID === $subscription_id ? 'arraysubs-pending' : $status;
add_filter('arraysubs_initial_payment_subscription_status', $defer, 10, 2);
$order = wc_get_order($order->get_id());
arraysubs_link_subscription_to_parent_order($order, $subscription_id);
$order = wc_get_order($order->get_id());
$order->set_date_paid(time());
$order->update_status('completed');
remove_filter('arraysubs_initial_payment_subscription_status', $defer, 10);

$fixture = [
    'run' => $run,
    'site_url' => site_url(),
    'admin_url' => admin_url(),
    'admin_login' => 'deferred-browser-admin-' . $run,
    'admin_password' => $password,
    'admin_id' => $admin_id,
    'customer_id' => $customer_id,
    'customer_email' => $customer_email,
    'product_id' => $product_id,
    'order_id' => $order->get_id(),
    'subscription_id' => $subscription_id,
    'next_payment_date' => get_post_meta($subscription_id, '_next_payment_date', true),
];
file_put_contents($record, wp_json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
chmod($record, 0600);

// Prevent fixture mail during the disposable admin's browser requests.
// Other users, subscriptions, orders and recipient addresses are untouched.
$mu_source = "<?php\n// deferred-browser-fixture-owner:" . $run . "\ndefined('ABSPATH') || exit;\n"
    . "add_filter('pre_wp_mail', static function (\$pre, \$mail) {\n"
    . "    if (get_current_user_id() === " . $admin_id . ") { return true; }\n"
    . "    \$to = is_array(\$mail['to']) ? implode(',', \$mail['to']) : (string) \$mail['to'];\n"
    . "    return strpos(\$to, " . var_export($customer_email, true) . ") !== false ? true : \$pre;\n"
    . "}, PHP_INT_MAX, 2);\n";
file_put_contents($mu_file, $mu_source);
remove_filter('pre_wp_mail', $suppress_mail, PHP_INT_MAX);
echo wp_json_encode([
    'credential_record' => $record,
    'admin_id' => $admin_id,
    'subscription_id' => $subscription_id,
    'subscription_status' => get_post_status($subscription_id),
    'order_id' => $order->get_id(),
    'order_status' => wc_get_order($order->get_id())->get_status(),
    'deferred_marker' => (bool) get_post_meta($subscription_id, '_arraysubs_deferred_activation', true),
    'renewal_scheduled' => Jobs::isScheduled(Jobs::HOOK_PROCESS_RENEWAL, [$subscription_id], Jobs::GROUP_RENEWALS),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
