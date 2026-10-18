<?php
// Local browser fixture; cleanup-browser-fixtures.php restores every changed setting.
$path = __DIR__ . '/browser-fixtures.json';
$data = json_decode(file_get_contents($path), true);
if (isset($data['switch_order_id'])) { WP_CLI::error('Switch fixture already exists.'); }
$data['original_plan_switching'] = arraysubs_get_setting('plan_switching', []);
$data['original_auto_apply'] = arraysubs_get_setting('store_credit.auto_apply_to_renewals', true);
file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
arraysubs_update_setting('plan_switching', array_merge($data['original_plan_switching'], ['enabled' => true, 'allow_customer_switch' => true, 'allow_upgrades' => true, 'allow_downgrades' => true, 'allow_crossgrades' => true, 'proration_type' => 'prorate_immediately']));
arraysubs_update_setting('store_credit.auto_apply_to_renewals', false);
[$target, $source] = $data['items'];
$sid = $source['subscription_id'];
$start = time() - 15 * DAY_IN_SECONDS;
update_post_meta($source['product_id'], '_arraysubs_downgrade_products', [$target['product_id']]);
update_post_meta($sid, '_start_date', gmdate('Y-m-d H:i:s', $start));
update_post_meta($sid, '_next_payment_date', gmdate('Y-m-d H:i:s', time() + 15 * DAY_IN_SECONDS));
$order = wc_create_order(['customer_id' => $data['user_id']]);
$order->set_currency(get_woocommerce_currency());
$order->set_billing_email(get_userdata($data['user_id'])->user_email);
$item = new WC_Order_Item_Product();
$item->set_product(wc_get_product($source['product_id']));
$item->set_quantity(1);
$item->set_subtotal(10);
$item->set_total(10);
$item->add_meta_data('_subscription_id', $sid, true);
$order->add_item($item);
$order->update_meta_data('_subscription_ids', [$sid]);
$order->calculate_totals(false);
$order->set_date_paid($start);
$order->set_status('completed');
$order->save();
update_post_meta($sid, '_parent_order_id', $order->get_id());
update_post_meta($sid, '_order_ids', wp_json_encode([$order->get_id()]));
$data['switch_order_id'] = $order->get_id();
file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
WP_CLI::success('Switch browser fixture ready.');
