<?php
/**
 * Google reCAPTCHA v2 Provider.
 *
 * Handles rendering and verification for reCAPTCHA v2 (Checkbox).
 *
 * @package HDAddons\Modules\Recaptcha\Provider
 */

namespace HDAddons\Modules\Recaptcha\Provider;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class RecaptchaV2Provider implements CaptchaProviderInterface {

	use VerifiesResponseTrait;

	// ------------------------------------------------------

	/**
	 * @param string $siteKey   reCAPTCHA v2 site key.
	 * @param string $secretKey reCAPTCHA v2 secret key.
	 */
	public function __construct(
		private readonly string $siteKey,
		private readonly string $secretKey,
	) {}

	// ------------------------------------------------------

	/** @inheritDoc */
	public function getName(): string {
		return 'Google reCAPTCHA v2';
	}

	/** @inheritDoc */
	public function enqueueAssets(): void {
		wp_enqueue_script(
			'hda-recaptcha-v2',
			'https://www.google.com/recaptcha/api.js',
			[],
			null,
			true
		);
	}

	/** @inheritDoc */
	public function renderWidget(): void {
		printf(
			'<div style="margin-bottom:16px;"><div class="g-recaptcha" data-sitekey="%s"></div></div>',
			esc_attr( $this->siteKey )
		);
	}

	/** @inheritDoc */
	public function getResponseToken(): string {
		return sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ?? '' ) );
	}

	/** @inheritDoc */
	public function verifyToken( string $token ): bool {
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			[
				'body'    => [
					'secret'   => $this->secretKey,
					'response' => $token,
					'remoteip' => Helper::ipAddress(),
				],
				'timeout' => 10,
			]
		);

		return $this->parseResponse( $response );
	}
}
