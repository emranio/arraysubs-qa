<?php
/** Send only the disposable fixture email through the configured local Mailpit sink. */
defined('ABSPATH') || exit;
$label = sanitize_text_field($args[0] ?? 'check');
$f = json_decode(file_get_contents(__DIR__ . '/fixtures.json'), true);
$email_id = 'arraysubs_renewal_invoice';
$observed = [];
add_filter('woocommerce_email_enabled_' . $email_id, '__return_true');
add_filter('woocommerce_email_subject_' . $email_id, static fn() => 'QA PDF INTEGRATION ' . $label);
add_filter('woocommerce_email_attachments', static function ($attachments, $id, $object) use ($email_id, &$observed) {
    if ($id === $email_id) {
        $files = [];
        foreach ($attachments as $path) {
            $files[] = ['name' => basename($path), 'bytes' => filesize($path), 'header' => file_get_contents($path, false, null, 0, 8)];
        }
        $observed = ['order_context_id' => $object instanceof WC_Order ? $object->get_id() : null, 'attachments' => $files];
    }
    return $attachments;
}, 1000, 3);
add_filter('wp_mail', static function ($mail) use ($f) {
    if ((array) $mail['to'] !== [$f['customer_email']]) { throw new RuntimeException('QA recipient isolation failed'); }
    return $mail;
});
WC()->mailer()->get_emails()[$email_id]->trigger($f['subscription_id'], ['order_id' => $f['order_id']]);
echo wp_json_encode(['label' => $label, 'email_id' => $email_id, 'recipient' => $f['customer_email']] + $observed, JSON_PRETTY_PRINT) . "\n";
