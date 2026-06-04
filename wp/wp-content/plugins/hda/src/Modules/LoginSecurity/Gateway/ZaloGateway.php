<?php
/**
 * Zalo Gateway
 *
 * Sends OTP via Zalo Bot Platform.
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class ZaloGateway extends AbstractGateway {

	private const API_URL = 'https://bot-api.zaloplatforms.com/bot%s/sendMessage';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'zalo';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'Zalo', 'hda' );
	}

	/**
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'zalo_chat_id';
	}

	/**
	 * Validate that required credentials are configured.
	 *
	 * @return bool
	 */
	public function validateConfig(): bool {
		$bot_token = $this->getConfig( 'bot_token' );

		if ( empty( $bot_token ) ) {
			$this->setError( __( 'Zalo Bot Token is required.', 'hda' ) );

			return false;
		}

		return true;
	}

	/**
	 * Send OTP via Zalo Bot.
	 *
	 * @param string $to  Recipient Zalo Chat ID.
	 * @param string $otp OTP code.
	 *
	 * @return bool
	 */
	public function send( string $to, string $otp ): bool {
		if ( ! $this->validateConfig() ) {
			return false;
		}

		if ( empty( $to ) ) {
			$this->setError( __( 'User has no Zalo Chat ID configured.', 'hda' ) );

			return false;
		}

		$bot_token = $this->getConfig( 'bot_token' );
		$url       = sprintf( self::API_URL, $bot_token );

		/* translators: %s: OTP code */
		$message = sprintf( __( 'Your login verification code is: %s. Do not share this code with anyone.', 'hda' ), $otp );

		$result = $this->makeRequest(
			$url,
			[
				'Content-Type' => 'application/json',
			],
			wp_json_encode(
				[
					'chat_id' => $to,
					'text'    => $message,
				]
			)
		);

		if ( $result['body'] === null ) {
			if ( empty( $this->lastError ) ) {
				$this->setError( __( 'Zalo returned an invalid response.', 'hda' ) );
			}

			return false;
		}

		$body = $result['body'];

		if ( empty( $body['ok'] ) ) {
			$this->setError( $body['description'] ?? __( 'Unknown Zalo error', 'hda' ) );

			return false;
		}

		return true;
	}
}
