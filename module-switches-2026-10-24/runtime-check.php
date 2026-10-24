<?php

/**
 * Read-only module regression checks against one freshly booted WordPress request.
 *
 * Usage: /tmp/arraysubs-module-wp eval-file <this-file> on off pro
 * Arguments: expected member_access on/off, toolkit on/off, installed mode free/pro.
 * Save the desired module states before starting a new request. The harness does
 * not save options, post metadata, users, sessions, or plugin activation state.
 * Request-local option/meta filters supply deliberate denial rules and controls.
 */

use ArraySubs\Features\EasySetup\Support\ModuleStatesTransfer;
use ArraySubs\Features\GutenbergIntegration\Services\Hooks as GutenbergHooks;
use ArraySubs\Features\LoginAsUser\Services\Impersonator;
use ArraySubs\Features\MembersAccess\Services\ContentGating;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionGate;
use ArraySubs\Features\Modules\Support\Registry;
use ArraySubs\Supports\ActionScheduler;
use ArraySubs\Supports\Config;

if (!defined('WP_CLI') || !WP_CLI) {
    exit("Run this file with WP-CLI eval-file.\n");
}

if (count($args) !== 3 || !in_array($args[0], ['on', 'off'], true)
    || !in_array($args[1], ['on', 'off'], true) || !in_array($args[2], ['free', 'pro'], true)) {
    WP_CLI::error('Arguments must be: <member_access on/off> <toolkit on/off> <free/pro>.');
}

$member = $args[0] === 'on';
$toolkit = $args[1] === 'on';
$pro = $args[2] === 'pro';
$failures = [];
$checks = 0;
$check = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    if (!$condition) {
        $failures[] = $message;
        WP_CLI::warning($message);
    }
};

// Find a callback by class and method without coupling checks to hook priority.
$has_callback = static function (string $hook, string $class, string $method): bool {
    global $wp_filter;
    foreach ($wp_filter[$hook]->callbacks ?? [] as $callbacks) {
        foreach ($callbacks as $callback) {
            $function = $callback['function'];
            if (is_array($function) && ($function[1] ?? null) === $method
                && (is_object($function[0]) ? is_a($function[0], $class) : $function[0] === $class)) {
                return true;
            }
        }
    }
    return false;
};

$stored_before = get_option('arraysubs_settings', []);
$check(arraysubs_is_module_enabled('member_access') === $member, 'Saved Member Access switch matches this matrix case.');
$check(arraysubs_is_module_enabled('toolkit') === $toolkit, 'Saved Toolkit switch matches this matrix case.');
$check(Config::has('proplugin.file') === $pro, 'Licensed Pro boot state matches free/pro matrix case.');

$registry = Registry::all();
$payload = array_column(Registry::adminPayload()['modules'], null, 'id');
foreach (['member_access' => $member, 'toolkit' => $toolkit] as $id => $enabled) {
    $check(isset($registry[$id]), $id . ' appears in the module registry.');
    $check(($registry[$id]['toggleable'] ?? false) === true, $id . ' is toggleable.');
    $check(($registry[$id]['available'] ?? false) === true, $id . ' remains available with free alone.');
    $check(($registry[$id]['badge']['id'] ?? '') === 'free', $id . ' has the Free badge.');
    $check(($payload[$id]['enabled'] ?? null) === $enabled, $id . ' payload reports its saved state.');
    $check(array_key_exists($id, ModuleStatesTransfer::export()), $id . ' travels in settings exports.');
}
$check(Registry::sanitizeStates(['member_access' => false, 'toolkit' => true])
    === ['member_access' => false, 'toolkit' => true], 'Registry accepts both independent switch values.');

$live = Config::get('live_boot_classes', []);
$dormant = Config::get('dormant_boot_classes', []);
$provider_check = static function (string $class, bool $enabled) use ($check, $live, $dormant): void {
    $check(in_array($class, $live, true) === $enabled, $class . ' live state follows its owner.');
    $check(in_array($class, $dormant, true) === !$enabled, $class . ' dormant state follows its owner.');
};

foreach (['MembersAccess', 'GutenbergIntegration', 'ElementorIntegration'] as $feature) {
    $provider_check('ArraySubs\\Features\\' . $feature . '\\Provider', $member);
}
foreach (['Toolkit', 'LoginAsUser'] as $feature) {
    $provider_check('ArraySubs\\Features\\' . $feature . '\\Provider', $toolkit);
}
foreach (['Subscriptions', 'SubscriptionProducts', 'SubscriptionCheckout', 'RecurringBilling', 'AutomaticPayments', 'MemberShortcodes', 'MemberInsight'] as $feature) {
    $provider_check('ArraySubs\\Features\\' . $feature . '\\Provider', true);
}
if ($pro) {
    $provider_check('ArraySubsPro\\Features\\MultiLoginPrevention\\Provider', $member);
    foreach (['FixedPeriodMembership', 'MemberInsight', 'FlexibleRenewalSync'] as $feature) {
        $provider_check('ArraySubsPro\\Features\\' . $feature . '\\Provider', true);
    }
} else {
    $check(count(array_filter(array_merge($live, $dormant), static fn($class) => str_starts_with($class, 'ArraySubsPro\\'))) === 0,
        'Free-only boot has no active or dormant premium providers.');
}

$member_hooks = 'ArraySubs\\Features\\MembersAccess\\Services\\Hooks';
$check($has_callback('the_content', $member_hooks, 'filterContent') === $member, 'Content filtering follows Member Access only.');
$check($has_callback('arraysubs_data_status_changed', $member_hooks, 'onSubscriptionStatusChanged') === $member,
    'Member role assignment follows Member Access only.');
$check($has_callback('woocommerce_customer_get_downloadable_products', 'ArraySubs\\Features\\MembersAccess\\Services\\DownloadManager', 'addSubscriptionDownloads') === $member,
    'Member download additions follow Member Access only.');
$check($has_callback('woocommerce_is_purchasable', 'ArraySubs\\Features\\MembersAccess\\Services\\EcommerceRestriction', 'filterIsPurchasable') === $member,
    'Shop purchase restrictions follow Member Access only.');
$check($has_callback('show_admin_bar', 'ArraySubs\\Features\\Toolkit\\Services\\AdminAccessControl', 'maybeHideAdminBar') === $toolkit,
    'Toolbar control follows Toolkit only.');
$check($has_callback('login_form_arraysubs_login_as_olduser', Impersonator::class, 'handleSwitchBack'),
    'Returning from existing impersonation remains registered in every switch state.');
$check($has_callback('woocommerce_order_status_changed', 'ArraySubs\\Features\\MembersAccess\\Services\\PurchaseHistory', 'onOrderStatusChanged'),
    'Shared purchase-history invalidation remains active while Member Access is off.');
$check($has_callback(ActionScheduler::HOOK_PROCESS_RENEWAL, 'ArraySubs\\Features\\RecurringBilling\\Services\\Hooks', 'processRenewal'),
    'Renewal payment processing remains registered.');
$check($has_callback(ActionScheduler::HOOK_GENERATE_RENEWAL_INVOICE, 'ArraySubs\\Features\\RecurringBilling\\Services\\Hooks', 'generateRenewalInvoice'),
    'Precise renewal invoice generation remains registered.');
$check($has_callback('transition_post_status', 'ArraySubs\\Features\\Subscriptions\\Services\\Hooks', 'onSubscriptionStatusChanged'),
    'Core subscription lifecycle remains registered.');
if ($pro) {
    $check($has_callback('wp_login', 'ArraySubsPro\\Features\\MultiLoginPrevention\\Services\\Hooks', 'onLogin') === $member,
        'Premium login-session enforcement follows Member Access independently of Toolkit.');
    $check($has_callback('arraysubs_developer_login_limits', 'ArraySubsPro\\Features\\MultiLoginPrevention\\Services\\SettingsExtender', 'readLoginPolicy'),
        'Premium read-only login policy remains available while Member Access is off.');
}

// Deliberately enable internal controls for this request, preserving saved
// module choices. These filters never call the option or metadata write APIs.
$start_impersonation = true;
$option_filter = static function ($settings) use (&$start_impersonation): array {
    $settings = is_array($settings) ? $settings : [];
    $settings['members_access'] = array_replace($settings['members_access'] ?? [], [
        'enabled' => true,
        'cpt_rules' => [],
        'url_rules' => [[
            'id' => 'module-regression-readonly', 'enabled' => true,
            'pattern' => '/module-regression-readonly', 'pattern_type' => 'exact',
            'conditions' => ['logic' => 'and', 'rules' => [['type' => 'user_login_status', 'login_status' => 'logged_in']]],
        ]],
        'login_limit_rules' => [],
    ]);
    $settings['toolkit'] = array_replace($settings['toolkit'] ?? [], [
        'hide_admin_bar' => true, 'hide_wp_login' => true,
        'allowed_roles' => [], 'login_as_user' => $start_impersonation,
        'multi_login_prevention' => true, 'multi_login_max_sessions' => 2,
    ]);
    return $settings;
};
$original_user_id = get_current_user_id();
$original_shortcodes = $GLOBALS['shortcode_tags'];
$meta_filter = null;
add_filter('option_arraysubs_settings', $option_filter);

try {
    wp_set_current_user(0);
    $check(arraysubs_is_members_access_enabled() === $member, 'Shared member-access helper honors the module switch.');
    $check(Impersonator::isEnabled() === $toolkit, 'Starting impersonation requires Toolkit even when its saved setting is on.');
    $start_impersonation = false;
    $check(Impersonator::isEnabled() === false, 'The individual Login as User setting can still disable impersonation.');
    $start_impersonation = true;

    // WooCommerce separately hides the toolbar from guests. Suppress only its
    // native policy for this assertion so it cannot mask Toolkit's own switch.
    $native_toolbar_policy = static fn(): bool => false;
    add_filter('woocommerce_disable_admin_bar', $native_toolbar_policy);
    try {
        $check(apply_filters('show_admin_bar', true) === !$toolkit, 'Configured toolbar hiding runs only while Toolkit is on.');
    } finally {
        remove_filter('woocommerce_disable_admin_bar', $native_toolbar_policy);
    }
    $login_url = site_url('wp-login.php', 'login');
    $filtered_login_url = apply_filters('login_url', $login_url, '', false);
    $check(($filtered_login_url !== $login_url) === $toolkit, 'Configured customer login URL replacement follows Toolkit only.');

    $body = '<p>Module runtime content</p>';
    add_shortcode('arraysubs_module_regression_inner', static fn() => '<strong>Nested shortcode</strong>');
    $restricted = do_shortcode('[arraysubs_restrict login_required="true" show_to_admins="false" message="Denied marker"]' . $body . '[/arraysubs_restrict]');
    $check($member ? !str_contains($restricted, $body) : $restricted === $body,
        'Saved restriction shortcode denies guests on and exposes inner content off without wrapper syntax.');
    $visibility = do_shortcode('[arraysubs_visibility show="logged_in" fallback="Denied marker"]' . $body . '[/arraysubs_visibility]');
    $check($visibility === ($member ? 'Denied marker' : $body), 'Saved visibility shortcode becomes transparent while Content Gate is off.');
    if (!$member) {
        $check(do_shortcode('[arraysubs_restrict][arraysubs_module_regression_inner][/arraysubs_restrict]') === '<strong>Nested shortcode</strong>',
            'Inactive restriction wrappers still render their nested shortcodes.');
    }
    $check(shortcode_exists('arraysubs_login') && shortcode_exists('arraysubs_logout') && shortcode_exists('arraysubs_user'),
        'Generic account shortcodes remain available in every switch state.');
    $block = GutenbergHooks::gate([
        'enabled' => true, 'loginRequired' => true, 'showToAdmins' => false,
        'message' => 'Denied marker', 'useDefaultMessage' => false,
    ], $body);
    $check($member ? !str_contains($block, $body) : $block === $body,
        'Saved restricted Gutenberg content denies guests on and passes through off.');

    global $wpdb;
    $post_id = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') ORDER BY ID LIMIT 1");
    $check($post_id > 0, 'A real existing post/page is available for read-only helper checks.');
    if ($post_id > 0) {
        $config = PostRestrictionConfig::sanitize([
            'enabled' => true, 'accessVersion' => 2, 'loginRequired' => true,
            'showToAdmins' => false, 'message' => 'Denied marker', 'useDefaultMessage' => false,
        ]);
        $meta_filter = static function ($value, $object_id, $key) use ($post_id, $config) {
            if ((int) $object_id === $post_id && $key === PostRestrictionConfig::META_CONFIG) {
                return wp_json_encode($config);
            }
            return $value;
        };
        add_filter('get_post_metadata', $meta_filter, 10, 3);
        $check(arraysubs_user_can_access(0, $post_id) === !$member, 'Public post access helper honors switch-off with a saved active gate.');
        $check(arraysubs_current_user_can_access($post_id) === !$member, 'Current-user access helper honors switch-off.');
        $check((new ContentGating())->canUserAccessPost(0, $post_id) === !$member, 'Direct content-gating service honors switch-off.');
        $check(PostRestrictionGate::evaluate(0, $config) === !$member, 'Direct post-gate evaluation honors switch-off.');
    }
    $check(arraysubs_is_url_restricted(home_url('/module-regression-readonly'), 0) === $member,
        'URL restriction helper honors Member Access independently of Toolkit.');

    $server = rest_get_server();
    $routes = $server->get_routes();
    foreach (['/arraysubs/v1/members-access/settings', '/arraysubs/v1/members-access/select-options'] as $route) {
        $check(isset($routes[$route]) === $member, $route . ' follows Member Access only.');
    }
    $toolkit_route = '/arraysubs/v1/toolkit/select/roles';
    $check(isset($routes[$toolkit_route]) === $toolkit, 'Toolkit role picker route remains independent of Member Access.');
    if ($toolkit) {
        $response = $server->dispatch(new WP_REST_Request('GET', $toolkit_route));
        $check(in_array($response->get_status(), [401, 403], true), 'Toolkit roles are unavailable to unauthenticated visitors.');
        $administrators = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
        $check(!empty($administrators), 'An existing administrator is available for permission checks.');
        if ($administrators) {
            wp_set_current_user((int) $administrators[0]);
            $response = $server->dispatch(new WP_REST_Request('GET', $toolkit_route));
            $data = $response->get_data();
            $check($response->get_status() === 200 && ($data['success'] ?? false) === true && !empty($data['data']),
                'Toolkit roles load for an administrator while Member Access may be off.');
            wp_set_current_user(0);
        }
    }
    global $wp_rest_additional_fields;
    $check(isset($wp_rest_additional_fields['post'][PostRestrictionConfig::REST_FIELD]) === $member,
        'Post restriction editor REST fields follow Member Access only.');
    if ($pro) {
        $policy = (new \ArraySubsPro\Features\MultiLoginPrevention\Services\SessionManager())->getPolicy(0);
        $check(($policy['availability']['enabled'] ?? null) === $member,
            'Premium login-limit policy follows Member Access independently of Toolkit.');
        $check(($policy['policy']['configured_maximum'] ?? null) === 2,
            'Premium login policy retains its configured limit even while the module is off.');
    }
} catch (Throwable $error) {
    $check(false, get_class($error) . ': ' . $error->getMessage());
} finally {
    remove_filter('option_arraysubs_settings', $option_filter);
    if ($meta_filter !== null) {
        remove_filter('get_post_metadata', $meta_filter, 10);
    }
    $GLOBALS['shortcode_tags'] = $original_shortcodes;
    wp_set_current_user($original_user_id);
}

// Wizard settings must not overwrite disabled modules, in either edition.
$wizard = (new ReflectionClass(\ArraySubs\Features\EasySetup\REST\SetupController::class))->newInstanceWithoutConstructor();
$invoke = static function (string $method, array $arguments = []) use ($wizard) {
    $reflection = new ReflectionMethod($wizard, $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs($wizard, $arguments);
};
$answers = $invoke('getDefaultWizardAnswers');
$answers['additional_features'] = ['login_as_user', 'hide_admin_bar', 'restrict_dashboard', 'multi_login'];
$answers = $invoke('sanitizeWizardAnswers', [$answers]);
$check(!is_wp_error($answers), 'Default wizard answers validate for the switch matrix.');
if (is_array($answers)) {
    foreach (['login_as_user', 'hide_admin_bar', 'restrict_dashboard'] as $feature) {
        $check(in_array($feature, $answers['additional_features'], true) === $toolkit,
            'Wizard removes unavailable Toolkit selection: ' . $feature);
    }
    $check(in_array('multi_login', $answers['additional_features'], true) === ($member && $pro),
        'Wizard offers login limits only with live premium Member Access.');
    $patch = $invoke('buildSettingsFromAnswers', [$answers]);
    $check(array_key_exists('hide_admin_bar', $patch['toolkit'] ?? []) === $toolkit,
        'Wizard does not write dormant Toolkit controls.');
    $check(array_key_exists('multi_login_prevention', $patch['toolkit'] ?? []) === ($member && $pro),
        'Wizard does not write dormant premium login-limit controls.');
}

$check(get_option('arraysubs_settings', []) === $stored_before, 'Saved settings are unchanged by the read-only regression harness.');
if ($failures) {
    WP_CLI::error(count($failures) . '/' . $checks . ' checks failed for member=' . $args[0] . ', toolkit=' . $args[1] . ', mode=' . $args[2] . '.');
}
WP_CLI::success($checks . ' runtime checks passed for member=' . $args[0] . ', toolkit=' . $args[1] . ', mode=' . $args[2] . '.');
