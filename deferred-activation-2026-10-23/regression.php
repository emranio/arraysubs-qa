<?php
/**
 * Targeted deferred-initial-activation integration regression.
 *
 * Run with WP-CLI eval-file against the local development installation.
 * Uses actual WooCommerce orders, WordPress hooks and Action Scheduler writes.
 * Every created record is removed in finally. No gateway payment is requested.
 */
defined('ABSPATH') || exit;

use ArraySubs\Features\RecurringBilling\Services\Hooks as BillingHooks;
use ArraySubs\Features\RecurringBilling\Services\OrderCreation;
use ArraySubs\Features\RecurringBilling\Services\RenewalProcessor;
use ArraySubs\Features\Subscriptions\Services\InitialPaymentContext;
use ArraySubs\Developer\Subscriptions;
use ArraySubs\Supports\ActionScheduler as Jobs;

final class DeferredActivationRegression
{
    public static array $checks = [];
    public static array $subscriptions = [];
    public static array $orders = [];
    public static array $tokens = [];
    public static array $choices = [];
    public static array $contexts = [];
    public static array $filterCalls = [];
    public static array $events = [];
    public static array $mails = [];
    public static array $remote = [];
    public static array $children = [];
    public static array $replayArgs = [];
    public static bool $mailScope = false;
    public static int $product = 0;
    public static int $customer = 0;
    public static string $run = '';

    public static function check(bool $ok, string $label, $detail = null): void
    {
        self::$checks[] = ['name' => $label, 'passed' => $ok] + ($ok ? [] : ['detail' => $detail]);
        echo ($ok ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;
        if (!$ok && $detail !== null) {
            echo wp_json_encode($detail, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }
    }

    public static function scope(callable $operation)
    {
        $previous = self::$mailScope;
        self::$mailScope = true;
        try {
            return $operation();
        } finally {
            self::$mailScope = $previous;
        }
    }

    public static function filter($status, $subscription, $order, $context)
    {
        if (!$subscription instanceof WP_Post || !in_array($subscription->ID, self::$subscriptions, true)) {
            return $status;
        }
        $id = $subscription->ID;
        self::$filterCalls[$id] = (self::$filterCalls[$id] ?? 0) + 1;
        self::$contexts[$id] = [
            'subscription' => $subscription,
            'order' => $order,
            'context' => $context,
            'default' => $status,
        ];
        return array_key_exists($id, self::$choices) ? self::$choices[$id] : $status;
    }

    public static function event(string $event, $subscription, $order = null, $context = null): void
    {
        $id = $subscription instanceof WP_Post ? $subscription->ID : (int) $subscription;
        if (!in_array($id, self::$subscriptions, true)) {
            return;
        }
        self::$events[$id][$event][] = [
            'status' => get_post_status($id),
            'order_id' => $order instanceof WC_Order ? $order->get_id() : (int) $order,
            'subscription_object' => $subscription instanceof WP_Post,
            'order_object' => $order instanceof WC_Order,
            'context' => $context,
            'payment_method' => get_post_meta($id, '_payment_method', true),
            'completed_payments' => (int) get_post_meta($id, '_completed_payments', true),
            'next_date' => get_post_meta($id, '_next_payment_date', true),
            'child_statuses' => array_map('get_post_status', self::$children[$id] ?? []),
        ];
    }

    public static function count(int $id, string $event): int
    {
        return count(self::$events[$id][$event] ?? []);
    }

    public static function order(): WC_Order
    {
        $order = wc_create_order(['customer_id' => self::$customer]);
        if (is_wp_error($order)) {
            throw new RuntimeException($order->get_error_message());
        }
        self::$orders[] = $order->get_id();
        $order->set_payment_method('bacs');
        $order->set_payment_method_title('Deferred activation regression manual payment');
        $order->set_billing_email('deferred-' . self::$run . '@example.test');
        $order->set_billing_first_name('Deferred');
        $order->set_billing_last_name('Fixture');
        $order->set_billing_city('Dhaka');
        $order->set_shipping_city('Chattogram');
        $order->set_customer_note('Provision IMEI ' . self::$run);
        $order->set_currency(get_woocommerce_currency());
        $order->set_total('10');
        $order->save();
        return $order;
    }

    public static function fixture(string $label, array $meta = [], string $status = 'arraysubs-pending'): array
    {
        return self::scope(static function () use ($label, $meta, $status) {
            $order = self::order();
            $id = wp_insert_post([
                'post_type' => 'arraysubs_data',
                'post_status' => $status,
                'post_title' => 'Deferred regression ' . self::$run . ' ' . $label,
                'meta_input' => array_merge([
                    '_customer_id' => self::$customer,
                    '_customer_email' => 'deferred-' . self::$run . '@example.test',
                    '_invoice_email' => 'deferred-' . self::$run . '@example.test',
                    '_product_id' => self::$product,
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
                    '_start_date' => gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS),
                    '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + 29 * DAY_IN_SECONDS),
                    '_parent_order_id' => $order->get_id(),
                    '_order_ids' => wp_json_encode([$order->get_id()]),
                    '_completed_payments' => 0,
                    '_payment_method' => 'bacs',
                    '_payment_method_title' => 'Manual fixture',
                ], $meta),
            ], true);
            if (is_wp_error($id)) {
                throw new RuntimeException($id->get_error_message());
            }
            self::$subscriptions[] = $id;
            $order = wc_get_order($order->get_id());
            arraysubs_link_subscription_to_parent_order($order, $id);
            return [$id, wc_get_order($order->get_id())];
        });
    }

    public static function pay(WC_Order $order, string $status = 'completed'): void
    {
        self::scope(static function () use ($order, $status) {
            $order->set_date_paid(time());
            $order->update_status($status);
        });
    }

    public static function jobs(int $id, string $hook, string $group): array
    {
        return as_get_scheduled_actions([
            'hook' => $hook, 'args' => [$id], 'group' => $group,
            'status' => 'pending', 'per_page' => 100,
        ], 'ids');
    }

    public static function hasBothJobs(int $id): bool
    {
        return count(self::jobs($id, Jobs::HOOK_PROCESS_RENEWAL, Jobs::GROUP_RENEWALS)) === 1
            && count(self::jobs($id, Jobs::HOOK_GENERATE_RENEWAL_INVOICE, Jobs::GROUP_BILLING)) === 1;
    }

    public static function noRenewalJobs(int $id): bool
    {
        return self::jobs($id, Jobs::HOOK_PROCESS_RENEWAL, Jobs::GROUP_RENEWALS) === []
            && self::jobs($id, Jobs::HOOK_GENERATE_RENEWAL_INVOICE, Jobs::GROUP_BILLING) === []
            && self::jobs($id, Jobs::HOOK_SEND_RENEWAL_REMINDER, Jobs::GROUP_EMAILS) === [];
    }

    public static function activate(int $id): void
    {
        self::scope(static fn() => Subscriptions::fromId($id)->changeStatus('arraysubs-active'));
    }

    public static function replay(int $id, WC_Order $order): void
    {
        self::scope(static function () use ($order) {
            // Simulate paid fan-out recovery after losing only the order-level marker.
            $order->read_meta_data(true);
            $order->delete_meta_data('_subscription_payment_processed');
            $order->save();
            do_action('arraysubs_order_paid', $order->get_id(), $order);
        });
    }

    public static function foreignLock(int $id, callable $operation)
    {
        $method = new ReflectionMethod(Jobs::class, 'getLockKey');
        $method->setAccessible(true);
        $key = '_transient_' . $method->invoke(null, Jobs::HOOK_SUBSCRIPTION_MUTATION, [$id]);
        if (!add_option($key, (time() + 120) . '|deferred-regression-foreign-worker', '', 'no')) {
            throw new RuntimeException('Cannot reserve isolated fixture lifecycle lock.');
        }
        try {
            return self::scope($operation);
        } finally {
            delete_option($key);
        }
    }
}

use DeferredActivationRegression as R;

$mail_capture = static function ($pre, $mail) {
    if (R::$mailScope) {
        R::$mails[] = ['to' => $mail['to'], 'subject' => $mail['subject']];
        return true;
    }
    return $pre;
};
$clock = static fn($owns, $slug, $id) => in_array((int) $id, R::$remote, true) ? true : $owns;
$capture_deferred = static fn($sub, $order, $context) => R::event('deferred', $sub, $order, $context);
$capture_activated = static fn($sub, $order = null) => R::event('activated', $sub, $order);
$capture_trial = static fn($sub) => R::event('trial', $sub);
$capture_payment = static fn($sub, $order) => R::event('payment', $sub, $order);
$capture_gateway = static fn($sub, $order, $context) => R::event('gateway', $sub, $order, $context);
$initial_plugins = get_option('active_plugins');
$initial_settings = get_option('arraysubs_settings');
$initial_user = get_current_user_id();
$pro = class_exists('ArraySubsPro', false);

add_filter('pre_wp_mail', $mail_capture, PHP_INT_MAX, 2);
add_filter('arraysubs_initial_payment_subscription_status', [R::class, 'filter'], 10, 4);
add_filter('arraysubs_gateway_owns_billing_clock', $clock, PHP_INT_MAX, 3);
add_action('arraysubs_subscription_activation_deferred', $capture_deferred, 99, 3);
add_action('arraysubs_data_activated', $capture_activated, 99, 2);
add_action('arraysubs_trial_started', $capture_trial, 99, 1);
add_action('arraysubs_gateway_payment_succeeded', $capture_payment, 99, 2);
add_action('arraysubs_gateway_context_captured', $capture_gateway, 99, 3);

try {
    if (!class_exists(InitialPaymentContext::class)) {
        throw new RuntimeException('Deferred activation implementation is not loaded yet.');
    }
    R::$run = substr(wp_generate_uuid4(), 0, 8);
    wp_set_current_user(1);
    R::$customer = wp_insert_user([
        'user_login' => 'deferred-regression-' . R::$run,
        'user_email' => 'deferred-' . R::$run . '@example.test',
        'user_pass' => wp_generate_password(30), 'role' => 'customer',
    ]);
    if (is_wp_error(R::$customer)) {
        throw new RuntimeException('Unable to create customer fixture.');
    }
    $product = new WC_Product_Simple();
    $product->set_name('Deferred activation fixture ' . R::$run);
    $product->set_status('private');
    $product->set_regular_price('10');
    $product->set_virtual(true);
    R::$product = $product->save();

    [$normal, $normal_order] = R::fixture('default');
    R::pay($normal_order);
    R::check(get_post_status($normal) === 'arraysubs-active' && R::count($normal, 'activated') === 1, 'Default paid initial order activates once');
    R::check(R::hasBothJobs($normal), 'Default activation retains one invoice and one charge job');

    foreach (['regular', 'explicit'] as $entrypoint) {
        [$locked_id, $locked_order] = R::fixture('lock contention ' . $entrypoint);
        R::$choices[$locked_id] = 'arraysubs-pending';
        $replay_args = [$locked_order->get_id(), $entrypoint === 'explicit' ? [$locked_id] : []];
        R::$replayArgs[] = $replay_args;
        if ($entrypoint === 'explicit') {
            // Establish a paid order before testing the post-paid integration entrypoint.
            $locked_order->update_meta_data('_subscription_payment_processed', 'yes');
            $locked_order->save();
            R::pay($locked_order);
            $locked_order->delete_meta_data('_subscription_payment_processed');
            $locked_order->save();
            R::foreignLock($locked_id, static fn() => do_action('arraysubs_activate_subscriptions_from_order', $locked_order, [$locked_id]));
        } else {
            R::foreignLock($locked_id, static fn() => R::pay($locked_order));
        }
        $held_order = wc_get_order($locked_order->get_id());
        R::check($held_order->get_meta('_subscription_payment_processed', true) !== 'yes' && (int) get_post_meta($locked_id, '_completed_payments', true) === 0 && (R::$filterCalls[$locked_id] ?? 0) === 0 && Jobs::isScheduled(Jobs::HOOK_REPLAY_ORDER_PAID_PROCESSING, $replay_args, Jobs::GROUP_BILLING), 'Contended subscription lock leaves payment unprocessed and queues replay: ' . $entrypoint, ['processed' => $held_order->get_meta('_subscription_payment_processed', true), 'count' => get_post_meta($locked_id, '_completed_payments', true), 'filter_calls' => R::$filterCalls[$locked_id] ?? 0]);
        R::scope(static fn() => do_action(Jobs::HOOK_REPLAY_ORDER_PAID_PROCESSING, ...$replay_args));
        R::check(get_post_status($locked_id) === 'arraysubs-pending' && (int) get_post_meta($locked_id, '_completed_payments', true) === 1 && R::count($locked_id, 'deferred') === 1 && wc_get_order($locked_order->get_id())->get_meta('_subscription_payment_processed', true) === 'yes', 'Paid replay completes exactly once after subscription lock releases: ' . $entrypoint);
        Jobs::unscheduleAll(Jobs::HOOK_REPLAY_ORDER_PAID_PROCESSING, $replay_args, Jobs::GROUP_BILLING);
    }

    [$pending, $pending_order] = R::fixture('pending');
    R::$choices[$pending] = 'arraysubs-pending';
    $token = new WC_Payment_Token_CC();
    $token->set_token('deferred-fixture-' . R::$run);
    $token->set_gateway_id('bacs');
    $token->set_user_id(R::$customer);
    $token->set_card_type('visa');
    $token->set_last4('4242');
    $token->set_expiry_month('12');
    $token->set_expiry_year((string) ((int) gmdate('Y') + 2));
    $token->save();
    R::$tokens[] = $token->get_id();
    $pending_order->add_payment_token($token);
    $pending_order->set_transaction_id('fixture-transaction-' . R::$run);
    foreach ([
        '_wc_billing/tec-logic/imei' => '000123456789012',
        '_wc_shipping/tec-logic/zero' => 0,
        '_wc_other/tec-logic/negative' => -7,
        '_wc_other/tec-logic/false' => false,
        '_wc_other/tec-logic/array' => ['accepted' => false, 'count' => 0],
        '_qa_private_secret' => 'must-not-enter-context',
        '_arraysubs_cf_imei' => 'pro-imei-' . R::$run,
    ] as $key => $value) {
        $pending_order->update_meta_data($key, $value);
    }
    $pending_order->save();
    $dates = [get_post_meta($pending, '_start_date', true), get_post_meta($pending, '_next_payment_date', true)];
    R::pay($pending_order, 'processing');
    $stored_order = wc_get_order($pending_order->get_id());
    R::check($stored_order->is_paid() && get_post_status($pending) === 'arraysubs-pending', 'Paid processing order can leave subscription pending');
    R::check((int) get_post_meta($pending, '_completed_payments', true) === 1 && get_post_meta($pending, '_last_payment_date', true) !== '', 'Deferred payment retains payment count and payment date');
    R::check((int) get_post_meta($pending, '_payment_token_id', true) === $token->get_id() && get_post_meta($pending, '_payment_method_token', true) === $token->get_token(), 'Deferred payment retains reusable payment token');
    R::check(get_post_meta($pending, '_payment_method', true) === 'bacs' && R::count($pending, 'gateway') === 1 && R::count($pending, 'payment') === 1, 'Deferred payment retains gateway capture and success event');
    R::check(R::noRenewalJobs($pending) && R::count($pending, 'activated') === 0 && R::count($pending, 'trial') === 0, 'Pending deferral emits no activation or trial event and queues no renewal work');
    $deferred_event = R::$events[$pending]['deferred'][0] ?? [];
    R::check(R::count($pending, 'deferred') === 1 && !empty($deferred_event['subscription_object']) && !empty($deferred_event['order_object']) && ($deferred_event['status'] ?? '') === 'arraysubs-pending' && ($deferred_event['completed_payments'] ?? 0) === 1, 'Deferred action receives fresh objects after persisted payment/status');
    $captured = R::$contexts[$pending] ?? [];
    $context = $captured['context'] ?? [];
    R::check(($captured['subscription'] ?? null) instanceof WP_Post && ($captured['order'] ?? null) instanceof WC_Order && ($context['gateway']['id'] ?? '') === 'bacs' && ($context['gateway']['transaction_id'] ?? '') === 'fixture-transaction-' . R::$run, 'Filter receives subscription, order and gateway context');
    $fields = $context['checkout_fields'] ?? [];
    R::check(($fields['billing']['city'] ?? '') === 'Dhaka' && ($fields['shipping']['city'] ?? '') === 'Chattogram' && ($fields['order_notes'] ?? '') === 'Provision IMEI ' . R::$run, 'Saved standard checkout fields are available without a checkout request');
    $additional = $fields['additional'] ?? [];
    $expected = [
        ['billing', 'tec-logic/imei', '_wc_billing/tec-logic/imei'],
        ['shipping', 'tec-logic/zero', '_wc_shipping/tec-logic/zero'],
        ['other', 'tec-logic/negative', '_wc_other/tec-logic/negative'],
        ['other', 'tec-logic/false', '_wc_other/tec-logic/false'],
        ['other', 'tec-logic/array', '_wc_other/tec-logic/array'],
    ];
    foreach ($expected as [$group, $field, $meta]) {
        R::check(array_key_exists($field, $additional[$group] ?? []) && $additional[$group][$field] === $stored_order->get_meta($meta, true), 'Additional checkout field preserves saved value: ' . $field, $additional[$group][$field] ?? null);
    }
    R::check(strpos(wp_json_encode($context), 'must-not-enter-context') === false, 'Context excludes arbitrary private order metadata');
    R::check($pro ? (($fields['custom']['_arraysubs_cf_imei'] ?? '') === 'pro-imei-' . R::$run) : (($fields['custom'] ?? null) === []), $pro ? 'Pro exposes saved Checkout Builder fields' : 'Core context operates without Pro custom-field helper');
    R::check($dates === [get_post_meta($pending, '_start_date', true), get_post_meta($pending, '_next_payment_date', true)], 'Deferral preserves agreed start and next-payment dates');
    R::scope(static fn() => $pending_order->update_status('completed'));
    R::replay($pending, $pending_order);
    R::check(get_post_status($pending) === 'arraysubs-pending' && (int) get_post_meta($pending, '_completed_payments', true) === 1 && R::count($pending, 'deferred') === 1 && (R::$filterCalls[$pending] ?? 0) === 1 && R::count($pending, 'payment') === 1, 'Completed transition and replay do not duplicate deferred payment effects');
    R::activate($pending);
    R::check(get_post_status($pending) === 'arraysubs-active' && !get_post_meta($pending, '_arraysubs_deferred_activation', true) && R::count($pending, 'activated') === 1 && R::hasBothJobs($pending), 'Manual pending approval clears deferral and activates with both renewal jobs');
    R::check($dates === [get_post_meta($pending, '_start_date', true), get_post_meta($pending, '_next_payment_date', true)], 'Manual approval retains the original billing date');
    R::activate($pending);
    R::replay($pending, $pending_order);
    R::check(R::count($pending, 'activated') === 1 && R::hasBothJobs($pending) && (int) get_post_meta($pending, '_completed_payments', true) === 1, 'Repeated approval and initial-order replay remain idempotent');

    [$hold, $hold_order] = R::fixture('on-hold');
    R::$choices[$hold] = 'arraysubs-on-hold';
    R::pay($hold_order);
    R::check(get_post_status($hold) === 'arraysubs-on-hold' && get_post_meta($hold, '_on_hold_date', true) !== '' && R::noRenewalJobs($hold), 'On-hold deferral retains hold date and no renewal work');
    $overdue = gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS);
    update_post_meta($hold, '_next_payment_date', $overdue);
    update_post_meta($hold, '_on_hold_date', $overdue);
    update_post_meta($hold, '_grace_days_before_on_hold', 0);
    update_post_meta($hold, '_grace_days_before_cancel', 0);
    $processor = new RenewalProcessor();
    $retry = R::scope(static fn() => $processor->process($hold));
    R::check($retry === false && get_post_status($hold) === 'arraysubs-on-hold' && !get_post_meta($hold, '_pending_renewal_order_id', true), 'Deferred on-hold cannot be charged by renewal processor');
    $limit_to_hold = static function ($sql) use ($hold) {
        if (strpos($sql, "p.post_type = 'arraysubs_data'") !== false && strpos($sql, "npd.meta_key = '_next_payment_date'") !== false && strpos($sql, 'ORDER BY p.ID ASC') !== false) {
            return str_replace('ORDER BY p.ID ASC', 'AND p.ID = ' . $hold . ' ORDER BY p.ID ASC', $sql);
        }
        return $sql;
    };
    $billing_hooks = (new ReflectionClass(BillingHooks::class))->newInstanceWithoutConstructor();
    $batch = new ReflectionMethod(BillingHooks::class, 'processOnHoldToCancelBatch');
    $batch->setAccessible(true);
    add_filter('query', $limit_to_hold, PHP_INT_MAX);
    try {
        R::scope(static fn() => $batch->invoke($billing_hooks, 0));
    } finally {
        remove_filter('query', $limit_to_hold, PHP_INT_MAX);
    }
    R::check(get_post_status($hold) === 'arraysubs-on-hold', 'Deferred on-hold is excluded from overdue cancellation');
    $approved_at = time();
    R::activate($hold);
    $next_charge = Jobs::getNextScheduledTime(Jobs::HOOK_PROCESS_RENEWAL, [$hold], Jobs::GROUP_RENEWALS);
    R::check(get_post_meta($hold, '_next_payment_date', true) === $overdue && R::hasBothJobs($hold) && $next_charge > 0 && $next_charge <= time(), 'Approval of overdue on-hold keeps due date and queues immediately runnable invoice/charge', ['charge_time' => $next_charge, 'approved_at' => $approved_at]);
    R::check(R::count($hold, 'activated') === 1 && !get_post_meta($hold, '_arraysubs_deferred_activation', true), 'On-hold approval announces initial activation exactly once');
    // Prevent a concurrent web queue runner from attempting fixture renewal later.
    Jobs::unscheduleAllForSubscription($hold);

    [$trial, $trial_order] = R::fixture('trial', ['_trial_length' => 7, '_trial_end_date' => gmdate('Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS)], 'arraysubs-trial');
    R::$choices[$trial] = 'arraysubs-pending';
    R::pay($trial_order);
    R::check(get_post_status($trial) === 'arraysubs-trial' && !get_post_meta($trial, '_arraysubs_deferred_activation', true) && R::count($trial, 'deferred') === 0 && R::count($trial, 'trial') === 1, 'Trials retain existing trial behavior when callback requests deferral');

    [$remote, $remote_order] = R::fixture('remote clock');
    R::$remote[] = $remote;
    R::$choices[$remote] = 'arraysubs-pending';
    R::pay($remote_order);
    R::check(get_post_status($remote) === 'arraysubs-active' && !get_post_meta($remote, '_arraysubs_deferred_activation', true) && R::count($remote, 'deferred') === 0, 'Remote billing clocks retain default activation');

    foreach (['publish', 'pending', false, null, ['arraysubs-pending']] as $invalid) {
        [$id, $order] = R::fixture('invalid return');
        R::$choices[$id] = $invalid;
        R::pay($order);
        R::check(get_post_status($id) === 'arraysubs-active' && R::count($id, 'deferred') === 0, 'Invalid filter return falls back to default: ' . wp_json_encode($invalid));
    }

    $normal_count = R::$filterCalls[$normal] ?? 0;
    $normal_paid = (int) get_post_meta($normal, '_completed_payments', true);
    Jobs::unscheduleAllForSubscription($normal);
    update_post_meta($normal, '_next_payment_date', gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS));
    $renewal = R::scope(static fn() => (new OrderCreation())->createRenewalOrder($normal));
    if (!$renewal instanceof WC_Order) {
        throw new RuntimeException('Unable to create fixture renewal order.');
    }
    R::$orders[] = $renewal->get_id();
    update_post_meta($normal, '_pending_renewal_order_id', $renewal->get_id());
    R::$choices[$normal] = 'arraysubs-pending';
    R::pay($renewal);
    R::check(get_post_status($normal) === 'arraysubs-active' && (R::$filterCalls[$normal] ?? 0) === $normal_count && (int) get_post_meta($normal, '_completed_payments', true) === $normal_paid + 1, 'Paid renewal bypasses initial-payment filter and advances normally', ['status' => get_post_status($normal), 'filter_calls' => R::$filterCalls[$normal] ?? 0, 'payments' => get_post_meta($normal, '_completed_payments', true), 'previous_payments' => $normal_paid, 'order_status' => wc_get_order($renewal->get_id())->get_status(), 'processed' => wc_get_order($renewal->get_id())->get_meta('_subscription_payment_processed', true)]);

    [$ended, $ended_order] = R::fixture('pending after fixed end', ['_arraysubs_fixed_end_date_enabled' => 'yes', '_end_date' => gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)]);
    R::$choices[$ended] = 'arraysubs-pending';
    R::pay($ended_order);
    $ended_result = R::scope(static fn() => Subscriptions::fromId($ended)->changeStatus('arraysubs-active'));
    R::check(is_wp_error($ended_result) && $ended_result->get_error_code() === 'deferred_activation_expired' && get_post_status($ended) === 'arraysubs-pending' && R::noRenewalJobs($ended), 'Manual pending approval refuses an elapsed fixed calendar end', is_wp_error($ended_result) ? $ended_result->get_error_code() : $ended_result);

    [$fixed, $fixed_order] = R::fixture('fixed expiry', ['_arraysubs_fixed_end_date_enabled' => 'yes', '_end_date' => gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)]);
    R::$choices[$fixed] = 'arraysubs-on-hold';
    R::pay($fixed_order);
    R::scope(static fn() => do_action(Jobs::HOOK_EXPIRE_SUBSCRIPTION, $fixed));
    R::check(get_post_status($fixed) === 'arraysubs-expired', 'Fixed calendar expiration remains authoritative during deferral');
    $expired_result = R::scope(static fn() => Subscriptions::fromId($fixed)->changeStatus('arraysubs-active'));
    R::check(is_wp_error($expired_result) && $expired_result->get_error_code() === 'deferred_activation_expired' && get_post_status($fixed) === 'arraysubs-expired' && (bool) get_post_meta($fixed, '_arraysubs_deferred_activation', true), 'An expired deferred subscription retains its marker and cannot bypass the elapsed fixed end', is_wp_error($expired_result) ? $expired_result->get_error_code() : $expired_result);

    if ($pro) {
        [$parent, $parent_order] = R::fixture('bundle parent');
        [$child, $child_order] = R::fixture('bundle child', ['_arraysubs_bundle_parent_subscription' => $parent, '_arraysubs_bundle_included' => 'yes']);
        update_post_meta($parent, '_arraysubs_bundle_child_subscriptions', wp_json_encode([$child]));
        R::$children[$parent] = [$child];
        R::$choices[$parent] = 'arraysubs-on-hold';
        R::pay($parent_order);
        $parent_deferred = R::$events[$parent]['deferred'][0] ?? [];
        R::check(get_post_status($child) === 'arraysubs-on-hold' && ($parent_deferred['child_statuses'] ?? []) === ['arraysubs-on-hold'], 'Pro bundle children mirror deferred parent before deferred action fires');
        R::activate($parent);
        $parent_activated = R::$events[$parent]['activated'][0] ?? [];
        R::check(get_post_status($child) === 'arraysubs-active' && ($parent_activated['child_statuses'] ?? []) === ['arraysubs-active'] && R::count($parent, 'activated') === 1, 'Deferred parent activation action observes completed Pro child mirroring');
    }

    R::check(get_option('active_plugins') === $initial_plugins && get_option('arraysubs_settings') === $initial_settings, 'Persistent plugin activation and settings remain unchanged');
} catch (Throwable $error) {
    R::check(false, 'Harness completes without an exception', ['message' => $error->getMessage(), 'file' => $error->getFile(), 'line' => $error->getLine()]);
} finally {
    remove_filter('arraysubs_initial_payment_subscription_status', [R::class, 'filter'], 10);
    remove_filter('arraysubs_gateway_owns_billing_clock', $clock, PHP_INT_MAX);
    remove_action('arraysubs_subscription_activation_deferred', $capture_deferred, 99);
    remove_action('arraysubs_data_activated', $capture_activated, 99);
    remove_action('arraysubs_trial_started', $capture_trial, 99);
    remove_action('arraysubs_gateway_payment_succeeded', $capture_payment, 99);
    remove_action('arraysubs_gateway_context_captured', $capture_gateway, 99);
    R::scope(static function () {
        foreach (R::$replayArgs as $replay_args) {
            Jobs::unscheduleAll(Jobs::HOOK_REPLAY_ORDER_PAID_PROCESSING, $replay_args, Jobs::GROUP_BILLING);
        }
        foreach (array_reverse(R::$subscriptions) as $id) {
            Jobs::unscheduleAllForSubscription($id);
            if (get_post($id)) {
                arraysubs_delete_subscription($id, true);
            }
        }
        foreach (array_reverse(R::$orders) as $id) {
            $order = wc_get_order($id);
            if ($order) {
                $order->delete(true);
            }
        }
        foreach (R::$tokens as $id) {
            WC_Payment_Tokens::delete($id);
        }
        if (R::$product && get_post(R::$product)) {
            wc_get_product(R::$product)->delete(true);
        }
        if (R::$customer && get_userdata(R::$customer)) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user(R::$customer);
        }
    });
    remove_filter('pre_wp_mail', $mail_capture, PHP_INT_MAX);
    wp_set_current_user($initial_user);
}

$remaining_subs = array_values(array_filter(R::$subscriptions, static fn($id) => (bool) get_post($id)));
$remaining_orders = array_values(array_filter(R::$orders, static fn($id) => (bool) wc_get_order($id)));
R::check(!$remaining_subs && !$remaining_orders && (!R::$product || !get_post(R::$product)) && (!R::$customer || !get_userdata(R::$customer)), 'All fixture subscriptions, orders, product and customer are removed', ['subscriptions' => $remaining_subs, 'orders' => $remaining_orders]);
$failed = array_values(array_filter(R::$checks, static fn($row) => !$row['passed']));
echo wp_json_encode([
    'mode' => $pro ? 'core-plus-pro' : 'core-only',
    'hpos' => \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(),
    'checks' => count(R::$checks),
    'passed' => count(R::$checks) - count($failed),
    'failed' => $failed,
    'fixture_mails_captured' => count(R::$mails),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if ($failed) {
    WP_CLI::error(count($failed) . ' deferred activation regression checks failed.');
}
