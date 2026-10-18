<?php
defined('ABSPATH') || exit;
/** Run with wp eval-file; creates isolated fixtures and removes them afterwards. */

use ArraySubs\Features\MembersAccess\Services\ConditionEvaluator;
use ArraySubs\Features\MembersAccess\Services\PurchaseHistory;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;

$checks = 0;
$assert = static function ($actual, $expected, string $message) use (&$checks): void {
    ++$checks;
    if ($actual !== $expected) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
};
$invoke = static function ($object, string $method, ...$args) {
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);
    return $reflection->invoke($object, ...$args);
};
$ids = [];
$order_ids = [];
$user_id = 0;
$original_user = get_current_user_id();
$history = new PurchaseHistory();
try {
    $passing = ['type' => 'user_login_status', 'login_status' => 'logged_in'];
    $failing = ['type' => 'user_login_status', 'login_status' => 'logged_out'];
    foreach (['AND', ' and ', '', null, 'xor', [], 1] as $logic) {
        $assert(arraysubs_normalize_condition_logic($logic), 'and', 'Unknown logic defaults to AND');
        $assert(arraysubs_user_meets_conditions(1, ['logic' => $logic, 'rules' => [$passing, $failing]]), false, 'AND requires every rule');
    }
    foreach (['OR', ' Or ', 'or'] as $logic) {
        $assert(arraysubs_user_meets_conditions(1, ['logic' => $logic, 'rules' => [$passing, $failing]]), true, 'OR accepts one passing rule');
    }
    $nested = ['logic' => 'AND', 'rules' => [['type' => 'group', 'id' => 'keep', 'logic' => ' OR ', 'rules' => [$passing, $failing]]]];
    $assert(arraysubs_user_meets_conditions(1, $nested), true, 'Nested groups normalize independently');
    $assert(arraysubs_normalize_conditions($nested)['rules'][0]['id'], 'keep', 'Group IDs are retained');
    $assert(PostRestrictionConfig::sanitize(['condition' => 'OR'])['condition'], 'or', 'Post config normalizes uppercase logic');
    $assert($invoke(new ArraySubs\Features\MembersAccess\Services\ScheduleEvaluator(), 'normalizeConditions', $nested)['rules'][0]['logic'], 'or', 'Schedule evaluation uses normalized nested conditions');
    $sanitizers = [
        new ArraySubs\Features\MembersAccess\REST\RestrictionController(false),
        new ArraySubs\Features\MemberStyling\REST\StyleController(false),
    ];
    if (class_exists(ArraySubsPro\Features\MultiLoginPrevention\Services\SettingsExtender::class)) {
        $sanitizers[] = new ArraySubsPro\Features\MultiLoginPrevention\Services\SettingsExtender();
    }
    foreach ($sanitizers as $sanitizer) {
        $normalized = $invoke($sanitizer, 'sanitizeConditions', $nested);
        $assert($normalized['logic'], 'and', get_class($sanitizer) . ' AND save');
        $assert($normalized['rules'][0]['logic'], 'or', get_class($sanitizer) . ' nested OR save');
        $assert($invoke($sanitizer, 'sanitizeConditions', ['rules' => []])['logic'], 'and', 'Missing logic saves AND');
    }

    $user_id = wp_insert_user(['user_login' => 'dev-condition-' . wp_generate_password(10, false), 'user_email' => 'condition-' . wp_generate_password(10, false) . '@example.invalid', 'user_pass' => wp_generate_password(), 'role' => 'customer']);
    if (is_wp_error($user_id)) {
        throw new RuntimeException($user_id->get_error_message());
    }
    wp_set_current_user($user_id);
    $assert(str_contains(do_shortcode('[arraysubs_restrict status="active" roles="customer" condition="OR" show_to_admins="false"]CONDITION_PASS[/arraysubs_restrict]'), 'CONDITION_PASS'), true, 'Uppercase shortcode OR grants via role');
    $assert(str_contains(do_shortcode('[arraysubs_restrict status="active" roles="customer" condition="AND" show_to_admins="false"]CONDITION_PASS[/arraysubs_restrict]'), 'CONDITION_PASS'), false, 'Uppercase shortcode AND denies without subscription');

    $parent = new WC_Product_Variable();
    $parent->set_name('Developer condition regression');
    $parent->set_status('publish');
    $parent_id = $parent->save();
    $ids[] = $parent_id;
    $v1 = new WC_Product_Variation();
    $v1->set_parent_id($parent_id);
    $v1->set_regular_price('10');
    $v1_id = $v1->save();
    $ids[] = $v1_id;
    $v2 = new WC_Product_Variation();
    $v2->set_parent_id($parent_id);
    $v2->set_regular_price('10');
    $v2_id = $v2->save();
    $ids[] = $v2_id;
    $row = static fn(string $name, string $type, $value) => ['id' => sanitize_key($name), 'name' => $name, 'type' => $type, 'value' => $value, 'enabled' => true];
    update_post_meta($parent_id, '_arraysubs_features', wp_json_encode([$row('Seats', 'number', 5), $row('Priority', 'toggle', 'Yes'), $row('Parent only', 'number', 99)]));
    update_post_meta($v1_id, '_arraysubs_features', wp_json_encode([$row('Seats', 'number', 10), $row('Priority', 'toggle', 'No'), $row('Region', 'text', 'EU')]));
    update_post_meta($v2_id, '_arraysubs_features', '[]');
    $subscription_id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Developer condition regression']);
    $ids[] = $subscription_id;
    update_post_meta($subscription_id, '_customer_id', $user_id);
    update_post_meta($subscription_id, '_product_id', $parent_id);
    update_post_meta($subscription_id, '_variation_id', $v1_id);

    $evaluator = new ConditionEvaluator();
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats'), 10, 'Variation replaces parent feature list');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Parent only'), 0, 'Variation omission does not inherit individual row');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Priority', 'sum'), 0, 'Toggle No is numeric zero');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Priority', 'max'), 0, 'Toggle No cannot satisfy numeric minimum');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Priority', 'any'), false, 'Overridden parent Yes cannot grant access');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Region', 'sum'), 0, 'Text does not add to numeric sums');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Region', 'any'), true, 'Non-empty text counts as presence');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats', ' MAX '), 10, 'Aggregation names ignore case/space');

    $order = wc_create_order(['customer_id' => $user_id]);
    $order->add_product($v1, 1);
    $parent_line = new WC_Order_Item_Product();
    $parent_line->set_product_id($parent_id);
    $parent_line->set_name('Ambiguous parent-only manual-payment line');
    $order->add_item($parent_line);
    $order->set_status('completed');
    $order->save();
    $order_ids[] = $order->get_id();
    $history->clearCache($user_id);
    $assert(count($history->getPurchasedItems($user_id)), 2, 'Pair cache preserves parent and variation separately');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats'), 10, 'Purchase and subscription dedupe same variation; ambiguous parent omitted');

    $second_subscription = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Developer inherited regression']);
    $ids[] = $second_subscription;
    update_post_meta($second_subscription, '_customer_id', $user_id);
    update_post_meta($second_subscription, '_product_id', $parent_id);
    update_post_meta($second_subscription, '_variation_id', $v2_id);
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats'), 15, 'Two variations are distinct holdings and empty list inherits');
    delete_post_meta($second_subscription, '_variation_id');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats'), 15, 'Explicit parent-only subscription remains a distinct holding');
    update_post_meta($second_subscription, '_variation_id', $v2_id);
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Priority'), 1, 'Toggle Yes contributes numeric one');

    update_post_meta($parent_id, '_arraysubs_features', wp_json_encode([$row('Seats', 'number', 'Unlimited')]));
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats'), PHP_INT_MAX, 'Unlimited dominates sum');
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats', 'max'), PHP_INT_MAX, 'Unlimited dominates max');
    $before = get_post_meta($subscription_id);
    $evaluator->getAggregatedFeatureValue($user_id, 'Seats');
    $assert(get_post_meta($subscription_id), $before, 'Entitlement reads do not mutate subscription metadata');

    wp_delete_post($subscription_id, true);
    wp_delete_post($second_subscription, true);
    $history->clearCache($user_id);
    $assert($evaluator->getAggregatedFeatureValue($user_id, 'Seats'), 10, 'Purchase survives without subscription and still honors override');
    $history->getPurchasedProductIds($user_id);
    $feature_condition = ['logic' => 'and', 'rules' => [['type' => 'feature_value', 'feature_key' => 'Seats', 'operator' => '>=', 'value' => 1]]];
    $assert($evaluator->evaluate($user_id, $feature_condition), true, 'Paid order grants feature condition');
    $order->update_status('refunded');
    $assert($history->getPurchasedItems($user_id), [], 'Refund invalidates purchased pair cache');
    $assert($history->getPurchasedProductIds($user_id), [], 'Refund invalidates old product cache too');
    $assert($evaluator->evaluate($user_id, $feature_condition), false, 'Order status invalidation clears request-local evaluator cache');
    WP_CLI::success($checks . ' membership condition assertions passed.');
} finally {
    wp_set_current_user($original_user);
    foreach ($order_ids as $id) {
        $order = wc_get_order($id);
        if ($order) {
            $order->delete(true);
        }
    }
    foreach (array_reverse($ids) as $id) {
        wp_delete_post($id, true);
    }
    if ($user_id && !is_wp_error($user_id)) {
        $history->clearCache($user_id);
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
    }
}
