<?php
/**
 * TinyMCE Plugin enhancements.
 *
 * Adds extra buttons and plugins to the classic TinyMCE editor.
 *
 * @author  HD
 * @package HDAddons\Modules\Editor
 */

namespace HDAddons\Modules\Editor;

\defined( 'ABSPATH' ) || exit;

final class TinyMCE {

	/**
	 * Base URL for TinyMCE plugins.
	 */
	private string $pluginsBaseUrl;

	// --------------------------------------------------

	/**
	 * Initialize TinyMCE customizations.
	 */
	public function __construct() {
		$this->pluginsBaseUrl = HDA_URL . 'src/Modules/Editor/tinymce/';

		add_filter( 'mce_buttons', $this->addMceButtons( ... ) );
		add_filter( 'mce_external_plugins', $this->addMcePlugins( ... ) );
	}

	// --------------------------------------------------

	/**
	 * Add extra buttons to TinyMCE toolbar.
	 *
	 * @param array $buttons Current toolbar buttons.
	 *
	 * @return array Modified buttons array.
	 */
	public function addMceButtons( array $buttons ): array {
		$extraButtons = [
			'table',
			'charmap',
			'backcolor',
			'superscript',
			'subscript',
			'codesample',
			'toc',
		];

		$insertions = [
			'italic'     => 'underline',
			'alignright' => 'alignjustify',
			'link'       => 'unlink',
		];

		// Add extra buttons with separators
		foreach ( $extraButtons as $btn ) {
			$buttons[] = 'separator';
			$buttons[] = $btn;
		}

		// Insert buttons after specific existing buttons
		foreach ( $insertions as $after => $button ) {
			$pos = array_search( $after, $buttons, true );
			if ( false !== $pos ) {
				array_splice( $buttons, $pos + 1, 0, [ 'separator', $button ] );
			} else {
				$buttons[] = 'separator';
				$buttons[] = $button;
			}
		}

		return $buttons;
	}

	// --------------------------------------------------

	/**
	 * Register external TinyMCE plugins.
	 *
	 * @param array $plugins Current plugins.
	 *
	 * @return array Modified plugins array.
	 */
	public function addMcePlugins( array $plugins ): array {
		$pluginFiles = [
			'table'      => 'table/plugin.min.js',
			'codesample' => 'codesample/plugin.min.js',
			'toc'        => 'toc/plugin.min.js',
			'wordcount'  => 'wordcount/plugin.min.js',
			'charcount'  => 'charcount/plugin.min.js',
		];

		foreach ( $pluginFiles as $key => $file ) {
			$plugins[ $key ] = $this->pluginsBaseUrl . $file;
		}

		return $plugins;
	}
}
