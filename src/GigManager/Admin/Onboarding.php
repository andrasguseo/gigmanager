<?php

namespace AGU\GigManager\Admin;

use AGU\GigManager\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation, welcome screen, and onboarding flow.
 *
 * @since 1.0.0
 */
class Onboarding {

	/**
	 * Option key for the activation redirect flag.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const REDIRECT_OPTION = 'gigmanager_activation_redirect';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'admin_init', [ $this, 'maybe_redirect' ] );
		add_action( 'admin_menu', [ $this, 'add_welcome_page' ] );
		add_action( 'admin_notices', [ $this, 'shows_page_deleted_notice' ] );
	}

	/**
	 * Run on plugin activation.
	 *
	 * Creates the Shows page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::maybe_create_shows_page();

		// Set redirect flag only on first activation.
		if ( ! get_option( 'gigmanager_activated_once' ) ) {
			update_option( self::REDIRECT_OPTION, true );
			update_option( 'gigmanager_activated_once', true );
		}
	}

	/**
	 * Create the Shows page with the shortcode if it doesn't exist.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected static function maybe_create_shows_page(): void {
		$existing_page_id = (int) Options::get( 'shows_page_id' );

		// If we already have a valid page, bail.
		if ( $existing_page_id && get_post( $existing_page_id ) && 'trash' !== get_post_status( $existing_page_id ) ) {
			return;
		}

		$page_id = wp_insert_post( [
			'post_title'   => __( 'Shows', 'gigmanager' ),
			'post_content' => '[gigmanager_shows]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
		] );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			Options::update( 'shows_page_id', $page_id );
		}
	}

	/**
	 * Redirect to welcome screen after first activation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_redirect(): void {
		if ( ! get_option( self::REDIRECT_OPTION ) ) {
			return;
		}

		delete_option( self::REDIRECT_OPTION );

		// Don't redirect during bulk activation or AJAX.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking presence, not processing data.
		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=gigmanager-welcome' ) );
		exit;
	}

	/**
	 * Register the hidden welcome page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_welcome_page(): void {
		add_submenu_page(
			null, // Hidden from menu.
			__( 'Welcome to GigManager', 'gigmanager' ),
			'',
			'manage_options',
			'gigmanager-welcome',
			[ $this, 'render_welcome' ]
		);
	}

	/**
	 * Render the welcome screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_welcome(): void {
		$shows_page_id = (int) Options::get( 'shows_page_id' );

		?>
		<div class="wrap gigmanager-welcome">
			<h1><?php esc_html_e( 'Welcome to GigManager!', 'gigmanager' ); ?></h1>

			<p class="about-text">
				<?php esc_html_e( 'GigManager helps you manage and display live shows, artists, venues, and tours on your WordPress site.', 'gigmanager' ); ?>
			</p>

			<?php $this->render_fresh_path( $shows_page_id ); ?>
		</div>
		<?php
	}

	/**
	 * Render the fresh install quick-start path.
	 *
	 * @since 1.0.0
	 *
	 * @param int $shows_page_id The auto-created Shows page ID.
	 *
	 * @return void
	 */
	protected function render_fresh_path( int $shows_page_id ): void {
		$has_artist = (bool) wp_count_posts( 'gigmanager_artist' )->publish;
		$has_venue  = (bool) wp_count_posts( 'gigmanager_venue' )->publish;
		$has_show   = (bool) wp_count_posts( 'gigmanager_show' )->publish;

		?>
		<div class="gigmanager-welcome__quickstart">
			<h2><?php esc_html_e( 'Quick Start', 'gigmanager' ); ?></h2>

			<p><?php esc_html_e( 'Follow these steps to get your first show listed:', 'gigmanager' ); ?></p>

			<ol class="gigmanager-welcome__checklist">
				<li class="<?php echo $has_artist ? 'is-complete' : ''; ?>">
					<?php if ( $has_artist ) : ?>
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Add an Artist', 'gigmanager' ); ?>
					<?php else : ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gigmanager_artist' ) ); ?>">
							<?php esc_html_e( 'Add an Artist', 'gigmanager' ); ?>
						</a>
					<?php endif; ?>
				</li>
				<li class="<?php echo $has_venue ? 'is-complete' : ''; ?>">
					<?php if ( $has_venue ) : ?>
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Add a Venue', 'gigmanager' ); ?>
					<?php else : ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gigmanager_venue' ) ); ?>">
							<?php esc_html_e( 'Add a Venue', 'gigmanager' ); ?>
						</a>
					<?php endif; ?>
				</li>
				<li class="<?php echo $has_show ? 'is-complete' : ''; ?>">
					<?php if ( $has_show ) : ?>
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Add a Show', 'gigmanager' ); ?>
					<?php else : ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gigmanager_show' ) ); ?>">
							<?php esc_html_e( 'Add a Show', 'gigmanager' ); ?>
						</a>
					<?php endif; ?>
				</li>
				<li>
					<?php if ( $shows_page_id && get_post( $shows_page_id ) ) : ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<a href="<?php echo esc_url( get_permalink( $shows_page_id ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View your Shows page', 'gigmanager' ); ?>
						</a>
					<?php else : ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<?php esc_html_e( 'View your Shows page (page will be created on next activation)', 'gigmanager' ); ?>
					<?php endif; ?>
				</li>
			</ol>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=gigmanager-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Go to Settings', 'gigmanager' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Show admin notice if the Shows page has been deleted.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function shows_page_deleted_notice(): void {
		$page_id = (int) Options::get( 'shows_page_id' );

		if ( ! $page_id ) {
			return;
		}

		$page = get_post( $page_id );

		if ( $page && 'trash' !== get_post_status( $page ) ) {
			return;
		}

		// Only show to users who can manage options and are on GigManager screens.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( false === strpos( $screen->id, 'gigmanager' ) && false === strpos( $screen->id, 'gigmanager' ) ) ) {
			return;
		}

		$recreate_url = wp_nonce_url(
			admin_url( 'admin.php?page=gigmanager-settings&gigmanager_recreate_page=1' ),
			'gigmanager_recreate_page'
		);

		?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: 1: opening strong tag, 2: closing strong tag, 3: opening link to recreate page, 4: closing link tag, 5: opening link to settings page, 6: closing link tag */
					esc_html__( '%1$sGigManager:%2$s Your Shows page has been deleted. %3$sRecreate it%4$s or select a different page in %5$sSettings%6$s.', 'gigmanager' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $recreate_url ) . '">',
					'</a>',
					'<a href="' . esc_url( admin_url( 'admin.php?page=gigmanager-settings' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
