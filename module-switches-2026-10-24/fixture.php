<?php
defined('ABSPATH') || exit;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;
$private = '/tmp/arraysubs-module-switches-private.json';
$manifest = __DIR__ . '/fixtures.json';
$mode = $args[0] ?? 'inspect';
if ($mode === 'setup') {
    if (file_exists($private) || file_exists($manifest)) { WP_CLI::error('Backup already exists.'); }
    $backup = [];
    foreach (['arraysubs_settings', 'active_plugins', 'arraysubs_settings_last_saved_at', 'woocommerce_coming_soon'] as $key) { $backup[$key] = get_option($key); }
    file_put_contents($private, wp_json_encode($backup)); chmod($private, 0600);
    $f = ['admin' => 1, 'pages' => []];
    $f['editor'] = wp_insert_user(['user_login' => 'qa_module_editor_20261024', 'user_email' => 'qa-module-editor-20261024@example.test', 'user_pass' => wp_generate_password(40), 'role' => 'editor']);
    if (is_wp_error($f['editor'])) { WP_CLI::error($f['editor']->get_error_message()); }
    $config = PostRestrictionConfig::sanitize(['enabled' => true, 'loginRequired' => true]);
    foreach (['post', 'gutenberg', 'shortcode', 'elementor'] as $surface) {
        $marker = 'QA MODULE ' . strtoupper($surface) . ' SECRET';
        $content = '<p>' . $marker . '</p>';
        if ($surface === 'shortcode') { $content = '[arraysubs_restrict login_required="true" access_version="2"]' . $content . '[/arraysubs_restrict]'; }
        if ($surface === 'gutenberg') {
            $child = ['blockName' => 'core/paragraph', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => $content, 'innerContent' => [$content]];
            $content = serialize_block(['blockName' => 'arraysubs/content-restricted', 'attrs' => $config, 'innerBlocks' => [$child], 'innerHTML' => '<div class="wp-block-arraysubs-content-restricted"></div>', 'innerContent' => ['<div class="wp-block-arraysubs-content-restricted">', null, '</div>']]);
        }
        $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'QA module ' . $surface, 'post_name' => 'qa-module-' . $surface . '-20261024', 'post_content' => $content]);
        if ($surface === 'post') { PostRestrictionConfig::save($id, $config); }
        if ($surface === 'elementor') {
            update_post_meta($id, '_elementor_edit_mode', 'builder');
            update_post_meta($id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '4.3.2');
            update_post_meta($id, '_wp_page_template', 'elementor_canvas');
            $data = [['id' => 'qamodule', 'elType' => 'container', 'settings' => ['arraysubs_enable' => 'yes', 'arraysubs_access_version' => 2, 'arraysubs_login_required' => 'yes', 'arraysubs_show_to_admins' => 'yes'], 'elements' => [['id' => 'qasecret', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['title' => $marker], 'elements' => []]], 'isInner' => false]];
            update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
        }
        $f['pages'][$surface] = ['id' => $id, 'url' => get_permalink($id), 'marker' => $marker];
    }
    file_put_contents($manifest, wp_json_encode($f, JSON_PRETTY_PRINT));
    $s = get_option('arraysubs_settings');
    $s['members_access'] = arraysubs_get_default_settings()['members_access'];
    $s['members_access']['default_message'] = '<p>QA MODULE ACCESS DENIED</p>';
    $s['toolkit'] = array_merge($s['toolkit'] ?? [], ['hide_admin_bar' => true, 'restrict_wp_admin' => true, 'wp_admin_redirect' => 'my_account', 'allowed_roles' => [], 'hide_wp_login' => true, 'wp_login_redirect' => 'my_account', 'login_as_user' => true, 'multi_login_prevention' => true, 'multi_login_max_sessions' => 2, 'multi_login_include_admins' => false]);
    arraysubs_save_settings($s); update_option('woocommerce_coming_soon', 'no');
    echo wp_json_encode($f, JSON_PRETTY_PRINT) . PHP_EOL;
} elseif ($mode === 'auth') {
    $f = json_decode(file_get_contents($manifest), true);
    $uid = $f['editor']; $expires = time() + 7200;
    $token = WP_Session_Tokens::get_instance($uid)->create($expires);
    $cookies = [];
    foreach ([LOGGED_IN_COOKIE => 'logged_in', AUTH_COOKIE => 'auth'] as $name => $scheme) {
        $cookies[] = ['name' => $name, 'value' => wp_generate_auth_cookie($uid, $expires, $scheme, $token), 'domain' => 'localhost', 'path' => '/', 'expires' => $expires, 'httpOnly' => true, 'secure' => false, 'sameSite' => 'Lax'];
    }
    file_put_contents('/tmp/arraysubs-module-editor-auth.json', wp_json_encode(['cookies' => $cookies, 'origins' => []])); chmod('/tmp/arraysubs-module-editor-auth.json', 0600);
    echo 'Temporary editor authentication prepared.' . PHP_EOL;
} elseif ($mode === 'restore') {
    $f = json_decode(file_get_contents($manifest), true); $b = json_decode(file_get_contents($private), true);
    foreach ($f['pages'] as $page) { wp_delete_post($page['id'], true); }
    require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user($f['editor']);
    foreach ($b as $key => $value) { update_option($key, $value); }
    // Flush once after the original Pro providers and endpoints boot again.
    update_option('arraysubs_modules_flush_rewrite_rules', 1, false);
    $auth_file = '/tmp/arraysubs-module-admin-auth.json';
    if (file_exists($auth_file)) {
        $state = json_decode(file_get_contents($auth_file), true);
        foreach ($state['cookies'] ?? [] as $cookie) {
            if (($cookie['name'] ?? '') === LOGGED_IN_COOKIE) {
                $parsed = wp_parse_auth_cookie($cookie['value'], 'logged_in');
                if ($parsed && ($parsed['username'] ?? '') === get_userdata(1)->user_login) {
                    WP_Session_Tokens::get_instance(1)->destroy($parsed['token']);
                }
            }
        }
    }
    echo 'Restored settings and plugin activation, removed own pages and editor.' . PHP_EOL;
} else { echo file_get_contents($manifest); }
