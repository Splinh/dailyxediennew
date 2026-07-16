<?php
/**
 * The template for displaying all single posts.
 *
 * Matches htmlmau/bai-viet.html layout using Tailwind utility classes.
 * Includes dynamic metadata, Table of Contents, social sharing, and related posts.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$cats      = get_the_category();
	$cat_name  = ! empty( $cats ) ? $cats[0]->name : __( 'Tin tức', 'spl' );
	$hero_url  = get_the_post_thumbnail_url( $post_id, 'full' );

	// Author data.
	$author_name = get_the_author();
	$author_bio  = get_the_author_meta( 'description' );
	$author_role = $author_bio ? '' : __( 'Chuyên gia xe điện', 'spl' );
	$initials    = '';
	foreach ( array_slice( explode( ' ', trim( $author_name ) ), -2 ) as $w ) {
		$initials .= mb_substr( $w, 0, 1 );
	}
	$initials = mb_strtoupper( $initials ?: 'DXD' );

	// Reading time (approx. 200 words/min).
	$word_count = count( preg_split( '/\s+/', trim( wp_strip_all_tags( get_the_content() ) ) ) );
	$read_time  = max( 1, (int) ceil( $word_count / 200 ) );

	// Views count.
	$views = (int) get_post_meta( $post_id, 'post_views_count', true );

	// Options.
	$hotline     = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
	$hotline_url = 'tel:' . preg_replace( '/\s+/', '', $hotline );
	$fb_url      = Helper::getField( 'facebook_url', 'option' );
	$yt_url      = Helper::getField( 'youtube_url', 'option' );
	?>

	<!-- ===== BREADCRUMBS & ARTICLE HEADER ===== -->
	<section class="bg-slate-50 border-b border-slate-100">
		<div class="max-w-7xl mx-auto px-4 py-6">
			<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-[#1e73be] transition-colors">
					<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
				</a>
				<svg class="w-2.5 h-2.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ); ?>" class="hover:text-[#1e73be] transition-colors">
					<?php esc_html_e( 'Tin tức', 'spl' ); ?>
				</a>
				<svg class="w-2.5 h-2.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<span class="text-slate-700 font-medium truncate max-w-[200px] sm:max-w-md md:max-w-xl lg:max-w-none"><?php the_title(); ?></span>
			</nav>
		</div>
	</section>

	<!-- ===== MAIN CONTENT ===== -->
	<main id="article-main" class="max-w-7xl mx-auto px-4 py-8 md:py-12">
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

			<!-- LEFT: Article Content (9/12 = 75%) -->
			<div class="lg:col-span-9 space-y-8">
				<article class="bg-white border border-slate-100 rounded-2xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5 md:p-8">
					<!-- Article Meta & Title -->
					<div class="space-y-4">
						<div class="flex flex-wrap items-center gap-3">
							<?php
							if ( ! empty( $cats ) ) :
								foreach ( $cats as $cat ) :
									?>
									<span class="bg-[#f0f5ff] text-[#1e73be] text-[10px] font-bold px-3 py-1 rounded-full border border-[#e0ebff] uppercase tracking-wider"><?php echo esc_html( $cat->name ); ?></span>
									<?php
								endforeach;
							endif;
							?>
						</div>
						<h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-slate-900 leading-snug">
							<?php the_title(); ?>
						</h1>
						<div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 font-semibold border-b border-slate-100 pb-5">
							<span class="flex items-center gap-1.5">
								<svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								<?php esc_html_e( 'Tác giả:', 'spl' ); ?> <strong class="text-slate-600"><?php echo esc_html( $author_name ); ?></strong>
							</span>
							<span class="hidden sm:inline text-slate-200">|</span>
							<span class="flex items-center gap-1.5">
								<svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
								<?php echo esc_html( get_the_date() ); ?>
							</span>
							<span class="text-slate-200">|</span>
							<span class="flex items-center gap-1.5">
								<svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<?php echo esc_html( sprintf( __( '%d phút đọc', 'spl' ), $read_time ) ); ?>
							</span>
							<?php if ( $views > 0 ) : ?>
								<span class="text-slate-200">|</span>
								<span class="flex items-center gap-1.5">
									<svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
									<?php echo esc_html( sprintf( __( '%s lượt xem', 'spl' ), number_format_i18n( $views ) ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Featured Image -->
					<?php if ( false && $hero_url ) : // Temporarily hidden ?>
						<div class="my-6 rounded-xl overflow-hidden aspect-[4/3] bg-slate-100 shadow-sm relative group">
							<img src="<?php echo esc_url( $hero_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.02]" loading="eager" />
						</div>
					<?php endif; ?>

					<!-- Article Body Content -->
					<div class="article-content text-slate-700 text-base leading-relaxed space-y-6">
						<?php the_content(); ?>
					</div>

					<!-- Tags -->
					<?php
					$tags = get_the_tags();
					if ( $tags && ! is_wp_error( $tags ) ) :
						?>
						<div class="flex flex-wrap items-center gap-2 pt-6 mt-8 border-t border-slate-100">
							<span class="text-xs font-bold text-slate-400 uppercase mr-2 flex items-center gap-1">
								<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
								<?php esc_html_e( 'Tags:', 'spl' ); ?>
							</span>
							<?php foreach ( $tags as $tag ) : ?>
								<a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="px-3 py-1.5 bg-slate-50 hover:bg-[#f0f5ff] hover:text-[#1e73be] rounded-lg text-xs text-slate-500 border border-slate-200/60 transition-colors">
									<?php echo esc_html( $tag->name ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<!-- Social Sharing -->
					<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-6 p-4 bg-slate-50 rounded-xl">
						<div class="text-xs font-bold text-slate-700"><?php esc_html_e( 'Chia sẻ bài viết này:', 'spl' ); ?></div>
						<div class="flex flex-wrap items-center gap-2">
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
								<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-2.7c0-.9.6-1.1 1-1.1h3V.29L14.17.2C10.24.2 8.5 2.61 8.5 5.5v2.96H5v4.54h3.5v11H13v-11h3.77l.5-4.54z"/></svg>
								Facebook
							</a>
							<a href="https://zalo.me/share?to=0&amp;url=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="flex items-center gap-1.5 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
								<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 .3C5.4.3 0 4.7 0 10.2c0 3.2 1.9 6.1 4.9 7.9-.2.8-.7 2.4-.7 2.5 0 .2.1.3.3.2.3-.2 2.8-1.8 3.7-2.4 1.2.3 2.5.5 3.8.5 6.6 0 12-4.4 12-9.9S18.6.3 12 .3z"/></svg>
								Zalo
							</a>
							<button class="flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold shadow-sm transition-colors" id="dxd-copy-link" data-url="<?php echo esc_url( get_permalink() ); ?>">
								<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
								<?php esc_html_e( 'Sao chép link', 'spl' ); ?>
							</button>
						</div>
					</div>
				</article>

				<!-- Author Bio Box -->
				<div class="bg-white border border-slate-100 rounded-2xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-6 flex gap-4 items-start">
					<div class="w-14 h-14 rounded-full bg-[#f0f5ff] text-[#1e73be] flex items-center justify-center font-black text-xl shadow-inner shrink-0"><?php echo esc_html( $initials ); ?></div>
					<div class="space-y-2">
						<h4 class="font-bold text-slate-800 text-base"><?php esc_html_e( 'Viết bởi:', 'spl' ); ?> <?php echo esc_html( $author_name ); ?></h4>
						<p class="text-slate-500 text-xs leading-relaxed">
							<?php echo esc_html( $author_bio ?: __( 'Đội ngũ chuyên gia xe điện của dailyxedien.vn. Chúng tôi cập nhật liên tục các tin tức khuyến mãi mới nhất, hướng dẫn sử dụng và xu hướng xe điện công nghệ tương lai tại Việt Nam.', 'spl' ) ); ?>
						</p>
					</div>
				</div>

				<!-- Related Posts -->
				<?php
				$related = get_posts( [
					'post_type'      => 'post',
					'posts_per_page' => 3,
					'post__not_in'   => [ $post_id ],
					'category__in'   => wp_list_pluck( $cats, 'term_id' ),
					'orderby'        => 'date',
					'order'          => 'DESC',
				] );

				if ( ! empty( $related ) ) :
					?>
					<div class="space-y-5">
						<h3 class="font-black text-slate-900 text-lg md:text-xl flex items-center gap-2">
							<svg class="w-5 h-5 text-[#1e73be]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
							<?php esc_html_e( 'Bài viết liên quan', 'spl' ); ?>
						</h3>
						<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
							<?php
							foreach ( $related as $i => $rp ) :
								$rp_thumb = get_the_post_thumbnail_url( $rp->ID, 'medium' );
								$rp_cats  = get_the_category( $rp->ID );
								$rp_cat   = ! empty( $rp_cats ) ? $rp_cats[0]->name : __( 'Tin tức', 'spl' );
								?>
								<article class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] overflow-hidden group hover:shadow-[0_20px_40px_-4px_rgba(0,0,0,0.08)] transition-all duration-300">
									<a href="<?php echo esc_url( get_permalink( $rp ) ); ?>" class="block aspect-[4/3] overflow-hidden bg-slate-100">
										<?php if ( $rp_thumb ) : ?>
											<img src="<?php echo esc_url( $rp_thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $rp->ID ) ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
										<?php else : ?>
											<div class="w-full h-full flex items-center justify-center text-slate-300">
												<svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
											</div>
										<?php endif; ?>
									</a>
									<div class="p-4 space-y-2">
										<span class="bg-[#f0f5ff] text-[#1e73be] text-[9px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"><?php echo esc_html( $rp_cat ); ?></span>
										<h4 class="font-bold text-slate-800 text-xs md:text-sm leading-snug line-clamp-2 group-hover:text-[#1e73be] transition-colors">
											<a href="<?php echo esc_url( get_permalink( $rp ) ); ?>"><?php echo esc_html( get_the_title( $rp->ID ) ); ?></a>
										</h4>
										<span class="text-[9px] text-slate-400 block font-semibold"><?php echo esc_html( get_the_date( '', $rp ) ); ?></span>
									</div>
								</article>
							<?php endforeach; wp_reset_postdata(); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- RIGHT: Sidebar (3/12 = 25%) -->
			<aside class="lg:col-span-3 space-y-6">
				<div data-fx-sticky data-sticky-offset="100" class="lg:sticky lg:top-28 space-y-6">
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
								'post__not_in'   => [ $post_id ],
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
							<?php endforeach; wp_reset_postdata(); ?>
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
				</div>
			</aside>

		</div>
	</main>

	<!-- Copy to Clipboard Script -->
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var btn = document.getElementById('dxd-copy-link');
		if (btn) {
			btn.addEventListener('click', function() {
				var url = btn.getAttribute('data-url');
				navigator.clipboard.writeText(url).then(function() {
					// Visual feedback
					var originalText = btn.innerHTML;
					btn.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> ' + <?php echo json_encode( __( 'Đã chép!', 'spl' ) ); ?>;
					setTimeout(function() {
						btn.innerHTML = originalText;
					}, 2000);
				}).catch(function(err) {
					console.error('Could not copy text: ', err);
				});
			});
		}
	});
	</script>

	<?php
endwhile;

get_footer();
