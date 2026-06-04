<?php

\defined( 'ABSPATH' ) || die;

$section_title = $args['title'] ?? '';
$title_tag     = $args['title_tag'] ?? 'p';
$currentPostId = $args['id'] ?? 0;
$taxSlug       = $args['taxonomy'] ?? 'category';
$max           = $args['max'] ?? 6;
$rows          = $args['rows'] ?? 1;
$related_query = \HD_Query::queryByRelated(
	$currentPostId,
	$taxSlug,
	[
		'limit'        => $max,
		'return_query' => false,
	]
);

if ( ! $related_query ) {
	return;
}
?>
<section class="section section-related section-related-post archive mb-12 lg:mb-24">
	<div class="container px-3 mx-auto closest-swiper">
		<?php echo $section_title ? '<' . $title_tag . ' class="h3 font-bold">' . $section_title . '</' . $title_tag . '>' : ''; ?>
		<div class="p-news-list mt-9">
			<div class="swiper-container">
				<?php
				$data = [
					'slidesPerView' => 'auto',
					'pagination'    => 'bullets',
					'spaceBetween'  => 12,
					'autoplay'      => true,
					'rows'          => $rows,
					'sm'            => [
						'spaceBetween' => 24,
					],
				];

				$swiper_data = wp_json_encode( $data, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE );
				if ( ! $swiper_data ) {
					$swiper_data = '';
				}
				?>
				<div class="swiper" data-fx-slider>
					<div class="swiper-wrapper" data-swiper-options="<?php echo esc_attr( $swiper_data ); ?>">
						<?php
						foreach ( $related_query as $related_id ) :
							echo '<div class="swiper-slide">';
							\HD_Helper::blockTemplate(
								'template-parts/post/loop',
								[
									'title_tag' => $title_tag,
									'id'        => $related_id,
								]
							);
							echo '</div>';
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
