<?php
defined('ABSPATH') || exit;

use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;

$dir = __DIR__;
$private = sys_get_temp_dir() . '/arraysubs-unified-restrictions-20261024.json';
$mode = $args[0] ?? 'inspect';
$manifest = $dir . '/fixtures.json';
$elementor_settings = static function (array $config): array {
    $map = ['status' => 'status', 'products' => 'products', 'variations' => 'variations', 'purchased' => 'purchased', 'roles' => 'roles', 'lifetimeSpent' => 'lifetime_spent', 'condition' => 'condition', 'feature' => 'feature', 'featureMatch' => 'feature_match', 'featureValue' => 'feature_value', 'featureOp' => 'feature_op', 'aggregation' => 'aggregation', 'message' => 'message', 'accessVersion' => 'access_version'];
    $out = ['arraysubs_enable' => $config['enabled'] ? 'yes' : ''];
    foreach ($map as $source => $target) {
        $out['arraysubs_' . $target] = $config[$source];
    }
    $out['arraysubs_login_required'] = $config['loginRequired'] ? 'yes' : '';
    $out['arraysubs_show_to_admins'] = $config['showToAdmins'] ? 'yes' : '';
    $out['arraysubs_custom_message_enabled'] = $config['useDefaultMessage'] ? '' : 'yes';
    return $out;
};
$write = static function (array $fixtures, array $config) use ($elementor_settings): void {
    PostRestrictionConfig::save($fixtures['post'], $config);
    $child = ['blockName' => 'core/paragraph', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '<p>QA PROTECTED GUTENBERG CONTENT</p>', 'innerContent' => ['<p>QA PROTECTED GUTENBERG CONTENT</p>']];
    $block = ['blockName' => 'arraysubs/content-restricted', 'attrs' => $config, 'innerBlocks' => [$child], 'innerHTML' => '<div class="wp-block-arraysubs-content-restricted"></div>', 'innerContent' => ['<div class="wp-block-arraysubs-content-restricted">', null, '</div>']];
    wp_update_post(['ID' => $fixtures['gutenberg'], 'post_content' => wp_slash(serialize_block($block))]);
    $data = [['id' => 'qaunif01', 'elType' => 'container', 'settings' => $elementor_settings($config), 'elements' => [['id' => 'qaunif02', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['title' => 'QA PROTECTED ELEMENTOR CONTENT', 'header_size' => 'h2'], 'elements' => []]], 'isInner' => false]];
    update_post_meta($fixtures['elementor'], '_elementor_data', wp_slash(wp_json_encode($data)));
    delete_post_meta($fixtures['elementor'], '_elementor_element_cache');
    delete_post_meta($fixtures['elementor'], '_elementor_css');
    clean_post_cache($fixtures['elementor']);
};

if ($mode === 'setup') {
    if (file_exists($private) || file_exists($manifest)) {
        WP_CLI::error('Existing fixture/backup found; refusing to overwrite.');
    }
    file_put_contents($private, wp_json_encode(['settings' => get_option('arraysubs_settings'), 'plugins' => get_option('active_plugins'), 'saved_at' => get_option('arraysubs_settings_last_saved_at'), 'coming_soon' => get_option('woocommerce_coming_soon')]));
    chmod($private, 0600);
    $fixtures = ['admin' => 1, 'member' => 224];
    $fixtures['customer'] = wp_insert_user(['user_login' => 'qa_unified_restrictions_20261024', 'user_email' => 'qa-unified-restrictions-20261024@example.test', 'user_pass' => wp_generate_password(40), 'role' => 'customer']);
    if (is_wp_error($fixtures['customer'])) {
        WP_CLI::error($fixtures['customer']->get_error_message());
    }
    foreach (['post', 'gutenberg', 'elementor', 'redirect'] as $kind) {
        $fixtures[$kind] = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'QA unified ' . $kind . ' 20261024', 'post_name' => 'qa-unified-' . $kind . '-20261024', 'post_content' => '<p>QA PROTECTED ' . strtoupper($kind) . ' CONTENT</p>']);
        $fixtures['urls'][$kind] = get_permalink($fixtures[$kind]);
    }
    update_post_meta($fixtures['elementor'], '_elementor_edit_mode', 'builder');
    update_post_meta($fixtures['elementor'], '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '4.3.2');
    update_post_meta($fixtures['elementor'], '_wp_page_template', 'elementor_canvas');
    file_put_contents($manifest, wp_json_encode($fixtures, JSON_PRETTY_PRINT));
    $settings = get_option('arraysubs_settings');
    $settings['members_access']['default_message'] = '<p>QA UNIFIED DEFAULT <strong>message</strong>. <a href="http://localhost:10013/my-account/">Sign in</a>.</p>';
    update_option('arraysubs_settings', $settings);
    update_option('woocommerce_coming_soon', 'no');
    $config = PostRestrictionConfig::sanitize(['enabled' => true]);
    $write($fixtures, $config);
    echo wp_json_encode($fixtures, JSON_PRETTY_PRINT) . PHP_EOL;
} elseif ($mode === 'state') {
    $fixtures = json_decode(file_get_contents($manifest), true);
    $state = $args[1] ?? 'login_only';
    $config = PostRestrictionConfig::sanitize(['enabled' => true, 'loginRequired' => true, 'showToAdmins' => false]);
    if (!in_array($state, ['login_only', 'public'], true)) {
        $config['roles'] = ['qa_nonexistent_role'];
        $config['products'] = [2147483000];
        $config['condition'] = 'or';
    }
    if (in_array($state, ['any_pass', 'all_fail', 'admin_bypass'], true)) {
        $config['roles'] = ['demo_premium'];
        $config['condition'] = $state === 'any_pass' ? 'or' : 'and';
    }
    if ($state === 'admin_bypass') {
        $config['showToAdmins'] = true;
    }
    if (in_array($state, ['feature_pass', 'feature_fail'], true)) {
        $config['roles'] = [];
        $config['products'] = [];
        $config['feature'] = 'Team Seats';
        $config['featureMatch'] = 'min_single';
        $config['featureValue'] = $state === 'feature_pass' ? '5' : '1000000000';
        $config['condition'] = 'and';
    }
    if (in_array($state, ['custom', 'empty', 'blank'], true)) {
        $config['useDefaultMessage'] = false;
        $config['message'] = match ($state) {
            'empty' => '', 'blank' => '<p>&nbsp;<br></p>',
            default => '<p>QA UNIFIED CUSTOM <strong>message</strong>. <a href="http://localhost:10013/my-account/">Join now</a>.</p>',
        };
    }
    if ($state === 'public') {
        $config['loginRequired'] = false;
    }
    if ($state === 'redirect') {
        $config['deniedAction'] = 'redirect';
        $config['redirectType'] = 'page';
        $config['redirectPageId'] = $fixtures['redirect'];
    }
    $write($fixtures, $config);
    echo 'Fixture state: ' . $state . PHP_EOL;
} elseif ($mode === 'pro') {
    $backup = json_decode(file_get_contents($private), true);
    $plugins = get_option('active_plugins');
    $pro = 'arraysubspro/arraysubspro.php';
    if (in_array($pro, $backup['plugins'], true) && !in_array($pro, $plugins, true)) {
        array_splice($plugins, array_search($pro, $backup['plugins'], true), 0, [$pro]);
        update_option('active_plugins', $plugins);
    }
    echo "Original Pro activation restored.\n";
} elseif ($mode === 'core') {
    update_option('active_plugins', array_values(array_diff(get_option('active_plugins'), ['arraysubspro/arraysubspro.php'])));
    echo "Core-only state enabled.\n";
} elseif ($mode === 'restore') {
    $backup = json_decode(file_get_contents($private), true);
    $fixtures = json_decode(file_get_contents($manifest), true);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach (['post', 'gutenberg', 'elementor', 'redirect', 'shortcode_redirect', 'author_post', 'author_gutenberg', 'author_elementor'] as $kind) {
        if (!empty($fixtures[$kind])) {
            wp_delete_post($fixtures[$kind], true);
        }
    }
    wp_delete_user($fixtures['customer']);
    // Restore only the settings changed by this run so parallel workspace work
    // cannot lose unrelated configuration or plugin activations.
    $current_settings = get_option('arraysubs_settings');
    $expected_default = (string) ($current_settings['members_access']['default_message'] ?? '');
    if (str_contains($expected_default, 'QA UNIFIED')) {
        $current_settings['members_access']['default_message'] = $backup['settings']['members_access']['default_message'];
        update_option('arraysubs_settings', $current_settings);
    }
    $current_plugins = get_option('active_plugins');
    $pro = 'arraysubspro/arraysubspro.php';
    if (in_array($pro, $backup['plugins'], true) && !in_array($pro, $current_plugins, true)) {
        array_splice($current_plugins, array_search($pro, $backup['plugins'], true), 0, [$pro]);
        update_option('active_plugins', $current_plugins);
    }
    if (get_option('woocommerce_coming_soon') === 'no') {
        $backup['coming_soon'] === false ? delete_option('woocommerce_coming_soon') : update_option('woocommerce_coming_soon', $backup['coming_soon']);
    }
    echo wp_json_encode(['settings_restored' => get_option('arraysubs_settings') === $backup['settings'], 'plugins_restored' => get_option('active_plugins') === $backup['plugins'], 'saved_at_restored' => get_option('arraysubs_settings_last_saved_at') === $backup['saved_at'], 'visibility_restored' => get_option('woocommerce_coming_soon') === $backup['coming_soon'], 'pages_removed' => !get_post($fixtures['post']) && !get_post($fixtures['gutenberg']) && !get_post($fixtures['elementor']), 'customer_removed' => !get_userdata($fixtures['customer'])], JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo file_get_contents($manifest);
}
