<?php

namespace AGU\GigManager\Meta;

use AGU\GigManager\Options;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Registers meta box and handles meta save for the Show CPT.
 *
 * @since 1.0.0
 */
class Show_Meta {

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
	const NONCE_ACTION = 'gigmanager_show_meta';

	/**
	 * Nonce field name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const NONCE_NAME = 'gigmanager_show_meta_nonce';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'add_meta_boxes_gigmanager_show', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_gigmanager_show', [ $this, 'save' ], 10, 2 );
	}

	/**
	 * Register the Show Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'gigmanager_show_details',
			__( 'Show Details', 'gigmanager' ),
			[ $this, 'render' ],
			'gigmanager_show',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Show Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The current post object.
	 *
	 * @return void
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$is_new_post  = 'auto-draft' === $post->post_status;
		$date         = get_post_meta( $post->ID, self::META_PREFIX . 'date', true );
		$time         = get_post_meta( $post->ID, self::META_PREFIX . 'time', true );
		$artist_id    = get_post_meta( $post->ID, self::META_PREFIX . 'artist_id', true );
		$venue_id     = get_post_meta( $post->ID, self::META_PREFIX . 'venue_id', true );
		$tour_id      = get_post_meta( $post->ID, self::META_PREFIX . 'tour_id', true );

		// Pre-fill with sticky defaults on new posts.
		$using_defaults = false;
		if ( $is_new_post && 'yes' === Options::get( 'sticky_defaults' ) ) {
			$user_id = get_current_user_id();

			if ( ! $artist_id ) {
				$artist_id = (int) get_user_meta( $user_id, 'gigmanager_default_artist', true );
			}
			if ( ! $venue_id ) {
				$venue_id = (int) get_user_meta( $user_id, 'gigmanager_default_venue', true );
			}
			if ( ! $tour_id ) {
				$tour_id = (int) get_user_meta( $user_id, 'gigmanager_default_tour', true );
			}

			$using_defaults = $artist_id || $venue_id || $tour_id;
		}
		$show_status  = get_post_meta( $post->ID, self::META_PREFIX . 'show_status', true );
		$price        = get_post_meta( $post->ID, self::META_PREFIX . 'price', true );
		$ticket_url   = get_post_meta( $post->ID, self::META_PREFIX . 'ticket_url', true );
		$ticket_phone = get_post_meta( $post->ID, self::META_PREFIX . 'ticket_phone', true );
		$external_url = get_post_meta( $post->ID, self::META_PREFIX . 'external_url', true );
		$notes        = get_post_meta( $post->ID, self::META_PREFIX . 'notes', true );

		$artists = get_posts( [
			'post_type'      => 'gigmanager_artist',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$venues = get_posts( [
			'post_type'      => 'gigmanager_venue',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$tours = get_posts( [
			'post_type'      => 'gigmanager_tour',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		?>
		<table class="form-table gigmanager-meta-table">
			<tr>
				<th>
					<label for="gigmanager_date">
						<?php esc_html_e( 'Date', 'gigmanager' ); ?>
						<span class="required"
						      aria-label="<?php esc_attr_e( 'Required', 'gigmanager' ); ?>">*</span>
					</label>
				</th>
				<td>
					<input
						type="date"
						id="gigmanager_date"
						name="gigmanager_date"
						value="<?php echo esc_attr( $date ); ?>"
						required
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_time">
						<?php esc_html_e( 'Time', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="time"
						id="gigmanager_time"
						name="gigmanager_time"
						value="<?php echo esc_attr( $time ); ?>"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_artist_id">
						<?php esc_html_e( 'Artist', 'gigmanager' ); ?>
						<span class="required"
						      aria-label="<?php esc_attr_e( 'Required', 'gigmanager' ); ?>">*</span>
					</label>
				</th>
				<td>
					<select id="gigmanager_artist_id" name="gigmanager_artist_id" required>
						<option value=""><?php esc_html_e( '— Select Artist —', 'gigmanager' ); ?></option>
						<?php foreach ( $artists as $artist ) : ?>
							<option
								value="<?php echo esc_attr( $artist->ID ); ?>" <?php selected( $artist_id, $artist->ID ); ?>>
								<?php echo esc_html( $artist->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if ( $using_defaults && $artist_id ) : ?>
						<p class="description"><?php esc_html_e( 'Your default — change or clear.', 'gigmanager' ); ?></p>
					<?php endif; ?>
					<?php if ( empty( $artists ) ) : ?>
						<p class="description">
							<?php
							printf(
							/* translators: 1: Opening tag and URL to add new artist, 2: Closing tag */
								esc_html__( 'No artists found. %1$sAdd an artist%2$s first.', 'gigmanager' ),
								'<a href="' . esc_url( admin_url( 'post-new.php?post_type=gigmanager_artist' ) ) . '">',
								'</a>'
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_venue_id">
						<?php esc_html_e( 'Venue', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<select id="gigmanager_venue_id" name="gigmanager_venue_id">
						<option value=""><?php esc_html_e( '— Select Venue —', 'gigmanager' ); ?></option>
						<?php foreach ( $venues as $venue ) : ?>
							<?php
							$city  = get_post_meta( $venue->ID, self::META_PREFIX . 'city', true );
							$label = $venue->post_title;
							if ( $city ) {
								$label .= ' — ' . $city;
							}
							?>
							<option
								value="<?php echo esc_attr( $venue->ID ); ?>" <?php selected( $venue_id, $venue->ID ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if ( $using_defaults && $venue_id ) : ?>
						<p class="description"><?php esc_html_e( 'Your default — change or clear.', 'gigmanager' ); ?></p>
					<?php endif; ?>
					<?php if ( empty( $venues ) ) : ?>
						<p class="description">
							<?php
							printf(
							/* translators: 1: Opening tag and URL to add new venue, 2: Closing tag */
								esc_html__( 'No venues found. %1$sAdd a venue%2$s first.', 'gigmanager' ),
								'<a href="' . esc_url( admin_url( 'post-new.php?post_type=gigmanager_venue' ) ) . '">',
								'</a>'
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_tour_id">
						<?php esc_html_e( 'Tour', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<select id="gigmanager_tour_id" name="gigmanager_tour_id">
						<option value=""><?php esc_html_e( '— Select Tour —', 'gigmanager' ); ?></option>
						<?php foreach ( $tours as $tour ) : ?>
							<option
								value="<?php echo esc_attr( $tour->ID ); ?>" <?php selected( $tour_id, $tour->ID ); ?>>
								<?php echo esc_html( $tour->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if ( $using_defaults && $tour_id ) : ?>
						<p class="description"><?php esc_html_e( 'Your default — change or clear.', 'gigmanager' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_show_status">
						<?php esc_html_e( 'Show Status', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<select id="gigmanager_show_status" name="gigmanager_show_status">
						<option value="" <?php selected( $show_status, '' ); ?>>
							<?php esc_html_e( '— None —', 'gigmanager' ); ?>
						</option>
						<option value="cancelled" <?php selected( $show_status, 'cancelled' ); ?>>
							<?php esc_html_e( 'Cancelled', 'gigmanager' ); ?>
						</option>
						<option value="sold_out" <?php selected( $show_status, 'sold_out' ); ?>>
							<?php esc_html_e( 'Sold Out', 'gigmanager' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_price">
						<?php esc_html_e( 'Price', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="gigmanager_price"
						name="gigmanager_price"
						value="<?php echo esc_attr( $price ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. $25, €15, Free', 'gigmanager' ); ?>"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_ticket_url">
						<?php esc_html_e( 'Ticket URL', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="url"
						id="gigmanager_ticket_url"
						name="gigmanager_ticket_url"
						value="<?php echo esc_attr( $ticket_url ); ?>"
						class="large-text"
						placeholder="https://"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_ticket_phone">
						<?php esc_html_e( 'Ticket Phone', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="tel"
						id="gigmanager_ticket_phone"
						name="gigmanager_ticket_phone"
						value="<?php echo esc_attr( $ticket_phone ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_external_url">
						<?php esc_html_e( 'External URL', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<input
						type="url"
						id="gigmanager_external_url"
						name="gigmanager_external_url"
						value="<?php echo esc_attr( $external_url ); ?>"
						class="large-text"
						placeholder="https://"
					/>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gigmanager_notes">
						<?php esc_html_e( 'Notes', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<textarea
						id="gigmanager_notes"
						name="gigmanager_notes"
						rows="4"
						class="large-text"
					><?php echo esc_textarea( $notes ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Plain text only.', 'gigmanager' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Show meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function save( int $post_id, WP_Post $post ): void {
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

		// Date — stored as Y-m-d.
		$date = isset( $_POST['gigmanager_date'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_date'] ) ) : '';
		if ( $date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = '';
		}
		update_post_meta( $post_id, self::META_PREFIX . 'date', $date );

		// Time — stored as H:i.
		$time = isset( $_POST['gigmanager_time'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_time'] ) ) : '';
		if ( $time && ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			$time = '';
		}
		update_post_meta( $post_id, self::META_PREFIX . 'time', $time );

		// Artist ID.
		$artist_id = isset( $_POST['gigmanager_artist_id'] ) ? absint( $_POST['gigmanager_artist_id'] ) : 0;
		update_post_meta( $post_id, self::META_PREFIX . 'artist_id', $artist_id );

		// Venue ID.
		$venue_id = isset( $_POST['gigmanager_venue_id'] ) ? absint( $_POST['gigmanager_venue_id'] ) : 0;
		update_post_meta( $post_id, self::META_PREFIX . 'venue_id', $venue_id );

		// Tour ID.
		$tour_id = isset( $_POST['gigmanager_tour_id'] ) ? absint( $_POST['gigmanager_tour_id'] ) : 0;
		update_post_meta( $post_id, self::META_PREFIX . 'tour_id', $tour_id );

		// Show Status.
		$show_status = isset( $_POST['gigmanager_show_status'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_show_status'] ) ) : '';
		if ( ! in_array( $show_status, [ '', 'cancelled', 'sold_out' ], true ) ) {
			$show_status = '';
		}
		update_post_meta( $post_id, self::META_PREFIX . 'show_status', $show_status );

		// Price — free-form string (e.g. "$25", "€15", "Free").
		$price = isset( $_POST['gigmanager_price'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_price'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'price', $price );

		// Ticket URL.
		$ticket_url = isset( $_POST['gigmanager_ticket_url'] ) ? esc_url_raw( wp_unslash( $_POST['gigmanager_ticket_url'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'ticket_url', $ticket_url );

		// Ticket Phone — free-form string.
		$ticket_phone = isset( $_POST['gigmanager_ticket_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['gigmanager_ticket_phone'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'ticket_phone', $ticket_phone );

		// External URL.
		$external_url = isset( $_POST['gigmanager_external_url'] ) ? esc_url_raw( wp_unslash( $_POST['gigmanager_external_url'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'external_url', $external_url );

		// Notes — plain text.
		$notes = isset( $_POST['gigmanager_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['gigmanager_notes'] ) ) : '';
		update_post_meta( $post_id, self::META_PREFIX . 'notes', $notes );

		// Update sticky defaults only on first save of a new show.
		// The _gigmanager_saved flag is set after the first save.
		$already_saved = get_post_meta( $post_id, self::META_PREFIX . 'saved', true );
		if ( ! $already_saved ) {
			if ( 'yes' === Options::get( 'sticky_defaults' ) ) {
				$user_id = get_current_user_id();
				update_user_meta( $user_id, 'gigmanager_default_artist', $artist_id );
				update_user_meta( $user_id, 'gigmanager_default_venue', $venue_id );
				update_user_meta( $user_id, 'gigmanager_default_tour', $tour_id );
			}
			update_post_meta( $post_id, self::META_PREFIX . 'saved', '1' );
		}
	}
}
