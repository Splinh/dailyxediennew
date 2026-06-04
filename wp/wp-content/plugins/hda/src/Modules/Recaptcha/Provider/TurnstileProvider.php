<?php
/**
 * Cloudflare Turnstile Provider.
 *
 * Handles rendering and verification for Cloudflare Turnstile.
 *
 * @package HDAddons\Modules\Recaptcha\Provider
 */

namespace HDAddons\Modules\Recaptcha\Provider;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class TurnstileProvider implements CaptchaProviderInterface {

	use VerifiesResponseTrait;

	// ------------------------------------------------------

	/**
	 * @param string $siteKey   Turnstile site key.
	 * @param string $secretKey Turnstile secret key.
	 */
	public function __construct(
		private readonly string $siteKey,
		private readonly string $secretKey,
	) {}

	// ------------------------------------------------------

	/** @inheritDoc */
	public function getName(): string {
		return 'Cloudflare Turnstile';
	}

	/** @inheritDoc */
	public function enqueueAssets(): void {
		wp_enqueue_script(
			'hda-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			[],
			null,
			true
		);
	}

	/** @inheritDoc */
	public function renderWidget(): void {
		printf(
			'<div style="margin-bottom:16px;"><div class="cf-turnstile" data-sitekey="%s" data-theme="auto"></div></div>',
			esc_attr( $this->siteKey )
		);
	}

	/** @inheritDoc */
	public function getResponseToken(): string {
		return sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );
	}

	/** @inheritDoc */
	public function verifyToken( string $token ): bool {
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
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
