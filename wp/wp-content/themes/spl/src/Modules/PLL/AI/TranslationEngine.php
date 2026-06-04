<?php
/**
 * Shared translation engine.
 *
 * @package SPL\Modules\PLL\AI
 */

namespace SPL\Modules\PLL\AI;

use SPL\Modules\PLL\PLLModule;

defined( 'ABSPATH' ) || exit;

final class TranslationEngine {

	public function __construct(
		private readonly AiClient $client = new AiClient(),
		private readonly PromptBuilder $promptBuilder = new PromptBuilder(),
		private readonly TranslationValidator $validator = new TranslationValidator()
	) {}

	/**
	 * @param array<int, TranslationUnit|array<string, mixed>> $units Translation units.
	 *
	 * @return TranslationResult[]|\WP_Error
	 */
	public function translateUnits( array $units, string $sourceLang, string $targetLang ): array|\WP_Error {
		$units = array_values(
			array_map(
				static fn( TranslationUnit|array $unit ): TranslationUnit => $unit instanceof TranslationUnit ? $unit : TranslationUnit::fromArray( $unit ),
				$units
			)
		);

		if ( empty( $units ) ) {
			return [];
		}

		$results = [];
		foreach ( $this->chunks( $units ) as $chunk ) {
			$chunkResults = $this->translateChunk( $chunk, $sourceLang, $targetLang );
			if ( is_wp_error( $chunkResults ) ) {
				return $chunkResults;
			}

			array_push( $results, ...$chunkResults );
		}

		return $results;
	}

	/**
	 * @param TranslationUnit[] $units Translation units.
	 *
	 * @return TranslationResult[]|\WP_Error
	 */
	private function translateChunk( array $units, string $sourceLang, string $targetLang ): array|\WP_Error {
		$response = $this->requestTranslations( $this->promptBuilder->messages( $units, $sourceLang, $targetLang ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$content      = AiClient::assistantContent( $response );
		$translations = $this->parseTranslations( $content, $units );
		if ( is_wp_error( $translations ) ) {
			return $translations;
		}

		$errors = $this->validator->validate( $units, $translations );
		if ( ! empty( $errors ) ) {
			$repair = $this->requestTranslations( $this->promptBuilder->repairMessages( $errors, $content ) );
			if ( is_wp_error( $repair ) ) {
				return $repair;
			}

			$translations = $this->parseTranslations( AiClient::assistantContent( $repair ), $units );
			if ( is_wp_error( $translations ) ) {
				return $translations;
			}

			$errors = $this->validator->validate( $units, $translations );
			if ( ! empty( $errors ) ) {
				return new \WP_Error( 'hd_pll_ai_validation_failed', __( 'Translation validation failed.', 'SPL' ), [ 'errors' => $errors ] );
			}
		}

		$usage = is_array( $response['usage'] ?? null ) ? $response['usage'] : [];

		return array_map(
			static fn( TranslationUnit $unit ): TranslationResult => new TranslationResult( $unit->id, $unit->source, $translations[ $unit->id ] ?? '', 'ok', [], $usage, $unit->path ),
			$units
		);
	}

	/**
	 * @param TranslationUnit[] $units Translation units.
	 *
	 * @return array<int, TranslationUnit[]>
	 */
	private function chunks( array $units ): array {
		$settings     = PLLModule::getCachedOptions();
		$maxUnits     = max( 1, absint( $settings['ai_max_units_per_request'] ?? 25 ) );
		$maxChars     = max( 1000, absint( $settings['ai_max_chars_per_request'] ?? 12000 ) );
		$chunks       = [];
		$current      = [];
		$currentChars = 0;

		foreach ( $units as $unit ) {
			$unitChars   = $this->unitLength( $unit );
			$limitByUnit = count( $current ) >= $maxUnits;
			$limitByChar = ! empty( $current ) && ( $currentChars + $unitChars ) > $maxChars;

			if ( $limitByUnit || $limitByChar ) {
				$chunks[]     = $current;
				$current      = [];
				$currentChars = 0;
			}

			$current[]     = $unit;
			$currentChars += $unitChars;
		}

		if ( ! empty( $current ) ) {
			$chunks[] = $current;
		}

		return $chunks;
	}

	private function unitLength( TranslationUnit $unit ): int {
		return strlen( $unit->source )
			+ strlen( $unit->context )
			+ strlen( $unit->format )
			+ strlen( implode( '', $unit->protected_tokens ) );
	}

	/**
	 * @param array<int, array<string, string>> $messages Chat messages.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private function requestTranslations( array $messages ): array|\WP_Error {
		$payload = [
			'messages'        => $messages,
			'temperature'     => 0.2,
			'routing_policy'  => [
				'allow_fallback'                   => true,
				'allow_structured_output_fallback' => true,
				'required_capabilities'            => [ 'chat_completions' ],
			],
			'response_format' => [
				'type'        => 'json_schema',
				'json_schema' => [
					'name'   => 'translations',
					'schema' => [
						'type'       => 'object',
						'required'   => [ 'translations' ],
						'properties' => [
							'translations' => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'required'   => [ 'id', 'text' ],
									'properties' => [
										'id'   => [ 'type' => 'string' ],
										'text' => [ 'type' => 'string' ],
									],
								],
							],
						],
					],
				],
			],
		];

		return $this->client->chat( $payload );
	}

	/**
	 * @param TranslationUnit[] $units Source units.
	 *
	 * @return array<string, string>|\WP_Error
	 */
	private function parseTranslations( string $content, array $units ): array|\WP_Error {
		$decoded = json_decode( trim( $content ), true );
		if ( ! is_array( $decoded ) ) {
			return new \WP_Error( 'hd_pll_ai_json_parse_failed', __( 'AI response is not valid JSON.', 'SPL' ) );
		}

		$items = $decoded['translations'] ?? $decoded;
		if ( ! is_array( $items ) ) {
			return new \WP_Error( 'hd_pll_ai_missing_translations', __( 'AI response is missing translations.', 'SPL' ) );
		}

		$translations = [];
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) || ! array_key_exists( 'text', $item ) ) {
				continue;
			}

			$translations[ strtolower( trim( (string) $item['id'] ) ) ] = (string) $item['text'];
		}

		// Verify all requested unit IDs are present in the response.
		$missingIds = [];
		foreach ( $units as $unit ) {
			if ( ! array_key_exists( $unit->id, $translations ) ) {
				$missingIds[] = $unit->id;
			}
		}

		if ( ! empty( $missingIds ) ) {
			return new \WP_Error(
				'hd_pll_ai_count_mismatch',
				sprintf(
					/* translators: %s: comma-separated list of missing unit IDs. */
					__( 'AI response is missing translations for: %s', 'SPL' ),
					implode( ', ', $missingIds )
				)
			);
		}

		return $translations;
	}
}
