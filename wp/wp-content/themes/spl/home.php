<?php
/**
 * The blog / posts listing template (home.php).
 *
 * Used when "Tin Tức" page is set as the posts page.
 * Matches htmlmau/tin-tuc.html layout using Tailwind utility classes:
 *   Hero → Category Tabs → Featured Post + Grid | Sidebar → Pagination
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

$archive_title = __( 'Tin tức & Kiến thức Xe điện', 'spl' );
$archive_desc  = __( 'Cập nhật xu hướng xe điện, hướng dẫn sử dụng, mẹo bảo dưỡng và đánh giá chi tiết từ chuyên gia.', 'spl' );
$is_cat        = false;

// Options.
$hotline     = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
$hotline_url = 'tel:' . preg_replace( '/\s+/', '', $hotline );
$ratio_css   = Helper::aspectRatioClass( 'post' );
?>

<!-- ===== HERO BANNER ===== -->
<section class="relative w-full bg-gradient-to-br from-[#165da0] via-[#1e73be] to-[#0e4880] overflow-hidden">
	<div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.12),transparent_50%)]"></div>
	<div class="relative z-10 max-w-7xl mx-auto px-4 py-10 md:py-14">
		<nav class="flex items-center gap-2 text-xs text-white/60 mb-5" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-white transition-colors">
				<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
			</a>
			<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="text-white font-semibold"><?php echo esc_html( $archive_title ); ?></span>
		</nav>
		<h1 class="text-2xl md:text-3xl lg:text-4xl font-black text-white leading-tight tracking-tight">
			<?php echo esc_html( $archive_title ); ?>
		</h1>
		<?php if ( $archive_desc ) : ?>
			<p class="text-sm text-slate-200 mt-3 max-w-2xl leading-relaxed"><?php echo wp_kses_post( $archive_desc ); ?></p>
		<?php endif; ?>
	</div>
</section>

<!-- ===== CATEGORY TABS ===== -->
<?php
$all_cats = get_categories( [
	'hide_empty' => true,
	'number'     => 10,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'exclude'    => [ (int) get_option( 'default_category' ) ],
] );

if ( $all_cats && ! is_wp_error( $all_cats ) ) :
	$blog_url = get_post_type_archive_link( 'post' ) ?: home_url( '/' );
	?>
	<div class="max-w-7xl mx-auto px-4 pt-8 md:pt-10">
		<div class="flex overflow-x-auto gap-2 pb-1" style="-ms-overflow-style:none;scrollbar-width:none;">
			<a href="<?php echo esc_url( $blog_url ); ?>"
			   class="px-5 py-2.5 text-xs font-bold rounded-full transition-all whitespace-nowrap bg-[#1e73be] text-white shadow-md shadow-[#1e73be]/30">
				<?php esc_html_e( 'Tất cả', 'spl' ); ?>
			</a>
			<?php foreach ( $all_cats as $cat ) : ?>
				<a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"
				   class="px-5 py-2.5 text-xs font-bold rounded-full transition-all whitespace-nowrap bg-slate-100 text-slate-600 hover:bg-slate-200">
					<?php echo esc_html( $cat->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<!-- ===== MAIN CONTENT ===== -->
<main id="blog-content" class="max-w-7xl mx-auto px-4 py-8 md:py-12">
	<div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-8">

		<!-- LEFT: Posts -->
		<div>
			<?php if ( have_posts() ) : ?>

				<?php
				// First 2 posts = featured (only on page 1).
				$paged = get_query_var( 'paged' ) ?: ( get_query_var( 'page' ) ?: 1 );
				if ( 1 === (int) $paged ) :
					$feat_posts = [];
					for ( $fi = 0; $fi < 2 && have_posts(); $fi++ ) {
						the_post();
						$fid          = get_the_ID();
						$f_cats       = get_the_category();
						$f_cat        = ! empty( $f_cats ) ? $f_cats[0] : null;
						$f_thumb      = get_the_post_thumbnail_url( $fid, 'large' );
						$feat_posts[] = [
							'id'        => $fid,
							'permalink' => get_permalink(),
							'title'     => get_the_title(),
							'cat'       => $f_cat,
							'thumb'     => $f_thumb,
							'date'      => get_the_date(),
							'excerpt'   => wp_trim_words( get_the_excerpt(), 20 ),
						];
					}
					?>
					<!-- Featured Posts (2 columns) -->
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
						<?php foreach ( $feat_posts as $fp ) : ?>
							<article class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] overflow-hidden group hover:shadow-[0_20px_40px_-4px_rgba(0,0,0,0.08)] transition-all duration-300">
								<a href="<?php echo esc_url( $fp['permalink'] ); ?>" class="block">
									<div class="<?php echo esc_attr( $ratio_css ); ?> bg-slate-100 relative overflow-hidden">
										<?php if ( $fp['thumb'] ) : ?>
											<img src="<?php echo esc_url( $fp['thumb'] ); ?>" alt="<?php echo esc_attr( $fp['title'] ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="eager" />
										<?php else : ?>
											<div class="w-full h-full min-h-[160px] bg-slate-100 flex items-center justify-center">
												<svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
											</div>
										<?php endif; ?>
										<div class="absolute top-3 right-3">
											<span class="bg-red-500 text-white text-[9px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow-sm"><?php esc_html_e( 'Nổi bật', 'spl' ); ?></span>
										</div>
									</div>
								</a>
								<div class="p-4 md:p-5 space-y-2">
									<div class="flex items-center gap-2">
										<?php if ( $fp['cat'] ) : ?>
											<span class="bg-[#f0f5ff] text-[#1e73be] text-[10px] font-bold px-2 py-0.5 rounded-full border border-[#e0ebff]"><?php echo esc_html( $fp['cat']->name ); ?></span>
										<?php endif; ?>
										<span class="text-[10px] text-slate-400 font-semibold"><?php echo esc_html( $fp['date'] ); ?></span>
									</div>
									<h2 class="text-sm md:text-base font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#1e73be] transition-colors">
										<a href="<?php echo esc_url( $fp['permalink'] ); ?>"><?php echo esc_html( $fp['title'] ); ?></a>
									</h2>
									<p class="text-xs text-slate-500 line-clamp-2 leading-relaxed"><?php echo esc_html( $fp['excerpt'] ); ?></p>
									<a href="<?php echo esc_url( $fp['permalink'] ); ?>" class="text-xs font-bold text-[#1e73be] hover:text-[#165da0] flex items-center gap-1 transition-colors pt-1">
										<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
										<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Post Grid (3 columns) -->
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php
					while ( have_posts() ) :
						the_post();
						$p_id    = get_the_ID();
						$p_cats  = get_the_category();
						$p_cat   = ! empty( $p_cats ) ? $p_cats[0] : null;
						$p_thumb = get_the_post_thumbnail_url( $p_id, 'medium_large' );
						?>
						<article class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] overflow-hidden group hover:shadow-[0_20px_40px_-4px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300">
							<a href="<?php the_permalink(); ?>" class="block">
								<div class="<?php echo esc_attr( $ratio_css ); ?> bg-slate-100 relative overflow-hidden">
									<?php if ( $p_thumb ) : ?>
										<img src="<?php echo esc_url( $p_thumb ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
									<?php else : ?>
										<div class="w-full h-full bg-slate-100 flex items-center justify-center">
											<svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
										</div>
									<?php endif; ?>
									<?php if ( is_sticky( $p_id ) ) : ?>
										<div class="absolute top-3 right-3"><span class="bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">Hot</span></div>
									<?php endif; ?>
								</div>
							</a>
							<div class="p-4 md:p-5 space-y-2.5">
								<div class="flex items-center gap-2">
									<?php if ( $p_cat ) : ?>
										<span class="bg-emerald-50 text-emerald-600 text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo esc_html( $p_cat->name ); ?></span>
									<?php endif; ?>
									<span class="text-[10px] text-slate-400 font-semibold"><?php echo esc_html( get_the_date() ); ?></span>
								</div>
								<h3 class="font-bold text-slate-800 text-sm leading-snug line-clamp-2 group-hover:text-[#1e73be] transition-colors">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>
								<p class="text-xs text-slate-400 line-clamp-2 leading-relaxed"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
								<a href="<?php the_permalink(); ?>" class="text-xs font-bold text-[#1e73be] hover:text-[#165da0] flex items-center gap-1 transition-colors pt-1">
									<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
									<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
								</a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<!-- Pagination -->
				<nav class="flex items-center justify-center gap-2 mt-10" aria-label="<?php esc_attr_e( 'Phân trang', 'spl' ); ?>">
					<?php
					$pagination = paginate_links( [
						'prev_text' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"/></svg>',
						'next_text' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-white" style="stroke: #ffffff !important;"><polyline points="9 6 15 12 9 18"/></svg>',
						'type'      => 'array',
					] );

					if ( $pagination ) :
						foreach ( $pagination as $link ) :
							// Current page.
							if ( str_contains( $link, 'current' ) ) :
								echo '<span class="w-9 h-9 rounded-lg bg-[#1e73be] text-white flex items-center justify-center text-xs font-bold shadow-md shadow-[#1e73be]/30">' . strip_tags( $link ) . '</span>';
							// Dots.
							elseif ( str_contains( $link, 'dots' ) ) :
								echo '<span class="text-slate-400 text-xs px-1">&hellip;</span>';
							// Prev link.
							elseif ( str_contains( $link, 'prev' ) ) :
								echo str_replace(
									'class="prev page-numbers"',
									'class="prev page-numbers w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-[#1e73be] hover:text-white hover:border-[#1e73be] flex items-center justify-center text-xs font-bold transition-colors"',
									$link
								);
							// Next link.
							elseif ( str_contains( $link, 'next' ) ) :
								echo str_replace(
									'class="next page-numbers"',
									'class="next page-numbers w-9 h-9 rounded-lg bg-[#1e73be] text-white hover:bg-[#165da0] flex items-center justify-center text-xs font-bold transition-colors shadow-md shadow-[#1e73be]/20" style="color: #ffffff !important; background-color: #1e73be !important;"',
									$link
								);
							// Numbered links.
							else :
								echo str_replace(
									'class="page-numbers"',
									'class="page-numbers w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-[#1e73be] hover:text-[#1e73be] flex items-center justify-center text-xs font-bold transition-colors"',
									$link
								);
							endif;
						endforeach;
					endif;
					?>
				</nav>

			<?php else : ?>
				<!-- No posts -->
				<div class="text-center py-16">
					<svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
					<h2 class="text-xl font-bold text-slate-700 mb-2"><?php esc_html_e( 'Chưa có bài viết nào', 'spl' ); ?></h2>
					<p class="text-sm text-slate-500 mb-6"><?php esc_html_e( 'Hiện tại chưa có nội dung phù hợp. Vui lòng quay lại sau.', 'spl' ); ?></p>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center gap-2 bg-[#1e73be] hover:bg-[#165da0] text-white px-6 py-3 rounded-xl text-sm font-bold transition-colors">
						<?php esc_html_e( 'Về trang chủ', 'spl' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

		<!-- RIGHT: Sidebar -->
		<aside class="space-y-5">
			<!-- Search -->
			<div class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5">
				<h3 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-3">
					<svg class="w-4 h-4 text-[#1e73be]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					<?php esc_html_e( 'Tìm kiếm', 'spl' ); ?>
				</h3>
				<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="relative">
					<input type="search" name="s" placeholder="<?php esc_attr_e( 'Tìm bài viết...', 'spl' ); ?>" value="<?php echo get_search_query(); ?>"
						   class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-[#1e73be] transition-all" />
					<button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1e73be]" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
						<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					</button>
				</form>
			</div>

			<!-- Categories -->
			<div class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5">
				<h3 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-4">
					<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
					<?php esc_html_e( 'Chuyên mục', 'spl' ); ?>
				</h3>
				<ul class="space-y-1">
					<?php
					$sidebar_cats = get_categories( [ 'hide_empty' => false, 'number' => 8 ] );
					foreach ( $sidebar_cats as $sc ) :
						?>
						<li>
							<a href="<?php echo esc_url( get_category_link( $sc ) ); ?>" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 transition-colors text-sm text-slate-600 hover:text-[#1e73be]">
								<span class="flex items-center gap-2">
									<svg class="w-2 h-2 text-slate-300" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
									<?php echo esc_html( $sc->name ); ?>
								</span>
								<span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full"><?php echo (int) $sc->count; ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Popular Posts -->
			<div class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5">
				<h3 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-4">
					<svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c-4.97 0-9-2.582-9-7v-.088C3 12.794 4.338 11.1 6.375 9.75a12.1 12.1 0 0 1-.375-3A9.004 9.004 0 0 0 9 4.5c.5.833 2.25 2.5 1.5 6-.5 2.5-1 4-1 5.5 0 2 1 3 1.5 3 .667 0 1.5-.5 1.5-3C12.5 13.5 15 10 15.5 8c.5 2 3.5 5 3.5 7v.088C19 17.418 16.97 22 12 22z"/></svg>
					<?php esc_html_e( 'Bài viết nổi bật', 'spl' ); ?>
				</h3>
				<div class="space-y-4">
					<?php
					$popular = get_posts( [
						'post_type'      => 'post',
						'posts_per_page' => 4,
						'orderby'        => 'comment_count',
						'order'          => 'DESC',
					] );
					foreach ( $popular as $pp ) :
						$pp_thumb = get_the_post_thumbnail_url( $pp->ID, 'thumbnail' );
						$pp_views = (int) get_post_meta( $pp->ID, 'post_views_count', true );
						?>
						<a href="<?php echo esc_url( get_permalink( $pp ) ); ?>" class="flex gap-3 group">
							<div class="w-16 h-16 rounded-lg bg-slate-100 shrink-0 overflow-hidden">
								<?php if ( $pp_thumb ) : ?>
									<img src="<?php echo esc_url( $pp_thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $pp ) ); ?>" class="w-full h-full object-cover" loading="lazy" />
								<?php else : ?>
									<div class="w-full h-full flex items-center justify-center text-slate-300">
										<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
									</div>
								<?php endif; ?>
							</div>
							<div>
								<p class="text-xs font-bold text-slate-700 line-clamp-2 group-hover:text-[#1e73be] transition-colors leading-snug"><?php echo esc_html( get_the_title( $pp ) ); ?></p>
								<span class="text-[10px] text-slate-400 mt-1 block">
									<?php echo esc_html( get_the_date( '', $pp ) ); ?>
									<?php if ( $pp_views > 0 ) : ?>
										· <?php echo esc_html( number_format_i18n( $pp_views ) ); ?> <?php esc_html_e( 'lượt xem', 'spl' ); ?>
									<?php endif; ?>
								</span>
							</div>
						</a>
					<?php endforeach; ?>
					<?php wp_reset_postdata(); ?>
				</div>
			</div>

			<!-- Newsletter CTA -->
			<div class="bg-gradient-to-br from-[#1e73be] to-[#0e4880] rounded-xl p-6 text-white relative overflow-hidden">
				<div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
				<div class="relative z-10">
					<div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center mb-4">
						<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
					</div>
					<h3 class="font-black text-base mb-1.5"><?php esc_html_e( 'Cần tư vấn?', 'spl' ); ?></h3>
					<p class="text-xs text-white/70 mb-4 leading-relaxed"><?php esc_html_e( 'Gọi ngay cho chúng tôi để được tư vấn miễn phí về các dòng xe điện.', 'spl' ); ?></p>
					<a href="<?php echo esc_url( $hotline_url ); ?>" class="block w-full text-center bg-white text-[#1e73be] hover:bg-[#f0f5ff] px-4 py-2.5 rounded-lg text-sm font-bold transition-colors">
						<?php echo esc_html( $hotline ); ?>
					</a>
				</div>
			</div>
		</aside>

	</div>
</main>

<?php
get_footer();
