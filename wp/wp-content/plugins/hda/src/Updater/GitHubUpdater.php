<?php
/**
 * GitHub Updater.
 *
 * Uses YahnisElsts/plugin-update-checker library for reliable GitHub updates.
 *
 * @package HDAddons\Updater
 */

namespace HDAddons\Updater;

use YahnisElsts\PluginUpdateChecker\v5p6\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p6\Vcs\GitHubApi;
use HDAddons\Helper;
use HDAddons\Modules\GlobalSetting\GlobalSetting;

\defined( 'ABSPATH' ) || exit;

final class GitHubUpdater {

	/**
	 * GitHub repository URL.
	 */
	private const REPO_URL = 'https://github.com/HD-Agency/hda';

	// --------------------------------------------------

	/**
	 * Initialize the updater.
	 */
	public function __construct() {
		$this->initUpdateChecker();
	}

	// --------------------------------------------------

	/**
	 * Initialize the plugin update checker.
	 *
	 * @return void
	 */
	private function initUpdateChecker(): void {
		try {
			// Create update checker instance.
			$updateChecker = PucFactory::buildUpdateChecker(
				self::REPO_URL,
				HDA_PATH . 'hda.php',
				'hda'
			);

			// Set branch to check (default: main).
			$updateChecker->setBranch( 'main' );

			// Use GitHub releases instead of tags.
			$vcsApi = $updateChecker->getVcsApi();
			if ( $vcsApi instanceof GitHubApi ) {
				$vcsApi->enableReleaseAssets();
			}

			// Set authentication for private repository.
			$token = $this->getToken();
			if ( $token ) {
				$updateChecker->setAuthentication( $token );
			}
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[GitHubUpdater] Init failed: ' . $e->getMessage() );
		}
	}

	// --------------------------------------------------

	/**
	 * Get GitHub access token.
	 *
	 * Priority: DB option → wp-config.php constant.
	 * NEVER hardcode tokens in source code.
	 *
	 * @return string|null Token or null if not set.
	 */
	private function getToken(): ?string {
		// 1. Check DB option (encrypted) — set via HDA Global Setting admin page.
		$stored = Helper::getOption( GlobalSetting::KEY_GITHUB_TOKEN, '' );
		if ( ! empty( $stored ) ) {
			$decrypted = Helper::decryptValue( $stored );
			if ( '' !== $decrypted ) {
				return $decrypted;
			}
		}

		// 2. wp-config.php constant fallback.
		if ( defined( 'HDA_GITHUB_TOKEN' ) && \HDA_GITHUB_TOKEN ) {
			return \HDA_GITHUB_TOKEN;
		}

		return null;
	}

	// --------------------------------------------------

	/**
	 * Check if updater is properly configured.
	 *
	 * @return bool True if configured.
	 */
	public function isConfigured(): bool {
		return $this->getToken() !== null;
	}
}
