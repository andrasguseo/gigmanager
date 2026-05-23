<?php

namespace AGU\GigManager\Admin;

use AGU\GigManager\Plugin;
use AGU\GigManager\Import\Artist_Importer;
use AGU\GigManager\Import\Venue_Importer;
use AGU\GigManager\Import\Tour_Importer;
use AGU\GigManager\Import\Show_Importer;
use AGU\GigManager\Export\Exporter;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the Import / Export admin page.
 *
 * @since 1.0.0
 */
class Import_Export {

	/**
	 * Page slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'gigmanager-import-export';

	/**
	 * Nonce action for import.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const IMPORT_NONCE = 'gigmanager_import';

	/**
	 * Nonce action for export.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const EXPORT_NONCE = 'gigmanager_export';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'handle_export' ] );
		add_action( 'admin_init', [ $this, 'handle_import' ] );
	}

	/**
	 * Add the Import / Export submenu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_submenu_page(
			'gigmanager',
			__( 'Import / Export', 'gigmanager' ),
			__( 'Import / Export', 'gigmanager' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render the Import / Export page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'import'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import / Export', 'gigmanager' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=import' ) ); ?>"
					class="nav-tab <?php echo 'import' === $tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'Import', 'gigmanager' ); ?>
				</a>
				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=export' ) ); ?>"
					class="nav-tab <?php echo 'export' === $tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'Export', 'gigmanager' ); ?>
				</a>
			</nav>

			<?php
			if ( 'import' === $tab ) {
				$this->render_import_tab();
			} else {
				$this->render_export_tab();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render the Export tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_export_tab(): void {
		?>
		<form method="post" action="">
			<?php wp_nonce_field( self::EXPORT_NONCE, '_gigmanager_export_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="gigmanager-export-type"><?php esc_html_e( 'Entity Type', 'gigmanager' ); ?></label></th>
					<td>
						<select name="export_type" id="gigmanager-export-type">
							<option value="shows"><?php esc_html_e( 'Shows', 'gigmanager' ); ?></option>
							<option value="artists"><?php esc_html_e( 'Artists', 'gigmanager' ); ?></option>
							<option value="venues"><?php esc_html_e( 'Venues', 'gigmanager' ); ?></option>
							<option value="tours"><?php esc_html_e( 'Tours', 'gigmanager' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Download CSV', 'gigmanager' ), 'primary', 'gigmanager_export' ); ?>
		</form>
		<?php
	}

	/**
	 * Render the Import tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function render_import_tab(): void {
		?>
		<div class="notice notice-info inline" style="margin: 16px 0;">
			<p>
				<?php esc_html_e( 'Import artists and venues before importing shows.', 'gigmanager' ); ?>
			</p>
		</div>

		<form method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( self::IMPORT_NONCE, '_gigmanager_import_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="gigmanager-import-type"><?php esc_html_e( 'Entity Type', 'gigmanager' ); ?></label></th>
					<td>
						<select name="import_type" id="gigmanager-import-type">
							<option value="artists"><?php esc_html_e( 'Artists', 'gigmanager' ); ?></option>
							<option value="venues"><?php esc_html_e( 'Venues', 'gigmanager' ); ?></option>
							<option value="tours"><?php esc_html_e( 'Tours', 'gigmanager' ); ?></option>
							<option value="shows"><?php esc_html_e( 'Shows', 'gigmanager' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'CSV File', 'gigmanager' ); ?></th>
					<td>
						<input type="file" name="import_file" accept=".csv" required />
						<p class="description">
							<?php esc_html_e( 'UTF-8 encoded, comma-delimited CSV with column headers in the first row.', 'gigmanager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Import CSV', 'gigmanager' ), 'primary', 'gigmanager_import' ); ?>
		</form>

		<?php $this->maybe_render_import_results(); ?>

		<hr />

		<h3><?php esc_html_e( 'CSV Column Reference', 'gigmanager' ); ?></h3>
		<p><?php esc_html_e( 'The first row of your CSV must contain column headers. Column order does not matter. Bold columns are required.', 'gigmanager' ); ?></p>

		<table class="widefat striped" style="max-width: 800px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Entity Type', 'gigmanager' ); ?></th>
					<th><?php esc_html_e( 'Columns', 'gigmanager' ); ?></th>
					<th><?php esc_html_e( 'Duplicate Handling', 'gigmanager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Artists', 'gigmanager' ); ?></td>
					<td><strong>name</strong>, slug, website_url</td>
					<td><?php esc_html_e( 'Matched by slug — existing artists are updated.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Venues', 'gigmanager' ); ?></td>
					<td><strong>name</strong>, slug, <strong>city</strong>, state, postal_code, country, address, phone, website_url</td>
					<td><?php esc_html_e( 'Matched by slug — existing venues are updated.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Tours', 'gigmanager' ); ?></td>
					<td><strong>name</strong>, slug</td>
					<td><?php esc_html_e( 'Matched by slug — existing tours are updated.', 'gigmanager' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Shows', 'gigmanager' ); ?></td>
					<td>post_id, <strong>date</strong>, time, <strong>artist_slug</strong>, venue_name, venue_city, tour_slug, performance_status, price, ticket_url, ticket_phone, external_url, notes</td>
					<td><?php esc_html_e( 'Matched by post_id — existing shows are updated. Empty or missing post_id creates a new show.', 'gigmanager' ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render import results if stored in the transient.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function maybe_render_import_results(): void {
		$results = get_transient( 'gigmanager_import_results' );

		if ( ! $results ) {
			return;
		}

		delete_transient( 'gigmanager_import_results' );

		$created = $results['created'] ?? 0;
		$updated = $results['updated'] ?? 0;
		$skipped = $results['skipped'] ?? 0;
		$errors  = $results['errors'] ?? [];
		$type    = $results['type'] ?? '';

		?>
		<div class="gigmanager-import-results">
			<h3><?php esc_html_e( 'Import Results', 'gigmanager' ); ?></h3>

			<div class="notice notice-<?php echo $skipped ? 'warning' : 'success'; ?> inline">
				<p>
					<?php
					printf(
						/* translators: %1$d: created count, %2$d: updated count, %3$d: skipped count */
						esc_html__( '%1$d created, %2$d updated, %3$d skipped.', 'gigmanager' ),
						intval( $created ),
						intval( $updated ),
						intval( $skipped )
					);
					?>
				</p>
			</div>

			<?php if ( ! empty( $errors ) ) : ?>
				<table class="widefat striped" style="margin-top: 12px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Row', 'gigmanager' ); ?></th>
							<th><?php esc_html_e( 'Error', 'gigmanager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $errors as $error ) : ?>
							<tr>
								<td><?php echo esc_html( $error['row'] ); ?></td>
								<td><?php echo esc_html( $error['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php $this->render_failed_rows_download( $results ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the "Download failed rows" button.
	 *
	 * @since 1.0.0
	 *
	 * @param array $results The import results.
	 *
	 * @return void
	 */
	protected function render_failed_rows_download( array $results ): void {
		$failed_rows = $results['failed_rows'] ?? [];

		if ( empty( $failed_rows ) ) {
			return;
		}

		// Store failed rows in a transient for download.
		set_transient( 'gigmanager_failed_rows', $failed_rows, HOUR_IN_SECONDS );

		$download_url = wp_nonce_url(
			admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=import&gigmanager_download_failed=1' ),
			'gigmanager_download_failed'
		);

		?>
		<p style="margin-top: 12px;">
			<a href="<?php echo esc_url( $download_url ); ?>" class="button">
				<?php esc_html_e( 'Download Failed Rows', 'gigmanager' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Handle export requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_export(): void {
		if ( empty( $_POST['gigmanager_export'] ) ) {
			$this->maybe_handle_failed_rows_download();

			return;
		}

		if ( ! isset( $_POST['_gigmanager_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_gigmanager_export_nonce'] ) ), self::EXPORT_NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$type = sanitize_key( $_POST['export_type'] ?? '' );

		$exporter = new Exporter();
		$exporter->export( $type );
	}

	/**
	 * Handle import requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_import(): void {
		if ( empty( $_POST['gigmanager_import'] ) ) {
			return;
		}

		if ( ! isset( $_POST['_gigmanager_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_gigmanager_import_nonce'] ) ), self::IMPORT_NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Validate file upload.
		if ( ! isset( $_FILES['import_file']['error'] ) || UPLOAD_ERR_OK !== $_FILES['import_file']['error'] || empty( $_FILES['import_file']['tmp_name'] ) ) {
			set_transient( 'gigmanager_import_results', [
				'created' => 0,
				'updated' => 0,
				'skipped' => 1,
				'errors'  => [ [ 'row' => '-', 'message' => __( 'File upload failed.', 'gigmanager' ) ] ],
				'type'    => '',
			], 60 );

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=import' ) );
			exit;
		}

		$type = sanitize_key( $_POST['import_type'] ?? '' );
		$file = sanitize_text_field( $_FILES['import_file']['tmp_name'] );

		$importer = $this->get_importer( $type );

		if ( ! $importer ) {
			return;
		}

		$results         = $importer->import( $file );
		$results['type'] = $type;

		set_transient( 'gigmanager_import_results', $results, 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=import' ) );
		exit;
	}

	/**
	 * Get the importer for the given entity type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The entity type.
	 *
	 * @return Artist_Importer|Venue_Importer|Tour_Importer|Show_Importer|null
	 */
	protected function get_importer( string $type ) {
		switch ( $type ) {
			case 'artists':
				return new Artist_Importer();
			case 'venues':
				return new Venue_Importer();
			case 'tours':
				return new Tour_Importer();
			case 'shows':
				return new Show_Importer();
			default:
				return null;
		}
	}

	/**
	 * Handle the failed rows CSV download.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function maybe_handle_failed_rows_download(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['gigmanager_download_failed'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gigmanager_download_failed' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$failed_rows = get_transient( 'gigmanager_failed_rows' );

		if ( ! $failed_rows || ! is_array( $failed_rows ) ) {
			return;
		}

		delete_transient( 'gigmanager_failed_rows' );

		$filename = 'gigmanager-failed-rows-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write BOM for Excel UTF-8 compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write header from first row.
		if ( ! empty( $failed_rows[0] ) ) {
			fputcsv( $output, array_keys( $failed_rows[0] ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Writing to php://output, not the filesystem.
		}

		foreach ( $failed_rows as $row ) {
			fputcsv( $output, $row ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Writing to php://output, not the filesystem.
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
