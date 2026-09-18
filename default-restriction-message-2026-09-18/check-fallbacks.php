<?php
/** Supplemental assertions; browser evidence is recorded alongside this file. */
function dm_check($name, $condition) { if (!$condition) { throw new RuntimeException('FAIL: '.$name); } echo 'PASS: '.$name.PHP_EOL; }
$GLOBALS['dm_test_settings']=get_option('arraysubs_settings',[]);
$GLOBALS['dm_test_settings']['members_access']['default_message']='<p><strong>Shared</strong> <a href="https://example.com/join">join</a></p>';
add_filter('pre_option_arraysubs_settings', function(){return $GLOBALS['dm_test_settings'];});
$global=arraysubs_resolve_restricted_message();
foreach(['','   ','<p><br></p>','<p>&nbsp;</p>',"\xc2\xa0",'<!-- blank -->'] as $empty) {
 dm_check('Empty custom HTML uses global: '.json_encode($empty), arraysubs_resolve_restricted_message($empty)===$global);
}
dm_check('Custom message wins',arraysubs_resolve_restricted_message('<p><em>Custom</em></p>')==='<p><em>Custom</em></p>');
dm_check('Zero is meaningful content',arraysubs_resolve_restricted_message('0')==='0');
$unsafe='<p><strong>Safe</strong> <em>italics</em></p><ul><li>Item</li></ul><a href="javascript:alert(1)" onclick="alert(1)">Link</a><img src="x" onerror="alert(1)"><script>alert(1)</script><iframe src="x"></iframe>';
$safe=arraysubs_resolve_restricted_message($unsafe);
dm_check('Removes scripts, events, iframes and unsafe links',!preg_match('/<script|<iframe|onerror=|onclick=|javascript:/i',$safe));
dm_check('Preserves basic HTML',strpos($safe,'<strong>Safe</strong>')!==false&&strpos($safe,'<ul><li>Item</li></ul>')!==false);
$controller=new ArraySubs\Features\MembersAccess\REST\RestrictionController(false);
$saved=$controller->sanitizeSettings(['default_message'=>$unsafe]);
dm_check('Global settings sanitize HTML on save',!preg_match('/<script|<iframe|onerror=|onclick=|javascript:/i',$saved['default_message']));
wp_set_current_user(0);
$out=ArraySubs\Features\GutenbergIntegration\Services\Hooks::gate(['loginRequired'=>true,'message'=>'<p>&nbsp;</p>'],'SECRET');
dm_check('Login-only gate uses global for empty rich HTML',strpos($out,'<strong>Shared</strong>')!==false&&strpos($out,'SECRET')===false);
$GLOBALS['dm_test_settings']['members_access']['default_message']='<p>&nbsp;<br></p>';
dm_check('Empty global HTML uses built-in',arraysubs_resolve_restricted_message()==='This content is restricted. Please subscribe to access.');
echo 'Core-only: '.(class_exists('ArraySubsPro',false)?'no':'yes').PHP_EOL;
