<?php

namespace AGU\GigManager\Feed;

use AGU\GigManager\Query\Show_Query;
use DateTime;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for GigManager feeds.
 *
 * Reads the shared feed request parameters (scope, sort, limit, artist),
 * fetches shows through the same Show_Query used by the shortcode, and
 * provides datetime helpers for the concrete feed formats.
 *
 * @since 1.1.0
 */
abstract class Abstract_Feed {

	/**
	 * Render the feed and send it to the browser.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	abstract public function render(): void;

	/**
	 * Fetch the shows for this feed request.
	 *
	 * @since 1.1.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_shows(): array {
		$scope = $this->get_scope();

		return ( new Show_Query() )->get_shows(
			$scope,
			$this->get_sort( $scope ),
			$this->get_limit(),
			$this->get_artist()
		);
	}

	/**
	 * Get the requested scope, defaulting to "upcoming".
	 *
	 * Feeds are public read-only endpoints; no nonce is required.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function get_scope(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$scope = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : 'upcoming';

		return array_key_exists( $scope, Show_Query::SCOPE_DEFAULTS ) ? $scope : 'upcoming';
	}

	/**
	 * Get the requested sort direction, defaulting to the scope default.
	 *
	 * @since 1.1.0
	 *
	 * @param string $scope The resolved scope.
	 *
	 * @return string
	 */
	protected function get_sort( string $scope ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sort = isset( $_GET['sort'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['sort'] ) ) ) : '';

		if ( in_array( $sort, [ 'ASC', 'DESC' ], true ) ) {
			return $sort;
		}

		return Show_Query::SCOPE_DEFAULTS[ $scope ];
	}

	/**
	 * Get the requested limit (0 = no limit).
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	protected function get_limit(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['limit'] ) ? absint( wp_unslash( $_GET['limit'] ) ) : 0;
	}

	/**
	 * Get the requested artist filter (ID or slug), or empty string.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function get_artist(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['artist'] ) ? sanitize_text_field( wp_unslash( $_GET['artist'] ) ) : '';
	}

	/**
	 * Build the feed's title from the site name.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function feed_title(): string {
		/* translators: %s: site name. */
		return sprintf( __( '%s — Shows', 'gigmanager' ), wp_strip_all_tags( get_bloginfo( 'name' ) ) );
	}

	/**
	 * Build a human-readable summary line for a show ("Artist at Venue, City").
	 *
	 * Falls back to the show post title when no artist/venue data is present.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function show_summary( array $show ): string {
		$artist = trim( (string) $show['artist_name'] );
		$place  = trim( (string) $show['venue_name'] );

		if ( '' !== $show['venue_city'] ) {
			$place = '' !== $place
				? $place . ', ' . $show['venue_city']
				: (string) $show['venue_city'];
		}

		if ( '' !== $artist && '' !== $place ) {
			/* translators: 1: artist name, 2: venue and/or city. */
			return sprintf( __( '%1$s at %2$s', 'gigmanager' ), $artist, $place );
		}

		$summary = '' !== $artist ? $artist : $place;

		if ( '' === $summary ) {
			$summary = (string) $show['title'];
		}

		return $summary;
	}

	/**
	 * Build the best available link for a show.
	 *
	 * Prefers the ticket URL, then the external URL, then the show permalink.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function show_link( array $show ): string {
		if ( '' !== (string) $show['ticket_url'] ) {
			return (string) $show['ticket_url'];
		}

		if ( '' !== (string) $show['external_url'] ) {
			return (string) $show['external_url'];
		}

		return (string) $show['permalink'];
	}

	/**
	 * Build a DateTime for a show in the site timezone.
	 *
	 * Returns null when the show has no stored date.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return DateTime|null
	 */
	protected function show_datetime( array $show ): ?DateTime {
		$date = (string) $show['date_raw'];

		if ( '' === $date ) {
			return null;
		}

		$time = trim( (string) $show['time_raw'] );

		if ( '' === $time ) {
			$time = '00:00:00';
		} elseif ( 1 === substr_count( $time, ':' ) ) {
			// Normalise HH:MM (from a time input) to HH:MM:SS.
			$time .= ':00';
		}

		$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $date . ' ' . $time, wp_timezone() );

		return false !== $dt ? $dt : null;
	}

	/**
	 * Whether a show is an all-day event (no start time recorded).
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return bool
	 */
	protected function is_all_day( array $show ): bool {
		return '' === trim( (string) $show['time_raw'] );
	}

	/**
	 * Get a DateTimeZone for UTC.
	 *
	 * @since 1.1.0
	 *
	 * @return DateTimeZone
	 */
	protected function utc(): DateTimeZone {
		return new DateTimeZone( 'UTC' );
	}
}
