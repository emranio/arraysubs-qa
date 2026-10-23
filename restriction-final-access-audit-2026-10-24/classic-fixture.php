<?php
defined('ABSPATH') || exit;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;
$f=json_decode(file_get_contents(__DIR__.'/fixtures.json'),true);
$c=['enabled'=>true,'mode'=>'restrict','roles'=>['demo_premium'],'products'=>[2147483000],'condition'=>'or','loginRequired'=>false,'showToAdmins'=>false,'message'=>'QA FINAL CLASSIC DENIED'];
if (!isset($f['product'])) {
 $id=wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_title'=>'QA final classic restriction 20261024','post_content'=>'<p>QA FINAL PROTECTED PRODUCT</p>']);
 wp_set_object_terms($id,'simple','product_type'); update_post_meta($id,'_regular_price','1');update_post_meta($id,'_price','1');update_post_meta($id,'_virtual','yes');
 update_post_meta($id,PostRestrictionConfig::META_CONFIG,wp_slash(wp_json_encode($c)));
 $f['product']=$id;$f['urls']['product']=get_permalink($id);file_put_contents(__DIR__.'/fixtures.json',wp_json_encode($f,JSON_PRETTY_PRINT));
}
echo 'QA_RESULT:'.wp_json_encode(['id'=>$f['product'],'url'=>$f['urls']['product']]);
