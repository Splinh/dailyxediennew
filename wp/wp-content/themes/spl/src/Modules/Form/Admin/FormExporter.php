<?php
/**
 * Form Entries Exporter — openspout-based XLSX/CSV streaming.
 *
 * Exports entries matching current list view filters.
 * Registered as an admin-post action to avoid memory issues with large datasets.
 *
 * @package SPL\Modules\Form\Admin
 */

namespace SPL\Modules\Form\Admin;

use SPL\Modules\Form\Repository\FormEntryRepository;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

defined( 'ABSPATH' ) || exit;

final class FormExporter {
	private const EXPORT_BATCH_SIZE = 500;

	/**
	 * Register export handler.
	 */
	public static function register(): void {
		add_action( 'admin_post_hd_export_form_entries', [ self::class, 'handleExport' ] );
	}

	/**
	 * Handle export request.
	 */
	public static function handleExport(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'SPL' ), 403 );
		}

		check_admin_referer( 'hd_export_entries' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$format = sanitize_key( $_GET['export_format'] ?? 'xlsx' );

		$filters = [];
		if ( ! empty( $_GET['status'] ) ) {
			$filters['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}
		if ( ! empty( $_GET['form_type'] ) ) {
			$filters['form_type'] = sanitize_text_field( wp_unslash( $_GET['form_type'] ) );
		}
		if ( ! empty( $_GET['s'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		// phpcs:enable

		$repo = new FormEntryRepository();

		$filename = 'form-entries-' . gmdate( 'Y-m-d-His' );

		if ( 'csv' === $format ) {
			self::exportCsv( $repo, $filters, $filename . '.csv' );
		} else {
			self::exportXlsx( $repo, $filters, $filename . '.xlsx' );
		}

		exit;
	}

	/**
	 * Export as XLSX.
	 *
	 * openToBrowser() sets Content-Type and Content-Disposition headers automatically.
	 *
	 * @param FormEntryRepository $repo     Entry repository.
	 * @param array               $filters  Export filters.
	 * @param string              $filename Full filename with extension.
	 */
	private static function exportXlsx( FormEntryRepository $repo, array $filters, string $filename ): void {
		$writer = new XlsxWriter();

		$writer->openToBrowser( $filename );
		self::writeRowsFromRepository( $writer, $repo, $filters );
		$writer->close();
	}

	/**
	 * Export as CSV.
	 *
	 * @param FormEntryRepository $repo     Entry repository.
	 * @param array               $filters  Export filters.
	 * @param string              $filename Full filename with extension.
	 */
	private static function exportCsv( FormEntryRepository $repo, array $filters, string $filename ): void {
		$writer = new CsvWriter();

		$writer->openToBrowser( $filename );
		self::writeRowsFromRepository( $writer, $repo, $filters );
		$writer->close();
	}

	/**
	 * Write header + data rows to the writer.
	 *
	 * @param XlsxWriter|CsvWriter $writer  The writer instance.
	 * @param array                $entries Entry rows.
	 */
	private static function writeRows( XlsxWriter|CsvWriter $writer, array $entries ): void {
		self::writeHeaderRow( $writer );
		self::writeDataRows( $writer, $entries );
	}

	private static function writeRowsFromRepository( XlsxWriter|CsvWriter $writer, FormEntryRepository $repo, array $filters ): void {
		self::writeHeaderRow( $writer );

		$page = 1;
		do {
			$entries = $repo->findAll( $filters, $page, self::EXPORT_BATCH_SIZE, 'id', 'DESC' );
			if ( is_wp_error( $entries ) || ! is_array( $entries ) ) {
				break;
			}

			$count = count( $entries );
			self::writeDataRows( $writer, $entries );
			++$page;
		} while ( $count === self::EXPORT_BATCH_SIZE );
	}

	private static function writeHeaderRow( XlsxWriter|CsvWriter $writer ): void {
		$headers = [ 'ID', 'Form Type', 'Form ID', 'Status', 'Name', 'Email', 'Phone', 'IP Address', 'Page URL', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'Notes', 'Date', 'Extra Fields' ];
		$writer->addRow( new Row( array_map( static fn( $h ) => Cell::fromValue( $h ), $headers ) ) );
	}

	private static function writeDataRows( XlsxWriter|CsvWriter $writer, array $entries ): void {
		$isCsv = $writer instanceof CsvWriter;

		foreach ( $entries as $entry ) {
			$dataFields = self::exportDataFields( $entry['data'] ?? [] );

			$cells = [
				Cell::fromValue( (int) $entry['id'] ),
				Cell::fromValue( self::exportCellValue( $entry['form_type'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['form_id'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['status'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['name'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['email'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['phone'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['ip_address'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['page_url'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['utm_source'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['utm_medium'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['utm_campaign'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['notes'] ?? '', $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $entry['created_at'], $isCsv ) ),
				Cell::fromValue( self::exportCellValue( $dataFields, $isCsv ) ),
			];

			$writer->addRow( new Row( $cells ) );
		}
	}

	/**
	 * Prefix formula-like CSV cells so spreadsheet apps treat them as text.
	 */
	private static function exportCellValue( mixed $value, bool $forCsv ): mixed {
		if ( ! $forCsv || ! is_string( $value ) ) {
			return $value;
		}

		return preg_match( '/^\s*[=+\-@]/', $value ) ? "'" . $value : $value;
	}

	private static function exportDataFields( mixed $data ): string {
		if ( is_string( $data ) ) {
			$decoded = json_decode( $data, true );
			$data    = is_array( $decoded ) ? $decoded : $data;
		}

		if ( is_array( $data ) ) {
			unset( $data['__labels'], $data['__files'], $data['__geo'] );

			return wp_json_encode( $data ) ?: '';
		}

		return is_scalar( $data ) || null === $data ? (string) $data : '';
	}
}
