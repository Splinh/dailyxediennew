<?php
/**
 * Logs module - Admin settings and menu registration.
 *
 * @package HDAddons\Modules\Logs
 */

namespace HDAddons\Modules\Logs;

use HDAddons\Plugin;
use HDAddons\Modules\Logs\LogsModule;
use HDAddons\Modules\Logs\ActivityLog\ActivityLogTable;
use HDAddons\Modules\Logs\Monitor404\Monitor404Table;
use HDAddons\Modules\Logs\TrafficMonitor\TrafficMonitorTable;

defined( 'ABSPATH' ) || exit;

final class LogsAdmin {

	/**
	 * Settings page slug.
	 */
	public const PAGE_SLUG = 'hda-logs';

	public function __construct() {
		add_action( 'admin_menu', $this->registerMenu( ... ) );
		add_action( 'admin_init', $this->handleClearAll( ... ) );
		add_action( 'admin_enqueue_scripts', $this->enqueueAssets( ... ) );
	}

	public function handleClearAll(): void {
		if ( ! isset( $_REQUEST['clear_all_logs'] ) ) {
			return;
		}

		$page = sanitize_key( $_REQUEST['page'] ?? '' );
		if ( ! in_array( $page, [ 'hda-activity-log', 'hda-traffic-monitor', 'hda-404-monitor' ], true ) ) {
			return;
		}

		$capability = Plugin::CAPABILITY;
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Simplified verification handling for our tables:
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_hda_clear_nonce'] ?? '' ) );

		if ( $page === 'hda-activity-log' && wp_verify_nonce( $nonce, 'hda_clear_all_activity_logs' ) ) {
			ActivityLog\ActivityLog::clearAll();
		} elseif ( $page === 'hda-traffic-monitor' && wp_verify_nonce( $nonce, 'hda_clear_all_traffic_logs' ) ) {
			TrafficMonitor\TrafficMonitor::clearAll();
		} elseif ( $page === 'hda-404-monitor' && wp_verify_nonce( $nonce, 'hda_clear_all_404_logs' ) ) {
			Monitor404\Monitor404::clearAll();
		} else {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&cleared=1' ) );
		exit;
	}

	/**
	 * Register the admin menu and submenus under HDA Settings.
	 */
	public function registerMenu(): void {
		$capability = Plugin::CAPABILITY;

		$activityOptions = ActivityLog\ActivityLog::getOptions();
		if ( ! empty( $activityOptions[ ActivityLog\ActivityLog::KEY_ENABLED ] ) ) {
			// Submenu: Activity Log
			add_submenu_page(
				'hda-settings',
				__( 'Activity Log', 'hda' ),
				__( 'Activity Log', 'hda' ),
				$capability,
				'hda-activity-log',
				$this->renderActivityLog( ... )
			);
		}

		$trafficOptions = LogsModule::getSubOptions( TrafficMonitor\TrafficMonitor::SUB_KEY );
		if ( ! empty( $trafficOptions[ TrafficMonitor\TrafficMonitor::KEY_ENABLED ] ) ) {
			// Submenu: Traffic Monitor
			add_submenu_page(
				'hda-settings',
				__( 'Traffic Monitor', 'hda' ),
				__( 'Traffic Monitor', 'hda' ),
				$capability,
				'hda-traffic-monitor',
				$this->renderTrafficMonitor( ... )
			);
		}

		$m404Options = Monitor404\Monitor404::getOptions();
		if ( ! empty( $m404Options[ Monitor404\Monitor404::KEY_ENABLED ] ) ) {
			// Submenu: 404 Monitor
			add_submenu_page(
				'hda-settings',
				__( '404 Monitor', 'hda' ),
				__( '404 Monitor', 'hda' ),
				$capability,
				'hda-404-monitor',
				$this->renderMonitor404( ... )
			);
		}
	}

	/**
	 * Enqueue specific assets if needed.
	 */
	public function enqueueAssets( string $hook ): void {
		// Enqueue anything needed for list tables formatting in admin
	}

	/**
	 * Render Activity Log List Table.
	 */
	public function renderActivityLog(): void {
		$table = new ActivityLogTable();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Activity Log', 'hda' ); ?></h1>
			<hr class="wp-header-end">
			<form method="get">
				<input type="hidden" name="page" value="hda-activity-log">
				<?php
				$table->search_box( __( 'Search Logs', 'hda' ), 'search_id' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Traffic Monitor List Table.
	 */
	public function renderTrafficMonitor(): void {
		$table = new TrafficMonitorTable();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Traffic Monitor', 'hda' ); ?></h1>
			<hr class="wp-header-end">
			<form method="get">
				<input type="hidden" name="page" value="hda-traffic-monitor">
				<?php
				$table->search_box( __( 'Search IP or URL', 'hda' ), 'search_id' );
				$table->views();
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render 404 Monitor List Table.
	 */
	public function renderMonitor404(): void {
		$table = new Monitor404Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( '404 Monitor', 'hda' ); ?></h1>
			<hr class="wp-header-end">
			<form method="get">
				<input type="hidden" name="page" value="hda-404-monitor">
				<?php
				$table->search_box( __( 'Search URL', 'hda' ), 'search_id' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
