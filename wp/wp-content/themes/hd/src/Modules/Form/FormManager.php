<?php
/**
 * Form Manager — Business Logic Orchestrator.
 *
 * Stateless service that coordinates form submission processing:
 * validate → honeypot → CAPTCHA → spam check → save → email → log.
 *
 * Instantiated by FormAPI — NOT a Module itself.
 *
 * @package HD\Modules\Form
 */

namespace HD\Modules\Form;

use HD\Core\Helper;
use HD\Modules\Form\DTO\FormEntry;
use HD\Modules\Form\Cron\AsyncFormProcessor;
use HD\Modules\Form\Notification\NotificationDispatcher;
use HD\Modules\Form\Notification\NotificationMessage;
use HD\Modules\Form\Repository\FormEntryRepository;
use HD\Modules\Form\Repository\FormLogRepository;
use HD\Modules\Form\Security\CaptchaGuard;
use HD\Modules\Form\Security\GeoIPResolver;
use HD\Modules\Form\Security\HoneypotGuard;
use HD\Modules\Form\Security\SpamChecker;
use HD\Modules\Form\Validator\FormValidator;

defined( 'ABSPATH' ) || exit;

final class FormManager {
	private const DEFAULT_MIN_SUBMIT_TIME = 3;
	private const DEFAULT_MAX_RENDER_AGE  = 1800;

	/**
	 * Process a form submission.
	 *
	 * This is the main entry point called by the REST endpoint.
	 * It separates business logic from HTTP-layer concerns.
	 *
	 * @param array  $input     Raw payload from WP_REST_Request.
	 * @param string $ip        Client IP address.
	 * @param string $userAgent Client User-Agent header.
	 * @param string $referer   HTTP Referer header.
	 * @param array  $files     Uploaded files ($_FILES-style).
	 *
	 * @return array{entry_id: int, spam: bool}|\WP_Error Result on success, WP_Error on failure.
	 */
	public function processSubmission( array $input, string $ip, string $userAgent, string $referer, array $files = [] ): array|\WP_Error {

		// 1. Honeypot check (before validation to fast-fail bots).
		if ( HoneypotGuard::isBot( $input ) ) {
			// Return success to avoid info leak — but don't save.
			return [
				'entry_id' => 0,
				'spam'     => true,
			];
		}

		// 1.5. Minimum submission time (bots submit too fast).
		$timestampResult = self::validateRenderTimestamp( $input, FormConfig::all() );
		if ( null !== $timestampResult ) {
			return $timestampResult;
		}

		// 2. Validate & sanitize input.
		$validator = new FormValidator();
		$validated = $validator->validate( $input );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$formType = $validated['formType'];

		// 3. Verify CAPTCHA (before file upload to prevent bot orphan files).
		$captchaError = $this->verifyCaptcha( $formType, $validated['captchaToken'], $ip );
		if ( is_wp_error( $captchaError ) ) {
			return $captchaError;
		}

		// 3.5. Duplicate submission detection (30min window).
		$dupeHash     = self::submissionHash( $ip, $formType, $validated );
		$transientKey = 'hd_form_dupe_' . $dupeHash;

		if ( ! self::claimDuplicateSubmission( $transientKey ) ) {
			self::logDuplicateDrop( $formType, $validated, $dupeHash, $ip );

			// Silent success — avoid info leak about duplicate detection.
			return [
				'entry_id' => 0,
				'spam'     => false,
			];
		}

		// 4. Validate & upload files.
		$uploadedFiles = [];
		if ( ! empty( $files ) ) {
			$fileResult = $validator->validateFiles( $files );
			if ( is_wp_error( $fileResult ) ) {
				self::releaseDuplicateSubmission( $transientKey );

				return $fileResult;
			}

			$uploadResult = $this->storeUploadedFiles( $files );
			if ( is_wp_error( $uploadResult ) ) {
				self::releaseDuplicateSubmission( $transientKey );

				return $uploadResult;
			}

			$uploadedFiles = $uploadResult;
		}

		// 5. Build DTO.
		$utm         = $validated['utm'];
		$entryData   = $validated['fields'];
		$fieldLabels = $validated['fieldLabels'] ?? [];

		if ( ! empty( $uploadedFiles ) ) {
			$entryData['__files'] = $uploadedFiles;
		}

		if ( ! empty( $fieldLabels ) ) {
			$entryData['__labels'] = $fieldLabels;
		}

		// 5.5. GeoIP enrichment.
		$geo = GeoIPResolver::resolve( $ip );
		if ( $geo ) {
			$entryData['__geo'] = $geo;
		}

		$entry = new FormEntry(
			formType:      $formType,
			formId:        $validated['formId'],
			name:          $validated['name'],
			email:         $validated['email'],
			phone:         $validated['phone'],
			phoneCountry:  '',
			phoneNational: '',
			ipAddress:     $ip,
			userAgent:     $userAgent,
			refererUrl:    $referer,
			pageUrl:       sanitize_url( (string) ( $input['page_url'] ?? '' ) ),
			data:          $entryData,
			submissionHash: $dupeHash,
			utmSource:     $utm['source'] ?? '',
			utmMedium:     $utm['medium'] ?? '',
			utmCampaign:   $utm['campaign'] ?? '',
			utmTerm:       $utm['term'] ?? '',
			utmContent:    $utm['content'] ?? '',
			userId:        get_current_user_id(),
		);

		// 6. Spam check.
		$spamOverride = null !== FormConfig::getFormType( $formType )
			? null
			: ( $input['spam_check'] ?? null );

		$spamReasons = SpamChecker::checkCheap( $entry, $spamOverride );
		$isSpam      = ! empty( $spamReasons );

		// 7. Save to database.
		$repo    = new FormEntryRepository();
		$entryId = $repo->insert( $entry );

		if ( is_wp_error( $entryId ) ) {
			self::releaseDuplicateSubmission( $transientKey );

			if ( 'duplicate_submission' === $entryId->get_error_code() ) {
				self::logDuplicateDrop( $formType, $validated, $dupeHash, $ip );

				return [
					'entry_id' => 0,
					'spam'     => false,
				];
			}

			return new \WP_Error(
				'save_failed',
				__( 'An error occurred. Please try again.', 'hd' ),
				[ 'status' => 500 ]
			);
		}

		// Mark as spam post-save if detected.
		if ( $isSpam ) {
			$repo->bulkUpdateStatus( [ $entryId ], 'spam' );
		}

		if ( ! $isSpam ) {
			AsyncFormProcessor::enqueueAkismet( $entryId );
		}

		// 8. Log the submission event.
		$logRepo = new FormLogRepository();
		$logRepo->log(
			$entryId,
			$isSpam ? 'spam_detected' : 'submitted',
			$isSpam ? 'Form submitted (spam detected).' : 'Form submitted.',
			[
				'form_id'      => $validated['formId'],
				'spam_reasons' => $spamReasons,
			],
			'system',
			$ip
		);

		// 9. Dispatch notifications (skip for spam).
		if ( ! $isSpam ) {
			$this->dispatchNotifications( $entryId, $formType, $entry, $logRepo );
		}

		// 10. Extensibility hook for third-party code.
		do_action( 'hd_form_submitted', $entryId, $formType, $entry, $isSpam );

		// 11. Invalidate badge count cache.
		delete_transient( 'hd_form_unread_count' );

		return [
			'entry_id' => $entryId,
			'spam'     => $isSpam,
		];
	}

	/**
	 * Dispatch notifications to all enabled channels.
	 *
	 * @param int              $entryId  The saved entry ID.
	 * @param string           $formType Form type slug.
	 * @param FormEntry        $entry    The form entry DTO.
	 * @param FormLogRepository $logRepo Log repository instance.
	 */
	private function dispatchNotifications( int $entryId, string $formType, FormEntry $entry, FormLogRepository $logRepo ): void {
		try {
			$message = NotificationMessage::fromEntry( $entry, $entryId );
			$results = NotificationDispatcher::dispatch( $message, [ 'email' ] );

			foreach ( $results as $channel => $success ) {
				$eventType = match ( true ) {
					$success => $channel . '_queued',
					default  => $channel . '_queue_failed',
				};
				$label = $success ? 'Queued' : 'Failed';

				$logRepo->log(
					$entryId,
					$eventType,
					sprintf( 'Notification via %s: %s', $channel, $label ),
					[ 'channel' => $channel ],
					'system',
					$entry->ipAddress
				);
			}

			$scheduled = AsyncFormProcessor::enqueueNotifications( $entryId, $entry );
			$logRepo->log(
				$entryId,
				$scheduled ? 'notifications_queued' : 'notifications_queue_failed',
				$scheduled ? 'Async notifications queued.' : 'Failed to queue async notifications.',
				[ 'channels' => 'non_email' ],
				'system',
				$entry->ipAddress
			);
		} catch ( \Throwable $e ) {
			$logRepo->log(
				$entryId,
				'notification_failed',
				'Failed to dispatch notifications.',
				[ 'error' => $e->getMessage() ],
				'system',
				$entry->ipAddress
			);
		}
	}

	/**
	 * Verify CAPTCHA token.
	 *
	 * @param string $formType     Form type slug.
	 * @param string $captchaToken Token from frontend.
	 * @param string $ip           Client IP.
	 *
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	private function verifyCaptcha( string $formType, string $captchaToken, string $ip ): bool|\WP_Error {
		// Provider always resolved from config — never trust client-provided type.
		$guard = CaptchaGuard::make( $formType );

		if ( ! $guard->verify( $captchaToken, $ip ) ) {
			return new \WP_Error(
				'captcha_failed',
				__( 'CAPTCHA verification failed.', 'hd' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Create a time-windowed hash for duplicate detection.
	 *
	 * Combines IP + form type + validated fields + 30-minute time tick.
	 * Same data within the same window produces the same hash.
	 *
	 * @param string $ip        Client IP.
	 * @param string $formType  Form type slug.
	 * @param array  $validated Validated input data.
	 *
	 * @return string Hash string.
	 */
	private static function submissionHash( string $ip, string $formType, array $validated ): string {
		$tick = (int) ceil( time() / ( HOUR_IN_SECONDS / 2 ) );

		$parts = [
			$tick,
			$ip,
			$formType,
			$validated['name'] ?? '',
			$validated['email'] ?? '',
			$validated['phone'] ?? '',
		];

		// Include extra fields sorted by key for consistency.
		$fields = $validated['fields'] ?? [];
		ksort( $fields );

		foreach ( $fields as $k => $v ) {
			$parts[] = $k . '=' . ( is_array( $v ) ? implode( ',', $v ) : $v );
		}

		return wp_hash( implode( '|', $parts ), 'nonce' );
	}

	/**
	 * Validate the client render timestamp before expensive validation.
	 *
	 * @param array<string, mixed> $input  Raw submission payload.
	 * @param array<string, mixed> $config Form module config.
	 *
	 * @return array{entry_id: int, spam: bool}|null
	 */
	private static function validateRenderTimestamp( array $input, array $config ): ?array {
		$renderedAt = self::normalizeRenderTimestamp( $input['_render_ts'] ?? null );
		if ( $renderedAt <= 0 ) {
			return self::spamSuspectResult();
		}

		$elapsed = time() - $renderedAt;
		if ( $elapsed < 0 ) {
			return self::spamSuspectResult();
		}

		$minTime = max( 0, (int) ( $config['min_submit_time'] ?? self::DEFAULT_MIN_SUBMIT_TIME ) );
		if ( $minTime > 0 && $elapsed < $minTime ) {
			return self::spamSuspectResult();
		}

		$maxAge = max( 0, (int) ( $config['max_render_age'] ?? self::DEFAULT_MAX_RENDER_AGE ) );
		if ( $maxAge > 0 && $elapsed > $maxAge ) {
			return self::spamSuspectResult();
		}

		return null;
	}

	private static function normalizeRenderTimestamp( mixed $value ): int {
		if ( ! is_scalar( $value ) ) {
			return 0;
		}

		$timestamp = abs( (int) $value );

		return $timestamp > 9999999999
			? (int) floor( $timestamp / 1000 )
			: $timestamp;
	}

	/**
	 * Silent success shape used for bot-suspect submissions.
	 *
	 * @return array{entry_id: int, spam: bool}
	 */
	private static function spamSuspectResult(): array {
		return [
			'entry_id' => 0,
			'spam'     => true,
		];
	}

	/**
	 * Move valid uploaded files into WordPress uploads and preserve per-field diagnostics.
	 *
	 * @param array<string, mixed> $files          $_FILES-style payload.
	 * @param callable|null       $isUploadedFile Optional upload-origin checker for tests.
	 * @param callable|null       $uploadHandler  Optional upload handler for tests.
	 *
	 * @return array<string, string>|\WP_Error
	 */
	private function storeUploadedFiles( array $files, ?callable $isUploadedFile = null, ?callable $uploadHandler = null ): array|\WP_Error {
		if ( null === $uploadHandler && ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$isUploadedFile ??= static fn( string $tmpName ): bool => is_uploaded_file( $tmpName );
		$uploadHandler  ??= static fn( array $file ): array => wp_handle_upload( $file, [ 'test_form' => false ] );

		$uploadedFiles = [];
		$fieldErrors   = [];
		$diagnostics   = [];

		foreach ( $files as $key => $file ) {
			$fieldKey = self::uploadFieldKey( $key );

			if ( ! is_array( $file ) ) {
				$fieldErrors[ $fieldKey ] = __( 'Invalid upload payload.', 'hd' );
				$diagnostics[ $fieldKey ] = [ 'reason' => 'invalid_shape' ];
				continue;
			}

			$errorCode = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );
			if ( UPLOAD_ERR_NO_FILE === $errorCode ) {
				self::logUploadDrop( $fieldKey, 'no_file' );
				continue;
			}

			if ( UPLOAD_ERR_OK !== $errorCode ) {
				$fieldErrors[ $fieldKey ] = __( 'File upload error. Please try again.', 'hd' );
				$diagnostics[ $fieldKey ] = [
					'reason' => 'php_upload_error',
					'code'   => $errorCode,
				];
				continue;
			}

			$tmpName = (string) ( $file['tmp_name'] ?? '' );
			if ( '' === $tmpName ) {
				$fieldErrors[ $fieldKey ] = __( 'Invalid upload payload.', 'hd' );
				$diagnostics[ $fieldKey ] = [ 'reason' => 'missing_tmp_name' ];
				continue;
			}

			if ( ! $isUploadedFile( $tmpName ) ) {
				self::logUploadDrop( $fieldKey, 'not_uploaded_file' );
				continue;
			}

			$upload = $uploadHandler( $file );
			if ( ! is_array( $upload ) || ! empty( $upload['error'] ) ) {
				$message                  = is_array( $upload ) ? (string) ( $upload['error'] ?? '' ) : '';
				$fieldErrors[ $fieldKey ] = $message ?: __( 'File upload error. Please try again.', 'hd' );
				$diagnostics[ $fieldKey ] = [
					'reason' => 'wp_handle_upload_failed',
					'error'  => $message,
				];
				continue;
			}

			$url = sanitize_url( (string) ( $upload['url'] ?? '' ) );
			if ( '' === $url ) {
				$fieldErrors[ $fieldKey ] = __( 'File upload error. Please try again.', 'hd' );
				$diagnostics[ $fieldKey ] = [ 'reason' => 'missing_upload_url' ];
				continue;
			}

			$uploadedFiles[ $fieldKey ] = $url;
		}

		if ( $fieldErrors ) {
			return new \WP_Error(
				'upload_failed',
				__( 'Please fix uploaded files.', 'hd' ),
				[
					'status'        => 422,
					'fields'        => $fieldErrors,
					'upload_errors' => $diagnostics,
				]
			);
		}

		return $uploadedFiles;
	}

	private static function uploadFieldKey( int|string $key ): string {
		$fieldKey = sanitize_key( (string) $key );

		return '' !== $fieldKey ? $fieldKey : 'file';
	}

	private static function logUploadDrop( string $fieldKey, string $reason ): void {
		Helper::errorLog( sprintf( '[FormManager] Dropped upload field "%s": %s', $fieldKey, $reason ) );
	}

	/**
	 * Record silently dropped duplicate submissions for admin diagnostics.
	 *
	 * @param array<string, mixed> $validated Validated form payload.
	 */
	private static function logDuplicateDrop( string $formType, array $validated, string $submissionHash, string $ip ): void {
		try {
			( new FormLogRepository() )->log(
				0,
				'duplicate_dropped',
				'Duplicate form submission dropped.',
				[
					'form_type'       => $formType,
					'form_id'         => (string) ( $validated['formId'] ?? '' ),
					'submission_hash' => $submissionHash,
				],
				'system',
				$ip
			);
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[FormManager] Failed to log duplicate drop: ' . $e->getMessage() );
		}
	}

	/**
	 * Atomically claim the duplicate-submission window before persistence.
	 */
	private static function claimDuplicateSubmission( string $transientKey ): bool {
		$timeoutOption = '_transient_timeout_' . $transientKey;
		$valueOption   = '_transient_' . $transientKey;
		$now           = time();
		$timeout       = (int) get_option( $timeoutOption, 0 );

		if ( $timeout > 0 && $timeout < $now ) {
			delete_option( $timeoutOption );
			delete_option( $valueOption );
		}

		if ( false !== get_transient( $transientKey ) ) {
			return false;
		}

		if ( ! add_option( $timeoutOption, $now + ( 30 * MINUTE_IN_SECONDS ), '', false ) ) {
			return false;
		}

		if ( add_option( $valueOption, 1, '', false ) ) {
			return true;
		}

		delete_option( $timeoutOption );

		return false;
	}

	/**
	 * Release a duplicate claim when persistence fails.
	 */
	private static function releaseDuplicateSubmission( string $transientKey ): void {
		delete_transient( $transientKey );
	}
}
