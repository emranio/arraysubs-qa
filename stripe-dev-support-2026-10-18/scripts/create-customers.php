<?php
/**
 * Create QA fixture customers (example.com) with US billing details.
 *
 * Usage: wp eval-file <abs>/scripts/create-customers.php <abs-password-file-outside-qa> <login> [<login>...] --allow-root
 * Existing customers (matched by email) are reused and their password is reset.
 * Passwords are appended to the password file (mode 0600) and never printed.
 * Prints {login: id} as JSON. Register the IDs with register-fixture.php.
 */

$pw_file = (string) ($args[0] ?? '');
$logins = array_slice($args, 1);
if ('' === $pw_file || false !== strpos($pw_file, '/plugins/qa/') || ! $logins) {
    WP_CLI::error('Usage: create-customers.php <password-file-outside-qa> <login>...');
}

$out = [];
$lines = '';
foreach ($logins as $login) {
    if (! preg_match('/^sqa-[a-z0-9-]+$/', $login)) {
        WP_CLI::error('Fixture logins must start with sqa-.');
    }
    $email = $login . '@example.com';
    $password = wp_generate_password(20, false, false);
    $existing = get_user_by('email', $email);
    if ($existing) {
        $uid = (int) $existing->ID;
        wp_set_password($password, $uid);
    } else {
        $uid = wc_create_new_customer($email, $login, $password, ['first_name' => 'Sqa', 'last_name' => $login]);
        if (is_wp_error($uid)) {
            WP_CLI::error('Customer create failed for ' . $email . ': ' . $uid->get_error_message());
        }
    }
    $customer = new WC_Customer($uid);
    $customer->set_billing_first_name('Sqa');
    $customer->set_billing_last_name($login);
    $customer->set_billing_address_1('100 QA Test Street');
    $customer->set_billing_city('San Francisco');
    $customer->set_billing_state('CA');
    $customer->set_billing_postcode('94105');
    $customer->set_billing_country('US');
    $customer->set_billing_phone('4155550100');
    $customer->set_billing_email($email);
    $customer->save();
    $out[$login] = $uid;
    $lines .= $uid . ' ' . $password . "\n";
}
file_put_contents($pw_file, $lines, FILE_APPEND);
chmod($pw_file, 0600);
echo wp_json_encode($out), "\n";
