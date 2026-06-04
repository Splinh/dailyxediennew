<?php
/**
 * MU plugin: Temporarily fix image upload on Laragon.
 * Disables intermediate image generation that causes GD errors.
 * Remove this file after uploads work fine.
 */
add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
	// Keep only thumbnail + medium to reduce processing load.
	return array_intersect_key( $sizes, array_flip( [ 'thumbnail', 'medium' ] ) );
} );

// Skip big-image scaling (already in theme but belt-and-suspenders).
add_filter( 'big_image_size_threshold', '__return_false' );
