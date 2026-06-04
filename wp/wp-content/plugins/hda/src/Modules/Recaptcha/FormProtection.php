<?php
/**
 * Form Protection — CAPTCHA integration for WordPress forms.
 *
 * Hooks a CaptchaProviderInterface into login, register,
 * lost-password, and comment forms.
 *
 * When instantiated, ALL forms are protected (no individual toggles).
 *
 * @package HDAddons\Modules\Recaptcha
 */

namespace HDAddons\Modules\Recaptcha;

use HDAddons\Modules\Recaptcha\Provider\CaptchaProviderInterface;

\defined( 'ABSPATH' ) || exit;

final class FormProtection {

	private CaptchaProviderInterface $provider;

	// ------------------------------------------------------

	/**
	 * @param CaptchaProviderInterface $provider Active CAPTCHA provider.
	 */
	public function __construct( CaptchaProviderInterface $provider ) {
		$this->provider = $provider;

		$this->initLoginPageForms();
		$this->initCommentForm();
	}

	// ─── INIT ───────────────────────────────────────────

	/**
	 * Initialize forms on wp-login.php (login, register, lost password).
	 *
	 * @return void
	 */
	private function initLoginPageForms(): void {
		// Enqueue once for all login page forms.
		add_action( 'login_enqueue_scripts', $this->provider->enqueueAssets( ... ), 99 );

		// Login form.
		add_action( 'login_form', $this->provider->renderWidget( ... ) );
		add_filter( 'authenticate', $this->verifyLogin( ... ), 25, 3 );

		// Registration form.
		add_action( 'register_form', $this->provider->renderWidget( ... ) );
		add_filter( 'registration_errors', $this->verifyRegistration( ... ), 10, 3 );

		// Lost password form.
		add_action( 'lostpassword_form', $this->provider->renderWidget( ... ) );
		add_action( 'lostpassword_post', $this->verifyLostPassword( ... ), 10, 2 );
	}

	/**
	 * Initialize comment form protection.
	 *
	 * @return void
	 */
	private function initCommentForm(): void {
		add_action( 'wp_enqueue_scripts', $this->enqueueCommentAssets( ... ) );
		add_action( 'comment_form_after_fields', $this->provider->renderWidget( ... ) );
		add_action( 'comment_form_logged_in_after', $this->provider->renderWidget( ... ) );
		add_filter( 'preprocess_comment', $this->verifyComment( ... ) );
	}

	// ─── ASSET ENQUEUE ─────────────────────────────────

	/**
	 * Conditionally enqueue CAPTCHA assets on singular pages with open comments.
	 *
	 * @return void
	 */
	public function enqueueCommentAssets(): void {
		if ( is_singular() && comments_open() ) {
			$this->provider->enqueueAssets();
		}
	}

	// ─── VERIFICATION: LOGIN ───────────────────────────

	/**
	 * Verify CAPTCHA on login form.
	 *
	 * @param mixed  $user     User object or WP_Error.
	 * @param string $username Username.
	 * @param string $password Password.
	 *
	 * @return mixed|\WP_Error
	 */
	public function verifyLogin( mixed $user, string $username, string $password ): mixed {
		// Only verify on POST with credentials.
		if ( empty( $username ) || empty( $password ) || ! $this->isPost() ) {
			return $user;
		}

		$result = $this->verifyOrError( 'captcha_login' );

		return is_wp_error( $result ) ? $result : $user;
	}

	// ─── VERIFICATION: REGISTRATION ────────────────────

	/**
	 * Verify CAPTCHA on registration form.
	 *
	 * @param \WP_Error $errors               Registration errors.
	 * @param string    $sanitized_user_login  Sanitized username.
	 * @param string    $user_email            User email.
	 *
	 * @return \WP_Error
	 */
	public function verifyRegistration( \WP_Error $errors, string $sanitized_user_login, string $user_email ): \WP_Error {
		if ( ! $this->isPost() ) {
			return $errors;
		}

		$result = $this->verifyOrError( 'captcha_register' );

		if ( is_wp_error( $result ) ) {
			$errors->merge_from( $result );
		}

		return $errors;
	}

	// ─── VERIFICATION: LOST PASSWORD ───────────────────

	/**
	 * Verify CAPTCHA on lost password form.
	 *
	 * @param \WP_Error $errors    Error object.
	 * @param mixed     $user_data User data.
	 *
	 * @return void
	 */
	public function verifyLostPassword( \WP_Error $errors, mixed $user_data ): void {
		if ( ! $this->isPost() ) {
			return;
		}

		$result = $this->verifyOrError( 'captcha_lostpassword' );

		if ( is_wp_error( $result ) ) {
			$errors->merge_from( $result );
		}
	}

	// ─── VERIFICATION: COMMENTS ────────────────────────

	/**
	 * Verify CAPTCHA on comment form.
	 *
	 * @param array $commentData Comment data.
	 *
	 * @return array
	 */
	public function verifyComment( array $commentData ): array {
		// Skip for logged-in admins/editors (trackbacks, pingbacks, etc.).
		if (
			! $this->isPost()
			|| ! empty( $commentData['comment_type'] )
			|| ( is_user_logged_in() && current_user_can( 'moderate_comments' ) )
		) {
			return $commentData;
		}

		$result = $this->verifyOrError( 'captcha_comment' );

		if ( is_wp_error( $result ) ) {
			wp_die(
				$result->get_error_message(),
				esc_html__( 'CAPTCHA Verification Failed', 'hda' ),
				[
					'response'  => 403,
					'back_link' => true,
				]
			);
		}

		return $commentData;
	}

	// ─── HELPERS ────────────────────────────────────────

	/**
	 * Core verification logic — shared by all forms.
	 *
	 * @param string $errorCode WP_Error code for this form context.
	 *
	 * @return true|\WP_Error
	 */
	private function verifyOrError( string $errorCode ): true|\WP_Error {
		$token = $this->provider->getResponseToken();

		if ( empty( $token ) ) {
			return new \WP_Error(
				$errorCode,
				__( '<strong>Error:</strong> Please complete the CAPTCHA verification.', 'hda' )
			);
		}

		if ( ! $this->provider->verifyToken( $token ) ) {
			return new \WP_Error(
				$errorCode,
				__( '<strong>Error:</strong> CAPTCHA verification failed. Please try again.', 'hda' )
			);
		}

		return true;
	}

	/**
	 * Check if current request is POST.
	 *
	 * @return bool
	 */
	private function isPost(): bool {
		return isset( $_SERVER['REQUEST_METHOD'] )
			&& strtoupper( sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) ) === 'POST';
	}
}
