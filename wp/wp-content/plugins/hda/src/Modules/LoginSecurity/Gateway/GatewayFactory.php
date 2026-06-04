<?php
/**
 * Gateway Factory
 *
 * @package HDAddons\Modules\LoginSecurity\Gateway
 */

namespace HDAddons\Modules\LoginSecurity\Gateway;

use HDAddons\Modules\LoginSecurity\LoginSecurityModule;

\defined( 'ABSPATH' ) || exit;

final class GatewayFactory {

	/**
	 * Registered gateways
	 *
	 * @var array<string, class-string<GatewayInterface>>
	 */
	private static array $gateways = [
		'telegram' => TelegramGateway::class,
		'zalo'     => ZaloGateway::class,
		'whatsapp' => WhatsAppGateway::class,
		'smsgate'  => SmsGateGateway::class,
		'viber'    => ViberGateway::class,
		'line'     => LineGateway::class,
		'discord'  => DiscordGateway::class,
	];

	/**
	 * Create gateway instance by name
	 *
	 * @param string|null $name Gateway name (null = use option)
	 *
	 * @return GatewayInterface|null
	 */
	public static function create( ?string $name = null ): ?GatewayInterface {
		if ( null === $name ) {
			$options = LoginSecurityModule::getCachedOptions();
			$name    = $options[ LoginSecurityModule::KEY_OTP_GATEWAY ] ?? 'telegram';
		}

		if ( ! isset( self::$gateways[ $name ] ) ) {
			return null;
		}

		$class = self::$gateways[ $name ];

		return new $class();
	}

	/**
	 * Get available gateways for admin dropdown
	 *
	 * @return array<string, string> [name => label]
	 */
	public static function getAvailable(): array {
		$available = [];
		foreach ( self::$gateways as $name => $class ) {
			$instance           = new $class();
			$available[ $name ] = $instance->getLabel();
		}

		return $available;
	}

	/**
	 * Register a custom gateway
	 *
	 * @param string $name  Gateway name
	 * @param string $class Gateway class (must implement GatewayInterface)
	 *
	 * @return void
	 */
	public static function register( string $name, string $class ): void {
		if ( is_subclass_of( $class, GatewayInterface::class ) ) {
			self::$gateways[ $name ] = $class;
		}
	}
}
