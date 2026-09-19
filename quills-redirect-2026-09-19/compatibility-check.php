<?php
/**
 * Read-only WP-CLI integration check using the actual WooPayments disabler class.
 * Supply its source file as the first eval-file argument if WooPayments is absent.
 * No options, posts or user records are written. Browser coverage is recorded separately.
 */
if (!class_exists('WC_Payments_Subscriptions_Disabler')) {
    require $args[0];
}
class ArraySubs_QA_Redirect extends RuntimeException {}
class ArraySubs_QA_Disabler extends WC_Payments_Subscriptions_Disabler {
    protected function redirect($target) { throw new ArraySubs_QA_Redirect($target); }
}
global $core, $disabler, $account, $path, $base, $passes;
$core = (new ReflectionClass(ArraySubs\Features\CustomerPortal\Services\MyAccountHooks::class))->newInstanceWithoutConstructor();
$disabler = new ArraySubs_QA_Disabler();
$account = wc_get_page_id('myaccount');
$path = get_page_uri($account);
$base = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
$passes = 0;
function check($condition, $label) {
    global $passes;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    ++$passes;
    echo 'PASS: ', $label, PHP_EOL;
}
function run_case($uri, $vars, $get = [], $protect = true, $main = true) {
    global $wp, $wp_filter, $wp_the_query, $core, $disabler, $base;
    // Exercise real WordPress hook removal while pre_get_posts is iterating.
    $wp_filter['pre_get_posts'] = new WP_Hook();
    $wp_filter['template_redirect'] = new WP_Hook();
    if ($protect) { add_action('pre_get_posts', [$core, 'protectEndpointsFromWooPayments'], 0); }
    add_action('pre_get_posts', [$disabler, 'maybe_redirect_subscription_endpoints'], 1);
    add_action('template_redirect', [$disabler, 'maybe_redirect_account_endpoints'], 5);
    $unrelated = static function () {};
    add_action('template_redirect', $unrelated, 5);
    add_filter('woocommerce_is_purchasable', [$disabler, 'make_subscription_products_unpurchasable'], 10, 2);
    $_GET = $get;
    $_SERVER['REQUEST_URI'] = '/' . ($base ? $base . '/' : '') . ltrim($uri, '/');
    $wp->query_vars = $vars;
    $query = new WP_Query();
    $query->parse_query($vars);
    $wp_the_query = $main ? $query : new WP_Query();
    $redirected = false;
    try {
        do_action_ref_array('pre_get_posts', [&$query]);
        do_action('template_redirect');
    } catch (ArraySubs_QA_Redirect $e) { $redirected = true; }
    $guards = false !== has_action('pre_get_posts', [$disabler, 'maybe_redirect_subscription_endpoints'])
        && false !== has_action('template_redirect', [$disabler, 'maybe_redirect_account_endpoints']);
    check(false !== has_action('template_redirect', $unrelated)
        && false !== has_filter('woocommerce_is_purchasable', [$disabler, 'make_subscription_products_unpurchasable']), 'unrelated redirects/purchase restrictions preserved: ' . $uri);
    return [$redirected, $guards];
}
foreach (['subscriptions' => '', 'subscriptions/2' => '2', 'view-subscription/123' => '123'] as $tail => $value) {
    $logical = explode('/', $tail)[0];
    $vars = ['pagename'=>$path, $logical=>$value, 'as-'.$logical=>$value];
    $uri = $path . '/as-' . $tail . '/';
    check(run_case($uri, $vars, [], false)[0], 'original WooPayments reproduces redirect: ' . $tail);
    check(run_case($uri, $vars) === [false, false], 'ArraySubs list/detail/pagination reaches renderer: ' . $tail);
}
check(run_case('?page_id='.$account.'&as-subscriptions=1', ['page_id'=>$account,'subscriptions'=>'1','as-subscriptions'=>'1'], ['page_id'=>$account,'as-subscriptions'=>'1']) === [false,false], 'plain permalink account route protected');
check(run_case($path.'/?as-view-subscription=123', ['pagename'=>$path,'view-subscription'=>'123','as-view-subscription'=>'123'], ['as-view-subscription'=>'123']) === [false,false], 'prefixed query detail route protected');
foreach ([
    [$path.'/subscriptions/', ['pagename'=>$path,'subscriptions'=>'','as-subscriptions'=>''], []],
    [$path.'/view-subscription/123/', ['pagename'=>$path,'view-subscription'=>'123','as-view-subscription'=>'123'], []],
    [$path.'/subscription-payment-method/123/', ['pagename'=>$path,'subscription-payment-method'=>'123'], []],
    [$path.'/orders/?as-subscriptions=1', ['pagename'=>$path,'orders'=>'','subscriptions'=>'1','as-subscriptions'=>'1'], ['as-subscriptions'=>'1']],
    [$path.'/as-subscriptions/?order-pay=123', ['pagename'=>$path,'subscriptions'=>'','as-subscriptions'=>'','order-pay'=>'123'], ['order-pay'=>'123']],
    [$path.'/as-subscriptions/?change_payment_method=123', ['pagename'=>$path,'subscriptions'=>'','as-subscriptions'=>''], ['change_payment_method'=>'123']],
    ['checkout/order-pay/123/', ['pagename'=>'checkout','order-pay'=>'123'], []],
    [$path.'/orders/', ['pagename'=>$path,'orders'=>''], []],
    ['other/as-subscriptions/', ['pagename'=>'other','subscriptions'=>'','as-subscriptions'=>''], []],
] as [$uri,$vars,$get]) {
    check(run_case($uri,$vars,$get)[1], 'WooPayments guards remain attached outside owned route: '.$uri);
}
check(run_case($path.'/as-subscriptions/', ['pagename'=>$path,'subscriptions'=>'','as-subscriptions'=>''], [], true, false)[1], 'secondary query cannot remove guards');
echo $passes, " checks passed.\n";
