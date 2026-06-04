<?php

use HDAddons\Helper;
use HDAddons\Modules\SocialLink\SocialLinkModule;

\defined( 'ABSPATH' ) || exit;

$socialOptions = SocialLinkModule::getOptions();
$socialLinks   = SocialLinkModule::getFollowsLinks();

?>
<div class="container">
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Social Links', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-share"></span>
				<?php esc_html_e( 'Configure social media profiles used across the site. Leave URL empty to hide a specific social link.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<?php
			if ( empty( $socialLinks ) ) {
				echo '<div class="col-span-full"><p><b>' . esc_html__( 'No data available or configuration not initialized.', 'hda' ) . '</b></p></div>';
			} else {
				foreach ( $socialLinks as $key => $social ) :
					if ( empty( $social['name'] ) || empty( $social['icon'] ) ) {
						continue;
					}

					$name        = $social['name'];
					$icon        = $social['icon'];
					$url         = $socialOptions[ $key ]['url'] ?? ( $social['url'] ?? '' );
					$placeholder = $social['placeholder'] ?? '';
					$iconHtml    = Helper::renderIcon( $icon, $name );
					?>
				<div class="section section-text">
					<label class="heading" for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></label>
					<div class="option">
						<div class="controls relative flex items-center">
							<span class="absolute left-3 flex items-center justify-center text-slate-500 pointer-events-none [&>svg]:w-5 [&>svg]:h-5 [&>i]:text-lg">
								<?php echo wp_kses_post( $iconHtml ); ?>
							</span>
							<input class="input w-full" type="url" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( SocialLinkModule::optionKey() ); ?>[<?php echo esc_attr( $key ); ?>-url]" value="<?php echo esc_url( $url ); ?>" title="URL" placeholder="<?php echo esc_attr( $placeholder ); ?>">
						</div>
					</div>
				</div>
					<?php
				endforeach;
			}
			?>
		</div>
	</fieldset>
</div>
