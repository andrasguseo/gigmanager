<?php

namespace AGU\GigManager\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the GigManager RSS and iCal feeds.
 *
 * Feeds are reachable at:
 *   - /feed/gigmanager-rss/   (or ?feed=gigmanager-rss)
 *   - /feed/gigmanager-ical/  (or ?feed=gigmanager-ical)
 *
 * Both accept the same request parameters as the shortcode: scope, sort,
 * limit, and artist.
 *
 * @since 1.1.0
 */
class Feeds {

	/**
	 * Feed name for the RSS feed.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const RSS_FEED = 'gigmanager-rss';

	/**
	 * Feed name for the iCal feed.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const ICAL_FEED = 'gigmanager-ical';

	/**
	 * Option key storing the plugin version last seen on the front end.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'gigmanager_version';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'init', [ $this, 'register_feeds' ] );
	}

	/**
	 * Register the feed endpoints and schedule a rewrite flush when needed.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function register_feeds(): void {
		add_feed( self::RSS_FEED, [ $this, 'render_rss' ] );
		add_feed( self::ICAL_FEED, [ $this, 'render_ical' ] );

		$this->maybe_schedule_flush();
	}

	/**
	 * Render the RSS feed.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function render_rss(): void {
		( new Rss_Feed() )->render();
	}

	/**
	 * Render the iCal feed.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function render_ical(): void {
		( new Ical_Feed() )->render();
	}

	/**
	 * Flag a rewrite-rule flush when the plugin version changes.
	 *
	 * The feed rewrite rules are added on `init`; when upgrading an existing
	 * install (where the activation hook does not run) the stored version will
	 * differ, so we reuse CPT_Registry's one-time flush mechanism to register
	 * the pretty feed permalinks.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	protected function maybe_schedule_flush(): void {
		$current = defined( 'GIGMANAGER_VERSION' ) ? GIGMANAGER_VERSION : '';
		$stored  = get_option( self::VERSION_OPTION, '' );

		if ( $stored === $current ) {
			return;
		}

		update_option( self::VERSION_OPTION, $current );
		update_option( 'gigmanager_flush_rewrites', true );
	}
}
