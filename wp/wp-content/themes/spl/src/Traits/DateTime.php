<?php
/**
 * Date and time utility trait.
 *
 * Provides date/time formatting, conversion, and manipulation utilities.
 *
 * @package SPL\Traits
 */

namespace SPL\Traits;

defined( 'ABSPATH' ) || exit;

trait DateTime {

	/**
	 * Humanizes the time difference between two timestamps.
	 *
	 * @param mixed $post Optional. The post-ID to get the time from.
	 * @param false|int|string $from Optional. The starting timestamp.
	 * @param false|int|string $to Optional. The ending timestamp.
	 *
	 * @return string The human-readable time difference.
	 */
	public static function humanizeTime( mixed $post = null, false|int|string $from = false, false|int|string $to = false ): string {
		if ( $to === false ) {
			$to = time();
		}

		if ( $from === false && $post ) {
			$from = get_the_time( 'U', $post );
		}

		if ( $from === false ) {
			return '';
		}

		return sprintf( __( '%s trước', 'SPL' ), human_time_diff( $from, $to ) );
	}

	// --------------------------------------------------

	/**
	 * Calculates the ISO 8601 duration between two date-time strings.
	 *
	 * @param string $dateTime1
	 * @param string $dateTime2
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function isoDuration( string $dateTime1, string $dateTime2 ): string {
		$dt1      = new \DateTimeImmutable( $dateTime1 );
		$dt2      = new \DateTimeImmutable( $dateTime2 );
		$interval = $dt1->diff( $dt2 );

		// Build date parts (Year, Month, Day)
		$dateParts = '';
		foreach (
			[
				'y' => 'Y',
				'm' => 'M',
				'd' => 'D',
			] as $prop => $suffix
		) {
			if ( $interval->$prop > 0 ) {
				$dateParts .= $interval->$prop . $suffix;
			}
		}

		// Build time parts (Hour, Minute, Second)
		$timeParts = '';
		foreach (
			[
				'h' => 'H',
				'i' => 'M',
				's' => 'S',
			] as $prop => $suffix
		) {
			if ( $interval->$prop > 0 ) {
				$timeParts .= $interval->$prop . $suffix;
			}
		}

		// Handle zero duration
		if ( $dateParts === '' && $timeParts === '' ) {
			return 'PT0S';
		}

		return 'P' . $dateParts . ( $timeParts !== '' ? 'T' . $timeParts : '' );
	}

	// -------------------------------------------------------------

	/**
	 * @param int|string $dateString
	 * @param string $format
	 *
	 * @return false|int|string
	 * @throws \Exception
	 */
	public static function convertToUTC( int|string $dateString, string $format = 'Y-m-d H:i:s' ): false|int|string {
		$siteTz = wp_timezone();

		if ( self::isNumericString( $dateString ) ) {
			$dt = ( new \DateTimeImmutable( '@' . (int) $dateString ) )->setTimezone( new \DateTimeZone( 'UTC' ) );
		} else {
			$dt = date_create_immutable( (string) $dateString, $siteTz );
			if ( $dt === false ) {
				return false;
			}
			$dt = $dt->setTimezone( new \DateTimeZone( 'UTC' ) );
		}

		return self::formatResult( $dt, $format );
	}

	// -------------------------------------------------------------

	/**
	 * @param int|string $dateString
	 * @param string $format
	 *
	 * @return false|int|string
	 * @throws \Exception
	 */
	public static function convertFromUTC( int|string $dateString, string $format = 'Y-m-d H:i:s' ): false|int|string {
		$siteTz = wp_timezone();

		if ( self::isNumericString( $dateString ) ) {
			$dt = ( new \DateTimeImmutable( '@' . (int) $dateString ) )->setTimezone( $siteTz );
		} else {
			$utc = new \DateTimeZone( 'UTC' );
			$dt  = date_create_immutable( (string) $dateString, $utc );
			if ( $dt === false ) {
				return false;
			}
			$dt = $dt->setTimezone( $siteTz );
		}

		return self::formatResult( $dt, $format );
	}

	// -------------------------------------------------------------

	/**
	 * @param int|string $dateString
	 * @param string $format
	 *
	 * @return false|int|string
	 * @throws \Exception
	 */
	public static function convertDatetimeFormat( int|string $dateString, string $format = 'Y-m-d H:i:s' ): false|int|string {
		$siteTz = wp_timezone();

		if ( self::isNumericString( $dateString ) ) {
			$dt = ( new \DateTimeImmutable( '@' . (int) $dateString ) )->setTimezone( $siteTz );
		} else {
			$dt = date_create_immutable( (string) $dateString, $siteTz );
			if ( $dt === false ) {
				return false;
			}
		}

		return self::formatResult( $dt, $format );
	}

	// -------------------------------------------------------------

	/**
	 * @param string $dateString
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function timeDifference( string $dateString ): array {
		$siteTz = wp_timezone();
		$target = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i:s', $dateString, $siteTz );

		if ( $target === false ) {
			try {
				$target = new \DateTimeImmutable( $dateString, $siteTz );
			} catch ( \Exception ) {
				return [
					'days'    => '00',
					'hours'   => '00',
					'minutes' => '00',
					'seconds' => '00',
				];
			}
		}

		$now      = new \DateTimeImmutable( 'now', $siteTz );
		$interval = $now->diff( $target );

		return [
			'days'    => str_pad( $interval->format( '%a' ), 2, '0', STR_PAD_LEFT ),
			'hours'   => str_pad( $interval->format( '%h' ), 2, '0', STR_PAD_LEFT ),
			'minutes' => str_pad( $interval->format( '%i' ), 2, '0', STR_PAD_LEFT ),
			'seconds' => str_pad( $interval->format( '%s' ), 2, '0', STR_PAD_LEFT ),
		];
	}

	// --------------------------------------------------

	/**
	 * Format a DateTimeImmutable result based on the requested format.
	 *
	 * @param \DateTimeImmutable $dt
	 * @param string $format
	 *
	 * @return int|string
	 */
	private static function formatResult( \DateTimeImmutable $dt, string $format ): int|string {
		return match ( $format ) {
			'timestamp', 'U' => $dt->getTimestamp(),
			'mysql'          => $dt->format( 'Y-m-d H:i:s' ),
			default          => $dt->format( $format ),
		};
	}

	// --------------------------------------------------

	/**
	 * @param mixed $val
	 *
	 * @return bool
	 */
	private static function isNumericString( mixed $val ): bool {
		if ( is_int( $val ) ) {
			return true;
		}

		if ( is_string( $val ) ) {
			$trim = trim( $val );

			return $trim !== '' && preg_match( '/^-?\d+$/', $trim ) === 1;
		}

		return false;
	}
}
