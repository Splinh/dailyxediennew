<?php
/**
 * Home page — Triple Promotional Banners.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$banners = $data['banners'] ?? [];

// Default fallback banners if empty.
if ( empty( $banners ) ) {
	$banners = [
		[
			'badge'       => __( 'Ưu đãi kép', 'spl' ),
			'title'       => __( 'TRẢ GÓP 0%', 'spl' ),
			'description' => __( 'Thủ tục nhanh chóng 15 phút, xét duyệt tức thì.', 'spl' ),
			'gradient'    => 'blue',
			'link'        => [ 'title' => __( 'XEM NGAY', 'spl' ), 'url' => '#consult-form' ],
		],
		[
			'badge'       => __( 'Chất lượng vàng', 'spl' ),
			'title'       => __( 'ẮC QUY CHÍNH HÃNG', 'spl' ),
			'description' => __( 'Bền bỉ vượt thời gian, an toàn tối đa cho xe.', 'spl' ),
			'gradient'    => 'dark',
			'link'        => [ 'title' => __( 'MUA NGAY', 'spl' ), 'url' => '#consult-form' ],
		],
		[
			'badge'       => __( 'Độc quyền', 'spl' ),
			'title'       => __( 'PHỤ KIỆN CHÍNH HÃNG', 'spl' ),
			'description' => __( 'Phụ tùng, trang trí đa dạng, nâng tầm xế yêu.', 'spl' ),
			'gradient'    => 'green',
			'link'        => [ 'title' => __( 'XEM NGAY', 'spl' ), 'url' => '#consult-form' ],
		],
	];
}

// Gradient class helper mapping
$gradient_classes = [
	'blue'  => 'from-blue-500 to-indigo-600',
	'dark'  => 'from-slate-800 to-slate-900',
	'green' => 'from-emerald-500 to-teal-600',
];
$btn_classes = [
	'blue'  => 'bg-white text-blue-600 hover:bg-slate-100',
	'dark'  => 'bg-emerald-500 text-white hover:bg-emerald-600',
	'green' => 'bg-white text-emerald-600 hover:bg-slate-100',
];
$badge_classes = [
	'blue'  => 'bg-white/20 text-white',
	'dark'  => 'bg-emerald-500/20 text-emerald-400',
	'green' => 'bg-white/20 text-white',
];
?>
<section class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
	<?php foreach ( $banners as $b ) :
		$grad_key = $b['gradient'] ?? 'blue';
		$grad_cls = $gradient_classes[ $grad_key ] ?? $gradient_classes['blue'];
		$btn_cls  = $btn_classes[ $grad_key ] ?? $btn_classes['blue'];
		$bdg_cls  = $badge_classes[ $grad_key ] ?? $badge_classes['blue'];
		$link     = $b['link'] ?? null;
		?>
		<div class="bg-gradient-to-r <?php echo esc_attr( $grad_cls ); ?> rounded-2xl p-6 text-white relative overflow-hidden shadow-lg group hover:-translate-y-1 transition-all duration-300">
			<div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
			<div class="relative z-10 space-y-3">
				<span class="<?php echo esc_attr( $bdg_cls ); ?> font-bold text-[10px] px-2.5 py-1 rounded-full uppercase tracking-widest inline-block"><?php echo esc_html( $b['badge'] ?? '' ); ?></span>
				<h3 class="text-xl font-extrabold leading-tight"><?php echo esc_html( $b['title'] ?? '' ); ?></h3>
				<p class="text-xs text-blue-100 leading-relaxed max-w-[200px] opacity-90"><?php echo esc_html( $b['description'] ?? '' ); ?></p>
				<?php if ( $link && ! empty( $link['url'] ) ) : ?>
					<a href="<?php echo esc_url( $link['url'] ); ?>" class="<?php echo esc_attr( $btn_cls ); ?> font-bold text-xs px-5 py-2.5 rounded-lg shadow-md transition-colors inline-block" target="<?php echo esc_attr( $link['target'] ?? '' ); ?>">
						<?php echo esc_html( $link['title'] ?: __( 'XEM NGAY', 'spl' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</section>
