<?php
/**
 * ArraySubs marketing media seed: twelve months of steady demo growth for the
 * analytics captures (Home dashboard, AI forecast, KPIs, retention, member page).
 *
 *   wp eval-file growth-seed.php plan     # print the projected figures, write nothing
 *   wp eval-file growth-seed.php seed     # create the demo history
 *   wp eval-file growth-seed.php cleanup  # remove everything carrying the marker
 *
 * Every entity carries the marker below so the whole set can be removed later.
 * Existing data is never edited or deleted.
 */

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

final class AS_Media_Growth_Seed
{
    const MARK_KEY      = '_arraysubs_media_seed';
    const USER_MARK_KEY = 'arraysubs_media_seed';
    const MARK          = 'analytics-growth-20261125';
    const REGISTRY      = 'arraysubs_media_seed_analytics_growth';
    const TEAM_SKU      = 'MEDIA-TEAM-MEMBERSHIP-20261125';

    private \DateTimeZone $tz;
    private \DateTimeZone $utc;
    private \DateTimeImmutable $now;
    private int $now_ts;
    private array $people_names = [];
    private array $used_names = [];
    private array $used_emails = [];
    private array $catalog = [];
    private array $plan = [];
    private array $customers = [];
    private array $registry = ['users' => [], 'subscriptions' => [], 'orders' => [], 'events' => [], 'logs' => [], 'products' => []];
    private array $cached_meta = [];

    public function __construct()
    {
        $this->tz     = wp_timezone();
        $this->utc    = new \DateTimeZone('UTC');
        $this->now_ts = time();
        $this->now    = (new \DateTimeImmutable('@' . $this->now_ts))->setTimezone($this->tz);
        mt_srand(20261125);
    }

    /* ------------------------------------------------------------------ */
    /* Entry                                                               */
    /* ------------------------------------------------------------------ */

    public function run(string $mode): void
    {
        if ('cleanup' === $mode) {
            $this->cleanup();
            return;
        }

        if ('append' === $mode) {
            $this->append();
            return;
        }

        if (get_option(self::REGISTRY) && in_array($mode, ['seed', 'seedtest'], true)) {
            \WP_CLI::error('A seeded set already exists (option ' . self::REGISTRY . '). Run cleanup first.');
        }

        $this->loadPeople();
        $this->buildCatalog(false);
        $this->buildPlan();
        $this->report();

        if ('seedtest' === $mode) {
            $this->buildCatalog(true);
            $pick = [];
            $want = ['featured' => 2, 'base' => 1, 'team' => 1, 'failed-trial' => 1, 'trial-converted' => 1, 'recent' => 1];
            $cancelled_cohort = false;
            foreach ($this->plan as $i => $p) {
                if (isset($want[$p['group']]) && $want[$p['group']] > 0) {
                    $pick[] = $i;
                    $want[$p['group']]--;
                } elseif (!$cancelled_cohort && 0 === strpos($p['group'], 'cohort-') && 'arraysubs-cancelled' === $p['status']) {
                    $pick[] = $i;
                    $cancelled_cohort = true;
                }
            }
            $map = [];
            $customers = [];
            $plan = [];
            foreach ($pick as $i) {
                $p = $this->plan[$i];
                if (!isset($map[$p['cust']])) {
                    $map[$p['cust']] = count($customers);
                    $customers[] = $this->customers[$p['cust']];
                }
                $p['cust'] = $map[$p['cust']];
                $plan[] = $p;
            }
            foreach ($this->plan_oneoffs as &$o) {
                $o['cust'] = $map[$o['cust']];
            }
            unset($o);
            $this->plan = $plan;
            $this->customers = $customers;
            $this->suppressSideEffects();
            $this->write();
        }

        if ('seed' === $mode) {
            $this->buildCatalog(true);
            $this->suppressSideEffects();
            $this->write();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Catalog                                                             */
    /* ------------------------------------------------------------------ */

    private function buildCatalog(bool $create_team): void
    {
        // key => product id, variation id, display name, price, period, interval, weight, attribute
        $this->catalog = [
            'plus'       => [6711, 0, 'Demo · Plus Membership', 39.0, 'month', 1, 24, null],
            'premium'    => [6712, 0, 'Demo · Premium Membership', 69.0, 'month', 1, 14, null],
            'tier_pro'   => [6722, 6724, 'Demo · Membership Tier - professional', 59.0, 'month', 1, 13, ['tier', 'professional']],
            'tier_basic' => [6722, 6723, 'Demo · Membership Tier - basic', 24.0, 'month', 1, 11, ['tier', 'basic']],
            'starter'    => [6710, 0, 'Demo · Starter Membership', 19.0, 'month', 1, 11, null],
            'quarterly'  => [6715, 0, 'Demo · Quarterly Learning Pass', 99.0, 'month', 3, 9, null],
            'annual'     => [6716, 0, 'Demo · Annual Library Pass', 199.0, 'year', 1, 8, null],
            'trialpass'  => [6717, 0, 'Demo · Library Pass with 14-day Trial', 29.0, 'month', 1, 0, null],
            'team'       => [0, 0, 'Demo · Team Membership', 399.0, 'month', 1, 0, null],
            // one-off products for the featured member
            'mug'        => [6698, 0, 'Demo · Ceramic Member Mug', 16.0, '', 0, 0, null],
            'flask'      => [6702, 0, 'Demo · Limited Edition Flask', 39.0, '', 0, 0, null],
            'workshop'   => [6701, 0, 'Demo · One-time Barista Workshop', 89.0, '', 0, 0, null],
            'tin'        => [6703, 0, 'Demo · Seasonal Gift Tin', 29.0, '', 0, 0, null],
        ];

        foreach ($this->catalog as $key => $row) {
            if ('team' === $key) {
                continue;
            }
            $id = $row[1] ?: $row[0];
            if (!wc_get_product($id)) {
                \WP_CLI::error("Missing catalog product $key ($id)");
            }
        }

        if ($create_team) {
            $this->catalog['team'][0] = $this->createTeamProduct();
        }
    }

    private function createTeamProduct(): int
    {
        $existing = wc_get_product_id_by_sku(self::TEAM_SKU);
        if ($existing) {
            return (int) $existing;
        }

        $product = new \WC_Product_Simple();
        $product->set_name('Demo · Team Membership');
        $product->set_sku(self::TEAM_SKU);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_regular_price('399');
        $product->set_price('399');
        $product->set_short_description('Membership for teams of up to 10 people.');
        foreach ([
            self::MARK_KEY           => self::MARK,
            '_is_subscription'       => 'yes',
            '_subscription_period'   => 'month',
            '_subscription_interval' => 1,
            '_subscription_length'   => 0,
            '_trial_length'          => 0,
            '_trial_period'          => 'day',
            '_signup_fee'            => 0,
        ] as $key => $value) {
            $product->update_meta_data($key, $value);
        }
        $id = (int) $product->save();
        $this->registry['products'][] = $id;

        return $id;
    }

    /* ------------------------------------------------------------------ */
    /* People                                                              */
    /* ------------------------------------------------------------------ */

    private function loadPeople(): void
    {
        $file = '/private/tmp/claude-501/-Users-emran-Local-Sites-wp-test2-app-public-wp-content-plugins/445f8e40-69f2-49d3-b83b-e02c8cdd2cd4/scratchpad/kit/people.json';
        $people = json_decode((string) file_get_contents($file), true) ?: [];
        foreach ($people as $p) {
            foreach (['from', 'to'] as $k) {
                if (!empty($p[$k])) {
                    $this->people_names[strtolower($p[$k])] = true;
                }
            }
        }
        global $wpdb;
        foreach ($wpdb->get_col("SELECT LOWER(display_name) FROM {$wpdb->users}") as $n) {
            $this->used_names[$n] = true;
        }
        foreach ($wpdb->get_col("SELECT LOWER(user_email) FROM {$wpdb->users}") as $e) {
            $this->used_emails[$e] = true;
        }
    }

    private function newPerson(?string $first = null, ?string $last = null): array
    {
        static $firsts = [
            'Olivia', 'Liam', 'Emma', 'Ethan', 'Ava', 'Mason', 'Isabella', 'Logan', 'Charlotte', 'Elijah',
            'Amelia', 'James', 'Harper', 'Benjamin', 'Evelyn', 'Henry', 'Abigail', 'Alexander', 'Emily', 'Sebastian',
            'Ella', 'Jack', 'Elizabeth', 'Owen', 'Camila', 'Matthew', 'Luna', 'Samuel', 'Mila', 'Joseph',
            'Aria', 'Wyatt', 'Penelope', 'John', 'Layla', 'Julian', 'Chloe', 'Levi', 'Victoria', 'Isaac',
            'Madison', 'Gabriel', 'Eleanor', 'Anthony', 'Grace', 'Dylan', 'Nora', 'Leo', 'Zoey', 'Lincoln',
            'Hannah', 'Asher', 'Hazel', 'Christopher', 'Lily', 'Josiah', 'Ellie', 'Andrew', 'Violet', 'Thomas',
            'Lillian', 'Joshua', 'Zoe', 'Ezra', 'Stella', 'Hudson', 'Aurora', 'Charles', 'Natalie', 'Caleb',
            'Emilia', 'Isaiah', 'Everly', 'Ryan', 'Leah', 'Nathan', 'Aubrey', 'Adrian', 'Willow', 'Christian',
            'Addison', 'Lucy', 'Colton', 'Audrey', 'Elias', 'Bella', 'Aaron', 'Nova', 'Eli', 'Brooklyn',
            'Landon', 'Paisley', 'Jonathan', 'Savannah', 'Nolan', 'Claire', 'Hunter', 'Skylar', 'Cameron', 'Isla',
            'Connor', 'Naomi', 'Santiago', 'Elena', 'Jeremiah', 'Caroline', 'Roman', 'Sadie', 'Easton', 'Gabriella',
            'Miles', 'Madelyn', 'Robert', 'Adeline', 'Nicholas', 'Autumn', 'Greyson', 'Aaliyah', 'Cooper', 'Kinsley',
            'Ian', 'Ruby', 'Carson', 'Alice', 'Axel', 'Ivy', 'Dominic', 'Quinn', 'Leonardo', 'Sarah',
            'Luca', 'Ayla', 'Austin', 'Anna', 'Adam', 'Valentina', 'Xavier', 'Gianna', 'Jose', 'Priya',
            'Everett', 'Aisha', 'Declan', 'Fatima', 'Evan', 'Mei', 'Parker', 'Ines', 'Wesley', 'Amara',
            'Kai', 'Leila', 'Jason', 'Zara', 'Emmett', 'Noor', 'Silas', 'Lucia', 'Camille', 'Micah',
            'Freya', 'Damian', 'Elif', 'Harrison', 'Hana', 'Ingrid', 'Rosa', 'Vincent', 'Kenji', 'Mateo',
            'Arjun', 'Rowan', 'Ravi', 'George', 'Omar', 'Luis', 'Tariq', 'Diego', 'Felix', 'Hugo',
            'Emil', 'Theo', 'Oscar', 'Jonas', 'Maria', 'Daniela', 'Sienna', 'Tessa', 'Julia', 'Marta',
        ];
        static $lasts = [
            'Anderson', 'Thompson', 'Martinez', 'Robinson', 'Clark', 'Rodriguez', 'Lewis', 'Walker', 'Hall', 'Allen',
            'Young', 'Hernandez', 'King', 'Wright', 'Lopez', 'Hill', 'Scott', 'Green', 'Adams', 'Baker',
            'Gonzalez', 'Nelson', 'Mitchell', 'Perez', 'Roberts', 'Phillips', 'Campbell', 'Evans', 'Edwards', 'Stewart',
            'Sanchez', 'Morris', 'Rogers', 'Cook', 'Bell', 'Rivera', 'Cooper', 'Richardson', 'Cox', 'Howard',
            'Torres', 'Peterson', 'Gray', 'Ramirez', 'James', 'Watson', 'Price', 'Sanders', 'Myers', 'Long',
            'Ross', 'Powell', 'Jenkins', 'Perry', 'Russell', 'Butler', 'Barnes', 'Fisher', 'Henderson', 'Coleman',
            'Simmons', 'Patterson', 'Reynolds', 'Hamilton', 'Graham', 'Wallace', 'Woods', 'Cole', 'West', 'Owens',
            'Marshall', 'Harrison', 'Gibson', 'Ruiz', 'Kennedy', 'Wells', 'Alvarez', 'Romero', 'Hoffman', 'Mendoza',
            'Castillo', 'Tran', 'Chen', 'Wang', 'Zhang', 'Liu', 'Singh', 'Gupta', 'Mehta', 'Suzuki',
            'Laurent', 'Ferrari', 'Bianchi', 'Romano', 'Weber', 'Nielsen', 'Johansson', 'Andersson', 'Hansen', 'Kelly',
            'Byrne', 'Okonkwo', 'Khalil', 'Aziz', 'Hussain', 'Santos', 'Oliveira', 'Pereira', 'Almeida', 'Navarro',
            'Herrera', 'Medina', 'Castro', 'Rojas', 'Fernandes', 'Petrov', 'Popescu', 'Horvat', 'Yilmaz', 'Demir',
            'Whitmore', 'Ashford', 'Caldwell', 'Holloway', 'Pemberton', 'Langley', 'Sinclair', 'Thornton', 'Prescott', 'Hartley',
        ];

        for ($try = 0; $try < 5000; $try++) {
            $f = $first ?? $firsts[mt_rand(0, count($firsts) - 1)];
            $l = $last ?? $lasts[mt_rand(0, count($lasts) - 1)];
            $name  = "$f $l";
            $key   = strtolower($name);
            $email = strtolower($f . '.' . $l) . '@example.com';
            if (isset($this->people_names[$key]) || isset($this->used_names[$key]) || isset($this->used_emails[$email])) {
                if (null !== $first) {
                    \WP_CLI::error("Featured name $name is taken");
                }
                continue;
            }
            $this->used_names[$key]    = true;
            $this->used_emails[$email] = true;

            static $cities = [
                ['Portland', 'OR', '97205'], ['Austin', 'TX', '78701'], ['Denver', 'CO', '80202'], ['Seattle', 'WA', '98101'],
                ['Chicago', 'IL', '60601'], ['Boston', 'MA', '02108'], ['Atlanta', 'GA', '30303'], ['Minneapolis', 'MN', '55401'],
                ['San Diego', 'CA', '92101'], ['Nashville', 'TN', '37203'], ['Philadelphia', 'PA', '19103'], ['Raleigh', 'NC', '27601'],
                ['Columbus', 'OH', '43215'], ['Salt Lake City', 'UT', '84101'], ['Madison', 'WI', '53703'], ['Sacramento', 'CA', '95814'],
                ['Pittsburgh', 'PA', '15222'], ['Richmond', 'VA', '23219'], ['Tucson', 'AZ', '85701'], ['Kansas City', 'MO', '64105'],
            ];
            static $streets = ['Maple', 'Oak', 'Cedar', 'Pine', 'Elm', 'Willow', 'Birch', 'Lakeview', 'Hillcrest', 'Park', 'River', 'Sunset', 'Highland', 'Meadow', 'Orchard', 'Harbor', 'Spring', 'Chestnut'];
            static $suffix = ['Street', 'Avenue', 'Road', 'Lane', 'Drive', 'Court'];
            $city = $cities[mt_rand(0, count($cities) - 1)];

            return [
                'first'   => $f,
                'last'    => $l,
                'name'    => $name,
                'email'   => $email,
                'login'   => strtolower($f . $l),
                'address' => [
                    'first_name' => $f,
                    'last_name'  => $l,
                    'company'    => '',
                    'address_1'  => mt_rand(12, 980) . ' ' . $streets[mt_rand(0, count($streets) - 1)] . ' ' . $suffix[mt_rand(0, count($suffix) - 1)],
                    'address_2'  => '',
                    'city'       => $city[0],
                    'state'      => $city[1],
                    'postcode'   => $city[2],
                    'country'    => 'US',
                    'email'      => $email,
                    'phone'      => sprintf('+1 202 555 01%02d', mt_rand(0, 99)),
                ],
            ];
        }

        \WP_CLI::error('Ran out of unique names');
    }

    /* ------------------------------------------------------------------ */
    /* Plan                                                                */
    /* ------------------------------------------------------------------ */

    private function local(string $ymd, int $h = 10, int $i = 0): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%s %02d:%02d:00', $ymd, $h, $i), $this->tz);
    }

    private function addPeriods(\DateTimeImmutable $d, string $period, int $interval, int $n): \DateTimeImmutable
    {
        if ($n <= 0) {
            return $d;
        }
        $units = $interval * $n;
        switch ($period) {
            case 'year':
                return $d->modify("+$units year");
            case 'week':
                return $d->modify('+' . (7 * $units) . ' days');
            case 'day':
                return $d->modify("+$units days");
            default:
                // Month arithmetic that keeps the day (clamped to month end).
                $y = (int) $d->format('Y');
                $m = (int) $d->format('n') + $units;
                $y += intdiv($m - 1, 12);
                $m = (($m - 1) % 12) + 1;
                $day = min((int) $d->format('j'), (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m), $this->tz))->format('t'));
                return $d->setDate($y, $m, $day);
        }
    }

    private function pickProduct(array $allowed = []): string
    {
        $pool = [];
        foreach ($this->catalog as $key => $row) {
            if ($row[6] <= 0) {
                continue;
            }
            if ($allowed && !in_array($key, $allowed, true)) {
                continue;
            }
            $pool[$key] = $row[6];
        }
        $total = array_sum($pool);
        $r = mt_rand(1, $total);
        foreach ($pool as $key => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $key;
            }
        }
        return array_key_first($pool);
    }

    private function monthly(string $key): float
    {
        [, , , $price, $period, $interval] = $this->catalog[$key];
        $per = ['day' => 30.4375, 'week' => 4.348214285714286, 'month' => 1.0, 'year' => 1 / 12][$period] ?? 0.0;
        return $price * $per / max(1, $interval);
    }

    /**
     * A start time whose next billing date is not within three days of now.
     */
    private function safeStart(\DateTimeImmutable $start, string $key): \DateTimeImmutable
    {
        [, , , , $period, $interval] = $this->catalog[$key];
        for ($guard = 0; $guard < 10; $guard++) {
            $n = 0;
            $next = $start;
            while ($next->getTimestamp() <= $this->now_ts) {
                $n++;
                $next = $this->addPeriods($start, $period, $interval, $n);
            }
            $gap = $next->getTimestamp() - $this->now_ts;
            if ($gap >= 3 * DAY_IN_SECONDS) {
                return $start;
            }
            $start = $start->modify('-' . (4 - intdiv($gap, DAY_IN_SECONDS)) . ' days');
        }
        return $start;
    }

    private function randomTime(\DateTimeImmutable $day): \DateTimeImmutable
    {
        return $day->setTime(mt_rand(8, 21), mt_rand(0, 59), mt_rand(0, 59));
    }

    private function randomDayIn(int $y, int $m, int $from = 1, ?int $to = null, bool $avoid_anniversary = true): \DateTimeImmutable
    {
        $last = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m), $this->tz))->format('t');
        $to   = min($to ?? $last, $last);
        for ($i = 0; $i < 50; $i++) {
            $d = mt_rand($from, $to);
            if ($avoid_anniversary && $d >= 24 && $d <= 27) {
                continue;
            }
            return $this->randomTime($this->local(sprintf('%04d-%02d-%02d', $y, $m, $d)));
        }
        return $this->randomTime($this->local(sprintf('%04d-%02d-%02d', $y, $m, $from)));
    }

    private function voluntaryReason(): string
    {
        $weights = [
            'too_expensive'      => 26,
            'not_using'          => 20,
            'found_alternative'  => 14,
            'temporary_pause'    => 11,
            'missing_features'   => 9,
            'delivery_frequency' => 6,
            'technical_issues'   => 5,
            'other'              => 4,
        ];
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $k => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $k;
            }
        }
        return 'other';
    }

    private function addSub(array $sub): void
    {
        $this->plan[] = $sub;
    }

    private function newCustomer(\DateTimeImmutable $first_purchase): int
    {
        $p = $this->newPerson();
        $p['registered'] = $first_purchase->modify('-' . mt_rand(0, 9) . ' days')->modify('-' . mt_rand(5, 300) . ' minutes');
        $this->customers[] = $p;
        return count($this->customers) - 1;
    }

    private function buildPlan(): void
    {
        $retention = [1 => 0.98, 2 => 0.955, 3 => 0.93, 4 => 0.905, 5 => 0.88, 6 => 0.86, 7 => 0.84, 8 => 0.815, 9 => 0.79, 10 => 0.765, 11 => 0.74, 12 => 0.72, 13 => 0.70, 14 => 0.68];

        // 0) Featured member with twelve months of continuous history.
        $clara = $this->newPerson('Clara', 'Whitmore');
        $clara['registered'] = $this->local('2025-10-14', 10, 12);
        $clara['featured'] = true;
        $this->customers[] = $clara;
        $ci = count($this->customers) - 1;
        $this->addSub(['cust' => $ci, 'key' => 'premium', 'start' => $this->local('2025-11-18', 10, 20), 'group' => 'featured']);
        $this->addSub(['cust' => $ci, 'key' => 'quarterly', 'start' => $this->local('2026-02-09', 19, 5), 'group' => 'featured']);
        $this->plan_oneoffs = [
            ['cust' => $ci, 'key' => 'mug', 'at' => $this->local('2026-01-21', 13, 40)],
            ['cust' => $ci, 'key' => 'flask', 'at' => $this->local('2026-04-03', 9, 15)],
            ['cust' => $ci, 'key' => 'workshop', 'at' => $this->local('2026-07-12', 16, 30)],
            ['cust' => $ci, 'key' => 'tin', 'at' => $this->local('2026-10-30', 11, 5)],
        ];

        // 1) Long-standing customers who joined before October 2025.
        for ($i = 0; $i < 30; $i++) {
            $m     = mt_rand(1, 9);
            $key   = $i < 9 ? 'annual' : $this->pickProduct(['plus', 'premium', 'tier_pro', 'starter', 'quarterly']);
            $start = $this->safeStart($this->randomDayIn(2025, $m), $key);
            $this->addSub(['cust' => $this->newCustomer($start), 'key' => $key, 'start' => $start, 'group' => 'base']);
        }
        // A few long-standing customers leave during 2026.
        foreach ([3 => '2026-03-12', 14 => '2026-06-19', 22 => '2026-09-08'] as $idx => $day) {
            $p = &$this->plan[2 + $idx];
            $p['end'] = $this->randomTime($this->local($day));
            $p['churn'] = 'voluntary';
            $p['reason'] = $this->voluntaryReason();
            unset($p);
        }

        // 2) Monthly cohorts, October 2025 to September 2026, decaying gradually.
        $cohort = 35;
        for ($c = 0; $c < 12; $c++) {
            $month = (new \DateTimeImmutable('2025-10-01 00:00:00', $this->tz))->modify("+$c months");
            $y = (int) $month->format('Y');
            $mo = (int) $month->format('n');
            $last_day = 'Sep' === $month->format('M') ? 23 : null;
            $members = [];
            for ($i = 0; $i < $cohort; $i++) {
                $key   = $this->pickProduct();
                $start = $this->safeStart($this->randomDayIn($y, $mo, 1, $last_day), $key);
                if ((int) $start->format('n') !== $mo) {
                    $start = $this->randomDayIn($y, $mo, 2, 20);
                }
                $this->addSub(['cust' => $this->newCustomer($start), 'key' => $key, 'start' => $start, 'group' => 'cohort-' . $month->format('Y-m')]);
                $members[] = count($this->plan) - 1;
            }
            // Cancellation schedule by calendar month of the cohort's life.
            shuffle($members);
            $lost_before = 0;
            for ($k = 1; $k <= 14; $k++) {
                $cal = $month->modify('+' . ($k - 1) . ' months');
                if ($cal->getTimestamp() > $this->now_ts) {
                    break;
                }
                $lost_by_k = (int) round($cohort * (1 - $retention[$k]));
                $new_losses = $lost_by_k - $lost_before;
                for ($j = 0; $j < $new_losses && !empty($members); $j++) {
                    $idx   = array_shift($members);
                    $start = $this->plan[$idx]['start'];
                    $cy = (int) $cal->format('Y');
                    $cm = (int) $cal->format('n');
                    $from = 1 === $k ? min(28, (int) $start->format('j') + 3) : 1;
                    $end = $this->randomDayIn($cy, $cm, $from, null, false);
                    if ($end <= $start->modify('+3 days')) {
                        $end = $start->modify('+' . mt_rand(4, 9) . ' days');
                    }
                    if ($end->getTimestamp() >= $this->now_ts - 3 * HOUR_IN_SECONDS) {
                        $members[] = $idx; // not yet due; keep active
                        continue;
                    }
                    $this->plan[$idx]['end'] = $end;
                    if (mt_rand(1, 100) <= 14) {
                        $this->plan[$idx]['churn']  = 'involuntary';
                        $this->plan[$idx]['reason'] = 'payment_failed';
                    } else {
                        $this->plan[$idx]['churn']  = 'voluntary';
                        $this->plan[$idx]['reason'] = $this->voluntaryReason();
                    }
                }
                $lost_before = $lost_by_k;
            }
        }

        // 3) Team accounts, early October.
        for ($i = 0; $i < 30; $i++) {
            $start = $this->randomDayIn(2026, 10, 2, 23);
            $this->addSub(['cust' => $this->newCustomer($start), 'key' => 'team', 'start' => $start, 'group' => 'team']);
        }

        // 4) Trial promotion on Sep 26: trials that did not convert.
        for ($i = 0; $i < 30; $i++) {
            $start = $this->local('2026-09-26', mt_rand(7, 22), mt_rand(0, 59));
            // Cancelled inside the trial and before the September month-end, so the
            // non-converting trials never count toward a month-end MRR point.
            $end   = $start->modify('+' . mt_rand(2, 4) . ' days')->setTime(mt_rand(8, 20), mt_rand(0, 59));
            $cap   = $this->local('2026-09-30', 21, 0);
            if ($end > $cap) {
                $end = $cap->modify('-' . mt_rand(0, 600) . ' minutes');
            }
            $this->addSub([
                'cust' => $this->newCustomer($start), 'key' => 'trialpass', 'start' => $start, 'trial' => true,
                'end' => $end, 'churn' => 'voluntary',
                'reason' => ['not_using', 'too_expensive', 'found_alternative', 'missing_features', 'not_using', 'other'][mt_rand(0, 5)],
                'group' => 'failed-trial',
            ]);
        }

        // 5) Last 30 days: steady, slightly accelerating sign-ups.
        $window_start = $this->local('2026-10-27', 0, 0);
        $window_end   = $this->now->setTime(0, 0, 0)->modify('-1 second'); // end of yesterday
        $days = (int) floor(($window_end->getTimestamp() - $window_start->getTimestamp()) / DAY_IN_SECONDS) + 1;
        $existing_customers = [];
        foreach ($this->plan as $idx => $p) {
            if (in_array($p['group'], ['base'], true) || 0 === strpos($p['group'], 'cohort-')) {
                if (empty($p['end'])) {
                    $existing_customers[] = $p['cust'];
                }
            }
        }
        shuffle($existing_customers);
        $trials_made = 0;
        $cross_sell  = 0;
        for ($d = 0; $d < $days; $d++) {
            $day  = $window_start->modify("+$d days");
            $t    = $days > 1 ? $d / ($days - 1) : 1;
            $rate = 6.2 + 3.0 * $t;
            $count = (int) floor($rate + (mt_rand(0, 99) / 100));
            for ($i = 0; $i < $count; $i++) {
                $start = $this->randomTime($day);
                $dom   = (int) $day->format('j');
                $is_trial_day = $day->format('Y-m-d') <= '2026-11-10';
                if ($is_trial_day && $trials_made < 66 && mt_rand(1, 100) <= 58) {
                    $trials_made++;
                    $this->addSub(['cust' => $this->newCustomer($start), 'key' => 'trialpass', 'start' => $start, 'trial' => true, 'group' => 'trial-converted']);
                    continue;
                }
                if ($day->format('Y-m-d') <= '2026-10-28') {
                    $key = $this->pickProduct(['quarterly', 'annual']);
                } else {
                    $key = $this->pickProduct();
                }
                if ($cross_sell < 40 && !empty($existing_customers) && mt_rand(1, 100) <= 22) {
                    $cust = array_shift($existing_customers);
                    $cross_sell++;
                } else {
                    $cust = $this->newCustomer($start);
                }
                $this->addSub(['cust' => $cust, 'key' => $key, 'start' => $start, 'group' => 'recent']);
            }
        }
        // Top up trials to 66 if the random draw fell short.
        while ($trials_made < 66) {
            $day   = $window_start->modify('+' . mt_rand(0, 14) . ' days');
            $start = $this->randomTime($day);
            if ($start->format('Y-m-d') > '2026-11-10') {
                continue;
            }
            $trials_made++;
            $this->addSub(['cust' => $this->newCustomer($start), 'key' => 'trialpass', 'start' => $start, 'trial' => true, 'group' => 'trial-converted']);
        }
        // A few team accounts in November.
        for ($i = 0; $i < 6; $i++) {
            $start = $this->randomDayIn(2026, 11, 2, 20);
            $this->addSub(['cust' => $this->newCustomer($start), 'key' => 'team', 'start' => $start, 'group' => 'team-nov']);
        }

        // Give the voluntary cancellations a clear reason ranking: exact shares
        // inside Dec 2025 - Sep 2026, weighted draws elsewhere.
        $win_a = $this->local('2025-12-01', 0, 0);
        $win_b = $this->local('2026-09-25', 23, 59);
        $inside = [];
        foreach ($this->plan as $idx => $p) {
            if ('voluntary' === ($p['churn'] ?? '') && 'failed-trial' !== $p['group'] && $p['end'] >= $win_a && $p['end'] <= $win_b) {
                $inside[] = $idx;
            }
        }
        $shares = ['too_expensive' => 0.30, 'not_using' => 0.22, 'found_alternative' => 0.16, 'temporary_pause' => 0.12,
                   'missing_features' => 0.08, 'delivery_frequency' => 0.06, 'technical_issues' => 0.04, 'other' => 0.02];
        $bag = [];
        foreach ($shares as $reason => $share) {
            $bag = array_merge($bag, array_fill(0, (int) round(count($inside) * $share), $reason));
        }
        while (count($bag) < count($inside)) {
            $bag[] = 'too_expensive';
        }
        shuffle($bag);
        foreach ($inside as $n => $idx) {
            $this->plan[$idx]['reason'] = $bag[$n];
        }

        // Resolve billing schedule for every planned subscription.
        foreach ($this->plan as $idx => &$p) {
            [, , , $price, $period, $interval] = $this->catalog[$p['key']];
            $p['trial']   = !empty($p['trial']);
            $p['monthly'] = $this->monthly($p['key']);
            $p['status']  = empty($p['end']) ? 'arraysubs-active' : 'arraysubs-cancelled';
            $limit_ts     = empty($p['end']) ? $this->now_ts : $p['end']->getTimestamp();
            $payments     = [];
            if ($p['trial']) {
                $p['trial_end'] = $p['start']->modify('+14 days');
                $first = $p['trial_end'];
            } else {
                $first = $p['start'];
            }
            $n = 0;
            $at = $first;
            while ($at->getTimestamp() <= $limit_ts && $at->getTimestamp() <= $this->now_ts) {
                $payments[] = $at;
                $n++;
                $at = $this->addPeriods($first, $period, $interval, $n);
            }
            $p['payments'] = $payments;
            $p['next']     = 'arraysubs-active' === $p['status'] ? $at : null;
            if (null !== $p['next'] && $p['next']->getTimestamp() - $this->now_ts < 2 * DAY_IN_SECONDS) {
                \WP_CLI::warning("Subscription plan $idx ({$p['group']}) renews too soon: " . $p['next']->format('Y-m-d H:i'));
            }
        }
        unset($p);
    }

    private array $plan_oneoffs = [];

    /* ------------------------------------------------------------------ */
    /* Projection report (no writes)                                       */
    /* ------------------------------------------------------------------ */

    private function report(): void
    {
        $months = \ArraySubs\Features\MainAdmin\Support\Overview\Range::monthEnds();
        $existing = \ArraySubs\Features\MainAdmin\Support\Overview\Builder::get('30d', true);
        $E = array_column($existing['mrr_history']['points'], 'value');
        $S = array_fill(0, 12, 0.0);
        foreach ($this->plan as $p) {
            foreach ($months as $i => $m) {
                $b = strtotime($m['end'] . ' UTC');
                if ($p['start']->getTimestamp() > $b) {
                    continue;
                }
                if (!empty($p['end']) && $p['end']->getTimestamp() <= $b) {
                    continue;
                }
                $S[$i] += $p['monthly'];
            }
        }
        $line = [];
        foreach ($months as $i => $m) {
            $line[] = sprintf('%s %s+%s=%s', $m['label'], round($E[$i]), round($S[$i]), round($E[$i] + $S[$i]));
        }
        \WP_CLI::log('MRR momentum: ' . implode(' | ', $line));

        $r = $existing['range'];
        $rs = strtotime($r['start'] . ' UTC');
        $re = strtotime($r['end'] . ' UTC');
        $len = $re - $rs;
        $ps = $rs - $len;
        $new = $newp = $can = $canp = 0;
        $rev = $revp = 0.0;
        $groups = [];
        foreach ($this->plan as $p) {
            $groups[$p['group']] = ($groups[$p['group']] ?? 0) + 1;
            $st = $p['start']->getTimestamp();
            if ($st >= $rs && $st <= $re) $new++;
            elseif ($st >= $ps && $st < $rs) $newp++;
            if (!empty($p['end'])) {
                $et = $p['end']->getTimestamp();
                if ($et >= $rs && $et <= $re) $can++;
                elseif ($et >= $ps && $et < $rs) $canp++;
            }
            foreach ($p['payments'] as $pay) {
                $t = $pay->getTimestamp();
                $amt = $this->catalog[$p['key']][3];
                if ($t >= $rs && $t <= $re) $rev += $amt;
                elseif ($t >= $ps && $t < $rs) $revp += $amt;
            }
            if ($p['trial']) {
                // zero-value trial sign-up order
            }
        }
        $mv = $existing['movement'];
        \WP_CLI::log(sprintf('Home 30d: new %d+%d=%d vs prev %d+%d=%d | cancelled %d+%d=%d vs prev %d+%d | net %d',
            $mv['new'], $new, $mv['new'] + $new, $mv['new_prev'], $newp, $mv['new_prev'] + $newp,
            $mv['cancelled'], $can, $mv['cancelled'] + $can, $mv['cancelled_prev'], $canp,
            ($mv['new'] + $new) - ($mv['cancelled'] + $can)));
        \WP_CLI::log(sprintf('Collected: %.0f+%.0f=%.0f vs prev %.0f+%.0f=%.0f',
            $existing['revenue']['collected'], $rev, $existing['revenue']['collected'] + $rev,
            $existing['revenue']['previous'], $revp, $existing['revenue']['previous'] + $revp));
        $active = 0; $cancelled = 0; $orders = 0; $mrr_now = 0.0;
        foreach ($this->plan as $p) {
            if ('arraysubs-active' === $p['status']) { $active++; $mrr_now += $p['monthly']; } else { $cancelled++; }
            $orders += count($p['payments']) + ($p['trial'] ? 1 : 0);
        }
        \WP_CLI::log(sprintf('Plan: %d subs (%d active, %d cancelled), %d customers, ~%d orders, seeded MRR %.0f; store active %d+%d, cancelled %d+%d',
            count($this->plan), $active, $cancelled, count($this->customers), $orders + count($this->plan_oneoffs), $mrr_now,
            $existing['totals']['active'], $active, $existing['status_mix'][4]['count'] ?? 0, $cancelled));
        \WP_CLI::log('Groups: ' . json_encode($groups));
        // Voluntary reasons between Dec 1 2025 and Sep 25 2026 (local).
        $a = $this->local('2025-12-01', 0, 0)->getTimestamp();
        $b = $this->local('2026-09-25', 23, 59)->getTimestamp();
        $reasons = [];
        foreach ($this->plan as $p) {
            if (!empty($p['end']) && 'voluntary' === ($p['churn'] ?? '') && $p['end']->getTimestamp() >= $a && $p['end']->getTimestamp() <= $b) {
                $reasons[$p['reason']] = ($reasons[$p['reason']] ?? 0) + 1;
            }
        }
        arsort($reasons);
        \WP_CLI::log('Seeded voluntary reasons Dec 1-Sep 25: ' . json_encode($reasons) . ' total ' . array_sum($reasons));
        // Cohort projection for seeded subs only.
        $rows = [];
        foreach ($this->plan as $p) {
            $cm = $p['start']->format('Y-m');
            if ($cm < '2025-10' || $cm > '2026-09') continue;
            $rows[$cm]['n'] = ($rows[$cm]['n'] ?? 0) + 1;
            for ($k = 1; $k <= 12; $k++) {
                $snap = (new \DateTimeImmutable($cm . '-01 00:00:00', $this->tz))->modify('+' . ($k - 1) . ' months')->modify('last day of this month')->setTime(23, 59, 59);
                if ($snap->getTimestamp() > $this->local('2026-09-25', 23, 59)->getTimestamp()) break;
                $alive = empty($p['end']) || $p['end'] > $snap;
                $rows[$cm]['k'][$k] = ($rows[$cm]['k'][$k] ?? 0) + ($alive ? 1 : 0);
            }
        }
        ksort($rows);
        foreach ($rows as $cm => $row) {
            $cells = [];
            foreach (($row['k'] ?? []) as $k => $alive) $cells[] = sprintf('%5.1f', 100 * $alive / $row['n']);
            \WP_CLI::log("  cohort $cm n={$row['n']} " . implode(' ', $cells));
        }
    }

    /* ------------------------------------------------------------------ */
    /* Writes                                                              */
    /* ------------------------------------------------------------------ */

    private function suppressSideEffects(): void
    {
        add_filter('pre_wp_mail', static fn () => true, PHP_INT_MAX);
        add_filter('arraysubs_is_automatic_payment', '__return_false', PHP_INT_MAX);
        add_filter('arraysubs_should_process_automatic_payment', '__return_false', PHP_INT_MAX);
        add_filter('wp_hash_password_options', static fn () => ['cost' => 4], PHP_INT_MAX);
        add_filter('pre_http_request', static fn () => new \WP_Error('media_seed', 'blocked'), PHP_INT_MAX);
        foreach ([
            'wp_after_insert_post', 'transition_post_status', 'save_post', 'save_post_arraysubs_data', 'wp_insert_post',
            'added_post_meta', 'updated_post_meta', 'arraysubs_data_created', 'arraysubs_data_status_changed',
            'user_register', 'profile_update', 'woocommerce_created_customer', 'add_user_role', 'set_user_role',
            'woocommerce_new_order', 'woocommerce_update_order', 'woocommerce_before_order_object_save',
            'woocommerce_after_order_object_save', 'woocommerce_order_status_changed', 'woocommerce_order_status_completed',
            'woocommerce_payment_complete', 'woocommerce_new_order_item', 'woocommerce_order_object_updated_props',
            'woocommerce_checkout_order_processed', 'woocommerce_order_status_pending_to_completed',
            'woocommerce_create_order', 'woocommerce_new_product', 'woocommerce_update_product',
        ] as $hook) {
            remove_all_actions($hook);
        }
        remove_all_filters('woocommerce_create_order');
    }

    private function cachedProductMeta(string $key): array
    {
        if (isset($this->cached_meta[$key])) {
            return $this->cached_meta[$key];
        }
        global $wpdb;
        [$pid, $vid, $name] = $this->catalog[$key];
        $sid = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} a ON a.post_id = p.ID AND a.meta_key = '_product_id' AND a.meta_value = %s
             JOIN {$wpdb->postmeta} b ON b.post_id = p.ID AND b.meta_key = '_variation_id' AND b.meta_value = %s
             JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = '_cached_product_data'
             WHERE p.post_type = 'arraysubs_data' ORDER BY p.ID DESC LIMIT 1",
            (string) $pid,
            (string) $vid
        ));
        $product = wc_get_product($vid ?: $pid);
        $meta = [
            '_cached_product_name' => $name,
            '_cached_product_sku'  => $product ? (string) $product->get_sku() : '',
            '_cached_product_data' => $sid ? (string) get_post_meta($sid, '_cached_product_data', true) : wp_json_encode([
                'id' => $vid ?: $pid, 'parent_id' => $vid ? $pid : 0, 'variation_id' => $vid, 'name' => $name,
                'sku' => $product ? $product->get_sku() : '', 'type' => $vid ? 'variation' : 'simple',
                'price' => (string) $this->catalog[$key][3], 'regular_price' => (string) $this->catalog[$key][3],
            ]),
        ];
        return $this->cached_meta[$key] = $meta;
    }

    private function utc(\DateTimeImmutable $d): string
    {
        return $d->setTimezone($this->utc)->format('Y-m-d H:i:s');
    }

    private function write(): void
    {
        global $wpdb;
        $t0 = microtime(true);
        wp_defer_term_counting(true);
        wp_suspend_cache_invalidation(false);

        // Users.
        foreach ($this->customers as $ci => &$c) {
            $uid = wp_insert_user([
                'user_login'      => $c['login'] . (username_exists($c['login']) ? mt_rand(10, 99) : ''),
                'user_pass'       => wp_generate_password(20, false),
                'user_email'      => $c['email'],
                'display_name'    => $c['name'],
                'first_name'      => $c['first'],
                'last_name'       => $c['last'],
                'nickname'        => $c['first'],
                'role'            => 'customer',
                'user_registered' => $this->utc($c['registered']),
            ]);
            if (is_wp_error($uid)) {
                \WP_CLI::error('User insert failed: ' . $uid->get_error_message());
            }
            $c['id'] = (int) $uid;
            $this->registry['users'][] = $c['id'];
            update_user_meta($c['id'], self::USER_MARK_KEY, self::MARK);
            update_user_meta($c['id'], 'paying_customer', 1);
            foreach (['billing', 'shipping'] as $type) {
                foreach ($c['address'] as $field => $value) {
                    if ('shipping' === $type && in_array($field, ['email'], true)) {
                        continue;
                    }
                    update_user_meta($c['id'], "{$type}_{$field}", $value);
                }
            }
        }
        unset($c);
        update_option(self::REGISTRY, $this->registry, false);
        \WP_CLI::log(sprintf('users: %d (%.1fs)', count($this->customers), microtime(true) - $t0));

        // Subscriptions, orders, ledger.
        $done = 0;
        foreach ($this->plan as $idx => $p) {
            $this->writeSubscription($p);
            if (0 === (++$done % 50)) {
                update_option(self::REGISTRY, $this->registry, false);
                \WP_CLI::log(sprintf('subscriptions: %d/%d, orders %d (%.1fs)', $done, count($this->plan), count($this->registry['orders']), microtime(true) - $t0));
            }
        }

        // One-off purchases for the featured member.
        foreach ($this->plan_oneoffs as $o) {
            $c = $this->customers[$o['cust']];
            $this->createOrder($c, $o['key'], $o['at'], null, 'Other');
        }

        // Retention saves in the last 30 days (customers who took an offer instead of cancelling).
        $this->writeSaves();

        // Store credit for the featured member.
        $featured = $this->customers[0];
        if (class_exists('\\ArraySubsPro\\Features\\StoreCredit\\Services\\CreditManager')) {
            $log_id = \ArraySubsPro\Features\StoreCredit\Services\CreditManager::adminAdjustCredit($featured['id'], 25.0, 'Loyalty reward');
            if ($log_id) {
                update_post_meta((int) $log_id, self::MARK_KEY, self::MARK);
            }
        }

        update_option(self::REGISTRY, $this->registry, false);
        $this->flushCaches();
        \WP_CLI::success(sprintf('Seeded %d users, %d subscriptions, %d orders, %d ledger events, %d logs in %.1fs. Featured member user %d.',
            count($this->registry['users']), count($this->registry['subscriptions']), count($this->registry['orders']),
            count($this->registry['events']), count($this->registry['logs']), microtime(true) - $t0, $featured['id']));
    }

    private function writeSubscription(array $p): void
    {
        global $wpdb;
        $c = $this->customers[$p['cust']];
        [$pid, $vid, $name, $price, $period, $interval] = $this->catalog[$p['key']];
        $status = $p['status'];
        $start_gmt = $this->utc($p['start']);
        $addr = $c['address'];
        $ship = $addr;
        unset($ship['email'], $ship['phone']);

        $meta = array_merge([
            self::MARK_KEY                         => self::MARK,
            '_arraysubs_media_seed_group'          => $p['group'],
            '_billing_period'                      => $period,
            '_billing_interval'                    => $interval,
            '_subscription_length'                 => 0,
            '_start_date'                          => $start_gmt,
            '_next_payment_date'                   => $p['next'] ? $this->utc($p['next']) : '',
            '_customer_id'                         => $c['id'],
            '_customer_email'                      => $c['email'],
            '_payment_method'                      => 'bacs',
            '_payment_method_title'                => 'Direct bank transfer',
            '_payment_gateway'                     => 'bacs',
            '_subscription_price'                  => $price,
            '_recurring_amount'                    => $price,
            '_signup_fee'                          => 0,
            '_currency'                            => 'USD',
            '_trial_length'                        => $p['trial'] ? 14 : 0,
            '_trial_period'                        => $p['trial'] ? 'day' : '',
            '_product_id'                          => $pid,
            '_variation_id'                        => $vid,
            '_quantity'                            => 1,
            '_enable_different_renewal_price'      => 'no',
            '_different_renewal_price'             => 0,
            '_different_renewal_price_after'       => 1,
            '_completed_payments'                  => count($p['payments']),
            '_billing_address'                     => wp_json_encode($addr),
            '_shipping_address'                    => wp_json_encode($ship),
            '_invoice_email'                       => $c['email'],
        ], $this->cachedProductMeta($p['key']));

        if (!empty($p['step'])) {
            $meta['_enable_different_renewal_price'] = 'yes';
            $meta['_different_renewal_price']        = $p['step'][1];
            $meta['_different_renewal_price_after']  = $p['step'][0];
        }
        if ($p['trial']) {
            $meta['_trial_end_date'] = $this->utc($p['trial_end']);
        }
        if (!empty($p['payments'])) {
            $meta['_last_payment_date'] = $this->utc(end($p['payments']));
        }
        if ('arraysubs-on-hold' === $status) {
            $meta['_on_hold_date'] = $this->utc($p['on_hold_at']);
            $meta['_payment_failure_count'] = 1;
            $meta['_last_payment_failure_date'] = $this->utc($p['failed_renewal']);
        }
        if ('arraysubs-cancelled' === $status) {
            $age = (int) floor(($p['end']->getTimestamp() - $p['start']->getTimestamp()) / DAY_IN_SECONDS);
            $meta['_cancelled_date']             = $this->utc($p['end']);
            $meta['_end_date']                   = $this->utc($p['end']);
            $meta['_cancelled_by']               = 'involuntary' === $p['churn'] ? 'system' : 'customer';
            $meta['_cancellation_type']          = 'immediate';
            $meta['_cancellation_reason']        = $p['reason'];
            $meta['_subscription_age_at_cancel'] = $age;
            $meta['_payments_at_cancel']         = count($p['payments']);
        }

        $sid = wp_insert_post([
            'post_type'     => 'arraysubs_data',
            'post_status'   => $status,
            'post_title'    => sprintf('Subscription for %s - %s', $name, $c['name']),
            'post_author'   => $c['id'],
            'post_date'     => get_date_from_gmt($start_gmt),
            'post_date_gmt' => $start_gmt,
            'meta_input'    => $meta,
        ], true);
        if (is_wp_error($sid)) {
            \WP_CLI::error('Subscription insert failed: ' . $sid->get_error_message());
        }
        $sid = (int) $sid;
        $this->registry['subscriptions'][] = $sid;

        // Orders: sign-up order (zero for trials) then one per paid cycle.
        $order_ids = [];
        if ($p['trial']) {
            $order_ids[] = $parent = $this->createOrder($c, $p['key'], $p['start'], $sid, 'Subs Trial', 0, true, 0.0);
            foreach ($p['payments'] as $n => $pay) {
                $order_ids[] = $this->createOrder($c, $p['key'], $pay, $sid, 'Subs Renew', $n + 1);
            }
        } else {
            foreach ($p['payments'] as $n => $pay) {
                $amount = (!empty($p['step']) && $n >= $p['step'][0]) ? (float) $p['step'][1] : null;
                $order_ids[] = $this->createOrder($c, $p['key'], $pay, $sid, 0 === $n ? 'Subs Purchase' : 'Subs Renew', $n, 0 === $n, $amount);
            }
            $parent = $order_ids[0] ?? 0;
        }
        if (!empty($p['failed_renewal'])) {
            $order_ids[] = $this->createOrder($c, $p['key'], $p['failed_renewal'], $sid, 'Subs Renew', 1, false, null, 'failed');
        }
        update_post_meta($sid, '_parent_order_id', $parent);
        update_post_meta($sid, '_order_ids', wp_json_encode($order_ids));
        if ($parent) {
            update_post_meta($sid, '_arraysubs_initial_payment_accounted_order', $parent);
            update_post_meta($sid, '_arraysubs_initial_payment_processed_order', $parent);
        }

        // Retention ledger: activation (and conversion / cancellation).
        $mrr = $p['monthly'];
        $act_status = $p['trial'] ? 'arraysubs-trial' : 'arraysubs-active';
        $this->ledger($sid, $c, $p, 'activated', $p['start'], 'arraysubs-pending', $act_status, $mrr, 0.0, $mrr);
        if ($p['trial'] && !empty($p['payments'])) {
            $this->ledger($sid, $c, $p, 'trial_converted', $p['trial_end'], 'arraysubs-trial', 'arraysubs-active', $mrr, $mrr, $mrr);
        }
        if ('arraysubs-on-hold' === $status) {
            $this->ledger($sid, $c, $p, 'status_changed', $p['on_hold_at'], 'arraysubs-active', 'arraysubs-on-hold', $mrr, $mrr, 0.0);
        }
        if ('arraysubs-cancelled' === $status) {
            $from = ($p['trial'] && empty($p['payments'])) ? 'arraysubs-trial' : 'arraysubs-active';
            $this->ledger($sid, $c, $p, 'cancelled', $p['end'], $from, 'arraysubs-cancelled', $mrr, $mrr, 0.0, [
                'churn_type'          => $p['churn'],
                'cancellation_reason' => $p['reason'],
                'cancellation_actor'  => 'involuntary' === $p['churn'] ? 'system' : 'customer',
                'cancellation_source' => 'involuntary' === $p['churn'] ? 'gateway' : '',
            ]);
            $this->retentionLog($sid, $c, $p, 'cancelled', $p['end'], $p['reason'], 'involuntary' === $p['churn'] ? 'system' : 'customer');
        }
    }

    private function createOrder(array $c, string $key, \DateTimeImmutable $at, ?int $sid, string $type, int $cycle = 0, bool $parent = false, ?float $amount = null, string $status = 'completed'): int
    {
        [$pid, $vid, $name, $price, , , , $attr] = $this->catalog[$key];
        $amount = $amount ?? $price;
        $ts = $at->getTimestamp();

        $order = new \WC_Order();
        $order->set_created_via($parent ? 'checkout' : ('Other' === $type ? 'checkout' : ''));
        $order->set_customer_id($c['id']);
        $order->set_currency('USD');
        $order->set_prices_include_tax(false);
        $order->set_payment_method('bacs');
        $order->set_payment_method_title('Direct bank transfer');
        $order->set_address($c['address'], 'billing');
        $ship = $c['address'];
        unset($ship['email']);
        $order->set_address($ship, 'shipping');

        $item = new \WC_Order_Item_Product();
        $item->set_props([
            'name'         => $name,
            'product_id'   => $pid,
            'variation_id' => $vid,
            'quantity'     => 1,
            'subtotal'     => $amount,
            'total'        => $amount,
            'subtotal_tax' => 0,
            'total_tax'    => 0,
            'tax_class'    => '',
        ]);
        if ($attr) {
            $item->add_meta_data($attr[0], $attr[1], true);
        }
        if ($sid) {
            $item->add_meta_data('_subscription_id', $sid, true);
        }
        $order->add_item($item);

        $order->set_discount_total(0);
        $order->set_discount_tax(0);
        $order->set_shipping_total(0);
        $order->set_shipping_tax(0);
        $order->set_cart_tax(0);
        $order->set_total($amount);
        // Status set while the object is not marked as read, so no status transition
        // (and no order note or completion hooks) is recorded for this historical order.
        $order->set_object_read(false);
        $order->set_status($status);
        $order->set_object_read(true);
        $order->set_date_created($ts);
        $order->set_date_modified($ts);
        if ('completed' === $status) {
            $order->set_date_paid($ts);
            $order->set_date_completed($ts + mt_rand(60, 3600));
        }
        $order->update_meta_data(self::MARK_KEY, self::MARK);
        $order->update_meta_data('_arraysubs_computed_type', $type);
        if ($sid) {
            $order->update_meta_data('_arraysubs_has_subscription_product', 'yes');
            $order->update_meta_data('_subscription_payment_processed', 'yes');
            if ($parent) {
                $order->update_meta_data('_subscription_ids', [$sid]);
            } else {
                $order->update_meta_data('_is_renewal_order', 'yes');
                $order->update_meta_data('_subscription_id', $sid);
                $order->update_meta_data('_subscription_renewal', $sid);
                $order->update_meta_data('_renewal_cycle_number', $cycle);
                $order->update_meta_data('_renewal_scheduled_date', gmdate('Y-m-d H:i:s', $ts));
            }
        }
        $id = (int) $order->save();
        if ($id <= 0) {
            \WP_CLI::error('Order save failed');
        }
        $this->registry['orders'][] = $id;
        \Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler::import($id);

        return $id;
    }

    private function ledger(int $sid, array $c, array $p, string $type, \DateTimeImmutable $at, string $old, string $new, float $norm, float $before, float $after, array $extra = []): void
    {
        global $wpdb;
        [$pid, $vid, , $price, $period, $interval] = $this->catalog[$p['key']];
        $gmt = $this->utc($at);
        if ('activated' === $type) {
            $uid = hash('sha256', 'activation|' . $sid . '|' . $gmt);
        } elseif ('cancelled' === $type) {
            $uid = hash('sha256', 'terminal|' . $sid . '|' . $gmt . '|' . $new);
        } else {
            $uid = hash('sha256', implode('|', [self::MARK, $sid, $type, $gmt, $old, $new]));
        }
        $row = [
            'event_uid'                  => $uid,
            'episode_uid'                => 'cancelled' === $type ? hash('sha256', self::MARK . '|terminal-episode|' . $sid . '|' . $gmt) : '',
            'subscription_id'            => $sid,
            'event_type'                 => $type,
            'event_origin'               => 'live',
            'old_status'                 => $old,
            'new_status'                 => $new,
            'customer_id'                => $c['id'],
            'customer_key'               => 'u:' . $c['id'],
            'previous_customer_key'      => '',
            'product_id'                 => $pid,
            'variation_id'               => $vid,
            'effective_product_id'       => $vid ?: $pid,
            'quantity'                   => 1,
            'recurring_amount'           => $price,
            'effective_recurring_amount' => $price,
            'billing_period'             => $period,
            'billing_interval'           => $interval,
            'normalized_mrr'             => round($norm, 4),
            'mrr_before'                 => round($before, 4),
            'mrr_after'                  => round($after, 4),
            'currency'                   => 'USD',
            'churn_type'                 => $extra['churn_type'] ?? '',
            'cancellation_reason'        => $extra['cancellation_reason'] ?? '',
            'cancellation_actor'         => $extra['cancellation_actor'] ?? '',
            'cancellation_source'        => $extra['cancellation_source'] ?? '',
            'occurred_at_gmt'            => $gmt,
            'recorded_at_gmt'            => $gmt,
            'context'                    => wp_json_encode(['media_seed' => self::MARK]),
            'is_backfilled'              => 0,
        ];
        $ok = $wpdb->insert($wpdb->prefix . 'arraysubs_retention_events', $row);
        if (false === $ok) {
            \WP_CLI::error('Ledger insert failed: ' . $wpdb->last_error);
        }
        $this->registry['events'][] = (int) $wpdb->insert_id;
    }

    private function retentionLog(int $sid, array $c, array $p, string $event, \DateTimeImmutable $at, string $reason, string $by, string $offer = '', string $offer_data = ''): void
    {
        global $wpdb;
        [$pid, $vid, $name, $price] = $this->catalog[$p['key']];
        $age = max(0, (int) floor(($at->getTimestamp() - $p['start']->getTimestamp()) / DAY_IN_SECONDS));
        $paid = 0;
        foreach ($p['payments'] as $pay) {
            if ($pay <= $at) {
                $paid++;
            }
        }
        $ok = $wpdb->insert($wpdb->prefix . 'arraysubs_retention_logs', [
            'subscription_id'        => $sid,
            'customer_id'            => $c['id'],
            'customer_name_snapshot' => $c['name'],
            'event_type'             => $event,
            'reason'                 => $reason,
            'reason_details'         => '',
            'offer_type'             => $offer,
            'offer_data'             => $offer_data,
            'initiated_by'           => $by,
            'effective_product_id'   => $vid ?: $pid,
            'product_name_snapshot'  => $name,
            'subscription_age'       => $age,
            'payments_count'         => $paid,
            'recurring_amount'       => $price,
            'currency'               => 'USD',
            'created_at'             => $this->utc($at),
        ]);
        if (false === $ok) {
            \WP_CLI::error('Retention log insert failed: ' . $wpdb->last_error);
        }
        $this->registry['logs'][] = (int) $wpdb->insert_id;
    }

    private function writeSaves(): void
    {
        // Seven active cohort customers who opened the cancel flow in the last 30 days and took an offer.
        $offers = ['discount', 'pause', 'skip', 'discount', 'downgrade', 'discount', 'pause'];
        $made = 0;
        $sids = $this->registry['subscriptions'];
        foreach ($this->plan as $i => $p) {
            if ($made >= count($offers)) {
                break;
            }
            if ('arraysubs-active' !== $p['status'] || 0 !== strpos($p['group'], 'cohort-')) {
                continue;
            }
            if (0 !== ($i % 23)) {
                continue;
            }
            $sid = (int) ($sids[$i] ?? 0);
            if (!$sid) {
                continue;
            }
            $at = $this->randomTime($this->local('2026-10-29')->modify('+' . (3 * $made + mt_rand(0, 2)) . ' days'));
            if ($at->getTimestamp() > $this->now_ts - DAY_IN_SECONDS) {
                $at = $this->now->modify('-2 days');
            }
            $c = $this->customers[$p['cust']];
            $offer = $offers[$made];
            $reason = ['too_expensive', 'temporary_pause', 'not_using', 'too_expensive', 'too_expensive', 'found_alternative', 'temporary_pause'][$made];
            $this->retentionLog($sid, $c, $p, 'offer_shown', $at, $reason, 'customer', $offer, wp_json_encode([$offer]));
            $this->retentionLog($sid, $c, $p, 'offer_accepted', $at->modify('+2 minutes'), $reason, 'customer', $offer);
            update_post_meta($sid, '_retention_offer_shown', '1');
            update_post_meta($sid, '_retention_offers_shown', [$offer]);
            update_post_meta($sid, '_retention_offers_shown_date', $this->utc($at));
            $made++;
        }
        \WP_CLI::log("retention saves: $made");
    }

    private function flushCaches(): void
    {
        global $wpdb;
        if (class_exists('\\ArraySubs\\Features\\MainAdmin\\Support\\Overview\\Builder')) {
            \ArraySubs\Features\MainAdmin\Support\Overview\Builder::flush();
        }
        if (class_exists('\\Automattic\\WooCommerce\\Admin\\API\\Reports\\Cache')) {
            \Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
        }
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%arraysubs%' OR option_name LIKE '\\_transient\\_timeout\\_%arraysubs%'");
        wp_cache_flush();
    }

    /* ------------------------------------------------------------------ */
    /* Append: extra November team accounts and one loyalty-priced member  */
    /* ------------------------------------------------------------------ */

    private function append(): void
    {
        $this->registry = get_option(self::REGISTRY) ?: $this->registry;
        mt_srand(20261126);
        $this->loadPeople();
        $this->buildCatalog(true);
        $this->plan = [];
        $this->customers = [];
        $spec = getenv('MEDIA_SEED_APPEND') ?: 'team:6,loyalty:1';
        foreach (explode(',', $spec) as $part) {
            [$what, $n] = array_pad(explode(':', $part), 2, 1);
            for ($i = 0; $i < (int) $n; $i++) {
                if ('team' === $what) {
                    $start = $this->randomDayIn(2026, 11, 3, 20);
                    $this->addSub(['cust' => $this->newCustomer($start), 'key' => 'team', 'start' => $start, 'group' => 'team-nov']);
                } elseif ('onhold' === $what) {
                    $days = [18, 19, 21, 22, 20, 23];
                    $keys = ['starter', 'premium', 'plus', 'tier_pro', 'plus', 'premium'];
                    $start = $this->randomTime($this->local(sprintf('2026-10-%02d', $days[$i % 6])));
                    $this->addSub(['cust' => $this->newCustomer($start), 'key' => $keys[$i % 6], 'start' => $start, 'group' => 'onhold', 'onhold' => true]);
                } elseif ('loyalty' === $what) {
                    $start = $this->randomDayIn(2026, 3, 2, 20);
                    $this->addSub(['cust' => $this->newCustomer($start), 'key' => 'plus', 'start' => $start, 'group' => 'loyalty', 'step' => [6, 38.0]]);
                }
            }
        }
        foreach ($this->plan as &$p) {
            [, , , $price, $period, $interval] = $this->catalog[$p['key']];
            $p['trial'] = false;
            $p['monthly'] = $this->monthly($p['key']);
            $p['status'] = 'arraysubs-active';
            if (!empty($p['onhold'])) {
                // First renewal failed a few days ago; the subscription is on hold.
                $failed = $this->addPeriods($p['start'], $period, $interval, 1);
                $p['status'] = 'arraysubs-on-hold';
                $p['payments'] = [$p['start']];
                $p['next'] = $failed;
                $p['failed_renewal'] = $failed;
                $p['on_hold_at'] = $failed->modify('+' . mt_rand(20, 30) . ' hours');
                continue;
            }
            $payments = [];
            $n = 0;
            $at = $p['start'];
            while ($at->getTimestamp() <= $this->now_ts) {
                $payments[] = $at;
                $n++;
                $at = $this->addPeriods($p['start'], $period, $interval, $n);
            }
            $p['payments'] = $payments;
            $p['next'] = $at;
        }
        unset($p);
        $this->suppressSideEffects();
        foreach ($this->customers as &$c) {
            $uid = wp_insert_user([
                'user_login' => $c['login'] . (username_exists($c['login']) ? mt_rand(10, 99) : ''),
                'user_pass' => wp_generate_password(20, false), 'user_email' => $c['email'], 'display_name' => $c['name'],
                'first_name' => $c['first'], 'last_name' => $c['last'], 'nickname' => $c['first'], 'role' => 'customer',
                'user_registered' => $this->utc($c['registered']),
            ]);
            if (is_wp_error($uid)) {
                \WP_CLI::error($uid->get_error_message());
            }
            $c['id'] = (int) $uid;
            $this->registry['users'][] = $c['id'];
            update_user_meta($c['id'], self::USER_MARK_KEY, self::MARK);
            update_user_meta($c['id'], 'paying_customer', 1);
            foreach ($c['address'] as $field => $value) {
                update_user_meta($c['id'], "billing_{$field}", $value);
                if ('email' !== $field) {
                    update_user_meta($c['id'], "shipping_{$field}", $value);
                }
            }
        }
        unset($c);
        foreach ($this->plan as $p) {
            $this->writeSubscription($p);
            \WP_CLI::log(sprintf('appended %s %s start %s', $p['group'], $this->customers[$p['cust']]['name'], $p['start']->format('Y-m-d H:i')));
        }
        update_option(self::REGISTRY, $this->registry, false);
        $this->flushCaches();
        \WP_CLI::success('Appended ' . count($this->plan) . ' subscriptions.');
    }

    /* ------------------------------------------------------------------ */
    /* Cleanup                                                             */
    /* ------------------------------------------------------------------ */

    private function cleanup(): void
    {
        global $wpdb;
        $this->suppressSideEffects();
        $reg = get_option(self::REGISTRY);
        // Store-credit ledger posts are delete-protected; the seed's own entry may go.
        remove_all_filters('pre_delete_post');
        $subs = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", self::MARK_KEY, self::MARK));
        $orders = $wpdb->get_col($wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s AND meta_value = %s", self::MARK_KEY, self::MARK));
        foreach ($orders as $oid) {
            $o = wc_get_order((int) $oid);
            if ($o) {
                $o->delete(true);
            }
            foreach (['wc_order_stats', 'wc_order_product_lookup', 'wc_order_coupon_lookup', 'wc_order_tax_lookup'] as $t) {
                $wpdb->delete($wpdb->prefix . $t, ['order_id' => (int) $oid]);
            }
        }
        $sub_ids = array_map('intval', $subs);
        if ($sub_ids) {
            $in = implode(',', $sub_ids);
            $wpdb->query("DELETE FROM {$wpdb->prefix}arraysubs_retention_events WHERE subscription_id IN ($in)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}arraysubs_retention_logs WHERE subscription_id IN ($in)");
            foreach ($sub_ids as $sid) {
                if (in_array(get_post_type($sid), ['arraysubs_data', 'product', 'arraysubs_credit'], true)) {
                    wp_delete_post($sid, true);
                }
            }
        }
        $users = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", self::USER_MARK_KEY, self::MARK));
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($users as $uid) {
            foreach ($wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'arraysubs_credit' AND post_author = %d", (int) $uid)) as $credit_id) {
                wp_delete_post((int) $credit_id, true);
            }
            $wpdb->delete($wpdb->prefix . 'wc_customer_lookup', ['user_id' => (int) $uid]);
            wp_delete_user((int) $uid);
        }
        foreach ($wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'arraysubs_sub_note' AND (post_content LIKE '%Demo · Team Membership%')") as $note_id) {
            wp_delete_post((int) $note_id, true);
        }
        delete_option(self::REGISTRY);
        $this->flushCaches();
        \WP_CLI::success(sprintf('Removed %d subscriptions/products, %d orders, %d users.', count($subs), count($orders), count($users)));
    }
}

(new AS_Media_Growth_Seed())->run($args[0] ?? 'plan');
