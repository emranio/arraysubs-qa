<?php
/** Dedicated Gutenberg author fixture; never changes other pages or users. */
defined('ABSPATH') || exit;
$fixture = json_decode(file_get_contents(__DIR__ . '/fixtures.json'), true);
$id = (int) $fixture['author_gutenberg'];
if ($id !== 20191) { WP_CLI::error('Unexpected author fixture ID.'); }
$state = $args[0] ?? 'native_any';
$attrs = ['roles' => ['demo_premium'], 'products' => [2147483000], 'condition' => 'or', 'showToAdmins' => false, 'message' => '<p>QA FINAL CUSTOM <strong>saved message</strong></p>'];
if ($state === 'invalid_login') { $attrs['loginRequired'] = 2; }
if ($state === 'old_enabled_false') { $attrs['mode'] = 'restrict'; $attrs['enabled'] = false; }
if ($state === 'visibility') { $attrs = ['mode' => 'visibility', 'visShow' => 'logged_out', 'visFallback' => '<p>QA FINAL FALLBACK</p>', 'showToAdmins' => false]; }
$html = '<p>QA FINAL PROTECTED GUTENBERG AUTHOR</p>';
$child = ['blockName' => 'core/paragraph', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [$html]];
$block = ['blockName' => 'arraysubs/content-restricted', 'attrs' => $attrs, 'innerBlocks' => [$child], 'innerHTML' => '<div class="wp-block-arraysubs-content-restricted"></div>', 'innerContent' => ['<div class="wp-block-arraysubs-content-restricted">', null, '</div>']];
wp_update_post(['ID' => $id, 'post_content' => wp_slash(serialize_block($block))]);
echo 'QA_RESULT:' . wp_json_encode(['id' => $id, 'state' => $state, 'attributes' => $attrs]);
