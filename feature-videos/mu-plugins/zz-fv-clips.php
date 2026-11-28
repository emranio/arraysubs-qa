<?php
/**
 * Temporary helper for the short website clips (qa/feature-videos/clip.mjs).
 * Active only for requests that carry the `fv_clip=<scene>` cookie, which only the
 * clip recorder sets. Reads qa/feature-videos/clips/overrides/<scene>.json and applies
 * request-scoped overrides: options, arraysubs_settings keys, post meta, product prices
 * and display-only text replacements. Nothing is written to the database; a guard keeps
 * arraysubs_settings unchanged if a settings save ever happens in such a request.
 * Delete after recording.
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $_COOKIE['fv_clip'] ) ) {
	return;
}

$fv_clip_scene = preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $_COOKIE['fv_clip'] ) );
$fv_clip_file  = WP_CONTENT_DIR . '/plugins/qa/feature-videos/clips/overrides/' . $fv_clip_scene . '.json';
if ( '' === $fv_clip_scene || ! is_readable( $fv_clip_file ) ) {
	return;
}

$GLOBALS['fv_clip_cfg'] = json_decode( (string) file_get_contents( $fv_clip_file ), true );
if ( ! is_array( $GLOBALS['fv_clip_cfg'] ) ) {
	return;
}

function fv_clip_cfg( $key ) {
	return isset( $GLOBALS['fv_clip_cfg'][ $key ] ) && is_array( $GLOBALS['fv_clip_cfg'][ $key ] ) ? $GLOBALS['fv_clip_cfg'][ $key ] : array();
}

// Plain options.
foreach ( fv_clip_cfg( 'options' ) as $fv_name => $fv_value ) {
	add_filter(
		'pre_option_' . $fv_name,
		static function () use ( $fv_value ) {
			return $fv_value;
		}
	);
}

// arraysubs_settings keys, "group.key" => value.
if ( fv_clip_cfg( 'settings' ) ) {
	add_filter(
		'option_arraysubs_settings',
		static function ( $settings ) {
			if ( ! is_array( $settings ) ) {
				return $settings;
			}
			foreach ( fv_clip_cfg( 'settings' ) as $path => $value ) {
				list( $group, $key ) = array_pad( explode( '.', $path, 2 ), 2, '' );
				if ( '' !== $group && '' !== $key ) {
					$settings[ $group ][ $key ] = $value;
				}
			}
			return $settings;
		},
		PHP_INT_MAX
	);
	add_filter(
		'pre_update_option_arraysubs_settings',
		static function ( $new_value, $old_value ) {
			return $old_value;
		},
		PHP_INT_MAX,
		2
	);
}

// Single post meta reads: { "<post id>": { "<meta key>": value } }.
if ( fv_clip_cfg( 'meta' ) ) {
	add_filter(
		'get_post_metadata',
		static function ( $value, $object_id, $meta_key, $single ) {
			$meta = fv_clip_cfg( 'meta' );
			$id   = (string) $object_id;
			if ( '' === $meta_key || ! isset( $meta[ $id ] ) || ! array_key_exists( $meta_key, $meta[ $id ] ) ) {
				return $value;
			}
			$v = $meta[ $id ][ $meta_key ];
			return $single ? array( $v ) : array( $v );
		},
		PHP_INT_MAX,
		4
	);
}

// Product prices: { "<product id>": { "price": "69.00", "regular_price": "69.00", "sale_price": "" } }.
if ( fv_clip_cfg( 'prices' ) ) {
	foreach ( array( 'price', 'regular_price', 'sale_price' ) as $fv_prop ) {
		foreach ( array( 'woocommerce_product_get_', 'woocommerce_product_variation_get_' ) as $fv_prefix ) {
			add_filter(
				$fv_prefix . $fv_prop,
				static function ( $value, $product ) use ( $fv_prop ) {
					$prices = fv_clip_cfg( 'prices' );
					$id     = (string) $product->get_id();
					return isset( $prices[ $id ] ) && array_key_exists( $fv_prop, $prices[ $id ] ) ? $prices[ $id ][ $fv_prop ] : $value;
				},
				PHP_INT_MAX,
				2
			);
		}
	}
}

// Display-only text replacements in page output and REST responses.
if ( fv_clip_cfg( 'text' ) ) {
	$GLOBALS['fv_clip_text'] = fv_clip_cfg( 'text' );
	function fv_clip_text( $s ) {
		return is_string( $s ) && '' !== $s ? strtr( $s, $GLOBALS['fv_clip_text'] ) : $s;
	}
	function fv_clip_deep( $v ) {
		if ( is_string( $v ) ) {
			return fv_clip_text( $v );
		}
		if ( is_array( $v ) ) {
			foreach ( $v as $k => $x ) {
				$v[ $k ] = fv_clip_deep( $x );
			}
		}
		return $v;
	}
	add_action(
		'init',
		static function () {
			ob_start( 'fv_clip_text' );
		},
		1
	);
	add_filter(
		'rest_post_dispatch',
		static function ( $response ) {
			if ( $response instanceof WP_REST_Response ) {
				$response->set_data( fv_clip_deep( $response->get_data() ) );
			}
			return $response;
		},
		PHP_INT_MAX
	);
}
