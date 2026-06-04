<?php
/**
 * @package HDAT\Interface\Admin
 */

declare(strict_types=1);

namespace HDAT\Interface\Admin;

use HDAT\Infrastructure\Asset;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress admin menu page + Vite asset enqueue.
 *
 * Registers a submenu page under Settings and enqueues the SPA
 * bundle built by Vite. The JS/CSS filenames are resolved from
 * the Vite manifest so cache-busting hashes work automatically.
 */
final class AdminPage {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'addMenuPage' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}

	public function addMenuPage(): void {
		add_options_page(
			'SPL AI Toolkit',
			'SPL AI Toolkit',
			'manage_options',
			'hdat',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		echo '<div id="hdat-app">'
			. '<nav id="hdat-nav"></nav>'
			. '<main id="hdat-content"></main>'
			. '</div>';
	}

	public function enqueueAssets( string $hook ): void {
		if ( 'settings_page_hdat' !== $hook ) {
			return;
		}

		Asset::enqueueJS( 'admin', [], null, true, [ 'module' ] );

		$handle = Asset::handle( 'admin' );
		if ( $handle ) {
			Asset::localize(
				$handle,
				'hdatAdmin',
				[
					'restUrl' => rest_url( 'hdat/v1' ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
				]
			);
		}
	}
}
