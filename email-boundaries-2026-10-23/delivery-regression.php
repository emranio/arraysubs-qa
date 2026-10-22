<?php
/** Issue #92: real Woo mail delivery callbacks, intercepted before any transport. */
defined('ABSPATH') || exit;

use ArraySubs\Features\Emails\Services\EmailManager;

$checks = [];
$fixtures = ['users' => [], 'product' => 0, 'subscriptions' => [], 'orders' => []];
$original_settings = get_option('arraysubs_settings', []);
$original_plugins = get_option('active_plugins', []);
$effective_settings = arraysubs_get_settings();
$effective_settings['emails']['renewal_reminder_rate_limit'] = 0;
foreach (arraysubs_get_email_setting_map() as $setting) {
    $pointer =& $effective_settings;
    foreach (explode('.', $setting) as $part) { $pointer =& $pointer[$part]; }
    $pointer = true;
    unset($pointer);
}
$settings_filter = static function () use (&$effective_settings) { return $effective_settings; };
$freeze_settings = static function ($value, $old) { return $old; };
$failure = null;
$mail_log = [];
$mail_id = '';
$transport = false;
$nested = null;
$nested_busy = false;
$fail_ids = [];
$email_state = [];
$callback_params = static function ($params, $email) use (&$mail_id) { $mail_id = (string) $email->id; return $params; };
$mail_interceptor = static function ($pre, $atts) use (&$mail_log, &$mail_id, &$transport, &$nested, &$nested_busy, &$fail_ids) {
    $entry = ['id' => $mail_id, 'to' => $atts['to'], 'subject' => $atts['subject'], 'message' => $atts['message']];
    $mail_log[] = $entry;
    if ($nested && !$nested_busy) {
        $nested_busy = true;
        try { $nested(); } finally { $nested_busy = false; }
        return false;
    }
    return $transport && !in_array($entry['id'], $fail_ids, true);
};
$candidate = in_array('candidate-templates', $args ?? [], true);
$template_filter = static function ($template, $name) use ($candidate) {
    $base = '/tmp/arraysubs-email-delivery/src/Features/Emails/templates/';
    if ($candidate && str_contains($template, '/Features/Emails/templates/') && is_file($base . $name)) {
        return $base . $name;
    }
    return $template;
};
$check = static function (bool $ok, string $label) use (&$checks): void {
    $checks[] = ['check' => $label, 'passed' => $ok];
    if (!$ok) { throw new RuntimeException($label); }
};
$count = static function (string $id) use (&$mail_log): int {
    return count(array_filter($mail_log, static fn($mail) => $mail['id'] === 'arraysubs_' . $id));
};

try {
    add_filter('pre_wp_mail', $mail_interceptor, PHP_INT_MAX, 2);
    add_filter('woocommerce_mail_callback_params', $callback_params, PHP_INT_MAX, 2);
    add_filter('woocommerce_locate_template', $template_filter, PHP_INT_MAX, 2);
    add_filter('pre_option_arraysubs_settings', $settings_filter);
    add_filter('pre_update_option_arraysubs_settings', $freeze_settings, PHP_INT_MAX, 2);
    $manager = null;
    foreach (($GLOBALS['wp_filter']['arraysubs_trial_started']->callbacks ?? []) as $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && $callback['function'][0] instanceof EmailManager) { $manager = $callback['function'][0]; }
        }
    }
    $check($manager instanceof EmailManager, 'Normal core boot registers EmailManager');
    $emails = WC()->mailer()->emails;
    foreach ($emails as $key => $email) {
        if (!str_starts_with($key, 'arraysubs_')) { continue; }
        $email_state[$key] = ['enabled' => $email->enabled, 'settings' => $email->settings, 'email_type' => $email->email_type];
        $email->enabled = 'yes';
        $email->email_type = 'html';
        $email->settings['recipient'] = 'delivery-admin@example.invalid';
    }
    $product = new WC_Product_Simple();
    $product->set_name('Email delivery QA manual product');
    $product->set_status('draft');
    $product->set_regular_price('19');
    $fixtures['product'] = $product->save();
    foreach (['first', 'second'] as $kind) {
        $token = strtolower(wp_generate_password(12, false, false));
        $user = wp_insert_user(['user_login' => 'email-delivery-' . $token, 'user_email' => $token . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        $check(!is_wp_error($user), 'Disposable ' . $kind . ' customer created');
        $fixtures['users'][] = $user;
        $subscription = arraysubs_create_subscription([
            'customer_id' => $user, 'product_id' => $fixtures['product'], 'status' => 'arraysubs-active',
            'recurring_amount' => 19, 'payment_method' => 'bacs', 'payment_method_title' => 'QA manual; never charged',
            'next_payment_date' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS),
        ]);
        $check(!is_wp_error($subscription) && $subscription > 0, 'Disposable ' . $kind . ' manual subscription created');
        $fixtures['subscriptions'][] = $subscription;
        $order = wc_create_order(['customer_id' => $user, 'status' => 'pending']);
        $order->set_payment_method('bacs');
        $order->set_billing_email(get_userdata($user)->user_email);
        $order->set_total(19);
        $order->update_meta_data('_subscription_id', $subscription);
        $order->update_meta_data('_is_renewal_order', 'yes');
        $order->save();
        $fixtures['orders'][] = $order->get_id();
    }
    [$sub, $other_sub] = $fixtures['subscriptions'];
    [$order_id, $other_order_id] = $fixtures['orders'];
    $mail_log = [];
    $check(!$manager->trigger('new_subscription', $sub), 'Failed mail transport is reported as false');
    $check(!EmailManager::sendSubscriptionEmail('new_subscription', $sub), 'Shared static delivery contract reports failed transport');
    $transport = true;
    $check($manager->trigger('new_subscription', $sub), 'Successful intercepted transport is reported as true');
    $check(EmailManager::sendSubscriptionEmail('new_subscription', $sub), 'Shared static delivery contract reuses the normally booted manager');
    if (in_array('integrated-helper', $args ?? [], true)) {
        $transport = false;
        $check(!arraysubs_send_subscription_email('new_subscription', $sub), 'Public helper reports failed transport through the shared contract');
        $transport = true;
        $check(arraysubs_send_subscription_email('new_subscription', $sub), 'Public helper reports successful intercepted delivery');
    }
    $email = $emails['arraysubs_new_subscription'];
    $before = count($mail_log);
    $email->enabled = 'no';
    $check(!$manager->trigger('new_subscription', $sub) && count($mail_log) === $before, 'Woo-disabled email returns false without mail');
    $email->enabled = 'yes';
    $effective_settings['emails']['new_subscription']['enabled'] = false;
    $check(!$manager->trigger('new_subscription', $sub) && count($mail_log) === $before, 'Feature-disabled email returns false without mail');
    $effective_settings['emails']['new_subscription']['enabled'] = true;
    $check(!$manager->trigger('new_subscription', 0) && count($mail_log) === $before, 'Missing subscription returns false without mail');
    $recipient_filter = static fn() => '';
    add_filter('woocommerce_email_recipient_arraysubs_new_subscription', $recipient_filter);
    $check(!$manager->trigger('new_subscription', $sub) && count($mail_log) === $before, 'Missing recipient returns false without mail');
    remove_filter('woocommerce_email_recipient_arraysubs_new_subscription', $recipient_filter);

    foreach (['invoice', 'success', 'trial', 'reminder'] as $kind) {
        $transport = false;
        $marker = match ($kind) {
            'invoice' => '_arraysubs_renewal_invoice_email_sent', 'success' => '_arraysubs_renewal_payment_success_email_sent',
            'trial' => '_arraysubs_trial_started_email_sent', 'reminder' => '_arraysubs_renewal_reminder_sent_for',
        };
        $invoke = static function () use ($manager, $kind, $sub, $order_id): void {
            match ($kind) {
                'invoice' => $manager->on_renewal_invoice_created($sub, $order_id),
                'success' => $manager->on_renewal_payment_complete($sub, $order_id),
                'trial' => $manager->on_trial_started($sub),
                'reminder' => $manager->send_renewal_reminder($sub, 3),
            };
        };
        $read_marker = static fn() => in_array($kind, ['invoice', 'success'], true)
            ? wc_get_order($order_id)->get_meta($marker, true) : get_post_meta($sub, $marker, true);
        $invoke();
        $check($read_marker() === '', $kind . ' failed delivery leaves its sent marker unset');
        $transport = true;
        $invoke();
        $check($read_marker() !== '', $kind . ' retry records delivery only after success');
        $before = count($mail_log);
        $invoke();
        $check(count($mail_log) === $before, $kind . ' successful email is deduped');
    }

    update_post_meta($sub, '_cancellation_scheduled_date', gmdate('Y-m-d H:i:s', time() + 20 * DAY_IN_SECONDS));
    $fail_ids = ['arraysubs_subscription_pending_cancellation'];
    $customer_before = $count('subscription_pending_cancellation');
    $admin_before = $count('admin_subscription_pending_cancellation');
    $manager->on_waiting_cancellation($sub);
    $check(get_post_meta($sub, '_arraysubs_pending_cancel_email_sent_for', true) === '', 'Partial pending-cancellation failure does not finalize both deliveries');
    $fail_ids = [];
    $manager->on_waiting_cancellation($sub);
    $check($count('subscription_pending_cancellation') === $customer_before + 2 && $count('admin_subscription_pending_cancellation') === $admin_before + 1, 'Pending-cancellation retry sends only its unsuccessful recipient');
    $before = count($mail_log);
    $manager->on_waiting_cancellation($sub);
    $check(count($mail_log) === $before && get_post_meta($sub, '_arraysubs_pending_cancel_email_sent_for', true) !== '', 'Both delivered pending-cancellation emails are deduped');
    $manager->on_cancellation_undone($sub);
    $check(get_post_meta($sub, '_arraysubs_pending_cancel_email_sent_for', true) === '' && get_post_meta($sub, '_arraysubs_pending_cancel_email_deliveries', true) === '', 'Undo clears combined and per-recipient delivery markers');

    $inner_result = null;
    foreach (['new_subscription', 'subscription_cancelled'] as $inner_id) {
        $nested = static function () use ($manager, $inner_id, $other_sub, &$inner_result) { $inner_result = $manager->trigger($inner_id, $other_sub); };
        $check(!$manager->trigger('new_subscription', $sub) && $inner_result === true, 'Nested ' . $inner_id . ' success cannot finalize an unsuccessful outer email');
        $nested = null;
    }
    $check($manager->trigger('new_subscription', $sub), 'Outer singleton remains usable after nested deliveries');

    $transport = false;
    $attempt = 'qa-delivery-attempt-' . wp_generate_uuid4();
    $customer_before = $count('payment_failed');
    $admin_before = $count('admin_payment_failed');
    $manager->on_gateway_payment_failed($sub, $order_id, 'QA; no gateway invoked', 'bacs', $attempt);
    $transport = true;
    $manager->on_gateway_payment_failed($sub, $order_id, 'QA; no gateway invoked', 'bacs', $attempt);
    $check($count('payment_failed') === $customer_before + 2 && $count('admin_payment_failed') === $admin_before + 2, 'Exact failure ledger releases failed delivery claims for retry');
    $before = count($mail_log);
    $manager->on_gateway_payment_failed($sub, $order_id, 'QA; no gateway invoked', 'bacs', $attempt);
    $check(count($mail_log) === $before, 'Exact failure ledger still dedupes successful customer/admin effects');

    foreach (['admin_subscription_cancelled', 'admin_subscription_pending_cancellation', 'admin_payment_failed'] as $id) {
        foreach (['html', 'plain'] as $format) {
            $emails['arraysubs_' . $id]->email_type = $format;
            $before = count($mail_log);
            $check($manager->trigger($id, $sub, $id === 'admin_payment_failed' ? ['order_id' => $order_id] : []), $id . ' ' . $format . ' renders and reaches intercepted transport');
            $message = $mail_log[$before]['message'];
            $check(str_contains($message, 'page=arraysubs-mainadmin#/subscriptions/detail/' . $sub) && !str_contains($message, 'page=arraysubs#/'), $id . ' ' . $format . ' uses the registered core details page');
        }
    }
} catch (Throwable $error) {
    $failure = get_class($error) . ': ' . $error->getMessage();
} finally {
    $nested = null;
    $transport = false;
    foreach ($email_state as $key => $state) {
        foreach ($state as $property => $value) { $emails[$key]->$property = $value; }
    }
    foreach ($fixtures['orders'] as $id) { $order = wc_get_order($id); if ($order) { $order->delete(true); } }
    foreach ($fixtures['subscriptions'] as $id) { wp_delete_post($id, true); }
    if ($fixtures['product']) { wp_delete_post($fixtures['product'], true); }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($fixtures['users'] as $id) { wp_delete_user($id); }
    remove_filter('pre_wp_mail', $mail_interceptor, PHP_INT_MAX);
    remove_filter('woocommerce_mail_callback_params', $callback_params, PHP_INT_MAX);
    remove_filter('woocommerce_locate_template', $template_filter, PHP_INT_MAX);
    remove_filter('pre_option_arraysubs_settings', $settings_filter);
    remove_filter('pre_update_option_arraysubs_settings', $freeze_settings, PHP_INT_MAX);
}
$settings_unchanged = get_option('arraysubs_settings', []) === $original_settings;
$plugins_unchanged = get_option('active_plugins', []) === $original_plugins;
echo wp_json_encode([
    'manager_source' => (new ReflectionClass(EmailManager::class))->getFileName(),
    'candidate_templates' => $candidate, 'checks' => $checks, 'failure' => $failure,
    'mail_intercepted_count' => count($mail_log), 'external_mail_sent' => 0,
    'deleted_fixtures' => $fixtures, 'saved_settings_unchanged' => $settings_unchanged,
    'active_plugins_unchanged' => $plugins_unchanged,
], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failure || !$settings_unchanged || !$plugins_unchanged) { WP_CLI::halt(1); }
