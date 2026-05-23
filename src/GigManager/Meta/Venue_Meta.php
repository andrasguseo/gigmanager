<?php

namespace AGU\GigManager\Meta;

defined( 'ABSPATH' ) || exit;

/**
 * Registers meta box and handles meta save for the Venue CPT.
 *
 * @since 1.0.0
 */
class Venue_Meta {

	/**
	 * Meta key prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const META_PREFIX = '_gigmanager_';

	/**
	 * Nonce action.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'gigmanager_venue_meta';

	/**
	 * Nonce field name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const NONCE_NAME = 'gigmanager_venue_meta_nonce';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'add_meta_boxes_gigmanager_venue', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_gigmanager_venue', [ $this, 'save' ], 10, 2 );
	}

	/**
	 * Register the Venue Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'gigmanager_venue_details',
			__( 'Venue Details', 'gigmanager' ),
			[ $this, 'render' ],
			'gigmanager_venue',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Venue Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post The current post object.
	 *
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$city        = get_post_meta( $post->ID, self::META_PREFIX . 'city', true );
		$state       = get_post_meta( $post->ID, self::META_PREFIX . 'state', true );
		$postal_code = get_post_meta( $post->ID, self::META_PREFIX . 'postal_code', true );
		$country     = get_post_meta( $post->ID, self::META_PREFIX . 'country', true );
		$address     = get_post_meta( $post->ID, self::META_PREFIX . 'address', true );
		$phone       = get_post_meta( $post->ID, self::META_PREFIX . 'phone', true );
		$website_url = get_post_meta( $post->ID, self::META_PREFIX . 'website_url', true );

		?>
		<table class="form-table gigmanager-meta-table">
			<tr>
				<th>
					<label for="gigmanager_address">
						<?php esc_html_e( 'Address', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="gigmanager_address"
						name="gigmanager_address"
						value="<?php echo esc_attr( $address ); ?>"
						class="large-text"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_city">
						<?php esc_html_e( 'City', 'gigmanager' ); ?>
						<span class="required" aria-label="<?php esc_attr_e( 'Required', 'gigmanager' ); ?>">*</span>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="gigmanager_city"
						name="gigmanager_city"
						value="<?php echo esc_attr( $city ); ?>"
						class="regular-text"
						required
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_state">
						<?php esc_html_e( 'State / Province', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="gigmanager_state"
						name="gigmanager_state"
						value="<?php echo esc_attr( $state ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_postal_code">
						<?php esc_html_e( 'Postal Code', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="gigmanager_postal_code"
						name="gigmanager_postal_code"
						value="<?php echo esc_attr( $postal_code ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_country">
						<?php esc_html_e( 'Country', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="gigmanager_country"
						name="gigmanager_country"
						value="<?php echo esc_attr( $country ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_phone">
						<?php esc_html_e( 'Phone', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="tel"
						id="gigmanager_phone"
						name="gigmanager_phone"
						value="<?php echo esc_attr( $phone ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_website_url">
						<?php esc_html_e( 'Website URL', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="url"
						id="gigmanager_website_url"
						name="gigmanager_website_url"
						value="<?php echo esc_attr( $website_url ); ?>"
						class="large-text"
						placeholder="https://"
					/>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Venue meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// City.
		$city = isset( $_POST['gigmanager_city'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_city'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'city', $city );

		// State / Province.
		$state = isset( $_POST['gigmanager_state'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_state'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'state', $state );

		// Postal Code.
		$postal_code = isset( $_POST['gigmanager_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_postal_code'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'postal_code', $postal_code );

		// Country.
		$country = isset( $_POST['gigmanager_country'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_country'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'country', $country );

		// Address.
		$address = isset( $_POST['gigmanager_address'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_address'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'address', $address );

		// Phone.
		$phone = isset( $_POST['gigmanager_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_phone'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'phone', $phone );

		// Website URL.
		$website_url = isset( $_POST['gigmanager_website_url'] ) ? esc_url_raw( wp_unslash( $_POST['gigmanager_website_url'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'website_url', $website_url );
	}
}
