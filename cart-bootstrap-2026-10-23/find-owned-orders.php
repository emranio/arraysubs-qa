<?php
/** Read-only discovery of any Woo checkout drafts containing this task's products. */
defined('ABSPATH') || exit;
$fixtures = json_decode(file_get_contents(__DIR__ . '/created-fixtures.json'), true);
$product_ids = array_column($fixtures['products'], 'id');
$found = [];
foreach (wc_get_orders(['limit' => -1, 'date_created' => '>=' . strtotime($fixtures['created_at']), 'status' => ['checkout-draft', 'pending', 'failed', 'processing', 'completed', 'cancelled', 'on-hold']]) as $order) {
    $matches = [];
    foreach ($order->get_items() as $item) {
        if (in_array($item->get_product_id(), $product_ids, true)) {
            $matches[] = ['product_id' => $item->get_product_id(), 'quantity' => $item->get_quantity()];
        }
    }
    if ($matches) {
        $found[] = ['id' => $order->get_id(), 'status' => $order->get_status(), 'customer_id' => $order->get_customer_id(), 'items' => $matches, 'has_transaction' => $order->get_transaction_id() !== ''];
    }
}
file_put_contents(__DIR__ . '/owned-orders.json', wp_json_encode($found, JSON_PRETTY_PRINT));
echo wp_json_encode($found, JSON_PRETTY_PRINT) . PHP_EOL;
