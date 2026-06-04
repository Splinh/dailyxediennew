<?php
/**
 * The loop.php file in WordPress handles displaying post's summaries in lists,
 * such as archives or blog pages v.v...
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

$item_id     = $args['id'] ?? 0;
$itemTitle   = $args['title'] ?? get_the_title( $item_id );
$itemTitle   = ! empty( $itemTitle ) ? $itemTitle : __( '(no title)', 'hd' );
$title_tag   = $args['title_tag'] ?? 'h3';
$ratio       = $args['ratio'] ?? \HD_Helper::aspectRatioClass( get_post_type( $item_id ) );
$first_class = ! empty( $args['first_class'] ) ? ' ' . $args['first_class'] : '';
$pos         = $args['pos'] ?? 0;
$thumbnail   = $args['thumbnail'] ?? 'medium_large';

$img_class = 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105';

$thumbnailHTML = \HD_Helper::postImageHTML(
	$item_id,
	$thumbnail,
	[
		'alt'     => \HD_Helper::escAttr( $itemTitle ),
		'class'   => $img_class,
		'loading' => 'lazy',
	]
);

if ( ! $thumbnailHTML ) {
	$thumbnailHTML = \HD_Helper::placeholderSrc( $img_class );
}

$permalink = get_permalink( $item_id );

?>
<article class="item group relative flex flex-col h-full rounded-2xl border border-black/5 bg-white shadow-sm overflow-hidden transition-all duration-300 hover:border-primary/20 hover:shadow-md<?php echo $pos === 0 ? $first_class : ''; ?>">

	<a href="<?php echo esc_url( $permalink ); ?>"
		class="block relative overflow-hidden no-underline <?php echo esc_attr( $ratio ); ?>"
		aria-label="<?php echo \HD_Helper::escAttr( $itemTitle ); ?>"
		aria-hidden="true" tabindex="-1">
		<?php echo $thumbnailHTML; ?>
		<div class="absolute inset-0 bg-linear-to-t from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" aria-hidden="true"></div>
	</a>

	<div class="flex flex-col grow p-5 gap-3">

		<div class="flex flex-wrap items-center gap-2 text-xs">
			<?php
			echo \HD_Query::getPrimaryTerm(
				[
					'post'          => $item_id,
					'taxonomy'      => 'category',
					'class'         => 'inline-flex px-2.5 py-1 rounded-md bg-primary/8 text-primary font-semibold tracking-wide',
					'wrapper_open'  => null,
					'wrapper_close' => null,
				]
			);
			?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c', $item_id ) ); ?>"
				class="text-gray-400 font-medium">
				<?php echo \HD_Helper::humanizeTime( $item_id ); ?>
			</time>
		</div>

		<?php
		echo '<a class="block no-underline" href="' . esc_url( $permalink ) . '" title="' . \HD_Helper::escAttr( $itemTitle ) . '">'
			. '<' . $title_tag . ' class="text-base font-bold tracking-tight m-0 line-clamp-2 leading-snug c-hover hover:text-primary">' . $itemTitle . '</' . $title_tag . '>'
			. '</a>';
		?>

		<?php echo \HD_Query::loopExcerpt( $item_id, 'text-sm text-gray-500 leading-relaxed m-0 line-clamp-2 grow' ); ?>

		<a href="<?php echo esc_url( $permalink ); ?>"
			class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary no-underline transition-all duration-200 group-hover:gap-2.5 mt-auto pt-2"
			title="<?php echo esc_attr__( 'Xem chi tiết', 'hd' ); ?>">
			<?php echo __( 'Chi tiết', 'hd' ); ?>
			<svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M5 12h14"/>
				<path d="m12 5 7 7-7 7"/>
			</svg>
		</a>

	</div>
</article>
