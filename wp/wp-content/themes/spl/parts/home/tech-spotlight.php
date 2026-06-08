<?php
/**
 * Home page — Tech Spotlight section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'Công nghệ thông minh', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Công nghệ bứt phá mọi giới hạn', 'spl' );
$features = $data['features'] ?? [];

// Default tech features if empty.
if ( empty( $features ) ) {
	$features = [
		[
			'feature_id'   => 'bms',
			'feature_name' => __( 'Quản lý Pin BMS', 'spl' ),
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="16" height="12" rx="2" ry="2"/><line x1="22" y1="11" x2="22" y2="15"/><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/></svg>',
			'title'        => __( 'Hệ thống Pin LFP & Quản lý Pin BMS Thông Minh', 'spl' ),
			'description'  => __( 'Pin LFP thế hệ mới kết hợp cùng bộ mạch quản lý BMS giúp điều phối dòng xả tối ưu, kiểm soát nhiệt độ phòng tránh cháy nổ tuyệt đối và gia tăng tuổi thọ pin gấp 3 lần bình thường.', 'spl' ),
			'image'        => get_theme_file_uri( 'resources/img/bms-battery.png' ),
			'details'      => [
				[ 'label' => __( 'Tuổi thọ Pin', 'spl' ), 'value' => __( '2.000 chu kỳ sạc/xả', 'spl' ) ],
				[ 'label' => __( 'Quãng đường sạc', 'spl' ), 'value' => __( '120km / một lần sạc', 'spl' ) ],
				[ 'label' => __( 'Công nghệ bảo vệ', 'spl' ), 'value' => __( 'Chống nước IP67 tuyệt đối', 'spl' ) ],
			],
		],
		[
			'feature_id'   => 'fingerprint',
			'feature_name' => __( 'Mở khóa Vân Tay', 'spl' ),
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-4.3-3-7-7-7s-7 2.7-7 7 3 7 7 7z"/><path d="M12 2a10 10 0 0 0-10 10c0 2.2.8 4.2 2 5.7"/><path d="M14 15a2 2 0 1 0-4 0"/></svg>',
			'title'        => __( 'Khóa Vân Tay Một Chạm Siêu Nhạy', 'spl' ),
			'description'  => __( 'Công nghệ vân tay sinh trắc học tiên tiến tích hợp ngay trên tay lái. Nhận diện chỉ 0.1s, chống sao chép và bảo mật tuyệt đối cho phương tiện của bạn.', 'spl' ),
			'image'        => get_theme_file_uri( 'resources/img/fingerprint-lock.png' ),
			'details'      => [
				[ 'label' => __( 'Tốc độ nhận diện', 'spl' ), 'value' => __( '0.1 giây', 'spl' ) ],
				[ 'label' => __( 'Dung lượng lưu trữ', 'spl' ), 'value' => __( 'Đến 10 vân tay khác nhau', 'spl' ) ],
				[ 'label' => __( 'Khóa cơ học dự phòng', 'spl' ), 'value' => __( 'Tích hợp chìa CNC chống sao chép', 'spl' ) ],
			],
		],
	];
}
?>
<section id="ai-tech-spotlight" class="max-w-7xl mx-auto px-4 mb-16">
	<div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-3xl p-6 md:p-12 text-white relative overflow-hidden shadow-2xl">
		<div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(99,102,241,0.15),transparent_60%)] pointer-events-none"></div>

		<!-- Section Heading -->
		<div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
			<div>
				<span class="text-xs text-primary-300 font-extrabold uppercase tracking-widest bg-primary-950/50 border border-primary-800/40 px-3 py-1.5 rounded-full inline-block mb-3">Tech Spotlight</span>
				<h2 class="text-2xl md:text-3xl font-black tracking-tight text-white"><?php echo esc_html( $title ); ?></h2>
				<p class="text-slate-400 text-xs md:text-sm mt-1.5"><?php echo esc_html( $subtitle ); ?></p>
			</div>
		</div>

		<!-- Main Layout Grid -->
		<div class="relative z-10 grid grid-cols-1 lg:grid-cols-10 gap-8 items-start">
			<!-- Sidebar selector controls -->
			<div class="lg:col-span-3 flex lg:flex-col gap-2 overflow-x-auto no-scrollbar w-full pb-3 lg:pb-0" role="tablist" aria-label="<?php esc_attr_e( 'Chọn tính năng công nghệ', 'spl' ); ?>">
				<?php foreach ( $features as $index => $feat ) :
					$feat_id = $feat['feature_id'] ?? '';
					$feat_name = $feat['feature_name'] ?? '';
					$active_btn = $index === 0
						? 'bg-gradient-to-r from-primary to-indigo-600 border-primary text-white shadow-lg shadow-primary/20'
						: 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10 hover:text-white';
					?>
					<button onclick="switchTechTab('<?php echo esc_attr( $feat_id ); ?>', this)"
						role="tab"
						aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
						class="w-full flex items-center gap-3.5 px-4.5 py-4 border text-left rounded-2xl font-bold text-xs tracking-wider transition-all whitespace-nowrap cursor-pointer <?php echo esc_attr( $active_btn ); ?>">
						<span class="shrink-0"><?php echo $feat['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php echo esc_html( $feat_name ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<!-- Dynamic Tab Panel Area -->
			<div class="lg:col-span-7 bg-white/5 border border-white/10 rounded-2xl p-5 md:p-8 backdrop-blur-md min-h-[350px] md:min-h-[380px] flex flex-col justify-between" id="ai-tab-content">
				<?php foreach ( $features as $index => $feat ) :
					$feat_id = $feat['feature_id'] ?? '';
					$feat_title = $feat['title'] ?? '';
					$feat_desc = $feat['description'] ?? '';
					$img_id = $feat['image'] ?? 0;
					$img_url = is_numeric( $img_id ) ? wp_get_attachment_image_url( $img_id, 'large' ) : (string) $img_id;
					$details = $feat['details'] ?? [];
					$active_panel = $index === 0 ? 'flex' : 'hidden';
					?>
					<div class="ai-tab-panel flex-col md:flex-row gap-6 items-center <?php echo esc_attr( $active_panel ); ?>" id="panel-<?php echo esc_attr( $feat_id ); ?>">
						<div class="flex-grow space-y-4 md:w-3/5">
							<h3 class="font-extrabold text-base md:text-xl text-white leading-tight"><?php echo esc_html( $feat_title ); ?></h3>
							<p class="text-slate-300 text-xs leading-relaxed"><?php echo esc_html( $feat_desc ); ?></p>
							
							<!-- Parameters grid -->
							<div class="grid grid-cols-2 gap-3.5 pt-3.5 border-t border-white/10">
								<?php foreach ( $details as $row ) : ?>
									<div>
										<span class="text-[10px] text-slate-400 font-bold uppercase block tracking-wider"><?php echo esc_html( $row['label'] ?? '' ); ?></span>
										<p class="text-xs font-black text-white mt-0.5"><?php echo esc_html( $row['value'] ?? '' ); ?></p>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="w-full md:w-2/5 flex items-center justify-center min-h-[160px]">
							<?php if ( $img_url ) : ?>
								<img loading="lazy" src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $feat_title ); ?>" class="max-h-44 object-contain filter drop-shadow-[0_10px_20px_rgba(99,102,241,0.3)] hover:scale-102 transition-transform duration-300">
							<?php else : ?>
								<div class="w-full max-w-[260px] aspect-[4/3] bg-gradient-to-tr from-primary to-indigo-600 rounded-xl flex items-center justify-center border border-white/20 shadow-inner">
									<?php echo spl_icon( 'bolt', 'w-16 h-16 text-white/60' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
