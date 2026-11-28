<?php
/**
 * Temporary helper for recording the ArraySubs feature videos (qa/feature-videos).
 * Active only for requests that carry the `fv_clean=1` cookie, which only the
 * headless recording browser sets. Strips the "Demo · " prefix from product names,
 * applies display-only renames from qa/feature-videos/clean-map.d/*.json and shows a
 * store name instead of "wp-test2". Also keeps ArraySubs Pro on for those requests.
 * Delete after recording.
 */
defined( 'ABSPATH' ) || exit;

$fv_is_recorder = ! empty( $_COOKIE['fv_clean'] ) && '1' === $_COOKIE['fv_clean'];
$fv_is_cli      = defined( 'WP_CLI' ) && WP_CLI && '1' === getenv( 'FV_PRO' );

if ( ! $fv_is_recorder && ! $fv_is_cli ) {
	return;
}

// Recording requests (and the recording agents' WP-CLI calls with FV_PRO=1) always run with
// ArraySubs Pro and without the WPSubscription plugins, whatever the stored plugin list says.
// Other visitors and sessions keep the stored list.
add_filter( 'option_active_plugins', static function ( $plugins ) {
	$plugins = array_values( array_diff( (array) $plugins, array( 'subscription/subscription.php', 'subscription-pro/subscription-pro.php' ) ) );
	if ( ! in_array( 'arraysubspro/arraysubspro.php', $plugins, true ) ) {
		$plugins[] = 'arraysubspro/arraysubspro.php';
	}
	return $plugins;
}, PHP_INT_MAX );

if ( ! $fv_is_recorder ) {
	return;
}

// If something flushed the rewrite rules while Pro was off, Pro's My Account endpoints
// (as-features, as-store-credit, ...) 404. Rebuild them once, with Pro loaded.
add_action( 'wp_loaded', static function () {
	$rules = get_option( 'rewrite_rules' );
	if ( is_array( $rules ) && ! preg_grep( '/as-features/', array_keys( $rules ) ) ) {
		flush_rewrite_rules( false );
	}
}, 20 );

function fv_clean_pairs() {
	static $pairs = null;
	if ( null !== $pairs ) {
		return $pairs;
	}
	$dot   = "\xC2\xB7";
	$pairs = array(
		'Demo ' . $dot . ' '          => '',
		'Demo \\' . 'u00b7 '          => '',
		'Demo &middot; '              => '',
		'Demo &#183; '                => '',
		'Demo &#xB7; '                => '',
		'Demo%20%C2%B7%20'            => '',
	);
	foreach ( (array) glob( WP_CONTENT_DIR . '/plugins/qa/feature-videos/clean-map.d/*.json' ) as $file ) {
		$json = json_decode( (string) file_get_contents( $file ), true );
		if ( is_array( $json ) && ! empty( $json['map'] ) && is_array( $json['map'] ) ) {
			foreach ( $json['map'] as $from => $to ) {
				if ( '' !== (string) $from ) {
					$pairs[ (string) $from ] = (string) $to;
				}
			}
		}
	}
	return $pairs;
}

function fv_clean_text( $s ) {
	if ( ! is_string( $s ) || '' === $s ) {
		return $s;
	}
	return strtr( $s, fv_clean_pairs() );
}

function fv_clean_deep( $v ) {
	if ( is_string( $v ) ) {
		return fv_clean_text( $v );
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			$v[ $k ] = fv_clean_deep( $x );
		}
		return $v;
	}
	if ( $v instanceof stdClass ) {
		foreach ( get_object_vars( $v ) as $k => $x ) {
			$v->$k = fv_clean_deep( $x );
		}
	}
	return $v;
}

add_filter( 'option_blogname', static function () {
	return 'Brightside Market';
} );

add_action( 'init', static function () {
	ob_start( 'fv_clean_text' );
}, 0 );

add_filter( 'rest_post_dispatch', static function ( $response ) {
	if ( $response instanceof WP_REST_Response ) {
		$response->set_data( fv_clean_deep( $response->get_data() ) );
	}
	return $response;
}, PHP_INT_MAX );
