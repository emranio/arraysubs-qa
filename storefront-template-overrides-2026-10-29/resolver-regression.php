<?php

/**
 * Isolated resolver checks. Run directly with PHP, without loading WordPress.
 *
 * Exercises the real loader and an unchanged copy of the subscriptions-list
 * view. It does not test product behavior or replace real browser coverage.
 */

if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    throw new RuntimeException('Run this regression directly with the PHP CLI, outside WordPress.');
}

define('ABSPATH', __DIR__ . '/');

use ArraySubs\Supports\Config;
use ArraySubs\Supports\StorefrontTemplate;

$workspace = dirname(__DIR__, 2);
require $workspace . '/arraysubs/src/Supports/StorefrontTemplate.php';
require $workspace . '/arraysubs/src/Supports/Traits/Singleton.php';
require $workspace . '/arraysubs/src/Supports/Config.php';

$theme_paths = [];
$theme_directory_reads = 0;
$setting_reads = [];

function get_stylesheet_directory(): string
{
    ++$GLOBALS['theme_directory_reads'];
    return $GLOBALS['theme_paths']['child'];
}

function get_template_directory(): string
{
    ++$GLOBALS['theme_directory_reads'];
    return $GLOBALS['theme_paths']['parent'];
}

function trailingslashit(string $path): string
{
    return rtrim($path, '/\\') . '/';
}

function arraysubs_get_setting(string $key, $default = null)
{
    $GLOBALS['setting_reads'][] = $key;
    return $key === 'customer_portal.subscription_list_view' ? 'cards' : $default;
}

function esc_html_e(string $text, string $domain): void
{
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$checks = 0;
$skips = [];
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};

$write = static function (string $path, string $content = '<?php /* resolver fixture */'): void {
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0700, true) && !is_dir(dirname($path))) {
        throw new RuntimeException('Cannot create fixture directory.');
    }
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Cannot write fixture.');
    }
};

$remove = static function (string $path): void {
    if (!is_dir($path)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            rmdir($entry->getPathname());
        } else {
            chmod($entry->getPathname(), 0600);
            unlink($entry->getPathname());
        }
    }
    rmdir($path);
};

$temporary = sys_get_temp_dir() . '/arraysubs-template-regression-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700, true);
$theme_paths = ['child' => $temporary . '/child', 'parent' => $temporary . '/parent'];
$default = $temporary . '/plugin/Features/CustomerPortal/views/view-subscription.php';
$relative = 'customer-portal/view-subscription.php';
$child = $theme_paths['child'] . '/arraysubs/' . $relative;
$parent = $theme_paths['parent'] . '/arraysubs/' . $relative;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $write($default);
    $assert(StorefrontTemplate::locate($relative, $default) === $default, 'Missing overrides must use the bundled file.');

    $write($parent);
    $assert(StorefrontTemplate::locate($relative, $default) === $parent, 'Parent override must precede the bundled file.');

    $write($child);
    $assert(StorefrontTemplate::locate($relative, $default) === $child, 'Child override must precede the parent override.');

    $pro_child = $theme_paths['child'] . '/arraysubspro/' . $relative;
    $assert(StorefrontTemplate::locate($relative, $default, 'arraysubspro') === $default, 'Pro must not select core overrides.');
    $write($pro_child);
    $assert(StorefrontTemplate::locate($relative, $default, 'arraysubspro') === $pro_child, 'Pro namespace must resolve its own override.');
    $assert(StorefrontTemplate::locate($relative, $default) === $child, 'Core namespace must remain separate from Pro.');

    unlink($child);
    $assert(StorefrontTemplate::locate($relative, $default) === $parent, 'Removing a child override must immediately restore the parent.');

    mkdir($child);
    $assert(StorefrontTemplate::locate($relative, $default) === $parent, 'A directory ending in .php must be skipped.');
    rmdir($child);

    $write($child);
    chmod($child, 0000);
    clearstatcache(true, $child);
    if (!is_readable($child)) {
        $assert(StorefrontTemplate::locate($relative, $default) === $parent, 'Unreadable child files must be skipped.');
    } else {
        $skips[] = 'Unreadable-file check: this process can read permission-000 files.';
    }
    chmod($child, 0600);

    $saved_child = $theme_paths['child'];
    $theme_paths['child'] = $theme_paths['parent'];
    $assert(StorefrontTemplate::locate($relative, $default) === $parent, 'A site with one active theme must resolve that theme.');
    $theme_paths['child'] = $saved_child;

    $write($child, '');
    $assert(StorefrontTemplate::locate($relative, $default) === $child, 'An intentionally empty readable override must be respected.');

    $invalid_paths = [
        '', '../view.php', 'customer-portal/../view.php', '/view.php',
        'C:/view.php', 'C:\\view.php', 'php://filter/resource=view.php',
        'file:///view.php', 'customer-portal\\view.php', "view\0.php",
        'customer-portal//view.php', './view.php', 'view.html', 'view.php.bak',
        'view.PHP', "view.php\n", 'view.php?path=other', 'view%2ephp',
    ];
    foreach ($invalid_paths as $invalid) {
        $theme_directory_reads = 0;
        $assert(StorefrontTemplate::locate($invalid, $default) === $default, 'Invalid path must return the bundled file: ' . json_encode($invalid));
        $assert($theme_directory_reads === 0, 'Invalid path must be rejected before theme lookup.');
    }
    foreach (['', '../arraysubs', '/arraysubs', 'arraysubs/other', 'ARRAYSUBS', 'other-plugin'] as $invalid_plugin) {
        $theme_directory_reads = 0;
        $assert(StorefrontTemplate::locate($relative, $default, $invalid_plugin) === $default, 'Invalid plugin namespace must return the bundled file.');
        $assert($theme_directory_reads === 0, 'Invalid namespace must be rejected before theme lookup.');
    }

    $scope_path = $theme_paths['child'] . '/arraysubs/tests/scope.php';
    $write($scope_path, '<?php $local_value .= ":" . $this->marker; echo $local_value;');
    $scope = new class {
        public string $marker = 'caller-object';

        public function render(string $fallback): array
        {
            $local_value = 'caller-local';
            ob_start();
            try {
                include StorefrontTemplate::locate('tests/scope.php', $fallback);
                return [(string) ob_get_contents(), $local_value];
            } finally {
                ob_end_clean();
            }
        }
    };
    $assert($scope->render($default) === ['caller-local:caller-object', 'caller-local:caller-object'], 'Include must retain caller locals, modifications, and $this.');

    // Copy the actual list view; nested bundled fallback must keep working
    // after __DIR__ changes to a child-theme directory with no partials.
    $config_property = new ReflectionProperty(Config::class, 'configData');
    $config_property->setValue(Config::instance(), ['plugin' => ['src_path' => $workspace . '/arraysubs/src']]);
    $list_relative = 'customer-portal/my-account-subscriptions.php';
    $list_copy = $theme_paths['child'] . '/arraysubs/' . $list_relative;
    $write($list_copy, file_get_contents($workspace . '/arraysubs/src/Features/CustomerPortal/views/my-account-subscriptions.php'));
    $render_list = static function (array $subscriptions = []) use ($list_relative, $workspace): string {
        $arraysubs_subscriptions = $subscriptions;
        ob_start();
        try {
            include StorefrontTemplate::locate($list_relative, $workspace . '/arraysubs/src/Features/CustomerPortal/views/my-account-subscriptions.php');
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    };

    $setting_reads = [];
    $html = $render_list();
    $assert(str_contains($html, 'You have no subscriptions yet.'), 'Copied list must still render its empty state.');
    $assert(in_array('customer_actions.allow_cancellation', $setting_reads, true), 'Copied list must execute the bundled cancellation partial when no theme partial exists.');

    $notice_relative = '/arraysubs/customer-portal/cancellation-contact-notice.php';
    $write($theme_paths['parent'] . $notice_relative, '<?php echo "parent-notice";');
    $assert(str_contains($render_list(), 'parent-notice'), 'Copied list must find an independently overridden parent partial.');
    $write($theme_paths['child'] . $notice_relative, '<?php echo "child-notice";');
    $assert(str_contains($render_list(), 'child-notice'), 'Copied list must prefer an independently overridden child partial.');
    $write($theme_paths['child'] . '/arraysubs/customer-portal/subscription-cards.php', '<?php echo "card-count:" . count($arraysubs_subscriptions);');
    $assert(str_contains($render_list([(object) ['ID' => 123]]), 'card-count:1'), 'Copied list must pass subscription locals into an independently overridden cards partial.');

    $detail_source = file_get_contents($workspace . '/arraysubs/src/Features/CustomerPortal/views/view-subscription.php');
    $assert(!str_contains($detail_source, "include __DIR__ . '/cancellation-contact-notice.php'"), 'Detail template must not anchor its nested fallback to the copied theme directory.');
    $assert(str_contains($detail_source, "Config::get('plugin.src_path') . '/Features/CustomerPortal/views/cancellation-contact-notice.php'"), 'Detail template nested fallback must remain anchored to the core plugin.');

    echo json_encode(['status' => 'passed', 'assertions' => $checks, 'skips' => $skips], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} finally {
    restore_error_handler();
    $remove($temporary);
}
