<?php
/**
 * LINE Gateway — LINE Messaging API
 *
 * Sends OTP via LINE Bot push messages.
 * Free tier: 500 messages/month (sufficient for login OTP).
 *
 * Setup:
 *   1. Create a LINE channel at https://developers.line.biz/console/
 *   2. Choose "Messaging API" channel type
 *   3. Get the Channel Access Token (long-lived)
 *   4. Users must add the bot as a friend and send a message
 *   5. The bot receives the user's LINE User ID from the webhook
 *
 * Docs: https://developers.line.biz/en/reference/messaging-api/#send-push-message
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class LineGateway extends AbstractGateway {

	/**
	 * LINE Messaging API push message endpoint.
	 */
	private const API_URL = 'https://api.line.me/v2/bot/message/push';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'line';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'LINE', 'hda' );
	}

	/**
	 * LINE identifies users by their LINE User ID (received via webhook).
	 *
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'line_user_id';
	}

	/**
	 * @return bool
	 */
	public function validateConfig(): bool {
		$channel_access_token = $this->getConfig( 'channel_access_token' );

		if ( empty( $channel_access_token ) ) {
			$this->setError( __( 'LINE Channel Access Token is required.', 'hda' ) );

			return false;
		}

		return true;
	}

	/**
	 * @param string $to  LINE User ID
	 * @param string $otp The OTP code
	 *
	 * @return bool
	 */
	public function send( string $to, string $otp ): bool {
		if ( ! $this->validateConfig() ) {
			return false;
		}

		if ( empty( $to ) ) {
			$this->setError( __( 'User has no LINE User ID configured.', 'hda' ) );

			return false;
		}

		$channel_access_token = $this->getConfig( 'channel_access_token' );
		$message              = $this->formatMessage( $otp );

		$result = $this->makeRequest(
			self::API_URL,
			[
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $channel_access_token,
			],
			wp_json_encode(
				[
					'to'       => $to,
					'messages' => [
						[
							'type' => 'text',
							'text' => $message,
						],
					],
				]
			),
		);

		// LINE returns 200 with empty JSON object {} on success
		if ( ! $result['ok'] ) {
			if ( $result['body'] !== null ) {
				$this->setError( $result['body']['message'] ?? __( 'Unknown LINE error', 'hda' ) );
			} elseif ( empty( $this->lastError ) ) {
				$this->setError( __( 'Unknown LINE error', 'hda' ) );
			}

			return false;
		}

		return true;
	}
}
