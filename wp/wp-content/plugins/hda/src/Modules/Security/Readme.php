<?php
/**
 * Readme.html deletion functionality.
 *
 * Removes the readme.html file from WordPress root for security.
 *
 * @author SiteGround Security
 * @link   https://github.com/SiteGround
 *
 * Modified by HD
 */

namespace HDAddons\Modules\Security;

\defined( 'ABSPATH' ) || exit;

final class Readme {

	/**
	 * Path to readme.html file.
	 */
	private const README_PATH = ABSPATH . 'readme.html';

	// --------------------------------------------------

	/**
	 * Initialize readme deletion on core updates.
	 */
	public function __construct() {
		add_action( '_core_updated_successfully', $this->deleteReadme( ... ) );

		// Also try to delete on init if enabled
		if ( $this->readmeExists() ) {
			$this->deleteReadme();
		}
	}

	// --------------------------------------------------

	/**
	 * Check if readme.html exists.
	 *
	 * @return bool True if file exists.
	 */
	public function readmeExists(): bool {
		return file_exists( self::README_PATH );
	}

	// --------------------------------------------------

	/**
	 * Delete readme.html from WordPress root.
	 *
	 * @return bool True if file was deleted or doesn't exist.
	 */
	public function deleteReadme(): bool {
		// Check if the readme.html file exists in the root of the application
		if ( ! $this->readmeExists() ) {
			return true;
		}

		// Check if file is writable (can be deleted)
		if ( ! is_writable( self::README_PATH ) ) {
			return false;
		}

		// Try to remove the file using WordPress API.
		wp_delete_file( self::README_PATH );

		return ! $this->readmeExists();
	}
}
