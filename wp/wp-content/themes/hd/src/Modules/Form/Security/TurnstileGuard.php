<?php
/**
 * Cloudflare Turnstile Guard
 *
 * @package HD\Modules\Form\Security
 */

namespace HD\Modules\Form\Security;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class TurnstileGuard implements CaptchaGuardInterface {
	private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	public function __construct(
		private readonly string $siteKey,
		private readonly string $secretKey,
		private readonly bool $failOpenOnNetworkError = false,
	) {}

	/** @inheritDoc */
	public function verify( string $token, string $ip ): bool {
		if ( empty( $this->secretKey ) || empty( $token ) ) {
			return false;
		}

		$response = wp_remote_post(
			self::VERIFY_URL,
			[
				'body'    => [
					'secret'   => $this->secretKey,
					'response' => $token,
					'remoteip' => $ip,
				],
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) ) {
			Helper::errorLog( '[TurnstileGuard] Network error: ' . $response->get_error_message() );

			return $this->failOpenOnNetworkError;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];

		if ( empty( $body['success'] ) ) {
			$this->logProviderErrorCodes( $body );

			return false;
		}

		return $this->hostnameMatches( $body['hostname'] ?? '' );
	}

	/** @inheritDoc */
	public function renderField(): string {
		if ( empty( $this->siteKey ) ) {
			return '';
		}

		return sprintf(
			'<div class="cf-turnstile" data-sitekey="%s"></div>',
			esc_attr( $this->siteKey )
		);
	}

	/** @inheritDoc */
	public function getScriptUrl(): string {
		return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
	}

	private function hostnameMatches( mixed $hostname ): bool {
		$expected = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$actual   = strtolower( rtrim( (string) $hostname, '.' ) );

		return '' !== $expected && $actual === $expected;
	}

	/**
	 * @param array<string, mixed> $body
	 */
	private function logProviderErrorCodes( array $body ): void {
		$codes = array_filter( array_map( 'strval', (array) ( $body['error-codes'] ?? [] ) ) );
		if ( ! $codes ) {
			return;
		}

		Helper::errorLog( '[TurnstileGuard] Provider errors: ' . implode( ', ', $codes ) );
	}
}
