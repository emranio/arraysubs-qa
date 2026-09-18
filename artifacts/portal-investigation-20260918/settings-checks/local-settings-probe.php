<?php
/** Temporary localhost-only request fixtures. Does not save WordPress settings. */
defined('ABSPATH') || exit;
if (($_SERVER['HTTP_HOST'] ?? '') !== 'localhost:10003') {
    return;
}
add_action('arraysubs_after_boot', static function () {
    if (!in_array(get_current_user_id(), [1, 525], true)) {
        return;
    }
    $case = sanitize_key($_GET['portal_case'] ?? $_COOKIE['arraysubs_portal_case'] ?? '');
    if (!in_array($case, ['free-saved-hidden', 'pro-hidden', 'pro-customization-off', 'pro-visible', 'url-role', 'url-schedule', 'css-hidden', 'off'], true)) {
        return;
    }
    if (isset($_GET['portal_case'])) {
        setcookie('arraysubs_portal_case', $case === 'off' ? '' : $case, [
            'expires' => $case === 'off' ? time() - 3600 : time() + 1800,
            'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        ]);
    }
    if ($case === 'off') {
        return;
    }
    $settings = arraysubs_get_settings();
    $settings['profile_builder']['my_account_menu_items_enabled'] = $case !== 'pro-customization-off';
    $settings['members_access']['url_rules'] = [];
    $settings['member_styling']['rules'] = [];
    $menu = [['id' => 'subscriptions', 'type' => 'default', 'label' => 'Subscriptions', 'enabled' => $case === 'pro-visible']];
    add_filter('pre_option_arraysubs_myaccount_menu_config', static fn() => $menu);
    if (str_starts_with($case, 'url-')) {
        $settings['members_access']['url_rules'] = [[
            'id' => 'portal-investigation', 'name' => 'Portal investigation',
            'enabled' => true, 'priority' => 1, 'pattern' => '/my-account/subscriptions',
            'pattern_type' => 'prefix', 'exclusions' => [], 'action' => 'redirect',
            'redirect_url' => home_url('/my-account/'),
            'conditions' => ['logic' => 'and', 'rules' => $case === 'url-role'
                ? [['type' => 'user_role', 'roles' => ['administrator']]]
                : [['type' => 'subscription_status', 'statuses' => ['active']]]],
            'schedule_enabled' => $case === 'url-schedule',
            'schedule_value' => 3650, 'schedule_unit' => 'days',
        ]];
    }
    if ($case === 'css-hidden') {
        $settings['member_styling']['rules'] = [[
            'id' => 'portal-investigation', 'name' => 'Portal investigation', 'enabled' => true,
            'conditions' => ['logic' => 'and', 'rules' => []],
            'custom_css' => '.woocommerce-MyAccount-navigation-link--subscriptions { display: none !important; }',
        ]];
    }
    add_filter('pre_option_arraysubs_settings', static fn() => $settings);
    if (str_starts_with($case, 'pro-')) {
        require_once WP_PLUGIN_DIR . '/arraysubspro/src/functions/myaccount-editor-helpers.php';
        require_once WP_PLUGIN_DIR . '/arraysubspro/src/Features/MyAccountEditor/Services/Hooks.php';
        new \ArraySubsPro\Features\MyAccountEditor\Services\Hooks();
    }
}, 20);
