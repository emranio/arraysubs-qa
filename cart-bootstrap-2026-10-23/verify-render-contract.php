<?php
/** Supplemental request-local render checks; real browser acceptance is separate. */
defined('ABSPATH') || exit;
wc_load_cart();
global $wp_filter, $wp_query, $post;
$callback = null;
foreach (($wp_filter['render_block_woocommerce/mini-cart']->callbacks ?? []) as $items) {
    foreach ($items as $item) {
        $candidate = $item['function'];
        if (is_array($candidate) && $candidate[0] instanceof \ArraySubs\Features\SubscriptionCheckout\Services\MiniCartCompatibility) {
            $callback = $candidate;
        }
    }
}
if (!$callback) { throw new RuntimeException('Compatibility service not loaded.'); }
$results = [];
$context = '';
// Woo caches page detection during CLI boot. Exercise its public page-conditional
// filters here; actual URL detection is covered by the separate browser matrix.
add_filter('woocommerce_is_cart', static function () use (&$context): bool { return $context === 'cart'; });
add_filter('woocommerce_is_checkout', static function () use (&$context): bool { return $context === 'checkout'; });
$render = static function (int $page_id, string $page, string $style) use (&$wp_query, &$post, &$context): array {
    $context = $page;
    $wp_query = new WP_Query(['page_id' => $page_id]);
    $post = get_post($page_id);
    wp_dequeue_script_module('woocommerce/mini-cart');
    $content = do_blocks('<!-- wp:woocommerce/mini-cart ' . wp_json_encode(['cartAndCheckoutRenderStyle' => $style]) . ' /-->');
    return ['content' => $content, 'queued' => in_array('woocommerce/mini-cart', wp_script_modules()->get_queue(), true), 'is_cart' => is_cart(), 'is_checkout' => is_checkout()];
};
foreach (['cart' => (int) get_option('woocommerce_cart_page_id'), 'checkout' => (int) get_option('woocommerce_checkout_page_id')] as $page => $page_id) {
    foreach (['hidden', 'removed'] as $style) {
        remove_filter('render_block_woocommerce/mini-cart', $callback);
        $before = $render($page_id, $page, $style);
        add_filter('render_block_woocommerce/mini-cart', $callback);
        $after = $render($page_id, $page, $style);
        $results[] = ['case' => $page . '-' . $style, 'same_markup' => $before['content'] === $after['content'], 'before_queued' => $before['queued'], 'after_queued' => $after['queued'], 'cart_conditional' => $after['is_cart'], 'checkout_conditional' => $after['is_checkout'], 'passed' => $before['content'] === $after['content'] && !$after['queued']];
    }
}
foreach (['<div data-wp-interactive="woocommerce/mini-cart"></div>', '<div data-wp-interactive=\'{"namespace":"woocommerce/mini-cart"}\'></div>'] as $index => $markup) {
    wp_enqueue_script_module('woocommerce/mini-cart');
    $filtered = apply_filters('render_block_woocommerce/mini-cart', $markup);
    $results[] = ['case' => 'interactive-syntax-' . $index, 'passed' => $filtered === $markup && in_array('woocommerce/mini-cart', wp_script_modules()->get_queue(), true)];
}
$fixtures = json_decode(file_get_contents(__DIR__ . '/created-fixtures.json'), true);
$context = 'product';
$wp_query = new WP_Query(['p' => $fixtures['products']['ordinary']['id'], 'post_type' => 'product']);
$post = get_post($fixtures['products']['ordinary']['id']);
wp_dequeue_script_module('woocommerce/mini-cart');
$normal = do_blocks('<!-- wp:woocommerce/mini-cart /-->');
$results[] = ['case' => 'normal-product-active-mini-cart', 'passed' => str_contains($normal, 'data-wp-interactive="woocommerce/mini-cart"') && in_array('woocommerce/mini-cart', wp_script_modules()->get_queue(), true)];
$output = ['pro_active' => defined('ARRAYSUBSPRO_VERSION'), 'results' => $results, 'passed' => !in_array(false, array_column($results, 'passed'), true)];
file_put_contents(__DIR__ . '/render-contract-' . ($output['pro_active'] ? 'pro' : 'free') . '.json', wp_json_encode($output, JSON_PRETTY_PRINT));
echo wp_json_encode($output, JSON_PRETTY_PRINT) . PHP_EOL;
