<?php
$action=$args[0] ?? '';
$directory=dirname(__DIR__).'/evidence/';
if ('save'===$action) {
 file_put_contents($directory.'original-active-plugin-paths.json', wp_json_encode(get_option('active_plugins')));
 echo 'Saved original active plugins.';
} elseif ('without-optional'===$action) {
 $settings=get_option('arraysubs_settings');
 $settings['modules']['store_credit']=false;
 $settings['modules']['audits']=false;
 update_option('arraysubs_settings',$settings);
 echo 'Store Credit and Audits disabled for this check.';
} elseif ('without-pro'===$action) {
 update_option('active_plugins',array_values(array_filter(get_option('active_plugins'),static fn($plugin)=>$plugin!=='arraysubspro/arraysubspro.php')));
 echo 'Pro omitted from active_plugins for core-only loading; lifecycle hooks not run.';
} elseif ('restore'===$action) {
 $settings=get_option('arraysubs_settings');
 $settings['modules']=json_decode(file_get_contents($directory.'original-modules.json'),true);
 update_option('arraysubs_settings',$settings);
 update_option('active_plugins',json_decode(file_get_contents($directory.'original-active-plugin-paths.json'),true));
 echo 'Restored original module switches and active plugins.';
}
