<?php
/**
 * GigManager — List template.
 *
 * This template can be overridden by copying it to
 * {your-theme}/gigmanager/list.php
 *
 * @since 1.0.0
 *
 * @var array  $shows      Array of show data.
 * @var string $scope      The current scope (upcoming, past, today, all).
 * @var array  $labels     Labels from settings.
 * @var array  $feed_links Feed URLs keyed by format ('rss', 'ical'); empty when disabled.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template loaded via Loader::load() with extract(), variables are local scope.
?>
<div class="gigmanager gigmanager-list" role="list" aria-label="<?php echo esc_attr( $labels['show_singular'] ); ?> listing">
	<?php foreach ( $shows as $show ) : ?>
		<article class="gigmanager-show <?php echo $show['show_status'] ? 'gigmanager-show--' . esc_attr( $show['show_status'] ) : ''; ?>" role="listitem">

			<div class="gigmanager-show__date">
				<time datetime="<?php echo esc_attr( $show['date_raw'] ); ?>">
					<?php echo esc_html( $show['date'] ); ?>
				</time>
				<?php if ( $show['time'] ) : ?>
					<span class="gigmanager-show__time"><?php echo esc_html( $show['time'] ); ?></span>
				<?php endif; ?>
			</div>

			<div class="gigmanager-show__details">
				<?php if ( $show['artist_name'] ) : ?>
					<span class="gigmanager-show__artist">
						<?php if ( $show['artist_url'] ) : ?>
							<a href="<?php echo esc_url( $show['artist_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $show['artist_name'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $show['artist_name'] ); ?>
						<?php endif; ?>
					</span>
				<?php endif; ?>

				<?php if ( $show['venue_name'] ) : ?>
					<span class="gigmanager-show__venue">
						<?php if ( $show['venue_url'] ) : ?>
							<a href="<?php echo esc_url( $show['venue_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $show['venue_name'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $show['venue_name'] ); ?>
						<?php endif; ?>
						<?php if ( $show['venue_city'] ) : ?>
							<span class="gigmanager-show__city"><?php echo esc_html( $show['venue_city'] ); ?></span>
						<?php endif; ?>
						<?php if ( $show['venue_country'] ) : ?>
							<span class="gigmanager-show__country"><?php echo esc_html( $show['venue_country'] ); ?></span>
						<?php endif; ?>
					</span>
				<?php endif; ?>

				<?php if ( $show['tour_name'] ) : ?>
					<span class="gigmanager-show__tour"><?php echo esc_html( $show['tour_name'] ); ?></span>
				<?php endif; ?>

				<?php if ( $show['show_status'] ) : ?>
					<span class="gigmanager-show__status gigmanager-show__status--<?php echo esc_attr( $show['show_status'] ); ?>">
						<?php
						if ( 'cancelled' === $show['show_status'] ) {
							esc_html_e( 'Cancelled', 'gigmanager' );
						} elseif ( 'sold_out' === $show['show_status'] ) {
							esc_html_e( 'Sold Out', 'gigmanager' );
						}
						?>
					</span>
				<?php endif; ?>
			</div>

			<div class="gigmanager-show__actions">
				<?php if ( $show['ticket_url'] ) : ?>
					<a href="<?php echo esc_url( $show['ticket_url'] ); ?>" class="gigmanager-btn gigmanager-btn--ticket" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $labels['ticket_button'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $show['external_url'] ) : ?>
					<a href="<?php echo esc_url( $show['external_url'] ); ?>" class="gigmanager-btn gigmanager-btn--external" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $labels['external_button'] ); ?>
					</a>
				<?php endif; ?>
			</div>

		</article>
	<?php endforeach; ?>
</div>
<?php \AGU\GigManager\Template\Loader::load( 'feed-links', [ 'feed_links' => $feed_links ] ); ?>
