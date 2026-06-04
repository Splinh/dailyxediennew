<?php
/**
 * WhatsApp Gateway (Meta Cloud API - Direct)
 *
 * Free tier: 1,000 service conversations/month
 * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

\defined( 'ABSPATH' ) || exit;

final class WhatsAppGateway extends AbstractGateway {

	/**
	 * Meta Graph API URL
	 * {phone_number_id} will be replaced with actual ID
	 */
	private const API_URL = 'https://graph.facebook.com/v21.0/%s/messages';

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'whatsapp';
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return __( 'WhatsApp', 'hda' );
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
		$phone_number_id = $this->getConfig( 'phone_number_id' );
		$access_token    = $this->getConfig( 'access_token' );
		$template_name   = $this->getConfig( 'template_name' );

		if ( empty( $phone_number_id ) ) {
			$this->setError( __( 'WhatsApp Phone Number ID is required.', 'hda' ) );

			return false;
		}

		if ( empty( $access_token ) ) {
			$this->setError( __( 'WhatsApp Access Token is required.', 'hda' ) );

			return false;
		}

		if ( empty( $template_name ) ) {
			$this->setError( __( 'WhatsApp Template Name is required for OTPs.', 'hda' ) );

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

		$phone_number_id   = $this->getConfig( 'phone_number_id' );
		$access_token      = $this->getConfig( 'access_token' );
		$template_name     = $this->getConfig( 'template_name' );
		$template_language = $this->getConfig( 'template_language', 'en_US' ); // default to en_US if not set

		// Format phone number (remove + and spaces, keep digits only with country code)
		$to_phone = $this->formatPhoneNumber( $to );

		$url = sprintf( self::API_URL, $phone_number_id );

		$result = $this->makeRequest(
			$url,
			[
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			],
			wp_json_encode(
				[
					'messaging_product' => 'whatsapp',
					'recipient_type'    => 'individual',
					'to'                => $to_phone,
					'type'              => 'template',
					'template'          => [
						'name'       => $template_name,
						'language'   => [
							'code' => $template_language,
						],
						'components' => [
							[
								'type'       => 'body',
								'parameters' => [
									[
										'type' => 'text',
										'text' => $otp,
									],
								],
							],
							[
								'type'       => 'button',
								'sub_type'   => 'url',
								'index'      => '0',
								'parameters' => [
									[
										'type' => 'text',
										'text' => $otp,
									],
								],
							],
						],
					],
				]
			),
		);

		if ( ! $result['ok'] ) {
			if ( $result['body'] !== null ) {
				$this->setError( $result['body']['error']['message'] ?? __( 'Unknown WhatsApp error', 'hda' ) );
			} elseif ( empty( $this->lastError ) ) {
				$this->setError( __( 'Unknown WhatsApp error', 'hda' ) );
			}

			return false;
		}

		$body = $result['body'];

		// Check for message ID in response (success indicator)
		if ( empty( $body['messages'][0]['id'] ) ) {
			$this->setError( __( 'WhatsApp message not sent', 'hda' ) );

			return false;
		}

		return true;
	}

	/**
	 * Format phone number for WhatsApp (digits only with country code, no +)
	 *
	 * @param string $phone
	 *
	 * @return string
	 */
	private function formatPhoneNumber( string $phone ): string {
		// Remove all non-digit characters
		$phone = preg_replace( '/\D/', '', $phone );

		// If starts with 0 (Vietnam local), convert to 84
		if ( str_starts_with( $phone, '0' ) ) {
			$phone = '84' . substr( $phone, 1 );
		}

		return $phone;
	}
}
