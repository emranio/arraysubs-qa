<?php
/** Isolated fixtures and reversible configuration for PDF integration QA. */
defined('ABSPATH') || exit;
$phase = $args[0] ?? 'inspect';
$path = __DIR__ . '/fixtures.json';
$baseline_path = __DIR__ . '/original-state.json';
add_filter('pre_wp_mail', '__return_true');

if ($phase === 'setup') {
    if (file_exists($path)) { throw new RuntimeException('Fixture record already exists'); }
    $baseline = [];
    foreach (['woocommerce_coming_soon', 'wpo_wcpdf_documents_settings_invoice', 'wpo_wcpdf_settings_general', 'wpo_wcpdf_settings_debug'] as $key) {
        $baseline[$key] = ['exists' => get_option($key, null) !== null, 'value' => get_option($key, null)];
    }
    file_put_contents($baseline_path, wp_json_encode($baseline, JSON_PRETTY_PRINT) . "\n");
    $f = json_decode(file_get_contents(dirname(__DIR__) . '/pdf-invoice-portal-2026-10-29/fixtures.json'), true);
    foreach (['absent' => 'pending', 'paid' => 'completed'] as $name => $status) {
        $order = wc_create_order(['customer_id' => $f['customer_id'], 'status' => 'pending']);
        $order->set_billing_first_name('QA');
        $order->set_billing_last_name('PDF Invoice');
        $order->set_billing_email($f['customer_email']);
        $order->set_created_via('qa-pdf-compatibility');
        $order->add_product(wc_get_product($f['product_id']), 1);
        $order->calculate_totals();
        $order->set_status($status);
        $order->save();
        $subscription_id = arraysubs_create_subscription([
            'customer_id' => $f['customer_id'], 'product_id' => $f['product_id'],
            'subscription_price' => 25, 'recurring_amount' => 25,
            'status' => 'arraysubs-pending', 'parent_order_id' => $order->get_id(),
        ]);
        if (is_wp_error($subscription_id) || !arraysubs_link_subscription_to_parent_order($order, $subscription_id)) {
            throw new RuntimeException('Could not create isolated ' . $name . ' fixture');
        }
        $f[$name] = ['order_id' => $order->get_id(), 'subscription_id' => $subscription_id,
            'url' => wc_get_endpoint_url('view-subscription', $subscription_id, wc_get_page_permalink('myaccount'))];
        if ($name === 'paid') { wcpdf_get_document('invoice', $order, true); }
    }
    $renewal = (new \ArraySubs\Features\RecurringBilling\Services\OrderCreation())->createRenewalOrder($f['absent']['subscription_id']);
    if (!$renewal instanceof WC_Order) { throw new RuntimeException('Renewal creation failed'); }
    update_post_meta($f['absent']['subscription_id'], '_order_ids', wp_json_encode([$f['absent']['order_id'], $renewal->get_id()]));
    $f['renewal_order_id'] = $renewal->get_id();
    $password = wp_generate_password(28, false);
    $f['other_customer_id'] = wp_insert_user(['user_login' => 'qa_pdf_other_20261029', 'user_pass' => $password,
        'user_email' => 'qa.pdf.other.20261029@example.test', 'role' => 'customer']);
    if (is_wp_error($f['other_customer_id'])) { throw new RuntimeException('Other customer creation failed'); }
    file_put_contents('/tmp/arraysubs-pdf-other-password', $password);
    chmod('/tmp/arraysubs-pdf-other-password', 0600);
    file_put_contents($path, wp_json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    update_option('woocommerce_coming_soon', 'no');
}

$f = json_decode(file_get_contents($path), true);
if ($phase === 'policy') {
    $settings = get_option('wpo_wcpdf_documents_settings_invoice', []);
    $settings['enabled'] = 1;
    $settings['my_account_buttons'] = $args[1];
    $settings['my_account_restrict'] = [$args[2] ?? 'completed' => 1];
    update_option('wpo_wcpdf_documents_settings_invoice', $settings);
} elseif ($phase === 'disabled') {
    $settings = get_option('wpo_wcpdf_documents_settings_invoice', []);
    unset($settings['enabled']);
    update_option('wpo_wcpdf_documents_settings_invoice', $settings);
} elseif ($phase === 'display') {
    $settings = get_option('wpo_wcpdf_settings_general', []);
    $settings['download_display'] = $args[1];
    update_option('wpo_wcpdf_settings_general', $settings);
} elseif ($phase === 'pretty') {
    $settings = get_option('wpo_wcpdf_settings_debug', []);
    $settings['pretty_document_links'] = $args[1] === 'yes' ? 1 : 0;
    update_option('wpo_wcpdf_settings_debug', $settings);
    WPO_WCPDF()->endpoint->add_endpoint();
    flush_rewrite_rules(false);
} elseif ($phase === 'email-config') {
    $settings = get_option('wpo_wcpdf_documents_settings_invoice', []);
    $settings['enabled'] = 1;
    $settings['attach_to_email_ids'] = ['arraysubs_renewal_invoice' => 1];
    update_option('wpo_wcpdf_documents_settings_invoice', $settings);
} elseif ($phase === 'vat') {
    $settings = get_option('wpo_wcpdf_settings_general', []);
    $settings['checkout_field_enable'] = 1;
    $settings['checkout_field_label'] = 'QA Customer VAT';
    $settings['checkout_field_as_vat_number'] = 1;
    update_option('wpo_wcpdf_settings_general', $settings);
    $parent = wc_get_order($f['absent']['order_id']);
    $parent->update_meta_data('_wpo_ips_checkout_field', 'QA-VAT-20261029');
    $parent->save();
    if (empty($f['vat_renewal_order_id'])) {
        $renewal = (new \ArraySubs\Features\RecurringBilling\Services\OrderCreation())->createRenewalOrder($f['absent']['subscription_id']);
        if (!$renewal instanceof WC_Order) { throw new RuntimeException('VAT renewal creation failed'); }
        $f['vat_renewal_order_id'] = $renewal->get_id();
        $ids = [$f['absent']['order_id'], $f['renewal_order_id'], $renewal->get_id()];
        update_post_meta($f['absent']['subscription_id'], '_order_ids', wp_json_encode($ids));
        file_put_contents($path, wp_json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
} elseif ($phase === 'settling') {
    $order = wc_get_order($f['renewal_order_id']);
    $order->set_status('on-hold');
    $order->set_transaction_id('qa-pdf-settlement-no-real-charge');
    $order->update_meta_data('_arraysubs_settlement_txn', 'qa-pdf-settlement-no-real-charge');
    $order->update_meta_data('_arraysubs_settlement_until', time() + 7200);
    $order->save();
} elseif ($phase === 'restore') {
    foreach (json_decode(file_get_contents($baseline_path), true) as $key => $original) {
        if ($original['exists']) { update_option($key, $original['value']); } else { delete_option($key); }
    }
    flush_rewrite_rules(false);
}

wp_set_current_user($f['customer_id']);
$states = [];
foreach (['existing' => $f['order_id'], 'absent' => $f['absent']['order_id'], 'paid' => $f['paid']['order_id'], 'renewal' => $f['renewal_order_id']] as $key => $id) {
    $order = wc_get_order($id);
    $invoice = function_exists('wcpdf_get_document') ? wcpdf_get_document('invoice', $order) : false;
    $states[$key] = ['id' => $id, 'status' => $order->get_status(), 'invoice_exists' => $invoice && $invoice->exists(),
        'invoice_number' => $order->get_meta('_wcpdf_invoice_number'),
        'native_actions' => array_keys(wc_get_account_orders_actions($order))];
}
echo wp_json_encode(['fixtures' => $f, 'states' => $states, 'policy' => get_option('wpo_wcpdf_documents_settings_invoice'),
    'coming_soon' => get_option('woocommerce_coming_soon'), 'hpos' => \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
