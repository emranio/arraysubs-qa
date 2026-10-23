<?php
defined('ABSPATH') || exit;

$backup_path = sys_get_temp_dir() . '/arraysubs-message-qa-20261024.json';
$mode = $args[0] ?? 'inspect';
$keys = ['url_rules', 'cpt_rules', 'ecommerce_rules', 'comment_rules', 'purchase_limit_rules'];
if ($mode === 'backup') {
    if (file_exists($backup_path)) {
        WP_CLI::error('Backup already exists; refusing to replace it.');
    }
    file_put_contents($backup_path, wp_json_encode([
        'settings' => get_option('arraysubs_settings'),
        'plugins' => get_option('active_plugins'),
        'saved_at' => get_option('arraysubs_settings_last_saved_at'),
        'coming_soon' => get_option('woocommerce_coming_soon'),
        'admin_color' => get_user_meta(1, 'admin_color', true),
    ]));
    chmod($backup_path, 0600);
    echo "Original settings and plugin state backed up privately.\n";
} elseif ($mode === 'seed') {
    $settings = get_option('arraysubs_settings');
    $settings['members_access']['default_message'] = '<p>QA shared default <strong>membership message</strong>. <a href="http://localhost:10013/my-account/">Sign in</a>.</p>';
    // Use the local site's canonical post path so WordPress does not redirect
    // past the existing demo rule's short exact-match pattern before gating.
    $settings['members_access']['url_rules'][0]['pattern'] = '/2026/09/27/demo-url-members/';
    foreach ($keys as $key) {
        $index = $key === 'purchase_limit_rules' ? 1 : 0;
        $settings['members_access'][$key][$index]['custom_message_enabled'] = false;
        $settings['members_access'][$key][$index]['message'] = '<p>QA custom ' . esc_html($key) . ' <strong>members only</strong>. <a href="http://localhost:10013/my-account/">Join now</a>.</p>';
    }
    update_option('arraysubs_settings', $settings);
    update_option('woocommerce_coming_soon', 'no');
    echo "Seeded message fixtures for five existing rules.\n";
} elseif ($mode === 'state') {
    $settings = get_option('arraysubs_settings');
    $state = $args[1] ?? 'off';
    foreach ($keys as $key) {
        $index = $key === 'purchase_limit_rules' ? 1 : 0;
        $settings['members_access'][$key][$index]['custom_message_enabled'] = $state !== 'off';
        $settings['members_access'][$key][$index]['message'] = match ($state) {
            'empty' => '',
            'blank' => '<p>&nbsp;<br></p>',
            default => '<p>QA custom ' . esc_html($key) . ' <strong>members only</strong>. <a href="http://localhost:10013/my-account/">Join now</a>.</p>',
        };
    }
    update_option('arraysubs_settings', $settings);
    echo "Fixture state: " . $state . "\n";
} elseif ($mode === 'global') {
    $settings = get_option('arraysubs_settings');
    $settings['members_access']['default_message'] = '<p>QA revised shared default.</p>';
    update_option('arraysubs_settings', $settings);
    echo "Shared default revised.\n";
} elseif ($mode === 'core') {
    $plugins = get_option('active_plugins');
    update_option('active_plugins', array_values(array_diff($plugins, ['arraysubspro/arraysubspro.php'])));
    echo "Temporarily running core only.\n";
} elseif ($mode === 'restore') {
    $backup = json_decode(file_get_contents($backup_path), true);
    foreach (['arraysubs_settings' => 'settings', 'active_plugins' => 'plugins', 'arraysubs_settings_last_saved_at' => 'saved_at', 'woocommerce_coming_soon' => 'coming_soon'] as $option => $key) {
        $backup[$key] === false ? delete_option($option) : update_option($option, $backup[$key]);
    }
    update_user_meta(1, 'admin_color', $backup['admin_color']);
    echo wp_json_encode([
        'settings_unchanged' => get_option('arraysubs_settings') === $backup['settings'],
        'plugins_unchanged' => get_option('active_plugins') === $backup['plugins'],
        'saved_at_unchanged' => get_option('arraysubs_settings_last_saved_at') === $backup['saved_at'],
        'coming_soon_unchanged' => get_option('woocommerce_coming_soon') === $backup['coming_soon'],
        'admin_color_unchanged' => get_user_meta(1, 'admin_color', true) === $backup['admin_color'],
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    $settings = get_option('arraysubs_settings');
    $out = [];
    foreach ($keys as $key) {
        $index = $key === 'purchase_limit_rules' ? 1 : 0;
        $out[$key] = array_intersect_key($settings['members_access'][$key][$index], array_flip(['id', 'message', 'custom_message_enabled']));
    }
    foreach ([9237, 9239, 6702, 6698] as $id) {
        $out['targets'][$id] = ['url' => get_permalink($id), 'per_post_active' => \ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig::isActive(\ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig::get($id))];
    }
    echo wp_json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
}
