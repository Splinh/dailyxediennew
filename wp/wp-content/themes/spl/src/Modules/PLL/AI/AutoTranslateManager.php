<?php
/**
 * PLL AI auto-translation manager.
 *
 * @package SPL\Modules\PLL\AI
 */

namespace SPL\Modules\PLL\AI;

use SPL\Modules\PLL\AI\Jobs\BatchManager;
use SPL\Modules\PLL\AI\Jobs\JobRepository;
use SPL\Modules\PLL\PLLModule;

defined( 'ABSPATH' ) || exit;

final class AutoTranslateManager {

	public static function shouldBoot(): bool {
		return is_admin()
			|| wp_doing_cron()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() );
	}

	public function register(): void {
		$repository = new JobRepository();
		add_action( 'init', [ $repository, 'registerPostType' ] );

		( new BatchManager( $repository ) )->register();

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
			( new RowActions() )->register();
			( new BulkActions( $repository ) )->register();
		}
	}

	public function enqueueAssets(): void {
		$settings = PLLModule::getCachedOptions();
		$assetUrl = THEME_URL . 'src/Modules/PLL/Admin/assets/ai/';

		wp_enqueue_style( 'hd-pll-ai-auto-translate', $assetUrl . 'auto-translate.css', [], THEME_VERSION );
		wp_register_script( 'hd-pll-ai-auto-translate', $assetUrl . 'auto-translate.js', [ 'wp-api-fetch' ], THEME_VERSION, true );
		wp_localize_script(
			'hd-pll-ai-auto-translate',
			'hdPllAi',
			[
				'root'         => esc_url_raw( rest_url( REST_NAMESPACE . '/pll/ai' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'adminUrl'     => esc_url_raw( admin_url() ),
				'editorAssist' => ! empty( $settings['ai_editor_assist_enabled'] ),
			]
		);

		wp_enqueue_script( 'hd-pll-ai-auto-translate' );
	}
}
