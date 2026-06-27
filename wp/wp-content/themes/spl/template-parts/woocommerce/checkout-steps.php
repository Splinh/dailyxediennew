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
<div class="bg-white border-b border-slate-100 py-5">
	<div class="container">
		<ol class="flex items-center justify-center gap-0 list-none p-0 m-0">
			<?php foreach ( $steps as $index => $label ) :
				$is_complete = $index < $current;
				$is_active   = $index === $current;

				// Circle classes.
				$circle_cls = 'w-7 h-7 rounded-full text-[11px] font-extrabold flex items-center justify-center shrink-0 transition-all';
				if ( $is_active ) {
					$circle_cls .= ' bg-primary text-white shadow-[0_0_0_4px_rgba(30,115,190,0.15)]';
				} elseif ( $is_complete ) {
					$circle_cls .= ' bg-emerald-500 text-white';
				} else {
					$circle_cls .= ' bg-slate-100 text-slate-400';
				}

				// Label classes.
				$label_cls = 'text-[13px] font-semibold';
				if ( $is_active ) {
					$label_cls .= ' text-slate-900';
				} elseif ( $is_complete ) {
					$label_cls .= ' text-emerald-500';
				} else {
					$label_cls .= ' text-slate-400';
				}

				// Connector classes.
				$connector_cls = 'w-10 h-0.5 mx-4 shrink-0';
				if ( $is_complete || $is_active ) {
					$connector_cls .= ' bg-emerald-500';
				} else {
					$connector_cls .= ' bg-slate-200';
				}
				?>
				<li class="flex items-center gap-2 text-[13px] font-semibold text-slate-400">
					<?php if ( $index > 1 ) : ?>
						<span class="<?php echo esc_attr( $connector_cls ); ?>"></span>
					<?php endif; ?>
					<span class="<?php echo esc_attr( $circle_cls ); ?>"><?php echo esc_html( $index ); ?></span>
					<strong class="<?php echo esc_attr( $label_cls ); ?>"><?php echo esc_html( $label ); ?></strong>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</div>
