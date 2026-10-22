<?php
/** Delete only this task's recorded disposable fixtures, after browser completion. */
defined('ABSPATH') || exit;
if (($args[0] ?? '') !== 'execute') { throw new RuntimeException('Explicit execute argument required.'); }
add_filter('pre_wp_mail', '__return_true', PHP_INT_MAX);
$fixtures = json_decode(file_get_contents(__DIR__ . '/created-fixtures.json'), true);
$customer = $fixtures['customer'];
$user = get_userdata($customer['id']);
if (!$user || $user->user_login !== $customer['login'] || $user->user_email !== $customer['email'] || $user->roles !== ['customer']) {
    throw new RuntimeException('Disposable customer ownership mismatch.');
}
if (arraysubs_get_customer_subscriptions((int) $user->ID)) {
    throw new RuntimeException('Unexpected subscription on disposable customer; investigate before cleanup.');
}
$product_ids = array_column($fixtures['products'], 'id');
foreach ($product_ids as $id) {
    $product = wc_get_product($id);
    if (!$product || !str_starts_with($product->get_name(), 'QA Cart 90 ')) {
        throw new RuntimeException('Disposable product ownership mismatch.');
    }
}
require __DIR__ . '/find-owned-orders.php';
$orders = json_decode(file_get_contents(__DIR__ . '/owned-orders.json'), true);
foreach ($orders as $record) {
    $order = wc_get_order($record['id']);
    if (!$order || $order->get_status() !== 'checkout-draft' || $order->get_transaction_id() !== '' || !in_array($order->get_customer_id(), [0, (int) $user->ID], true)) {
        throw new RuntimeException('Unexpected owned order state; investigate before cleanup.');
    }
    foreach ($order->get_items() as $item) {
        if (!in_array($item->get_product_id(), $product_ids, true)) {
            throw new RuntimeException('Order contains an unrelated product; do not delete.');
        }
    }
}
$result = ['deleted_orders' => [], 'deleted_products' => [], 'deleted_customer' => null];
foreach ($orders as $record) {
    wc_get_order($record['id'])->delete(true);
    $result['deleted_orders'][] = $record['id'];
}
$session = new WC_Session_Handler();
$session->delete_session((string) $user->ID);
foreach ($product_ids as $id) {
    wc_get_product($id)->delete(true);
    $result['deleted_products'][] = $id;
}
require_once ABSPATH . 'wp-admin/includes/user.php';
if (!wp_delete_user((int) $user->ID)) { throw new RuntimeException('Disposable customer deletion failed.'); }
$result['deleted_customer'] = (int) $user->ID;
$result['verified_absent'] = !get_userdata($user->ID) && !array_filter($product_ids, 'get_post');
file_put_contents(__DIR__ . '/cleanup.json', wp_json_encode($result, JSON_PRETTY_PRINT));
echo wp_json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
