<?php
/** Read-only actual WordPress REST permission/payload checks for QA86. */
defined('ABSPATH') || exit;
$original_user = get_current_user_id();
$owner = 224;
$others = get_users(['role' => 'customer', 'exclude' => [$owner], 'number' => 1, 'fields' => 'ID']);
$other = (int) ($others[0] ?? 0);
$pro = class_exists('ArraySubsPro\\Features\\StoreCredit\\Services\\CreditManager');
$checks = [];
$failure = null;
$check = static function ($passed, $label) use (&$checks) {
    $checks[] = ['check' => $label, 'passed' => (bool) $passed];
    if (!$passed) { throw new RuntimeException($label); }
};
$get = static function ($route, $params = []) {
    $request = new WP_REST_Request('GET', '/arraysubs/v1/' . $route);
    $request->set_query_params($params);
    return rest_do_request($request);
};
$has_type = static function ($data) use (&$has_type): bool {
    if (!is_array($data)) { return false; }
    foreach ($data as $key => $value) {
        if ($key === 'type' || (is_array($value) && $has_type($value))) { return true; }
    }
    return false;
};
try {
    $check($other > 0, 'Existing second ordinary customer available');
    $core = ['my-subscriptions/8326/shipping-address', 'subscriptions/8326/payment-method'];
    $premium = ['my-features/subscription/8326', 'my-subscriptions/8326/early-renew'];
    foreach (array_merge($core, $premium) as $route) {
        $available = in_array($route, $core, true) || $pro;
        foreach ([0 => 'guest', $other => 'different customer', $owner => 'owner'] as $id => $label) {
            wp_set_current_user($id);
            $response = $get($route);
            $expected = !$available ? [404] : ($id === 0 ? [401,403] : ($id !== $owner ? [403] : (str_ends_with($route, '/shipping-address') ? [400] : (str_ends_with($route, '/payment-method') ? [404] : [200]))));
            $check(in_array($response->get_status(), $expected, true), $route . ' / ' . $label . ' status ' . $response->get_status());
            if ($available && $id === $owner && str_ends_with($route, '/shipping-address')) {
                $check(($response->get_data()['code'] ?? '') === 'no_shipping', 'Virtual lifetime subscription returns its expected no-shipping policy');
            }
            if ($available && $id === $owner && str_ends_with($route, '/payment-method')) {
                $check(($response->get_data()['message'] ?? '') === 'No payment method found for this subscription.', 'Lifetime subscription returns controlled missing-method response');
            }
            if ($available && $id === $owner && str_starts_with($route, 'my-features')) {
                $check(!$has_type($response->get_data()['content']['features'] ?? []), 'Subscription feature response omits internal feature types');
            }
        }
    }
    foreach (['my-features', 'my-credits', 'my-credits/history'] as $route) {
        wp_set_current_user(0);
        $response = $get($route);
        $check(in_array($response->get_status(), $pro ? [401,403] : [404], true), $route . ' refuses anonymous access or is absent in Free');
        if (!$pro) { continue; }
        foreach ([$owner, $other] as $id) {
            wp_set_current_user($id);
            $response = $get($route, ['customer_id' => $owner, 'user_id' => $owner]);
            $check($response->get_status() === 200, $route . ' current customer ' . $id . ' loads');
            $body = $response->get_data();
            $data = $body['data'] ?? $body['content'] ?? [];
            if ($route === 'my-features') {
                $check(($data['customer_id'] ?? 0) === $id, 'Feature customer identity ignores requested foreign ID for ' . $id);
                $check(!$has_type($data['features'] ?? []), 'Aggregated feature response omits internal types for ' . $id);
            } else {
                $expected = \ArraySubsPro\Features\StoreCredit\Services\CreditManager::getCustomerCredit($id);
                $check((float) ($data['balance'] ?? -1) === (float) $expected, 'Credit balance is current customer only for ' . $id);
            }
        }
    }
    wp_set_current_user($owner);
    foreach (['members/224', 'features/product/6725', 'credits/customer/224', 'cancellation/form-config'] as $route) {
        $response = $get($route);
        $check(in_array($response->get_status(), $pro || str_starts_with($route, 'members/') ? [403] : [404], true), 'Customer cannot read admin API ' . $route);
    }
} catch (Throwable $e) { $failure = get_class($e) . ': ' . $e->getMessage(); }
finally { wp_set_current_user($original_user); }
echo wp_json_encode(['pro_loaded' => $pro, 'subscription_id' => 8326, 'owner_id' => $owner, 'other_customer_id' => $other, 'checks' => $checks, 'failure' => $failure], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failure) { WP_CLI::halt(1); }
