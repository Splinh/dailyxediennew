<?php
/**
 * Home page — Tech Spotlight section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data     = $args ?? [];
$title    = ! empty( $data['title'] ) ? $data['title'] : __( 'Công nghệ thông minh', 'spl' );
$subtitle = ! empty( $data['subtitle'] ) ? $data['subtitle'] : __( 'Công nghệ bứt phá mọi giới hạn', 'spl' );
$features = $data['features'] ?? [];

// Default tech features if empty.
if ( empty( $features ) ) {
	$features = [
		[
			'feature_id'   => 'bms',
			'feature_name' => __( 'Quản lý Pin BMS', 'spl' ),
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="16" height="12" rx="2" ry="2"/><line x1="22" y1="11" x2="22" y2="15"/><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/></svg>',
			'title'        => __( 'Hệ thống Pin LFP & Quản lý Pin BMS Thông Minh', 'spl' ),
			'description'  => __( 'Pin LFP (Lithium Iron Phosphate) thế hệ mới tích hợp bộ mạch quản lý BMS 16 cell giúp điều phối dòng xả tối ưu, tự ngắt khi quá nhiệt, chống quá tải và gia tăng tuổi thọ pin gấp 3 lần so với pin chì thông thường. Được trang bị trên các dòng xe Bluera và AI Ebike.', 'spl' ),
			'image'        => get_theme_file_uri( 'resources/img/bms-battery-v2.png' ),
			'details'      => [
				[ 'label' => __( 'Tuổi thọ Pin', 'spl' ), 'value' => __( '2.000 chu kỳ sạc/xả', 'spl' ) ],
				[ 'label' => __( 'Quãng đường sạc', 'spl' ), 'value' => __( '80–120km / một lần sạc', 'spl' ) ],
				[ 'label' => __( 'Công nghệ bảo vệ', 'spl' ), 'value' => __( 'Chống nước IP67 tuyệt đối', 'spl' ) ],
			],
		],
		[
			'feature_id'   => 'fingerprint',
			'feature_name' => __( 'Mở khóa Vân Tay', 'spl' ),
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-4.3-3-7-7-7s-7 2.7-7 7 3 7 7 7z"/><path d="M12 2a10 10 0 0 0-10 10c0 2.2.8 4.2 2 5.7"/><path d="M14 15a2 2 0 1 0-4 0"/></svg>',
			'title'        => __( 'Khóa Vân Tay Một Chạm — Bảo Mật Sinh Trắc Học', 'spl' ),
			'description'  => __( 'Công nghệ vân tay bán dẫn (capacitive) tích hợp ngay tay lái, nhận diện chỉ 0.1 giây, hoạt động ổn định dưới mưa và mồ hôi. Chống sao chép vân tay giả, kết hợp chìa khóa CNC dự phòng. Có trên các dòng xe AI Ebike S5, S7 và Bluera Sportage.', 'spl' ),
			'image'        => get_theme_file_uri( 'resources/img/fingerprint-lock.png' ),
			'details'      => [
				[ 'label' => __( 'Tốc độ nhận diện', 'spl' ), 'value' => __( '0.1 giây / lần quét', 'spl' ) ],
				[ 'label' => __( 'Dung lượng lưu trữ', 'spl' ), 'value' => __( 'Lên đến 10 vân tay', 'spl' ) ],
				[ 'label' => __( 'Khóa dự phòng', 'spl' ), 'value' => __( 'Chìa CNC chống sao chép', 'spl' ) ],
			],
		],
		[
			'feature_id'   => 'smart-app',
			'feature_name' => __( 'Kết nối App Thông Minh', 'spl' ),
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
			'title'        => __( 'Hệ Sinh Thái IoT & Ứng Dụng Di Động AI EBIKE', 'spl' ),
			'description'  => __( 'Ứng dụng tích hợp công nghệ đỉnh cao, kết nối IoT thời gian thực, quản lý pin BMS thông minh, định vị GPS toàn cầu chính xác cao và kích hoạt bảo hành điện tử tiện lợi. Tải ứng dụng để tối ưu hóa trải nghiệm lái xe điện của bạn.', 'spl' ),
			'image'        => get_theme_file_uri( 'resources/img/smart-app-connect-v2.png' ),
			'details'      => [
				[ 'label' => __( 'Hệ sinh thái', 'spl' ), 'value' => __( 'Kết nối IoT thời gian thực', 'spl' ) ],
				[ 'label' => __( 'Định vị xe', 'spl' ), 'value' => __( 'GPS chính xác cao', 'spl' ) ],
				[ 'label' => __( 'Bảo hành', 'spl' ), 'value' => __( 'Kích hoạt điện tử tự động', 'spl' ) ],
			],
		],
	];
}

// Ensure 4th tab "Tải Ứng Dụng AI EBIKE" exists in features list.
$has_download = false;
foreach ( $features as $f ) {
	if ( ( $f['feature_id'] ?? '' ) === 'download-app' ) {
		$has_download = true;
		break;
	}
}

if ( ! $has_download ) {
	$features[] = [
		'feature_id'   => 'download-app',
		'feature_name' => __( 'Tải Ứng Dụng AI EBIKE', 'spl' ),
		'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
		'title'        => __( 'Hệ Sinh Thái & Tải Ứng Dụng Di Động AI EBIKE', 'spl' ),
		'description'  => __( 'Quét mã QR hoặc truy cập App Store / Google Play để cài đặt ứng dụng AI EBIKE chính thức. Tự động kết nối xe, quản lý pin BMS, định vị GPS chính xác và kích hoạt bảo hành điện tử.', 'spl' ),
		'image'        => get_theme_file_uri( 'resources/img/smart-app-connect-v2.png' ),
		'details'      => [
			[ 'label' => __( 'Nền tảng hỗ trợ', 'spl' ), 'value' => __( 'iOS & Android', 'spl' ) ],
			[ 'label' => __( 'Dung lượng app', 'spl' ), 'value' => __( '~ 45 MB', 'spl' ) ],
			[ 'label' => __( 'Cập nhật', 'spl' ), 'value' => __( 'Miễn phí trọn đời', 'spl' ) ],
		],
	];
}

$qr_img_url           = get_theme_file_uri( 'resources/img/qr-ai-ebike.jpg' );
$app_store_url        = 'https://apps.apple.com/vn/app/ai-ebike/id6714467509?l=vi';
$google_play_url      = 'https://play.google.com/store/apps/details?id=com.expomobile87.AIEBIKE';
$download_landing_url = 'https://download.aiebike.vn/';
?>
<section id="ai-tech-spotlight" class="max-w-7xl mx-auto px-4 mb-8 md:mb-16">
	<div class="bg-gradient-to-br from-[#0B2545] via-[#0D2E58] to-[#0A192F] rounded-3xl p-6 md:p-12 text-white relative overflow-hidden shadow-2xl border border-slate-800">
		<div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(245,158,11,0.15),transparent_60%)] pointer-events-none"></div>

		<!-- Section Heading -->
		<div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
			<div>
				<span class="text-xs text-amber-400 font-black uppercase tracking-widest bg-amber-500/10 border border-amber-500/30 px-3.5 py-1.5 rounded-full inline-block mb-3 shadow-sm">Tech Spotlight</span>
				<h2 class="text-2xl md:text-3xl font-black tracking-tight text-white"><?php echo esc_html( $title ); ?></h2>
				<p class="text-slate-300 text-xs md:text-sm mt-1.5"><?php echo esc_html( $subtitle ); ?></p>
			</div>
		</div>

		<!-- Main Layout Grid -->
		<div class="relative z-10 grid grid-cols-1 lg:grid-cols-10 gap-8 items-start">
			<!-- Sidebar selector controls -->
			<div class="lg:col-span-3 flex flex-col gap-3.5 w-full">
				<div class="flex lg:flex-col gap-2 overflow-x-auto no-scrollbar w-full pb-3 lg:pb-0 snap-x scroll-smooth" role="tablist" aria-label="<?php esc_attr_e( 'Chọn tính năng công nghệ', 'spl' ); ?>">
					<?php foreach ( $features as $index => $feat ) :
						$feat_id   = $feat['feature_id'] ?? '';
						$feat_name = $feat['feature_name'] ?? '';
						$active_btn = $index === 0
							? 'bg-gradient-to-r from-amber-500 to-amber-600 border-amber-400 text-slate-950 font-black shadow-lg shadow-amber-500/25'
							: 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10 hover:text-white font-bold';
						?>
						<button onclick="switchTechTab('<?php echo esc_attr( $feat_id ); ?>', this)"
							role="tab"
							aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
							class="shrink-0 lg:shrink flex items-center gap-3.5 px-4.5 py-4 border text-left rounded-2xl text-xs tracking-wider transition-all whitespace-nowrap cursor-pointer snap-start <?php echo esc_attr( $active_btn ); ?>">
							<span class="shrink-0"><?php echo $feat['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php echo esc_html( $feat_name ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<!-- Sidebar QR & Store Badges Box (Image 2 Red Box / Image 3 style) -->
				<div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-md flex flex-col gap-2.5 shadow-lg">
					<span class="text-xs font-extrabold text-slate-200 flex items-center gap-1.5">
						<svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
						<?php esc_html_e( 'Tải ứng dụng ngay', 'spl' ); ?>
					</span>
					<div class="flex items-center gap-3">
						<div class="bg-white p-1 rounded-xl shrink-0 border border-white/20 shadow-sm">
							<img src="<?php echo esc_url( $qr_img_url ); ?>" alt="Mã QR Tải App AI EBIKE" class="w-20 h-20 md:w-22 md:h-22 object-contain rounded-lg">
						</div>
						<div class="flex flex-col gap-2 shrink-0">
							<a href="<?php echo esc_url( $app_store_url ); ?>" target="_blank" rel="noopener" class="inline-block transition-transform hover:scale-105">
								<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/appstore_black.webp' ) ); ?>" alt="Download on App Store" class="h-8 md:h-9 w-auto">
							</a>
							<a href="<?php echo esc_url( $google_play_url ); ?>" target="_blank" rel="noopener" class="inline-block transition-transform hover:scale-105">
								<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/googleplay_black.webp' ) ); ?>" alt="Get it on Google Play" class="h-8 md:h-9 w-auto">
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Dynamic Tab Panel Area -->
			<div class="lg:col-span-7 bg-white/5 border border-white/10 rounded-2xl p-5 md:p-8 backdrop-blur-md min-h-[350px] md:min-h-[380px] flex flex-col justify-between" id="ai-tab-content">
				<?php foreach ( $features as $index => $feat ) :
					$feat_id      = $feat['feature_id'] ?? '';
					$feat_title   = $feat['title'] ?? '';
					$feat_desc    = $feat['description'] ?? '';
					$img_id       = $feat['image'] ?? 0;
					$img_url      = is_numeric( $img_id ) && (int) $img_id > 0 ? wp_get_attachment_image_url( (int) $img_id, 'large' ) : (string) $img_id;
					if ( empty( $img_url ) ) {
						if ( 'bms' === $feat_id ) {
							$img_url = get_theme_file_uri( 'resources/img/bms-battery-v2.png' );
						} elseif ( 'fingerprint' === $feat_id ) {
							$img_url = get_theme_file_uri( 'resources/img/fingerprint-lock.png' );
						} else {
							$img_url = get_theme_file_uri( 'resources/img/smart-app-connect-v2.png' );
						}
					}
					$details      = $feat['details'] ?? [];
					$active_panel = $index === 0 ? 'flex' : 'hidden';
					$is_app_tab   = ( 'download-app' === $feat_id );
					?>
					<div class="ai-tab-panel flex-col md:flex-row gap-6 items-center <?php echo esc_attr( $active_panel ); ?>" id="panel-<?php echo esc_attr( $feat_id ); ?>">
						<div class="flex-grow space-y-4 md:w-7/12">
							<h3 class="font-extrabold text-base md:text-xl text-white leading-tight"><?php echo esc_html( $feat_title ); ?></h3>
							<p class="text-slate-300 text-xs leading-relaxed"><?php echo esc_html( $feat_desc ); ?></p>

							<?php if ( $is_app_tab ) : ?>
								<!-- QR Code + Store Badges Box Inside Tab Content (Image 3 style) -->
								<div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm space-y-2.5 my-3">
									<p class="text-xs font-extrabold text-slate-200 flex items-center gap-1.5">
										<svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
										<?php esc_html_e( 'Tải ứng dụng ngay', 'spl' ); ?>
									</p>
									<div class="flex items-center gap-4">
										<div class="bg-white p-1 rounded-xl shrink-0 border border-white/20 shadow-md">
											<img src="<?php echo esc_url( $qr_img_url ); ?>" alt="Mã QR Tải App AI EBIKE" class="w-24 h-24 md:w-28 md:h-28 object-contain rounded-lg">
										</div>
										<div class="flex flex-col gap-2.5 shrink-0">
											<a href="<?php echo esc_url( $app_store_url ); ?>" target="_blank" rel="noopener" class="inline-block transition-transform hover:scale-105">
												<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/appstore_black.webp' ) ); ?>" alt="Download on App Store" class="h-9 md:h-10 w-auto">
											</a>
											<a href="<?php echo esc_url( $google_play_url ); ?>" target="_blank" rel="noopener" class="inline-block transition-transform hover:scale-105">
												<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/googleplay_black.webp' ) ); ?>" alt="Get it on Google Play" class="h-9 md:h-10 w-auto">
											</a>
										</div>
									</div>
								</div>

								<!-- Link "Xem thêm về app" -->
								<div class="pt-1">
									<a href="<?php echo esc_url( $download_landing_url ); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white font-extrabold text-xs rounded-xl border border-emerald-400/30 shadow-md transition-all hover:scale-[1.02] active:scale-[0.98]">
										<span><?php esc_html_e( 'Xem thêm về app', 'spl' ); ?></span>
										<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
									</a>
								</div>
							<?php endif; ?>
							
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

						<div class="w-full md:w-5/12 flex items-center justify-center min-h-[220px] md:min-h-[280px]">
							<?php if ( $img_url ) : ?>
								<img loading="lazy" src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $feat_title ); ?>" class="w-full max-w-[280px] sm:max-w-[340px] md:max-w-none max-h-64 md:max-h-80 object-contain filter drop-shadow-[0_12px_24px_rgba(99,102,241,0.35)] hover:scale-105 transition-all duration-300">
							<?php else : ?>
								<div class="w-full max-w-[300px] aspect-[4/3] bg-gradient-to-tr from-primary to-indigo-600 rounded-xl flex items-center justify-center border border-white/20 shadow-inner">
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

