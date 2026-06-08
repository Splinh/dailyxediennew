<?php
/**
 * Home page — Latest News section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'Tin tức nổi bật', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Cập nhật tin tức và mẹo vặt sử dụng xe hữu ích', 'spl' );
$count = isset( $data['count'] ) ? absint( $data['count'] ) : 3;

?>
<section class="max-w-7xl mx-auto px-4 mb-16 scroll-mt-24" id="news-section">
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
		<?php
		$rendered = false;

		$posts_query = new \WP_Query( [
			'post_type'           => 'post',
			'posts_per_page'      => $count,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		] );

		if ( $posts_query->have_posts() ) :
			$rendered = true;
			while ( $posts_query->have_posts() ) :
				$posts_query->the_post();
				$img_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ?: wc_placeholder_img_src();
				?>
				<article class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all duration-300 group flex flex-col justify-between">
					<div>
						<div class="relative overflow-hidden aspect-[16/10]">
							<img loading="lazy" src="<?php echo esc_url( $img_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300">
						</div>
						<div class="p-5 space-y-2">
							<span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block"><?php echo esc_html( get_the_date() ); ?></span>
							<h3 class="font-bold text-slate-800 text-sm md:text-base line-clamp-2 group-hover:text-primary transition-colors leading-snug">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>
							<p class="text-xs text-slate-500 line-clamp-3 leading-relaxed"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
						</div>
					</div>
					<div class="p-5 pt-0">
						<a href="<?php the_permalink(); ?>" class="text-xs font-black text-primary hover:text-primary-hover transition-colors inline-flex items-center gap-1">
							<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
							<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
		endif;

		if ( ! $rendered ) :
			// Static fallback news
			$fallback = [
				[
					'title'   => 'Hướng Dẫn Sạc Pin Xe Máy Điện Đúng Cách Không Chai',
					'date'    => '04/06/2026',
					'excerpt' => 'Sạc pin đúng cách sẽ kéo dài tuổi thọ của pin LFP cũng như bảo vệ hệ thống quản lý nguồn điện an toàn hơn...',
					'img'     => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=400&q=80',
				],
				[
					'title'   => 'So Sánh Xe Máy Điện VinFast Feliz S Và Yadea Orla',
					'date'    => '02/06/2026',
					'excerpt' => 'Hai dòng xe điện đang cực hot trên thị trường phân khúc 30 triệu đồng có điểm gì nổi bật và phù hợp với ai...',
					'img'     => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=400&q=80',
				],
				[
					'title'   => 'Chính Sách Bảo Dưỡng Xe Điện Định Kỳ Tại Cửa Hàng',
					'date'    => '30/05/2026',
					'excerpt' => 'Kiểm tra thắng, hệ thống pin, mạch BMS định kỳ giúp bạn luôn di chuyển an toàn và tránh các sự cố hỏng hóc giữa đường...',
					'img'     => 'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=400&q=80',
				],
			];
			foreach ( $fallback as $item ) :
				?>
				<article class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all duration-300 group flex flex-col justify-between">
					<div>
						<div class="relative overflow-hidden aspect-[16/10]">
							<img loading="lazy" src="<?php echo esc_url( $item['img'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300">
						</div>
						<div class="p-5 space-y-2">
							<span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block"><?php echo esc_html( $item['date'] ); ?></span>
							<h3 class="font-bold text-slate-800 text-sm md:text-base line-clamp-2 group-hover:text-primary transition-colors leading-snug">
								<a href="#"><?php echo esc_html( $item['title'] ); ?></a>
							</h3>
							<p class="text-xs text-slate-500 line-clamp-3 leading-relaxed"><?php echo esc_html( $item['excerpt'] ); ?></p>
						</div>
					</div>
					<div class="p-5 pt-0">
						<a href="#" class="text-xs font-black text-primary hover:text-primary-hover transition-colors inline-flex items-center gap-1">
							<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
							<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</div>
				</article>
				<?php
			endforeach;
		endif;
		?>
	</div>
</section>
