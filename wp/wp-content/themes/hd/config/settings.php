<?php
/**
 * Theme Settings Configuration.
 *
 * All theme settings are registered here via the `hd_settings_filter` hook.
 * Consumed by `Helper::filterSettingOptions( $key )` across Features & Modules.
 *
 * To add a new setting section, simply add a new key to the `$settings` array.
 *
 * @package HD
 */

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

add_filter( 'hd_settings_filter', '_hd_settings_filter_callback', 99 );

/**
 * Build the theme settings array.
 *
 * All top-level keys are directly accessible via `Helper::filterSettingOptions( 'key_name' )`.
 * Fires exactly once — the caller caches the result in a static variable.
 *
 * @param array $arr Existing settings from other filters.
 *
 * @return array
 */
function _hd_settings_filter_callback( array $arr ): array {

	$settings = [

		// ── Menus ────────────────────────────────────────────────
		'menus'                => [
			'main-nav'   => __( 'Primary Menu', 'hd' ),
			'policy-nav' => __( 'Term Menu', 'hd' ),
		],

		// ── Performance (Scripts & Styles) ───────────────────────
		'defer_script'         => [
			'admin-bar'       => 'defer',
			'swv'             => 'defer',
			'contact-form-7'  => 'defer',
			'toc-front'       => 'defer',
			'kk-star-ratings' => 'delay',
			'comment-reply'   => 'delay',
			'wp-embed'        => 'delay',
		],
		'defer_style'          => [
			'dashicons',
			'contact-form-7',
			'kk-star-ratings',
		],

		// ── Admin (Dashboard, Menus, ACF) ────────────────────────
		'admin_list_table'     => [
			// term_row_actions: auto-detected in Admin.php (all public taxonomies)
			// post_row_actions: fixed in Admin.php (WP core: user/post/page only)
			'term_thumb_columns'              => [ 'category' ],
			'post_type_exclude_thumb_columns' => [
				'page',
				'filter-set',
				'wpcf7_contact_form',
				'hd_filter_preset',
			],
		],
		'admin_menu'           => [
			'admin_hide_menu'             => [],
			'admin_hide_submenu'          => [],
			'admin_hide_menu_ignore_user' => [ 1 ], // Super admins see all menus
		],
		'acf_menu'             => [
			'acf_menu_items_locations' => [
				'main-nav',
				'header-nav',
				'policy-nav',
			],
			'acf_mega_menu_locations'  => [ 'main-nav' ],
		],

		// ── Security (IP, Users) ─────────────────────────────────
		'security'             => [
			'allowlist_ips_login_access'          => [],      // Allowed IPs for wp-login.php (empty = all)
			'privileged_user_ids'                 => [ 1 ],   // Super admins immune to lockouts & WAF
			'allowed_users_ids_show_plugins'      => [ 1 ],   // Can see the Plugins HDA menu
			'allowed_users_ids_install_plugins'   => [ 1 ],   // Can install/delete plugins & use theme/plugin editor
			'disallowed_users_ids_delete_account' => [ 1 ],   // Protected accounts hidden from other admins
		],

		// ── Social & Contact Links ───────────────────────────────
		'social_follows_links' => _hd_build_social_links(),
		'contact_links'        => _hd_build_contact_links(),

		// ── Misc (Post Types, Aspect Ratio) ──────────────────────
		'post_type_terms'      => _hd_build_post_type_terms(),
		'aspect_ratio'         => [
			'post_type_term'       => _hd_build_aspect_ratio_post_types(),
			'aspect_ratio_default' => [],
		],

		// ── Form Module Config ───────────────────────────────────
		'form_config'          => _hd_build_form_config(),
	];

	// ── WooCommerce additions (row actions & taxonomies auto-detected) ──
	if ( Helper::isWoocommerceActive() ) {
		$settings['admin_list_table']['post_type_exclude_thumb_columns'][] = 'product';
	}

	return array_merge( $arr, $settings );
}

// ── Helper: Social Links Builder ─────────────────────────────────

/**
 * Build social follow links array from definitions.
 *
 * @return array<string, array{name: string, icon: string, placeholder: string, url: string}>
 */
function _hd_build_social_links(): array {
	$defs = [
		'facebook'  => [ 'Facebook', 'facebook', 'https://www.facebook.com' ],
		'instagram' => [ 'Instagram', 'instagram', 'https://www.instagram.com' ],
		'youtube'   => [ 'Youtube', 'youtube', 'https://www.youtube.com' ],
		'x'         => [ 'X (Twitter)', 'x', 'https://x.com' ],
		'tiktok'    => [ 'Tiktok', 'tiktok', 'https://www.tiktok.com' ],
		'telegram'  => [ 'Telegram', 'telegram', 'https://t.me' ],
	];

	$links = [];
	foreach ( $defs as $key => [ $name, $icon, $placeholder ] ) {
		$links[ $key ] = [
			'name'        => $name,
			'icon'        => \hd_svg( $icon ),
			'placeholder' => $placeholder,
			'url'         => '',
		];
	}

	return $links;
}

// ── Helper: Contact Links Builder ────────────────────────────────

/**
 * Build contact links array from definitions.
 *
 * @return array<string, array{name: string, icon: string, value: string, placeholder: string, class: string, target?: string}>
 */
function _hd_build_contact_links(): array {
	$defs = [
		'messenger' => [ 'Messenger', 'messenger', 'https://m.me/username', '_blank' ],
		'zalo'      => [ 'Zalo', 'zalo', 'https://zalo.me/0123456789', '_blank' ],
		'hotline'   => [ 'Hotline', 'phone', '0123456789', null ],
		'tiktok'    => [ 'Tiktok', 'tiktok', 'https://www.tiktok.com/@username', '_blank' ],
		'whatsapp'  => [ 'Whatsapp', 'whatsapp', 'https://wa.me/0123456789', '_blank' ],
		'viber'     => [ 'Viber', 'viber', 'viber://chat?number=0123456789', '_blank' ],
	];

	$links = [];
	foreach ( $defs as $key => [ $name, $icon, $placeholder, $target ] ) {
		$links[ $key ] = [
			'name'        => $name,
			'icon'        => \hd_svg( $icon ),
			'value'       => '',
			'placeholder' => $placeholder,
			'class'       => str_replace( '_', '-', $key ),
		];

		if ( $target ) {
			$links[ $key ]['target'] = $target;
		}
	}

	return $links;
}

// ── Helper: Form Module Config Builder ───────────────────────────

/**
 * Build form configuration array.
 *
 * @return array
 */
function _hd_build_form_config(): array {
	return [
		// Master toggle — set to false to fully disable the HD Form module.
		'enabled'         => true,

		// Form type registry.
		'form_types'      => [
			'contact' => [
				'label'          => __( 'Liên hệ', 'hd' ),
				'required'       => [ 'name', 'phone' ],
				'captcha'        => 'none',
				'spam_check'     => true,
				'email_to'       => [],
				'email_template' => 'contact',
			],
			'service' => [
				'label'          => __( 'Đăng ký dịch vụ', 'hd' ),
				'required'       => [ 'name', 'phone' ],
				'captcha'        => 'none',
				'spam_check'     => true,
				'email_to'       => [],
				'email_template' => 'service',
			],
		],

		// Notification channel defaults. Admin settings can override these values.
		'notifications'   => [
			'channels' => [
				'email'    => [
					'enabled' => true,
				],
				'telegram' => [
					'enabled'   => false,
					'bot_token' => '',
					'chat_id'   => '',
				],
				'viber'    => [
					'enabled'    => false,
					'auth_token' => '',
					'receiver'   => '',
					'sender'     => [
						'name'   => 'HD Notify',
						'avatar' => '',
					],
				],
				'zalo'     => [
					'enabled'   => false,
					'bot_token' => '',
					'chat_id'   => '',
				],
			],
		],

		// Bot timing guard defaults. Admin settings can override these values.
		'min_submit_time' => 3,
		'max_render_age'  => 1800,
	];
}
