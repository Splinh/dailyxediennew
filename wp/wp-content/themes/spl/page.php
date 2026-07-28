<?php
/**
 * The Template for displaying all single pages (Chính sách, Giới thiệu, Liên hệ...).
 *
 * Designed for High-Contrast, Premium Readability & Easy Policy Navigation.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$page_id      = get_the_ID();
	$updated      = get_the_modified_date( 'd/m/Y' );
	$hotline      = Helper::getField( 'hotline', 'option' ) ?: '0933 505 222';
	$hotline_disp = is_array( $hotline ) ? ( $hotline['title'] ?? '0933 505 222' ) : $hotline;
	$hotline_url  = 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline_disp );

	// Calculate reading time estimate (approx 220 words per minute)
	$content_text = wp_strip_all_tags( get_the_content() );
	$word_count   = count( preg_split( '/\s+/', trim( $content_text ) ) );
	$read_time    = max( 1, (int) ceil( $word_count / 220 ) );

	// Ministry of Industry and Trade (Bộ Công Thương) policy pages list for sidebar menu
	$policy_pages = [
		'chinh-sach-bao-hanh'          => __( 'Chính sách bảo hành', 'spl' ),
		'chinh-sach-doi-tra-hang'      => __( 'Chính sách đổi trả hàng', 'spl' ),
		'giao-hang-va-lap-dat'         => __( 'Giao hàng và lắp đặt', 'spl' ),
		'phuong-thuc-thanh-toan'       => __( 'Phương thức thanh toán', 'spl' ),
		'bao-mat-thong-tin-khach-hang' => __( 'Bảo mật thông tin khách hàng', 'spl' ),
		'chinh-sach-ban-hang-dailyxedien-vn' => __( 'Chính sách bán hàng', 'spl' ),
		'huong-dan-mua-hang'           => __( 'Hướng dẫn mua hàng', 'spl' ),
	];
	?>

	<!-- ===== BREADCRUMB BAR ===== -->
	<div class="bg-slate-100/80 border-b border-slate-200/80 py-2.5 px-4 text-xs">
		<div class="max-w-7xl mx-auto flex items-center gap-2 text-slate-500 font-medium overflow-x-auto whitespace-nowrap">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-primary transition-colors flex items-center gap-1.5 shrink-0">
				<?php echo spl_icon( 'home', 'w-3.5 h-3.5 text-slate-400' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
			</a>
			<span class="text-slate-300">/</span>
			<span class="text-slate-800 font-semibold truncate"><?php the_title(); ?></span>
		</div>
	</div>

	<!-- ===== MAIN PAGE CONTENT ===== -->
	<main id="main-content" class="max-w-7xl mx-auto px-4 py-6 md:py-10">
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

			<!-- LEFT / MAIN CONTENT (8 or 9 cols) -->
			<div class="lg:col-span-8 xl:col-span-9 space-y-6">
				<article class="bg-white border border-slate-200/90 rounded-2xl shadow-[0_2px_12px_rgba(0,0,0,0.04)] p-5 md:p-8 lg:p-10">
					
					<!-- Header / Meta -->
					<div class="border-b border-slate-100 pb-6 mb-6">
						<span class="inline-block bg-primary-50 text-primary font-bold text-[11px] uppercase tracking-wider px-3 py-1 rounded-md mb-3 border border-primary-100">
							<?php esc_html_e( 'Thông tin & Chính sách', 'spl' ); ?>
						</span>
						<h1 class="text-xl md:text-2xl lg:text-3xl font-black text-slate-900 leading-snug tracking-tight mb-4">
							<?php the_title(); ?>
						</h1>
						<div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-medium">
							<span class="flex items-center gap-1.5">
								<?php echo spl_icon( 'clock', 'w-3.5 h-3.5 text-slate-400' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php printf( esc_html__( 'Cập nhật: %s', 'spl' ), esc_html( $updated ) ); ?>
							</span>
							<span class="text-slate-300">•</span>
							<span class="flex items-center gap-1.5">
								<?php echo spl_icon( 'book-open', 'w-3.5 h-3.5 text-slate-400' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php printf( esc_html__( 'Thời gian đọc: ~%d phút', 'spl' ), $read_time ); ?>
							</span>
						</div>
					</div>

					<!-- Body Content -->
					<div class="page-content article-content">
						<?php the_content(); ?>
					</div>

					<!-- Bottom Assistance Callout -->
					<div class="mt-10 bg-slate-50 border border-slate-200/90 rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
						<div class="flex items-center gap-3.5">
							<div class="w-10 h-10 rounded-full bg-primary-50 text-primary flex items-center justify-center shrink-0 border border-primary-100">
								<?php echo spl_icon( 'phone', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div>
								<h4 class="font-bold text-slate-900 text-sm"><?php esc_html_e( 'Bạn có thắc mắc về chính sách này?', 'spl' ); ?></h4>
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Đội ngũ tư vấn luôn sẵn sàng giải đáp 24/7 qua Hotline miễn phí.', 'spl' ); ?></p>
							</div>
						</div>
						<a href="<?php echo esc_url( $hotline_url ); ?>" class="bg-primary hover:bg-primary-hover text-white text-xs font-bold px-5 py-2.5 rounded-lg transition-all shadow-sm shrink-0 flex items-center gap-2">
							<?php echo spl_icon( 'phone', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( $hotline_disp ); ?>
						</a>
					</div>

				</article>
			</div>

			<!-- RIGHT SIDEBAR (3 or 4 cols) -->
			<div class="lg:col-span-4 xl:col-span-3 space-y-6">

				<!-- Policy Quick Navigation Widget -->
				<div class="bg-white border border-slate-200/90 rounded-2xl shadow-sm p-5">
					<h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3 mb-3 flex items-center gap-2">
						<?php echo spl_icon( 'shield-check', 'w-4 h-4 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'DANH MỤC CHÍNH SÁCH', 'spl' ); ?>
					</h3>
					<ul class="space-y-1 text-xs font-medium">
						<?php foreach ( $policy_pages as $pslug => $ptitle ) :
							$pobj = get_page_by_path( $pslug );
							if ( ! $pobj ) { continue; }
							$is_active  = ( $pobj->ID === $page_id );
							$active_cls = $is_active ? 'bg-primary-50 text-primary font-bold border-l-2 border-primary pl-3' : 'text-slate-700 hover:text-primary hover:bg-slate-50 pl-2';
							?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $pobj->ID ) ); ?>" class="block py-2 pr-2 rounded-md transition-all <?php echo esc_attr( $active_cls ); ?>">
									<?php echo esc_html( $ptitle ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<!-- Store & Support Widget -->
				<div class="bg-gradient-to-br from-primary-900 to-navy text-white rounded-2xl p-5 space-y-3.5 shadow-md">
					<span class="bg-accent text-slate-900 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full inline-block"><?php esc_html_e( 'Hỗ Trợ 24/7', 'spl' ); ?></span>
					<h4 class="font-bold text-base text-white leading-snug"><?php esc_html_e( 'Hệ Thống Xe Điện Bluera Việt Nhật', 'spl' ); ?></h4>
					<p class="text-xs text-slate-300 leading-relaxed"><?php esc_html_e( 'Phân phối chính hãng, bảo hành tận tâm trên toàn quốc.', 'spl' ); ?></p>
					<div class="pt-2 border-t border-white/10 flex flex-col gap-2 text-xs">
						<a href="<?php echo esc_url( home_url( '/he-thong-cua-hang/' ) ); ?>" class="bg-white/10 hover:bg-white/20 text-white font-bold py-2 px-3 rounded-lg text-center transition-colors flex items-center justify-center gap-2">
							<?php echo spl_icon( 'map-pin', 'w-3.5 h-3.5 text-accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'Tìm cửa hàng gần nhất', 'spl' ); ?>
						</a>
						<a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>" class="bg-accent hover:bg-amber-400 text-slate-900 font-extrabold py-2 px-3 rounded-lg text-center transition-colors">
							<?php esc_html_e( 'Gửi thông tin liên hệ', 'spl' ); ?>
						</a>
					</div>
				</div>

			</div>

		</div>
	</main>

	<?php
endwhile;

get_footer();
