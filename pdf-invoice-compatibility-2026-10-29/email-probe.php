<?php
/** Inspect WooCommerce attachment context without sending email or changing settings. */
defined('ABSPATH') || exit;

$subscription_id = 23967;
$order_id = 23966;
$subscription = arraysubs_get_subscription($subscription_id);
if (!$subscription instanceof WP_Post || !wc_get_order($order_id) instanceof WC_Order) {
    throw new RuntimeException('The disposable PDF reproduction fixture is unavailable.');
}

$emails = WC()->mailer()->get_emails();
$results = [];
foreach (['arraysubs_renewal_invoice', 'arraysubs_payment_successful'] as $email_id) {
    $email = $emails[$email_id];
    $reflection = new ReflectionClass($email);
    foreach (['subscription_id' => $subscription_id, 'subscription' => $subscription] as $name => $value) {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($email, $value);
    }
    $populate = $reflection->getMethod('populate_placeholders');
    $populate->setAccessible(true);
    $order_property = $reflection->getProperty('order');
    $order_property->setAccessible(true);

    foreach (['with_order' => ['order_id' => $order_id], 'without_order' => []] as $scenario => $arguments) {
        $populate->invoke($email, $arguments);
        $hook_context = null;
        $observer = static function ($attachments, $current_id, $object) use ($email_id, &$hook_context) {
            if ($email_id === $current_id) {
                $hook_context = [
                    'object_type' => get_debug_type($object),
                    'order_id' => $object instanceof WC_Order ? $object->get_id() : null,
                ];
            }
            return $attachments;
        };
        add_filter('woocommerce_email_attachments', $observer, 1, 3);
        try {
            $attachments = $email->get_attachments();
        } finally {
            remove_filter('woocommerce_email_attachments', $observer, 1);
        }
        $internal_order = $order_property->getValue($email);
        $results[$email_id][$scenario] = [
            'internal_order_id' => $internal_order instanceof WC_Order ? $internal_order->get_id() : null,
            'inherited_object_type' => get_debug_type($email->object),
            'inherited_order_id' => $email->object instanceof WC_Order ? $email->object->get_id() : null,
            'attachment_hook_context' => $hook_context,
            'attachment_count' => count($attachments),
        ];
    }
}

echo wp_json_encode([
    'php_version' => PHP_VERSION,
    'subscription_id' => $subscription_id,
    'order_id' => $order_id,
    'configured_invoice_attachment_emails' => get_option('wpo_wcpdf_documents_settings_invoice', [])['attach_to_email_ids'] ?? [],
    'email_results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
