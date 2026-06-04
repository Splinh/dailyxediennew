<?php
/**
 * Abstract Base Gateway
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

use HDAddons\Modules\LoginSecurity\LoginSecurityModule;

\defined( 'ABSPATH' ) || exit;

abstract class AbstractGateway implements GatewayInterface {

	/**
	 * Gateway configuration
	 *
	 * @var array
	 */
	protected array $config = [];

	/**
	 * Last error message
	 *
	 * @var string
	 */
	protected string $lastError = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$options      = LoginSecurityModule::getCachedOptions();
		$gateway_name = $this->getName();
		$this->config = $options[ LoginSecurityModule::KEY_OTP_GATEWAY_CONFIG ][ $gateway_name ] ?? [];
	}

	/**
	 * Get configuration value.
	 *
	 * Returns the default if the key is missing OR its value is an empty string.
	 *
	 * @param string $key     Config key
	 * @param mixed  $default Default value
	 *
	 * @return mixed
	 */
	protected function getConfig( string $key, mixed $default = '' ): mixed {
		$value = $this->config[ $key ] ?? null;

		// Treat missing key AND empty-string the same → use default.
		if ( $value === null || $value === '' ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Set error message
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	protected function setError( string $message ): void {
		$this->lastError = $message;
	}

	/**
	 * Get last error
	 *
	 * @return string
	 */
	public function getLastError(): string {
		return $this->lastError;
	}

	/**
	 * Format OTP message (plain text, no HTML/Markdown).
	 *
	 * @param string $otp
	 *
	 * @return string
	 */
	protected function formatMessage( string $otp ): string {
		return sprintf(
			/* translators: %1$s: site name, %2$s: OTP code */
			__( '[%1$s] Your login verification code is: %2$s. Valid for 5 minutes.', 'hda' ),
			get_bloginfo( 'name' ),
			$otp
		);
	}

	/**
	 * Make an HTTP POST request and return the parsed response.
	 *
	 * Centralises wp_remote_post → WP_Error check → JSON decode
	 * so individual gateways only deal with the API-specific logic.
	 *
	 * @param string               $url     Request URL
	 * @param array<string,string> $headers HTTP headers (Content-Type etc.)
	 * @param array|string|null    $body    Request body (array = form-encoded, string = raw)
	 * @param int                  $timeout Seconds (default 30)
	 *
	 * @return array{ok: bool, status: int, body: array|null}
	 */
	protected function makeRequest(
		string $url,
		array $headers = [],
		array|string|null $body = null,
		int $timeout = 30,
	): array {
		$args = [
			'timeout' => $timeout,
			'headers' => $headers,
		];

		if ( $body !== null ) {
			$args['body'] = $body;
		}

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->setError( $response->get_error_message() );

			return [
				'ok'     => false,
				'status' => 0,
				'body'   => null,
			];
		}

		$statusCode = wp_remote_retrieve_response_code( $response );
		$rawBody    = wp_remote_retrieve_body( $response );
		$parsed     = $this->parseJson( $rawBody );

		return [
			'ok'     => $statusCode >= 200 && $statusCode < 300,
			'status' => $statusCode,
			'body'   => $parsed,
		];
	}

	/**
	 * Safely parse a JSON string — never throws.
	 *
	 * @param string $json Raw JSON string
	 *
	 * @return array|null Parsed array or null on failure
	 */
	protected function parseJson( string $json ): ?array {
		if ( $json === '' ) {
			return null;
		}

		try {
			$decoded = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );

			return is_array( $decoded ) ? $decoded : null;
		} catch ( \JsonException ) {
			return null;
		}
	}
}
