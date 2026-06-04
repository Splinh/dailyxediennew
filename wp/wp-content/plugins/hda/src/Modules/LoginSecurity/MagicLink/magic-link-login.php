<?php
/**
 * Magic Link Login Form — Email-only passwordless login.
 *
 * @package HDAddons\Modules\LoginSecurity
 * @author  HD
 */

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

// ─── State flags ──────────────────────────────────────
$success = ! empty( $args['success'] );
$error   = $args['error'] ?? '';

?>
<?php if ( $error ) : ?>
<div id="login_error" class="notice notice-error">
	<p><strong><?php esc_html_e( 'Error:', 'hda' ); ?></strong> <?php echo esc_html( $error ); ?></p>
</div>
<?php endif; ?>

<?php if ( $success ) : ?>
<div class="notice notice-success" style="margin-bottom:16px;padding:12px 15px;">
	<p>
		<span class="dashicons dashicons-email-alt" style="color:#00a32a;vertical-align:middle;margin-right:4px;"></span>
		<?php esc_html_e( 'A login link has been sent to your email. Please check your inbox (and spam folder).', 'hda' ); ?>
	</p>
	<p style="font-size:12px;color:#666;margin:4px 0 0;">
		<?php
		printf(
			/* translators: %d: number of minutes */
			esc_html__( 'The link will expire in %d minutes and can only be used once.', 'hda' ),
			$args['expiry_minutes'] ?? 5
		);
		?>
	</p>
</div>
<?php endif; ?>

<form name="magic_link_form" id="loginform" action="<?php echo esc_url( $args['action'] ?? '' ); ?>" method="post">
	<?php echo Helper::CSRFToken( 'magic_link_csrf' ); ?>
	<?php if ( ! empty( $args['redirect_to'] ) ) : ?>
	<input type="hidden" name="redirect_to" value="<?php echo esc_url( $args['redirect_to'] ); ?>"/>
	<?php endif; ?>
	<input type="hidden" name="magic_link_request" value="1">

	<p class="otp-prompt">
		<?php esc_html_e( 'Enter your email address and we\'ll send you a link to log in instantly — no password needed.', 'hda' ); ?>
	</p>
	<p>
		<label for="magic_link_email"><?php esc_html_e( 'Email Address', 'hda' ); ?></label>
		<input
			required
			autofocus
			type="email"
			name="magic_link_email"
			id="magic_link_email"
			class="input"
			value="<?php echo esc_attr( $args['email'] ?? '' ); ?>"
			autocomplete="email"
			placeholder="name@example.com"
			size="20"
		/>
	</p>
	<?php submit_button( __( 'Send Login Link', 'hda' ), 'primary large', 'wp-submit', true ); ?>
</form>

<?php if ( ! empty( $args['show_password_fallback'] ) ) : ?>
<p id="nav" style="text-align:center;margin-top:12px;">
	<a href="<?php echo esc_url( $args['password_login_url'] ?? wp_login_url() ); ?>?force_password=1">
		<?php esc_html_e( '← Login with password', 'hda' ); ?>
	</a>
</p>
<?php endif; ?>
