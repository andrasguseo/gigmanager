<?php

namespace AGU\GigManager\Shortcode;

use AGU\GigManager\Feed\Feeds;
use AGU\GigManager\Options;
use AGU\GigManager\Query\Show_Query;
use AGU\GigManager\Template\Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles the [gigmanager_shows] shortcode.
 *
 * @since 1.0.0
 */
class Shows {

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

		$shows = ( new Show_Query() )->get_shows( $scope, $sort, $limit, $atts['artist'] );

		if ( ! $this->enqueued ) {
			wp_enqueue_style( 'gigmanager' );
			$this->enqueued = true;
		}

		if ( empty( $shows ) ) {
			return $this->no_results_message( $scope );
		}

		$labels = $this->get_labels();

		ob_start();
		Loader::load( $template, [
			'shows'      => $shows,
			'scope'      => $scope,
			'labels'     => $labels,
			'feed_links' => $this->get_feed_links( $scope, $atts['artist'] ),
		] );

		return ob_get_clean();
	}

	/**
	 * Build the RSS and iCal feed URLs for the current listing.
	 *
	 * The feeds are filtered to match what the shortcode is displaying, so a
	 * listing of one artist's past shows links to that same selection.
	 *
	 * Returns an empty array when the "Show Feed Links" setting is disabled,
	 * which the feed-links template treats as "render nothing".
	 *
	 * @since 1.1.0
	 *
	 * @param string     $scope  The resolved scope.
	 * @param string|int $artist The artist filter (ID or slug), or empty.
	 *
	 * @return array<string, string>
	 */
	protected function get_feed_links( string $scope, $artist ): array {
		if ( 'yes' !== Options::get( 'show_feed_links' ) ) {
			return [];
		}

		$args = [];

		// "upcoming" is the feed default, so only pass a scope that differs.
		if ( 'upcoming' !== $scope ) {
			$args['scope'] = $scope;
		}

		if ( '' !== (string) $artist ) {
			$args['artist'] = (string) $artist;
		}

		return [
			'rss'  => Feeds::get_feed_url( Feeds::RSS_FEED, $args ),
			'ical' => Feeds::get_feed_url( Feeds::ICAL_FEED, $args ),
		];
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
		if ( array_key_exists( $scope, Show_Query::SCOPE_DEFAULTS ) ) {
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
			return Show_Query::SCOPE_DEFAULTS[ $scope ];
		}

		$sort = strtoupper( $sort );

		if ( in_array( $sort, [ 'ASC', 'DESC' ], true ) ) {
			return $sort;
		}

		$this->debug_notice( sprintf( 'Invalid sort "%s", falling back to scope default.', $sort ) );

		return Show_Query::SCOPE_DEFAULTS[ $scope ];
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
