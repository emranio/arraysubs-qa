<?php
/** Read-only configuration snapshot for the coordinated native email UI checks. */
defined('ABSPATH') || exit;
global $wpdb;
$email_options = [];
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s ORDER BY option_name",
    $wpdb->esc_like('woocommerce_arraysubs') . '%',
    $wpdb->esc_like('woocommerce_email_') . '%'
));
foreach ($rows as $row) {
    $email_options[$row->option_name] = hash('sha256', $row->option_value);
}
$settings = get_option('arraysubs_settings', []);
$state = [
    'active_plugins' => get_option('active_plugins', []),
    'coming_soon' => get_option('woocommerce_coming_soon'),
    'modules' => $settings['modules'] ?? [],
    'arraysubs_settings_hash' => hash('sha256', serialize($settings)),
    'menu_hash' => hash('sha256', serialize(get_option('arraysubs_myaccount_menu_config', []))),
    'profile_fields_hash' => hash('sha256', serialize(get_option('arraysubs_profile_fields_config', []))),
    'email_option_hashes' => $email_options,
];
$target = __DIR__ . '/' . sanitize_file_name($args[0] ?? 'state.json');
file_put_contents($target, wp_json_encode($state, JSON_PRETTY_PRINT));
echo basename($target) . ' captured; active plugins=' . count($state['active_plugins']) . '; email options=' . count($email_options) . PHP_EOL;
