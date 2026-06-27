<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Simplify WooCommerce checkout for Vietnamese e-commerce.
 *
 * Removes unnecessary fields (company, address_2, postcode, country, state)
 * and reorders remaining fields for a streamlined checkout flow.
 *
 * @package SPL
 */
final class CheckoutFields {

	/**
	 * Fields to remove from checkout.
	 *
	 * @var list<string>
	 */
	private const REMOVE_BILLING = [
		'billing_company',
		'billing_address_2',
		'billing_postcode',
		'billing_country',
		'billing_state',
	];

	private const REMOVE_SHIPPING = [
		'shipping_company',
		'shipping_address_2',
		'shipping_postcode',
		'shipping_country',
		'shipping_state',
	];

	public static function register(): void {
		add_filter( 'woocommerce_checkout_fields', [ self::class, 'simplifyFields' ], 20 );
		add_filter( 'woocommerce_default_address_fields', [ self::class, 'adjustDefaults' ], 20 );
	}

	/**
	 * Remove unnecessary fields and adjust priorities.
	 *
	 * @param array<string, array<string, mixed>> $fields Checkout fields by section.
	 * @return array<string, array<string, mixed>>
	 */
	public static function simplifyFields( array $fields ): array {
		foreach ( self::REMOVE_BILLING as $key ) {
			unset( $fields['billing'][ $key ] );
		}

		foreach ( self::REMOVE_SHIPPING as $key ) {
			unset( $fields['shipping'][ $key ] );
		}

		// Reorder: name → phone → email → address (top to bottom).
		if ( isset( $fields['billing']['billing_first_name'] ) ) {
			$fields['billing']['billing_first_name']['priority'] = 10;
		}
		if ( isset( $fields['billing']['billing_last_name'] ) ) {
			$fields['billing']['billing_last_name']['priority'] = 20;
		}
		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['priority'] = 30;
			$fields['billing']['billing_phone']['required'] = true;
		}
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['priority'] = 40;
		}
		if ( isset( $fields['billing']['billing_city'] ) ) {
			$fields['billing']['billing_city']['priority']    = 50;
			$fields['billing']['billing_city']['label']       = __( 'Tỉnh/Thành phố', 'spl' );
			$fields['billing']['billing_city']['placeholder'] = __( 'TP. Hồ Chí Minh', 'spl' );
		}
		if ( isset( $fields['billing']['billing_address_1'] ) ) {
			$fields['billing']['billing_address_1']['priority']    = 60;
			$fields['billing']['billing_address_1']['label']       = __( 'Địa chỉ giao hàng', 'spl' );
			$fields['billing']['billing_address_1']['placeholder'] = __( 'Số nhà, tên đường, phường/xã, quận/huyện', 'spl' );
		}

		// Order notes.
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['placeholder'] = __( 'Ghi chú đơn hàng, yêu cầu giao hàng...', 'spl' );
		}

		return $fields;
	}

	/**
	 * Adjust default address field definitions.
	 *
	 * @param array<string, array<string, mixed>> $fields Default address fields.
	 * @return array<string, array<string, mixed>>
	 */
	public static function adjustDefaults( array $fields ): array {
		// Make phone always required.
		if ( isset( $fields['phone'] ) ) {
			$fields['phone']['required'] = true;
		}

		// Remove country/state/postcode from default set.
		unset(
			$fields['company'],
			$fields['address_2'],
			$fields['postcode'],
			$fields['country'],
			$fields['state']
		);

		return $fields;
	}
}
