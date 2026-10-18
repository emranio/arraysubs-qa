<?php
defined('ABSPATH') || exit;
/** Read APIs: real WordPress fixtures, explicit identity isolation, no benefit mutations. */

use ArraySubs\Developer\MemberAccess;
use ArraySubs\Developer\Downloads;
use ArraySubs\Developer\Discounts;
use ArraySubs\Developer\MemberStyling;
use ArraySubs\Developer\LoginLimits;
use ArraySubs\Features\MembersAccess\Services\ConditionEvaluator;
use ArraySubs\Features\MembersAccess\Services\ScheduleEvaluator;
use ArraySubs\Features\MembersAccess\Services\DiscountEngine;
use ArraySubs\Features\MemberStyling\Services\StyleEngine;

$checks = 0;
$assert = static function ($actual, $expected, string $message) use (&$checks): void {
    ++$checks;
    if ($actual !== $expected) {
        throw new RuntimeException($message . ': ' . var_export($actual, true) . ' expected ' . var_export($expected, true));
    }
};
$ids = [];
$user_id = 0;
$old_user = get_current_user_id();
$settings = arraysubs_get_settings();
$setting_filter = static function () use (&$settings) { return $settings; };
add_filter('pre_option_arraysubs_settings', $setting_filter);
try {
    $user_id = wp_insert_user(['user_login' => 'member-api-' . wp_generate_password(10, false), 'user_pass' => wp_generate_password(30), 'role' => 'customer']);
    $products = [];
    $subscriptions = [];
    foreach ([5, 20] as $index => $seats) {
        $product = new WC_Product_Simple();
        $product->set_name('Member API fixture');
        $product->set_status('publish');
        $product->set_regular_price('100');
        $id = $product->save();
        $products[] = $id;
        $ids[] = $id;
        update_post_meta($id, '_arraysubs_features', wp_json_encode([['name' => 'Seats', 'type' => 'number', 'value' => $seats, 'enabled' => true]]));
        $id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Member API fixture', 'meta_input' => ['_customer_id' => $user_id, '_product_id' => $id, '_start_date' => gmdate('Y-m-d H:i:s', time() - ($index ? 365 : 0) * DAY_IN_SECONDS)]]);
        $subscriptions[] = $id;
        $ids[] = $id;
    }
    [$a, $b] = $subscriptions;
    $conditions = ['logic' => 'and', 'rules' => [['type' => 'feature_value', 'feature_key' => 'Seats', 'operator' => '>=', 'value' => 10]]];
    $rule = ['id' => 'api-rule', 'enabled' => true, 'conditions' => $conditions];
    $settings['members_access']['enabled'] = true;
    $settings['members_access']['url_rules'] = [$rule + ['pattern_type' => 'prefix', 'pattern' => '/member-api']];
    $settings['members_access']['ecommerce_rules'] = [$rule + ['scope' => 'all', 'action' => 'block_purchase']];
    $settings['members_access']['comment_rules'] = [$rule + ['target_type' => 'all', 'restriction_mode' => 'block_all']];
    $settings['members_access']['discount_rules'] = [$rule + ['discount_type' => 'percentage', 'discount_value' => 20, 'target_scope' => 'all', 'apply_to' => 'per_item']];
    $settings['members_access']['download_rules'] = [$rule + ['download_limit' => 3, 'download_expiry' => 7, 'files' => [['id' => 'safe-file', 'name' => 'Member file', 'url' => home_url('/protected/secret-file.pdf')]]]];
    $settings['members_access']['login_limit_rules'] = [$rule + ['max_sessions' => 5]];
    $settings['member_styling'] = ['enabled' => true, 'rules' => [$rule + ['body_classes' => 'member-api-vip', 'custom_css' => '.member-api-vip { color: inherit; }']]];
    $settings['toolkit']['multi_login_prevention'] = true;
    $settings['toolkit']['multi_login_max_sessions'] = 2;
    $settings['toolkit']['multi_login_include_admins'] = false;
    ConditionEvaluator::clearCache();
    ScheduleEvaluator::clearCache();
    DiscountEngine::clearCache();
    StyleEngine::clearCache();

    $assert(is_wp_error(MemberAccess::fromUserId(-1)), true, 'Invalid user rejected');
    $assert(is_wp_error(MemberAccess::fromSubscriptionId(0)), true, 'Invalid subscription rejected');
    $user = MemberAccess::fromUserId($user_id);
    $sub_a = MemberAccess::fromSubscriptionId($a);
    $sub_b = MemberAccess::fromSubscription(arraysubs_get_subscription($b));
    $assert($user->evaluateConditions($conditions), true, 'User sees all held features');
    $assert($sub_a->evaluateConditions($conditions), false, 'Subscription A cannot borrow B features');
    $assert($sub_b->evaluateConditions($conditions), true, 'Subscription B sees own feature');
    $scheduled = $rule + ['schedule_enabled' => true, 'schedule_value' => 10, 'schedule_unit' => 'days'];
    $assert((new ScheduleEvaluator('view', $a))->isScheduleSatisfied($user_id, $scheduled), false, 'A schedule uses own recent start');
    $assert((new ScheduleEvaluator('view', $b))->isScheduleSatisfied($user_id, $scheduled), true, 'B schedule remains isolated in cache');
    $assert($sub_a->check('product', $products[0])['decision']['allowed'], false, 'Explicit ecommerce user A denied');
    $assert($sub_b->check('product', $products[0])['decision']['allowed'], true, 'Explicit ecommerce user B allowed');
    $assert(is_wp_error($user->check('invalid', 1)), true, 'Unsupported entity rejected');
    $assert(is_wp_error($user->check('url', 'https://example.invalid/')), true, 'External URL rejected');
    $assert(is_wp_error($user->check('post', 0)), true, 'Missing target rejected');
    $assert(is_wp_error($user->check('product', $products[0], ['metadata'])), true, 'Unknown field rejected');
    $assert(array_keys($user->check('product', $products[0], ['decision'])), ['decision'], 'Known partial fields returned alone');
    $post_id = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Member API page']);
    $ids[] = $post_id;
    update_post_meta($post_id, '_arraysubs_restrict_enabled', '1');
    update_post_meta($post_id, '_arraysubs_restrict_conditions', wp_json_encode($conditions));
    $assert($sub_a->check('post', $post_id)['decision']['allowed'], false, 'Raw post gate obeys scoped feature');
    $assert($sub_b->check('post', $post_id)['decision']['allowed'], true, 'Raw post gate allows qualifying scope');
    $assert($sub_a->check('comments', $post_id)['decision']['allowed'], false, 'Comment rule uses explicit context');
    $assert($sub_b->check('comments', $post_id)['decision']['allowed'], true, 'Comment rule grants qualifying context');

    $before_meta = get_user_meta($user_id);
    $before_a = get_post_meta($a);
    $before_b = get_post_meta($b);
    $before_product = get_post_meta($products[0]);
    $before_styles = wp_styles()->queue;
    $a_files = Downloads::fromSubscriptionId($a)->files();
    $b_files = Downloads::fromSubscriptionId($b)->files();
    $assert($a_files['items'][0]['eligible'], false, 'Download A denied without other feature');
    $file = $b_files['items'][0];
    $assert($file['eligible'], true, 'Download B eligible');
    $assert($file['granted_at'], null, 'Preview does not initialize grant');
    $assert($file['expires_at'], null, 'Preview does not start expiry');
    $assert($file['remaining'], 3, 'Unconsumed file quota visible');
    $assert(str_contains(wp_json_encode($file), '/protected/'), false, 'No protected file location exported');
    $assert(str_contains($file['download_url'], 'arraysubs_download='), true, 'Uses existing protected endpoint');
    $assert(get_user_meta($user_id), $before_meta, 'Download previews write no user metadata');
    $assert(Downloads::fromUserId($user_id)->files(['per_page' => 1])['total'], 1, 'Download collection has exact bounded envelope');
    $assert(Downloads::fromUserId($user_id)->files(['output' => 'ids'])['items'], [$file['download_id']], 'Collection supports ID output');
    $assert(Downloads::fromUserId($user_id)->files(['include' => ['unknown']])['items'], [], 'Collection include filter is exact');
    $assert(array_keys(Downloads::fromUserId($user_id)->files(['fields' => ['eligible']])['items'][0]), ['eligible'], 'Collection fields are projected');
    $assert(is_wp_error(Downloads::fromUserId($user_id)->files(['fields' => ['secret_path']])), true, 'Unknown collection fields rejected');
    $assert(is_wp_error(Downloads::fromUserId($user_id)->files(['unknown' => true])), true, 'Unknown collection options rejected');
    $assert(is_wp_error(Downloads::fromUserId($user_id)->files(['per_page' => 101])), true, 'Collection maximum enforced');

    $assert(Discounts::fromSubscriptionId($a)->calculateProduct($products[0])['member_unit_price'], null, 'A gets no borrowed discount');
    $assert(Discounts::fromSubscriptionId($b)->calculateProduct($products[0], 2)['member_unit_price'], 80.0, 'B uses same engine product calculation');
    $assert(Discounts::fromSubscriptionId($b)->calculateProduct($products[0], 2)['line_price'], 160.0, 'Explicit quantity represented');
    $assert(MemberStyling::fromSubscriptionId($a)->data()['effective']['classes'], [], 'A gets no borrowed styling');
    $assert(MemberStyling::fromSubscriptionId($b)->data()['effective']['classes'], ['member-api-vip'], 'B gets existing engine tokens');
    $assert(wp_styles()->queue, $before_styles, 'Styling reader enqueues no assets');
    $assert(get_current_user_id(), $old_user, 'Reads never switch logged-in user');
    $assert(get_post_meta($a), $before_a, 'Reads preserve A metadata');
    $assert(get_post_meta($b), $before_b, 'Reads preserve B metadata');
    $assert(get_post_meta($products[0]), $before_product, 'Pricing reads never save product');
    $assert(get_user_meta($user_id), $before_meta, 'All benefit reads preserve customer metadata');

    $policy = LoginLimits::fromUserId($user_id)->data();
    if ($policy['availability']['available']) {
        update_user_meta($user_id, 'session_tokens', ['first' => ['expiration' => time() + HOUR_IN_SECONDS], 'expired' => ['expiration' => time() - HOUR_IN_SECONDS], 'impersonation' => ['expiration' => time() + HOUR_IN_SECONDS, 'arraysubs_impersonated_from_id' => 1]]);
        $before_sessions = get_user_meta($user_id, 'session_tokens', true);
        $policy = LoginLimits::fromSubscriptionId($a)->data();
        $assert($policy['policy']['maximum'], 2, 'A falls back to global login limit');
        $assert(LoginLimits::fromSubscriptionId($b)->data()['policy']['maximum'], 5, 'B login rule applies within scope');
        $assert($policy['sessions']['active'], 1, 'Safe count excludes expired and impersonated sessions');
        $assert($policy['sessions']['remaining'], 1, 'Remaining capacity computed');
        $assert(LoginLimits::fromUserId($user_id)->data([], ['impersonating' => true])['policy']['applies'], false, 'Explicit impersonation is exempt');
        $assert(get_user_meta($user_id, 'session_tokens', true), $before_sessions, 'Login policy never touches session records');
        $assert(str_contains(wp_json_encode($policy), 'first'), false, 'No session secrets exported');
    } else {
        $assert($policy['policy']['reason'], 'module_unavailable', 'Core-only login policy reports unavailable');
        $assert($policy['policy']['maximum'], null, 'Unavailable is distinct from zero');
    }
    $active_condition = ['rules' => [['type' => 'subscription_status', 'statuses' => ['active']]]];
    $assert($sub_a->evaluateConditions($active_condition), true, 'Initial explicit status read is active');
    wp_update_post(['ID' => $a, 'post_status' => 'arraysubs-cancelled']);
    $assert($sub_a->evaluateConditions($active_condition), false, 'A later read sees a same-request lifecycle mutation');
    WP_CLI::success($checks . ' member developer API assertions passed.');
} finally {
    remove_filter('pre_option_arraysubs_settings', $setting_filter);
    foreach (array_reverse($ids) as $id) {
        wp_delete_post($id, true);
    }
    if ($user_id && !is_wp_error($user_id)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
    }
}
