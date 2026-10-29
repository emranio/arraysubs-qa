<?php

/**
 * Read-only source and WordPress integration coverage for storefront overrides.
 *
 * Run with wp --allow-root eval-file after loading the actual local WordPress.
 * Theme-directory filters below are process-local and force bundled fallbacks;
 * this script does not create content, change settings, or write theme files.
 * Browser coverage and child/parent precedence are recorded separately.
 */

defined('ABSPATH') || exit;

use ArraySubs\Supports\Config;
use ArraySubs\Supports\StorefrontTemplate;

$workspace = dirname(__DIR__, 2);
$checks = 0;
$runtime = [];
$skips = [];
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};

// Independent inventory: every bundled non-admin storefront PHP view.
$inventory = [
    'arraysubs' => [
        'customer-portal/view-subscription.php' => 'CustomerPortal/views/view-subscription.php',
        'customer-portal/my-account-subscriptions.php' => 'CustomerPortal/views/my-account-subscriptions.php',
        'customer-portal/subscription-cards.php' => 'CustomerPortal/views/subscription-cards.php',
        'customer-portal/cancellation-contact-notice.php' => 'CustomerPortal/views/cancellation-contact-notice.php',
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

$admin_views = [
    'arraysubs' => [
        'MainAdmin/views/mainadmin.php',
        'SubscriptionBox/views/general-tab.php',
        'SubscriptionProducts/views/simple-product-fields.php',
        'SubscriptionProducts/views/variation-fields.php',
        'SubscriptionProducts/views/pro-subscription-type-placeholder.php',
        'SubscriptionProducts/views/pro-subscription-options-placeholder.php',
        'SubscriptionProducts/views/renewal-sync-defaults-notice.php',
    ],
    'arraysubspro' => [
        'Analytics/views/order-list-report.php',
        'RedirectProductPage/views/product-redirect-fields.php',
        'FixedPeriodMembership/views/simple-product-fields.php',
        'FixedPeriodMembership/views/variation-fields.php',
        'FlexibleRenewalSync/views/simple-product-fields.php',
        'FlexibleRenewalSync/views/variation-fields.php',
        'FlexibleSubscription/views/simple-product-fields.php',
        'FlexibleSubscription/views/variation-fields.php',
        'SubscriptionBundle/views/general-tab.php',
        'ProfileFields/views/admin-user-fields.php',
        'ProfileFields/views/admin-user-fields-readonly.php',
    ],
];

$assert(class_exists(StorefrontTemplate::class), 'Shared loader must autoload during a real WordPress request.');
$reflection = new ReflectionClass(StorefrontTemplate::class);
$assert(realpath($reflection->getFileName()) === realpath($workspace . '/arraysubs/src/Supports/StorefrontTemplate.php'), 'Both plugins must use the core shared loader.');
$assert(realpath((string) Config::get('plugin.src_path')) === realpath($workspace . '/arraysubs/src'), 'Nested plugin source configuration must resolve the installed core.');

$docs = file_get_contents($workspace . '/user-manual/markdowns/developers/template-overrides.md');
$call_sites = [];
$template_count = 0;
foreach ($inventory as $plugin => $templates) {
    $src = $workspace . '/' . $plugin . '/src/Features/';
    $calls = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $entry) {
        if (!$entry->isFile() || $entry->getExtension() !== 'php') {
            continue;
        }
        $relative = substr($entry->getPathname(), strlen($src));
        $source = file_get_contents($entry->getPathname());
        preg_match_all("/StorefrontTemplate::locate\\(\\s*'([^']+)'[^\\r\\n]*/", $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $logical = $match[1];
            $assert(isset($templates[$logical]), 'Unexpected template loader call in ' . $plugin . '/' . $relative . ': ' . $logical);
            $expected_source = $templates[$logical];
            $fallback_suffix = substr($expected_source, strpos($expected_source, '/'));
            $anchored_fallback = str_contains($match[0], $fallback_suffix);
            if ($logical === 'members-access/access-denied.php') {
                $anchored_fallback = str_contains($source, "plugin_dir_path(__DIR__) . 'views/access-denied.php'")
                    && str_contains($match[0], '$default_template)');
            }
            $assert($anchored_fallback, 'Call site must retain the correct bundled source fallback: ' . $plugin . '/' . $logical);
            $assert(str_contains($match[0], "'arraysubspro'") === ($plugin === 'arraysubspro'), 'Call site must select its own plugin theme directory: ' . $plugin . '/' . $logical);
            $assert(!in_array($relative, $admin_views[$plugin], true), 'Admin views must retain their own include system.');
            $assert(!str_contains($relative, 'Emails/') && !str_contains($relative, '/emails/'), 'Email templates must retain WooCommerce loading.');
            $calls[$logical][] = $relative;
        }

        if (preg_match('#/(?:views|templates)/[^/]+\\.php$#', '/' . $relative)
            && !str_starts_with($relative, 'Emails/') && !str_contains($relative, '/emails/')) {
            $assert(in_array($relative, $templates, true) || in_array($relative, $admin_views[$plugin], true), 'Unclassified PHP view needs an explicit storefront/admin decision: ' . $plugin . '/' . $relative);
        }
    }

    foreach ($templates as $logical => $source_relative) {
        ++$template_count;
        $source_path = $src . $source_relative;
        $assert(is_file($source_path), 'Bundled template must exist: ' . $source_path);
        $assert(!empty($calls[$logical]), 'Bundled template must have a shared-loader call site: ' . $logical);
        $assert(str_contains(file_get_contents($source_path), $plugin . '/' . $logical), 'Bundled header must identify its override path: ' . $logical);
        $assert(str_contains($docs, '`' . $plugin . '/' . $logical . '`'), 'Developer manual must list exact override path: ' . $logical);
        $assert(str_contains($docs, '`src/Features/' . $source_relative . '`'), 'Developer manual must list exact source path: ' . $source_relative);
    }
    $call_sites[$plugin] = $calls;
}
$assert($template_count === 15, 'The audit must cover ten core and five Pro storefront templates.');

foreach (['my-account-subscriptions.php', 'view-subscription.php'] as $view) {
    $source = file_get_contents($workspace . '/arraysubs/src/Features/CustomerPortal/views/' . $view);
    $assert(!preg_match('/(?:include|require)\\s+__DIR__/', $source), 'Copied portal views must not resolve nested partials from the child directory.');
    $assert(str_contains($source, "Config::get('plugin.src_path') . '/Features/CustomerPortal/views/cancellation-contact-notice.php'"), 'Nested notice fallback must remain anchored to the installed core.');
}
$list_source = file_get_contents($workspace . '/arraysubs/src/Features/CustomerPortal/views/my-account-subscriptions.php');
$assert(str_contains($list_source, "Config::get('plugin.src_path') . '/Features/CustomerPortal/views/subscription-cards.php'"), 'Nested cards fallback must remain anchored to the installed core.');

$email_base = file_get_contents($workspace . '/arraysubs/src/Features/Emails/Abstracts/BaseSubscriptionEmail.php');
$assert(substr_count($email_base, 'wc_get_template_html(') === 2, 'Core HTML and plain emails must retain WooCommerce template loading.');
foreach (['CreditAddedEmail', 'CreditUsedEmail', 'CreditExpiringEmail', 'CreditExpiredEmail'] as $class) {
    $email_source = file_get_contents($workspace . '/arraysubspro/src/Features/StoreCredit/Emails/' . $class . '.php');
    $assert(substr_count($email_source, 'wc_get_template_html(') === 2, 'Pro HTML and plain emails must retain WooCommerce loading: ' . $class);
}

// Process-local filters isolate integration checks from root's temporary theme
// switching without creating overrides or changing persisted theme settings.
$missing_theme = static fn(): string => $workspace . '/qa/storefront-template-overrides-2026-10-29/nonexistent-theme';
add_filter('stylesheet_directory', $missing_theme, PHP_INT_MAX);
add_filter('template_directory', $missing_theme, PHP_INT_MAX);
$previous_product = $GLOBALS['product'] ?? null;
$render = static function (callable $callback): string {
    ob_start();
    try {
        $callback();
        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
};

try {
    foreach ($inventory as $plugin => $templates) {
        foreach ($templates as $logical => $source_relative) {
            $default = $workspace . '/' . $plugin . '/src/Features/' . $source_relative;
            $assert(StorefrontTemplate::locate($logical, $default, $plugin) === $default, 'Real WordPress filters must permit bundled fallback: ' . $plugin . '/' . $logical);
        }
    }

    $products = wc_get_products(['limit' => 100, 'status' => 'publish']);
    $product_hooks = (new ReflectionClass(\ArraySubs\Features\SubscriptionProducts\Services\Hooks::class))->newInstanceWithoutConstructor();
    $info_method = new ReflectionMethod($product_hooks, 'getSubscriptionInfoHtml');
    $simple_tested = false;
    $variable_tested = false;
    foreach ($products as $product) {
        if (!$simple_tested && $product->is_type('simple') && arraysubs_is_subscription_product($product)) {
            $html = (string) $info_method->invoke($product_hooks, $product);
            if (str_contains($html, 'arraysubs-product-info')) {
                $assert(str_contains($html, 'subscription-terms'), 'Simple product controller must render the original supplemental-terms markup.');
                $runtime['simple_product_id'] = $product->get_id();
                $simple_tested = true;
            }
        }
        if (!$variable_tested && $product->is_type('variable') && arraysubs_has_subscription_variations($product)) {
            $html = (string) $info_method->invoke($product_hooks, $product);
            $assert(str_contains($html, 'arraysubs-variable-subscription-info'), 'Variable product controller must retain its JavaScript target container.');
            $runtime['variable_product_id'] = $product->get_id();
            $variable_tested = true;
        }
        if ($simple_tested && $variable_tested) {
            break;
        }
    }
    $assert($simple_tested, 'Existing products must exercise a real simple-product controller render.');
    $assert($variable_tested, 'Existing products must exercise a real variable-product controller render.');

    $box_tested = false;
    foreach ($products as $product) {
        if (!\ArraySubs\Features\SubscriptionBox\Services\BoxConfig::isBoxProduct($product)) {
            continue;
        }
        $config = \ArraySubs\Features\SubscriptionBox\Services\BoxConfig::getConfig($product->get_id());
        if ($config === null || is_wp_error(\ArraySubs\Features\SubscriptionBox\Services\BoxConfig::validateAvailability($product->get_id(), $config))) {
            continue;
        }
        $box_hooks = (new ReflectionClass(\ArraySubs\Features\SubscriptionBox\Services\FrontendHooks::class))->newInstanceWithoutConstructor();
        $builder_method = new ReflectionMethod($box_hooks, 'getBuilderMarkup');
        $GLOBALS['product'] = $previous_product;
        $html = (string) $builder_method->invoke($box_hooks, $product);
        $assert(str_contains($html, 'data-arraysubs-box-open='), 'Box controller must retain its launcher interaction target.');
        $assert(str_contains($html, 'arraysubs-box-overlay-'), 'Box controller must retain its independently identified overlay.');
        $assert(($GLOBALS['product'] ?? null) === $previous_product, 'Box controller must restore the caller product global after rendering.');
        $runtime['box_product_id'] = $product->get_id();
        $box_tested = true;
        break;
    }
    if (!$box_tested) {
        $skips[] = 'No currently available configured Subscription Box among the existing product sample.';
    }

    $portal = (new ReflectionClass(\ArraySubs\Features\CustomerPortal\Services\MyAccountHooks::class))->newInstanceWithoutConstructor();
    $html = $render(static fn() => $portal->viewSubscriptionEndpointContent(0));
    $assert(str_contains($html, 'Invalid subscription ID.'), 'Invalid subscription requests must fail before loading any view.');
} finally {
    $GLOBALS['product'] = $previous_product;
    remove_filter('stylesheet_directory', $missing_theme, PHP_INT_MAX);
    remove_filter('template_directory', $missing_theme, PHP_INT_MAX);
}

echo wp_json_encode([
    'status' => 'passed',
    'assertions' => $checks,
    'templates' => $template_count,
    'call_sites' => $call_sites,
    'runtime' => $runtime,
    'skips' => $skips,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
