<?php
/**
 * GigManager — Feed links partial.
 *
 * Shared by the list, table, and classic templates so the feed links only
 * need to be customized in one place.
 *
 * This template can be overridden by copying it to
 * {your-theme}/gigmanager/feed-links.php
 *
 * @since 1.1.0
 *
 * @var array $feed_links Feed URLs keyed by format ('rss', 'ical'). Empty when
 *                        the "Display Feed Links" setting is disabled.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template loaded via Loader::load() with extract(), variables are local scope.

if ( empty( $feed_links ) ) {
	return;
}

$has_rss  = ! empty( $feed_links['rss'] );
$has_ical = ! empty( $feed_links['ical'] );
?>
<p class="gigmanager gigmanager-feed-links">
	<?php if ( $has_rss ) : ?>
		<a class="gigmanager-feed-links__link gigmanager-feed-links__link--rss" href="<?php echo esc_url( $feed_links['rss'] ); ?>">
			<?php esc_html_e( 'RSS', 'gigmanager' ); ?>
		</a>
	<?php endif; ?>

	<?php if ( $has_rss && $has_ical ) : ?>
		<span class="gigmanager-feed-links__separator" aria-hidden="true">|</span>
	<?php endif; ?>

	<?php if ( $has_ical ) : ?>
		<a class="gigmanager-feed-links__link gigmanager-feed-links__link--ical" href="<?php echo esc_url( $feed_links['ical'] ); ?>">
			<?php esc_html_e( 'iCal', 'gigmanager' ); ?>
		</a>
	<?php endif; ?>
</p>
