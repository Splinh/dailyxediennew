<?php
/**
 * Gateway Interface for OTP messaging
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

interface GatewayInterface {

	/**
	 * Send OTP message to recipient
	 *
	 * @param string $to   Recipient identifier (phone, chat_id, etc.)
	 * @param string $otp  The OTP code to send
	 *
	 * @return bool True on success, false on failure
	 */
	public function send( string $to, string $otp ): bool;

	/**
	 * Get the gateway unique identifier
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get the gateway display label
	 *
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * Get the user meta key for recipient identifier
	 * e.g., 'phone_number' for SMS, 'telegram_chat_id' for Telegram
	 *
	 * @return string
	 */
	public function getUserMetaKey(): string;

	/**
	 * Validate gateway configuration
	 *
	 * @return bool True if config is valid
	 */
	public function validateConfig(): bool;

	/**
	 * Get last error message
	 *
	 * @return string
	 */
	public function getLastError(): string;
}
