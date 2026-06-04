<?php
/**
 * Filter Horizontal Template — Top bar with CSS popover panels.
 *
 * @var array<string, mixed> $args {
 *   @type array  $groups    Filter groups with id, label, html, config.
 *   @type int    $presetId  Preset ID.
 *   @type string $layout    Layout mode.
 *   @type string $trigger   Trigger mode.
 *   @type string $chipsHtml Server-rendered chips HTML.
 *   @type string $class     Extra CSS class.
 * }
 *
 * @package SPL\Modules\WooCommerce\Filter
 */

defined( 'ABSPATH' ) || exit;

$groups    = $args['groups'] ?? [];
$presetId  = $args['presetId'] ?? 0;
$trigger   = $args['trigger'] ?? 'hybrid';
$chipsHtml = $args['chipsHtml'] ?? '';
$class     = $args['class'] ?? '';

if ( empty( $groups ) ) {
	return;
}
?>
<div class="hd-filter hd-filter--horizontal <?php echo esc_attr( $class ); ?>"
	data-wc-filter
	data-preset="<?php echo absint( $presetId ); ?>"
	data-trigger="<?php echo esc_attr( $trigger ); ?>">

	<div class="hd-filter__bar">
		<?php
		foreach ( $groups as $group ) :
			$config   = $group['config'];
			$adoptive = \SPL\Modules\WooCommerce\Filter\Enum\AdoptiveMode::fromConfig( $config['adoptive'] ?? 'show' )->value;
			$moreData = '';

			if ( ! empty( $config['more_less'] ) ) {
				$moreData = sprintf( ' data-more-less="%d"', absint( $config['more_less_count'] ?? 5 ) );
			}
			?>
			<div class="hd-filter__popover-wrap" data-filter-group="<?php echo esc_attr( $group['id'] ); ?>" data-adoptive="<?php echo esc_attr( $adoptive ); ?>">
				<button type="button" class="hd-filter__popover-trigger">
					<?php echo esc_html( $group['label'] ); ?>
					<span class="hd-filter__popover-arrow dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<div class="hd-filter__popover-panel"<?php echo $moreData; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php if ( ! empty( $config['searchable'] ) ) : ?>
						<input type="search" class="hd-filter__term-search" data-searchable
							placeholder="<?php esc_attr_e( 'Search...', 'SPL' ); ?>" />
					<?php endif; ?>
					<div class="hd-filter__body">
						<?php echo $group['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<button type="button" class="hd-filter__reset" data-filter-reset>
			<?php esc_html_e( 'Xóa bộ lọc', 'SPL' ); ?>
		</button>
	</div>

	<?php if ( $chipsHtml ) : ?>
		<?php echo $chipsHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</div>
