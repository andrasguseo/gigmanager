<?php

namespace AGU\GigManager\Admin;

use AGU\GigManager\Meta\Show_Meta;
use AGU\GigManager\Plugin;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Customises the Shows CPT admin list screen.
 *
 * Adds custom columns, sortable date column, and filter dropdowns
 * for Artist and Tour.
 *
 * @since 1.0.0
 */
class Show_List {

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_filter( 'manage_gigmanager_show_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_gigmanager_show_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
		add_filter( 'manage_edit-gigmanager_show_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'sort_by_show_date' ] );
		add_action( 'restrict_manage_posts', [ $this, 'filter_dropdowns' ], 10, 1 );
		add_action( 'pre_get_posts', [ $this, 'apply_filters' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
	}

	/**
	 * Define custom columns for the Shows list screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns The default columns.
	 *
	 * @return array
	 */
	public function columns( array $columns ): array {
		$new_columns = [];

		$new_columns['cb']                    = $columns['cb'];
		$new_columns['title']                 = $columns['title'];
		$new_columns['gigmanager_show_date']    = __( 'Show Date', 'gigmanager' );
		$new_columns['gigmanager_show_time']    = __( 'Time', 'gigmanager' );
		$new_columns['gigmanager_show_artist']  = __( 'Artist', 'gigmanager' );
		$new_columns['gigmanager_show_venue']   = __( 'Venue', 'gigmanager' );
		$new_columns['gigmanager_show_tour']    = __( 'Tour', 'gigmanager' );
		$new_columns['gigmanager_show_status']  = __( 'Status', 'gigmanager' );
		$new_columns['gigmanager_show_tickets'] = __( 'Tickets', 'gigmanager' );

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  The column name.
	 * @param int    $post_id The post ID.
	 *
	 * @return void
	 */
	public function column_content( string $column, int $post_id ): void {
		$prefix = Show_Meta::META_PREFIX;

		switch ( $column ) {
			case 'gigmanager_show_date':
				$date = get_post_meta( $post_id, $prefix . 'date', true );
				if ( $date ) {
					echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
				} else {
					echo '—';
				}
				break;

			case 'gigmanager_show_time':
				$time = get_post_meta( $post_id, $prefix . 'time', true );
				if ( $time ) {
					echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( '1970-01-01 ' . $time ) ) );
				} else {
					echo '—';
				}
				break;

			case 'gigmanager_show_artist':
				$artist_id = get_post_meta( $post_id, $prefix . 'artist_id', true );
				if ( $artist_id ) {
					$artist = get_post( $artist_id );
					if ( $artist ) {
						printf(
							'<a href="%s">%s</a>',
							esc_url( get_edit_post_link( $artist_id ) ),
							esc_html( $artist->post_title )
						);
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'gigmanager_show_venue':
				$venue_id = get_post_meta( $post_id, $prefix . 'venue_id', true );
				if ( $venue_id ) {
					$venue = get_post( $venue_id );
					if ( $venue ) {
						$city  = get_post_meta( $venue_id, $prefix . 'city', true );
						$label = $venue->post_title;
						if ( $city ) {
							$label .= ', ' . $city;
						}
						printf(
							'<a href="%s">%s</a>',
							esc_url( get_edit_post_link( $venue_id ) ),
							esc_html( $label )
						);
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'gigmanager_show_tour':
				$tour_id = get_post_meta( $post_id, $prefix . 'tour_id', true );
				if ( $tour_id ) {
					$tour = get_post( $tour_id );
					if ( $tour ) {
						printf(
							'<a href="%s">%s</a>',
							esc_url( get_edit_post_link( $tour_id ) ),
							esc_html( $tour->post_title )
						);
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'gigmanager_show_status':
				$show_status = get_post_meta( $post_id, $prefix . 'show_status', true );
				if ( 'cancelled' === $show_status ) {
					echo '<span class="gigmanager-badge gigmanager-badge--cancelled">'
						. esc_html__( 'Cancelled', 'gigmanager' )
						. '</span>';
				} elseif ( 'sold_out' === $show_status ) {
					echo '<span class="gigmanager-badge gigmanager-badge--sold-out">'
						. esc_html__( 'Sold Out', 'gigmanager' )
						. '</span>';
				} else {
					echo '—';
				}
				break;

			case 'gigmanager_show_tickets':
				$ticket_url = get_post_meta( $post_id, $prefix . 'ticket_url', true );
				if ( $ticket_url ) {
					printf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( $ticket_url ),
						esc_html__( 'Link', 'gigmanager' )
					);
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Make the Show Date column sortable.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns The sortable columns.
	 *
	 * @return array
	 */
	public function sortable_columns( array $columns ): array {
		$columns['gigmanager_show_date'] = 'gigmanager_show_date';

		return $columns;
	}

	/**
	 * Handle sorting by show date.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return void
	 */
	public function sort_by_show_date( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'gigmanager_show' ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		// Default sort: show date ascending (when no explicit sort requested).
		if ( empty( $orderby ) ) {
			$query->set( 'meta_key', Show_Meta::META_PREFIX . 'date' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );

			return;
		}

		if ( 'gigmanager_show_date' === $orderby ) {
			$query->set( 'meta_key', Show_Meta::META_PREFIX . 'date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Render Artist and Tour filter dropdowns on the Shows list screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The current post type.
	 *
	 * @return void
	 */
	public function filter_dropdowns( string $post_type ): void {
		if ( 'gigmanager_show' !== $post_type ) {
			return;
		}

		$this->render_artist_dropdown();
		$this->render_tour_dropdown();
	}

	/**
	 * Render the Artist filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_artist_dropdown(): void {
		$artists = get_posts( [
			'post_type'      => 'gigmanager_artist',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		if ( empty( $artists ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['gigmanager_artist_filter'] ) ? absint( $_GET['gigmanager_artist_filter'] ) : 0;

		?>
		<select name="gigmanager_artist_filter">
			<option value=""><?php esc_html_e( 'All Artists', 'gigmanager' ); ?></option>
			<?php foreach ( $artists as $artist ) : ?>
				<option value="<?php echo esc_attr( $artist->ID ); ?>" <?php selected( $selected, $artist->ID ); ?>>
					<?php echo esc_html( $artist->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render the Tour filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_tour_dropdown(): void {
		$tours = get_posts( [
			'post_type'      => 'gigmanager_tour',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		if ( empty( $tours ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['gigmanager_tour_filter'] ) ? absint( $_GET['gigmanager_tour_filter'] ) : 0;

		?>
		<select name="gigmanager_tour_filter">
			<option value=""><?php esc_html_e( 'All Tours', 'gigmanager' ); ?></option>
			<?php foreach ( $tours as $tour ) : ?>
				<option value="<?php echo esc_attr( $tour->ID ); ?>" <?php selected( $selected, $tour->ID ); ?>>
					<?php echo esc_html( $tour->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Apply Artist and Tour filters to the Shows list query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return void
	 */
	public function apply_filters( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'gigmanager_show' ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$artist_filter = isset( $_GET['gigmanager_artist_filter'] ) ? absint( $_GET['gigmanager_artist_filter'] ) : 0;
		if ( $artist_filter ) {
			$meta_query[] = [
				'key'   => Show_Meta::META_PREFIX . 'artist_id',
				'value' => $artist_filter,
			];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tour_filter = isset( $_GET['gigmanager_tour_filter'] ) ? absint( $_GET['gigmanager_tour_filter'] ) : 0;
		if ( $tour_filter ) {
			$meta_query[] = [
				'key'   => Show_Meta::META_PREFIX . 'tour_id',
				'value' => $tour_filter,
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Enqueue admin styles for GigManager screens.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_admin_styles( string $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$gigmanager_types = [
			'gigmanager_show',
			'gigmanager_artist',
			'gigmanager_venue',
			'gigmanager_tour',
		];

		$is_gigmanager_cpt    = in_array( $screen->post_type, $gigmanager_types, true );
		$is_gigmanager_page   = false !== strpos( $screen->id, 'gigmanager' );
		$is_dashboard       = 'dashboard' === $screen->id;

		if ( ! $is_gigmanager_cpt && ! $is_gigmanager_page && ! $is_dashboard ) {
			return;
		}

		wp_enqueue_style(
			'gigmanager-admin',
			Plugin::get()->plugin_url . 'src/resources/css/gigmanager-admin.css',
			[],
			'1.0.0'
		);
	}
}
