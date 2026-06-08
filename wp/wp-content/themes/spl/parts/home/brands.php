<?php
/**
 * Home page — Strategic Partner Brands.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'ĐỐI TÁC ĐỒNG HÀNH', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Các nhãn hiệu liên kết phân phối trực tiếp', 'spl' );
$brands = $data['brands'] ?? [];

// Default fallback brands if empty.
if ( empty( $brands ) ) {
	$brands = [
		[ 'name' => 'VINFAST', 'sub_label' => 'Smart E-Scooter', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'YADEA', 'sub_label' => 'Global Brand', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'DIBAO', 'sub_label' => 'Taiwan Quality', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'OSAKAR', 'sub_label' => 'Japan Standard', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'KAZUKI', 'sub_label' => 'Modern Look', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'TAILG', 'sub_label' => 'Premium Quality', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'NIJIA', 'sub_label' => 'Youthful Style', 'logo' => '', 'link' => '#' ],
		[ 'name' => 'DK BIKE', 'sub_label' => 'Eco-Friendly', 'logo' => '', 'link' => '#' ],
	];
}
?>
<section class="max-w-7xl mx-auto px-4 mb-16">
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-xl font-extrabold text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-xs text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
		<?php foreach ( $brands as $b ) :
			$logo_id = $b['logo'] ?? 0;
			$logo_url = is_numeric( $logo_id ) ? wp_get_attachment_image_url( $logo_id, 'medium' ) : (string) $logo_id;
			$link = $b['link'] ?? '#';
			$name = $b['name'] ?? '';
			$sub = $b['sub_label'] ?? '';
			
			// Decide text badge color depending on partner name
			$badge_color = 'text-primary';
			if ( false !== stripos( $name, 'vinfast' ) || false !== stripos( $name, 'dk' ) ) {
				$badge_color = 'text-emerald-500';
			} elseif ( false !== stripos( $name, 'osakar' ) ) {
				$badge_color = 'text-yellow-500';
			} elseif ( false !== stripos( $name, 'kazuki' ) ) {
				$badge_color = 'text-pink-500';
			} elseif ( false !== stripos( $name, 'nijia' ) ) {
				$badge_color = 'text-purple-500';
			}
			?>
			<a href="<?php echo esc_url( $link ); ?>" class="bg-white border border-slate-100 rounded-2xl p-4 flex flex-col items-center justify-center h-20 hover:border-primary-300 hover:shadow-sm transition-all cursor-pointer">
				<?php if ( $logo_url ) : ?>
					<img loading="lazy" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="max-h-12 object-contain">
				<?php else : ?>
					<span class="font-black text-slate-800 tracking-wider text-sm"><?php echo esc_html( $name ); ?></span>
					<span class="text-[9px] <?php echo esc_attr( $badge_color ); ?> font-bold uppercase mt-1"><?php echo esc_html( $sub ); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>
