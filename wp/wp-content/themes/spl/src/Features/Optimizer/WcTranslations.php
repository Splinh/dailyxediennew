<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Vietnamese translations for WooCommerce UI strings.
 *
 * Overrides specific WooCommerce English strings that are commonly
 * untranslated or incorrectly translated in Vietnamese e-commerce.
 *
 * @package SPL
 */
final class WcTranslations {

	/**
	 * WooCommerce string map: English → Vietnamese.
	 *
	 * @var array<string, string>
	 */
	private const MAP = [
		'Select options'       => 'Tùy chọn',
		'Add to cart'          => 'Thêm vào giỏ',
		'Read more'            => 'Xem chi tiết',
		'Out of stock'         => 'Hết hàng',
		'In stock'             => 'Còn hàng',
		'Sale!'                => 'Giảm giá!',
		'Description'          => 'Mô tả',
		'Additional information' => 'Thông tin bổ sung',
		'Reviews'              => 'Đánh giá',
		'Related products'     => 'Sản phẩm liên quan',
		'You may also like&hellip;' => 'Có thể bạn cũng thích&hellip;',
	];

	public static function register(): void {
		add_filter( 'gettext', [ self::class, 'translate' ], 10, 3 );
	}

	/**
	 * Translate WooCommerce UI strings to Vietnamese.
	 *
	 * @param string $translation Translated string.
	 * @param string $text        Original string.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function translate( string $translation, string $text, string $domain ): string {
		if ( 'woocommerce' !== $domain ) {
			return $translation;
		}

		return self::MAP[ $text ] ?? $translation;
	}
}
