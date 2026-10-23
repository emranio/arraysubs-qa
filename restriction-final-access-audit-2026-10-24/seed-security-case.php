<?php
defined('ABSPATH') || exit;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;
$f = json_decode(file_get_contents(__DIR__ . '/fixtures.json'), true);
$case = $args[0] ?? 'enabled-string';
$kind = $args[1] ?? 'post';
$c = ['enabled' => true, 'mode' => 'restrict', 'roles' => ['qa_final_missing_role'], 'showToAdmins' => false, 'message' => 'QA FINAL SECURITY DENIED'];
if ($case === 'enabled-string') $c['enabled'] = 'false';
if ($case === 'login-string') { unset($c['roles']); $c['loginRequired'] = 'false'; }
if ($case === 'admin-null') $c['showToAdmins'] = null;
if ($case === 'invalid-status' || $case === 'wildcard-status') {
    unset($c['mode']);
    $c['accessVersion'] = 2;
    $c['status'] = [$case === 'invalid-status' ? 'qa-final-unknown-status' : 'any'];
    $c['condition'] = 'or';
}
if ($kind === 'post' || $kind === 'author_post') {
    update_post_meta($f[$kind], PostRestrictionConfig::META_CONFIG, wp_slash(wp_json_encode($c)));
    delete_post_meta($f[$kind], PostRestrictionConfig::META_ENABLED);
    delete_post_meta($f[$kind], PostRestrictionConfig::META_CONDITIONS);
} elseif ($kind === 'gutenberg') {
    $html = '<p>QA FINAL PROTECTED GUTENBERG</p>';
    $child = ['blockName' => 'core/paragraph', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [$html]];
    $block = ['blockName' => 'arraysubs/content-restricted', 'attrs' => $c, 'innerBlocks' => [$child], 'innerHTML' => '<div class="wp-block-arraysubs-content-restricted"></div>', 'innerContent' => ['<div class="wp-block-arraysubs-content-restricted">', null, '</div>']];
    wp_update_post(['ID' => $f[$kind], 'post_content' => wp_slash(serialize_block($block))]);
} elseif ($kind === 'elementor') {
    $settings = ['arraysubs_enable' => 'yes', 'arraysubs_access_version' => 2, 'arraysubs_roles' => $c['roles'], 'arraysubs_status' => $c['status'], 'arraysubs_condition' => 'or', 'arraysubs_show_to_admins' => '', 'arraysubs_custom_message_enabled' => 'yes', 'arraysubs_message' => 'QA FINAL SECURITY DENIED'];
    $data = [['id' => 'security1', 'elType' => 'container', 'settings' => $settings, 'elements' => [['id' => 'security2', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['title' => 'QA FINAL PROTECTED ELEMENTOR'], 'elements' => []]], 'isInner' => false]];
    update_post_meta($f[$kind], '_elementor_data', wp_slash(wp_json_encode($data)));
    delete_post_meta($f[$kind], '_elementor_element_cache');
    delete_post_meta($f[$kind], '_elementor_css');
}
clean_post_cache($f[$kind]);
echo 'QA_RESULT:' . wp_json_encode(['case' => $case, 'kind' => $kind, 'raw' => $c]);
