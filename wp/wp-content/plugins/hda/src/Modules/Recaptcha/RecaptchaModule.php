<?php
/**
 * CAPTCHA Module — Unified CAPTCHA integration.
 *
 * Supports Google reCAPTCHA v2 (Checkbox) and Cloudflare Turnstile.
 * Stores API keys, resolves the active provider, and delegates
 * form protection to FormProtection.
 *
 * @package HDAddons\Modules\Recaptcha
 */

namespace HDAddons\Modules\Recaptcha;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;
use HDAddons\Modules\Recaptcha\FormProtection;
use HDAddons\Modules\Recaptcha\Provider\CaptchaProviderInterface;
use HDAddons\Modules\Recaptcha\Provider\RecaptchaV2Provider;
use HDAddons\Modules\Recaptcha\Provider\TurnstileProvider;

defined( 'ABSPATH' ) || exit;

final class RecaptchaModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'recaptcha';
	}

	public static function title(): string {
		return 'CAPTCHA';
	}

	public static function description(): string {
		return 'reCAPTCHA v2 and Cloudflare Turnstile for forms.';
	}

	public static function group(): string {
		return 'security';
	}


	// ── Constants ───────────────────────────────────


	// API keys.
	public const KEY_V2_SITE_KEY          = 'recaptcha_v2_site_key';
	public const KEY_V2_SECRET_KEY        = 'recaptcha_v2_secret_key';
	public const KEY_TURNSTILE_SITE_KEY   = 'turnstile_site_key';
	public const KEY_TURNSTILE_SECRET_KEY = 'turnstile_secret_key';
	public const KEY_ALLOWLIST_IPS        = 'recaptcha_allowlist_ips';

	// Provider & form protection.
	public const KEY_CAPTCHA_PROVIDER = 'captcha_provider';
	public const KEY_PROTECT_FORMS    = 'protect_forms';


	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Resolve the active provider and initialize form protection.
		$this->initFormProtection( self::getCachedOptions() );
	}


	// ── Provider Resolution ─────────────────────────

	/**
	 * Resolve the active CAPTCHA provider from settings.
	 */
	public static function resolveProvider( array $options ): ?CaptchaProviderInterface {
		$providerKey = $options[ self::KEY_CAPTCHA_PROVIDER ] ?? '';

		if ( empty( $providerKey ) ) {
			return null;
		}

		return match ( $providerKey ) {
			'recaptcha_v2' => self::resolveRecaptchaV2( $options ),
			'turnstile'    => self::resolveTurnstile( $options ),
			default        => null,
		};
	}

	private static function resolveRecaptchaV2( array $options ): ?RecaptchaV2Provider {
		$siteKey   = $options[ self::KEY_V2_SITE_KEY ] ?? '';
		$secretKey = $options[ self::KEY_V2_SECRET_KEY ] ?? '';

		if ( empty( $siteKey ) || empty( $secretKey ) ) {
			return null;
		}

		return new RecaptchaV2Provider( $siteKey, $secretKey );
	}

	private static function resolveTurnstile( array $options ): ?TurnstileProvider {
		$siteKey   = $options[ self::KEY_TURNSTILE_SITE_KEY ] ?? '';
		$secretKey = $options[ self::KEY_TURNSTILE_SECRET_KEY ] ?? '';

		if ( empty( $siteKey ) || empty( $secretKey ) ) {
			return null;
		}

		return new TurnstileProvider( $siteKey, $secretKey );
	}

	// ── Form Protection ─────────────────────────────

	/**
	 * Initialize form protection if provider is active and forms toggle is enabled.
	 */
	private function initFormProtection( array $options ): void {
		// Emergency bypass via constants.
		if (
			( defined( 'HDA_DISABLE_LOGIN_CAPTCHA' ) && \HDA_DISABLE_LOGIN_CAPTCHA )
			|| ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY )
		) {
			return;
		}

		$provider = self::resolveProvider( $options );
		if ( ! $provider ) {
			return;
		}

		// Check IP allowlist — skip CAPTCHA for allowlisted IPs.
		$allowlistIps = $options[ self::KEY_ALLOWLIST_IPS ] ?? [];
		$ip           = Helper::ipAddress();
		if ( ! empty( $allowlistIps ) && $ip && Helper::ipMatchesAny( $ip, $allowlistIps ) ) {
			return;
		}

		// Build form flags map.
		$protectAll = ! empty( $options[ self::KEY_PROTECT_FORMS ] );

		if ( ! $protectAll ) {
			return;
		}

		new FormProtection( $provider );
	}



	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$fields = [
			self::KEY_V2_SITE_KEY,
			self::KEY_V2_SECRET_KEY,
			self::KEY_TURNSTILE_SITE_KEY,
			self::KEY_TURNSTILE_SECRET_KEY,
			self::KEY_ALLOWLIST_IPS,
			self::KEY_CAPTCHA_PROVIDER,
			self::KEY_PROTECT_FORMS,
		];

		$options = self::extractFields( $data, $fields, true );
		self::saveOrRemove( self::optionKey(), $options );
		self::resetCache();
	}
}
