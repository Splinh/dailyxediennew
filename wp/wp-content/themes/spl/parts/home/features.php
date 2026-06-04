<?php
/**
 * Home page — Features strip section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

$default_features = [
	[
		'icon'  => '<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
		'title' => __( 'Giao Hàng Toàn Quốc', 'spl' ),
		'desc'  => __( 'Miễn phí ship đơn từ 500K', 'spl' ),
	],
	[
		'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>',
		'title' => __( '100% Thiên Nhiên', 'spl' ),
		'desc'  => __( 'Nguồn gốc rõ ràng', 'spl' ),
	],
	[
		'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
		'title' => __( 'An Toàn & Chất Lượng', 'spl' ),
		'desc'  => __( 'Kiểm định nghiêm ngặt', 'spl' ),
	],
	[
		'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
		'title' => __( 'Tư Vấn Miễn Phí', 'spl' ),
		'desc'  => __( 'Hotline: 0901 806 930', 'spl' ),
	],
];

$features = $data['features'] ?? $default_features;

?>
<section class="features-strip">
	<div class="container">
		<div class="features-grid">
			<?php foreach ( $features as $item ) : ?>
				<div class="feature-item reveal">
					<div class="feature-item__icon"><?php echo $item['icon'] ?? ''; ?></div>
					<div>
						<div class="feature-item__title"><?php echo esc_html( $item['title'] ?? '' ); ?></div>
						<div class="feature-item__desc"><?php echo esc_html( $item['desc'] ?? '' ); ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
