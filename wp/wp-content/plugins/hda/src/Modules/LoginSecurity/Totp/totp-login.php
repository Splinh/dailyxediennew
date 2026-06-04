<?php
/**
 * TOTP Login Form — Authenticator App verification during login.
 *
 * @package HDAddons\Modules\LoginSecurity
 * @author  HD
 */

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$user_id = $args['uid'] ?? 0;

if ( ! empty( $args['error'] ) ) :
	?>
<div id="login_error" class="notice notice-error">
	<p><strong><?php esc_html_e( 'Error:', 'hda' ); ?></strong> <?php echo esc_html( $args['error'] ); ?></p>
</div>
<?php endif ?>

<form name="totp_validate_form" id="loginform" action="<?php echo esc_url( $args['action'] ?? '' ); ?>" method="post">
	<?php if ( $args['interim_login'] ?? '' ) : ?>
	<input type="hidden" name="interim-login" value="1"/>
	<?php endif; ?>
	<?php if ( ! empty( $args['redirect_to'] ) ) : ?>
	<input type="hidden" name="redirect_to" value="<?php echo esc_url( $args['redirect_to'] ); ?>"/>
	<?php endif; ?>
	<input type="hidden" name="uid" value="<?php echo esc_attr( $user_id ); ?>">

	<?php echo Helper::CSRFToken( 'otp_csrf_token' ); ?>

	<input type="hidden" name="rememberme" id="rememberme" value="0"/>
	<p class="otp-prompt">
		<?php esc_html_e( 'Enter the 6-digit code from your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.)', 'hda' ); ?>
	</p>
	<p class="auth extra">
		<label for="authcode">
			<?php esc_html_e( 'Authentication Code:', 'hda' ); ?>
		</label>
		<input
			required
			autofocus
			type="text"
			inputmode="numeric"
			name="authcode"
			id="authcode"
			class="input authcode"
			value=""
			autocomplete="one-time-code"
			pattern="[0-9]*"
			size="6"
			maxlength="6"
			placeholder="xxxxxx"
		/>
	</p>
	<p class="otp-help">
		<?php esc_html_e( "Can't access your authenticator app? Contact the site administrator.", 'hda' ); ?>
	</p>
	<?php submit_button( __( 'Verify', 'hda' ) ); ?>
</form>
