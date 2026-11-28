<?php
/**
 * Temporary helper for the checkout-builder feature video
 * (qa/feature-videos/scenes/checkout-builder.mjs). Delete after recording.
 *
 * Active only for requests that carry the `fv_cb=1` cookie, which only that scene's
 * headless recording browser sets. Other recordings and real visitors are untouched.
 *
 * - The Checkout Builder layout is read from and saved to the private option
 *   `fv_cb_overlay` instead of the shared `arraysubs_settings['checkout_builder']`,
 *   so the demo layout never reaches other sessions. The shared option is never
 *   written from the recording session.
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $_COOKIE['fv_cb'] ) || '1' !== $_COOKIE['fv_cb'] ) {
	return;
}

add_filter(
	'option_arraysubs_settings',
	static function ( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$overlay = get_option( 'fv_cb_overlay', null );
		if ( is_array( $overlay ) ) {
			$value['checkout_builder'] = $overlay;
		}
		return $value;
	},
	PHP_INT_MAX
);

add_filter(
	'pre_update_option_arraysubs_settings',
	static function ( $value, $old_value ) {
		if ( is_array( $value ) && isset( $value['checkout_builder'] ) && is_array( $value['checkout_builder'] ) ) {
			update_option( 'fv_cb_overlay', $value['checkout_builder'], false );
		}
		// Returning the current value makes update_option() a no-op for the shared option.
		return $old_value;
	},
	PHP_INT_MAX,
	2
);

// Storefront header: hide the two scaffolding pages ("Checkout Classic", "Cart Page") from the
// recording browser's view of the Site Header Menu. Display only, the menu is not changed.
add_filter(
	'render_block_core/navigation-link',
	static function ( $content, $block ) {
		$label = isset( $block['attrs']['label'] ) ? (string) $block['attrs']['label'] : '';
		return in_array( $label, array( 'Checkout Classic', 'Cart Page' ), true ) ? '' : $content;
	},
	10,
	2
);

// Admin menu: hide this dev site's plugin/update count badges on camera.
add_action(
	'admin_head',
	static function () {
		echo '<style id="fv-cb-badges">#adminmenu .update-plugins, #adminmenu .awaiting-mod { display: none !important; }</style>';
	}
);
