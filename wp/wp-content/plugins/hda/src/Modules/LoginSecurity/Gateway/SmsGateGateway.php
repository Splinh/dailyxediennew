<?php
/**
 * SMSGate Gateway — Android SMS Gateway (sms-gate.app)
 *
 * Turns an Android phone into an SMS gateway via REST API.
 * Free & unlimited — uses the phone's SIM to send real SMS.
 *
 * Modes:
 *   - Cloud: https://api.sms-gate.app (default, accessible anywhere)
 *   - Local: http://<device-ip>:8080 (same network only)
 *   - Private: self-hosted server URL
 *
 * Docs: https://docs.sms-gate.app/integration/api/
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class SmsGateGateway extends AbstractGateway {

	/**
	 * Default cloud API endpoint.
	 */
	private const DEFAULT_API_URL = 'https://api.sms-gate.app';

	/**
	 * API path for sending messages.
	 */
	private const SEND_PATH = '/3rdparty/v1/message';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'smsgate';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'SMSGate', 'hda' );
	}

	/**
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'phone_number';
	}

	/**
	 * @return bool
	 */
	public function validateConfig(): bool {
		$username = $this->getConfig( 'username' );
		$password = $this->getConfig( 'password' );

		if ( empty( $username ) ) {
			$this->setError( __( 'SMSGate username is required.', 'hda' ) );

			return false;
		}

		if ( empty( $password ) ) {
			$this->setError( __( 'SMSGate password is required.', 'hda' ) );

			return false;
		}

		return true;
	}

	/**
	 * @param string $to
	 * @param string $otp
	 *
	 * @return bool
	 */
	public function send( string $to, string $otp ): bool {
		if ( ! $this->validateConfig() ) {
			return false;
		}

		if ( empty( $to ) ) {
			$this->setError( __( 'User has no phone number configured.', 'hda' ) );

			return false;
		}

		$username = $this->getConfig( 'username' );
		$password = $this->getConfig( 'password' );
		$message  = $this->formatMessage( $otp );

		// Format phone number to international format
		$phone = $this->formatPhoneNumber( $to );

		// getConfig() already falls back to DEFAULT_API_URL when value is empty.
		$server_url = $this->getConfig( 'server_url', self::DEFAULT_API_URL );
		$url        = rtrim( $server_url, '/' ) . self::SEND_PATH;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic Auth
		$result = $this->makeRequest(
			$url,
			[
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
			],
			wp_json_encode(
				[
					'message'      => $message,
					'phoneNumbers' => [ $phone ],
				]
			),
		);

		if ( ! $result['ok'] ) {
			if ( $result['body'] !== null ) {
				$this->setError( $result['body']['message'] ?? __( 'Unknown SMSGate error', 'hda' ) );
			} elseif ( empty( $this->lastError ) ) {
				$this->setError( __( 'Unknown SMSGate error', 'hda' ) );
			}

			return false;
		}

		$body = $result['body'];

		if ( $body === null ) {
			$this->setError( __( 'SMSGate returned an invalid response.', 'hda' ) );

			return false;
		}

		// Check state — "Pending" or "Sent" both indicate success
		$state = $body['state'] ?? '';
		if ( ! in_array( $state, [ 'Pending', 'Sent', 'Delivered' ], true ) ) {
			$this->setError(
				/* translators: %s: message state from API */
				sprintf( __( 'SMSGate message state: %s', 'hda' ), $state ?: 'unknown' )
			);

			return false;
		}

		return true;
	}

	/**
	 * Format phone number to international format with +.
	 *
	 * @param string $phone
	 *
	 * @return string
	 */
	private function formatPhoneNumber( string $phone ): string {
		// Remove all non-digit characters except leading +
		$phone = preg_replace( '/(?!^\+)\D/', '', $phone );

		// Already international format
		if ( str_starts_with( $phone, '+' ) ) {
			return $phone;
		}

		// If starts with 0 (Vietnam local), convert to +84
		if ( str_starts_with( $phone, '0' ) ) {
			return '+84' . substr( $phone, 1 );
		}

		return '+' . $phone;
	}
}
