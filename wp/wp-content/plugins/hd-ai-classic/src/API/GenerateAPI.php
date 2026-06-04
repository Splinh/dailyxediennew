<?php
/**
 * REST API proxy for AI generation.
 *
 * Routes: POST hd-ai-classic/v1/generate
 * Auth: WordPress nonce (logged-in admin with edit_posts capability).
 * Backend calls HDAT via InternalRequestContext.
 *
 * @package HDAC\API
 */

namespace HDAC\API;

defined( 'ABSPATH' ) || exit;

use HDAC\AiClient;
use HDAC\Admin\ImageHandler;
use HDAC\Prompts\ContentPrompt;
use HDAC\Prompts\ExcerptPrompt;
use HDAC\Prompts\ImagePrompt;
use HDAC\Prompts\LongContentOutlinePrompt;
use HDAC\Prompts\LongContentSectionPrompt;
use HDAC\Prompts\TermDescriptionPrompt;
use HDAC\Prompts\TitlePrompt;
use HDAC\Settings;

final class GenerateAPI {

	private const NAMESPACE = 'hd-ai-classic/v1';

	private const FEATURES = [ 'title', 'excerpt', 'term-description', 'content', 'long-content-outline', 'long-content-section', 'image', 'image-prompt' ];

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/generate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'permission' ],
				'args'                => [
					'feature'       => [
						'required'          => true,
						'type'              => 'string',
						'enum'              => self::FEATURES,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'context'       => [
						'required' => true,
						'type'     => 'object',
					],
					'prompt_preset' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'custom_prompt' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
				],
			]
		);
	}

	/**
	 * Permission check: must be logged-in user with edit_posts capability.
	 */
	public function permission(): bool|\WP_Error {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'hdac_forbidden',
				__( 'You do not have permission to use AI generation.', 'hd-ai-classic' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Handle generation request.
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$feature      = $request->get_param( 'feature' );
		$context      = (array) $request->get_param( 'context' );
		$presetId     = (string) $request->get_param( 'prompt_preset' );
		$customPrompt = (string) $request->get_param( 'custom_prompt' );

		// Build messages based on feature type.
		$messages = match ( $feature ) {
			'title'            => TitlePrompt::build( $context, $presetId, $customPrompt ),
			'excerpt'          => ExcerptPrompt::build( $context, $presetId, $customPrompt ),
			'term-description' => TermDescriptionPrompt::build( $context, $presetId, $customPrompt ),
			'content'          => ContentPrompt::build( $context, $presetId, $customPrompt ),
			'long-content-outline' => LongContentOutlinePrompt::build( $context, $presetId, $customPrompt ),
			'long-content-section' => LongContentSectionPrompt::build( $context, $presetId, $customPrompt ),
			'image', 'image-prompt' => ImagePrompt::build( $context, $presetId, $customPrompt ),
			default            => null,
		};

		if ( null === $messages ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Invalid feature type.', 'hd-ai-classic' ),
				],
				400
			);
		}

		$temp      = (float) Settings::get( 'temperature', 0.7 );
		$maxTokens = match ( $feature ) {
			'title'            => Settings::get( 'max_tokens_title', 256 ),
			'excerpt'          => Settings::get( 'max_tokens_excerpt', 512 ),
			'term-description' => Settings::get( 'max_tokens_excerpt', 512 ),
			'content'          => Settings::get( 'max_tokens_content', 2048 ),
			'long-content-outline' => Settings::get( 'max_tokens_excerpt', 512 ),
			'long-content-section' => Settings::get( 'max_tokens_content', 2048 ),
			'image', 'image-prompt' => Settings::get( 'max_tokens_image', 1024 ),
			default            => 1024,
		};

		// Call HDAT gateway.
		$client  = new AiClient();
		$payload = [
			'messages'    => $messages,
			'temperature' => $temp,
			'max_tokens'  => $maxTokens,
		];

		if ( 'long-content-outline' === $feature ) {
			$payload['response_format'] = self::outlineResponseFormat();
		}

		/**
		 * Filter the AI request payload before sending to HDAT.
		 *
		 * @param array  $payload AI request payload.
		 * @param string $feature Feature type.
		 * @param array  $context Request context.
		 */
		$payload = (array) apply_filters( 'hdac_request_payload', $payload, $feature, $context );

		$response = $client->chat( $payload );
		if ( 'long-content-outline' === $feature && is_wp_error( $response ) && self::shouldRetryOutlineWithoutSchema( $response ) ) {
			unset( $payload['response_format'] );
			$response = $client->chat( $payload );
		}

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => $response->get_error_message(),
				],
				(int) ( $response->get_error_data()['status'] ?? 500 )
			);
		}

		$result = AiClient::assistantContent( $response );

		if ( '' === $result ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'No result was generated. Please try again.', 'hd-ai-classic' ),
				],
				500
			);
		}

		$trimmedResult = trim( $result, " \t\n\r\0\x0B\"'" );

		if ( 'long-content-outline' === $feature ) {
			$sections = self::parseOutlineSections( $trimmedResult );
			if ( is_wp_error( $sections ) ) {
				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => $sections->get_error_message(),
					],
					500
				);
			}

			return new \WP_REST_Response(
				[
					'success'  => true,
					'sections' => $sections,
					'result'   => wp_json_encode( [ 'sections' => $sections ] ),
				]
			);
		}

		// If prompt-only requested, return directly.
		if ( 'image-prompt' === $feature ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'result'  => sanitize_textarea_field( $trimmedResult ),
				]
			);
		}

		// If direct image generation is requested.
		if ( 'image' === $feature ) {
			$postId = absint( $context['post_id'] ?? 0 );
			if ( ! $postId ) {
				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => __( 'Missing post ID for image generation.', 'hd-ai-classic' ),
					],
					400
				);
			}

			// Request image generation from HDAT using the generated prompt text.
			$imgResponse = $client->generateImage( $trimmedResult );

			if ( is_wp_error( $imgResponse ) ) {
				$status = (int) ( $imgResponse->get_error_data()['status'] ?? 500 );
				if ( 401 === $status || 403 === $status ) {
					// Fallback to prompt-only
					return new \WP_REST_Response(
						[
							'success'  => true,
							'fallback' => true,
							'result'   => sanitize_textarea_field( $trimmedResult ),
							'message'  => __( 'Direct image generation is unauthorized. Falling back to Prompt Only.', 'hd-ai-classic' ),
						]
					);
				}

				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => $imgResponse->get_error_message(),
					],
					$status
				);
			}

			$imageItem = $imgResponse['data'][0] ?? [];
			$imgUrl    = is_array( $imageItem ) ? (string) ( $imageItem['url'] ?? '' ) : '';
			$imgBase64 = is_array( $imageItem ) ? (string) ( $imageItem['b64_json'] ?? '' ) : '';
			if ( ! $imgUrl && ! $imgBase64 ) {
				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => __( 'No image data was returned from HDAT.', 'hd-ai-classic' ),
					],
					500
				);
			}

			// Save the generated image and assign it as the featured thumbnail.
			$attachmentId = $imgUrl ? ImageHandler::sideload( $imgUrl, $postId ) : ImageHandler::sideloadBase64( $imgBase64, $postId );

			if ( is_wp_error( $attachmentId ) ) {
				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => $attachmentId->get_error_message(),
					],
					500
				);
			}

			return new \WP_REST_Response(
				[
					'success'       => true,
					'attachment_id' => $attachmentId,
					'url'           => wp_get_attachment_url( $attachmentId ),
					'thumbnail_url' => wp_get_attachment_thumb_url( $attachmentId ),
					'result'        => sanitize_textarea_field( $trimmedResult ),
				]
			);
		}

		$sanitizedResult = in_array( $feature, [ 'content', 'long-content-section' ], true ) ? wp_kses_post( $trimmedResult ) : sanitize_textarea_field( $trimmedResult );

		return new \WP_REST_Response(
			[
				'success' => true,
				'result'  => $sanitizedResult,
			]
		);
	}

	/**
	 * Structured response format for long-content outlines.
	 *
	 * @return array<string, mixed>
	 */
	private static function outlineResponseFormat(): array {
		return [
			'type'        => 'json_schema',
			'json_schema' => [
				'name'   => 'long_content_outline',
				'schema' => [
					'type'       => 'object',
					'required'   => [ 'sections' ],
					'properties' => [
						'sections' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'required'   => [ 'heading', 'intent' ],
								'properties' => [
									'heading' => [ 'type' => 'string' ],
									'intent'  => [ 'type' => 'string' ],
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Determine whether a structured-output request should be retried as plain JSON.
	 */
	private static function shouldRetryOutlineWithoutSchema( \WP_Error $error ): bool {
		return in_array( $error->get_error_code(), [ 'pool_exhausted', 'unsupported_capability' ], true );
	}

	/**
	 * Parse and validate outline sections.
	 *
	 * @return array<int, array{heading: string, intent: string}>|\WP_Error
	 */
	private static function parseOutlineSections( string $content ): array|\WP_Error {
		$json = trim( preg_replace( '/^```(?:json)?|```$/m', '', $content ) ?? $content );
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'hdac_outline_json_invalid', __( 'Long content outline response is not valid JSON.', 'hd-ai-classic' ) );
		}

		$items = $data['sections'] ?? $data;
		if ( ! is_array( $items ) ) {
			return new \WP_Error( 'hdac_outline_missing_sections', __( 'Long content outline response is missing sections.', 'hd-ai-classic' ) );
		}

		$sections = [];
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$heading = sanitize_text_field( (string) ( $item['heading'] ?? '' ) );
			$intent  = sanitize_textarea_field( (string) ( $item['intent'] ?? $item['prompt'] ?? '' ) );
			if ( '' === $heading || '' === $intent ) {
				continue;
			}

			$sections[] = [
				'heading' => $heading,
				'intent'  => $intent,
			];

			if ( count( $sections ) >= 8 ) {
				break;
			}
		}

		if ( count( $sections ) < 2 ) {
			return new \WP_Error( 'hdac_outline_too_short', __( 'Long content outline must include at least two usable sections.', 'hd-ai-classic' ) );
		}

		return $sections;
	}
}
