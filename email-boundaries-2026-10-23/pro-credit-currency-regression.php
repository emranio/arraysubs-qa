<?php
// Actual local Woo email render probe. Own draft fixtures only, every mail intercepted.
$source = $args[0] ?? '';
$result = ['source' => $source, 'checks' => []];
$checks = static function ($name, $pass, $details = []) use (&$result) {
    $result['checks'][] = ['name' => $name, 'pass' => (bool) $pass, 'details' => $details];
};
$mail_count = 0;
add_filter('pre_wp_mail', static function () use (&$mail_count) { ++$mail_count; return true; }, PHP_INT_MAX);
$initial_settings = get_option('arraysubs_settings');
$initial_plugins = get_option('active_plugins');
foreach (['CreditAddedEmail', 'CreditUsedEmail', 'CreditExpiringEmail', 'CreditExpiredEmail'] as $class) {
    require_once $source . '/src/Features/StoreCredit/Emails/' . $class . '.php';
}
register_post_type('arraysubs_credit', ['public' => false]);
$user = wp_insert_user([
    'user_login' => 'qa93-currency-' . wp_generate_password(8, false, false),
    'user_pass' => wp_generate_password(32, true, true),
    'user_email' => 'qa93-currency-' . wp_generate_password(8, false, false) . '@example.invalid',
    'display_name' => 'QA93 Currency', 'role' => 'customer',
]);
if (is_wp_error($user)) { throw new RuntimeException($user->get_error_message()); }
$subscription = 0;
$credit_log = 0;
$order = null;
try {
    update_user_meta($user, '_arraysubs_store_credit', 5.0);
    $subscription = wp_insert_post([
        'post_type' => 'arraysubs_data', 'post_status' => 'draft', 'post_title' => 'QA93 GBP draft',
        'meta_input' => ['_customer_id' => $user, '_currency' => 'GBP', '_store_credit' => 7.0],
    ]);
    $credit_log = wp_insert_post([
        'post_type' => 'arraysubs_credit', 'post_status' => 'draft', 'post_title' => 'QA93 currency-only draft fixture',
        'meta_input' => ['_customer_id' => $user, '_subscription_id' => $subscription, '_currency' => 'GBP'],
    ]);
    $order = wc_create_order(['customer_id' => $user, 'status' => 'pending']);
    $order->set_currency('GBP');
    $order->set_billing_email(get_userdata($user)->user_email);
    $order->save();
    $result['fixtures'] = ['customer' => $user, 'subscription' => $subscription, 'draft_credit_log' => $credit_log, 'pending_order' => $order->get_id(), 'store_currency' => get_woocommerce_currency()];
    $money = static fn ($amount, $currency) => html_entity_decode(wp_strip_all_tags(wc_price($amount, ['currency' => $currency])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    foreach (['Added', 'Used', 'Expiring', 'Expired'] as $kind) {
        $class = 'ArraySubsPro\\Features\\StoreCredit\\Emails\\Credit' . $kind . 'Email';
        $email = new $class();
        $email->template_base = $source . '/src/Features/StoreCredit/templates/';
        if ($kind === 'Added') { $email->triggerFromSubscription($subscription, 12.5, 7.0, 'admin'); }
        if ($kind === 'Used') { $email->trigger($order, 12.5, $subscription); }
        if ($kind === 'Expiring') { $email->trigger($user, 12.5, gmdate('Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS), $credit_log); }
        if ($kind === 'Expired') { $email->trigger($user, 12.5, $credit_log, $subscription); }
        $plain = $email->get_content_plain();
        $html = $email->get_content_html();
        $result['plain'][$kind] = $plain;
        $expected = $money(12.5, 'GBP');
        $html_text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $checks($kind . ' body uses GBP source currency', str_contains($plain, $expected) && str_contains($html_text, $expected), ['expected' => $expected]);
        $key = ['Added' => '{credit_amount}', 'Used' => '{credit_used}', 'Expiring' => '{expiring_amount}', 'Expired' => '{expired_amount}'][$kind];
        $checks($kind . ' placeholders use GBP source currency', $email->placeholders[$key] === $expected, ['actual' => $email->placeholders[$key], 'expected' => $expected]);
        if ($kind === 'Used') {
            $checks('foreign-currency remaining balance excludes customer store currency', str_contains($plain, 'Remaining Balance: ' . $money(7, 'GBP')), ['expected' => $money(7, 'GBP'), 'customer_store_balance' => 5, 'subscription_GBP_balance' => 7]);
        }
        if ($kind === 'Added') {
            $email->trigger($user, 12.5, 5.0, 'admin');
            $checks('customer-only next trigger resets currency to store currency', $email->placeholders['{credit_amount}'] === $money(12.5, get_woocommerce_currency()));
        }
    }
    $checks('email rendering leaves own balances unchanged', 5.0 === (float) get_user_meta($user, '_arraysubs_store_credit', true) && 7.0 === (float) get_post_meta($subscription, '_store_credit', true));
    $used = new ArraySubsPro\Features\StoreCredit\Emails\CreditUsedEmail();
    $used->template_base = $source . '/src/Features/StoreCredit/templates/';
    $order->set_currency(get_woocommerce_currency());
    update_post_meta($subscription, '_currency', get_woocommerce_currency());
    $used->trigger($order, 12.5, $subscription);
    $checks('same-currency customer and owned subscription balances still combine', str_contains($used->get_content_plain(), 'Remaining Balance: ' . $money(12, get_woocommerce_currency())));
    $order->set_currency('GBP');
    $used->trigger($order, 12.5, $subscription);
    $checks('different-currency subscription balance is excluded', str_contains($used->get_content_plain(), 'Remaining Balance: ' . $money(0, 'GBP')));
    update_post_meta($subscription, '_currency', 'GBP');
    update_post_meta($subscription, '_customer_id', 0);
    $used->trigger($order, 12.5, $subscription);
    $checks('different-owner subscription balance is excluded', str_contains($used->get_content_plain(), 'Remaining Balance: ' . $money(0, 'GBP')));
    update_post_meta($subscription, '_customer_id', $user);
    update_post_meta($subscription, '_store_credit', 0.0);
    $used->trigger($order, 12.5, $subscription);
    $checks('real zero foreign balance remains zero', str_contains($used->get_content_plain(), 'Remaining Balance: ' . $money(0, 'GBP')) && !str_contains($used->get_content_plain(), 'John Doe'));
    $expired = new ArraySubsPro\Features\StoreCredit\Emails\CreditExpiredEmail();
    $expired->template_base = $source . '/src/Features/StoreCredit/templates/';
    $expired->trigger($user, 12.5, 0, $subscription);
    $checks('expired credit can use surviving subscription currency when log is missing', $expired->placeholders['{expired_amount}'] === $money(12.5, 'GBP'));
} finally {
    if ($order instanceof WC_Order) { $order->delete(true); }
    if ($credit_log) { wp_delete_post($credit_log, true); }
    if ($subscription) { wp_delete_post($subscription, true); }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($user);
    $result['cleanup'] = !get_userdata($user) && !get_post($subscription) && !get_post($credit_log) && !wc_get_order($order ? $order->get_id() : 0);
    $result['settings_unchanged'] = $initial_settings === get_option('arraysubs_settings');
    $result['activation_unchanged'] = $initial_plugins === get_option('active_plugins');
    $result['intercepted_mail_count'] = $mail_count;
}
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
