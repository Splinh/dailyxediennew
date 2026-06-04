<?php
/**
 * Viber Channel
 *
 * Sends form submission notifications via Viber REST API.
 *
 * @package SPL\Modules\Form\Notification\Channel
 */

namespace SPL\Modules\Form\Notification\Channel;

use SPL\Modules\Form\Notification\NotificationChannelInterface;
use SPL\Modules\Form\Notification\NotificationMessage;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class ViberChannel implements NotificationChannelInterface {
	private const API_URL = 'https://chatapi.viber.com/pa/send_message';

	private string $authToken;
	private string $receiverId;
	private string $senderName;
	private string $senderAvatar;

	/**
	 * @param array $config Channel configuration from notifications.channels.viber.
	 */
	public function __construct( array $config = [] ) {
		$this->authToken    = $config['auth_token'] ?? '';
		$this->receiverId   = $config['receiver'] ?? '';
		$this->senderName   = $config['sender']['name'] ?? 'HD Notify';
		$this->senderAvatar = $config['sender']['avatar'] ?? '';
	}

	/** @inheritDoc */
	public function getSlug(): string {
		return 'viber';
	}

	/**
	 * Send notification via Viber REST API.
	 *
	 * @param NotificationMessage $message The notification message DTO.
	 *
	 * @return bool True if the API responded with status=0.
	 */
	public function send( NotificationMessage $message ): bool {
		if ( '' === $this->authToken || '' === $this->receiverId ) {
			return false;
		}

		$siteName = Helper::getOption( 'blogname', '' );
		$text     = $message->toPlainText( $siteName );

		$sender = [ 'name' => $this->senderName ];
		if ( '' !== $this->senderAvatar ) {
			$sender['avatar'] = $this->senderAvatar;
		}

		$payload = [
			'receiver' => $this->receiverId,
			'type'     => 'text',
			'text'     => $text,
			'sender'   => $sender,
		];

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 10,
				'headers' => [
					'Content-Type'       => 'application/json',
					'X-Viber-Auth-Token' => $this->authToken,
				],
				'body'    => wp_json_encode( $payload ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return isset( $body['status'] ) && 0 === (int) $body['status'];
	}
}
