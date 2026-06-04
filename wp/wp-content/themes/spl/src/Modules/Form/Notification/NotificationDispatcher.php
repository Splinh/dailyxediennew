<?php
/**
 * Notification Dispatcher
 *
 * Registry + Dispatcher: resolves enabled channels from config,
 * instantiates each, and dispatches the notification message.
 *
 * @package SPL\Modules\Form\Notification
 */

namespace SPL\Modules\Form\Notification;

use SPL\Modules\Form\FormConfig;
use SPL\Modules\Form\Notification\Channel\EmailChannel;
use SPL\Modules\Form\Notification\Channel\TelegramChannel;
use SPL\Modules\Form\Notification\Channel\ViberChannel;
use SPL\Modules\Form\Notification\Channel\ZaloChannel;

defined( 'ABSPATH' ) || exit;

final class NotificationDispatcher {

	/**
	 * Registry: channel slug → FQCN.
	 *
	 * @var array<string, string>
	 */
	private static array $channelMap = [
		'email'    => EmailChannel::class,
		'telegram' => TelegramChannel::class,
		'viber'    => ViberChannel::class,
		'zalo'     => ZaloChannel::class,
	];

	/**
	 * Dispatch notification to all enabled channels for a form type.
	 *
	 * @param NotificationMessage $message The channel-agnostic message DTO.
	 *
	 * @return array<string, bool> Results keyed by channel slug.
	 */
	public static function dispatch( NotificationMessage $message, ?array $onlyChannels = null ): array {
		$results  = [];
		$channels = self::resolveChannels( $message->entry->formType );
		if ( null !== $onlyChannels ) {
			$channels = array_intersect_key( $channels, array_flip( $onlyChannels ) );
		}

		foreach ( $channels as $slug => $config ) {
			$channelMap = self::channelMap( $message );
			$class      = $channelMap[ $slug ] ?? null;
			if ( null === $class || ! class_exists( $class ) ) {
				continue;
			}

			try {
				/** @var NotificationChannelInterface $channel */
				$channel          = new $class( $config );
				$results[ $slug ] = $channel->send( $message );
			} catch ( \Throwable ) {
				$results[ $slug ] = false;
			}
		}

		// Filter: allow third-party code to modify results or add custom channels.
		return apply_filters( 'hd_notification_dispatch_results', $results, $message );
	}

	/**
	 * @return array<string, string>
	 */
	private static function channelMap( NotificationMessage $message ): array {
		$map = apply_filters( 'hd_notification_channels', self::$channelMap, $message );

		return is_array( $map ) ? $map : self::$channelMap;
	}

	/**
	 * Dispatch all enabled non-email channels.
	 *
	 * @return array<string, bool>
	 */
	public static function dispatchNonEmail( NotificationMessage $message ): array {
		$channels = array_keys( self::enabledChannels( $message->entry->formType ) );
		$channels = array_values( array_diff( $channels, [ 'email' ] ) );

		return self::dispatch( $message, $channels );
	}

	/**
	 * @return array<string, array>
	 */
	public static function enabledChannels( string $formType ): array {
		return self::resolveChannels( $formType );
	}

	/**
	 * Resolve which channels to activate for a given form type.
	 *
	 * @param string $formType Form type slug.
	 *
	 * @return array<string, array> Enabled channel configs keyed by slug.
	 */
	private static function resolveChannels( string $formType ): array {
		$config   = FormConfig::all();
		$channels = $config['notifications']['channels'] ?? [];

		// Filter to only enabled channels.
		$enabled = [];
		foreach ( $channels as $slug => $channelConfig ) {
			if ( ! empty( $channelConfig['enabled'] ) ) {
				$enabled[ $slug ] = $channelConfig;
			}
		}

		// Per-type override: if form_type defines specific channels, keep only those.
		$formTypeConfig  = $config['form_types'][ $formType ] ?? [];
		$allowedChannels = $formTypeConfig['channels'] ?? null;

		if ( is_array( $allowedChannels ) ) {
			$enabled = array_intersect_key( $enabled, array_flip( $allowedChannels ) );
		}

		return $enabled;
	}
}
