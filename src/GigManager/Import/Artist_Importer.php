<?php

namespace AGU\GigManager\Import;

defined( 'ABSPATH' ) || exit;

/**
 * CSV importer for Artists.
 *
 * Upsert logic: slug-based. If an artist with the same slug exists, it is updated.
 *
 * @since 1.0.0
 */
class Artist_Importer extends Abstract_Importer {

	/**
	 * @inheritDoc
	 */
	protected function required_columns(): array {
		return [ 'name' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function process_row( array $row, int $row_number ): array {
		$name = trim( $row['name'] ?? '' );

		if ( '' === $name ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Missing required field: name.', 'gigmanager' ),
			];
		}

		$slug        = trim( $row['slug'] ?? '' );
		$website_url = trim( $row['website_url'] ?? '' );

		// Look for existing post by slug.
		$existing = null;
		if ( $slug ) {
			$existing = get_page_by_path( $slug, OBJECT, 'gigmanager_artist' );
		}

		if ( $existing ) {
			wp_update_post( [
				'ID'         => $existing->ID,
				'post_title' => $name,
			] );

			update_post_meta( $existing->ID, self::META_PREFIX . 'website_url', esc_url_raw( $website_url ) );

			return [ 'status' => 'updated' ];
		}

		$post_data = [
			'post_type'   => 'gigmanager_artist',
			'post_title'  => $name,
			'post_status' => 'publish',
		];

		if ( $slug ) {
			$post_data['post_name'] = $slug;
		}

		$post_id = wp_insert_post( $post_data );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Failed to create artist.', 'gigmanager' ),
			];
		}

		update_post_meta( $post_id, self::META_PREFIX . 'website_url', esc_url_raw( $website_url ) );

		return [ 'status' => 'created' ];
	}
}
