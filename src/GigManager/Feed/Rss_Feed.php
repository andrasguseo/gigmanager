<?php

namespace AGU\GigManager\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * Renders an RSS 2.0 feed of shows.
 *
 * @since 1.1.0
 */
class Rss_Feed extends Abstract_Feed {

	/**
	 * Render the RSS feed.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function render(): void {
		$shows   = $this->get_shows();
		$charset = get_option( 'blog_charset' );

		if ( ! headers_sent() ) {
			header( 'Content-Type: application/rss+xml; charset=' . $charset, true );
		}

		echo '<?xml version="1.0" encoding="' . esc_attr( $charset ) . '"?>' . "\n";
		?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?php echo esc_html( $this->feed_title() ); ?></title>
		<link><?php echo esc_url( home_url( '/' ) ); ?></link>
		<description><?php echo esc_html( wp_strip_all_tags( get_bloginfo( 'description' ) ) ); ?></description>
		<language><?php echo esc_html( get_bloginfo( 'language' ) ); ?></language>
		<lastBuildDate><?php echo esc_html( gmdate( 'r' ) ); ?></lastBuildDate>
		<generator>GigManager</generator>
		<atom:link href="<?php echo esc_url( $this->self_url() ); ?>" rel="self" type="application/rss+xml" />
		<?php foreach ( $shows as $show ) : ?>
		<item>
			<title><?php echo esc_html( $this->show_summary( $show ) ); ?></title>
			<link><?php echo esc_url( $this->show_link( $show ) ); ?></link>
			<guid isPermaLink="false"><?php echo esc_html( $this->guid( $show ) ); ?></guid>
			<?php $pub = $this->pub_date( $show ); ?>
			<?php if ( '' !== $pub ) : ?>
			<pubDate><?php echo esc_html( $pub ); ?></pubDate>
			<?php endif; ?>
			<description><?php echo esc_html( $this->description( $show ) ); ?></description>
		</item>
		<?php endforeach; ?>
	</channel>
</rss>
		<?php
	}

	/**
	 * Build the item description from the show's date, venue, and notes.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function description( array $show ): string {
		$parts = [];

		$when = trim( $show['date'] . ' ' . $show['time'] );
		if ( '' !== $when ) {
			$parts[] = $when;
		}

		$location = $this->location( $show );
		if ( '' !== $location ) {
			$parts[] = $location;
		}

		if ( 'cancelled' === $show['show_status'] ) {
			$parts[] = __( 'Cancelled', 'gigmanager' );
		} elseif ( 'sold_out' === $show['show_status'] ) {
			$parts[] = __( 'Sold Out', 'gigmanager' );
		}

		if ( '' !== trim( (string) $show['notes'] ) ) {
			$parts[] = trim( (string) $show['notes'] );
		}

		return implode( ' — ', $parts );
	}

	/**
	 * Build a single-line location string for a show.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function location( array $show ): string {
		$parts = array_filter( [
			(string) $show['venue_name'],
			(string) $show['venue_city'],
			(string) $show['venue_state'],
			(string) $show['venue_country'],
		] );

		return implode( ', ', $parts );
	}

	/**
	 * Build a stable GUID for a show item.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function guid( array $show ): string {
		return 'gigmanager-show-' . (int) $show['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Build the RFC-822 pubDate for a show from its date/time.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $show The prepared show data.
	 *
	 * @return string
	 */
	protected function pub_date( array $show ): string {
		$dt = $this->show_datetime( $show );

		return null !== $dt ? $dt->format( 'r' ) : '';
	}

	/**
	 * Build the self-referencing feed URL for the atom:link element.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function self_url(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		return home_url( sanitize_text_field( $request ) );
	}
}
