<?php
use ArraySubs\Features\GutenbergIntegration\Services\Hooks;
use ArraySubs\Features\GutenbergIntegration\Services\LegacyAttributes;
use ArraySubs\Features\GutenbergIntegration\Services\EditorContentCompatibility;
$repo=WP_PLUGIN_DIR.'/arraysubs';
$source=shell_exec('git -C '.escapeshellarg($repo).' show HEAD:src/Features/GutenbergIntegration/Services/Hooks.php');
$source=preg_replace('/^<\?php/', '', $source);
$source=str_replace('class Hooks','class OldGutenbergHooksAudit',$source);
eval($source);
$oldClass='ArraySubs\\Features\\GutenbergIntegration\\Services\\OldGutenbergHooksAudit';
$schema=json_decode(shell_exec('git -C '.escapeshellarg($repo).' show HEAD:src/Features/GutenbergIntegration/block/block.json'),true);
$old=new WP_Block_Type('arraysubs/content-restricted-audit',['attributes'=>$schema['attributes']]);
$base=['roles'=>['qa_nonexistent_role'],'products'=>[2147483000],'condition'=>'or','showToAdmins'=>false,'message'=>'<p>Custom denial</p>'];
$cases=['ordinary-any'=>array_replace($base,['roles'=>['demo_premium']]),'ordinary-all-false'=>$base,'ordinary-all-match'=>array_replace($base,['roles'=>['demo_premium'],'lifetimeSpent'=>'0']),'explicit-old-enabled-false'=>array_replace($base,['mode'=>'restrict','enabled'=>false]),'guests-only'=>['mode'=>'visibility','visShow'=>'logged_out','visFallback'=>'<p>Old fallback</p>','showToAdmins'=>false], 'uppercase-role'=>array_replace($base,['roles'=>['Demo_Premium']]),'csv-role'=>array_replace($base,['roles'=>'demo_premium,qa_missing']),'csv-status'=>array_replace($base,['status'=>'active,on-hold']),'csv-products'=>array_replace($base,['products'=>'2147483000,2147483001']),'negative-product'=>array_replace($base,['products'=>[-2147483000]]),'default-container'=>[]];
foreach([2,-1,1,1.0,0,0.0,'false','true','0','1','yes',null,[],['x'],(object)['x'=>1]] as $i=>$v){$cases['login-value-'.$i]=array_replace($base,['loginRequired'=>$v]);$cases['admin-value-'.$i]=array_replace($base,['showToAdmins'=>$v]);}
$actors=['guest'=>0,'customer'=>455,'member'=>224,'admin'=>1];$rows=[];$fail=[];$checks=0;$body='<p>AUDIT_PROTECTED_MARKER</p>';
foreach($cases as $name=>$raw){
 $prepared=$old->prepare_attributes_for_render($raw);
 $canonical=LegacyAttributes::forEditor($raw);
 // JS JSON roundtrip loses 1.0, but canonicalization must already have resolved it.
 $saved=json_decode(wp_json_encode($canonical),true);
 foreach($actors as $actor=>$uid){wp_set_current_user($uid);
  $expected=$oldClass::gate($prepared,$body);
  $live=Hooks::gate($raw,$body);$after=Hooks::gate($saved,$body);
  foreach(['live'=>$live,'afterEditorJson'=>$after] as $phase=>$output){$checks++;if($expected!==$output)$fail[]=['case'=>$name,'actor'=>$actor,'phase'=>$phase,'old'=>$expected,'actual'=>$output,'raw'=>$raw,'canonical'=>$canonical];}
  $rows[]=['case'=>$name,'actor'=>$actor,'allowed'=>strpos($expected,'AUDIT_PROTECTED_MARKER')!==false];
 }
}
wp_set_current_user(1);
$content='<!-- wp:group --><div class="wp-block-group"><!-- wp:arraysubs/content-restricted {"loginRequired":1.0,"roles":["qa_nonexistent_role"],"showToAdmins":false} --><div class="wp-block-arraysubs-content-restricted"><!-- wp:paragraph --><p>BODY</p><!-- /wp:paragraph --></div><!-- /wp:arraysubs/content-restricted --></div><!-- /wp:group -->';
$normalized=EditorContentCompatibility::normalizeContent($content);
$checks++;if($normalized!==EditorContentCompatibility::normalizeContent($normalized))$fail[]=['idempotence'=>false];
$parsed=parse_blocks($normalized)[0]['innerBlocks'][0];$checks++;if($parsed['attrs']['legacy']['loginOnly']!==false)$fail[]=['float-preserved'=>false,'attrs'=>$parsed['attrs']];
$checks++;if($parsed['innerBlocks'][0]['innerHTML']!=='<p>BODY</p>')$fail[]=['innerHTML'=>false];
$out=['checks'=>$checks,'cases'=>count($cases),'actors'=>$actors,'failures'=>$fail,'audiences'=>$rows];
file_put_contents(WP_PLUGIN_DIR.'/qa/restriction-final-access-audit-2026-10-24/evidence/gutenberg-head-server-differential.json',wp_json_encode($out,JSON_PRETTY_PRINT|JSON_PRESERVE_ZERO_FRACTION));
echo 'QA_RESULT:'.wp_json_encode(['checks'=>$checks,'cases'=>count($cases),'failures'=>count($fail),'firstFailures'=>array_slice($fail,0,3)]);if($fail)WP_CLI::halt(1);
