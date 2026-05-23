<?php
// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- All fputcsv() calls write to php://output, not the filesystem.

namespace AGU\GigManager\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Handles CSV export for all entity types.
 *
 * @since 1.0.0
 */
class Exporter {

	/**
	 * Meta key prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const META_PREFIX = '_gigmanager_';

	/**
	 * Export CSV for the given entity type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Entity type: shows, artists, venues, tours.
	 *
	 * @return void
	 */
	public function export( string $type ): void {
		$method = 'export_' . $type;

		if ( ! method_exists( $this, $method ) ) {
			return;
		}

		$filename = 'gigmanager-' . $type . '-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write BOM for Excel UTF-8 compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		$this->$method( $output );

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Export shows.
	 *
	 * @since 1.0.0
	 *
	 * @param resource $output File handle.
	 *
	 * @return void
	 */
	protected function export_shows( $output ): void {
		fputcsv( $output, [
			'post_id',
			'date',
			'time',
			'artist_slug',
			'venue_name',
			'venue_city',
			'tour_slug',
			'performance_status',
			'price',
			'ticket_url',
			'ticket_phone',
			'external_url',
			'notes',
		] );

		$posts = get_posts( [
			'post_type'      => 'gigmanager_show',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => self::META_PREFIX . 'date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'ASC',
		] );

		foreach ( $posts as $post ) {
			$artist_id = get_post_meta( $post->ID, self::META_PREFIX . 'artist_id', true );
			$venue_id  = get_post_meta( $post->ID, self::META_PREFIX . 'venue_id', true );
			$tour_id   = get_post_meta( $post->ID, self::META_PREFIX . 'tour_id', true );

			$artist_slug = '';
			if ( $artist_id ) {
				$artist = get_post( $artist_id );
				if ( $artist ) {
					$artist_slug = $artist->post_name;
				}
			}

			$venue_name = '';
			$venue_city = '';
			if ( $venue_id ) {
				$venue = get_post( $venue_id );
				if ( $venue ) {
					$venue_name = $venue->post_title;
					$venue_city = get_post_meta( $venue_id, self::META_PREFIX . 'city', true );
				}
			}

			$tour_slug = '';
			if ( $tour_id ) {
				$tour = get_post( $tour_id );
				if ( $tour ) {
					$tour_slug = $tour->post_name;
				}
			}

			fputcsv( $output, [
				$post->ID,
				get_post_meta( $post->ID, self::META_PREFIX . 'date', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'time', true ),
				$artist_slug,
				$venue_name,
				$venue_city,
				$tour_slug,
				get_post_meta( $post->ID, self::META_PREFIX . 'show_status', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'price', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'ticket_url', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'ticket_phone', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'external_url', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'notes', true ),
			] );
		}
	}

	/**
	 * Export artists.
	 *
	 * @since 1.0.0
	 *
	 * @param resource $output File handle.
	 *
	 * @return void
	 */
	protected function export_artists( $output ): void {
		fputcsv( $output, [ 'name', 'slug', 'website_url' ] );

		$posts = get_posts( [
			'post_type'      => 'gigmanager_artist',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $posts as $post ) {
			fputcsv( $output, [
				$post->post_title,
				$post->post_name,
				get_post_meta( $post->ID, self::META_PREFIX . 'website_url', true ),
			] );
		}
	}

	/**
	 * Export venues.
	 *
	 * @since 1.0.0
	 *
	 * @param resource $output File handle.
	 *
	 * @return void
	 */
	protected function export_venues( $output ): void {
		fputcsv( $output, [ 'name', 'slug', 'city', 'state', 'postal_code', 'country', 'address', 'phone', 'website_url' ] );

		$posts = get_posts( [
			'post_type'      => 'gigmanager_venue',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $posts as $post ) {
			fputcsv( $output, [
				$post->post_title,
				$post->post_name,
				get_post_meta( $post->ID, self::META_PREFIX . 'city', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'state', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'postal_code', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'country', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'address', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'phone', true ),
				get_post_meta( $post->ID, self::META_PREFIX . 'website_url', true ),
			] );
		}
	}

	/**
	 * Export tours.
	 *
	 * @since 1.0.0
	 *
	 * @param resource $output File handle.
	 *
	 * @return void
	 */
	protected function export_tours( $output ): void {
		fputcsv( $output, [ 'name', 'slug' ] );

		$posts = get_posts( [
			'post_type'      => 'gigmanager_tour',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $posts as $post ) {
			fputcsv( $output, [
				$post->post_title,
				$post->post_name,
			] );
		}
	}
}
