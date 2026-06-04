<?php
/**
 * @package HDAT\Providers\Custom
 */

declare(strict_types=1);

namespace HDAT\Providers\Custom;

use HDAT\Domain\Provider\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Value object encapsulating custom provider configuration.
 *
 * Stored as JSON in the `custom_provider_meta` column of hdat_ai_keys.
 */
final class CustomProviderMeta {

	/**
	 * @param Capability[] $capabilities
	 */
	public function __construct(
		public readonly string $apiFormat,
		public readonly string $customLabel,
		public readonly ?string $modelsUrl = null,
		public readonly string $authHeaderName = 'Authorization',
		public readonly string $authHeaderPrefix = 'Bearer',
		public readonly array $capabilities = [ Capability::Chat ],
		public readonly bool $supportsLiveModels = false,
	) {}

	/**
	 * Deserialize from JSON-decoded array.
	 *
	 * @param array<string, mixed> $data
	 */
	public static function fromArray( array $data ): self {
		$capabilities = [];
		foreach ( (array) ( $data['capabilities'] ?? [] ) as $cap ) {
			if ( is_string( $cap ) ) {
				$capabilities[] = Capability::tryFrom( $cap ) ?? Capability::Chat;
			}
		}

		return new self(
			apiFormat:          (string) self::pick( $data, 'api_format', 'apiFormat', 'openai_compatible' ),
			customLabel:        (string) self::pick( $data, 'custom_label', 'customLabel', 'Custom Provider' ),
			modelsUrl: ! empty( self::pick( $data, 'models_url', 'modelsUrl' ) ) ? (string) self::pick( $data, 'models_url', 'modelsUrl' ) : null,
			authHeaderName:     (string) self::pick( $data, 'auth_header_name', 'authHeaderName', 'Authorization' ),
			authHeaderPrefix:   (string) self::pick( $data, 'auth_header_prefix', 'authHeaderPrefix', 'Bearer' ),
			capabilities:       $capabilities ?: [ Capability::Chat ],
			supportsLiveModels: (bool) self::pick( $data, 'supports_live_models', 'supportsLiveModels', false ),
		);
	}

	/**
	 * Serialize to array for JSON encoding.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'api_format'           => $this->apiFormat,
			'custom_label'         => $this->customLabel,
			'models_url'           => $this->modelsUrl,
			'auth_header_name'     => $this->authHeaderName,
			'auth_header_prefix'   => $this->authHeaderPrefix,
			'capabilities'         => array_map( static fn( Capability $c ) => $c->value, $this->capabilities ),
			'supports_live_models' => $this->supportsLiveModels,
		];
	}

	/**
	 * Validate the configuration.
	 *
	 * @return array<string> List of validation errors (empty if valid).
	 */
	public function validate(): array {
		$errors = [];

		if ( ! in_array( $this->apiFormat, [ 'openai_compatible', 'anthropic_messages' ], true ) ) {
			$errors[] = 'api_format must be "openai_compatible" or "anthropic_messages"';
		}

		if ( '' === trim( $this->customLabel ) ) {
			$errors[] = 'custom_label is required';
		}

		if ( '' === trim( $this->authHeaderName ) ) {
			$errors[] = 'auth_header_name cannot be empty';
		}

		if ( $this->supportsLiveModels && null === $this->modelsUrl ) {
			$errors[] = 'models_url is required when supports_live_models is true';
		}

		if ( null !== $this->modelsUrl && ! filter_var( $this->modelsUrl, FILTER_VALIDATE_URL ) ) {
			$errors[] = 'models_url must be a valid URL';
		}

		return $errors;
	}

	/**
	 * Read canonical snake_case keys while preserving older camelCase rows.
	 *
	 * @param array<string, mixed> $data
	 */
	private static function pick( array $data, string $snakeKey, string $camelKey, mixed $defaultVal = null ): mixed {
		return $data[ $snakeKey ] ?? $data[ $camelKey ] ?? $defaultVal;
	}
}
