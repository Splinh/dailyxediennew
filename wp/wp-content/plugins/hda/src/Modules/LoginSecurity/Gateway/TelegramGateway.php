<?php
/**
 * Telegram Gateway - FREE & UNLIMITED
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class TelegramGateway extends AbstractGateway {

	private const API_URL = 'https://api.telegram.org/bot%s/sendMessage';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'telegram';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'Telegram', 'hda' );
	}

	/**
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'telegram_chat_id';
	}

	/**
	 * @return bool
	 */
	public function validateConfig(): bool {
		$bot_token = $this->getConfig( 'bot_token' );

		if ( empty( $bot_token ) ) {
			$this->setError( __( 'Telegram Bot Token is required.', 'hda' ) );

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
			$this->setError( __( 'User has no Telegram Chat ID configured.', 'hda' ) );

			return false;
		}

		$bot_token = $this->getConfig( 'bot_token' );
		$url       = sprintf( self::API_URL, $bot_token );
		$message   = $this->formatMessage( $otp );

		// Use form-encoded body (Telegram accepts both form and JSON).
		// No parse_mode — plain text is safest (avoids issues with site names containing < > &).
		$result = $this->makeRequest(
			$url,
			[],
			[
				'chat_id' => $to,
				'text'    => $message,
			]
		);

		if ( ! $result['ok'] && $result['body'] === null ) {
			// WP_Error already handled by makeRequest()
			return false;
		}

		$body = $result['body'];

		if ( empty( $body['ok'] ) ) {
			$this->setError( $body['description'] ?? __( 'Unknown Telegram error', 'hda' ) );

			return false;
		}

		return true;
	}
}
