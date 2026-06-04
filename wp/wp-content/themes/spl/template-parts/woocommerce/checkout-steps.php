<?php
/**
 * Cart and checkout progress bar.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$current = is_order_received_page() ? 3 : ( is_checkout() ? 2 : 1 );
$steps   = [
	1 => __( 'Giỏ hàng', 'spl' ),
	2 => __( 'Thanh toán', 'spl' ),
	3 => __( 'Hoàn tất', 'spl' ),
];
?>
<div class="commerce-steps">
	<div class="container">
		<ol>
			<?php foreach ( $steps as $index => $label ) : ?>
				<li class="<?php echo $index < $current ? 'is-complete' : ( $index === $current ? 'is-active' : '' ); ?>">
					<span><?php echo esc_html( $index ); ?></span>
					<strong><?php echo esc_html( $label ); ?></strong>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</div>
