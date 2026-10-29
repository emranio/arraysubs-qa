<?php
/** Disposable fixtures for real-browser storefront override verification. */
defined('ABSPATH') || exit;
$phase = $args[0] ?? 'inspect';
$state_file = '/tmp/arraysubs-template-qa-state.json';
$record_file = __DIR__ . '/evidence/fixtures.json';
$theme = WP_CONTENT_DIR . '/themes/arraysubs-template-qa';
$templates = [
    'arraysubs' => [
        'customer-portal/my-account-subscriptions.php' => 'CustomerPortal/views/my-account-subscriptions.php',
        'customer-portal/view-subscription.php' => 'CustomerPortal/views/view-subscription.php',
        'customer-portal/cancellation-contact-notice.php' => 'CustomerPortal/views/cancellation-contact-notice.php',
        'customer-portal/subscription-cards.php' => 'CustomerPortal/views/subscription-cards.php',
        'subscription-products/product-subscription-info.php' => 'SubscriptionProducts/views/product-subscription-info.php',
        'subscription-products/variable-product-subscription-info.php' => 'SubscriptionProducts/views/variable-product-subscription-info.php',
        'subscription-box/box-builder.php' => 'SubscriptionBox/views/box-builder.php',
        'subscription-checkout/order-subscriptions.php' => 'SubscriptionCheckout/views/order-subscriptions.php',
        'members-access/access-denied.php' => 'MembersAccess/views/access-denied.php',
        'members-access/comment-restricted.php' => 'MembersAccess/views/comment-restricted.php',
    ],
    'arraysubspro' => [
        'feature-manager/my-account-features.php' => 'FeatureManager/views/my-account-features.php',
        'store-credit/my-account-store-credit.php' => 'StoreCredit/templates/my-account-store-credit.php',
        'profile-fields/avatar-section.php' => 'ProfileFields/views/avatar-section.php',
        'fixed-period-membership/product-info.php' => 'FixedPeriodMembership/views/product-info.php',
        'flexible-subscription/product-options.php' => 'FlexibleSubscription/views/product-options.php',
    ],
];
add_filter('pre_wp_mail', '__return_true');
if ($phase === 'setup') {
    if (file_exists($state_file) || file_exists($theme)) throw new RuntimeException('QA state/theme already exists.');
    $state = [];
    foreach (['stylesheet', 'template', 'current_theme', 'active_plugins', 'woocommerce_coming_soon', 'arraysubs_settings', 'arraysubs_avatar_settings'] as $key) {
        $state[$key] = ['exists' => get_option($key, null) !== null, 'value' => get_option($key, null)];
    }
    file_put_contents($state_file, wp_json_encode($state));
    chmod($state_file, 0600);
    wp_mkdir_p($theme);
    file_put_contents($theme . '/style.css', "/*\nTheme Name: ArraySubs Template QA\nTemplate: " . get_template() . "\nVersion: 1.0\n*/\n");
    file_put_contents($theme . '/page-template-qa.php', '<?php /* Template Name: QA Comments */ ?><!doctype html><html><head><?php wp_head(); ?></head><body><?php while(have_posts()){the_post();the_content();comments_template();} wp_footer(); ?></body></html>');
    foreach ($templates as $plugin => $files) foreach ($files as $path => $source) {
        $target = $theme . '/' . $plugin . '/' . $path;
        wp_mkdir_p(dirname($target));
        $code = file_get_contents(WP_PLUGIN_DIR . '/' . $plugin . '/src/Features/' . $source);
        $label = 'CHILD ' . $plugin . '/' . $path;
        $code = str_replace("defined('ABSPATH') || exit;", "defined('ABSPATH') || exit;\necho '<p data-template-qa=\"" . esc_attr($label) . "\">QA " . esc_html($label) . "</p>';", $code);
        file_put_contents($target, $code);
    }
    $password = wp_generate_password(28, false);
    $user = wp_insert_user(['user_login' => 'qa_template_20261029', 'user_pass' => $password, 'user_email' => 'qa.template.20261029@example.test', 'role' => 'customer', 'display_name' => 'QA Template Customer']);
    if (is_wp_error($user)) throw new RuntimeException($user->get_error_message());
    file_put_contents('/tmp/arraysubs-template-qa-password', $password);
    chmod('/tmp/arraysubs-template-qa-password', 0600);
    $product = new WC_Product_Simple();
    $product->set_name('QA Template Subscription 20261029');
    $product->set_status('publish');
    $product->set_catalog_visibility('hidden');
    $product->set_virtual(true);
    $product->set_regular_price('25');
    $product->update_meta_data('_is_subscription', 'yes');
    $product->update_meta_data('_subscription_period', 'month');
    $product->update_meta_data('_subscription_interval', 1);
    $product->save();
    $order = wc_create_order(['customer_id' => $user, 'status' => 'pending']);
    $order->set_created_via('qa-template-overrides');
    $order->add_product($product, 1);
    $order->calculate_totals();
    $order->save();
    $sub = arraysubs_create_subscription(['customer_id' => $user, 'product_id' => $product->get_id(), 'subscription_price' => 25, 'recurring_amount' => 25, 'status' => 'arraysubs-pending', 'parent_order_id' => $order->get_id()]);
    if (is_wp_error($sub)) throw new RuntimeException($sub->get_error_message());
    if (!arraysubs_link_subscription_to_parent_order($order, $sub)) throw new RuntimeException('Order link failed.');
    $page = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'QA Template Comments', 'post_name' => 'qa-template-comments-20261029', 'post_content' => '<p>QA comments template page.</p>', 'comment_status' => 'open']);
    update_post_meta($page, '_wp_page_template', 'page-template-qa.php');
    $denied = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'QA Template Denied', 'post_name' => 'qa-template-denied-20261029', 'post_content' => '<p>Protected QA content.</p>']);
    $record = ['customer_id' => $user, 'customer_login' => 'qa_template_20261029', 'customer_email' => 'qa.template.20261029@example.test', 'customer_role' => 'customer', 'product_id' => $product->get_id(), 'order_id' => $order->get_id(), 'subscription_id' => $sub, 'comments_page_id' => $page, 'denied_page_id' => $denied, 'subscription_url' => wc_get_endpoint_url('view-subscription', $sub, wc_get_page_permalink('myaccount')), 'order_url' => wc_get_endpoint_url('view-order', $order->get_id(), wc_get_page_permalink('myaccount')), 'product_url' => $product->get_permalink(), 'comments_url' => get_permalink($page), 'denied_url' => get_permalink($denied), 'theme_path' => $theme];
    file_put_contents($record_file, wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $settings = get_option('arraysubs_settings');
    $settings['members_access']['comment_rules'][] = ['id' => 'qa-template-comments', 'enabled' => true, 'target_type' => 'specific_posts', 'post_ids' => [$page], 'conditions' => ['logic' => 'and', 'rules' => [['type' => 'user_role', 'roles' => ['administrator']]]], 'restriction_mode' => 'hide_comments', 'message' => 'QA comments denied.', 'custom_message_enabled' => true];
    $settings['members_access']['url_rules'][] = ['id' => 'qa-template-denied', 'enabled' => true, 'pattern' => '/qa-template-denied-20261029/', 'pattern_type' => 'exact', 'conditions' => ['logic' => 'and', 'rules' => [['type' => 'user_role', 'roles' => ['administrator']]]], 'action' => '403', 'message' => 'QA template access denied.'];
    update_option('arraysubs_settings', $settings);
    update_option('arraysubs_avatar_settings', ['enabled' => true, 'max_file_size' => 2]);
    update_option('woocommerce_coming_soon', 'no');
    switch_theme('arraysubs-template-qa');
    echo wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} elseif ($phase === 'restore') {
    $record = json_decode(file_get_contents($record_file), true);
    $state = json_decode(file_get_contents($state_file), true);
    switch_theme($state['stylesheet']['value']);
    foreach ($state as $key => $saved) {
        if ($saved['exists']) update_option($key, $saved['value']); else delete_option($key);
    }
    foreach (['comments_page_id', 'denied_page_id', 'subscription_id', 'product_id'] as $key) wp_delete_post($record[$key], true);
    $order = wc_get_order($record['order_id']);
    if ($order) $order->delete(true);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($record['customer_id']);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) { if ($file->isDir()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
    rmdir($theme);
    delete_option('theme_mods_arraysubs-template-qa');
    echo wp_json_encode(['stylesheet' => get_stylesheet(), 'template' => get_template(), 'coming_soon' => get_option('woocommerce_coming_soon'), 'pro_active' => is_plugin_active('arraysubspro/arraysubspro.php'), 'theme_removed' => !file_exists($theme), 'fixture_user_removed' => !get_user_by('id', $record['customer_id'])], JSON_PRETTY_PRINT);
} else {
    echo file_get_contents($record_file);
}
