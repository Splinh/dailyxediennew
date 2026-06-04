<?php
/**
 * OTP Sender — delivery logic for email and SMS/gateway OTP codes.
 *
 * Extracted from LoginOtpVerification to separate "send" concern
 * from "verify" concern (Single Responsibility).
 *
 * @package HDAddons\Modules\LoginSecurity
 */

namespace HDAddons\Modules\LoginSecurity;

use HDAddons\Helper;

use HDAddons\Modules\LoginSecurity\Gateway\GatewayFactory;
use HDAddons\Modules\LoginSecurity\Gateway\GatewayInterface;

\defined( 'ABSPATH' ) || exit;

final class OtpSender {
	/**
	 * Cached gateway instance for the current request.
	 *
	 * @var GatewayInterface|null|false null=not resolved, false=unavailable
	 */
	private GatewayInterface|null|false $cachedGateway = null;

	/* ---------- PUBLIC API ----------------------------------------------- */

	/**
	 * Send an OTP to user via configured channel (email or SMS gateway).
	 *
	 * Handles: cooldown check, OTP generation, channel routing,
	 * fallback (SMS → email), and transient storage.
	 *
	 * @param \WP_User $user Target user.
	 *
	 * @return bool|null true=sent, false=error, null=cooldown active
	 * @throws \Exception
	 */
	public function send( \WP_User $user ): ?bool {
		// Check cooldown
		$last_sent = (int) get_user_meta( $user->ID, LoginOtpVerification::META_LASTSEND, true );
		if ( $last_sent && ( time() - $last_sent ) < LoginOtpVerification::RESEND_INTERVAL ) {
			return null;
		}

		// Generate OTP
		$otp = str_pad( (string) random_int( 0, ( 10 ** LoginOtpVerification::OTP_DIGITS ) - 1 ), LoginOtpVerification::OTP_DIGITS, '0', STR_PAD_LEFT );

		// Send via appropriate channel
		$mode = $this->getMode();
		$sent = ( 'sms' === $mode )
			? $this->_sendViaSms( $user, $otp )
			: $this->_sendViaEmail( $user, $otp );

		if ( ! $sent ) {
			return false;
		}

		// Success — store cooldown and transients
		update_user_meta( $user->ID, LoginOtpVerification::META_LASTSEND, time() );
		set_transient( sprintf( LoginOtpVerification::KEY_OTP, $user->ID ), $this->hashOtp( $otp ), LoginOtpVerification::OTP_LIFETIME );
		set_transient( sprintf( LoginOtpVerification::KEY_ATTEMPT, $user->ID ), 0, LoginOtpVerification::OTP_LIFETIME );

		return true;
	}

	/**
	 * Get the current OTP mode (email, sms, or totp).
	 *
	 * @return string 'email', 'sms', or 'totp'
	 */
	public function getMode(): string {
		$opt = LoginSecurityModule::getCachedOptions();

		if ( isset( $opt[ LoginSecurityModule::KEY_OTP_MODE ] ) && 'disabled' !== $opt[ LoginSecurityModule::KEY_OTP_MODE ] ) {
			return $opt[ LoginSecurityModule::KEY_OTP_MODE ];
		}

		return 'email';
	}

	/**
	 * Get the channel label for display (e.g., "Telegram", "Email").
	 *
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	public function getChannelLabel( \WP_User $user ): string {
		if ( 'sms' !== $this->getMode() ) {
			return __( 'Email', 'hda' );
		}

		$gateway = $this->_getGateway();
		if ( ! $gateway ) {
			return __( 'Email', 'hda' );
		}

		$recipient = get_user_meta( $user->ID, $gateway->getUserMetaKey(), true );

		return empty( $recipient )
			? __( 'Email', 'hda' )
			: $gateway->getLabel();
	}

	/**
	 * Mask the recipient for display (e.g., "***5678", "d***@gmail.com").
	 *
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	public function maskRecipient( \WP_User $user ): string {
		if ( 'sms' !== $this->getMode() ) {
			return $this->_maskEmail( $user->user_email );
		}

		$gateway = $this->_getGateway();
		if ( ! $gateway ) {
			return $this->_maskEmail( $user->user_email );
		}

		$recipient = get_user_meta( $user->ID, $gateway->getUserMetaKey(), true );

		if ( empty( $recipient ) ) {
			return $this->_maskEmail( $user->user_email );
		}

		// Mask phone/chat_id: show last 4 chars
		$length = strlen( $recipient );

		return $length <= 4
			? str_repeat( '*', $length )
			: str_repeat( '*', $length - 4 ) . substr( $recipient, -4 );
	}

	/**
	 * Create a secure hash of the OTP.
	 *
	 * @param string $otp The OTP to hash.
	 *
	 * @return string Hashed OTP.
	 */
	public function hashOtp( string $otp ): string {
		return hash_hmac( 'sha256', $otp, AUTH_SALT . SECURE_AUTH_SALT );
	}

	/* ---------- PRIVATE ------------------------------------------------- */

	/**
	 * Send OTP via email.
	 *
	 * @param \WP_User $user
	 * @param string   $otp
	 *
	 * @return bool
	 */
	private function _sendViaEmail( \WP_User $user, string $otp ): bool {
		return wp_mail(
			$user->user_email,
			__( 'Your One-Time OTP', 'hda' ),
			sprintf(
				__( "Hello %1\$s,\n\nYour OTP is: %2\$s\nThis code will expire in 5 minutes.\n\nIf you didn't request this login, please ignore this email.", 'hda' ),
				$user->user_login,
				$otp
			)
		);
	}

	/**
	 * Send OTP via SMS gateway with email fallback.
	 *
	 * @param \WP_User $user
	 * @param string   $otp
	 *
	 * @return bool
	 */
	private function _sendViaSms( \WP_User $user, string $otp ): bool {
		$gateway = $this->_getGateway();

		if ( ! $gateway ) {
			Helper::errorLog( 'HDA OTP: Gateway not configured, falling back to email.' );

			return $this->_sendViaEmail( $user, $otp );
		}

		$recipient = get_user_meta( $user->ID, $gateway->getUserMetaKey(), true );

		if ( empty( $recipient ) ) {
			return $this->_sendViaEmail( $user, $otp );
		}

		$sent = $gateway->send( $recipient, $otp );

		if ( ! $sent ) {
			Helper::errorLog( 'HDA OTP Gateway Error: ' . $gateway->getLastError() . ' - falling back to email.' );

			return $this->_sendViaEmail( $user, $otp );
		}

		return true;
	}

	/**
	 * Get the cached gateway instance.
	 *
	 * @return GatewayInterface|null null if gateway not available
	 */
	private function _getGateway(): ?GatewayInterface {
		if ( null === $this->cachedGateway ) {
			$instance            = GatewayFactory::create();
			$this->cachedGateway = ( $instance && $instance->validateConfig() ) ? $instance : false;
		}

		return $this->cachedGateway ?: null;
	}

	/**
	 * Mask email address for display.
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	private function _maskEmail( string $email ): string {
		if ( ! str_contains( $email, '@' ) ) {
			return $email;
		}

		[ $local, $domain ] = explode( '@', $email, 2 );

		$localLength = strlen( $local );
		if ( $localLength <= 2 ) {
			$maskedLocal = str_repeat( '*', $localLength );
		} else {
			$maskedLocal = $local[0] . str_repeat( '*', $localLength - 2 ) . $local[ $localLength - 1 ];
		}

		return $maskedLocal . '@' . $domain;
	}
}
