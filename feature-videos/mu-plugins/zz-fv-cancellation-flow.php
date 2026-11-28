<?php
/**
 * Temporary helper for the cancellation-flow feature video (qa/feature-videos).
 * Active only for requests that carry BOTH the `fv_clean=1` cookie (recording browser)
 * and the `fv_cf=1` cookie (set only by scenes/cancellation-flow.mjs for its own sessions).
 *
 * For those requests only (display/runtime overrides, never persisted):
 * - shows the Cancel Subscription link (`customer_actions.allow_cancellation`);
 * - books customer cancellations for the end of the paid period (`cancellation.cancel_immediately`);
 * - shows a neutral help-page URL in the Contact Support offer instead of the local demo page;
 * - prints prices with the currency symbol first ($69.00), like the admin stat cards;
 * - hides the two developer pages (Checkout Classic, Cart Page) in the storefront header menu.
 *
 * A settings write in such a request keeps the stored values of every overridden key.
 * Other agents' recordings are unaffected. Delete after recording.
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $_COOKIE['fv_clean'] ) || '1' !== $_COOKIE['fv_clean'] || empty( $_COOKIE['fv_cf'] ) || '1' !== $_COOKIE['fv_cf'] ) {
	return;
}

/** Overridden arraysubs_settings paths => display value. */
function fv_cf_overrides() {
	return array(
		'customer_actions.allow_cancellation'                       => true,
		'cancellation.cancel_immediately'                           => false,
		'cancellation.retention_offers.contact_support.support_url' => 'https://example.com/help/',
	);
}

function fv_cf_set_path( array $arr, $path, $value, $unset = false ) {
	$keys = explode( '.', $path );
	$ref  = &$arr;
	foreach ( array_slice( $keys, 0, -1 ) as $k ) {
		if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
			if ( $unset ) {
				return $arr;
			}
			$ref[ $k ] = array();
		}
		$ref = &$ref[ $k ];
	}
	$last = end( $keys );
	if ( $unset ) {
		unset( $ref[ $last ] );
	} else {
		$ref[ $last ] = $value;
	}
	return $arr;
}

function fv_cf_get_path( $arr, $path, &$found ) {
	$found = false;
	foreach ( explode( '.', $path ) as $k ) {
		if ( ! is_array( $arr ) || ! array_key_exists( $k, $arr ) ) {
			return null;
		}
		$arr = $arr[ $k ];
	}
	$found = true;
	return $arr;
}

add_filter( 'option_arraysubs_settings', static function ( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	foreach ( fv_cf_overrides() as $path => $v ) {
		$value = fv_cf_set_path( $value, $path, $v );
	}
	return $value;
}, 99 );

// Never let an override reach the database: restore the stored value of every overridden key.
add_filter( 'pre_update_option_arraysubs_settings', static function ( $new_value ) {
	global $wpdb;
	if ( ! is_array( $new_value ) ) {
		return $new_value;
	}
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'arraysubs_settings' ) );
	$raw = is_string( $raw ) ? maybe_unserialize( $raw ) : array();
	$raw = is_array( $raw ) ? $raw : array();
	foreach ( array_keys( fv_cf_overrides() ) as $path ) {
		$stored = fv_cf_get_path( $raw, $path, $found );
		$new_value = $found ? fv_cf_set_path( $new_value, $path, $stored ) : fv_cf_set_path( $new_value, $path, null, true );
	}
	return $new_value;
}, 99 );

add_filter( 'option_woocommerce_currency_pos', static function () {
	return 'left';
}, 99 );

add_filter( 'render_block_core/navigation-link', static function ( $html, $block ) {
	$label = isset( $block['attrs']['label'] ) ? (string) $block['attrs']['label'] : '';
	return in_array( $label, array( 'Checkout Classic', 'Cart Page' ), true ) ? '' : $html;
}, 10, 2 );
