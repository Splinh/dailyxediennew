<?php
/**
 * Filter Vertical Template — Accordion layout using <details>/<summary>.
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
<div class="hd-filter hd-filter--vertical <?php echo esc_attr( $class ); ?>"
	data-wc-filter
	data-preset="<?php echo absint( $presetId ); ?>"
	data-trigger="<?php echo esc_attr( $trigger ); ?>">

	<?php if ( $chipsHtml ) : ?>
		<?php echo $chipsHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped ?>
	<?php endif; ?>

	<?php
	foreach ( $groups as $group ) :
		$config   = $group['config'];
		$collapse = ! empty( $config['collapse'] );
		$adoptive = \SPL\Modules\WooCommerce\Filter\Enum\AdoptiveMode::fromConfig( $config['adoptive'] ?? 'show' )->value;
		$moreData = '';

		if ( ! empty( $config['more_less'] ) ) {
			$moreData = sprintf( ' data-more-less="%d"', absint( $config['more_less_count'] ?? 5 ) );
		}
		?>
		<details class="hd-filter__group" data-filter-group="<?php echo esc_attr( $group['id'] ); ?>" data-adoptive="<?php echo esc_attr( $adoptive ); ?>"<?php echo $collapse ? '' : ' open'; ?><?php echo $moreData; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<summary class="hd-filter__title"><?php echo esc_html( $group['label'] ); ?></summary>
			<div class="hd-filter__body">
				<?php if ( ! empty( $config['searchable'] ) ) : ?>
					<input type="search" class="hd-filter__term-search" data-searchable
						placeholder="<?php esc_attr_e( 'Search...', 'SPL' ); ?>" />
				<?php endif; ?>
				<?php echo $group['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in render methods ?>
			</div>
		</details>
	<?php endforeach; ?>

	<div class="hd-filter__actions">
		<button type="button" class="hd-filter__reset" data-filter-reset>
			<?php esc_html_e( 'Xóa bộ lọc', 'SPL' ); ?>
		</button>
	</div>
</div>
