<?php
$path = __DIR__ . '/browser-fixtures.json';
$data = json_decode(file_get_contents($path), true);
if (isset($data['access_post_id'])) { WP_CLI::error('Access fixtures already exist.'); }
$post_id = wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Developer QA Access and Comments','post_content'=>'Developer QA protected content is visible to this member.','comment_status'=>'open']);
$conditions=['logic'=>'AND','rules'=>[['type'=>'has_active_subscription','product_ids'=>[$data['items'][1]['product_id']]]]];
update_post_meta($post_id,'_arraysubs_restrict_enabled','1');
update_post_meta($post_id,'_arraysubs_restrict_conditions',wp_json_encode($conditions));
update_post_meta($post_id,'_arraysubs_restrict_message','Developer QA content access denied.');
$data['access_post_id']=$post_id;
$data['access_post_url']=get_permalink($post_id);
$data['product_url']=get_permalink($data['items'][0]['product_id']);
$data['original_comment_rules']=arraysubs_get_setting('members_access.comment_rules',[]);
$data['original_ecommerce_rules']=arraysubs_get_setting('members_access.ecommerce_rules',[]);
$rule=['id'=>'developer-support-access','enabled'=>true,'conditions'=>$conditions];
arraysubs_update_setting('members_access.comment_rules',array_merge($data['original_comment_rules'],[$rule+['target_type'=>'specific_posts','post_ids'=>[$post_id],'restriction_mode'=>'block_all','message'=>'Developer QA comment access denied.']]));
arraysubs_update_setting('members_access.ecommerce_rules',array_merge($data['original_ecommerce_rules'],[$rule+['scope'=>'specific_products','target_product_ids'=>[$data['items'][0]['product_id']],'action'=>'block_purchase','message'=>'Developer QA purchase access denied.']]));
file_put_contents($path,wp_json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo wp_json_encode(['post_id'=>$post_id,'post_url'=>$data['access_post_url'],'product_url'=>$data['product_url']]),"\n";
