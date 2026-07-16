<?php
/**
 * About — Team section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'Đội ngũ của chúng tôi';
$desc  = $data['description'] ?? 'Những con người tận tâm đứng sau mỗi chiếc xe giao đến tay bạn';
$items = ! empty( $data['items'] ) ? $data['items'] : [
	[
		'name'  => 'Nguyễn Văn A',
		'role'  => 'Giám đốc điều hành',
		'desc'  => 'Hơn 10 năm kinh nghiệm trong lĩnh vực phân phối xe điện.',
		'class' => 'from-primary-100 to-primary-50',
		'dot'   => 'bg-primary-500',
		'text'  => 'text-primary-500',
	],
	[
		'name'  => 'Trần Thị B',
		'role'  => 'Trưởng phòng kinh doanh',
		'desc'  => 'Chuyên gia tư vấn xe điện cho gia đình và cá nhân.',
		'class' => 'from-emerald-100 to-emerald-50',
		'dot'   => 'bg-emerald-500',
		'text'  => 'text-emerald-500',
	],
	[
		'name'  => 'Lê Văn C',
		'role'  => 'Trưởng phòng kỹ thuật',
		'desc'  => 'Phụ trách bảo hành, sửa chữa và kiểm tra chất lượng xe.',
		'class' => 'from-amber-100 to-amber-50',
		'dot'   => 'bg-amber-500',
		'text'  => 'text-amber-500',
	],
	[
		'name'  => 'Phạm Thị D',
		'role'  => 'Trưởng phòng CSKH',
		'desc'  => 'Luôn lắng nghe và giải quyết mọi vấn đề sau mua hàng.',
		'class' => 'from-rose-100 to-rose-50',
		'dot'   => 'bg-rose-500',
		'text'  => 'text-rose-500',
	],
];

$fallback_classes = [
	'from-primary-100 to-primary-50',
	'from-emerald-100 to-emerald-50',
	'from-amber-100 to-amber-50',
	'from-rose-100 to-rose-50',
];
$fallback_dots = [
	'bg-primary-500',
	'bg-emerald-500',
	'bg-amber-500',
	'bg-rose-500',
];
$fallback_texts = [
	'text-primary-500',
	'text-emerald-500',
	'text-amber-500',
	'text-rose-500',
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
			<div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$card_class = $item['class'] ?? $fallback_classes[ $index % count( $fallback_classes ) ];
					$dot_class  = $item['dot'] ?? $fallback_dots[ $index % count( $fallback_dots ) ];
					$text_class = $item['text'] ?? $fallback_texts[ $index % count( $fallback_texts ) ];
					$image_id   = $item['image'] ?? 0;
					$image_url  = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
					?>
					<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all group text-center reveal">
						<div class="h-40 md:h-52 bg-gradient-to-br <?php echo esc_attr( $card_class ); ?> flex items-center justify-center overflow-hidden relative">
							<?php if ( $image_url ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $item['name'] ?? '' ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
							<?php else : ?>
								<div class="w-20 h-20 md:w-24 md:h-24 rounded-full <?php echo esc_attr( $dot_class ); ?> flex items-center justify-center text-white group-hover:scale-110 transition-transform">
									<svg class="w-10 h-10 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								</div>
							<?php endif; ?>
						</div>
						<div class="p-4">
							<h3 class="font-bold text-slate-800 text-sm"><?php echo esc_html( $item['name'] ?? '' ); ?></h3>
							<p class="text-xs <?php echo esc_attr( $text_class ); ?> font-semibold mt-0.5"><?php echo esc_html( $item['role'] ?? '' ); ?></p>
							<p class="text-[11px] text-slate-400 mt-2 line-clamp-2"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
