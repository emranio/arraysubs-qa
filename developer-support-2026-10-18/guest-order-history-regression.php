<?php

defined('ABSPATH') || exit;

use ArraySubs\Developer\Orders;
use ArraySubs\Developer\Subscriptions;

$users = [];
$subscriptions = [];
$orders = [];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$assertions;
};
$ids = static function ($result): array {
    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }
    return $result['items'];
};

try {
    $run = substr(wp_generate_uuid4(), 0, 8);
    $email = 'guest-history-' . $run . '@example.test';
    foreach (['owner', 'other'] as $name) {
        $id = wp_insert_user(['user_login' => 'guest-history-' . $run . '-' . $name, 'user_email' => $name . '-' . $run . '@example.test', 'user_pass' => wp_generate_password(), 'role' => 'customer']);
        if (is_wp_error($id)) {
            throw new RuntimeException($id->get_error_message());
        }
        $users[] = $id;
    }
    $subscription = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Guest order history regression', 'meta_input' => ['_customer_id' => $users[0], '_customer_email' => strtoupper($email)]]);
    $subscriptions[] = $subscription;
    $create = static function (int $customer, string $billing_email, bool $linked = true) use (&$orders, $subscription): int {
        $order = wc_create_order(['customer_id' => $customer]);
        if (is_wp_error($order)) {
            throw new RuntimeException($order->get_error_message());
        }
        $orders[] = $order->get_id();
        $order->set_billing_email($billing_email);
        if ($linked) {
            $order->update_meta_data('_subscription_id', $subscription);
        }
        $order->save();
        return $order->get_id();
    };
    $guest_order = $create(0, $email);
    WP_CLI::log('Fixtures: subscription=' . $subscription . ', guest_order=' . $guest_order . ', customers=' . implode(',', $users) . ', logins=guest-history-' . $run . '-{owner,other}.');
    update_post_meta($subscription, '_parent_order_id', $guest_order);
    $resource = Subscriptions::fromId($subscription);
    $assert(arraysubs_subscription_matches_order_customer($guest_order, $subscription), 'Canonical identity accepts a linked guest order for its registered subscription owner.');
    $assert($ids($resource->orders(['include' => [$guest_order], 'output' => 'ids'])) === [$guest_order], 'Registered subscription history retains its linked guest order.');
    $assert($resource->data(['order_references'])['order_references']['initial_order_id'] === $guest_order, 'Initial-order projection retains the verified guest reference.');

    $expected = [$guest_order];
    // Cross the engine batch boundary with mixed guest and registered records.
    for ($index = 0; $index < 102; ++$index) {
        $expected[] = $create($index % 2 ? $users[0] : 0, $email);
    }
    $changed_email_order = $create($users[0], 'changed-' . $run . '@example.test');
    $expected[] = $changed_email_order;
    $foreign = $create($users[1], $email);
    $unlinked = $create(0, $email, false);
    update_post_meta($subscription, '_order_ids', wp_json_encode(array_merge($expected, [$foreign])));
    $assert(!$ids($resource->orders(['include' => [$foreign, $unlinked], 'output' => 'ids'])), 'Equal emails cannot bridge different registered customers or manufacture a subscription link.');
    $assert($ids($resource->orders(['include' => [$changed_email_order], 'output' => 'ids'])) === [$changed_email_order], 'Registered ownership still matches by ID after a billing email change.');
    $assert(!$ids($resource->orders(['customer_id' => $users[0], 'include' => [$guest_order], 'output' => 'ids'])), 'Explicit customer selector remains ANDed and excludes guest orders.');
    $assert(!$ids($resource->orders(['customer_id' => $users[1], 'output' => 'ids'])), 'Explicit other-customer selector cannot bypass canonical identity.');
    $compile = new ReflectionMethod(WC_Order_Data_Store_CPT::class, 'get_wp_query_args');
    $compile->setAccessible(true);
    $legacy_query = $compile->invoke(new WC_Order_Data_Store_CPT(), ['customer' => [$users[0], $email]]);
    $legacy_identity = end($legacy_query['meta_query']);
    $assert($legacy_identity['relation'] === 'OR' && $legacy_identity['customer_ids']['value'] === [(string) $users[0]] && $legacy_identity['customer_emails']['value'] === [$email], 'WooCommerce CPT query compilation also preserves the customer ID/email union.');
    foreach (['asc', 'desc'] as $direction) {
        $actual = [];
        $page = 1;
        do {
            $result = $resource->orders(['output' => 'ids', 'per_page' => 17, 'page' => $page, 'order' => $direction]);
            $actual = array_merge($actual, $ids($result));
            $assert($result['page'] === $page && count($result['items']) <= 17, 'Bounded page metadata remains correct.');
            $page = $result['next_page'];
        } while ($page !== null);
        $assert($actual === ($direction === 'asc' ? $expected : array_reverse($expected)), 'Mixed identity pages remain complete, stable and duplicate-free in ' . $direction . ' order.');
    }
    $assert(in_array($subscription, $ids(Subscriptions::fromOrderId($guest_order, ['output' => 'ids'])), true), 'Forward and reverse guest relationships agree.');
    update_post_meta($subscription, '_customer_email', '');
    $assert($ids($resource->orders(['include' => [$changed_email_order], 'output' => 'ids'])) === [$changed_email_order], 'Registered identity remains usable without a stored email.');
    update_post_meta($subscription, '_customer_id', 0);
    $assert(is_wp_error($resource->orders()), 'An unidentified guest subscription still fails predictably.');
    update_post_meta($subscription, '_customer_email', $email);
    $assert($ids($resource->orders(['include' => [$guest_order], 'output' => 'ids'])) === [$guest_order], 'Guest-only history retains email-based discovery.');
} finally {
    foreach ($orders as $id) {
        $order = wc_get_order($id);
        if ($order) {
            $order->delete(true);
        }
    }
    foreach ($subscriptions as $id) {
        wp_delete_post($id, true);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($users as $id) {
        wp_delete_user($id);
    }
}

WP_CLI::success($assertions . ' guest-order history assertions passed; disposable fixtures cleaned.');
