<?php
// Request-local actual-Woo regression probe. Every mail is intercepted.
$mode = $args[0] ?? 'before';
$worktree = $args[1] ?? '';
$helper_source = $args[2] ?? '';
$messages = [];
add_filter('pre_wp_mail', static function ($return, $attributes) use (&$messages) {
    $messages[] = ['to' => $attributes['to'], 'subject' => $attributes['subject'], 'message' => $attributes['message']];
    return true;
}, PHP_INT_MAX, 2);
$initial_plugins = get_option('active_plugins');
$initial_settings = get_option('arraysubs_settings');
$instance_property = new ReflectionProperty(WC_Emails::class, 'instance');
$instance_property->setAccessible(true);
$results = ['mode' => $mode, 'initial_mailer_loaded' => null !== $instance_property->getValue(), 'checks' => []];
$check = static function ($name, $condition, $details = []) use (&$results) {
    $results['checks'][] = ['name' => $name, 'pass' => (bool) $condition, 'details' => $details];
};
if ($worktree !== '') {
    if (!function_exists('arraysubs_format_plain_email_content') && $helper_source !== '') {
        $helper_text = file_get_contents($helper_source);
        $start = strpos($helper_text, 'function arraysubs_format_plain_email_content(');
        $end = strpos($helper_text, "\n}\n", $start) + 3;
        eval(substr($helper_text, $start, $end - $start));
    }
    foreach (['CreditAddedEmail', 'CreditUsedEmail', 'CreditExpiringEmail', 'CreditExpiredEmail'] as $class) {
        $fqcn = 'ArraySubsPro\\Features\\StoreCredit\\Emails\\' . $class;
        if (class_exists($fqcn, false)) {
            throw new RuntimeException('Worktree injection must happen before email classes load.');
        }
        require $worktree . '/src/Features/StoreCredit/Emails/' . $class . '.php';
    }
    ArraySubs\Supports\Config::add('proplugin.src_path', $worktree . '/src');
    require_once $worktree . '/src/Features/StoreCredit/Services/EmailManager.php';
    new ArraySubsPro\Features\StoreCredit\Services\EmailManager();
    add_filter('woocommerce_email_classes', static function ($emails) use ($worktree) {
        foreach (['Added', 'Used', 'Expiring', 'Expired'] as $kind) {
            $emails['ArraySubsPro_Credit_' . $kind . '_Email']->template_base = $worktree . '/src/Features/StoreCredit/templates/';
        }
        return $emails;
    }, 100);
    // Isolate Woo's lazy-mailer lifecycle in this request; no plugin/settings writes.
    $instance_property->setValue(null, null);
    $results['isolated_mailer_reset'] = true;
}
$targets = [
    'arraysubs_customer_credit_added', 'arraysubs_subscription_credit_added',
    'arraysubs_credits_applied_to_order', 'arraysubs_credit_expiring_soon', 'arraysubs_credit_expired',
];
foreach ($targets as $hook) {
    // Keep Woo's actual synchronous dispatch, excluding unrelated credit business work.
    remove_action($hook, [WC_Emails::class, 'queue_transactional_email'], 10);
    foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? []) as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $callable = $callback['function'];
            if (is_array($callable) && is_object($callable[0]) && str_contains(get_class($callable[0]), 'StoreCredit\\Services\\')) {
                remove_action($hook, $callable, $priority);
            }
        }
    }
    add_action($hook, [WC_Emails::class, 'send_transactional_email'], 10, 10);
}
$fixture_id = wp_insert_user([
    'user_login' => 'qa-credit-email-' . wp_generate_password(8, false, false),
    'user_pass' => wp_generate_password(32, true, true),
    'user_email' => 'qa-credit-email-' . wp_generate_password(8, false, false) . '@example.invalid',
    'display_name' => 'A & B <Credit>', 'role' => 'customer',
]);
if (is_wp_error($fixture_id)) { throw new RuntimeException($fixture_id->get_error_message()); }
$results['fixture_user_id'] = $fixture_id;
$order = null;
try {
    $order = wc_create_order(['customer_id' => $fixture_id, 'status' => 'pending']);
    $order->set_billing_email(get_userdata($fixture_id)->user_email);
    $order->save();
    $results['fixture_order_id'] = $order->get_id();
    do_action('arraysubs_customer_credit_added', $fixture_id, 12.5, 25.0, 'admin');
    $check(($worktree !== '' ? 'isolated cold' : 'initial warm') . ' raw credit action sends once', count($messages) === 1, ['count' => count($messages)]);
    $messages = [];
    do_action('arraysubs_customer_credit_added', $fixture_id, 12.5, 25.0, 'admin');
    $check('warm raw credit action sends once', count($messages) === 1, ['count' => count($messages)]);
    $messages = [];
    WC_Emails::send_queued_transactional_email('arraysubs_customer_credit_added', [$fixture_id, 12.5, 25.0, 'admin']);
    $check('actual queued notification dispatcher sends once', count($messages) === 1, ['count' => count($messages)]);
    foreach ([
        ['arraysubs_credits_applied_to_order', [$order, 12.5, null]],
        ['arraysubs_credit_expiring_soon', [$fixture_id, 12.5, gmdate('Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS), 0]],
        ['arraysubs_credit_expired', [$fixture_id, 12.5]],
    ] as [$event, $arguments]) {
        $messages = [];
        WC_Emails::send_queued_transactional_email($event, $arguments);
        $check($event . ' queued notification sends once', count($messages) === 1, ['count' => count($messages)]);
    }
    $metadata = static function ($value, $object_id, $key) use ($fixture_id) {
        return $object_id === 987654321 && $key === '_customer_id' ? $fixture_id : $value;
    };
    add_filter('get_post_metadata', $metadata, 10, 3);
    $messages = [];
    WC_Emails::send_queued_transactional_email('arraysubs_subscription_credit_added', [987654321, 12.5, 25.0, 'admin']);
    remove_filter('get_post_metadata', $metadata, 10);
    $check('subscription credit queued notification forwards customer', count($messages) === 1, ['count' => count($messages), 'subscription_context' => 'request-local metadata filter, no stored subscription']);
    $messages = [];
    foreach ($targets as $hook) {
        remove_action($hook, [WC_Emails::class, 'send_transactional_email'], 10);
    }
    $queue_seen = 0;
    add_action('arraysubs_customer_credit_added', static function () use (&$queue_seen) { ++$queue_seen; }, 10, 4);
    do_action('arraysubs_customer_credit_added', $fixture_id, 12.5, 25.0, 'admin');
    $check('deferred raw event does not send before worker', count($messages) === 0 && $queue_seen === 1, ['count' => count($messages), 'queue_observed' => $queue_seen]);
    $emails = WC()->mailer()->get_emails();
    foreach (['Added', 'Used', 'Expiring', 'Expired'] as $kind) {
        $email = $emails['ArraySubsPro_Credit_' . $kind . '_Email'];
        add_filter('woocommerce_email_additional_content_' . $email->id, static fn () => '<p>Thanks & welcome!</p>');
        if ($kind === 'Added') { $email->trigger($fixture_id, 12.5, 25.0, 'admin'); }
        if ($kind === 'Used') { $email->trigger($order, 12.5); }
        if ($kind === 'Expiring') { $email->trigger($fixture_id, 12.5, gmdate('Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS), 0); }
        if ($kind === 'Expired') { $email->trigger($fixture_id, 12.5); }
        add_filter('woocommerce_email_footer_text', static fn () => '<p>Store & Co <a href="https://example.invalid/help">Help</a></p>');
        $plain = $email->get_content_plain();
        remove_all_filters('woocommerce_email_footer_text');
        $results['rendered_plain'][$kind] = $plain;
        $check($kind . ' plaintext literal customer name', str_contains($plain, 'A & B') && !str_contains($plain, 'A &amp; B'));
        $check($kind . ' plaintext contains no footer HTML markup', !str_contains($plain, '<p>') && !str_contains($plain, '<a '));
        $check($kind . ' plaintext contains no HTML entities', !preg_match('/&(?:#\d+|#x[0-9a-f]+|[a-z]+);/i', $plain));
        if ($kind === 'Used') {
            $detail_url = $order->get_view_order_url();
            $check('real used email keeps its actual order detail URL', str_contains($plain, $detail_url) && str_contains($email->get_content_html(), esc_url($detail_url)) && $email->placeholders['{order_url}'] === $detail_url, ['url' => $detail_url]);
        }
    }
    $messages = [];
    foreach (['Added', 'Expiring', 'Expired'] as $kind) {
        $email = $emails['ArraySubsPro_Credit_' . $kind . '_Email'];
        if ($kind === 'Added') { $email->trigger(999999999, 12.5, 25.0, 'admin'); }
        if ($kind === 'Expiring') { $email->trigger(999999999, 12.5, '', 0); }
        if ($kind === 'Expired') { $email->trigger(999999999, 12.5); }
    }
    $check('missing customers produce no mail', count($messages) === 0);
    foreach (['Added' => '25.00', 'Used' => '20.00', 'Expiring' => '30.00', 'Expired' => '15.00'] as $kind => $amount) {
        $class = get_class($emails['ArraySubsPro_Credit_' . $kind . '_Email']);
        $preview = new $class();
        $preview->template_base = $emails['ArraySubsPro_Credit_' . $kind . '_Email']->template_base;
        $plain = $preview->get_content_plain();
        $html = $preview->get_content_html();
        $check($kind . ' zero-context preview uses sample customer and amount', str_contains($plain, 'Hi John Doe,') && str_contains($plain, $amount) && str_contains($html, 'John Doe') && str_contains($html, $amount));
        if ($kind === 'Used') {
            $url = wc_get_account_endpoint_url('orders');
            $check('used preview keeps sample id but links to valid Orders list', str_contains($plain, '#12345') && str_contains($plain, $url) && str_contains($html, esc_url($url)) && !str_contains($plain, '/view-order/12345') && !str_contains($html, '/view-order/12345') && $preview->placeholders['{order_url}'] === $url);
        }
        if ($kind === 'Expiring') {
            $check('expiring preview shows seven days', str_contains($plain, 'Days Remaining: 7 days'));
        }
    }
    $email = $emails['ArraySubsPro_Credit_Added_Email'];
    $email->trigger($fixture_id, 0.0, 0.0, 'admin');
    $zero = $email->get_content_plain();
    $check('real zero balances remain zero without preview values', str_contains($zero, 'Credit Added: 0.00') && str_contains($zero, 'New Balance: 0.00') && !str_contains($zero, 'John Doe'));
} finally {
    if ($order instanceof WC_Order) {
        $order_id = $order->get_id();
        $order->delete(true);
        $results['fixture_order_deleted'] = !wc_get_order($order_id);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($fixture_id);
    $results['fixture_deleted'] = !get_userdata($fixture_id);
    $results['settings_unchanged'] = $initial_settings === get_option('arraysubs_settings');
    $results['activation_unchanged'] = $initial_plugins === get_option('active_plugins');
}
echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
