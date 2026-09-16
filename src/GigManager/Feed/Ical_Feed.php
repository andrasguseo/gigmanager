<?php

namespace AGU\GigManager\Feed;

use DateInterval;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Renders an iCalendar (.ics) feed of shows.
 *
 * Timed events are emitted in UTC (with a trailing "Z") so no VTIMEZONE
 * component is required; timeless shows are emitted as all-day events.
 *
 * @since 1.1.0
 */
class Ical_Feed extends Abstract_Feed {

	/**
	 * Render the iCal feed.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function render(): void {
		$shows = $this->get_shows();
		$lines = [];

		$lines[] = 'BEGIN:VCALENDAR';
		$lines[] = 'VERSION:2.0';
		$lines[] = 'PRODID:-//GigManager//GigManager Shows//EN';
		$lines[] = 'CALSCALE:GREGORIAN';
		$lines[] = 'METHOD:PUBLISH';
		$lines[] = 'X-WR-CALNAME:' . $this->escape_text( $this->feed_title() );

		foreach ( $shows as $show ) {
			$this->append_event( $lines, $show );
		}

		$lines[] = 'END:VCALENDAR';

		$output = '';
		foreach ( $lines as $line ) {
			$output .= $this->fold( $line ) . "\r\n";
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/calendar; charset=' . get_option( 'blog_charset' ), true );
			header( 'Content-Disposition: inline; filename="gigmanager.ics"' );
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values escaped per iCal rules via escape_text().
	}

	/**
	 * Append a VEVENT block for a single show.
	 *
	 * @since 1.1.0
	 *
	 * @param array<int, string>   $lines The accumulating line buffer (by reference).
	 * @param array<string, mixed> $show  The prepared show data.
	 *
	 * @return void
	 */
	protected function append_event( array &$lines, array $show ): void {
		$start = $this->show_datetime( $show );

		// Skip shows with no valid date — they cannot form a calendar event.
		if ( null === $start ) {
			return;
		}

		$lines[] = 'BEGIN:VEVENT';
		$lines[] = 'UID:' . $this->uid( $show );
		$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );

		if ( $this->is_all_day( $show ) ) {
			$end = ( clone $start )->add( new DateInterval( 'P1D' ) );
			$lines[] = 'DTSTART;VALUE=DATE:' . $start->format( 'Ymd' );
			$lines[] = 'DTEND;VALUE=DATE:' . $end->format( 'Ymd' );
		} else {
			$start->setTimezone( $this->utc() );
			$lines[] = 'DTSTART:' . $start->format( 'Ymd\THis\Z' );
		}

		$lines[] = 'SUMMARY:' . $this->escape_text( $this->show_summary( $show ) );

		$location = $this->location( $show );
		if ( '' !== $location ) {
			$lines[] = 'LOCATION:' . $this->escape_text( $location );
		}

		$description = $this->description( $show );
		if ( '' !== $description ) {
			$lines[] = 'DESCRIPTION:' . $this->escape_text( $description );
		}

		$url = $this->show_link( $show );
		if ( '' !== $url ) {
			$lines[] = 'URL:' . $this->escape_text( $url );
		}

		if ( 'cancelled' === $show['show_status'] ) {
			$lines[] = 'STATUS:CANCELLED';
		}

		$lines[] = 'END:VEVENT';
	}

	/**
	 * Build a stable UID for a show event.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function uid( array $show ): string {
		return 'gigmanager-show-' . (int) $show['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Build a multi-line description for a show event.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function description( array $show ): string {
		$parts = [];

		if ( '' !== trim( (string) $show['artist_name'] ) ) {
			$parts[] = (string) $show['artist_name'];
		}

		if ( 'cancelled' === $show['show_status'] ) {
			$parts[] = __( 'Cancelled', 'gigmanager' );
		} elseif ( 'sold_out' === $show['show_status'] ) {
			$parts[] = __( 'Sold Out', 'gigmanager' );
		}

		if ( '' !== trim( (string) $show['notes'] ) ) {
			$parts[] = trim( (string) $show['notes'] );
		}

		return implode( "\n", $parts );
	}

	/**
	 * Build a single-line location string for a show.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function location( array $show ): string {
		$parts = array_filter( [
			(string) $show['venue_name'],
			(string) $show['venue_address'],
			(string) $show['venue_city'],
			(string) $show['venue_state'],
			(string) $show['venue_postal_code'],
			(string) $show['venue_country'],
		] );

		return implode( ', ', $parts );
	}

	/**
	 * Escape a text value per RFC 5545 (backslash, semicolon, comma, newline).
	 *
	 * @since 1.1.0
	 *
	 * @param string $value The raw text value.
	 *
	 * @return string
	 */
	protected function escape_text( string $value ): string {
		$value = str_replace( '\\', '\\\\', $value );
		$value = str_replace( [ ';', ',' ], [ '\;', '\,' ], $value );
		$value = str_replace( [ "\r\n", "\r", "\n" ], '\n', $value );

		return $value;
	}

	/**
	 * Fold a content line to 75 octets per RFC 5545.
	 *
	 * Continuation lines are prefixed with a single space. Folding is done on
	 * byte boundaries that respect UTF-8 multibyte sequences.
	 *
	 * @since 1.1.0
	 *
	 * @param string $line The unfolded content line.
	 *
	 * @return string
	 */
	protected function fold( string $line ): string {
		if ( strlen( $line ) <= 75 ) {
			return $line;
		}

		$folded    = '';
		$current   = '';
		$max       = 75;
		$length    = strlen( $line );
		$i         = 0;

		while ( $i < $length ) {
			// Determine the width of the current UTF-8 character in bytes.
			$char_len = 1;
			$byte     = ord( $line[ $i ] );
			if ( $byte >= 0xF0 ) {
				$char_len = 4;
			} elseif ( $byte >= 0xE0 ) {
				$char_len = 3;
			} elseif ( $byte >= 0xC0 ) {
				$char_len = 2;
			}

			$char = substr( $line, $i, $char_len );

			// +1 accounts for the leading space on continuation lines.
			if ( strlen( $current ) + $char_len > $max ) {
				$folded .= ( '' === $folded ) ? $current : "\r\n " . $current;
				$current = '';
				$max     = 74;
			}

			$current .= $char;
			$i       += $char_len;
		}

		$folded .= ( '' === $folded ) ? $current : "\r\n " . $current;

		return $folded;
	}
}
