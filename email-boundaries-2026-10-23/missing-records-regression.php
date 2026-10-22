<?php
/** Real core email fallbacks; delete only own fixtures and block every mail. */
defined('ABSPATH') || exit;

$checks = [];
$warnings = [];
$failure = null;
$fixtures = ['user' => 0, 'product' => 0, 'subscription' => 0, 'order' => 0];
$original_settings = get_option('arraysubs_settings', []);
$original_plugins = get_option('active_plugins', []);
$captures = [];
$block_mail = static function ($pre, $atts) use (&$captures) { $captures[] = $atts; return false; };
$check = static function (bool $ok, string $label) use (&$checks): void {
    $checks[] = ['check' => $label, 'passed' => $ok];
    if (!$ok) { throw new RuntimeException($label); }
};
$email_warning = static function ($level, $message, $file, $line) use (&$warnings) {
    if (str_contains($file, '/Features/Emails/') || str_contains($file, '/functions/email-helpers.php')) {
        $warnings[] = compact('level', 'message', 'file', 'line');
        return true;
    }
    return false;
};
add_filter('pre_wp_mail', $block_mail, PHP_INT_MAX, 2);
set_error_handler($email_warning, E_WARNING | E_NOTICE);
try {
    require_once ABSPATH . 'wp-admin/includes/user.php';
    $token = strtolower(wp_generate_password(12, false, false));
    $customer_email = 'missing-record-' . $token . '@example.invalid';
    $user = wp_insert_user(['user_login' => 'missing-record-' . $token, 'user_email' => $customer_email, 'user_pass' => wp_generate_password(32), 'first_name' => 'Own customer', 'role' => 'customer']);
    $check(!is_wp_error($user), 'Own disposable fallback customer created');
    $fixtures['user'] = $user;
    $product = new WC_Product_Simple();
    $product->set_name('Own disposable email product');
    $product->set_status('draft');
    $product->set_regular_price('23');
    $fixtures['product'] = $product->save();
    $fixtures['subscription'] = wp_insert_post([
        'post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Own email fallback fixture', 'post_author' => 0,
        'meta_input' => ['_customer_id' => $user, '_customer_email' => $customer_email, '_product_id' => $fixtures['product'], '_currency' => 'GBP', '_recurring_amount' => '23', '_subscription_price' => '23', '_billing_interval' => 1, '_billing_period' => 'month', '_payment_method' => 'bacs', '_payment_method_title' => 'QA manual; never paid', '_start_date' => gmdate('Y-m-d H:i:s'), '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS)],
    ]);
    $check(is_int($fixtures['subscription']) && $fixtures['subscription'] > 0, 'Own complete manual subscription snapshot created');
    $order = wc_create_order(['customer_id' => $user, 'status' => 'pending']);
    $order->set_payment_method('bacs');
    $order->set_currency('GBP');
    $order->set_total(23);
    $order->set_billing_first_name('Stored order buyer');
    $order->set_billing_email($customer_email);
    $fixtures['order'] = $order->save();
    $core_classes = array_filter(WC()->mailer()->emails, static fn($email) => $email instanceof \ArraySubs\Features\Emails\Abstracts\BaseSubscriptionEmail);
    $check(count($core_classes) === 25, 'All 25 core email classes are available');
    $product->delete(true);
    $check(!get_post($fixtures['product']), 'Own product is genuinely deleted before fallback checks');

    foreach (['deleted-product', 'deleted-customer', 'no-recipient'] as $scenario) {
        if ($scenario === 'deleted-customer') {
            wp_delete_user($user);
            $check(!get_userdata($user) && arraysubs_get_subscription($fixtures['subscription']) instanceof WP_Post, 'Deleting own customer preserves the independent fixture subscription');
        } elseif ($scenario === 'no-recipient') {
            delete_post_meta($fixtures['subscription'], '_customer_email');
        }
        foreach ($core_classes as $registered) {
            $class = get_class($registered);
            $email = new $class();
            $email->enabled = 'yes';
            $email->settings['recipient'] = 'fallback-admin@example.invalid';
            $before = count($captures);
            $email->trigger($fixtures['subscription'], ['order_id' => $fixtures['order'], 'old_product_id' => $fixtures['product'], 'new_product_id' => $fixtures['product']]);
            $emitted = count($captures) - $before;
            $expected_emitted = $scenario === 'no-recipient' && $email->is_customer_email() ? 0 : 1;
            $check($emitted === $expected_emitted, $scenario . ' ' . $email->id . ' sends only when a recipient exists');
            if ($emitted && $email->is_customer_email()) {
                $check($captures[$before]['to'] === $customer_email, $scenario . ' ' . $email->id . ' uses only the recorded fixture address');
            }
            $method = new ReflectionMethod($email, 'get_template_args');
            $method->setAccessible(true);
            $data = $method->invoke($email, false);
            $check($data['arraysubs_product'] === false || $data['arraysubs_product'] === null, $scenario . ' ' . $email->id . ' never resolves a missing product to a sample record');
            if ($scenario !== 'deleted-product') {
                $check($data['arraysubs_customer']['first_name'] === 'Stored order buyer', $scenario . ' ' . $email->id . ' uses its own order billing fallback');
            }
            foreach (['html', 'plain'] as $format) {
                $method = 'get_content_' . $format;
                $body = $email->$method();
                $check(strlen($body) > 100 && !str_contains($body, 'customer@example.com'), $scenario . ' ' . $email->id . ' ' . $format . ' renders without preview customer data');
            }
        }
    }
    $check($warnings === [], 'Missing/deleted related records produce no email-source warning or notice');
} catch (Throwable $error) {
    $failure = get_class($error) . ': ' . $error->getMessage();
} finally {
    if ($fixtures['order']) { $order = wc_get_order($fixtures['order']); if ($order) { $order->delete(true); } }
    if ($fixtures['subscription']) {
        \ArraySubs\Supports\ActionScheduler::unscheduleAllForSubscription($fixtures['subscription']);
        wp_delete_post($fixtures['subscription'], true);
    }
    if ($fixtures['product'] && get_post($fixtures['product'])) { wp_delete_post($fixtures['product'], true); }
    if ($fixtures['user'] && get_userdata($fixtures['user'])) { wp_delete_user($fixtures['user']); }
    restore_error_handler();
    remove_filter('pre_wp_mail', $block_mail, PHP_INT_MAX);
}
$settings_unchanged = get_option('arraysubs_settings', []) === $original_settings;
$plugins_unchanged = get_option('active_plugins', []) === $original_plugins;
echo wp_json_encode(['checks' => $checks, 'failure' => $failure, 'warnings' => $warnings, 'mail_intercepted_count' => count($captures), 'external_mail_sent' => 0, 'deleted_fixtures' => $fixtures, 'saved_settings_unchanged' => $settings_unchanged, 'active_plugins_unchanged' => $plugins_unchanged], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failure || !$settings_unchanged || !$plugins_unchanged) { WP_CLI::halt(1); }
