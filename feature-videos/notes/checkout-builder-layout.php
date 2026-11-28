<?php
/**
 * Demo Checkout Builder layout for the checkout-builder feature video.
 * Writes ONLY the private overlay option `fv_cb_overlay` (read by the cookie-gated
 * mu-plugin wp-content/mu-plugins/zz-fv-checkout-builder.php). The shared
 * arraysubs_settings['checkout_builder'] is never touched.
 *
 * Usage: wp eval-file checkout-builder-layout.php <pre|noaddr|final|delete> --allow-root
 *   pre    = layout before the on-camera drag (no "Photo of your dog" upload, stock address form)
 *   noaddr = after the on-camera drag + save (upload field), stock address form (chapters 3-4)
 *   final  = noaddr + the on-camera address edits of chapter 4 (US/CA only, no phone,
 *            email renamed); chapters 5-9
 *   delete = remove the overlay option
 */
defined( 'ABSPATH' ) || exit;

use ArraySubsPro\Features\CheckoutBuilder\Services\ConfigSanitizer;

$variant = $args[0] ?? 'final';
if ( 'delete' === $variant ) {
	delete_option( 'fv_cb_overlay' );
	echo "deleted\n";
	return;
}

$f = static function ( array $field ): array {
	return array_merge(
		array(
			'placeholder'   => '',
			'is_required'   => false,
			'help_text'     => '',
			'default_value' => '',
			'column_width'  => 'full',
			'css_class'     => '',
			'is_custom'     => true,
			'is_builtin'    => false,
			'is_locked'     => false,
		),
		$field
	);
};

$trim_address = ( 'final' === $variant );
$billing      = $f(
	array(
		'key'               => 'arraysubs_billing_address',
		'type'              => 'billing_address',
		'label'             => 'Billing Address',
		'is_custom'         => false,
		'is_builtin'        => true,
		'address_countries' => $trim_address ? array( 'US', 'CA' ) : array(),
		'fields_config'     => $trim_address ? array(
			'billing_phone' => array( 'hidden' => true ),
			'billing_email' => array( 'label' => 'Email for delivery updates' ),
		) : array(),
	)
);
$coupon   = $f( array( 'key' => 'arraysubs_checkout_coupon_notices', 'type' => 'checkout_coupon_notices', 'label' => 'Coupon and Notices', 'is_custom' => false ) );
$payment  = $f( array( 'key' => 'arraysubs_order_info_payment', 'type' => 'order_info_payment', 'label' => 'Order Info and Payment', 'is_custom' => false ) );

$heading = $f( array( 'key' => 'heading_about_your_dog', 'type' => 'heading', 'label' => 'Heading', 'content' => 'Tell us about your dog', 'heading_level' => 'h3' ) );

$dog_size = $f(
	array(
		'key'           => '_arraysubs_cf_dog_size',
		'type'          => 'grid_select',
		'label'         => 'Dog size',
		'is_required'   => true,
		'help_text'     => 'We portion each bag to your dog.',
		'type_settings' => array( 'multi' => false, 'columns' => 3 ),
		'options'       => array(
			array( 'value' => 'small', 'label' => 'Small', 'description' => 'Under 10 kg' ),
			array( 'value' => 'medium', 'label' => 'Medium', 'description' => '10 to 25 kg' ),
			array( 'value' => 'large', 'label' => 'Large', 'description' => 'Over 25 kg' ),
		),
	)
);

$allergies = $f(
	array(
		'key'           => '_arraysubs_cf_food_allergies',
		'type'          => 'grid_select',
		'label'         => 'Food allergies',
		'is_required'   => true,
		'type_settings' => array( 'multi' => false, 'columns' => 2 ),
		'options'       => array(
			array( 'value' => 'no', 'label' => 'No', 'description' => 'Any recipe is fine' ),
			array( 'value' => 'yes', 'label' => 'Yes', 'description' => 'We leave some out' ),
		),
	)
);

$avoid = $f(
	array(
		'key'              => '_arraysubs_cf_ingredients_to_avoid',
		'type'             => 'grid_select',
		'label'            => 'Ingredients to avoid',
		'is_required'      => true,
		'type_settings'    => array( 'multi' => true, 'columns' => 4 ),
		'options'          => array(
			array( 'value' => 'chicken', 'label' => 'Chicken' ),
			array( 'value' => 'beef', 'label' => 'Beef' ),
			array( 'value' => 'wheat', 'label' => 'Wheat' ),
			array( 'value' => 'dairy', 'label' => 'Dairy' ),
		),
		'visibility_rules' => array(
			array( 'field' => '_arraysubs_cf_food_allergies', 'operator' => 'is', 'value' => 'yes', 'logic' => 'AND' ),
		),
	)
);

$photo = $f(
	array(
		'key'           => '_arraysubs_cf_photo_of_your_dog',
		'type'          => 'upload',
		'label'         => 'Photo of your dog',
		'help_text'     => 'Optional. We print it on the box label.',
		'type_settings' => array( 'allowed_types' => array( 'jpg', 'png' ), 'max_file_count' => 2 ),
	)
);

$day = $f(
	array(
		'key'           => '_arraysubs_cf_delivery_day',
		'type'          => 'grid_select',
		'label'         => 'Delivery day',
		'is_required'   => true,
		'type_settings' => array( 'multi' => false, 'columns' => 3 ),
		'options'       => array(
			array( 'value' => 'wednesday', 'label' => 'Wednesday' ),
			array( 'value' => 'friday', 'label' => 'Friday' ),
			array( 'value' => 'saturday', 'label' => 'Saturday' ),
		),
	)
);
$first = $f(
	array(
		'key'           => '_arraysubs_cf_first_delivery',
		'type'          => 'calendar',
		'label'         => 'First delivery',
		'type_settings' => array( 'min_date' => '2026-12-01', 'max_date' => '2027-01-31', 'format' => '' ),
	)
);

$treat_ids = array( 31450, 31401, 31407, 31413 );
$treats    = $f(
	array(
		'key'           => '_arraysubs_cf_add_a_treat',
		'type'          => 'product_table',
		'label'         => 'Add a treat to the first box',
		'type_settings' => array(
			'product_ids'          => $treat_ids,
			'category_ids'         => array(),
			'columns'              => array( 'thumbnail' => true, 'title' => true, 'quantity' => true, 'sku' => false, 'price' => true, 'sale_price' => false, 'short_description' => false ),
			'product_options_data' => array_map(
				static function ( $id ) {
					return array( 'value' => (string) $id, 'label' => get_the_title( $id ) );
				},
				$treat_ids
			),
			'category_options_data' => array(),
		),
	)
);

$notes = $f( array( 'key' => 'arraysubs_order_notes', 'type' => 'order_notes', 'label' => 'Order Notes', 'is_custom' => false, 'is_builtin' => true, 'fields_config' => array( 'order_comments' => array( 'label' => 'Delivery notes' ) ) ) );

$dog_fields = array( $dog_size, $allergies, $avoid, $treats );
if ( 'pre' !== $variant ) {
	$dog_fields[] = $photo;
}

$config = array(
	'mode'            => 'multistep',
	'default'         => array( 'sections' => array() ),
	'copy_to_renewal' => true,
	'multistep'       => array(
		'design' => array(
			'color_scheme'         => 'custom',
			'primary_color'        => '#7e43f6',
			'secondary_color'      => '#64748b',
			'accent_color'         => '#10b981',
			'error_color'          => '#ef4444',
			'background_color'     => '#ffffff',
			'text_color'           => '#1e293b',
			'container_width'      => 'medium',
			'container_max_width'  => '',
			'border_radius'        => 10,
			'step_position'        => 'top',
			'step_indicator_style' => 'numbers',
			'spacing'              => 'comfortable',
		),
		'steps'  => array(
			array( 'id' => 'step_details', 'name' => 'Your details', 'icon' => '', 'fields' => array( $billing ) ),
			array( 'id' => 'step_dog', 'name' => 'Your dog', 'icon' => '', 'fields' => $dog_fields ),
			array( 'id' => 'step_delivery', 'name' => 'Delivery', 'icon' => '', 'fields' => array( $day, $first, $notes ) ),
			array( 'id' => 'step_payment', 'name' => 'Payment', 'icon' => '', 'fields' => array( $coupon, $payment ) ),
		),
	),
);

$clean = ( new ConfigSanitizer() )->sanitizeConfig( $config );
$clean['copy_to_renewal'] = true;
// The field editor binds "Allow Multiple" to type_settings.multiple, while the sanitizer only
// stores type_settings.multi (product bug, see notes/checkout-builder.md). Store both so the
// editor shows the real state of the multi-choice grid on camera. Display only.
foreach ( $clean['multistep']['steps'] as &$step ) {
	foreach ( $step['fields'] as &$field ) {
		if ( in_array( $field['type'], array( 'grid_select', 'image_select' ), true ) && ! empty( $field['type_settings']['multi'] ) ) {
			$field['type_settings']['multiple'] = true;
		}
	}
	unset( $field );
}
unset( $step );
update_option( 'fv_cb_overlay', $clean, false );
echo 'overlay ' . $variant . ': ' . count( $clean['multistep']['steps'] ) . " steps\n";
