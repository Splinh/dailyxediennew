<?php
/**
 * Discord Gateway — Discord Bot API
 *
 * Sends OTP via Discord Bot DM (Direct Message).
 * FREE & unlimited. User must share a server with the bot.
 *
 * Setup:
 *   1. Go to https://discord.com/developers/applications → New Application
 *   2. Go to Bot tab → create bot → copy Bot Token
 *   3. Enable "Message Content Intent" in Bot → Privileged Gateway Intents
 *   4. Invite bot to a shared server using OAuth2 URL with `bot` scope
 *   5. Users provide their Discord User ID (enable Developer Mode → right-click → Copy ID)
 *
 * Flow: Create DM Channel → Send Message to that channel.
 *
 * Docs: https://discord.com/developers/docs/resources/user#create-dm
 *       https://discord.com/developers/docs/resources/message#create-message
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class DiscordGateway extends AbstractGateway {

	/**
	 * Discord API base URL.
	 */
	private const API_BASE = 'https://discord.com/api/v10';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'discord';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'Discord', 'hda' );
	}

	/**
	 * Discord identifies users by their snowflake User ID.
	 *
	 * @return string
	 */
	public function getUserMetaKey(): string {
		return 'discord_user_id';
	}

	/**
	 * @return bool
	 */
	public function validateConfig(): bool {
		$bot_token = $this->getConfig( 'bot_token' );

		if ( empty( $bot_token ) ) {
			$this->setError( __( 'Discord Bot Token is required.', 'hda' ) );

			return false;
		}

		return true;
	}

	/**
	 * @param string $to  Discord User ID (snowflake)
	 * @param string $otp The OTP code
	 *
	 * @return bool
	 */
	public function send( string $to, string $otp ): bool {
		if ( ! $this->validateConfig() ) {
			return false;
		}

		if ( empty( $to ) ) {
			$this->setError( __( 'User has no Discord User ID configured.', 'hda' ) );

			return false;
		}

		$bot_token = $this->getConfig( 'bot_token' );
		$message   = $this->formatMessage( $otp );

		// Step 1: Create a DM channel with the user
		$dm_channel_id = $this->createDMChannel( $bot_token, $to );
		if ( ! $dm_channel_id ) {
			return false;
		}

		// Step 2: Send the OTP message to the DM channel
		return $this->sendMessage( $bot_token, $dm_channel_id, $message );
	}

	/**
	 * Create a DM channel with a Discord user.
	 *
	 * @param string $bot_token
	 * @param string $user_id Discord User ID
	 *
	 * @return string|null Channel ID on success, null on failure
	 */
	private function createDMChannel( string $bot_token, string $user_id ): ?string {
		$result = $this->makeRequest(
			self::API_BASE . '/users/@me/channels',
			[
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bot ' . $bot_token,
			],
			wp_json_encode(
				[
					'recipient_id' => $user_id,
				]
			),
			15,
		);

		if ( ! $result['ok'] ) {
			if ( empty( $this->lastError ) ) {
				$msg = ( $result['body'] !== null )
					? ( $result['body']['message'] ?? __( 'Failed to create Discord DM channel', 'hda' ) )
					: __( 'Failed to create Discord DM channel', 'hda' );
				$this->setError( $msg );
			}

			return null;
		}

		$body = $result['body'];

		if ( empty( $body['id'] ) ) {
			$this->setError( __( 'Discord DM channel ID missing from response.', 'hda' ) );

			return null;
		}

		return $body['id'];
	}

	/**
	 * Send a message to a Discord channel.
	 *
	 * @param string $bot_token
	 * @param string $channel_id
	 * @param string $message
	 *
	 * @return bool
	 */
	private function sendMessage( string $bot_token, string $channel_id, string $message ): bool {
		$result = $this->makeRequest(
			self::API_BASE . '/channels/' . $channel_id . '/messages',
			[
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bot ' . $bot_token,
			],
			wp_json_encode(
				[
					'content' => $message,
				]
			),
			15,
		);

		if ( ! $result['ok'] ) {
			if ( empty( $this->lastError ) ) {
				$msg = ( $result['body'] !== null )
					? ( $result['body']['message'] ?? __( 'Failed to send Discord message', 'hda' ) )
					: __( 'Failed to send Discord message', 'hda' );
				$this->setError( $msg );
			}

			return false;
		}

		return true;
	}
}
