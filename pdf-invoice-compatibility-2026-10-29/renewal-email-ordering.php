<?php
/**
 * Root-run only: run-wp eval-file .../renewal-email-ordering.php before|after.
 * Uses real subscription credit, renewal creation, registered event listeners
 * and configured local mail transport. No payment processing or fake credits.
 */
defined('ABSPATH') || exit;

use ArraySubs\Features\RecurringBilling\Services\OrderCreation;
use ArraySubsPro\Features\StoreCredit\Services\CreditManager;

$label = sanitize_key($args[0] ?? 'before');
if (!preg_match('/^(?:before|after)(?:-[a-z0-9-]+)?$/', $label)) {
    throw new RuntimeException('Use a before/after label; each label creates one isolated renewal.');
}
$path = __DIR__ . '/evidence/renewal-email-ordering-' . $label . '.json';
if (file_exists($path)) {
    throw new RuntimeException('This label already has evidence. Inspect its order/credit IDs before another run.');
}
$subscription_id = 23976;
$customer_id = 479;
$subscription = arraysubs_get_subscription($subscription_id);
if (!$subscription instanceof WP_Post || (int) get_post_meta($subscription_id, '_customer_id', true) !== $customer_id) {
    throw new RuntimeException('Expected disposable subscription 23976 owned by customer 479.');
}
$customer = get_user_by('ID', $customer_id);
if (!$customer || 'qa.pdf.invoice.20261029@example.test' !== $customer->user_email) {
    throw new RuntimeException('Disposable customer identity does not match.');
}
$settings = arraysubs_get_settings();
if (empty($settings['store_credit']['auto_apply_to_renewals'] ?? true)
    || (float) ($settings['store_credit']['min_order_amount'] ?? 0) > 5) {
    throw new RuntimeException('Enable actual renewal credit auto-application with minimum amount <= 5 before testing.');
}
if (!arraysubs_is_email_enabled('renewal_invoice')
    || arraysubs_is_automatic_payment_subscription($subscription_id)) {
    throw new RuntimeException('This fixture must use enabled manual renewal-invoice emails.');
}
$emails = WC()->mailer()->get_emails();
if (!$emails['arraysubs_renewal_invoice']->is_enabled()) {
    throw new RuntimeException('Enable the actual WooCommerce ArraySubs Renewal Invoice email before testing.');
}
if (CreditManager::getCustomerCredit($customer_id) !== 0.0 || CreditManager::getSubscriptionCredit($subscription_id) !== 0.0) {
    throw new RuntimeException('Both disposable credit balances must start at zero to avoid consuming existing credit.');
}
$document = wcpdf_get_document('invoice', null);
if (!$document || !in_array('arraysubs_renewal_invoice', $document->get_attach_to_email_ids('pdf'), true)) {
    throw new RuntimeException('Select the real ArraySubs Renewal Invoice email in PDF Invoices Attach to settings first.');
}

$record = [
    'label' => $label,
    'php_version' => PHP_VERSION,
    'subscription_id' => $subscription_id,
    'customer_id' => $customer_id,
    'recipient' => $customer->user_email,
    'created_subscription_ids' => [],
    'created_order_ids' => [],
    'credit_grant' => 5,
    'customer_credit_before' => 0,
    'subscription_credit_before' => 0,
    'invoice_mail_attempts' => [],
    'email_delivery_results' => [],
];
$save = static function () use (&$record, $path): void {
    file_put_contents($path, wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
};
$save();
$order_id = 0;
$subject = 'QA PDF RENEWAL ORDERING ' . strtoupper($label);
add_filter('woocommerce_email_subject_arraysubs_renewal_invoice', static fn() => $subject);
add_filter('wp_mail', static function ($mail) use (&$record, $customer, $subject) {
    if ((array) $mail['to'] !== [$customer->user_email]) {
        throw new RuntimeException('QA recipient isolation failed.');
    }
    if ($mail['subject'] === $subject) {
        $body_path = __DIR__ . '/evidence/renewal-email-ordering-' . $record['label'] . '-body.html';
        file_put_contents($body_path, $mail['message']);
        $record['invoice_mail_attempts'][] = ['subject' => $mail['subject'], 'body_path' => $body_path];
    }
    return $mail;
});
add_filter('woocommerce_email_attachments', static function ($attachments, $id, $object) use (&$record, &$order_id) {
    if ('arraysubs_renewal_invoice' !== $id || !$object instanceof WC_Order || $object->get_id() !== $order_id) {
        return $attachments;
    }
    $record['invoice_order_total_at_send'] = (float) $object->get_total();
    foreach ($attachments as $path) {
        $destination = __DIR__ . '/evidence/renewal-email-ordering-' . $record['label'] . '-' . basename($path);
        copy($path, $destination);
        $record['invoice_attachments'][] = ['original_path' => $path, 'saved_path' => $destination, 'bytes' => filesize($path)];
    }
    return $attachments;
}, PHP_INT_MAX, 3);
add_action('arraysubs_email_delivery_result', static function ($email, $sent, $context) use (&$record, &$order_id) {
    if ('arraysubs_renewal_invoice' === $email->id && (int) ($context['order_id'] ?? 0) === $order_id) {
        $record['email_delivery_results'][] = ['email_id' => $email->id, 'accepted_by_transport' => $sent];
    }
}, 10, 3);

try {
    $currency = (string) get_post_meta($subscription_id, '_currency', true) ?: get_woocommerce_currency();
    $grant = CreditManager::grantSubscriptionCreditOnce(
        $subscription_id, 5, CreditManager::SOURCE_ADMIN, $subscription_id,
        'QA PDF renewal invoice ordering ' . $label, $currency,
        hash('sha256', 'qa-pdf-renewal-email-ordering-20261029-' . $label)
    );
    if (is_wp_error($grant)) {
        throw new RuntimeException($grant->get_error_message());
    }
    $record['credit_grant_log_id'] = $grant;
    $save();
    $order = (new OrderCreation())->createRenewalOrder($subscription_id);
    if (!$order instanceof WC_Order) {
        throw new RuntimeException('The genuine renewal-order builder failed.');
    }
    $order_id = $order->get_id();
    $record['created_order_ids'][] = $order_id;
    $record['order_total_before_event'] = (float) $order->get_total();
    arraysubs_add_subscription_order($subscription_id, $order_id);
    $save();
    do_action('arraysubs_renewal_invoice_created', $subscription_id, $order_id);
    $final_order = wc_get_order($order_id);
    $record['final_order_total'] = (float) $final_order->get_total();
    $record['final_order_status'] = $final_order->get_status();
    $record['credit_from_subscription'] = (float) $final_order->get_meta('_arraysubs_credit_from_subscription');
    $record['credit_from_customer'] = (float) $final_order->get_meta('_arraysubs_credit_from_customer');
    $record['invoice_email_sent_marker'] = $final_order->get_meta('_arraysubs_renewal_invoice_email_sent');
    $record['subscription_credit_after'] = CreditManager::getSubscriptionCredit($subscription_id);
    $record['customer_credit_after'] = CreditManager::getCustomerCredit($customer_id);
    $record['email_total_matches_final_order'] = isset($record['invoice_order_total_at_send'])
        && $record['invoice_order_total_at_send'] === $record['final_order_total'];
    $save();
} catch (Throwable $error) {
    $record['error'] = $error->getMessage();
    $save();
    throw $error;
}
echo wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
