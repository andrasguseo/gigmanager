<?php
/**
 * GigManager — Table template.
 *
 * This template can be overridden by copying it to
 * {your-theme}/gigmanager/table.php
 *
 * @since 1.0.0
 *
 * @var array  $shows  Array of show data.
 * @var string $scope  The current scope (upcoming, past, today, all).
 * @var array  $labels Labels from settings.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template loaded via Loader::load() with extract(), variables are local scope.

// Determine which optional columns have data.
$has_time    = false;
$has_tour    = false;
$has_country = false;

foreach ( $shows as $show ) {
	if ( $show['time'] ) {
		$has_time = true;
	}
	if ( $show['tour_name'] ) {
		$has_tour = true;
	}
	if ( $show['venue_country'] ) {
		$has_country = true;
	}
}
?>
<table class="gigmanager gigmanager-table">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Date', 'gigmanager' ); ?></th>
			<?php if ( $has_time ) : ?>
				<th scope="col"><?php esc_html_e( 'Time', 'gigmanager' ); ?></th>
			<?php endif; ?>
			<th scope="col"><?php echo esc_html( $labels['artist_singular'] ); ?></th>
			<th scope="col"><?php echo esc_html( $labels['venue_singular'] ); ?></th>
			<th scope="col"><?php esc_html_e( 'City', 'gigmanager' ); ?></th>
			<?php if ( $has_country ) : ?>
				<th scope="col"><?php esc_html_e( 'Country', 'gigmanager' ); ?></th>
			<?php endif; ?>
			<?php if ( $has_tour ) : ?>
				<th scope="col"><?php echo esc_html( $labels['tour_singular'] ); ?></th>
			<?php endif; ?>
			<th scope="col"><?php esc_html_e( 'Status', 'gigmanager' ); ?></th>
			<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'gigmanager' ); ?></span></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $shows as $show ) : ?>
			<tr class="<?php echo $show['show_status'] ? 'gigmanager-show--' . esc_attr( $show['show_status'] ) : ''; ?>">
				<td>
					<time datetime="<?php echo esc_attr( $show['date_raw'] ); ?>">
						<?php echo esc_html( $show['date'] ); ?>
					</time>
				</td>
				<?php if ( $has_time ) : ?>
					<td><?php echo $show['time'] ? esc_html( $show['time'] ) : ''; ?></td>
				<?php endif; ?>
				<td>
					<?php if ( $show['artist_url'] ) : ?>
						<a href="<?php echo esc_url( $show['artist_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $show['artist_name'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $show['artist_name'] ); ?>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( $show['venue_url'] ) : ?>
						<a href="<?php echo esc_url( $show['venue_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $show['venue_name'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $show['venue_name'] ); ?>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( $show['venue_city'] ); ?></td>
				<?php if ( $has_country ) : ?>
					<td><?php echo esc_html( $show['venue_country'] ); ?></td>
				<?php endif; ?>
				<?php if ( $has_tour ) : ?>
					<td><?php echo esc_html( $show['tour_name'] ); ?></td>
				<?php endif; ?>
				<td>
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
				</td>
				<td>
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
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
