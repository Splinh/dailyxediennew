<?php
/**
 * ACF Options Pages — Language Switcher view.
 *
 * Rendered inside the Publish metabox sidebar via
 * `acf/options_page/submitbox_before_major_actions`.
 *
 * @var array  $languages    PLL language objects.
 * @var string $defaultSlug  Default language slug.
 * @var string $postId       Current options page post_id.
 * @var array  $statusMap    ['lang_slug' => bool] — true if has data.
 *
 * @package SPL\Modules\PLL\ACF\Options
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="pll-options-languages" style="padding: 10px 10px 0; border-bottom: 1px solid #dcdcde; margin-bottom: 10px;">
	<strong style="display: block; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; color: #646970;">
		<?php esc_html_e( 'Translations', 'SPL' ); ?>
	</strong>

	<?php foreach ( $languages as $lang ) : ?>
		<?php
		$isDefault = ( $lang->slug === $defaultSlug );
		$hasData   = $statusMap[ $lang->slug ] ?? false;
		$dotColor  = $hasData ? '#00a32a' : '#dcdcde';
		$dotTitle  = $hasData ? __( 'Has translation', 'SPL' ) : __( 'No translation', 'SPL' );
		?>
		<div class="pll-options-lang-row"
			style="display: flex; align-items: center; gap: 8px; padding: 4px 0;"
			data-lang="<?php echo esc_attr( $lang->slug ); ?>"
			data-has-data="<?php echo esc_attr( $hasData ? '1' : '0' ); ?>"
			data-post-id="<?php echo esc_attr( $postId ); ?>">

			<?php if ( ! empty( $lang->flag_url ) ) : ?>
				<img src="<?php echo esc_url( set_url_scheme( $lang->flag_url ) ); ?>"
					alt="<?php echo esc_attr( $lang->name ); ?>"
					style="width: 16px; height: 11px;">
			<?php endif; ?>

			<span style="flex: 1; font-size: 13px;">
				<?php echo esc_html( $lang->name ); ?>
			</span>

			<span class="pll-status-dot"
				style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo esc_attr( $dotColor ); ?>;"
				title="<?php echo esc_attr( $dotTitle ); ?>">
			</span>

			<?php if ( $isDefault ) : ?>
				<span style="font-size: 11px; color: #646970; font-style: italic;">
					<?php esc_html_e( 'default', 'SPL' ); ?>
				</span>
			<?php else : ?>
				<button type="button"
					class="button button-small pll-translate-btn"
					data-lang="<?php echo esc_attr( $lang->slug ); ?>"
					data-lang-name="<?php echo esc_attr( $lang->name ); ?>"
					style="min-height: 26px; line-height: 24px; padding: 0 8px; font-size: 11px;">
					<?php esc_html_e( 'Translate', 'SPL' ); ?>
				</button>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
