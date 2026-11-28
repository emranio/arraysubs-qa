<?php
/**
 * Temporary helper for the renewal-sync feature video (qa/feature-videos/scenes/renewal-sync.mjs).
 * Delete after recording.
 *
 * Active only for requests that carry BOTH the `fv_clean=1` cookie (recording browser) and the
 * `fv_rs=1` cookie (set only by scenes/renewal-sync.mjs). For those requests the store-wide
 * Renewal Sync switch and First Charge mode are read from the private option `fv_rs_overlay`
 * instead of the shared `arraysubs_settings['renewals']`, and a Save on the Renewal Settings
 * screen writes only that private option. The shared settings are never written from such a
 * request, so other recordings and visitors keep the real (stored) values.
 * It also shows the cart's "First billing cycle" row (the plugin default) for those requests only,
 * while the shared Pro Cart Info Editor setting keeps hiding it for everyone else.
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $_COOKIE['fv_clean'] ) || '1' !== $_COOKIE['fv_clean'] || empty( $_COOKIE['fv_rs'] ) || '1' !== $_COOKIE['fv_rs'] ) {
	return;
}

function fv_rs_overlay() {
	$overlay = get_option( 'fv_rs_overlay', null );
	return is_array( $overlay ) ? $overlay : array();
}

add_filter(
	'option_arraysubs_settings',
	static function ( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		// Show the cart's "First billing cycle" row (plugin default) for this browser only; the
		// shared store currently hides it through the Pro Cart Info Editor.
		if ( ! isset( $value['cart_info_editor'] ) || ! is_array( $value['cart_info_editor'] ) ) {
			$value['cart_info_editor'] = array();
		}
		$value['cart_info_editor']['hide_first_billing_cycle_info'] = false;
		$overlay = fv_rs_overlay();
		if ( ! $overlay ) {
			return $value;
		}
		if ( ! isset( $value['renewals'] ) || ! is_array( $value['renewals'] ) ) {
			$value['renewals'] = array();
		}
		foreach ( array( 'sync_to_billing_cycle', 'sync_first_charge_mode' ) as $key ) {
			if ( array_key_exists( $key, $overlay ) ) {
				$value['renewals'][ $key ] = $overlay[ $key ];
			}
		}
		return $value;
	},
	PHP_INT_MAX
);

// Renewal Settings "Save": keep the two sync keys in the private overlay, write nothing shared.
add_filter(
	'rest_pre_dispatch',
	static function ( $result, $server, $request ) {
		if ( null !== $result || ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}
		if ( '/arraysubs/v1/settings/save' !== $request->get_route() || 'POST' !== $request->get_method() ) {
			return $result;
		}
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return $result;
		}
		$settings = $request->get_param( 'settings' );
		$renewals = is_array( $settings ) && isset( $settings['renewals'] ) && is_array( $settings['renewals'] ) ? $settings['renewals'] : array();
		$overlay  = fv_rs_overlay();
		if ( array_key_exists( 'sync_to_billing_cycle', $renewals ) ) {
			$overlay['sync_to_billing_cycle'] = (bool) $renewals['sync_to_billing_cycle'];
		}
		if ( array_key_exists( 'sync_first_charge_mode', $renewals ) ) {
			$overlay['sync_first_charge_mode'] = 'full' === $renewals['sync_first_charge_mode'] ? 'full' : 'prorate';
		}
		update_option( 'fv_rs_overlay', $overlay, false );
		$values = function_exists( 'arraysubs_get_settings' ) ? arraysubs_get_settings() : array();
		$when   = function_exists( 'arraysubs_format_timestamp_local' ) ? arraysubs_format_timestamp_local( time() ) : '';
		return new WP_REST_Response(
			array(
				'code'    => 200,
				'message' => __( 'Settings saved successfully', 'arraysubs' ),
				'content' => array(
					'values'                 => $values,
					'settings_last_saved_at' => $when,
				),
			),
			200
		);
	},
	10,
	3
);

// Belt and braces: any other write of the shared settings from such a request keeps the stored sync keys.
add_filter(
	'pre_update_option_arraysubs_settings',
	static function ( $new_value ) {
		global $wpdb;
		if ( ! is_array( $new_value ) ) {
			return $new_value;
		}
		$raw = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'arraysubs_settings' ) );
		$raw = is_string( $raw ) ? maybe_unserialize( $raw ) : array();
		foreach ( array( 'sync_to_billing_cycle', 'sync_first_charge_mode' ) as $key ) {
			if ( is_array( $raw ) && isset( $raw['renewals'] ) && is_array( $raw['renewals'] ) && array_key_exists( $key, $raw['renewals'] ) ) {
				$new_value['renewals'][ $key ] = $raw['renewals'][ $key ];
			}
		}
		if ( is_array( $raw ) && isset( $raw['cart_info_editor'] ) && is_array( $raw['cart_info_editor'] ) && array_key_exists( 'hide_first_billing_cycle_info', $raw['cart_info_editor'] ) ) {
			$new_value['cart_info_editor']['hide_first_billing_cycle_info'] = $raw['cart_info_editor']['hide_first_billing_cycle_info'];
		}
		return $new_value;
	},
	PHP_INT_MAX
);

// The store's one-click setting sends "Subscribe Now" straight to checkout. For this browser only,
// land on the cart instead, so the video shows the cart (first payment rows) once, then checkout.
add_filter(
	'woocommerce_add_to_cart_redirect',
	static function () {
		return wc_get_cart_url();
	},
	PHP_INT_MAX
);
// Matching WooCommerce notice wording ("Continue shopping" instead of "View cart" on the cart page).
add_filter(
	'pre_option_woocommerce_cart_redirect_after_add',
	static function () {
		return 'yes';
	}
);

// Display-only: the demo excerpt of Plus Membership (#6711) reads "— demo catalog." Some cart data
// reaches the block cart without passing the shared clean-map filters, so replace it at the source,
// for this browser only.
add_filter(
	'woocommerce_product_get_short_description',
	static function ( $desc, $product ) {
		if ( $product instanceof WC_Product && 6711 === (int) $product->get_id() ) {
			return 'Unlimited library access and 10 downloads a month.';
		}
		return $desc;
	},
	PHP_INT_MAX,
	2
);
