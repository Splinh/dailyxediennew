<?php

use HDAddons\Helper;
use HDAddons\Modules\LoginSecurity\LoginSecurityModule;

\defined( 'ABSPATH' ) || exit;

$user_id        = $args['uid'] ?? 0;
$sentAt         = $args['send_at'] ?? 0;
$resendInterval = $args['resend_interval'] ?? 0;
$secondsLeft    = max( 0, ( $sentAt + $resendInterval ) - time() );
$channel        = $args['channel'] ?? '';
$recipientHint  = $args['recipient_hint'] ?? '';

if ( ! empty( $args['error'] ) ) :
	?>
<div id="login_error" class="notice notice-error">
	<p><strong>Error:</strong> <?php echo esc_html( $args['error'] ); ?></p>
</div>
<?php endif ?>

<form name="otp_validate_form" id="loginform" action="<?php echo esc_url( $args['action'] ?? '' ); ?>" method="post">
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
		<?php
		if ( $channel && $recipientHint ) {
			printf(
				/* translators: 1: channel name (Telegram, Email), 2: masked recipient (***5678, d***@gmail.com) */
				esc_html__( 'A verification code has been sent to your %1$s (%2$s).', 'hda' ),
				'<strong>' . esc_html( $channel ) . '</strong>',
				'<code>' . esc_html( $recipientHint ) . '</code>'
			);
		} else {
			esc_html_e( 'Enter a recovery code.', 'hda' );
		}
		?>
	</p>
	<p class="auth extra">
		<label for="authcode">
			<?php esc_html_e( 'Verification Code:', 'hda' ); ?>
			<span id="countdown" data-time="<?php echo (int) $secondsLeft; ?>" data-interval="<?php echo (int) $resendInterval; ?>"></span>
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
			pattern="[0-9]*"
			size="<?php echo (int) ( $args['otp_digits'] ?? 20 ); ?>"
			maxlength="<?php echo (int) ( $args['otp_digits'] ?? 20 ); ?>"
			placeholder="<?php echo str_repeat( 'x', (int) ( $args['otp_digits'] ?? 6 ) ); ?>"
			data-digits="<?php echo (int) ( $args['otp_digits'] ?? 6 ); ?>"
		/>
	</p>
	<p class="otp-help">
		<?php
		$login_opts = LoginSecurityModule::getCachedOptions();
		$custom_uri = $login_opts[ LoginSecurityModule::KEY_CUSTOM_LOGIN_URI ] ?? '';
		$login_link = ( $custom_uri && ! in_array( $custom_uri, [ 'wp-login.php', 'wp-admin' ], true ) )
			? home_url( $custom_uri )
			: wp_login_url();

		printf(
			/* translators: %s: login link */
			esc_html__( 'No code? %s to resend.', 'hda' ),
			'<a href="' . esc_url( $login_link ) . '">' . esc_html__( 'Login again', 'hda' ) . '</a>'
		);
		?>
	</p>
	<?php submit_button( __( 'Submit', 'hda' ) ); ?>
</form>

