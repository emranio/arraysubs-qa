<?php
/** Targeted integration checks. Run through WP-CLI eval-file, with or without Pro. */
defined('ABSPATH') || exit;

$checks = [];
$check = static function (bool $passed, string $label) use (&$checks): void {
    $checks[] = ['check' => $label, 'passed' => $passed];
    if (!$passed) {
        throw new RuntimeException($label);
    }
};
$request = static function (string $method, string $path, array $params = []): WP_REST_Response {
    $request = new WP_REST_Request($method, '/arraysubs/v1/' . $path);
    if ($method === 'GET') {
        $request->set_query_params($params);
    } else {
        $request->set_body_params($params);
    }
    return rest_do_request($request);
};
$original_user = get_current_user_id();
$admin_id = (int) get_users(['role' => 'administrator', 'fields' => 'ID', 'number' => 1])[0];
$customer_id = 224;
$manager_id = 0;
$mode = arraysubs_has_module('Features\\StoreCredit\\') === 1 ? 'pro' : 'free';
$fixture = [];

try {
    wp_set_current_user($admin_id);
    $response = $request('GET', 'members/' . $customer_id);
    $data = $response->get_data()['data'] ?? [];
    $check($response->get_status() === 200 && $data['profile']['id'] === $customer_id, 'Administrator can read an existing member');
    $check(isset($data['profile']['billing'], $data['profile']['shipping'], $data['subscriptions'], $data['orders'], $data['products']), 'Shared account, address and purchase sections are returned');
    $check(($data['stats']['store_credit_enabled'] ?? false) === ($mode === 'pro'), 'Premium credit is present only when its provider is live');

    $routes = rest_get_server()->get_routes();
    $handlers = $routes['/arraysubs/v1/members/(?P<user_id>\d+)'];
    $get_handlers = array_filter($handlers, static fn($handler) => isset($handler['methods']['GET']));
    $check(count($get_handlers) === 1, 'There is exactly one member read route');
    $owner = reset($get_handlers)['callback'][0];
    $check($owner instanceof \ArraySubs\Features\MemberInsight\REST\MemberController, 'Core owns the route in both configurations');

    $search = $request('GET', 'members/search', ['query' => 'Finley'])->get_data();
    $check(in_array($customer_id, array_column($search['data'] ?? [], 'id'), false), 'Member search finds the target customer');
    $check($request('GET', 'members/2147483647')->get_status() === 404, 'Missing members return 404');

    $before_email = get_userdata($customer_id)->user_email;
    $invalid = $request('POST', 'members/' . $customer_id, ['profile' => ['email' => 'invalid-email']]);
    $check($invalid->get_status() === 400 && get_userdata($customer_id)->user_email === $before_email, 'Invalid profile saves are rejected without changing the account');

    $paid = get_posts(['post_type' => 'arraysubs_data', 'post_status' => 'any', 'numberposts' => 1, 'meta_query' => [
        ['key' => '_recurring_amount', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL'],
        ['key' => '_customer_id', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'],
    ]]);
    $check(!empty($paid), 'A paid subscription fixture exists');
    $paid = $paid[0];
    $paid_customer = (int) get_post_meta($paid->ID, '_customer_id', true);
    $paid_data = $request('GET', 'members/' . $paid_customer)->get_data()['data'];
    $rows = array_column($paid_data['subscriptions'], null, 'id');
    $expected_amount = arraysubs_get_subscription_effective_recurring_amount($paid->ID);
    $expected_currency = get_post_meta($paid->ID, '_currency', true) ?: get_woocommerce_currency();
    $check((float) $rows[$paid->ID]['total'] === $expected_amount && $expected_amount > 0, 'Member subscription totals reflect actual recurring billing amounts');
    $check($rows[$paid->ID]['currency'] === $expected_currency && $rows[$paid->ID]['total_formatted'] === wc_price($expected_amount, ['currency' => $expected_currency]), 'Member totals use the subscription currency');
    $fixture = ['subscription_id' => $paid->ID, 'customer_id' => $paid_customer, 'recurring_amount' => $expected_amount, 'currency' => $expected_currency];

    // This filter is request-local; no saved module settings or product statuses change.
    $disable_credit = static fn($enabled, $module) => $module === 'store_credit' ? false : $enabled;
    add_filter('arraysubs_is_module_enabled', $disable_credit, 99, 2);
    $without_credit = $request('GET', 'members/' . $customer_id)->get_data()['data'];
    $check($without_credit['stats']['store_credit_enabled'] === false && $without_credit['store_credit']['enabled'] === false, 'Switching credit off leaves core member details available without premium balances');
    $shortcuts = apply_filters('arraysubs_member_customer_shortcuts', []);
    $check(!in_array('store-credit', array_column($shortcuts, 'key'), true), 'Disabled credit does not add customer shortcuts');
    remove_filter('arraysubs_is_module_enabled', $disable_credit, 99);

    wp_set_current_user(0);
    $check(in_array($request('GET', 'members/' . $customer_id)->get_status(), [401, 403], true), 'Anonymous visitors cannot read member data');
    wp_set_current_user($customer_id);
    $check($request('GET', 'members/' . $customer_id)->get_status() === 403, 'Customers cannot use the administrative member API');
    $check($request('POST', 'members/' . $customer_id, ['profile' => ['display_name' => 'Forbidden']])->get_status() === 403, 'Customers cannot edit through the administrative member API');

    wp_set_current_user($admin_id);
    $manager_id = wp_insert_user([
        'user_login' => 'member-qa-' . wp_generate_password(10, false, false),
        'user_email' => 'member-qa-' . wp_generate_password(10, false, false) . '@example.test',
        'user_pass' => wp_generate_password(32),
        'role' => 'shop_manager',
    ]);
    $check(!is_wp_error($manager_id), 'Disposable shop manager created for capability checks');
    wp_set_current_user($manager_id);
    $check($request('GET', 'members/' . $customer_id)->get_status() === 200, 'Shop managers can view customer members');
    $check($request('POST', 'members/' . $admin_id, ['profile' => ['display_name' => 'Forbidden']])->get_status() === 403, 'Shop managers cannot edit administrator profiles');
    $check($request('POST', 'members/' . $customer_id, ['profile' => ['email' => 'invalid-email']])->get_status() === 400, 'Shop managers can reach validation only for editable customer profiles');
} finally {
    wp_set_current_user($admin_id);
    if ($manager_id && !is_wp_error($manager_id)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($manager_id);
    }
    wp_set_current_user($original_user);
}
echo wp_json_encode(['mode' => $mode, 'checks' => $checks, 'paid_fixture' => $fixture, 'temporary_manager_id_deleted' => $manager_id], JSON_PRETTY_PRINT) . PHP_EOL;
