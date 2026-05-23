<?php
/**
 * GigManager — Classic template.
 *
 * This template can be overridden by copying it to
 * {your-theme}/gigmanager/classic.php
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
<table class="gigmanager gigmanager-classic">
	<thead>
		<tr class="gigmanager-classic__header-row">
			<th scope="col" class="gigmanager-classic__date"><?php esc_html_e( 'Date', 'gigmanager' ); ?></th>
			<th scope="col" class="gigmanager-classic__artist"><?php echo esc_html( $labels['artist_singular'] ); ?></th>
			<th scope="col" class="gigmanager-classic__city"><?php esc_html_e( 'City', 'gigmanager' ); ?></th>
			<th scope="col" class="gigmanager-classic__venue"><?php echo esc_html( $labels['venue_singular'] ); ?></th>
			<?php if ( $has_country ) : ?>
			<th scope="col" class="gigmanager-classic__country"><?php esc_html_e( 'Country', 'gigmanager' ); ?></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tbody>
	<?php $previous_tour = ''; ?>
	<?php foreach ( $shows as $show ) : ?>
		<?php $divider = ''; ?>
		<?php if ( $show['tour_name'] && $show['tour_name'] !== $previous_tour ) : ?>
		<?php $previous_tour = $show['tour_name']; ?>
		<tr class="gigmanager-classic__tour-row">
			<td class="gigmanager-classic__tour" colspan="<?php echo $has_country ? '5' : '4'; ?>">
				<?php echo esc_html( $labels['tour_singular'] ); ?>: <?php echo esc_html( $show['tour_name'] ); ?>
			</td>
		</tr>
		<?php endif; ?>
		<?php if ( $previous_tour !== '' && $show['tour_name'] === '' ) : ?>
		<?php $divider = 'gigmanager-classic__divider'; ?>
		<?php $previous_tour = ''; ?>
		<?php endif; ?>
		<tr	class="
			gigmanager-classic__top-row
			<?php echo $show['tour_name'] ? 'gigmanager-classic__row--tour' : ''; ?>
			<?php echo $show['show_status'] ? 'gigmanager-show--' . esc_attr( $show['show_status'] ) : ''; ?>
			<?php echo esc_attr( $divider ); ?>
		">
			<td class="gigmanager-classic__date">
				<time datetime="<?php echo esc_attr( $show['date_raw'] ); ?>">
					<?php echo esc_html( $show['date'] ); ?>
				</time>
			</td>
			<td class="gigmanager-classic__artist">
				<?php if ( $show['artist_url'] ) : ?>
					<a href="<?php echo esc_url( $show['artist_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $show['artist_name'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $show['artist_name'] ); ?>
				<?php endif; ?>
			</td>
			<td class="gigmanager-classic__city">
				<?php echo esc_html( $show['venue_city'] ); ?>
			</td>
			<td class="gigmanager-classic__venue">
				<?php if ( $show['venue_url'] ) : ?>
					<a href="<?php echo esc_url( $show['venue_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $show['venue_name'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $show['venue_name'] ); ?>
				<?php endif; ?>
			</td>
			<?php if ( $has_country ) : ?>
			<td class="gigmanager-classic__country">
				<?php echo esc_html( $show['venue_country'] ); ?>
			</td>
			<?php endif; ?>
		</tr>
		<tr	class="
			gigmanager-classic__bottom-row
			<?php echo $show['tour_name'] ? 'gigmanager-classic__row--tour' : ''; ?>
			<?php echo $show['show_status'] ? 'gigmanager-show--' . esc_attr( $show['show_status'] ) : ''; ?>
		">
			<td></td>
			<td class="gigmanager-classic__notes" colspan="<?php echo $has_country ? '4' : '3'; ?>">
				<?php if ( $show['time'] ) : ?>
					<span class="label"><?php esc_html_e( 'Time:', 'gigmanager' ); ?></span> <?php echo esc_html( $show['time'] ); ?>.
				<?php endif; ?>

				<?php if ( $show['price'] ) : ?>
					<span class="label"><?php esc_html_e( 'Admission:', 'gigmanager' ); ?></span> <?php echo esc_html( $show['price'] ); ?>.
				<?php endif; ?>

				<?php if ( $show['ticket_phone'] ) : ?>
					<span class="label"><?php esc_html_e( 'Box office:', 'gigmanager' ); ?></span> <?php echo esc_html( $show['ticket_phone'] ); ?>.
				<?php endif; ?>

				<?php if ( $show['venue_address'] ) : ?>
					<span class="label"><?php esc_html_e( 'Address:', 'gigmanager' ); ?></span> <?php echo esc_html( $show['venue_address'] ); ?>.
				<?php endif; ?>

				<?php if ( $show['venue_phone'] ) : ?>
					<span class="label"><?php esc_html_e( 'Venue phone:', 'gigmanager' ); ?></span> <?php echo esc_html( $show['venue_phone'] ); ?>.
				<?php endif; ?>

				<?php echo $show['notes'] ? esc_html( $show['notes'] ) . '.' : ''; ?>

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

				<?php if ( $show['ticket_url'] ) : ?>
					<a href="<?php echo esc_url( $show['ticket_url'] ); ?>" class="" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $labels['ticket_button'] ); ?>
					</a>&nbsp;
				<?php endif; ?>

				<?php if ( $show['external_url'] ) : ?>
					<a href="<?php echo esc_url( $show['external_url'] ); ?>" class="" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $labels['external_button'] ); ?>
					</a>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
