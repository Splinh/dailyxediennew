<?php
/**
 * Shared verification-response parsing for CAPTCHA providers.
 *
 * Extracts the duplicated parseResponse() logic from
 * RecaptchaV2Provider and TurnstileProvider into a single trait.
 * Requires the consuming class to implement CaptchaProviderInterface
 * (specifically getName()) for log context.
 *
 * @package HDAddons\Modules\Recaptcha\Provider
 */

namespace HDAddons\Modules\Recaptcha\Provider;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

trait VerifiesResponseTrait {

	/**
	 * Parse the CAPTCHA verification API response.
	 *
	 * @param array|\WP_Error $response HTTP response from wp_remote_post().
	 *
	 * @return bool True if the CAPTCHA was solved successfully.
	 */
	private function parseResponse( array|\WP_Error $response ): bool {
		$name = $this->getName();

		if ( is_wp_error( $response ) ) {
			Helper::errorLog( "[HDA] {$name} verification failed: " . $response->get_error_message() );

			return false; // Fail closed.
		}

		$body = wp_remote_retrieve_body( $response );

		try {
			$result = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			Helper::errorLog( "[HDA] {$name} returned invalid JSON: " . $e->getMessage() );

			return false;
		}

		return is_array( $result ) && ! empty( $result['success'] );
	}
}
