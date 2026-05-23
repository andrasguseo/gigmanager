<?php

namespace AGU\GigManager\Import;

defined( 'ABSPATH' ) || exit;

/**
 * CSV importer for Shows.
 *
 * Upsert logic: post_id-based. If a show with the given post_id exists, it is updated.
 * If post_id is empty or not found, a new show is created.
 *
 * @since 1.0.0
 */
class Show_Importer extends Abstract_Importer {

	/**
	 * Allowed show status values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const ALLOWED_STATUSES = [ '', 'cancelled', 'sold_out' ];

	/**
	 * @inheritDoc
	 */
	protected function required_columns(): array {
		return [ 'date', 'artist_slug' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function process_row( array $row, int $row_number ): array {
		$date         = trim( $row['date'] ?? '' );
		$artist_slug  = trim( $row['artist_slug'] ?? '' );

		// Validate required fields.
		if ( '' === $date ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Missing required field: date.', 'gigmanager' ),
			];
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Invalid date format. Expected YYYY-MM-DD.', 'gigmanager' ),
			];
		}

		if ( '' === $artist_slug ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Missing required field: artist_slug.', 'gigmanager' ),
			];
		}

		// Look up artist by slug.
		$artist = get_page_by_path( $artist_slug, OBJECT, 'gigmanager_artist' );

		if ( ! $artist ) {
			return [
				'status'  => 'skipped',
				'message' => sprintf(
					/* translators: %s: artist slug */
					__( 'Artist slug "%s" not found — import artists first.', 'gigmanager' ),
					$artist_slug
				),
			];
		}

		// Look up venue by name + city.
		$venue_name = trim( $row['venue_name'] ?? '' );
		$venue_city = trim( $row['venue_city'] ?? '' );
		$venue_id   = 0;

		if ( $venue_name ) {
			$venue_id = $this->find_venue( $venue_name, $venue_city );

			if ( ! $venue_id ) {
				return [
					'status'  => 'skipped',
					'message' => sprintf(
						/* translators: %1$s: venue name, %2$s: venue city */
						__( 'Venue "%1$s" in "%2$s" not found — import venues first.', 'gigmanager' ),
						$venue_name,
						$venue_city
					),
				];
			}
		}

		// Look up tour by slug (optional).
		$tour_slug = trim( $row['tour_slug'] ?? '' );
		$tour_id   = 0;

		if ( $tour_slug ) {
			$tour = get_page_by_path( $tour_slug, OBJECT, 'gigmanager_tour' );

			if ( ! $tour ) {
				return [
					'status'  => 'skipped',
					'message' => sprintf(
						/* translators: %s: tour slug */
						__( 'Tour slug "%s" not found — import tours first.', 'gigmanager' ),
						$tour_slug
					),
				];
			}

			$tour_id = $tour->ID;
		}

		// Sanitize optional fields.
		$time               = trim( $row['time'] ?? '' );
		$performance_status = trim( $row['performance_status'] ?? '' );
		$price              = trim( $row['price'] ?? '' );
		$ticket_url         = trim( $row['ticket_url'] ?? '' );
		$ticket_phone       = trim( $row['ticket_phone'] ?? '' );
		$external_url       = trim( $row['external_url'] ?? '' );
		$notes              = trim( $row['notes'] ?? '' );

		if ( $time && ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			$time = '';
		}

		if ( ! in_array( $performance_status, self::ALLOWED_STATUSES, true ) ) {
			$performance_status = '';
		}

		// Upsert: check post_id.
		$post_id  = (int) ( $row['post_id'] ?? 0 );
		$existing = null;

		if ( $post_id ) {
			$existing = get_post( $post_id );

			if ( $existing && 'gigmanager_show' !== $existing->post_type ) {
				$existing = null;
			}
		}

		if ( $existing ) {
			$this->update_show_meta( $existing->ID, $date, $time, $artist->ID, $venue_id, $tour_id, $performance_status, $price, $ticket_url, $ticket_phone, $external_url, $notes );

			return [ 'status' => 'updated' ];
		}

		$new_post_id = wp_insert_post( [
			'post_type'   => 'gigmanager_show',
			'post_title'  => $date . ' — ' . $artist->post_title,
			'post_status' => 'publish',
		] );

		if ( ! $new_post_id || is_wp_error( $new_post_id ) ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Failed to create show.', 'gigmanager' ),
			];
		}

		$this->update_show_meta( $new_post_id, $date, $time, $artist->ID, $venue_id, $tour_id, $performance_status, $price, $ticket_url, $ticket_phone, $external_url, $notes );

		return [ 'status' => 'created' ];
	}

	/**
	 * Find a venue by name and city.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Venue name.
	 * @param string $city Venue city.
	 *
	 * @return int Venue post ID, or 0 if not found.
	 */
	protected function find_venue( string $name, string $city ): int {
		$venues = get_posts( [
			'post_type'      => 'gigmanager_venue',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $venues as $venue ) {
			if ( $venue->post_title !== $name ) {
				continue;
			}

			$venue_city = get_post_meta( $venue->ID, self::META_PREFIX . 'city', true );

			if ( '' === $city || $venue_city === $city ) {
				return $venue->ID;
			}
		}

		return 0;
	}

	/**
	 * Update all show meta fields.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id            Post ID.
	 * @param string $date               Date (YYYY-MM-DD).
	 * @param string $time               Time (HH:MM).
	 * @param int    $artist_id          Artist post ID.
	 * @param int    $venue_id           Venue post ID.
	 * @param int    $tour_id            Tour post ID.
	 * @param string $performance_status Show status.
	 * @param string $price              Price.
	 * @param string $ticket_url         Ticket URL.
	 * @param string $ticket_phone       Ticket phone.
	 * @param string $external_url       External URL.
	 * @param string $notes              Notes.
	 *
	 * @return void
	 */
	protected function update_show_meta( int $post_id, string $date, string $time, int $artist_id, int $venue_id, int $tour_id, string $performance_status, string $price, string $ticket_url, string $ticket_phone, string $external_url, string $notes ): void {
		update_post_meta( $post_id, self::META_PREFIX . 'date', $date );
		update_post_meta( $post_id, self::META_PREFIX . 'time', $time );
		update_post_meta( $post_id, self::META_PREFIX . 'artist_id', $artist_id );
		update_post_meta( $post_id, self::META_PREFIX . 'venue_id', $venue_id );
		update_post_meta( $post_id, self::META_PREFIX . 'tour_id', $tour_id );
		update_post_meta( $post_id, self::META_PREFIX . 'show_status', $performance_status );
		update_post_meta( $post_id, self::META_PREFIX . 'price', sanitize_text_field( $price ) );
		update_post_meta( $post_id, self::META_PREFIX . 'ticket_url', esc_url_raw( $ticket_url ) );
		update_post_meta( $post_id, self::META_PREFIX . 'ticket_phone', sanitize_text_field( $ticket_phone ) );
		update_post_meta( $post_id, self::META_PREFIX . 'external_url', esc_url_raw( $external_url ) );
		update_post_meta( $post_id, self::META_PREFIX . 'notes', sanitize_textarea_field( $notes ) );
		update_post_meta( $post_id, self::META_PREFIX . 'saved', '1' );
	}
}
