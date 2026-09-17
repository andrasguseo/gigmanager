<?php

namespace AGU\GigManager\Admin;

use AGU\GigManager\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the GigManager Settings page with General and Labels tabs.
 *
 * All settings are stored in a single serialized option: 'gigmanager_options'.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Settings page slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'gigmanager-settings';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'handle_clear_defaults' ] );
		add_action( 'admin_init', [ $this, 'handle_recreate_page' ] );
		add_filter( 'plugin_action_links_gigmanager/gigmanager.php', [ $this, 'add_action_links' ] );
	}

	/**
	 * Add the Settings submenu page under GigManager.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'gigmanager',
			__( 'Settings', 'gigmanager' ),
			__( 'Settings', 'gigmanager' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register the single serialized option.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( 'gigmanager_settings', Options::OPTION_KEY, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_options' ],
			'default'           => Options::get_defaults(),
		] );
	}

	/**
	 * Sanitize the options array on save.
	 *
	 * Merges submitted values with existing options so that saving one tab
	 * does not erase the other tab's values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The submitted values.
	 *
	 * @return array
	 */
	public function sanitize_options( array $input ): array {
		$existing = get_option( Options::OPTION_KEY, [] );
		$defaults = Options::get_defaults();

		// Merge: existing → defaults → submitted input on top.
		$options = array_merge( $defaults, $existing );

		// General tab fields.
		if ( isset( $input['shows_page_id'] ) ) {
			$options['shows_page_id'] = absint( $input['shows_page_id'] );
		}

		if ( isset( $input['display_country'] ) ) {
			$options['display_country'] = 'yes' === $input['display_country'] ? 'yes' : 'no';
		}

		if ( isset( $input['artist_link'] ) ) {
			$options['artist_link'] = 'yes' === $input['artist_link'] ? 'yes' : 'no';
		}

		if ( isset( $input['venue_link'] ) ) {
			$options['venue_link'] = 'yes' === $input['venue_link'] ? 'yes' : 'no';
		}

		if ( isset( $input['show_feed_links'] ) ) {
			$options['show_feed_links'] = 'yes' === $input['show_feed_links'] ? 'yes' : 'no';
		}

		if ( isset( $input['sticky_defaults'] ) ) {
			$options['sticky_defaults'] = 'yes' === $input['sticky_defaults'] ? 'yes' : 'no';
		}

		// Labels tab fields.
		$label_fields = $this->get_label_fields();
		foreach ( $label_fields as $key => $field ) {
			if ( isset( $input[ $key ] ) ) {
				$options[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		return $options;
	}

	/**
	 * Get the current tab.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

		if ( ! in_array( $tab, [ 'general', 'labels', 'help' ], true ) ) {
			$tab = 'general';
		}

		return $tab;
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_tab = $this->get_current_tab();
		$tabs        = [
			'general' => __( 'General', 'gigmanager' ),
			'labels'  => __( 'Labels', 'gigmanager' ),
			'help'    => __( 'Help', 'gigmanager' ),
		];

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GigManager Settings', 'gigmanager' ); ?></h1>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( ! empty( $_GET['defaults_cleared'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Your sticky defaults have been cleared.', 'gigmanager' ); ?></p>
				</div>
			<?php endif; ?>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( ! empty( $_GET['page_recreated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Your Shows page has been recreated.', 'gigmanager' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_slug => $tab_label ) : ?>
					<a
						href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab_slug ) ); ?>"
						class="nav-tab <?php echo $current_tab === $tab_slug ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'gigmanager_settings' );

				if ( 'general' === $current_tab ) {
					$this->render_general_tab();
				} elseif ( 'labels' === $current_tab ) {
					$this->render_labels_tab();
				} else {
					$this->render_help_tab();
				}

				if ( 'help' !== $current_tab ) {
					submit_button();
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the General tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_general_tab(): void {
		$shows_page_id   = (int) Options::get( 'shows_page_id' );
		$display_country = Options::get( 'display_country' );
		$artist_link     = Options::get( 'artist_link' );
		$venue_link      = Options::get( 'venue_link' );
		$show_feed_links = Options::get( 'show_feed_links' );
		$sticky_defaults = Options::get( 'sticky_defaults' );

		$name_prefix = Options::OPTION_KEY;

		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $name_prefix . '_shows_page_id' ); ?>">
						<?php esc_html_e( 'Shows Page', 'gigmanager' ); ?>
					</label>
				</th>
				<td>
					<?php
					wp_dropdown_pages( [
						'name'              => esc_attr( $name_prefix . '[shows_page_id]' ),
						'id'                => esc_attr( $name_prefix . '_shows_page_id' ),
						'selected'          => intval( $shows_page_id ),
						'show_option_none'  => esc_html__( '— Select a page —', 'gigmanager' ),
						'option_none_value' => 0,
					] );

					if ( $shows_page_id && ! get_post( $shows_page_id ) ) {
						echo '<p class="description" style="color: #d63638;">';
						esc_html_e( 'The selected page no longer exists. Please choose a new page.', 'gigmanager' );
						echo '</p>';
					}
					?>
					<p class="description">
						<?php esc_html_e( 'The Upcoming Shows widget can link to this page. Add a shortcode to this page to display your full show listing.', 'gigmanager' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Display Country on Venue', 'gigmanager' ); ?>
				</th>
				<td>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[display_country]' ); ?>"
						       value="yes" <?php checked( $display_country, 'yes' ); ?> />
						<?php esc_html_e( 'Yes', 'gigmanager' ); ?>
					</label>
					<label style="margin-left: 16px;">
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[display_country]' ); ?>"
						       value="no" <?php checked( $display_country, 'no' ); ?> />
						<?php esc_html_e( 'No', 'gigmanager' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Useful for international touring acts. Local performers can turn this off to reduce clutter.', 'gigmanager' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Artist Name Links to URL', 'gigmanager' ); ?>
				</th>
				<td>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[artist_link]' ); ?>"
						       value="yes" <?php checked( $artist_link, 'yes' ); ?> />
						<?php esc_html_e( 'Yes', 'gigmanager' ); ?>
					</label>
					<label style="margin-left: 16px;">
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[artist_link]' ); ?>"
						       value="no" <?php checked( $artist_link, 'no' ); ?> />
						<?php esc_html_e( 'No', 'gigmanager' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Venue Name Links to URL', 'gigmanager' ); ?>
				</th>
				<td>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[venue_link]' ); ?>"
						       value="yes" <?php checked( $venue_link, 'yes' ); ?> />
						<?php esc_html_e( 'Yes', 'gigmanager' ); ?>
					</label>
					<label style="margin-left: 16px;">
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[venue_link]' ); ?>"
						       value="no" <?php checked( $venue_link, 'no' ); ?> />
						<?php esc_html_e( 'No', 'gigmanager' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Display Feed Links', 'gigmanager' ); ?>
				</th>
				<td>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[show_feed_links]' ); ?>"
						       value="yes" <?php checked( $show_feed_links, 'yes' ); ?> />
						<?php esc_html_e( 'Yes', 'gigmanager' ); ?>
					</label>
					<label style="margin-left: 16px;">
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[show_feed_links]' ); ?>"
						       value="no" <?php checked( $show_feed_links, 'no' ); ?> />
						<?php esc_html_e( 'No', 'gigmanager' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Display "RSS | iCal" links below your show listings so fans can add the dates to their calendar.', 'gigmanager' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Enable Sticky Defaults', 'gigmanager' ); ?>
				</th>
				<td>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[sticky_defaults]' ); ?>"
						       value="yes" <?php checked( $sticky_defaults, 'yes' ); ?> />
						<?php esc_html_e( 'Yes', 'gigmanager' ); ?>
					</label>
					<label style="margin-left: 16px;">
						<input type="radio"
						       name="<?php echo esc_attr( $name_prefix . '[sticky_defaults]' ); ?>"
						       value="no" <?php checked( $sticky_defaults, 'no' ); ?> />
						<?php esc_html_e( 'No', 'gigmanager' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'When enabled, the Artist, Venue, and Tour fields are pre-filled with the values from your last new show.', 'gigmanager' ); ?>
					</p>
					<p>
						<?php
						$clear_url = wp_nonce_url(
							admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general&gigmanager_clear_defaults=1' ),
							'gigmanager_clear_defaults'
						);
						?>
						<a
							href="<?php echo esc_url( $clear_url ); ?>"
							onclick="return confirm( '<?php echo esc_js( __( 'Clear your default Artist, Venue, and Tour? This cannot be undone.', 'gigmanager' ) ); ?>' );"
						>
							<?php esc_html_e( 'Click here to clear your defaults.', 'gigmanager' ); ?>
						</a>
					</p>
				</td>
			</tr>
		</table>

		<hr />

		<h3><?php esc_html_e( 'Onboarding', 'gigmanager' ); ?></h3>

		<p>
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=gigmanager-welcome' ) ); ?>"
				class="button"
			>
				<?php esc_html_e( 'View Welcome Screen', 'gigmanager' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render the Labels tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_labels_tab(): void {
		$fields      = $this->get_label_fields();
		$name_prefix = Options::OPTION_KEY;

		?>
		<p class="description">
			<?php esc_html_e( 'These labels are used on the front end only. Admin labels are not affected.', 'gigmanager' ); ?>
		</p>
		<table class="form-table">
			<?php foreach ( $fields as $key => $field ) : ?>
				<?php $value = Options::get( $key ); ?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $name_prefix . '_' . $key ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
						</label>
					</th>
					<td>
						<?php if ( 'textarea' === ( $field['type'] ?? 'text' ) ) : ?>
							<textarea
								id="<?php echo esc_attr( $name_prefix . '_' . $key ); ?>"
								name="<?php echo esc_attr( $name_prefix . '[' . $key . ']' ); ?>"
								rows="2"
								class="large-text"
							><?php echo esc_textarea( $value ); ?></textarea>
						<?php else : ?>
							<input
								type="text"
								id="<?php echo esc_attr( $name_prefix . '_' . $key ); ?>"
								name="<?php echo esc_attr( $name_prefix . '[' . $key . ']' ); ?>"
								value="<?php echo esc_attr( $value ); ?>"
								class="regular-text"
							/>
						<?php endif; ?>
						<?php if ( ! empty( $field['description'] ) ) : ?>
							<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Get label field definitions.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_label_fields(): array {
		return [
			'label_show_singular'  => [
				'label'   => __( 'Show (singular)', 'gigmanager' ),
				'default' => 'Show',
			],
			'label_show_plural'    => [
				'label'   => __( 'Show (plural)', 'gigmanager' ),
				'default' => 'Shows',
			],
			'label_artist_singular' => [
				'label'   => __( 'Artist (singular)', 'gigmanager' ),
				'default' => 'Artist',
			],
			'label_artist_plural'  => [
				'label'   => __( 'Artist (plural)', 'gigmanager' ),
				'default' => 'Artists',
			],
			'label_tour_singular'  => [
				'label'   => __( 'Tour (singular)', 'gigmanager' ),
				'default' => 'Tour',
			],
			'label_tour_plural'    => [
				'label'   => __( 'Tour (plural)', 'gigmanager' ),
				'default' => 'Tours',
			],
			'label_venue_singular' => [
				'label'   => __( 'Venue (singular)', 'gigmanager' ),
				'default' => 'Venue',
			],
			'label_venue_plural'   => [
				'label'   => __( 'Venue (plural)', 'gigmanager' ),
				'default' => 'Venues',
			],
			'label_ticket_button'  => [
				'label'   => __( 'Ticket Button Label', 'gigmanager' ),
				'default' => 'Buy Tickets',
			],
			'label_external_button' => [
				'label'   => __( 'External Link Button Label', 'gigmanager' ),
				'default' => 'More Info',
			],
			'label_no_upcoming'    => [
				'label'       => __( '"No Upcoming Shows" Message', 'gigmanager' ),
				'default'     => 'No upcoming shows.',
				'type'        => 'textarea',
				'description' => __( 'Displayed when no shows match the current shortcode filter.', 'gigmanager' ),
			],
			'label_no_past'        => [
				'label'   => __( '"No Past Shows" Message', 'gigmanager' ),
				'default' => 'No past shows.',
				'type'    => 'textarea',
			],
		];
	}

	/**
	 * Render the Help tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_help_tab(): void {
		?>
		<h3><?php esc_html_e( 'Support', 'gigmanager' ); ?></h3>
		<p>
			<?php
			printf(
			/* translators: 1: opening <a> tag, 2: closing </a> tag and external link icon */
				esc_html__( 'I\'m happy to support you with bugs and issues through the %1$sofficial WordPress.org support forums of GigManager%2$s. Feel free to also submit feature requests there and if there is a high demand, it just might make it into the plugin.', 'gigmanager' ),
				'<a href="https://wordpress.org/support/plugin/gigmanager/" target="_blank">',
				'</a> <span class="dashicons dashicons-external"></span>'
			);
			?>
		</p>
		<p>
			<?php
			printf(
			/* translators: 1: opening <a> tag, 2: closing </a> tag and external link icon, 3: opening <a> tag, 4: closing </a> tag and external link icon */
				esc_html__( 'In exchange, you can support me and my work by leaving a %1$sreview%2$s, by a %3$sdonation%4$s, or by spreading the word on social media.', 'gigmanager' ),
				'<a href="https://wordpress.org/support/plugin/gigmanager/reviews/" target="_blank">',
				'</a> <span class="dashicons dashicons-external"></span>',
				'<a href="https://www.paypal.com/paypalme/guseo">',
				'</a> <span class="dashicons dashicons-external"></span>'
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Shortcode', 'gigmanager' ); ?></h3>

		<p><?php esc_html_e( 'Use the following shortcode to display shows on any page or post:', 'gigmanager' ); ?></p>
		<p><code>[gigmanager_shows]</code></p>

		<h4><?php esc_html_e( 'Attributes', 'gigmanager' ); ?></h4>

		<table class="widefat striped" style="max-width: 800px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Attribute', 'gigmanager' ); ?></th>
					<th><?php esc_html_e( 'Values', 'gigmanager' ); ?></th>
					<th><?php esc_html_e( 'Default', 'gigmanager' ); ?></th>
					<th><?php esc_html_e( 'Description', 'gigmanager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>scope</code></td>
					<td><code>upcoming</code>, <code>past</code>, <code>today</code>, <code>all</code></td>
					<td><code>upcoming</code></td>
					<td><?php esc_html_e( 'Which shows to display based on date.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>artist</code></td>
					<td><?php esc_html_e( 'Artist slug or ID', 'gigmanager' ); ?></td>
					<td><?php esc_html_e( '(all artists)', 'gigmanager' ); ?></td>
					<td><?php esc_html_e( 'Filter shows by a specific artist.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>limit</code></td>
					<td><?php esc_html_e( 'Number', 'gigmanager' ); ?></td>
					<td><code>0</code> <?php esc_html_e( '(no limit)', 'gigmanager' ); ?></td>
					<td><?php esc_html_e( 'Maximum number of shows to display.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>sort</code></td>
					<td><code>ASC</code>, <code>DESC</code></td>
					<td><?php esc_html_e( 'ASC for upcoming/today/all, DESC for past', 'gigmanager' ); ?></td>
					<td><?php esc_html_e( 'Sort direction by date.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>template</code></td>
					<td><code>list</code>, <code>table</code>, <code>classic</code></td>
					<td><code>list</code></td>
					<td><?php esc_html_e( 'Display template to use.', 'gigmanager' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'Examples', 'gigmanager' ); ?></h4>
		<table class="widefat striped" style="max-width: 800px;">
			<tbody>
				<tr>
					<td><code>[gigmanager_shows]</code></td>
					<td><?php esc_html_e( 'All upcoming shows in list format.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>[gigmanager_shows scope="past" template="table"]</code></td>
					<td><?php esc_html_e( 'Past shows in a table.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>[gigmanager_shows artist="the-midnight-waves" limit="5"]</code></td>
					<td><?php esc_html_e( 'Next 5 upcoming shows for a specific artist.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><code>[gigmanager_shows scope="all" sort="DESC" template="classic"]</code></td>
					<td><?php esc_html_e( 'All shows, newest first, classic layout.', 'gigmanager' ); ?></td>
				</tr>
			</tbody>
		</table>

		<hr />

		<h3><?php esc_html_e( 'Templates', 'gigmanager' ); ?></h3>
		<p>
			<?php esc_html_e( 'Three built-in templates are available:', 'gigmanager' ); ?>
		</p>
		<ul style="list-style: disc; padding-left: 20px;">
			<li>
				<strong>list</strong> &mdash;
				<?php esc_html_e( 'Card-style layout with date, details, and action buttons. Best for modern themes.', 'gigmanager' ); ?>
			</li>
			<li>
				<strong>table</strong> &mdash;
				<?php esc_html_e( 'Traditional data table with one row per show and sortable-style columns.', 'gigmanager' ); ?>
			</li>
			<li>
				<strong>classic</strong> &mdash;
				<?php esc_html_e( 'Two-row layout with show details on a second row.', 'gigmanager' ); ?>
			</li>
		</ul>
		<p>
			<?php
			printf(
				/* translators: 1: plugin templates path, 2: theme override path */
				esc_html__( 'To customise templates, copy files from %1$s to %2$s. Your theme copies will take priority over the plugin defaults.', 'gigmanager' ),
				'<code>/plugins/gigmanager/templates/</code>',
				'<code>/themes/your-theme/gigmanager/</code>'
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Time Zones', 'gigmanager' ); ?></h3>
		<p>
			<?php
			esc_html_e( 'All shows use the site timezone (Settings → General → Timezone). For multi-timezone touring, add the timezone manually in the show\'s Notes field (e.g., "8pm ET / 7pm CT").', 'gigmanager' );
			?>
		</p>

		<hr />

		<p><strong>GigManager - A plugin by András Guseo - Do all things with Heart & Craft.</strong></p>
		<?php
	}

	/**
	 * Handle the "Clear my defaults" action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_clear_defaults(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['gigmanager_clear_defaults'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gigmanager_clear_defaults' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		delete_user_meta( $user_id, 'gigmanager_default_artist' );
		delete_user_meta( $user_id, 'gigmanager_default_venue' );
		delete_user_meta( $user_id, 'gigmanager_default_tour' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general&defaults_cleared=1' ) );
		exit;
	}

	/**
	 * Handle the "Recreate Shows page" action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_recreate_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['gigmanager_recreate_page'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gigmanager_recreate_page' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Force-create a new Shows page.
		Options::delete( 'shows_page_id' );
		Onboarding::activate();

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general&page_recreated=1' ) );
		exit;
	}

	/**
	 * Add a "Settings" link to the plugin action links on the Plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array Modified plugin action links.
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=gigmanager-settings' ) ),
			esc_html__( 'Settings', 'gigmanager' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
