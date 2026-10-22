<?php
/** Read-only cart QA configuration snapshot. Invoke through WP-CLI --allow-root. */
defined('ABSPATH') || exit;
$settings = get_option('arraysubs_settings', []);
$options = [];
foreach (['template', 'stylesheet', 'current_theme', 'woocommerce_coming_soon', 'woocommerce_store_pages_only', 'woocommerce_private_link', 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_enable_ajax_add_to_cart', 'woocommerce_cart_redirect_after_add'] as $key) {
    $options[$key] = get_option($key, '__ABSENT__');
}
$pages = [];
foreach (['cart', 'checkout'] as $kind) {
    $id = (int) get_option('woocommerce_' . $kind . '_page_id');
    $post = get_post($id);
    $pages[$kind] = $post ? [
        'ID' => $id,
        'title' => $post->post_title,
        'status' => $post->post_status,
        'content' => $post->post_content,
        'url' => get_permalink($post),
    ] : null;
}
$state = [
    'active_plugins' => get_option('active_plugins', []),
    'options' => $options,
    'share_key_hash' => hash('sha256', (string) get_option('woocommerce_share_key')),
    'modules' => $settings['modules'] ?? [],
    'arraysubs_settings_hash' => hash('sha256', serialize($settings)),
    'menu_hash' => hash('sha256', serialize(get_option('arraysubs_myaccount_menu_config', []))),
    'profile_fields_hash' => hash('sha256', serialize(get_option('arraysubs_profile_fields_config', []))),
    'pages' => $pages,
    'version' => ['wordpress' => get_bloginfo('version'), 'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null],
];
$target = __DIR__ . '/browser/' . sanitize_file_name($args[0] ?? 'state.json');
wp_mkdir_p(dirname($target));
file_put_contents($target, wp_json_encode($state, JSON_PRETTY_PRINT));
echo basename($target) . ' captured; active plugins=' . count($state['active_plugins']) . PHP_EOL;
