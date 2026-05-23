<?php

namespace AGU\GigManager\Import;

defined( 'ABSPATH' ) || exit;

/**
 * CSV importer for Venues.
 *
 * Upsert logic: slug-based. If a venue with the same slug exists, it is updated.
 *
 * @since 1.0.0
 */
class Venue_Importer extends Abstract_Importer {

	/**
	 * @inheritDoc
	 */
	protected function required_columns(): array {
		return [ 'name', 'city' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function process_row( array $row, int $row_number ): array {
		$name = trim( $row['name'] ?? '' );
		$city = trim( $row['city'] ?? '' );

		if ( '' === $name ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Missing required field: name.', 'gigmanager' ),
			];
		}

		if ( '' === $city ) {
			return [
				'status'  => 'skipped',
				'message' => __( 'Missing required field: city.', 'gigmanager' ),
			];
		}

		$slug        = trim( $row['slug'] ?? '' );
		$state       = sanitize_text_field( $row['state'] ?? '' );
		$postal_code = sanitize_text_field( $row['postal_code'] ?? '' );
		$country     = sanitize_text_field( $row['country'] ?? '' );
		$address     = sanitize_text_field( $row['address'] ?? '' );
		$phone       = sanitize_text_field( $row['phone'] ?? '' );
		$website_url = trim( $row['website_url'] ?? '' );

		// Look for existing post by slug.
		$existing = null;
		if ( $slug ) {
			$existing = get_page_by_path( $slug, OBJECT, 'gigmanager_venue' );
		}

		if ( $existing ) {
			wp_update_post( [
				'ID'         => $existing->ID,
				'post_title' => $name,
			] );

			$this->update_meta( $existing->ID, $city, $state, $postal_code, $country, $address, $phone, $website_url );

			return [ 'status' => 'updated' ];
		}

		$post_data = [
			'post_type'   => 'gigmanager_venue',
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
				'message' => __( 'Failed to create venue.', 'gigmanager' ),
			];
		}

		$this->update_meta( $post_id, $city, $state, $postal_code, $country, $address, $phone, $website_url );

		return [ 'status' => 'created' ];
	}

	/**
	 * Update venue meta fields.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $city        City.
	 * @param string $state       State / Province.
	 * @param string $postal_code Postal Code.
	 * @param string $country     Country.
	 * @param string $address     Address.
	 * @param string $phone       Phone.
	 * @param string $website_url Website URL.
	 *
	 * @return void
	 */
	protected function update_meta( int $post_id, string $city, string $state, string $postal_code, string $country, string $address, string $phone, string $website_url ): void {
		update_post_meta( $post_id, self::META_PREFIX . 'city', sanitize_text_field( $city ) );
		update_post_meta( $post_id, self::META_PREFIX . 'state', $state );
		update_post_meta( $post_id, self::META_PREFIX . 'postal_code', $postal_code );
		update_post_meta( $post_id, self::META_PREFIX . 'country', $country );
		update_post_meta( $post_id, self::META_PREFIX . 'address', $address );
		update_post_meta( $post_id, self::META_PREFIX . 'phone', $phone );
		update_post_meta( $post_id, self::META_PREFIX . 'website_url', esc_url_raw( $website_url ) );
	}
}
