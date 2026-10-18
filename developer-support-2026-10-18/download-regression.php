<?php
$manager = new ArraySubs\Features\MembersAccess\Services\DownloadManager();
$removed = ['getDownloadCount','checkDownloadLimit','onRenewalComplete','incrementDownloadCount','resetDownloadCount','getUsageData','saveUsageData','sanitizePeriod','getPeriodStartTimestamp'];
foreach ($removed as $method) { if (method_exists($manager,$method)) { WP_CLI::error('Legacy method remains: '.$method); } }
if (function_exists('arraysubs_check_download_limit')) { WP_CLI::error('Legacy helper remains.'); }
$hooks = $GLOBALS['wp_filter']['arraysubs_renewal_payment_complete']->callbacks;
$classes=[];
foreach ($hooks as $callbacks) { foreach($callbacks as $cb) { if (is_array($cb['function']) && is_object($cb['function'][0])) { $classes[]=get_class($cb['function'][0]); } } }
foreach (['ArraySubs\\Features\\Integrations\\Services\\LifecycleDispatcher', 'ArraySubs\\Features\\Emails\\Services\\EmailManager'] as $required) {
    if (!in_array($required,$classes,true)) { WP_CLI::error('Missing renewal listener: '.$required); }
}
echo wp_json_encode(['legacy_symbols_absent'=>true,'renewal_listeners'=>$classes],JSON_PRETTY_PRINT),"\n";
