<?php
/** Actual WP/Woo email rendering regression. All outbound mail is intercepted. */
defined('ABSPATH') || exit;
$dir = __DIR__ . '/';
$label = sanitize_key($args[0] ?? 'baseline');
@mkdir($dir . 'render-' . $label, 0777, true);
$checks = [];
$warnings = [];
$captures = [];
$fixtures = ['users' => [], 'products' => [], 'subscriptions' => [], 'orders' => []];
$original_plugins = get_option('active_plugins', []);
$settings_hash = hash('sha256', serialize(get_option('arraysubs_settings', [])));
$current_capture = '';
$check = static function ($pass, $name) use (&$checks): void { $checks[] = ['check' => $name, 'passed' => (bool) $pass]; };
$block_mail = static function ($return, $atts) use (&$captures, &$current_capture) {
    $captures[] = ['label' => $current_capture, 'to' => $atts['to'], 'subject' => $atts['subject'], 'message' => $atts['message']];
    return false; // No external SMTP, notifications or fixture mail, even on failure.
};
add_filter('pre_wp_mail', $block_mail, PHP_INT_MAX, 2);
set_error_handler(static function ($level, $message, $file, $line) use (&$warnings) {
    if (str_contains($file, '/Features/Emails/') || str_contains($file, '/StoreCredit/Emails/') || str_contains($file, '/templates/emails/')) {
        $warnings[] = compact('level', 'message', 'file', 'line'); return true;
    }
    return false;
}, E_WARNING | E_NOTICE);
try {
    $mailer = WC()->mailer();
    $email_classes = array_filter($mailer->get_emails(), static fn($email) => str_starts_with($email->id, 'arraysubs_'));
    $check(count($email_classes) >= 25, 'All core email classes register');
    $pro = class_exists('ArraySubsPro\\Features\\StoreCredit\\Services\\EmailManager');
    $check(count($email_classes) === ($pro ? 29 : 25), 'Only available core / Pro email classes register');
    foreach (['alpha' => 'Alice & QA', 'beta' => 'Bob QA'] as $key => $name) {
        $token = strtolower(wp_generate_password(10, false, false));
        $id = wp_insert_user(['user_login' => 'email-qa-' . $key . '-' . $token, 'user_pass' => wp_generate_password(32), 'user_email' => 'email-qa-' . $key . '-' . $token . '@example.invalid', 'first_name' => $name, 'last_name' => 'Email', 'display_name' => $name, 'role' => 'customer']);
        if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
        $fixtures['users'][$key] = (int) $id;
        $product = new WC_Product_Simple();
        $product->set_name($name . ' <Library>'); $product->set_status('draft'); $product->set_regular_price('31.25'); $product->set_virtual(true);
        $fixtures['products'][$key] = $product->save();
        $sub = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'QA91 ' . $name, 'post_author' => $id, 'meta_input' => [
            '_customer_id' => $id, '_customer_email' => get_userdata($id)->user_email, '_product_id' => $product->get_id(), '_currency' => 'GBP', '_recurring_amount' => '31.25', '_subscription_price' => '31.25', '_billing_interval' => 1, '_billing_period' => 'month', '_start_date' => gmdate('Y-m-d H:i:s'), '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS), '_end_date' => gmdate('Y-m-d H:i:s', time() + 90 * DAY_IN_SECONDS), '_payment_method' => 'bacs', '_payment_method_title' => 'Manual QA',
        ]]);
        if (!$sub || is_wp_error($sub)) { throw new RuntimeException('Cannot create subscription fixture'); }
        $fixtures['subscriptions'][$key] = $sub;
    }
    $order = wc_create_order(['customer_id' => $fixtures['users']['alpha'], 'created_via' => 'qa-email-20261023']);
    $fixtures['orders'][] = $order->get_id();
    $order->add_product(wc_get_product($fixtures['products']['alpha']), 1); $order->set_currency('GBP');
    $order->set_billing_first_name('Alice & QA'); $order->set_billing_last_name('Email'); $order->set_billing_email(get_userdata($fixtures['users']['alpha'])->user_email); $order->set_payment_method('bacs'); $order->set_payment_method_title('QA manual only'); $order->update_meta_data('_is_renewal_order', 'yes'); $order->update_meta_data('_subscription_id', $fixtures['subscriptions']['alpha']); $order->calculate_totals(); $order->save();
    $pay_url = $order->get_checkout_payment_url();
    $core_args = ['order_id' => $order->get_id(), 'days_before' => 3, 'cycles' => 1, 'skipped_cycles' => 1, 'old_product_id' => $fixtures['products']['alpha'], 'new_product_id' => $fixtures['products']['beta']];
    foreach ($email_classes as $key => $email) {
        if (!$email instanceof \ArraySubs\Features\Emails\Abstracts\BaseSubscriptionEmail) { continue; }
        $email->enabled = 'yes'; $email->settings['enabled'] = 'yes'; $email->settings['recipient'] = 'email-qa-admin@example.invalid';
        // Preview rendering uses the real registered class before any trigger.
        foreach (['html', 'plain'] as $format) {
            $method = 'get_content_' . $format;
            $content = $email->$method();
            file_put_contents($dir . 'render-' . $label . '/' . $email->id . '-preview.' . ($format === 'html' ? 'html' : 'txt'), $content);
            $check(strlen($content) > 100, $email->id . ' ' . $format . ' preview renders');
            $check(!preg_match('/#0\b/', html_entity_decode(wp_strip_all_tags($content))), $email->id . ' ' . $format . ' preview does not show subscription zero');
        }
        $current_capture = $email->id;
        $email->trigger($fixtures['subscriptions']['alpha'], $core_args);
        foreach (['html', 'plain'] as $format) {
            $method = 'get_content_' . $format;
            $content = $email->$method();
            file_put_contents($dir . 'render-' . $label . '/' . $email->id . '-actual.' . ($format === 'html' ? 'html' : 'txt'), $content);
            $check(strlen($content) > 100, $email->id . ' ' . $format . ' real fixture renders');
            $check(!str_contains($content, 'page=arraysubs#'), $email->id . ' ' . $format . ' admin links use registered page');
            if ($format === 'plain') {
                $check(!preg_match('/<(?:a|p|br|span|div)\b/i', $content) && !str_contains($content, '&amp;'), $email->id . ' plaintext omits HTML and entity-escaped customer names');
            }
        }
    }
    foreach (['payment_failed', 'renewal_invoice', 'payment_successful', 'admin_payment_failed'] as $id) {
        $email = $email_classes['arraysubs_' . $id];
        $email->settings['subject'] = 'QA {customer_name} order {order_id} {order_total} {order_pay_url} {retry_url}';
        $email->trigger($fixtures['subscriptions']['alpha'], ['order_id' => $order->get_id()]);
        foreach ([[], ['order_id' => PHP_INT_MAX]] as $missing_order) {
            $email->trigger($fixtures['subscriptions']['beta'], $missing_order);
            $all = $email->get_content_html() . $email->get_content_plain() . $email->get_subject();
            $check(!str_contains($all, $pay_url) && !str_contains($all, 'wc_order_' . $order->get_order_key()), $id . ' does not reuse another customer payment URL without valid order');
            $check(!str_contains($email->get_subject(), 'order ' . $order->get_id()), $id . ' clears stale order placeholders without valid order');
        }
    }
    $email = $email_classes['arraysubs_new_subscription'];
    $email->trigger($fixtures['subscriptions']['alpha']);
    $check(str_contains(html_entity_decode(wp_strip_all_tags($email->get_content_html())), html_entity_decode(wp_strip_all_tags(wc_price(31.25, ['currency' => 'GBP'])))), 'Recurring price in email body uses subscription currency');
    $placeholders = arraysubs_get_email_placeholders($fixtures['subscriptions']['alpha']);
    $check(html_entity_decode(wp_strip_all_tags($placeholders['{recurring_amount}'])) === html_entity_decode(wp_strip_all_tags(wc_price(31.25, ['currency' => 'GBP']))), 'Email placeholder helper uses subscription currency');
    $check($warnings === [], 'No email-source PHP warnings/notices across preview and actual renders');
} catch (Throwable $e) { $failure = get_class($e) . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(); }
finally {
    foreach ($fixtures['subscriptions'] as $id) { \ArraySubs\Supports\ActionScheduler::unscheduleAllForSubscription($id); wp_delete_post($id, true); }
    foreach ($fixtures['orders'] as $id) { $order = wc_get_order($id); if ($order) { $order->delete(true); } }
    foreach ($fixtures['products'] as $id) { $product = wc_get_product($id); if ($product) { $product->delete(true); } }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($fixtures['users'] as $id) { wp_delete_user($id); }
    restore_error_handler(); remove_filter('pre_wp_mail', $block_mail, PHP_INT_MAX);
}
$result = ['pro_loaded' => $pro ?? null, 'checks' => $checks, 'failure' => $failure ?? null, 'warnings' => $warnings, 'intercepted_mail_count' => count($captures), 'outbound_transport_blocked' => true, 'deleted_fixtures' => $fixtures, 'settings_unchanged' => hash('sha256', serialize(get_option('arraysubs_settings', []))) === $settings_hash, 'active_plugins_unchanged' => get_option('active_plugins', []) === $original_plugins];
file_put_contents($dir . 'render-' . $label . '/intercepted-subjects.json', wp_json_encode(array_map(static fn($row) => ['email' => $row['label'], 'subject' => $row['subject']], $captures), JSON_PRETTY_PRINT));
echo wp_json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
if (!empty($failure) || array_filter($checks, static fn($row) => !$row['passed'])) { WP_CLI::halt(1); }
