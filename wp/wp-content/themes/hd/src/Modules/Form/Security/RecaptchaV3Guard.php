<?php
/**
 * Google reCAPTCHA v3 Guard — Invisible, score-based
 *
 * @package HD\Modules\Form\Security
 */

namespace HD\Modules\Form\Security;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class RecaptchaV3Guard implements CaptchaGuardInterface {
	private const VERIFY_URL    = 'https://www.google.com/recaptcha/api/siteverify';
	private const DEFAULT_SCORE = 0.5;

	public function __construct(
		private readonly string $siteKey,
		private readonly string $secretKey,
		private readonly float $scoreThreshold = self::DEFAULT_SCORE,
		private readonly string $expectedAction = 'form_submit',
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
			Helper::errorLog( '[RecaptchaV3Guard] Network error: ' . $response->get_error_message() );

			return $this->failOpenOnNetworkError;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];

		if ( empty( $body['success'] ) ) {
			$this->logProviderErrorCodes( $body );

			return false;
		}

		if ( ! $this->hostnameMatches( $body['hostname'] ?? '' ) ) {
			return false;
		}

		if ( $this->expectedAction !== (string) ( $body['action'] ?? '' ) ) {
			return false;
		}

		if ( ! $this->challengeTimestampIsFresh( $body['challenge_ts'] ?? '' ) ) {
			return false;
		}

		// v3 returns a score (0.0 = bot, 1.0 = human).
		$score = (float) ( $body['score'] ?? 0.0 );

		return $score >= $this->scoreThreshold;
	}

	/** @inheritDoc */
	public function renderField(): string {
		// v3 is invisible — no visible widget needed.
		return '';
	}

	/** @inheritDoc */
	public function getScriptUrl(): string {
		if ( empty( $this->siteKey ) ) {
			return '';
		}

		return sprintf(
			'https://www.google.com/recaptcha/api.js?render=%s',
			rawurlencode( $this->siteKey )
		);
	}

	private function hostnameMatches( mixed $hostname ): bool {
		$expected = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$actual   = strtolower( rtrim( (string) $hostname, '.' ) );

		return '' !== $expected && $actual === $expected;
	}

	private function challengeTimestampIsFresh( mixed $timestamp ): bool {
		if ( ! is_string( $timestamp ) || '' === $timestamp ) {
			return false;
		}

		$issuedAt = strtotime( $timestamp );
		if ( false === $issuedAt ) {
			return false;
		}

		$age = time() - $issuedAt;

		return $age >= -60 && $age <= 600;
	}

	/**
	 * @param array<string, mixed> $body
	 */
	private function logProviderErrorCodes( array $body ): void {
		$codes = array_filter( array_map( 'strval', (array) ( $body['error-codes'] ?? [] ) ) );
		if ( ! $codes ) {
			return;
		}

		Helper::errorLog( '[RecaptchaV3Guard] Provider errors: ' . implode( ', ', $codes ) );
	}
}
