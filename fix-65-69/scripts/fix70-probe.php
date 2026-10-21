<?php
/**
 * #70 probe: site URL identity and Paddle keys in the current request context.
 *
 * Run it twice and compare: once through WP-CLI (HTTPS, like web requests) and once
 * through plain PHP without HTTPS (like the system cron):
 *   wp eval-file <abs>/qa/fix-65-69/scripts/fix70-probe.php --allow-root
 *   sudo -u www-data php -r 'define("WP_USE_THEMES", false); $_SERVER["HTTP_HOST"]="mirror-help.arrayhash.com"; require "<root>/wp-load.php"; require "<abs>/qa/fix-65-69/scripts/fix70-probe.php";'
 * Reads only; builds keys in memory.
 */
use ArraySubs\Features\AutomaticPayments\Gateways\Paddle\PaddleGateway;

$call = static function ($object, string $method, array $args = []) {
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs($object, $args);
};
$out = [
    'is_ssl'                  => is_ssl(),
    'get_site_url'            => get_site_url(),
    'site_identity_url'       => arraysubs_get_site_identity_url(),
    'site_identity_key'       => arraysubs_get_site_identity_key(),
    'matches_home_option'     => arraysubs_is_this_site_url((string) get_option('home')),
    'matches_https'           => arraysubs_is_this_site_url('https://mirror-help.arrayhash.com'),
    'matches_http_slash'      => arraysubs_is_this_site_url('http://mirror-help.arrayhash.com/'),
    'matches_other_site'      => arraysubs_is_this_site_url('https://test1.emran.io'),
    'matches_empty'           => arraysubs_is_this_site_url(''),
];
$gateway = (new ReflectionClass(PaddleGateway::class))->newInstanceWithoutConstructor();
$out['checkout_creation_key'] = substr($call($gateway, 'getCheckoutCreationKey', [12345, 678]), 0, 16);
$out['plan_switch_key']       = substr($call($gateway, 'getPaddlePlanSwitchKey', [12345, 678, 'contracthash']), 0, 16);
echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
