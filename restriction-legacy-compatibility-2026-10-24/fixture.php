<?php
defined('ABSPATH') || exit;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;
$manifest=__DIR__.'/fixtures.json';
$backup=sys_get_temp_dir().'/arraysubs-legacy-20261024-backup.json';
$mode=$args[0]??'inspect';
function qa_legacy_elementor(array $c): array {
 $map=['status'=>'status','products'=>'products','variations'=>'variations','purchased'=>'purchased','roles'=>'roles','lifetimeSpent'=>'lifetime_spent','condition'=>'condition','feature'=>'feature','featureMatch'=>'feature_match','featureValue'=>'feature_value','featureOp'=>'feature_op','aggregation'=>'aggregation','message'=>'message','mode'=>'mode','visShow'=>'vis_show','visFallback'=>'vis_fallback','accessVersion'=>'access_version'];
 $s=['arraysubs_enable'=>'yes','arraysubs_login_required'=>!empty($c['loginRequired'])?'yes':'','arraysubs_show_to_admins'=>!empty($c['showToAdmins'])?'yes':''];
 foreach($map as $key=>$target){if(array_key_exists($key,$c))$s['arraysubs_'.$target]=$c[$key];}
 if(array_key_exists('useDefaultMessage',$c))$s['arraysubs_custom_message_enabled']=$c['useDefaultMessage']?'':'yes';
 return $s;
}
function qa_legacy_write(int $id,string $kind,array $c):void {
 if($kind==='post') { update_post_meta($id,PostRestrictionConfig::META_CONFIG,wp_slash(wp_json_encode($c)));delete_post_meta($id,PostRestrictionConfig::META_ENABLED);delete_post_meta($id,PostRestrictionConfig::META_CONDITIONS); }
 elseif($kind==='gutenberg') { $html='<p>QA LEGACY PROTECTED GUTENBERG</p>'; $child=['blockName'=>'core/paragraph','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>$html,'innerContent'=>[$html]]; $block=['blockName'=>'arraysubs/content-restricted','attrs'=>$c,'innerBlocks'=>[$child],'innerHTML'=>'<div class="wp-block-arraysubs-content-restricted"></div>','innerContent'=>['<div class="wp-block-arraysubs-content-restricted">',null,'</div>']];wp_update_post(['ID'=>$id,'post_content'=>wp_slash(serialize_block($block))]); }
 else { $data=[['id'=>'legacy01','elType'=>'container','settings'=>qa_legacy_elementor($c),'elements'=>[['id'=>'legacy02','elType'=>'widget','widgetType'=>'heading','settings'=>['title'=>'QA LEGACY PROTECTED ELEMENTOR'],'elements'=>[]]],'isInner'=>false]];update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($data)));delete_post_meta($id,'_elementor_element_cache');delete_post_meta($id,'_elementor_css'); }
 clean_post_cache($id);
}
if($mode==='setup') {
 if(file_exists($manifest)||file_exists($backup))WP_CLI::error('Existing fixtures or backup.');
 $settings=get_option('arraysubs_settings');file_put_contents($backup,wp_json_encode(['default'=>$settings['members_access']['default_message']??null,'comingSoon'=>get_option('woocommerce_coming_soon'),'plugins'=>get_option('active_plugins')]));chmod($backup,0600);
 $f=['admin'=>1,'member'=>224];$f['customer']=wp_insert_user(['user_login'=>'qa_legacy_20261024','user_email'=>'qa-legacy-20261024@example.test','user_pass'=>wp_generate_password(40),'role'=>'customer']);if(is_wp_error($f['customer']))WP_CLI::error($f['customer']->get_error_message());
 foreach(['post','gutenberg','elementor','author_post','author_gutenberg','author_elementor'] as $kind) {$f[$kind]=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA legacy '.$kind.' 20261024','post_content'=>'<p>QA LEGACY PROTECTED POST</p>']);$f['urls'][$kind]=get_permalink($f[$kind]); if(str_contains($kind,'elementor')){update_post_meta($f[$kind],'_elementor_edit_mode','builder');update_post_meta($f[$kind],'_elementor_version',ELEMENTOR_VERSION);update_post_meta($f[$kind],'_wp_page_template','elementor_canvas');}}
 $settings['members_access']['default_message']='<p>QA LEGACY DEFAULT</p>';update_option('arraysubs_settings',$settings);update_option('woocommerce_coming_soon','no');file_put_contents($manifest,wp_json_encode($f,JSON_PRETTY_PRINT));echo 'QA_RESULT:'.wp_json_encode($f);
} elseif($mode==='state'||$mode==='authors') {
 $f=json_decode(file_get_contents($manifest),true);$state=$args[1]??'legacy_login';
 $c=['enabled'=>true,'mode'=>'restrict','roles'=>['qa_nonexistent_role'],'products'=>[2147483000],'condition'=>'or','loginRequired'=>false,'showToAdmins'=>false,'message'=>'<p>QA LEGACY CUSTOM <strong>saved message</strong></p>'];
 if($state==='legacy_login')$c['loginRequired']=true;
 if($state==='legacy_any')$c['roles']=['demo_premium'];
 if(str_starts_with($state,'visibility')){$c['mode']='visibility';$c['visShow']=str_contains($state,'_in')?'logged_in':'logged_out';$c['visFallback']=$state==='visibility_empty'?'':($state==='visibility_shortcode'?'[arraysubs_restrict roles="qa_nonexistent_role" show_to_admins="false" message="QA LEGACY EXPANDED"]x[/arraysubs_restrict]':'<p>QA LEGACY FALLBACK</p>');}
 if($state==='default')$c['message']='';
 if($state==='admin')$c['showToAdmins']=true;
 if(str_starts_with($state,'current')) { unset($c['mode']);$c['accessVersion']=2;$c['loginRequired']=true;$c['useDefaultMessage']=false;if($state==='current_any_pass')$c['roles']=['demo_premium']; }
 if($state==='transitional_custom'||$state==='transitional_default'){unset($c['mode']);$c['loginRequired']=true;$c['useDefaultMessage']=$state==='transitional_default';}
 foreach(['post','gutenberg','elementor'] as $kind)qa_legacy_write($f[($mode==='authors'?'author_':'').$kind],$kind,$c);
 echo 'QA_RESULT:'.wp_json_encode(['state'=>$state]);
} elseif($mode==='core'||$mode==='pro') {
 $b=json_decode(file_get_contents($backup),true);$plugins=get_option('active_plugins');$pro='arraysubspro/arraysubspro.php';
 if($mode==='core')$plugins=array_values(array_diff($plugins,[$pro]));elseif(in_array($pro,$b['plugins'],true)&&!in_array($pro,$plugins,true))array_splice($plugins,(int)array_search($pro,$b['plugins'],true),0,[$pro]);update_option('active_plugins',$plugins);echo 'QA_RESULT:'.wp_json_encode(['mode'=>$mode]);
} elseif($mode==='restore') {
 $f=json_decode(file_get_contents($manifest),true);$b=json_decode(file_get_contents($backup),true);require_once ABSPATH.'wp-admin/includes/user.php';
 foreach(['post','gutenberg','elementor','author_post','author_gutenberg','author_elementor'] as $kind)wp_delete_post($f[$kind],true);wp_delete_user($f['customer']);
 $settings=get_option('arraysubs_settings');if(str_contains((string)($settings['members_access']['default_message']??''),'QA LEGACY')){$settings['members_access']['default_message']=$b['default'];update_option('arraysubs_settings',$settings);}
 if(get_option('woocommerce_coming_soon')==='no'){$b['comingSoon']===false?delete_option('woocommerce_coming_soon'):update_option('woocommerce_coming_soon',$b['comingSoon']);}
 $plugins=get_option('active_plugins');if(in_array('arraysubspro/arraysubspro.php',$b['plugins'],true)&&!in_array('arraysubspro/arraysubspro.php',$plugins,true)){array_splice($plugins,(int)array_search('arraysubspro/arraysubspro.php',$b['plugins'],true),0,['arraysubspro/arraysubspro.php']);update_option('active_plugins',$plugins);}
 unlink($backup);echo 'QA_RESULT:'.wp_json_encode(['pagesRemoved'=>!get_post($f['post'])&&!get_post($f['author_elementor']),'customerRemoved'=>!get_userdata($f['customer']),'defaultRestored'=>(get_option('arraysubs_settings')['members_access']['default_message']??null)===$b['default'],'visibilityRestored'=>get_option('woocommerce_coming_soon')===$b['comingSoon'],'pluginsRestored'=>get_option('active_plugins')===$b['plugins'],'privateBackupRemoved'=>!file_exists($backup)]);
} else { echo 'QA_RESULT:'.file_get_contents($manifest); }
