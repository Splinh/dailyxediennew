<?php
/**
 * Form Configuration Handler
 *
 * Reads form config from the theme settings filter and provides
 * accessors for per-type configuration (CAPTCHA, recipients, templates).
 *
 * @package HD\Modules\Form
 */

namespace HD\Modules\Form;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

class FormConfig {
	private const OPTION_KEY = 'hd_form_settings';

	private static ?array $cache = null;

	public static function register(): void {
		add_action( 'add_option_' . self::OPTION_KEY, [ self::class, 'resetCache' ], 10, 0 );
		add_action( 'update_option_' . self::OPTION_KEY, [ self::class, 'resetCache' ], 10, 0 );
		add_action( 'delete_option_' . self::OPTION_KEY, [ self::class, 'resetCache' ], 10, 0 );
	}

	/**
	 * Get all form configurations.
	 *
	 * @return array
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$config = Helper::filterSettingOptions( 'form_config' );
		$config = is_array( $config ) ? $config : [];

		// Merge admin settings into config. Admin-managed keys override code defaults.
		$admin = get_option( self::OPTION_KEY, [] );

		if ( isset( $admin['notifications'] ) && is_array( $admin['notifications'] ) ) {
			$config['notifications'] = self::mergeSettings(
				$config['notifications'] ?? [],
				$admin['notifications']
			);
		}

		$mergeKeys = [ 'captcha', 'cleanup', 'email_filter', 'default_email_to', 'spam_check', 'min_submit_time', 'max_render_age', 'phone_vn_only' ];
		foreach ( $mergeKeys as $key ) {
			if ( isset( $admin[ $key ] ) ) {
				$config[ $key ] = $admin[ $key ];
			}
		}

		self::$cache = apply_filters( 'hd_form_config', $config );

		return self::$cache;
	}

	/**
	 * Get configuration for a specific form type.
	 *
	 * @param string $type Form type slug.
	 *
	 * @return array|null
	 */
	public static function getFormType( string $type ): ?array {
		$config = static::all();

		return $config['form_types'][ $type ] ?? null;
	}

	/**
	 * Get the CAPTCHA provider configured for a form type.
	 *
	 * @param string $formType Form type slug.
	 *
	 * @return string Provider slug (e.g. 'turnstile', 'recaptcha_v2', 'recaptcha_v3', 'none').
	 */
	public static function getCaptchaProvider( string $formType ): string {
		$formConfig = static::getFormType( $formType );
		$config     = static::all();

		return $formConfig['captcha'] ?? $config['captcha']['default_provider'] ?? 'none';
	}

	/**
	 * Get email recipients for a form type (falls back to default).
	 *
	 * @param string $formType Form type slug.
	 *
	 * @return array<string>
	 */
	public static function getEmailRecipients( string $formType ): array {
		$formConfig = static::getFormType( $formType );
		$recipients = $formConfig['email_to'] ?? [];

		if ( empty( $recipients ) ) {
			$config     = static::all();
			$recipients = ! empty( $config['default_email_to'] )
				? $config['default_email_to']
				: [ Helper::getOption( 'admin_email' ) ];
		}

		return $recipients;
	}

	/**
	 * Get email template for a form type.
	 *
	 * @param string $formType Form type slug.
	 *
	 * @return string Template name (without extension).
	 */
	public static function getEmailTemplate( string $formType ): string {
		$formConfig = static::getFormType( $formType );

		return $formConfig['email_template'] ?? 'default';
	}

	/**
	 * Reset cached config (call after settings update).
	 */
	public static function resetCache(): void {
		self::$cache = null;
	}

	private static function mergeSettings( array $base, array $override ): array {
		foreach ( $override as $key => $value ) {
			if (
				is_array( $value )
				&& isset( $base[ $key ] )
				&& is_array( $base[ $key ] )
				&& ! array_is_list( $value )
				&& ! array_is_list( $base[ $key ] )
			) {
				$base[ $key ] = self::mergeSettings( $base[ $key ], $value );
				continue;
			}

			$base[ $key ] = $value;
		}

		return $base;
	}
}
