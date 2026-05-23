<?php

namespace AGU\GigManager\Widget;

use AGU\GigManager\Meta\Show_Meta;
use AGU\GigManager\Options;
use WP_Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Upcoming Shows sidebar widget.
 *
 * Displays a compact list of upcoming shows in any widget area.
 *
 * @since 1.0.0
 */
class Upcoming_Shows extends WP_Widget {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			'gigmanager_upcoming_shows',
			__( 'GigManager — Upcoming Shows', 'gigmanager' ),
			[
				'description' => __( 'Displays a list of upcoming shows.', 'gigmanager' ),
			]
		);
	}

	/**
	 * Register the widget.
	 *
	 * Called from Plugin::register() via widgets_init hook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		register_widget( self::class );
	}

	/**
	 * Front-end output.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget settings.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ): void {
		$title = ! empty( $instance['title'] )
			? $instance['title']
			: __( 'Upcoming Shows', 'gigmanager' );

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$limit = ! empty( $instance['limit'] ) ? absint( $instance['limit'] ) : 5;

		$prefix = Show_Meta::META_PREFIX;
		$today  = current_time( 'Y-m-d' );

		$query = new \WP_Query( [
			'post_type'      => 'gigmanager_show',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => $prefix . 'date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => $prefix . 'date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				],
			],
		] );

		if ( ! $query->have_posts() ) {
			return;
		}

		wp_enqueue_style(
			'gigmanager',
			\AGU\GigManager\Plugin::get()->plugin_url . 'src/resources/css/gigmanager.css',
			[],
			'1.0.0'
		);

		$artist_link     = Options::get( 'artist_link' );
		$venue_link      = Options::get( 'venue_link' );
		$display_country = Options::get( 'display_country' );
		$ticket_label    = Options::get( 'label_ticket_button', __( 'Buy Tickets', 'gigmanager' ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget args are pre-escaped by WordPress.
		echo $args['before_widget'];

		if ( $title ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget args are pre-escaped by WordPress.
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		?>
		<ul class="gigmanager-widget" aria-label="<?php esc_attr_e( 'Upcoming shows', 'gigmanager' ); ?>">
			<?php while ( $query->have_posts() ) : ?>
				<?php
				$query->the_post();
				$post_id = get_the_ID();

				$date        = get_post_meta( $post_id, $prefix . 'date', true );
				$time        = get_post_meta( $post_id, $prefix . 'time', true );
				$artist_id   = (int) get_post_meta( $post_id, $prefix . 'artist_id', true );
				$venue_id    = (int) get_post_meta( $post_id, $prefix . 'venue_id', true );
				$show_status = get_post_meta( $post_id, $prefix . 'show_status', true );
				$ticket_url  = get_post_meta( $post_id, $prefix . 'ticket_url', true );

				$formatted_date = $date ? date_i18n( get_option( 'date_format' ), strtotime( $date ) ) : '';
				$formatted_time = $time ? date_i18n( get_option( 'time_format' ), strtotime( '1970-01-01 ' . $time ) ) : '';

				// Artist.
				$artist_name = '';
				$artist_url  = '';
				if ( $artist_id ) {
					$artist_post = get_post( $artist_id );
					if ( $artist_post ) {
						$artist_name = $artist_post->post_title;
						if ( 'yes' === $artist_link ) {
							$artist_url = get_post_meta( $artist_id, $prefix . 'website_url', true );
						}
					}
				}

				// Venue.
				$venue_name    = '';
				$venue_city    = '';
				$venue_country = '';
				$venue_url     = '';
				if ( $venue_id ) {
					$venue_post = get_post( $venue_id );
					if ( $venue_post ) {
						$venue_name = $venue_post->post_title;
						$venue_city = get_post_meta( $venue_id, $prefix . 'city', true );
						if ( 'yes' === $display_country ) {
							$venue_country = get_post_meta( $venue_id, $prefix . 'country', true );
						}
						if ( 'yes' === $venue_link ) {
							$venue_url = get_post_meta( $venue_id, $prefix . 'website_url', true );
						}
					}
				}

				// Suppress ticket for cancelled/sold-out.
				if ( in_array( $show_status, [ 'cancelled', 'sold_out' ], true ) ) {
					$ticket_url = '';
				}
				?>
				<li class="gigmanager-widget__show <?php echo $show_status ? 'gigmanager-widget__show--' . esc_attr( $show_status ) : ''; ?>">
					<span class="gigmanager-widget__date">
						<time datetime="<?php echo esc_attr( $date ); ?>">
							<?php echo esc_html( $formatted_date ); ?>
						</time>
						<?php if ( $formatted_time ) : ?>
							<span class="gigmanager-widget__time"><?php echo esc_html( $formatted_time ); ?></span>
						<?php endif; ?>
					</span>

					<?php if ( $artist_name ) : ?>
						<span class="gigmanager-widget__artist">
							<?php if ( $artist_url ) : ?>
								<a href="<?php echo esc_url( $artist_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $artist_name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $artist_name ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>

					<?php if ( $venue_name || $venue_city ) : ?>
						<span class="gigmanager-widget__venue">
							<?php if ( $venue_url ) : ?>
								<a href="<?php echo esc_url( $venue_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $venue_name ); ?></a>
							<?php elseif ( $venue_name ) : ?>
								<?php echo esc_html( $venue_name ); ?>
							<?php endif; ?>
							<?php if ( $venue_city ) : ?>
								<span class="gigmanager-widget__city"><?php echo esc_html( $venue_city ); ?></span>
							<?php endif; ?>
							<?php if ( $venue_country ) : ?>
								<span class="gigmanager-widget__country"><?php echo esc_html( $venue_country ); ?></span>
							<?php endif; ?>
						</span>
					<?php endif; ?>

					<?php if ( $show_status ) : ?>
						<span class="gigmanager-widget__status gigmanager-show__status--<?php echo esc_attr( $show_status ); ?>">
							<?php
							if ( 'cancelled' === $show_status ) {
								esc_html_e( 'Cancelled', 'gigmanager' );
							} elseif ( 'sold_out' === $show_status ) {
								esc_html_e( 'Sold Out', 'gigmanager' );
							}
							?>
						</span>
					<?php endif; ?>

					<?php if ( $ticket_url ) : ?>
						<a href="<?php echo esc_url( $ticket_url ); ?>" class="gigmanager-widget__ticket" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $ticket_label ); ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endwhile; ?>
		</ul>
		<?php

		wp_reset_postdata();

		$link_text     = ! empty( $instance['link_text'] ) ? $instance['link_text'] : '';
		$shows_page_id = absint( Options::get( 'shows_page_id' ) );

		if ( $link_text && $shows_page_id && 'publish' === get_post_status( $shows_page_id ) ) :
			?>
			<p class="gigmanager-widget__more">
				<a href="<?php echo esc_url( get_permalink( $shows_page_id ) ); ?>">
					<?php echo esc_html( $link_text ); ?>
				</a>
			</p>
		<?php endif;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget args are pre-escaped by WordPress.
		echo $args['after_widget'];
	}

	/**
	 * Widget settings form in admin.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Current widget settings.
	 *
	 * @return void
	 */
	public function form( $instance ): void {
		$title     = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Upcoming Shows', 'gigmanager' );
		$limit     = ! empty( $instance['limit'] ) ? absint( $instance['limit'] ) : 5;
		$link_text = ! empty( $instance['link_text'] ) ? $instance['link_text'] : '';

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'gigmanager' ); ?>
			</label>
			<input
				type="text"
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
				<?php esc_html_e( 'Number of shows to display:', 'gigmanager' ); ?>
			</label>
			<input
				type="number"
				class="tiny-text"
				id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"
				value="<?php echo esc_attr( $limit ); ?>"
				min="1"
				max="50"
				step="1"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link_text' ) ); ?>">
				<?php esc_html_e( 'Link text:', 'gigmanager' ); ?>
			</label>
			<input
				type="text"
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'link_text' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'link_text' ) ); ?>"
				value="<?php echo esc_attr( $link_text ); ?>"
			/>
			<span class="description" style="color:rgb(100, 105, 112);">
				<?php esc_html_e( 'This phrase is used to link to the page specified in your GigManager settings. (Leave blank to disable this link.)', 'gigmanager' ); ?>
			</span>
		</p>
		<?php
	}

	/**
	 * Sanitize widget settings on save.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance = [];

		$instance['title'] = ! empty( $new_instance['title'] )
			? sanitize_text_field( $new_instance['title'] )
			: '';

		$instance['limit'] = ! empty( $new_instance['limit'] )
			? absint( $new_instance['limit'] )
			: 5;

		$instance['link_text'] = ! empty( $new_instance['link_text'] )
			? sanitize_text_field( $new_instance['link_text'] )
			: '';

		return $instance;
	}
}
