<?php

namespace AGU\GigManager\Admin;

use AGU\GigManager\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard widget showing the quick-start checklist.
 *
 * Auto-hides when all four steps are complete.
 *
 * @since 1.0.0
 */
class Dashboard_Widget {

	/**
	 * User meta key for tracking checklist completion.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const CHECKLIST_META = 'gigmanager_checklist_complete';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'register_widget' ] );
	}

	/**
	 * Register the dashboard widget if the checklist is not yet complete.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_widget(): void {
		// Don't show to users who can't edit shows.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Don't show if user has completed the checklist.
		if ( get_user_meta( get_current_user_id(), self::CHECKLIST_META, true ) ) {
			return;
		}

		// Check completion and auto-hide.
		$steps = $this->get_steps();
		$all_complete = ! in_array( false, array_column( $steps, 'complete' ), true );

		if ( $all_complete ) {
			update_user_meta( get_current_user_id(), self::CHECKLIST_META, '1' );

			return;
		}

		wp_add_dashboard_widget(
			'gigmanager_quickstart',
			__( 'GigManager — Quick Start', 'gigmanager' ),
			[ $this, 'render' ]
		);
	}

	/**
	 * Get checklist steps with completion status.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_steps(): array {
		$shows_page_id = (int) Options::get( 'shows_page_id' );
		$has_shows_page = $shows_page_id && get_post( $shows_page_id ) && 'trash' !== get_post_status( $shows_page_id );

		return [
			[
				'label'    => __( 'Add an Artist', 'gigmanager' ),
				'url'      => admin_url( 'post-new.php?post_type=gigmanager_artist' ),
				'complete' => (bool) wp_count_posts( 'gigmanager_artist' )->publish,
			],
			[
				'label'    => __( 'Add a Venue', 'gigmanager' ),
				'url'      => admin_url( 'post-new.php?post_type=gigmanager_venue' ),
				'complete' => (bool) wp_count_posts( 'gigmanager_venue' )->publish,
			],
			[
				'label'    => __( 'Add a Show', 'gigmanager' ),
				'url'      => admin_url( 'post-new.php?post_type=gigmanager_show' ),
				'complete' => (bool) wp_count_posts( 'gigmanager_show' )->publish,
			],
			[
				'label'    => __( 'View your Shows page', 'gigmanager' ),
				'url'      => $has_shows_page ? get_permalink( $shows_page_id ) : '',
				'complete' => $has_shows_page && (bool) wp_count_posts( 'gigmanager_show' )->publish,
			],
		];
	}

	/**
	 * Render the dashboard widget.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render(): void {
		$steps     = $this->get_steps();
		$completed = count( array_filter( array_column( $steps, 'complete' ) ) );
		$total     = count( $steps );

		?>
		<p>
			<?php
			printf(
				/* translators: %1$d: completed steps, %2$d: total steps */
				esc_html__( '%1$d of %2$d steps complete', 'gigmanager' ),
				intval( $completed ),
				intval( $total )
			);
			?>
		</p>

		<ul class="gigmanager-dashboard-checklist">
			<?php foreach ( $steps as $step ) : ?>
				<li class="<?php echo $step['complete'] ? 'is-complete' : ''; ?>">
					<?php if ( $step['complete'] ) : ?>
						<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
						<?php echo esc_html( $step['label'] ); ?>
					<?php elseif ( $step['url'] ) : ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<a href="<?php echo esc_url( $step['url'] ); ?>">
							<?php echo esc_html( $step['label'] ); ?>
						</a>
					<?php else : ?>
						<span class="dashicons dashicons-minus"></span>
						<?php echo esc_html( $step['label'] ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
	}
}
