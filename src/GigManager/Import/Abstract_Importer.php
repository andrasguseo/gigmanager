<?php

namespace AGU\GigManager\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for CSV importers.
 *
 * @since 1.0.0
 */
abstract class Abstract_Importer {

	/**
	 * Meta key prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const META_PREFIX = '_gigmanager_';

	/**
	 * Required CSV columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	abstract protected function required_columns(): array;

	/**
	 * Process a single row.
	 *
	 * @since 1.0.0
	 *
	 * @param array $row Associative array of column => value.
	 * @param int   $row_number The 1-based row number.
	 *
	 * @return array Result with 'status' key: 'created', 'updated', or 'skipped'.
	 */
	abstract protected function process_row( array $row, int $row_number ): array;

	/**
	 * Import a CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Path to the uploaded CSV file.
	 *
	 * @return array Import results.
	 */
	public function import( string $file ): array {
		$results = [
			'created'     => 0,
			'updated'     => 0,
			'skipped'     => 0,
			'errors'      => [],
			'failed_rows' => [],
		];

		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV parsing requires native file handle for fgetcsv.

		if ( ! $handle ) {
			$results['skipped'] = 1;
			$results['errors'][] = [
				'row'     => '-',
				'message' => __( 'Could not open the CSV file.', 'gigmanager' ),
			];

			return $results;
		}

		// Skip BOM if present.
		$bom = fread( $handle, 3 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading BOM from CSV stream.
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		// Read header row.
		$headers = fgetcsv( $handle );

		if ( ! $headers ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$results['skipped'] = 1;
			$results['errors'][] = [
				'row'     => '-',
				'message' => __( 'Could not read CSV headers.', 'gigmanager' ),
			];

			return $results;
		}

		// Trim whitespace from headers.
		$headers = array_map( 'trim', $headers );

		// Validate required columns exist.
		$missing = array_diff( $this->required_columns(), $headers );

		if ( ! empty( $missing ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$results['skipped'] = 1;
			$results['errors'][] = [
				'row'     => '-',
				'message' => sprintf(
					/* translators: %s: comma-separated list of missing column names */
					__( 'Missing required columns: %s', 'gigmanager' ),
					implode( ', ', $missing )
				),
			];

			return $results;
		}

		$row_number = 1; // Data rows start at 1 (after header).

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			// Skip empty rows.
			if ( empty( array_filter( $data ) ) ) {
				continue;
			}

			// Pad data to match header count.
			if ( count( $data ) < count( $headers ) ) {
				$data = array_pad( $data, count( $headers ), '' );
			}

			$row    = array_combine( $headers, array_slice( $data, 0, count( $headers ) ) );
			$result = $this->process_row( $row, $row_number );

			switch ( $result['status'] ) {
				case 'created':
					$results['created']++;
					break;
				case 'updated':
					$results['updated']++;
					break;
				case 'skipped':
					$results['skipped']++;
					$results['errors'][]      = [
						'row'     => $row_number,
						'message' => $result['message'] ?? __( 'Unknown error.', 'gigmanager' ),
					];
					$results['failed_rows'][] = $row;
					break;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $results;
	}
}
