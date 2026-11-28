<?php
/**
 * Temporary helper for the subscription-bundles feature video
 * (qa/feature-videos/scenes/subscription-bundles.mjs). Delete after recording.
 *
 * Active only for requests that carry BOTH the `fv_clean=1` cookie (recording browser) and the
 * `fv_sb=1` cookie (set only by scenes/subscription-bundles.mjs). Display only, nothing is written:
 * - prices print with the currency symbol first ($67.15), like the ArraySubs admin screens;
 * - the storefront header menu hides this dev store's scaffolding pages
 *   (Checkout, Checkout Classic, Cart Page); Home, Shop, My Account and the cart stay;
 * - the My Account subscriptions endpoint uses the plugin's default label instead of this
 *   store's custom "Memberships" label.
 * Other recordings and real visitors are unaffected.
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $_COOKIE['fv_clean'] ) || '1' !== $_COOKIE['fv_clean'] || empty( $_COOKIE['fv_sb'] ) || '1' !== $_COOKIE['fv_sb'] ) {
	return;
}

add_filter( 'option_woocommerce_currency_pos', static function () {
	return 'left';
}, 99 );

add_filter( 'render_block_core/navigation-link', static function ( $html, $block ) {
	$label = isset( $block['attrs']['label'] ) ? trim( wp_strip_all_tags( (string) $block['attrs']['label'] ) ) : '';
	return in_array( $label, array( 'Checkout', 'Checkout Classic', 'Cart Page' ), true ) ? '' : $html;
}, 10, 2 );

add_filter( 'option_arraysubs_myaccount_menu_config', static function ( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	foreach ( $value as $i => $item ) {
		if ( is_array( $item ) && isset( $item['id'] ) && 'subscriptions' === $item['id'] ) {
			$value[ $i ]['label'] = '';
		}
	}
	return $value;
}, 99 );
