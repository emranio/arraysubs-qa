<?php
/** Reversible local visual-QA settings only. */
defined('ABSPATH') || exit;
$path = __DIR__ . '/original-state.json';
$phase = $args[0] ?? 'inspect';
$keys = ['woocommerce_coming_soon', 'wpo_wcpdf_documents_settings_invoice', 'wpo_wcpdf_settings_general', 'wpo_wcpdf_settings_debug'];
if ($phase === 'setup') {
    if (file_exists($path)) { throw new RuntimeException('Visual QA baseline already recorded'); }
    $state = ['options' => [], 'pro_active' => is_plugin_active('arraysubspro/arraysubspro.php')];
    foreach ($keys as $key) {
        $state['options'][$key] = ['exists' => get_option($key, null) !== null, 'value' => get_option($key, null)];
    }
    file_put_contents($path, wp_json_encode($state, JSON_PRETTY_PRINT) . "\n");
    update_option('woocommerce_coming_soon', 'no');
    $invoice = get_option('wpo_wcpdf_documents_settings_invoice', []);
    $invoice['enabled'] = 1;
    $invoice['my_account_buttons'] = 'available';
    update_option('wpo_wcpdf_documents_settings_invoice', $invoice);
    $general = get_option('wpo_wcpdf_settings_general', []);
    $general['download_display'] = 'display';
    update_option('wpo_wcpdf_settings_general', $general);
} elseif ($phase === 'download' || $phase === 'display') {
    $general = get_option('wpo_wcpdf_settings_general', []);
    $general['download_display'] = $phase;
    update_option('wpo_wcpdf_settings_general', $general);
} elseif ($phase === 'restore') {
    foreach (json_decode(file_get_contents($path), true)['options'] as $key => $original) {
        if ($original['exists']) { update_option($key, $original['value']); } else { delete_option($key); }
    }
}
$state = ['pro_active' => is_plugin_active('arraysubspro/arraysubspro.php'), 'pdf_active' => is_plugin_active('woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php')];
foreach ($keys as $key) { $state['options'][$key] = get_option($key, null); }
echo wp_json_encode($state, JSON_PRETTY_PRINT) . "\n";
