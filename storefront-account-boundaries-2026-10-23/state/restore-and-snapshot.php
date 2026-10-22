<?php
/** Scoped browser QA restoration; invoke with WP-CLI eval-file --allow-root. */
defined('ABSPATH') || exit;

$qa_dir = __DIR__;
$initial = json_decode(file_get_contents($qa_dir . '/initial.json'), true);
$visibility = json_decode(file_get_contents($qa_dir . '/visibility-initial.json'), true);
$restore = ['coming_soon_before' => get_option('woocommerce_coming_soon')];
update_option('woocommerce_coming_soon', $visibility['coming_soon']);
$restore['coming_soon_after'] = get_option('woocommerce_coming_soon');

$cart_key = '_woocommerce_persistent_cart_1';
$initial_cart_absent = !array_key_exists($cart_key, $initial['customer_224']['meta']);
$current_cart = get_user_meta(224, $cart_key, true);
$restore['cart_initial_absent'] = $initial_cart_absent;
if ($initial_cart_absent && ['cart' => []] === $current_cart) {
    $restore['new_empty_cart_removed'] = delete_user_meta(224, $cart_key);
} else {
    $restore['new_empty_cart_removed'] = false;
    $restore['cart_preserved'] = metadata_exists('user', 224, $cart_key);
}

$customer = get_userdata(224);
$meta = [];
foreach (get_user_meta(224) as $key => $values) {
    if (in_array($key, ['nickname', 'first_name', 'last_name', 'description', '_arraysubs_additional_shipping_addresses', $cart_key], true)
        || str_starts_with($key, 'billing_') || str_starts_with($key, 'shipping_')) {
        $meta[$key] = $values;
    }
}
$settings = get_option('arraysubs_settings', []);
$snapshot = [
    'active_plugins' => get_option('active_plugins', []),
    'modules' => $settings['modules'] ?? [],
    'portal_settings' => $settings['customer_portal'] ?? [],
    'profile_settings' => $settings['profile_builder'] ?? [],
    'myaccount_menu' => get_option('arraysubs_myaccount_menu_config', []),
    'profile_fields' => get_option('arraysubs_profile_fields_config', []),
    'customer_224' => [
        'ID' => $customer->ID,
        'user_login' => $customer->user_login,
        'user_email' => $customer->user_email,
        'display_name' => $customer->display_name,
        'roles' => $customer->roles,
        'meta' => $meta,
    ],
    'pages' => [
        'account' => wc_get_page_permalink('myaccount'),
        'shop' => wc_get_page_permalink('shop'),
        'cart' => wc_get_cart_url(),
    ],
];
$visibility_final = [
    'coming_soon' => get_option('woocommerce_coming_soon'),
    'private_link' => get_option('woocommerce_private_link'),
    'store_pages_only' => get_option('woocommerce_store_pages_only'),
    'share_key_hash' => hash('sha256', (string) get_option('woocommerce_share_key')),
];
file_put_contents($qa_dir . '/restoration.json', wp_json_encode($restore, JSON_PRETTY_PRINT));
file_put_contents($qa_dir . '/final.json', wp_json_encode($snapshot, JSON_PRETTY_PRINT));
file_put_contents($qa_dir . '/visibility-final.json', wp_json_encode($visibility_final, JSON_PRETTY_PRINT));
echo wp_json_encode($restore, JSON_PRETTY_PRINT) . PHP_EOL;
