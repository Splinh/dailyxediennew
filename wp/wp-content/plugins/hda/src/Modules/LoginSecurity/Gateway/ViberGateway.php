<?php
/**
 * Viber Gateway — Viber Bot API
 *
 * Sends OTP via Viber Bot 1:1 messages. FREE & unlimited for 1:1 messages.
 * User must follow/subscribe to the bot first to receive messages.
 *
 * Setup:
 *   1. Create a Viber Bot at https://partners.viber.com/account/create-bot-account
 *   2. Get the Auth Token from the bot dashboard
 *   3. Users must send a message to the bot first (to establish subscription)
 *   4. The bot receives the user's Viber User ID from the webhook
 *
 * Docs: https://developers.viber.com/docs/api/rest-bot-api/#send-message
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class ViberGateway extends AbstractGateway {

	/**
	 * Viber Bot API endpoint for sending messages.
	 */
	private const API_URL = 'https://chatapi.viber.com/pa/send_message';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'viber';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'Viber', 'hda' );
	}

	/**
	 * Viber identifies users by their Viber User ID (received via webhook when user subscribes).
	 *
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'viber_user_id';
	}

	/**
	 * @return bool
	 */
	public function validateConfig(): bool {
		$auth_token = $this->getConfig( 'auth_token' );

		if ( empty( $auth_token ) ) {
			$this->setError( __( 'Viber Bot Auth Token is required.', 'hda' ) );

			return false;
		}

		return true;
	}

	/**
	 * @param string $to  Viber User ID
	 * @param string $otp The OTP code
	 *
	 * @return bool
	 */
	public function send( string $to, string $otp ): bool {
		if ( ! $this->validateConfig() ) {
			return false;
		}

		if ( empty( $to ) ) {
			$this->setError( __( 'User has no Viber User ID configured.', 'hda' ) );

			return false;
		}

		$auth_token  = $this->getConfig( 'auth_token' );
		$sender_name = $this->getConfig( 'sender_name', get_bloginfo( 'name' ) );
		$message     = $this->formatMessage( $otp );

		$result = $this->makeRequest(
			self::API_URL,
			[
				'Content-Type'       => 'application/json',
				'X-Viber-Auth-Token' => $auth_token,
			],
			wp_json_encode(
				[
					'receiver'        => $to,
					'min_api_version' => 1,
					'sender'          => [
						'name' => $sender_name,
					],
					'tracking_data'   => 'otp',
					'type'            => 'text',
					'text'            => $message,
				]
			),
		);

		if ( $result['body'] === null ) {
			if ( empty( $this->lastError ) ) {
				$this->setError( __( 'Viber returned an invalid response.', 'hda' ) );
			}

			return false;
		}

		$body = $result['body'];

		// Viber API returns status 0 on success
		if ( ( $body['status'] ?? -1 ) !== 0 ) {
			$this->setError( $body['status_message'] ?? __( 'Unknown Viber error', 'hda' ) );

			return false;
		}

		return true;
	}
}
