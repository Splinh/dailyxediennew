<?php
/**
 * About — Stats section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$stats = ! empty( $data['stats'] ) ? $data['stats'] : [
	[ 'number' => '20+', 'label' => 'Cửa hàng toàn quốc' ],
	[ 'number' => '10K+', 'label' => 'Khách hàng hài lòng' ],
	[ 'number' => '50+', 'label' => 'Thương hiệu đối tác' ],
	[ 'number' => '98%', 'label' => 'Tỷ lệ hài lòng' ],
];
?>
<?php if ( ! empty( $stats ) ) : ?>
<section class="about-stats">
	<div class="container">
		<div class="about-stats__grid reveal">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="about-stat">
					<div class="about-stat__number"><?php echo esc_html( $stat['number'] ?? '' ); ?></div>
					<div class="about-stat__label"><?php echo esc_html( $stat['label'] ?? '' ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
