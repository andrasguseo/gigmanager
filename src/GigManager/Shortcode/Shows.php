<?php

namespace AGU\GigManager\Shortcode;

use AGU\GigManager\Meta\Show_Meta;
use AGU\GigManager\Options;
use AGU\GigManager\Template\Loader;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles the [gigmanager_shows] shortcode.
 *
 * @since 1.0.0
 */
class Shows {

	/**
	 * Valid scopes and their default sort direction.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	const SCOPE_DEFAULTS = [
		'upcoming' => 'ASC',
		'past'     => 'DESC',
		'today'    => 'ASC',
		'all'      => 'ASC',
	];

	/**
	 * Whether the shortcode has been rendered in this request.
	 *
	 * Used to enqueue front-end styles only when needed.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected bool $enqueued = false;

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_shortcode( 'gigmanager_shows', [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_styles' ] );
	}

	/**
	 * Register front-end styles (enqueued only when shortcode is used).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_styles(): void {
		wp_register_style(
			'gigmanager',
			\AGU\GigManager\Plugin::get()->plugin_url . 'src/resources/css/gigmanager.css',
			[],
			'1.0.0'
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Shortcode content (unused).
	 *
	 * @return string
	 */
	public function render( $atts, ?string $content = null ): string {
		$atts = shortcode_atts(
			[
				'artist'   => '',
				'scope'    => 'upcoming',
				'limit'    => 0,
				'sort'     => '',
				'template' => 'list',
			],
			$atts,
			'gigmanager_shows'
		);

		$scope    = $this->validate_scope( $atts['scope'] );
		$sort     = $this->validate_sort( $atts['sort'], $scope );
		$limit    = $this->validate_limit( $atts['limit'] );
		$template = $this->validate_template( $atts['template'] );

		$query_args = $this->build_query_args( $scope, $sort, $limit, $atts['artist'] );
		$query      = new WP_Query( $query_args );

		if ( ! $this->enqueued ) {
			wp_enqueue_style( 'gigmanager' );
			$this->enqueued = true;
		}

		if ( ! $query->have_posts() ) {
			return $this->no_results_message( $scope );
		}

		$shows  = $this->prepare_show_data( $query );
		$labels = $this->get_labels();

		ob_start();
		Loader::load( $template, [
			'shows'  => $shows,
			'scope'  => $scope,
			'labels' => $labels,
		] );

		return ob_get_clean();
	}

	/**
	 * Build WP_Query arguments from shortcode parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $scope  The scope filter.
	 * @param string     $sort   The sort direction.
	 * @param int        $limit  Maximum number of shows (0 = no limit).
	 * @param string|int $artist The artist filter (ID or slug).
	 *
	 * @return array
	 */
	protected function build_query_args( string $scope, string $sort, int $limit, $artist ): array {
		$prefix = Show_Meta::META_PREFIX;
		$today  = current_time( 'Y-m-d' );

		$args = [
			'post_type'      => 'gigmanager_show',
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'meta_key'       => $prefix . 'date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => $sort,
		];

		// Scope-based date filtering.
		$meta_query = [];

		switch ( $scope ) {
			case 'upcoming':
				$meta_query[] = [
					'key'     => $prefix . 'date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				];
				break;

			case 'past':
				$meta_query[] = [
					'key'     => $prefix . 'date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				];
				break;

			case 'today':
				$meta_query[] = [
					'key'     => $prefix . 'date',
					'value'   => $today,
					'compare' => '=',
					'type'    => 'DATE',
				];
				break;

			// 'all' — no date filter.
		}

		// Artist filter.
		if ( '' !== $artist ) {
			$artist_id = $this->resolve_artist( $artist );
			$meta_query[] = [
				'key'   => $prefix . 'artist_id',
				'value' => $artist_id,
			];
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return $args;
	}

	/**
	 * Resolve an artist parameter to a post ID.
	 *
	 * Accepts a numeric ID or a post slug. Returns 0 if not found
	 * (which will cause the query to return zero results).
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $artist The artist ID or slug.
	 *
	 * @return int
	 */
	protected function resolve_artist( $artist ): int {
		if ( is_numeric( $artist ) ) {
			$post = get_post( (int) $artist );
			if ( $post && 'gigmanager_artist' === $post->post_type ) {
				return $post->ID;
			}

			$this->debug_notice( sprintf( 'Artist ID "%d" not found.', (int) $artist ) );

			return 0;
		}

		// Slug lookup.
		$posts = get_posts( [
			'post_type'      => 'gigmanager_artist',
			'name'           => sanitize_title( $artist ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		] );

		if ( ! empty( $posts ) ) {
			return $posts[0]->ID;
		}

		$this->debug_notice( sprintf( 'Artist slug "%s" not found.', $artist ) );

		return 0;
	}

	/**
	 * Prepare an array of show data from a WP_Query result.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query result.
	 *
	 * @return array
	 */
	protected function prepare_show_data( WP_Query $query ): array {
		$prefix          = Show_Meta::META_PREFIX;
		$display_country = Options::get( 'display_country' );
		$artist_link     = Options::get( 'artist_link' );
		$venue_link      = Options::get( 'venue_link' );

		$shows = [];

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			$date        = get_post_meta( $post_id, $prefix . 'date', true );
			$time        = get_post_meta( $post_id, $prefix . 'time', true );
			$artist_id   = (int) get_post_meta( $post_id, $prefix . 'artist_id', true );
			$venue_id    = (int) get_post_meta( $post_id, $prefix . 'venue_id', true );
			$tour_id     = (int) get_post_meta( $post_id, $prefix . 'tour_id', true );
			$show_status  = get_post_meta( $post_id, $prefix . 'show_status', true );
			$price        = get_post_meta( $post_id, $prefix . 'price', true );
			$ticket_url   = get_post_meta( $post_id, $prefix . 'ticket_url', true );
			$ticket_phone = get_post_meta( $post_id, $prefix . 'ticket_phone', true );
			$external_url = get_post_meta( $post_id, $prefix . 'external_url', true );
			$notes        = get_post_meta( $post_id, $prefix . 'notes', true );

			// Format date.
			$formatted_date = '';
			if ( $date ) {
				$formatted_date = date_i18n( get_option( 'date_format' ), strtotime( $date ) );
			}

			// Format time.
			$formatted_time = '';
			if ( $time ) {
				$formatted_time = date_i18n( get_option( 'time_format' ), strtotime( '1970-01-01 ' . $time ) );
			}

			// Artist data.
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

			// Venue data.
			$venue_name        = '';
			$venue_address     = '';
			$venue_city        = '';
			$venue_state       = '';
			$venue_postal_code = '';
			$venue_phone       = '';
			$venue_country     = '';
			$venue_url         = '';
			if ( $venue_id ) {
				$venue_post = get_post( $venue_id );
				if ( $venue_post ) {
					$venue_name        = $venue_post->post_title;
					$venue_address     = get_post_meta( $venue_id, $prefix . 'address', true );
					$venue_city        = get_post_meta( $venue_id, $prefix . 'city', true );
					$venue_state       = get_post_meta( $venue_id, $prefix . 'state', true );
					$venue_postal_code = get_post_meta( $venue_id, $prefix . 'postal_code', true );
					$venue_phone       = get_post_meta( $venue_id, $prefix . 'phone', true );
					if ( 'yes' === $display_country ) {
						$venue_country = get_post_meta( $venue_id, $prefix . 'country', true );
					}
					if ( 'yes' === $venue_link ) {
						$venue_url = get_post_meta( $venue_id, $prefix . 'website_url', true );
					}
				}
			}

			// Tour data.
			$tour_name = '';
			if ( $tour_id ) {
				$tour_post = get_post( $tour_id );
				if ( $tour_post ) {
					$tour_name = $tour_post->post_title;
				}
			}

			// Suppress ticket button for cancelled/sold-out shows.
			$show_ticket_url = $ticket_url;
			if ( in_array( $show_status, [ 'cancelled', 'sold_out' ], true ) ) {
				$show_ticket_url = '';
			}

			$shows[] = [
				'id'                => $post_id,
				'date'              => $formatted_date,
				'date_raw'          => $date,
				'time'              => $formatted_time,
				'time_raw'          => $time,
				'artist_name'       => $artist_name,
				'artist_url'        => $artist_url,
				'venue_name'        => $venue_name,
				'venue_address'     => $venue_address,
				'venue_city'        => $venue_city,
				'venue_state'       => $venue_state,
				'venue_postal_code' => $venue_postal_code,
				'venue_country'     => $venue_country,
				'venue_phone'       => $venue_phone,
				'venue_url'         => $venue_url,
				'tour_name'         => $tour_name,
				'show_status'       => $show_status,
				'price'             => $price,
				'ticket_url'        => $show_ticket_url,
				'ticket_phone'      => $ticket_phone,
				'external_url'      => $external_url,
				'notes'             => $notes,
			];
		}

		wp_reset_postdata();

		return $shows;
	}

	/**
	 * Get the "no results" message for the given scope.
	 *
	 * @since 1.0.0
	 *
	 * @param string $scope The scope filter.
	 *
	 * @return string
	 */
	protected function no_results_message( string $scope ): string {
		if ( 'past' === $scope ) {
			$message = Options::get( 'label_no_past', __( 'No past shows.', 'gigmanager' ) );
		} else {
			$message = Options::get( 'label_no_upcoming', __( 'No upcoming shows.', 'gigmanager' ) );
		}

		return '<p class="gigmanager-no-shows">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Validate the scope parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $scope The scope value.
	 *
	 * @return string
	 */
	protected function validate_scope( string $scope ): string {
		if ( array_key_exists( $scope, self::SCOPE_DEFAULTS ) ) {
			return $scope;
		}

		$this->debug_notice( sprintf( 'Invalid scope "%s", falling back to "upcoming".', $scope ) );

		return 'upcoming';
	}

	/**
	 * Validate the sort parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sort  The sort value.
	 * @param string $scope The validated scope.
	 *
	 * @return string
	 */
	protected function validate_sort( string $sort, string $scope ): string {
		if ( '' === $sort ) {
			return self::SCOPE_DEFAULTS[ $scope ];
		}

		$sort = strtoupper( $sort );

		if ( in_array( $sort, [ 'ASC', 'DESC' ], true ) ) {
			return $sort;
		}

		$this->debug_notice( sprintf( 'Invalid sort "%s", falling back to scope default.', $sort ) );

		return self::SCOPE_DEFAULTS[ $scope ];
	}

	/**
	 * Validate the limit parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $limit The limit value.
	 *
	 * @return int
	 */
	protected function validate_limit( $limit ): int {
		$limit = (int) $limit;

		if ( $limit < 0 ) {
			$this->debug_notice( sprintf( 'Invalid limit "%d", falling back to no limit.', $limit ) );

			return 0;
		}

		return $limit;
	}

	/**
	 * Validate the template parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The template name.
	 *
	 * @return string
	 */
	protected function validate_template( string $template ): string {
		if ( in_array( $template, [ 'list', 'table', 'classic' ], true ) ) {
			return $template;
		}

		$this->debug_notice( sprintf( 'Invalid template "%s", falling back to "list".', $template ) );

		return 'list';
	}

	/**
	 * Get front-end labels from settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_labels(): array {
		return [
			'show_singular'   => Options::get( 'label_show_singular', __( 'Show', 'gigmanager' ) ),
			'show_plural'     => Options::get( 'label_show_plural', __( 'Shows', 'gigmanager' ) ),
			'artist_singular' => Options::get( 'label_artist_singular', __( 'Artist', 'gigmanager' ) ),
			'venue_singular'  => Options::get( 'label_venue_singular', __( 'Venue', 'gigmanager' ) ),
			'tour_singular'   => Options::get( 'label_tour_singular', __( 'Tour', 'gigmanager' ) ),
			'ticket_button'   => Options::get( 'label_ticket_button', __( 'Buy Tickets', 'gigmanager' ) ),
			'external_button' => Options::get( 'label_external_button', __( 'More Info', 'gigmanager' ) ),
		];
	}

	/**
	 * Output a WP_DEBUG notice.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The debug message.
	 *
	 * @return void
	 */
	protected function debug_notice( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				'[GigManager] ' . esc_html( $message ),
				E_USER_NOTICE
			);
		}
	}
}
