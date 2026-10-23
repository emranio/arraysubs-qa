<?php
use ArraySubs\Features\GutenbergIntegration\Services\EditorContentCompatibility;
wp_set_current_user(1);
$marker='<!-- wp:arraysubs/content-restricted {"loginRequired":1.0,"roles":["qa_nonexistent_role"],"showToAdmins":false} --><div class="wp-block-arraysubs-content-restricted"><!-- wp:paragraph --><p>REST_BODY</p><!-- /wp:paragraph --></div><!-- /wp:arraysubs/content-restricted -->';
$GLOBALS['auditChecks']=[];$GLOBALS['auditErrors']=[];$created=[];$id=20191;$original=get_post_field('post_content',$id);$widgetBefore=get_option('widget_block',null);$sidebarsBefore=get_option('sidebars_widgets');
function auditCheck($name,$value){$GLOBALS['auditChecks'][$name]=(bool)$value;if(!$value)$GLOBALS['auditErrors'][]=$name;}
function requestAudit($path,$context='edit'){$r=new WP_REST_Request('GET',$path);$r->set_param('context',$context);return rest_do_request($r);}
function rawContract($raw){$b=parse_blocks($raw)[0]??[];return $b['attrs']['legacy']['loginOnly']??null;}
try {
 global $wpdb;$wpdb->update($wpdb->posts,['post_content'=>$marker],['ID'=>$id]);clean_post_cache($id);
 $stored=get_post_field('post_content',$id);$beforeHash=hash('sha256',$stored);auditCheck('stored-float-native-json',strpos($stored,'1.0')!==false);
 $edit=requestAudit('/wp/v2/pages/'.$id);$raw=$edit->get_data()['content']['raw']??'';
 auditCheck('post-edit-200',$edit->get_status()===200);auditCheck('post-float-contract-false',rawContract($raw)===false);auditCheck('post-int-contract-true',rawContract(EditorContentCompatibility::normalizeContent(str_replace('1.0','1',$marker)))===true);
 auditCheck('post-edit-idempotent',$raw===EditorContentCompatibility::normalizeContent($raw));auditCheck('post-db-no-write',hash('sha256',get_post_field('post_content',$id))===$beforeHash);$view=requestAudit('/wp/v2/pages/'.$id,'view');auditCheck('post-view-no-raw',!isset($view->get_data()['content']['raw']));
 $preload=rest_preload_api_request([], '/wp/v2/pages/'.$id.'?context=edit');$preloaded=$preload['/wp/v2/pages/'.$id.'?context=edit']['body']['content']['raw']??'';
 auditCheck('internal-editor-preload-canonical',rawContract($preloaded)===false);auditCheck('internal-editor-preload-no-write',get_post_field('post_content',$id)===$stored);
 foreach(['wp_block','wp_template','wp_template_part'] as $type){
  $slug='qa-final-gutenberg-'.str_replace('_','-',$type).'-20261024';
  $record=wp_insert_post(['post_type'=>$type,'post_status'=>'publish','post_title'=>$slug,'post_name'=>$slug,'post_content'=>wp_slash($marker)]);$created[]=$record;
  $wpdb->update($wpdb->posts,['post_content'=>$marker],['ID'=>$record]);clean_post_cache($record);
  if($type==='wp_block')$route='/wp/v2/blocks/'.$record;
  else{wp_set_object_terms($record,get_stylesheet(),'wp_theme');$route='/wp/v2/'.($type==='wp_template'?'templates':'template-parts').'/'.get_stylesheet().'//'.$slug;}
  $storedType=get_post_field('post_content',$record);$result=requestAudit($route);$d=$result->get_data();
  auditCheck($type.'-edit-200',$result->get_status()===200);auditCheck($type.'-float-contract-false',rawContract($d['content']['raw']??'')===false);auditCheck($type.'-no-write',get_post_field('post_content',$record)===$storedType);
  $viewResult=requestAudit($route,'view');auditCheck($type.'-view-unmodified',!isset($viewResult->get_data()['content']['raw'])||strpos($viewResult->get_data()['content']['raw'],'accessVersion')===false);
 }
 $widgets=is_array($widgetBefore)?$widgetBefore:[];$widgets[989899]=['content'=>$marker];$widgets['_multiwidget']=1;update_option('widget_block',$widgets);
 $GLOBALS['wp_widget_factory']->widgets['WP_Widget_Block']->_set(989899);$GLOBALS['wp_widget_factory']->widgets['WP_Widget_Block']->_register_one(989899);$sidebars=$sidebarsBefore;$sidebars['wp_inactive_widgets'][]='block-989899';update_option('sidebars_widgets',$sidebars);$GLOBALS['_wp_sidebars_widgets']=$sidebars;$widgetStored=get_option('widget_block');$widget=requestAudit('/wp/v2/widgets/block-989899');$wd=$widget->get_data();
 auditCheck('widget-edit-200',$widget->get_status()===200);auditCheck('widget-float-contract-false',rawContract($wd['instance']['raw']['content']??'')===false);
 $serialized=isset($wd['instance']['raw'])?serialize($wd['instance']['raw']):'';
 auditCheck('widget-signed-encoded-consistent',($wd['instance']['encoded']??null)===base64_encode($serialized));auditCheck('widget-signed-hash-consistent',($wd['instance']['hash']??null)===wp_hash($serialized));auditCheck('widget-db-no-write',get_option('widget_block')===$widgetStored);
 $widgetView=requestAudit('/wp/v2/widgets/block-989899','view');auditCheck('widget-view-no-raw',!isset($widgetView->get_data()['instance']['raw']));
 wp_set_current_user(455);$denied=requestAudit('/wp/v2/pages/'.$id);auditCheck('unauthorized-edit-denied',$denied->get_status()===403);wp_set_current_user(1);
 $GLOBALS['auditNestedGuard']=false;$GLOBALS['auditNestedResult']=[];
 $nestedCallback=function($response,$post,$request)use($id){
  if(!$GLOBALS['auditNestedGuard']&&$request->get_param('context')==='edit'){
   $GLOBALS['auditNestedGuard']=true;
   $nestedEdit=requestAudit('/wp/v2/pages/'.$id);$nestedView=requestAudit('/wp/v2/pages/'.$id,'view');
   $GLOBALS['auditNestedResult']=['edit'=>rawContract($nestedEdit->get_data()['content']['raw']??'')===false,'view'=>!isset($nestedView->get_data()['content']['raw'])];
  }return $response;
 };
 $GLOBALS['auditViewAfterParser']=false;$afterView=function($response,$handler,$request){if($request->get_param('context')==='view')$GLOBALS['auditViewAfterParser']=apply_filters('block_parser_class','WP_Block_Parser')==='WP_Block_Parser';return $response;};add_filter('rest_request_after_callbacks',$afterView,30,3);
 add_filter('rest_prepare_page',$nestedCallback,4,3);$nestedOuter=requestAudit('/wp/v2/pages/'.$id);remove_filter('rest_prepare_page',$nestedCallback,4);
 auditCheck('nested-edit-no-recursion',$GLOBALS['auditNestedResult']['edit']??false);remove_filter('rest_request_after_callbacks',$afterView,30);auditCheck('nested-view-after-callback-parser-unmodified',$GLOBALS['auditViewAfterParser']);
 auditCheck('nested-view-unmodified',$GLOBALS['auditNestedResult']['view']??false);
 auditCheck('parser-filter-cleaned-after-dispatch',apply_filters('block_parser_class','WP_Block_Parser')==='WP_Block_Parser');
 $GLOBALS['auditOriginalParserCalls']=0;
 if(!class_exists('QAOriginalBlockParser')){class QAOriginalBlockParser extends WP_Block_Parser{public function parse($document){$GLOBALS['auditOriginalParserCalls']++;return parent::parse($document);}}}
 $customParser=static function(){return 'QAOriginalBlockParser';};add_filter('block_parser_class',$customParser);
 $customResponse=requestAudit('/wp/v2/pages/'.$id);remove_filter('block_parser_class',$customParser);
 auditCheck('configured-parser-delegated',$GLOBALS['auditOriginalParserCalls']>0&&rawContract($customResponse->get_data()['content']['raw']??'')===false);
 $activeAdapter=static function(){ArraySubs\Features\GutenbergIntegration\Services\EditorBlockParser::$originalClass='WP_Block_Parser';return ArraySubs\Features\GutenbergIntegration\Services\EditorBlockParser::class;};add_filter('block_parser_class',$activeAdapter);
 $activeNormalized=EditorContentCompatibility::normalizeContent($marker);remove_filter('block_parser_class',$activeAdapter);
 auditCheck('normalize-content-under-adapter-persists',rawContract($activeNormalized)===false&&strpos($activeNormalized,'accessVersion')!==false);
 $GLOBALS['auditThrowOnce']=false;$GLOBALS['auditThrowGuard']=false;$GLOBALS['auditAfterCaughtException']=false;
 $throwingParser=static function(){if($GLOBALS['auditThrowOnce']){$GLOBALS['auditThrowOnce']=false;throw new RuntimeException('QA expected nested parser exception');}return 'WP_Block_Parser';};
 $catchNested=function($response,$post,$request)use($id,$marker){
  if(!$GLOBALS['auditThrowGuard']&&$request->get_param('context')==='edit'){
   $GLOBALS['auditThrowGuard']=true;$GLOBALS['auditThrowOnce']=true;
   try{requestAudit('/wp/v2/pages/'.$id);}catch(RuntimeException $error){}
   $tree=parse_blocks($marker);$GLOBALS['auditAfterCaughtException']=($tree[0]['attrs']['legacy']['loginOnly']??null)===false;
  }return $response;
 };
 add_filter('block_parser_class',$throwingParser);add_filter('rest_prepare_page',$catchNested,4,3);requestAudit('/wp/v2/pages/'.$id);remove_filter('rest_prepare_page',$catchNested,4);remove_filter('block_parser_class',$throwingParser);
 auditCheck('caught-nested-exception-restores-outer-scope',$GLOBALS['auditAfterCaughtException']);
 auditCheck('caught-exception-filter-cleaned',apply_filters('block_parser_class','WP_Block_Parser')==='WP_Block_Parser');
 $unrelated='<!-- wp:paragraph --><p>Unrelated</p><!-- /wp:paragraph -->';auditCheck('unrelated-content-byte-identical',EditorContentCompatibility::normalizeContent($unrelated)===$unrelated);
}finally{wp_set_current_user(1);wp_update_post(['ID'=>$id,'post_content'=>wp_slash($original)]);foreach($created as $record)wp_delete_post($record,true);update_option('sidebars_widgets',$sidebarsBefore);$GLOBALS['_wp_sidebars_widgets']=$sidebarsBefore;if($widgetBefore===null)delete_option('widget_block');else update_option('widget_block',$widgetBefore);}
$checks=$GLOBALS['auditChecks'];$errors=$GLOBALS['auditErrors'];$result=['checks'=>count($checks),'failures'=>$errors,'results'=>$checks,'initialContentSha256'=>$beforeHash,'afterReadSha256'=>hash('sha256',$stored),'readOnly'=>true,'temporaryFixturesRemoved'=>true];file_put_contents(WP_PLUGIN_DIR.'/qa/restriction-final-access-audit-2026-10-24/evidence/gutenberg-edit-rest-readonly.json',wp_json_encode($result,JSON_PRETTY_PRINT));echo 'QA_RESULT:'.wp_json_encode($result);if($errors)WP_CLI::halt(1);
