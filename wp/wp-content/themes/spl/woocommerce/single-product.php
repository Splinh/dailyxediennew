<?php
/**
 * WooCommerce Single Product Template.
 *
 * Overrides default single-product.php — matches website/single-product.html (sp-* markup).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Render a 5-star block (full / half) from an average rating.
 */
if ( ! function_exists( 'spl_render_stars' ) ) {
	function spl_render_stars( float $rating, string $extra_class = '' ): void {
		$star = '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>';
		echo '<div class="sp-stars ' . esc_attr( $extra_class ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$is_half = ( $rating >= $i - 0.75 && $rating < $i - 0.25 );
			$is_full = ( $rating >= $i - 0.25 );
			$cls     = $is_half ? ' class="sp-star--half"' : '';
			// An "empty" star still uses the same polygon; CSS colours it via fill defaults.
			echo '<svg viewBox="0 0 24 24"' . ( $is_full ? '' : $cls ) . '>' . $star . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
	}
}

/**
 * Render a themed product review form (matches sp-* design).
 *
 * Posts through the standard WP comment system with WooCommerce's `rating`
 * field so reviews are saved exactly like wc default review submissions.
 */
if ( ! function_exists( 'spl_render_review_form' ) ) {
	function spl_render_review_form( \WC_Product $product ): void {
		// Respect WooCommerce review settings.
		if ( ! comments_open( $product->get_id() ) || 'no' === get_option( 'woocommerce_enable_reviews' ) ) {
			return;
		}

		$commenter      = wp_get_current_commenter();
		$rating_required = wc_review_ratings_enabled() && wc_review_ratings_required();
		$must_log_in     = get_option( 'comment_registration' ) && ! is_user_logged_in();

		?>
		<div class="sp-review-form" id="review_form_wrapper">
			<h3 class="sp-review-form__title"><?php esc_html_e( 'Viết đánh giá của bạn', 'spl' ); ?></h3>

			<?php if ( $must_log_in ) : ?>
				<p class="sp-review-form__login">
					<?php
					printf(
						/* translators: %s login URL */
						wp_kses_post( __( 'Bạn phải <a href="%s">đăng nhập</a> để viết đánh giá.', 'spl' ) ),
						esc_url( wp_login_url( get_permalink( $product->get_id() ) ) )
					);
					?>
				</p>
			<?php else : ?>
				<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" class="sp-review-form__form" id="commentform">

					<?php if ( wc_review_ratings_enabled() ) : ?>
						<div class="sp-review-form__field sp-review-form__rating">
							<label><?php esc_html_e( 'Đánh giá của bạn', 'spl' ); ?><?php echo $rating_required ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="sp-rating-input" role="radiogroup" aria-label="<?php esc_attr_e( 'Chọn số sao', 'spl' ); ?>">
								<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
									<input type="radio" id="rating-<?php echo (int) $i; ?>" name="rating" value="<?php echo (int) $i; ?>" <?php echo $rating_required ? 'required' : ''; ?> />
									<label for="rating-<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( _n( '%d sao', '%d sao', $i, 'spl' ), $i ) ); ?>">
										<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
									</label>
								<?php endfor; ?>
							</div>
						</div>
					<?php endif; ?>

					<div class="sp-review-form__field">
						<label for="comment"><?php esc_html_e( 'Nội dung đánh giá', 'spl' ); ?> <span class="required">*</span></label>
						<textarea id="comment" name="comment" rows="5" required placeholder="<?php esc_attr_e( 'Chia sẻ cảm nhận của bạn về sản phẩm...', 'spl' ); ?>"></textarea>
					</div>

					<?php if ( ! is_user_logged_in() ) : ?>
						<div class="sp-review-form__row">
							<div class="sp-review-form__field">
								<label for="author"><?php esc_html_e( 'Họ tên', 'spl' ); ?> <span class="required">*</span></label>
								<input id="author" name="author" type="text" value="<?php echo esc_attr( $commenter['comment_author'] ); ?>" required />
							</div>
							<div class="sp-review-form__field">
								<label for="email"><?php esc_html_e( 'Email', 'spl' ); ?> <span class="required">*</span></label>
								<input id="email" name="email" type="email" value="<?php echo esc_attr( $commenter['comment_author_email'] ); ?>" required />
							</div>
						</div>
						<p class="sp-review-form__note"><?php esc_html_e( 'Email của bạn sẽ không được hiển thị công khai.', 'spl' ); ?></p>
					<?php endif; ?>

					<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $product->get_id() ); ?>" id="comment_post_ID" />
					<input type="hidden" name="comment_parent" id="comment_parent" value="0" />

					<button type="submit" class="btn btn--primary sp-review-form__submit" name="submit">
						<svg class="icon" viewBox="0 0 24 24"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2z"/></svg>
						<?php esc_html_e( 'Gửi đánh giá', 'spl' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}

get_header();

while ( have_posts() ) :
	the_post();
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		continue;
	}

	$image_id  = $product->get_image_id();
	$image_url = wp_get_attachment_image_url( $image_id, 'large' ) ?: wc_placeholder_img_src( 'large' );
	$gallery   = $product->get_gallery_image_ids();
	$is_sale   = $product->is_on_sale();
	$cats      = wp_get_post_terms( get_the_ID(), 'product_cat' );
	$cat_name  = ! empty( $cats ) ? $cats[0]->name : '';
	$cat_link  = ! empty( $cats ) ? get_term_link( $cats[0] ) : '';

	$avg_rating   = (float) $product->get_average_rating();
	$review_count = (int) $product->get_review_count();
	$total_sales  = (int) get_post_meta( get_the_ID(), 'total_sales', true );

	// Sale discount.
	$reg_price  = (float) $product->get_regular_price();
	$cur_price  = (float) $product->get_price();
	$sale_pct   = ( $is_sale && $reg_price > 0 ) ? round( ( ( $reg_price - $cur_price ) / $reg_price ) * 100 ) : 0;
	$saving_amt = ( $is_sale && $reg_price > $cur_price ) ? ( $reg_price - $cur_price ) : 0;

	$available_variations       = [];
	$variation_attributes       = [];
	$default_variation          = [];
	$default_variation_attrs    = [];
	$default_variation_id       = 0;
	$default_variation_price    = '';
	$default_variation_oldprice = '';

	if ( $product->is_type( 'variable' ) ) {
		/** @var WC_Product_Variable $product */
		$available_variations = $product->get_available_variations();
		$variation_attributes = $product->get_variation_attributes();

		foreach ( $available_variations as &$variation_data ) {
			if ( array_key_exists( 'display_price', $variation_data ) ) {
				$variation_data['spl_price_html'] = wc_price( (float) $variation_data['display_price'] );
			}

			$variation_data['spl_old_price_html'] = (
				isset( $variation_data['display_regular_price'], $variation_data['display_price'] )
				&& (float) $variation_data['display_regular_price'] > (float) $variation_data['display_price']
			)
				? wc_price( (float) $variation_data['display_regular_price'] )
				: '';
		}
		unset( $variation_data );

		$default_variation          = $available_variations[0] ?? [];
		$default_variation_attrs    = $default_variation['attributes'] ?? [];
		$default_variation_id       = absint( $default_variation['variation_id'] ?? 0 );
		$default_variation_price    = $default_variation['spl_price_html'] ?? '';
		$default_variation_oldprice = $default_variation['spl_old_price_html'] ?? '';
	}
	?>

	<!-- ===== BREADCRUMB ===== -->
	<div class="breadcrumb-bar">
		<div class="container">
			<nav class="breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
					<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
				</a>
				<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Sản phẩm', 'spl' ); ?></a>
				<?php if ( $cat_name && ! is_wp_error( $cat_link ) ) : ?>
					<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
					<a href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $cat_name ); ?></a>
				<?php endif; ?>
				<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<span class="breadcrumb__current"><?php echo esc_html( $product->get_name() ); ?></span>
			</nav>
		</div>
	</div>

	<!-- ===== PRODUCT DETAIL ===== -->
	<section class="sp-detail">
		<div class="container">
			<div class="sp-detail__grid">

				<!-- Gallery -->
				<div class="sp-gallery reveal">
					<div class="sp-gallery__main" id="sp-gallery-main">
						<?php if ( $sale_pct > 0 ) : ?>
							<span class="sp-gallery__badge">-<?php echo (int) $sale_pct; ?>%</span>
						<?php endif; ?>
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" id="sp-main-img" />
						<button class="sp-gallery__zoom" aria-label="<?php esc_attr_e( 'Phóng to ảnh', 'spl' ); ?>" id="sp-zoom-btn">
							<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
						</button>
						<button type="button" class="sp-gallery__nav sp-gallery__nav--prev" aria-label="<?php esc_attr_e( 'Ảnh trước', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
						</button>
						<button type="button" class="sp-gallery__nav sp-gallery__nav--next" aria-label="<?php esc_attr_e( 'Ảnh sau', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
						</button>
					</div>
					<div class="sp-gallery__thumbs" id="sp-gallery-thumbs">
						<button class="sp-gallery__thumb active" data-img="<?php echo esc_url( $image_url ); ?>">
							<img src="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'thumbnail' ) ?: $image_url ); ?>" alt="<?php esc_attr_e( 'Ảnh 1', 'spl' ); ?>" />
						</button>
						<?php foreach ( $gallery as $i => $gal_id ) : ?>
							<button class="sp-gallery__thumb" data-img="<?php echo esc_url( wp_get_attachment_image_url( $gal_id, 'large' ) ); ?>">
								<img src="<?php echo esc_url( wp_get_attachment_image_url( $gal_id, 'thumbnail' ) ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Ảnh %d', 'spl' ), $i + 2 ) ); ?>" />
							</button>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Product Info -->
				<div class="sp-info reveal">
					<?php if ( $cat_name ) : ?>
						<div class="sp-info__category">
							<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
							<?php echo esc_html( $cat_name ); ?>
						</div>
					<?php endif; ?>

					<h1 class="sp-info__title"><?php echo esc_html( $product->get_name() ); ?></h1>

					<?php if ( $review_count > 0 || $total_sales > 0 ) : ?>
						<div class="sp-info__rating">
							<?php spl_render_stars( $avg_rating ); ?>
							<?php if ( $review_count > 0 ) : ?>
								<span class="sp-info__rating-text"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?> (<?php echo (int) $review_count; ?> <?php esc_html_e( 'đánh giá', 'spl' ); ?>)</span>
							<?php endif; ?>
							<?php if ( $total_sales > 0 ) : ?>
								<span class="sp-info__sold">
									<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
									<?php echo esc_html( sprintf( __( 'Đã bán %s', 'spl' ), number_format_i18n( $total_sales ) ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

				<div class="sp-info__price-box" id="sp-price-box">
					<?php if ( $product->is_type( 'variable' ) && $default_variation_price ) : ?>
						<span class="sp-info__price"><?php echo wp_kses_post( $default_variation_price ); ?></span>
						<?php if ( $default_variation_oldprice ) : ?>
							<span class="sp-info__old-price"><?php echo wp_kses_post( $default_variation_oldprice ); ?></span>
						<?php endif; ?>
					<?php elseif ( $product->is_type( 'variable' ) ) : ?>
						<span class="sp-info__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					<?php elseif ( $is_sale && $reg_price > 0 ) : ?>
						<span class="sp-info__price"><?php echo wp_kses_post( wc_price( $cur_price ) ); ?></span>
						<span class="sp-info__old-price"><?php echo wp_kses_post( wc_price( $reg_price ) ); ?></span>
						<?php if ( $saving_amt > 0 ) : ?>
							<span class="sp-info__discount-tag"><?php echo esc_html__( 'Tiết kiệm', 'spl' ) . ' ' . wp_strip_all_tags( wc_price( $saving_amt ) ); ?></span>
						<?php endif; ?>
					<?php else : ?>
						<span class="sp-info__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="sp-info__short-desc">
						<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
					</div>
				<?php endif; ?>

				<?php
				// ── Variation Selector ──────────────────────────
				if ( $product->is_type( 'variable' ) ) :
					?>
					<div class="sp-variations" id="sp-variations"
						data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
						data-variations="<?php echo esc_attr( wp_json_encode( $available_variations ) ); ?>">
						<?php foreach ( $variation_attributes as $attribute_name => $options ) :
							$attr_label = wc_attribute_label( $attribute_name );
							$attr_key   = 'attribute_' . sanitize_title( $attribute_name );
							?>
							<div class="sp-variations__field">
								<label class="sp-variations__label" for="<?php echo esc_attr( $attr_key ); ?>">
									<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
									<?php echo esc_html( $attr_label ); ?>
								</label>
								<div class="sp-variations__options" data-attribute="<?php echo esc_attr( $attr_key ); ?>">
									<?php
									// Check if it's a taxonomy attribute.
									// Mặc định chọn biến thể đầu tiên của mỗi thuộc tính.
									if ( taxonomy_exists( $attribute_name ) ) {
										$terms          = wc_get_product_terms( $product->get_id(), $attribute_name, [ 'fields' => 'all' ] );
										$first_selected = false;
										$default_value  = (string) ( $default_variation_attrs[ $attr_key ] ?? '' );
										foreach ( $terms as $term ) {
											$in_options = in_array( $term->slug, $options, true );
											$is_active  = $in_options && ( $default_value ? $term->slug === $default_value : ! $first_selected );
											if ( $is_active ) {
												$first_selected = true;
											}
											echo '<button type="button" class="sp-variations__btn' . ( $is_active ? ' active' : '' ) . '" data-value="' . esc_attr( $term->slug ) . '">'
												. esc_html( $term->name )
												. '</button>';
										}
									} else {
										$first_selected = false;
										$default_value  = (string) ( $default_variation_attrs[ $attr_key ] ?? '' );
										foreach ( $options as $option ) {
											$is_active = ( $default_value ? (string) $option === $default_value : ! $first_selected );
											if ( $is_active ) {
												$first_selected = true;
											}
											echo '<button type="button" class="sp-variations__btn' . ( $is_active ? ' active' : '' ) . '" data-value="' . esc_attr( $option ) . '">'
												. esc_html( $option )
												. '</button>';
										}
									}
									?>
								</div>
							</div>
						<?php endforeach; ?>
						<input type="hidden" name="variation_id" id="sp-variation-id" value="<?php echo esc_attr( $default_variation_id ); ?>" />
						<div class="sp-variations__reset" id="sp-variations-reset" style="display:none;">
							<button type="button" class="sp-variations__clear">
								<svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
								<?php esc_html_e( 'Xóa lựa chọn', 'spl' ); ?>
							</button>
						</div>
					</div>
				<?php endif; ?>

				<!-- Quantity -->
				<div class="sp-info__quantity">
					<label><?php esc_html_e( 'Số lượng:', 'spl' ); ?></label>
					<div class="sp-qty">
						<button class="sp-qty__btn" id="qty-minus" aria-label="<?php esc_attr_e( 'Giảm', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
						</button>
						<input type="number" class="sp-qty__input" value="1" min="1" max="99" id="qty-input" />
						<button class="sp-qty__btn" id="qty-plus" aria-label="<?php esc_attr_e( 'Tăng', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						</button>
					</div>
				</div>

				<!-- Actions -->
				<div class="sp-info__actions">
					<button class="btn btn--primary btn--lg sp-add-cart" id="sp-add-cart"
						data-product-id="<?php echo esc_attr( get_the_ID() ); ?>"
						data-product-type="<?php echo esc_attr( $product->get_type() ); ?>">
						<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
						<?php esc_html_e( 'Thêm vào giỏ hàng', 'spl' ); ?>
					</button>
					<button class="btn btn--accent btn--lg sp-buy-now" id="sp-buy-now"
						data-product-id="<?php echo esc_attr( get_the_ID() ); ?>"
						data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
						data-checkout="<?php echo esc_url( wc_get_checkout_url() ); ?>">
						<svg class="icon" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
						<?php esc_html_e( 'Mua ngay', 'spl' ); ?>
					</button>
					<?php /* TODO: tạm ẩn nút Yêu thích (wishlist) — bỏ comment để bật lại. ?>
					<button class="btn-icon sp-wishlist" id="sp-wishlist" aria-label="<?php esc_attr_e( 'Yêu thích', 'spl' ); ?>">
						<svg class="icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
					</button>
					<?php */ ?>
				</div>

					<!-- Trust badges (ACF options, with fallback) -->
					<?php
					$trust_items = Helper::getField( 'product_trust', 'option' );
					if ( ! is_array( $trust_items ) || empty( $trust_items ) ) {
						$trust_items = [
							[ 'icon' => 'truck', 'text' => __( 'Miễn phí ship từ 500K', 'spl' ) ],
							[ 'icon' => 'clock', 'text' => __( 'Giao hàng 1-3 ngày', 'spl' ) ],
							[ 'icon' => 'return', 'text' => __( 'Đổi trả trong 7 ngày', 'spl' ) ],
						];
					}
					$trust_icons = [
						'truck'  => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
						'clock'  => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M12 7v5l3 3"/>',
						'return' => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
					];
					?>
					<div class="sp-trust">
						<?php foreach ( $trust_items as $ti ) :
							$icon_key = $ti['icon'] ?? 'truck';
							$icon_svg = $trust_icons[ $icon_key ] ?? $trust_icons['truck'];
							?>
							<div class="sp-trust__item">
								<svg class="icon" viewBox="0 0 24 24"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></svg>
								<span><?php echo esc_html( $ti['text'] ?? '' ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="sp-tabs reveal">
				<div class="sp-tabs__nav" role="tablist">
					<button class="sp-tabs__tab active" role="tab" aria-selected="true" data-tab="desc">
						<svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
						<?php esc_html_e( 'Mô tả sản phẩm', 'spl' ); ?>
					</button>
					<button class="sp-tabs__tab" role="tab" aria-selected="false" data-tab="reviews">
						<svg class="icon" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
						<?php printf( esc_html__( 'Đánh giá (%d)', 'spl' ), (int) $review_count ); ?>
					</button>
				</div>

				<!-- Panel: Description -->
				<div class="sp-tabs__panel active" id="tab-desc">
					<div class="sp-desc">
						<?php
						$content = get_the_content();
						if ( trim( $content ) ) {
							echo wp_kses_post( apply_filters( 'the_content', $content ) );
						} else {
							echo '<p>' . esc_html( $product->get_short_description() ?: __( 'Đang cập nhật mô tả sản phẩm.', 'spl' ) ) . '</p>';
						}

						// Spec table from product attributes.
						$attributes = $product->get_attributes();
						if ( ! empty( $attributes ) ) :
							?>
							<h3><?php esc_html_e( 'Thông số sản phẩm', 'spl' ); ?></h3>
							<table class="sp-spec-table">
								<?php foreach ( $attributes as $attribute ) :
									$label = wc_attribute_label( $attribute->get_name() );
									$value = $product->get_attribute( $attribute->get_name() );
									if ( ! $value ) { continue; }
									?>
									<tr>
										<td><?php echo esc_html( $label ); ?></td>
										<td><?php echo esc_html( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<!-- Panel: Reviews -->
				<div class="sp-tabs__panel" id="tab-reviews">
					<div class="sp-reviews">
						<?php
						$review_comments = get_comments( [
							'post_id' => get_the_ID(),
							'status'  => 'approve',
							'type'    => 'review',
						] );

						// Rating distribution.
						$dist = [ 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 ];
						foreach ( $review_comments as $rc ) {
							$r = (int) get_comment_meta( $rc->comment_ID, 'rating', true );
							if ( $r >= 1 && $r <= 5 ) {
								$dist[ $r ]++;
							}
						}
						?>
						<?php if ( $review_count > 0 ) : ?>
							<div class="sp-reviews__summary">
								<div class="sp-reviews__score">
									<span class="sp-reviews__number"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></span>
									<?php spl_render_stars( $avg_rating, 'sp-stars--lg' ); ?>
									<span><?php echo (int) $review_count; ?> <?php esc_html_e( 'đánh giá', 'spl' ); ?></span>
								</div>
								<div class="sp-reviews__bars">
									<?php for ( $s = 5; $s >= 1; $s-- ) :
										$pct = $review_count > 0 ? round( ( $dist[ $s ] / $review_count ) * 100 ) : 0;
										?>
										<div class="sp-bar">
											<span><?php echo (int) $s; ?> ★</span>
											<div class="sp-bar__track"><div class="sp-bar__fill" style="width:<?php echo (int) $pct; ?>%"></div></div>
											<span><?php echo (int) $dist[ $s ]; ?></span>
										</div>
									<?php endfor; ?>
								</div>
							</div>

							<div class="sp-reviews__list">
								<?php foreach ( $review_comments as $rc ) :
									$rating  = (int) get_comment_meta( $rc->comment_ID, 'rating', true );
									$initials = mb_strtoupper( mb_substr( $rc->comment_author, 0, 2 ) );
									?>
									<div class="sp-review">
										<div class="sp-review__avatar"><?php echo esc_html( $initials ); ?></div>
										<div class="sp-review__content">
											<div class="sp-review__header">
												<strong><?php echo esc_html( $rc->comment_author ); ?></strong>
												<?php spl_render_stars( (float) $rating, 'sp-stars--sm' ); ?>
												<span class="sp-review__date"><?php echo esc_html( get_comment_date( 'd/m/Y', $rc->comment_ID ) ); ?></span>
											</div>
											<p><?php echo esc_html( $rc->comment_content ); ?></p>
											<?php if ( wc_review_is_from_verified_owner( $rc->comment_ID ) ) : ?>
												<div class="sp-review__verified">
													<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
													<?php esc_html_e( 'Đã mua hàng', 'spl' ); ?>
												</div>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="sp-reviews__empty"><?php esc_html_e( 'Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!', 'spl' ); ?></p>
						<?php endif; ?>

						<?php
						// Always render the review form below the list.
						spl_render_review_form( $product );
						?>
					</div>
				</div>
			</div>

			<!-- Related Products -->
			<?php
			$related_ids = wc_get_related_products( get_the_ID(), 8 );
			if ( ! empty( $related_ids ) ) :
				?>
				<section class="section-compact section-related">
					<div class="container">
						<div class="section-title reveal">
							<div class="section-title__label">
								<svg class="icon" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
								<?php esc_html_e( 'Gợi Ý', 'spl' ); ?>
							</div>
							<h2 class="section-title__heading">
								<svg class="section-title__icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
								<?php esc_html_e( 'Sản Phẩm Liên Quan', 'spl' ); ?>
							</h2>
							<div class="section-title__line"></div>
						</div>
						<div class="sp-related-slider" id="related-products">
							<div class="sp-related-slider__track">
								<?php foreach ( $related_ids as $rid ) : ?>
									<div class="sp-related-slider__slide">
										<?php get_template_part( 'parts/product-card', null, [ 'id' => $rid ] ); ?>
									</div>
								<?php endforeach; ?>
							</div>
							<button type="button" class="sp-related-slider__nav sp-related-slider__nav--prev" aria-label="<?php esc_attr_e( 'Trước', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
							</button>
							<button type="button" class="sp-related-slider__nav sp-related-slider__nav--next" aria-label="<?php esc_attr_e( 'Sau', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
							</button>
						</div>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</section>

	<?php
endwhile;

get_footer();
