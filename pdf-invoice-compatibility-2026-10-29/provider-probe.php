<?php
/** Temporary MU-plugin for explicitly scoped browser regression probes; remove after QA. */
defined('ABSPATH') || exit;
add_filter('wpo_wcpdf_myaccount_button_text', static function ($label) {
    return get_current_user_id() === 479 && ($_GET['qa_pdf_probe'] ?? '') === 'label'
        ? 'QA <Invoice> & bill' : $label;
});
add_filter('woocommerce_my_account_my_orders_actions', static function ($actions, $order) {
    if (get_current_user_id() === 479 && $order->get_id() === 23966
        && ($_GET['qa_pdf_probe'] ?? '') === 'failure') {
        throw new RuntimeException('QA controlled document provider failure');
    }
    return $actions;
}, 1001, 2);
