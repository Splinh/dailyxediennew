<?php
/**
 * Cooperation template part - Partnership Packages.
 *
 * @package SPL
 */

use SPL\Core\Helper;

$title    = $args['title'] ?? 'Gói hợp tác đại lý';
$subtitle = $args['subtitle'] ?? 'Chọn gói phù hợp với quy mô kinh doanh của bạn';

$packages = $args['packages'] ?? [
	[
		'badge'       => 'Cơ bản',
		'title'       => 'Đại lý Cấp 3',
		'subtitle'    => 'Phù hợp cửa hàng nhỏ, mới khởi nghiệp',
		'discount'    => '20%',
		'details'     => "Đặt hàng tối thiểu 10 xe/tháng\nHỗ trợ ảnh/video sản phẩm\nĐào tạo bán hàng cơ bản\nBảo hành chính hãng 3 năm\nx Bảo vệ vùng bán\nx Hỗ trợ chạy ads",
		'btn_text'    => 'Đăng ký tư vấn',
		'btn_link'    => '#register-form',
		'is_featured' => 0,
	],
	[
		'badge'       => 'Phổ biến nhất',
		'title'       => 'Đại lý Cấp 2',
		'subtitle'    => 'Phù hợp cửa hàng trung bình, muốn mở rộng',
		'discount'    => '28%',
		'details'     => "Đặt hàng tối thiểu 30 xe/tháng\nHỗ trợ ảnh/video + banner quảng cáo\nĐào tạo bán hàng + kỹ thuật chuyên sâu\nBảo hành chính hãng 3 năm\nBảo vệ vùng bán theo quận/huyện\nx Hỗ trợ chạy ads",
		'btn_text'    => 'Đăng ký ngay',
		'btn_link'    => '#register-form',
		'is_featured' => 1,
	],
	[
		'badge'       => 'Premium',
		'title'       => 'Đại lý Cấp 1',
		'subtitle'    => 'Showroom lớn, đối tác chiến lược',
		'discount'    => '35%',
		'details'     => "Đặt hàng tối thiểu 80 xe/tháng\nFull bộ marketing: ảnh, video, landing page\nĐào tạo VIP + tham quan nhà máy\nBảo hành chính hãng 3 năm\nBảo vệ vùng bán theo tỉnh/thành\nHỗ trợ chạy ads lên đến 5tr/tháng",
		'btn_text'    => 'Liên hệ hợp tác',
		'btn_link'    => '#register-form',
		'is_featured' => 0,
	]
];
?>

<section class="max-w-7xl mx-auto px-4 py-14 md:py-20 border-t border-slate-100" id="packages">
	<div class="text-center mb-12">
		<div class="flex items-center gap-3 justify-center mb-4">
			<span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
			<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<?php if ( $subtitle ) : ?>
			<p class="text-sm text-slate-500 max-w-xl mx-auto"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $packages ) ) : ?>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
			<?php foreach ( $packages as $pkg ) :
				$is_featured = ! empty( $pkg['is_featured'] );
				?>
				<div class="bg-white border <?php echo $is_featured ? 'border-2 border-primary' : 'border-slate-200'; ?> rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all duration-300 flex flex-col justify-between group relative">
					<?php if ( $is_featured ) : ?>
						<div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary to-emerald-500"></div>
					<?php endif; ?>

					<div>
						<!-- Header -->
						<div class="<?php echo $is_featured ? 'bg-primary/5 border-primary/10' : 'bg-slate-50 border-slate-100'; ?> p-6 text-center border-b">
							<?php if ( ! empty( $pkg['badge'] ) ) : ?>
								<span class="inline-flex items-center gap-1.5 <?php echo $is_featured ? 'bg-primary text-white' : 'bg-slate-200 text-slate-600'; ?> text-[10px] font-bold px-3 py-1 rounded-full uppercase mb-3">
									<?php if ( $is_featured ) : ?>
										<svg class="w-2.5 h-2.5 fill-white" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
									<?php endif; ?>
									<?php echo esc_html( $pkg['badge'] ); ?>
								</span>
							<?php endif; ?>
							<h3 class="text-lg font-black text-slate-900"><?php echo esc_html( $pkg['title'] ); ?></h3>
							<p class="text-xs <?php echo $is_featured ? 'text-slate-500' : 'text-slate-400'; ?> mt-1"><?php echo esc_html( $pkg['subtitle'] ); ?></p>
						</div>

						<!-- Details & Discount -->
						<div class="p-6">
							<div class="text-center mb-5">
								<span class="text-3xl font-black <?php echo $is_featured ? 'text-primary' : 'text-slate-900'; ?>"><?php echo esc_html( $pkg['discount'] ); ?></span>
								<span class="text-sm text-slate-400 font-medium ml-1"><?php esc_html_e( 'chiết khấu', 'spl' ); ?></span>
							</div>

							<?php if ( ! empty( $pkg['details'] ) ) : 
								$lines = explode( "\n", $pkg['details'] );
								?>
								<ul class="space-y-2.5 text-xs text-slate-600">
									<?php foreach ( $lines as $line ) :
										$line = trim( $line );
										if ( empty( $line ) ) { continue; }
										$is_unavailable = false;
										if ( str_starts_with( $line, 'x ' ) || str_starts_with( $line, 'x:' ) || str_starts_with( $line, '[x]' ) ) {
											$is_unavailable = true;
											$line = ltrim( substr( $line, str_starts_with( $line, '[x]' ) ? 3 : 2 ) );
										}
										?>
										<li class="flex items-start gap-2 <?php echo $is_unavailable ? 'text-slate-400' : ''; ?>">
											<?php if ( $is_unavailable ) : ?>
												<svg class="w-3.5 h-3.5 text-slate-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
											<?php else : ?>
												<svg class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
											<?php endif; ?>
											<span><?php echo wp_kses_post( $line ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					</div>

					<div class="px-6 pb-6">
						<a href="<?php echo esc_url( $pkg['btn_link'] ); ?>" class="block text-center w-full <?php echo $is_featured ? 'bg-primary hover:bg-primary-hover text-white shadow-lg shadow-primary/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?> font-bold text-xs py-3 rounded-xl transition-all duration-200">
							<?php echo esc_html( $pkg['btn_text'] ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
