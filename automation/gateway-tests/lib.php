<?php

/**
 * Gateway test harness.
 *
 * Runs inside a booted WordPress via `wp eval-file`, so the code under test is
 * the real plugin with its real hooks — nothing is stubbed except the provider's
 * HTTP client. That boundary is the point: the branches worth testing in a
 * payment integration are the ones that decide whether money moved, and every
 * one of them is unreachable if the only way in is a live API call.
 *
 * @package ArraySubs\QA
 */

defined('ABSPATH') || exit;

/**
 * Minimal assertion harness with a non-zero exit code on failure.
 */
class ArraySubs_Gateway_Test_Runner
{
    /** @var int */
    public static $passed = 0;

    /** @var array<int, string> */
    public static $failures = [];

    /** @var string */
    private static $group = '';

    /**
     * Start a named group of assertions.
     *
     * @param string $name Group name.
     * @return void
     */
    public static function group(string $name): void
    {
        self::$group = $name;
        echo "\n" . $name . "\n";
    }

    /**
     * Assert a condition.
     *
     * @param bool   $condition Condition.
     * @param string $label     What is being asserted.
     * @param string $detail    Extra context shown on failure.
     * @return void
     */
    public static function assert(bool $condition, string $label, string $detail = ''): void
    {
        if ($condition) {
            ++self::$passed;
            echo '  PASS  ' . $label . "\n";
            return;
        }

        self::$failures[] = self::$group . ' :: ' . $label . ('' !== $detail ? ' — ' . $detail : '');
        echo '  FAIL  ' . $label . ('' !== $detail ? ' — ' . $detail : '') . "\n";
    }

    /**
     * Assert two values are identical.
     *
     * @param mixed  $expected Expected value.
     * @param mixed  $actual   Actual value.
     * @param string $label    What is being asserted.
     * @return void
     */
    public static function same($expected, $actual, string $label): void
    {
        self::assert(
            $expected === $actual,
            $label,
            $expected === $actual ? '' : sprintf(
                'expected %s, got %s',
                var_export($expected, true),
                var_export($actual, true)
            )
        );
    }

    /**
     * Print the summary and exit with a status code.
     *
     * @return void
     */
    public static function finish(): void
    {
        echo "\n" . str_repeat('─', 68) . "\n";
        printf("%d assertions passed, %d failed\n", self::$passed, count(self::$failures));

        if ([] !== self::$failures) {
            echo "\nFailures:\n";
            foreach (self::$failures as $failure) {
                echo '  - ' . $failure . "\n";
            }
            exit(1);
        }

        echo "ALL GREEN\n";
    }
}

/**
 * A PayPal client that answers from a script instead of the network.
 *
 * Extends the real client so the gateway's own `instanceof` seam accepts it, and
 * records every call so a test can assert on what the gateway *sent*, which is
 * usually the interesting half.
 */
class ArraySubs_Fake_PayPal_Client extends \ArraySubs\Features\AutomaticPayments\Gateways\PayPal\PayPalApiClient
{
    /** @var array<string, mixed> Queued responses keyed by method name. */
    public array $responses = [];

    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];

    public function __construct(array $responses = [])
    {
        parent::__construct('test-client-id', 'test-secret', 'sandbox');
        $this->responses = $responses;
    }

    /**
     * Pop the next scripted response for a method.
     *
     * A list of responses is consumed in order, so a test can script
     * "first call fails, re-read shows suspended".
     *
     * @param string $method Method name.
     * @param array  $args   Call arguments.
     * @return mixed
     */
    private function answer(string $method, array $args)
    {
        $this->calls[] = ['method' => $method, 'args' => $args];

        if (! array_key_exists($method, $this->responses)) {
            return new \WP_Error('fake_unscripted', 'No scripted response for ' . $method);
        }

        $scripted = $this->responses[$method];

        if (is_array($scripted) && array_key_exists('__sequence', $scripted)) {
            $next = array_shift($this->responses[$method]['__sequence']);

            return null === $next
                ? new \WP_Error('fake_sequence_exhausted', 'Sequence exhausted for ' . $method)
                : $next;
        }

        return $scripted;
    }

    /**
     * How many times a method was called.
     *
     * @param string $method Method name.
     * @return int
     */
    public function countCalls(string $method): int
    {
        return count(array_filter($this->calls, static fn (array $c): bool => $c['method'] === $method));
    }

    /**
     * The arguments of the first call to a method.
     *
     * @param string $method Method name.
     * @return array
     */
    public function firstCallArgs(string $method): array
    {
        foreach ($this->calls as $call) {
            if ($call['method'] === $method) {
                return $call['args'];
            }
        }

        return [];
    }

    public function getAccessToken()
    {
        return 'fake-token';
    }

    public function createProduct(array $params)
    {
        return $this->answer('createProduct', [$params]);
    }

    public function createPlan(array $params)
    {
        return $this->answer('createPlan', [$params]);
    }

    public function getPlan(string $plan_id)
    {
        return $this->answer('getPlan', [$plan_id]);
    }

    public function createSubscription(array $params)
    {
        return $this->answer('createSubscription', [$params]);
    }

    public function getSubscription(string $subscription_id)
    {
        return $this->answer('getSubscription', [$subscription_id]);
    }

    public function cancelSubscription(string $subscription_id, string $reason = '')
    {
        return $this->answer('cancelSubscription', [$subscription_id, $reason]);
    }

    public function suspendSubscription(string $subscription_id, string $reason = '')
    {
        return $this->answer('suspendSubscription', [$subscription_id, $reason]);
    }

    public function activateSubscription(string $subscription_id, string $reason = '')
    {
        return $this->answer('activateSubscription', [$subscription_id, $reason]);
    }

    public function reviseSubscription(string $subscription_id, array $params)
    {
        return $this->answer('reviseSubscription', [$subscription_id, $params]);
    }

    public function captureOutstanding(string $subscription_id, array $params = [])
    {
        return $this->answer('captureOutstanding', [$subscription_id, $params]);
    }

    public function refundSale(string $sale_id, array $params = [], string $request_id = '')
    {
        // The real client refuses a missing key; that guard is tested directly
        // against the real client, so the fake mirrors it rather than hiding it.
        if ('' === trim($request_id)) {
            return new \WP_Error('paypal_refund_key_missing', 'A per-attempt PayPal refund idempotency key is required.');
        }

        return $this->answer('refundSale', [$sale_id, $params, $request_id]);
    }

    public function getSubscriptionTransactions(string $subscription_id, string $start_time, string $end_time)
    {
        return $this->answer('getSubscriptionTransactions', [$subscription_id, $start_time, $end_time]);
    }
}

/**
 * A Mollie client that answers from a script instead of the network.
 */
class ArraySubs_Fake_Mollie_Client extends \ArraySubs\Features\AutomaticPayments\Services\MollieApiClient
{
    /** @var array<string, mixed> Responses keyed by "METHOD path-prefix". */
    public array $routes = [];

    /** @var array<int, array{method: string, path: string, body: array}> */
    public array $calls = [];

    public function __construct(array $routes = [])
    {
        parent::__construct('test_mollie_key');
        $this->routes = $routes;
    }

    /**
     * Resolve a scripted route by method and path prefix.
     *
     * @param string $method HTTP method.
     * @param string $path   Request path.
     * @param array  $body   Request body.
     * @return mixed
     */
    private function answer(string $method, string $path, array $body = [])
    {
        $this->calls[] = ['method' => $method, 'path' => $path, 'body' => $body];

        foreach ($this->routes as $pattern => $response) {
            [$route_method, $route_path] = array_pad(explode(' ', $pattern, 2), 2, '');

            if (strtoupper($route_method) !== $method) {
                continue;
            }

            if ('' !== $route_path && 0 === strpos($path, $route_path)) {
                return $response;
            }
        }

        return new \WP_Error('fake_unscripted', 'No scripted route for ' . $method . ' ' . $path);
    }

    /**
     * How many calls matched a method and path prefix.
     *
     * @param string $method HTTP method.
     * @param string $prefix Path prefix.
     * @return int
     */
    public function countCalls(string $method, string $prefix = ''): int
    {
        return count(array_filter(
            $this->calls,
            static fn (array $c): bool => $c['method'] === $method
                && ('' === $prefix || 0 === strpos($c['path'], $prefix))
        ));
    }

    public function hasKey(): bool
    {
        return true;
    }

    public function get(string $path, array $query = [])
    {
        return $this->answer('GET', $path, $query);
    }

    public function post(string $path, array $body, string $idempotency_key = '')
    {
        return $this->answer('POST', $path, $body);
    }

    public function delete(string $path)
    {
        return $this->answer('DELETE', $path);
    }
}

/**
 * Build a throwaway subscription for a test.
 *
 * @param array $meta Meta to set.
 * @return int Subscription post ID.
 */
function arraysubs_test_make_subscription(array $meta = []): int
{
    $subscription_id = wp_insert_post([
        'post_type'   => 'arraysubs_data',
        'post_status' => $meta['__status'] ?? 'arraysubs-active',
        'post_title'  => 'Gateway test subscription',
    ]);

    unset($meta['__status']);

    $defaults = [
        '_billing_period'   => 'month',
        '_billing_interval' => 1,
        '_recurring_amount' => '20.00',
        '_subscription_price' => '20.00',
        '_quantity'         => 1,
        '_currency'         => 'USD',
        '_trial_length'     => 0,
        '_trial_period'     => '',
        '_signup_fee'       => 0,
    ];

    foreach (array_merge($defaults, $meta) as $key => $value) {
        update_post_meta($subscription_id, $key, $value);
    }

    return (int) $subscription_id;
}

/**
 * Delete a test subscription and anything attached to it.
 *
 * @param int $subscription_id Subscription post ID.
 * @return void
 */
function arraysubs_test_delete_subscription(int $subscription_id): void
{
    wp_delete_post($subscription_id, true);
}
