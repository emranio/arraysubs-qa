<?php
/** Run with wp eval-file. Temporary fixtures are removed in finally. */

use ArraySubs\Supports\ActionScheduler;

$mode = $args[0] ?? 'regression';
if ('worker' === $mode) {
    $result = arraysubs_increment_feature_usage((int) $args[1], 'Seats', 1, ['operation_id' => (string) $args[2]]);
    if (is_wp_error($result)) {
        WP_CLI::error($result->get_error_code());
    }
    WP_CLI::line('OK');
    return;
}
if ('cleanup' === $mode) {
    $fixture = json_decode(file_get_contents($args[1]), true);
    wp_delete_post($fixture['subscription_id'], true);
    wp_delete_post($fixture['product_id'], true);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($fixture['customer_id']);
    WP_CLI::success('Concurrency fixtures removed.');
    return;
}
if ('check-concurrency' === $mode) {
    $fixture = json_decode(file_get_contents($args[1]), true);
    $usage = arraysubs_get_feature_usage($fixture['subscription_id'], 'Seats');
    if (20.0 !== $usage['used'] || 20.0 !== $usage['limit']) {
        WP_CLI::error('Expected exactly 20 used out of 20: ' . wp_json_encode($usage));
    }
    WP_CLI::success('Twenty parallel increments recorded exactly 20; quota stayed at 20.');
    return;
}

$ids = [];
$order_ids = [];
$user_id = wp_insert_user(['user_login' => 'feature-usage-' . wp_generate_password(8, false), 'role' => 'customer', 'user_pass' => wp_generate_password(30)]);
$old_timezone = get_option('timezone_string');
$old_week_start = get_option('start_of_week');
$old_user = get_current_user_id();
$checks = 0;
$assert = static function ($condition, string $message) use (&$checks) {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$features = static function ($limit, string $period = 'none', string $region = 'EU', string $priority = 'Yes') {
    return arraysubs_sanitize_product_features([
        ['name' => 'Seats', 'type' => 'number', 'value' => (string) $limit, 'enabled' => true, 'reset_period' => $period],
        ['name' => 'Region', 'type' => 'text', 'value' => $region, 'enabled' => true, 'reset_period' => 'day'],
        ['name' => 'Priority', 'type' => 'toggle', 'value' => $priority, 'enabled' => true],
    ]);
};
$product = static function (array $rows) use (&$ids) {
    $product = new WC_Product_Simple();
    $product->set_name('Temporary feature usage regression');
    $product->set_status('private');
    $id = $product->save();
    $ids[] = $id;
    update_post_meta($id, '_arraysubs_features', wp_json_encode($rows));
    return $id;
};
$subscription = static function (int $product_id) use (&$ids, $user_id) {
    $id = wp_insert_post([
        'post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active',
        'post_title' => 'Temporary feature usage regression',
        'meta_input' => ['_customer_id' => $user_id, '_product_id' => $product_id, '_start_date' => '2026-01-01 00:00:00'],
    ]);
    $ids[] = $id;
    return $id;
};

try {
    $a_product = $product($features(5));
    $a = $subscription($a_product);
    if ('setup-concurrency' === $mode) {
        update_post_meta($a_product, '_arraysubs_features', wp_json_encode($features(20, 'day')));
        update_post_meta($a, arraysubs_get_feature_usage_meta_key('Seats'), ['used' => 999, 'period_key' => 'day:1900-01-01', 'ops' => []]);
        file_put_contents($args[1], wp_json_encode(['subscription_id' => $a, 'product_id' => $a_product, 'customer_id' => $user_id]));
        $ids = [];
        $user_id = 0;
        WP_CLI::success('Concurrency fixtures prepared.');
        return;
    }
    $b_product = $product($features(10, 'none', 'US-East', 'No'));
    $b = $subscription($b_product);
    foreach (['MAX' => 'max', ' max ' => 'max', 'Combine' => 'combine', 'sum' => 'per_subscription', 'highest' => 'per_subscription', 'per_product' => 'per_subscription'] as $input => $expected) {
        $assert(arraysubs_sanitize_feature_manager_aggregation_mode($input) === $expected, 'Aggregation vocabulary: ' . $input);
    }
    $assert($features(5)[1]['reset_period'] === 'none', 'Non-numeric periods normalize to none.');
    $assert(arraysubs_get_feature_usage_meta_key('Seats') !== arraysubs_get_feature_usage_meta_key('seats'), 'Exact feature names do not collide.');
    $assert(arraysubs_get_feature_usage_meta_key('A-B') !== arraysubs_get_feature_usage_meta_key('AB'), 'Punctuation does not collide.');
    $max = array_column(arraysubs_get_customer_features($user_id, 'max'), null, 'name');
    $sum = array_column(arraysubs_get_customer_features($user_id, 'combine'), null, 'name');
    $assert((float) $max['Seats']['value'] === 10.0 && (float) $sum['Seats']['value'] === 15.0, 'Max and combine allowances.');
    $assert($max['Region']['value'] === 'US-East' && $max['Priority']['value'] === 'Yes', 'Text and toggle aggregation.');
    $mixed = arraysubs_aggregate_feature(['type' => 'number', 'value' => '10'], ['type' => 'text', 'value' => 'Earth'], 'combine');
    $assert($mixed['type'] === 'text' && $mixed['value'] === 'Earth', 'Mixed types are not added as numbers.');
    arraysubs_update_feature_usage($a, 'Seats', 2);
    arraysubs_update_feature_usage($b, 'Seats', 7);
    $max = arraysubs_get_customer_feature_usage($user_id, 'Seats', 'max');
    $combined = arraysubs_get_customer_feature_usage($user_id, 'Seats', 'combine');
    $assert($max['source_subscription_id'] === $b && $max['used'] === 7.0 && $max['remaining'] === 3.0, 'Max uses the winner usage.');
    $assert($combined['used'] === 9.0 && $combined['limit'] === 15.0 && $combined['remaining'] === 6.0, 'Combine adds usage and limits.');
    $assert(!arraysubs_is_customer_feature_usage_allowed($user_id, 'Seats', 1, 'per_subscription'), 'Per-subscription requires an ID.');
    $failed = arraysubs_consume_customer_feature_usage($user_id, 'Seats', 7, ['aggregation_mode' => 'combine']);
    $assert(is_wp_error($failed) && arraysubs_get_feature_usage($a, 'Seats')['used'] === 2.0, 'Oversized customer consume changes nothing.');
    $reject_second_write = static function ($check, $id, $key) use ($b) {
        return $id === $b && $key === arraysubs_get_feature_usage_meta_key('Seats') ? false : $check;
    };
    add_filter('update_post_metadata', $reject_second_write, 10, 3);
    $rolled_back = arraysubs_consume_customer_feature_usage($user_id, 'Seats', 4, ['aggregation_mode' => 'combine', 'operation_id' => 'rolled-back']);
    remove_filter('update_post_metadata', $reject_second_write);
    $assert(is_wp_error($rolled_back) && arraysubs_get_feature_usage($a, 'Seats')['used'] === 2.0 && arraysubs_get_feature_usage($b, 'Seats')['used'] === 7.0, 'A failed second write rolls back the first subscription.');
    $consumed = arraysubs_consume_customer_feature_usage($user_id, 'Seats', 4, ['aggregation_mode' => 'combine', 'operation_id' => 'combined-once']);
    $assert(!is_wp_error($consumed) && arraysubs_get_feature_usage($a, 'Seats')['used'] === 5.0 && arraysubs_get_feature_usage($b, 'Seats')['used'] === 8.0, 'Customer consume allocates lowest ID on equal reset.');
    arraysubs_consume_customer_feature_usage($user_id, 'Seats', 4, ['aggregation_mode' => 'combine', 'operation_id' => 'combined-once']);
    $assert(arraysubs_get_customer_feature_usage($user_id, 'Seats')['used'] === 13.0, 'Customer operation replay applies once.');
    $denied = arraysubs_increment_feature_usage($a, 'Seats', 1, ['allow_overage' => true]);
    $assert(is_wp_error($denied), 'Anonymous overage cannot bypass the limit.');
    $admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    wp_set_current_user((int) ($admin[0] ?? 0));
    $assert(!is_wp_error(arraysubs_increment_feature_usage($a, 'Seats', 1, ['allow_overage' => true])), 'Privileged overage is explicit.');
    $assert(!is_wp_error(arraysubs_increment_feature_usage($a, 'Seats', -100)) && arraysubs_get_feature_usage($a, 'Seats')['used'] === 0.0, 'Usage never falls below zero.');
    arraysubs_increment_feature_usage($a, 'Seats', 1, ['operation_id' => 'once']);
    arraysubs_increment_feature_usage($a, 'Seats', 1, ['operation_id' => 'once']);
    $assert(arraysubs_get_feature_usage($a, 'Seats')['used'] === 1.0, 'Single mutation replay applies once.');
    update_post_meta($a_product, '_arraysubs_features', wp_json_encode($features(20, 'day')));
    $meta_key = arraysubs_get_feature_usage_meta_key('Seats');
    $stored = get_post_meta($a, $meta_key, true);
    $assert(arraysubs_get_feature_usage($a, 'Seats')['used'] === 0.0, 'Changing the period starts fresh.');
    $assert(get_post_meta($a, $meta_key, true) === $stored, 'Reading a rollover never writes.');
    arraysubs_increment_feature_usage($a, 'Seats', 1, ['operation_id' => 'once']);
    $assert(get_post_meta($a, $meta_key, true) === $stored, 'Operation IDs survive period changes.');
    arraysubs_increment_feature_usage($a, 'Seats', 3, ['operation_id' => 'new-day']);
    $assert(arraysubs_get_feature_usage($a, 'Seats')['used'] === 3.0, 'First write commits current period.');
    update_post_meta($a_product, '_arraysubs_features', wp_json_encode($features(30, 'day')));
    $assert(arraysubs_get_feature_usage($a, 'Seats')['used'] === 3.0, 'Plan changes with the same period carry usage.');
    update_post_meta($b_product, '_arraysubs_features', wp_json_encode($features('Unlimited')));
    $unlimited = arraysubs_get_customer_feature_usage($user_id, 'Seats', 'max');
    $assert($unlimited['source_subscription_id'] === $b && null === $unlimited['limit'] && null === $unlimited['remaining'], 'Unlimited wins max and uses null.');
    update_post_meta($b_product, '_arraysubs_features', '[]');
    $assert(!arraysubs_get_feature_usage($b, 'Seats')['defined'] && count(arraysubs_get_customer_feature_usage($user_id, 'Seats')['per_subscription']) === 1, 'Removed features exclude stale usage.');
    update_option('timezone_string', 'America/New_York');
    update_option('start_of_week', 1);
    $before = arraysubs_get_calendar_period_bounds('day', strtotime('2026-03-08 04:59:59 UTC'));
    $after = arraysubs_get_calendar_period_bounds('day', strtotime('2026-03-08 05:00:00 UTC'));
    $assert($before['period_key'] === 'day:2026-03-07' && $after['period_key'] === 'day:2026-03-08', 'Calendar boundary follows local midnight.');
    $assert($after['resets_at'] - $after['period_start'] === 23 * 3600, 'DST day is 23 hours.');
    $week = arraysubs_get_calendar_period_bounds('week', strtotime('2026-10-18 12:00:00 UTC'));
    $assert($week['period_key'] === 'week:2026-10-12', 'Week follows Monday start.');
    update_option('start_of_week', 0);
    $assert(arraysubs_get_calendar_period_bounds('week', strtotime('2026-10-18 12:00:00 UTC'))['period_key'] === 'week:2026-10-18', 'Week follows Sunday start.');
    update_post_meta($a_product, '_arraysubs_features', wp_json_encode($features(20, 'billing_cycle')));
    arraysubs_increment_feature_usage($a, 'Seats', 2);
    $order = wc_create_order(['customer_id' => $user_id, 'status' => 'completed']);
    $order_ids[] = $order->get_id();
    $assert(arraysubs_get_feature_usage($a, 'Seats')['period_key'] === 'cycle:0', 'Initial cycle has a stable zero marker.');
    update_post_meta($a, '_current_cycle_order_id', $order->get_id());
    $assert(arraysubs_get_feature_usage($a, 'Seats')['used'] === 0.0, 'Paid cycle marker rolls usage.');
    arraysubs_increment_feature_usage($a, 'Seats', 2);
    update_post_meta($a, '_next_payment_date', '2027-01-01 00:00:00');
    $assert(arraysubs_get_feature_usage($a, 'Seats')['used'] === 2.0, 'Date edits alone do not reset billing usage.');
    update_post_meta($a_product, '_arraysubs_features', wp_json_encode($features(20, 'none')));
    update_post_meta($b_product, '_arraysubs_features', wp_json_encode($features(10, 'day')));
    $soonest = arraysubs_consume_customer_feature_usage($user_id, 'Seats', 1, ['aggregation_mode' => 'combine']);
    $assert(!is_wp_error($soonest) && arraysubs_get_feature_usage($a, 'Seats')['used'] === 0.0 && arraysubs_get_feature_usage($b, 'Seats')['used'] === 1.0, 'Customer consumption spends the soonest reset before an older ID with no reset.');
    update_post_meta($b_product, '_arraysubs_features', '[]');
    update_post_meta($a_product, '_arraysubs_features', wp_json_encode($features('0.3', 'none')));
    arraysubs_consume_feature_usage($a, 'Seats', 0.1);
    $decimal = arraysubs_consume_feature_usage($a, 'Seats', 0.2);
    $assert(!is_wp_error($decimal) && $decimal['used'] === 0.3, 'Decimal consumption fits its exact allowance without floating point overage.');
    $assert(is_wp_error(arraysubs_consume_feature_usage($a, 'Seats', 0.00000000001)), 'Tiny consumption cannot bypass an exhausted allowance.');
    $hook_fired = false;
    $callback = static function ($subscription) use (&$hook_fired, $a, $meta_key, $assert) {
        $assert($subscription instanceof WP_Post, 'Usage hook receives WP_Post.');
        $assert(!ActionScheduler::isLocked(ActionScheduler::HOOK_FEATURE_USAGE_MUTATION, [$a, $meta_key]), 'Usage hook runs after release.');
        $hook_fired = true;
    };
    add_action('arraysubs_feature_usage_changed', $callback);
    arraysubs_reset_feature_usage($a, 'Seats');
    remove_action('arraysubs_feature_usage_changed', $callback);
    $assert($hook_fired, 'Usage change notification fires.');
    WP_CLI::success($checks . ' feature usage assertions passed.');
} finally {
    wp_set_current_user($old_user);
    update_option('timezone_string', $old_timezone);
    update_option('start_of_week', $old_week_start);
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->delete(true);
        }
    }
    foreach (array_reverse($ids) as $id) {
        wp_delete_post($id, true);
    }
    if ($user_id) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
    }
}
