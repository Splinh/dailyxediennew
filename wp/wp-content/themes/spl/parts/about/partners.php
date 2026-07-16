<?php
/**
 * About — Partners section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'Đối tác & Thương hiệu';
$desc  = $data['description'] ?? 'Đại lý ủy quyền chính thức của các hãng xe điện hàng đầu';
$items = ! empty( $data['items'] ) ? $data['items'] : [
	[ 'name' => 'BLUERA' ],
	[ 'name' => 'YADEA' ],
	[ 'name' => 'VINFAST' ],
	[ 'name' => 'XMEN' ],
	[ 'name' => 'BLUESUDA' ],
	[ 'name' => 'VESPA' ],
];
?>
<section class="py-12 md:py-16 bg-white">
	<div class="max-w-7xl mx-auto px-4">
		<!-- Section Header -->
		<div class="text-center mb-10 reveal">
			<div class="flex items-center gap-3 justify-center mb-4">
				<span class="w-1.5 h-6 bg-primary-500 rounded-full"></span>
				<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
			</div>
			<?php if ( $desc ) : ?>
				<p class="text-sm text-slate-500 max-w-xl mx-auto"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $items ) ) : ?>
			<div class="grid grid-cols-3 md:grid-cols-6 gap-4 md:gap-6">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$logo_id   = $item['logo'] ?? 0;
					$logo_url  = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
					$name      = $item['name'] ?? '';
					?>
					<div class="bg-white border border-slate-100 rounded-2xl p-4 md:p-6 flex items-center justify-center h-20 md:h-28 shadow-premium hover:shadow-hover-card hover:-translate-y-1 transition-all group reveal">
						<?php if ( $logo_url ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="max-h-full max-w-full object-contain filter grayscale group-hover:grayscale-0 transition-all duration-300">
						<?php else : ?>
							<span class="text-sm md:text-lg font-black text-slate-300 group-hover:text-primary-500 transition-colors tracking-wide"><?php echo esc_html( $name ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
