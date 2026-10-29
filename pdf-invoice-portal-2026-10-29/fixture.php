<?php
/** Disposable fixtures for the customer-reported PDF invoice portal failure. */
defined('ABSPATH') || exit;

$phase = $args[0] ?? 'inspect';
$record_path = __DIR__ . '/fixtures.json';
add_filter('pre_wp_mail', '__return_true');

if ('setup' === $phase) {
    if (file_exists($record_path)) {
        throw new RuntimeException('Fixture record already exists. Inspect it before creating another fixture.');
    }

    $password = wp_generate_password(28, false);
    $customer_id = wp_insert_user([
        'user_login' => 'qa_pdf_invoice_20261029',
        'user_pass' => $password,
        'user_email' => 'qa.pdf.invoice.20261029@example.test',
        'display_name' => 'QA PDF Invoice',
        'first_name' => 'QA',
        'last_name' => 'PDF Invoice',
        'role' => 'customer',
    ]);
    if (is_wp_error($customer_id)) {
        throw new RuntimeException($customer_id->get_error_message());
    }
    file_put_contents('/tmp/arraysubs-pdf-repro-password', $password);
    chmod('/tmp/arraysubs-pdf-repro-password', 0600);

    $product = new WC_Product_Simple();
    $product->set_name('QA PDF Invoice Portal 20261029');
    $product->set_status('publish');
    $product->set_catalog_visibility('hidden');
    $product->set_virtual(true);
    $product->set_regular_price('25');
    $product->save();

    $order = wc_create_order(['customer_id' => $customer_id, 'status' => 'pending']);
    if (is_wp_error($order)) {
        throw new RuntimeException($order->get_error_message());
    }
    $order->set_billing_first_name('QA');
    $order->set_billing_last_name('PDF Invoice');
    $order->set_billing_email('qa.pdf.invoice.20261029@example.test');
    $order->set_created_via('qa-pdf-invoice-reproduction');
    $order->add_product($product, 1);
    $order->calculate_totals();
    $order->save();

    $subscription_id = arraysubs_create_subscription([
        'customer_id' => $customer_id,
        'product_id' => $product->get_id(),
        'subscription_price' => 25,
        'recurring_amount' => 25,
        'status' => 'arraysubs-pending',
        'parent_order_id' => $order->get_id(),
        'payment_method_title' => 'Manual QA fixture',
    ]);
    if (is_wp_error($subscription_id)) {
        throw new RuntimeException($subscription_id->get_error_message());
    }
    if (!arraysubs_link_subscription_to_parent_order($order, $subscription_id)) {
        throw new RuntimeException('Could not link disposable parent order.');
    }

    $record = [
        'customer_id' => $customer_id,
        'customer_login' => 'qa_pdf_invoice_20261029',
        'customer_email' => 'qa.pdf.invoice.20261029@example.test',
        'customer_role' => 'customer',
        'product_id' => $product->get_id(),
        'order_id' => $order->get_id(),
        'subscription_id' => $subscription_id,
        'subscription_url' => wc_get_endpoint_url('view-subscription', $subscription_id, wc_get_page_permalink('myaccount')),
        'orders_url' => wc_get_account_endpoint_url('orders'),
    ];
    file_put_contents($record_path, wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

$record = json_decode(file_get_contents($record_path), true);
$order = wc_get_order($record['order_id']);
wp_set_current_user($record['customer_id']);

if ('generate' === $phase) {
    $invoice = wcpdf_get_document('invoice', $order, true);
    if (!$invoice || !$invoice->exists()) {
        throw new RuntimeException('The official PDF plugin did not create an invoice.');
    }
}

$invoice = wcpdf_get_document('invoice', $order);
$actions = wc_get_account_orders_actions($order);
$safe_actions = [];
foreach ($actions as $key => $action) {
    $safe_actions[$key] = ['name' => $action['name'] ?? '', 'has_url' => !empty($action['url'])];
}
$result = $record + [
    'cli_php_version' => PHP_VERSION,
    'core_version' => ARRAYSUBS_VERSION,
    'pro_version' => constant('ARRAYSUBSPRO_VERSION'),
    'woocommerce_version' => WC_VERSION,
    'pdf_plugin_version' => WPO_WCPDF()->version,
    'invoice_class' => $invoice ? get_class($invoice) : null,
    'invoice_exists' => $invoice ? $invoice->exists() : false,
    'deprecated_get_invoice_function_exists' => function_exists('wcpdf_get_invoice'),
    'invoice_get_endpoint_url_callable' => $invoice && is_callable([$invoice, 'get_endpoint_url']),
    'endpoint_get_document_link_callable' => is_callable([WPO_WCPDF()->endpoint, 'get_document_link']),
    'invoice_allowed_in_my_account' => $invoice && $invoice->is_allowed_in_my_account('available'),
    'standard_woocommerce_actions' => $safe_actions,
];
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
