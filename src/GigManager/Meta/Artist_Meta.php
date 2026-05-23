<?php

namespace AGU\GigManager\Meta;

defined( 'ABSPATH' ) || exit;

/**
 * Registers meta box and handles meta save for the Artist CPT.
 *
 * @since 1.0.0
 */
class Artist_Meta {

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
	const NONCE_ACTION = 'gigmanager_artist_meta';

	/**
	 * Nonce field name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const NONCE_NAME = 'gigmanager_artist_meta_nonce';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'add_meta_boxes_gigmanager_artist', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_gigmanager_artist', [ $this, 'save' ], 10, 2 );
	}

	/**
	 * Register the Artist Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'gigmanager_artist_details',
			__( 'Artist Details', 'gigmanager' ),
			[ $this, 'render' ],
			'gigmanager_artist',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Artist Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post The current post object.
	 *
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$website_url = get_post_meta( $post->ID, self::META_PREFIX . 'website_url', true );

		?>
		<table class="form-table gigmanager-meta-table">
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
	 * Save Artist meta data.
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

		$website_url = isset( $_POST['gigmanager_website_url'] ) ? esc_url_raw( wp_unslash( $_POST['gigmanager_website_url'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'website_url', $website_url );
	}
}
