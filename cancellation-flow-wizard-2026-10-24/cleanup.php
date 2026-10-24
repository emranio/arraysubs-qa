<?php
defined('ABSPATH') || exit;
$b=json_decode(file_get_contents('/tmp/arraysubs-cancellation-backup.json'),true);
$f=json_decode(file_get_contents(__DIR__.'/fixtures.json'),true);
$current=get_option('arraysubs_settings');
$outside=$current;$original=$b['settings'];unset($outside['cancellation'],$original['cancellation']);
$outside['customer_actions']['allow_cancellation']=$original['customer_actions']['allow_cancellation'];
$unrelated_unchanged=$outside===$original;
$current['cancellation']=$b['settings']['cancellation'];
$current['customer_actions']['allow_cancellation']=$b['settings']['customer_actions']['allow_cancellation'];
update_option('arraysubs_settings',$current);
if($unrelated_unchanged) {
    $b['saved_at']===false?delete_option('arraysubs_settings_last_saved_at'):update_option('arraysubs_settings_last_saved_at',$b['saved_at']);
}
if(get_option('woocommerce_coming_soon')==='no') update_option('woocommerce_coming_soon',$b['coming_soon']);
// Restore just the plugin whose activation this validation changed.
$plugins=get_option('active_plugins');$pro='arraysubspro/arraysubspro.php';
if(in_array($pro,$b['plugins'],true)&&!in_array($pro,$plugins,true)) {array_splice($plugins,(int)array_search($pro,$b['plugins'],true),0,[$pro]);update_option('active_plugins',$plugins);}
if(!in_array($pro,$b['plugins'],true)&&in_array($pro,$plugins,true)) update_option('active_plugins',array_values(array_diff($plugins,[$pro])));
add_filter('pre_wp_mail','__return_true');
foreach($f['subscriptions'] as $id) {
    \ArraySubs\Supports\ActionScheduler::unscheduleAllForSubscription($id);
    wp_delete_post($id,true);
    global $wpdb;
    foreach(['arraysubs_retention_logs','arraysubs_retention_event_outbox'] as $suffix) {
        $table=$wpdb->prefix.$suffix;
        if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))===$table) $wpdb->delete($table,['subscription_id'=>$id],['%d']);
    }
}
foreach($f['products'] as $id) { $product=wc_get_product($id);if($product)$product->delete(true); }
if(!empty($f['order'])) { $order=wc_get_order($f['order']);if($order)$order->delete(true); }
require_once ABSPATH.'wp-admin/includes/user.php';
wp_delete_user($f['user']);
if(!empty($f['admin'])) wp_delete_user($f['admin']);
if(file_exists('/tmp/arraysubs-cancellation-admin-token')) WP_Session_Tokens::get_instance(1)->destroy(file_get_contents('/tmp/arraysubs-cancellation-admin-token'));
$restored=get_option('arraysubs_settings');
$results=[
    'cancellation_restored'=>$restored['cancellation']===$b['settings']['cancellation'],
    'customer_cancellation_policy_restored'=>$restored['customer_actions']['allow_cancellation']===$b['settings']['customer_actions']['allow_cancellation'],
    'unrelated_settings_preserved'=>$unrelated_unchanged ? $restored===$b['settings'] : true,
    'plugins_restored'=>get_option('active_plugins')===$b['plugins'],
    'saved_at_restored'=>$unrelated_unchanged ? get_option('arraysubs_settings_last_saved_at')===$b['saved_at'] : 'preserved concurrent timestamp',
    'test_users_removed'=>!get_user_by('id',$f['user'])&&!get_user_by('id',$f['admin']),
    'test_posts_removed'=>count(array_filter(array_merge(array_values($f['subscriptions']),$f['products']),'get_post'))===0,
    'test_order_removed'=>!wc_get_order($f['order']),
];
file_put_contents(__DIR__.'/evidence/restoration.json',wp_json_encode($results,JSON_PRETTY_PRINT));echo wp_json_encode($results,JSON_PRETTY_PRINT).PHP_EOL;
if(in_array(false,$results,true)) WP_CLI::error('Cleanup verification failed.');
