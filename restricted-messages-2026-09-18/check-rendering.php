<?php
/** Run with WP-CLI eval-file; supplements the browser tests. */
use ArraySubs\Features\GutenbergIntegration\Services\Hooks as Gutenberg;
use ArraySubs\Features\MembersAccess\REST\RestrictionController;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;

function rm_check($name, $passed) {
    if (!$passed) {
        throw new RuntimeException('FAIL: ' . $name);
    }
    echo 'PASS: ' . $name . PHP_EOL;
}

wp_set_current_user(0);
$markup = '<p><strong>Members</strong> <em>only</em><br>Join now.</p><ul><li>Lessons</li><li>Downloads</li></ul><p><a href="http://localhost:10003/my-account/?a=1&amp;b=2">Sign in</a></p>';
$unsafe = $markup . '<script>rmUnsafe()</script><img src="x" onerror="rmUnsafe()"><a href="javascript:rmUnsafe()" onclick="rmUnsafe()">Unsafe</a><iframe src="https://example.com"></iframe>';
$output = Gutenberg::gate(['loginRequired'=>true,'message'=>$unsafe], 'PROTECTED');
rm_check('Gutenberg preserves basic HTML', strpos($output, '<strong>Members</strong>') !== false && strpos($output, '<ul>') !== false && strpos($output, '<em>only</em>') !== false);
rm_check('Denied HTML is not escaped', strpos($output, '&lt;p&gt;') === false);
rm_check('Unsafe tags, event handlers and URL schemes removed', !preg_match('/<script|<iframe|onerror=|onclick=|javascript:/i', $output));
rm_check('Protected content hidden', strpos($output, 'PROTECTED') === false);
rm_check('No nested paragraph wrapper', !preg_match('/<p>\s*<(?:p|ul|ol)>/', $output));
rm_check('Empty login message fallback', strpos(Gutenberg::gate(['loginRequired'=>true,'message'=>''], 'PROTECTED'), wp_kses_post(wpautop(arraysubs_resolve_restricted_message()))) !== false);
rm_check('Plain message remains a paragraph', strpos(Gutenberg::gate(['loginRequired'=>true,'message'=>'Plain message'], 'PROTECTED'), '<p>Plain message</p>') !== false);
$controller = new RestrictionController();
$settings = $controller->sanitizeSettings(['cpt_rules'=>[['id'=>'rm-safety','message'=>$unsafe]]]);
rm_check('CPT save retains basic HTML', strpos($settings['cpt_rules'][0]['message'], '<strong>Members</strong>') !== false);
rm_check('CPT save strips unsafe HTML', !preg_match('/<script|<iframe|onerror=|onclick=|javascript:/i', $settings['cpt_rules'][0]['message']));
$config = PostRestrictionConfig::sanitize(['enabled'=>true,'message'=>$unsafe]);
rm_check('Per-post save retains safe HTML only', strpos($config['message'], '<strong>Members</strong>') !== false && !preg_match('/<script|<iframe|onerror=|onclick=|javascript:/i', $config['message']));
$gating = new ArraySubs\Features\MembersAccess\Services\ContentGating();
$format = new ReflectionMethod($gating, 'formatRestrictedMessage');
$formatted = $format->invoke($gating, $markup);
rm_check('CPT/per-post formatter has no nested paragraph wrapper', !preg_match('/<p>\\s*<(?:p|ul|ol)>/', $formatted));
wp_set_current_user(1);
rm_check('Authorized visitor sees content', Gutenberg::gate(['loginRequired'=>true,'message'=>$unsafe], 'PROTECTED') === 'PROTECTED');
echo 'Core-only: ' . (class_exists('ArraySubsPro', false) ? 'no' : 'yes') . PHP_EOL;
