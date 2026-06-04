<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Consumer\ConsumerTokenId;
use HDAT\Domain\Credential\CredentialId;
use HDAT\Domain\Routing\QuotaWindow;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Rolling RPM/RPD/TPM/TPD windows per subject.
 *
 * Window types:
 *   - 'minute' : window_start = current minute, window_end = +60s
 *   - 'day'    : window_start = current UTC day, window_end = +24h
 *
 * Each subject (consumer or credential) gets one row per (subject, window_type,
 * window_start) — enforced by UNIQUE KEY on (route_hash, window_type, window_start).
 * route_hash is reused here as a generic "subject" hash so we don't need a
 * second unique index.
 *
 * Increments are atomic via INSERT ... ON DUPLICATE KEY UPDATE — that's the
 * only safe way to handle concurrent dispatch.
 */
final class QuotaWindowRepository {

	public function getConsumerWindow( ConsumerTokenId $id ): QuotaWindow {
		return $this->snapshot( $this->consumerHash( $id ) );
	}

	public function getCredentialWindow( CredentialId $id ): QuotaWindow {
		return $this->snapshot( $this->credentialHash( $id ) );
	}

	public function recordConsumerUsage( ConsumerTokenId $id, int $tokens ): void {
		$this->increment( $this->consumerHash( $id ), 'consumer', $id->value, null, $tokens );
	}

	public function recordCredentialUsage( CredentialId $id, int $tokens ): void {
		$this->increment( $this->credentialHash( $id ), 'credential', null, $id->value, $tokens );
	}

	public function pruneExpired(): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query( DB::db()->prepare( 'DELETE FROM %i WHERE window_end < NOW()', $table ) );
	}

	private function snapshot( string $hash ): QuotaWindow {
		$table = $this->table();

		$minuteStart = $this->minuteStart();
		$dayStart    = $this->dayStart();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$minute = DB::db()->get_row(
			DB::db()->prepare(
				'SELECT request_count, used_tokens FROM %i WHERE route_hash = %s AND window_type = %s AND window_start = %s',
				$table,
				$hash,
				'minute',
				$minuteStart
			),
			ARRAY_A
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$day = DB::db()->get_row(
			DB::db()->prepare(
				'SELECT request_count, used_tokens FROM %i WHERE route_hash = %s AND window_type = %s AND window_start = %s',
				$table,
				$hash,
				'day',
				$dayStart
			),
			ARRAY_A
		);

		return new QuotaWindow(
			requestsThisMinute: (int) ( $minute['request_count'] ?? 0 ),
			requestsToday:      (int) ( $day['request_count'] ?? 0 ),
			tokensThisMinute:   (int) ( $minute['used_tokens'] ?? 0 ),
			tokensToday:        (int) ( $day['used_tokens'] ?? 0 ),
		);
	}

	private function increment( string $hash, string $scope, ?int $consumerId, ?int $credentialId, int $tokens ): void {
		$table = $this->table();

		$this->upsertWindow(
			$table,
			$hash,
			$consumerId,
			$credentialId,
			'minute',
			$this->minuteStart(),
			gmdate( 'Y-m-d H:i:s', time() + 60 ),
			$tokens
		);

		$this->upsertWindow(
			$table,
			$hash,
			$consumerId,
			$credentialId,
			'day',
			$this->dayStart(),
			gmdate( 'Y-m-d 00:00:00', time() + DAY_IN_SECONDS ),
			$tokens
		);
	}

	private function upsertWindow(
		string $table,
		string $hash,
		?int $consumerId,
		?int $credentialId,
		string $type,
		string $windowStart,
		string $windowEnd,
		int $tokens,
	): void {
		// %d on null is a hack — wpdb::prepare handles null poorly, so we coerce.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->query(
			DB::db()->prepare(
				'INSERT INTO %i
				(route_hash, consumer_token_id, credential_id, window_type, window_start, window_end, request_count, used_tokens)
				VALUES (%s, %d, %d, %s, %s, %s, 1, %d)
				ON DUPLICATE KEY UPDATE
					request_count = request_count + 1,
					used_tokens   = used_tokens + VALUES(used_tokens)',
				$table,
				$hash,
				$consumerId ?? 0,
				$credentialId ?? 0,
				$type,
				$windowStart,
				$windowEnd,
				$tokens
			)
		);
	}

	private function consumerHash( ConsumerTokenId $id ): string {
		return hash( 'sha256', 'consumer|' . $id->value );
	}

	private function credentialHash( CredentialId $id ): string {
		return hash( 'sha256', 'credential|' . $id->value );
	}

	private function minuteStart(): string {
		return gmdate( 'Y-m-d H:i:00' );
	}

	private function dayStart(): string {
		return gmdate( 'Y-m-d 00:00:00' );
	}

	private function table(): string {
		return Schema::table( Schema::QUOTA_WINDOWS );
	}
}
