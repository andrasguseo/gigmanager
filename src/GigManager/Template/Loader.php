<?php

namespace AGU\GigManager\Template;

use AGU\GigManager\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Template loader with theme override support.
 *
 * Checks {theme}/gigmanager/{template}.php first, then falls
 * back to the plugin's templates/ directory.
 *
 * @since 1.0.0
 */
class Loader {

	/**
	 * Load a template file with extracted data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_name The template name (without .php extension).
	 * @param array  $data          Data to extract into template scope.
	 *
	 * @return void
	 */
	public static function load( string $template_name, array $data = [] ): void {
		$template_file = self::locate( $template_name );

		if ( ! $template_file ) {
			return;
		}

		// Extract data into template scope.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data );

		include $template_file;
	}

	/**
	 * Locate a template file.
	 *
	 * Checks the theme directory first, then falls back to the plugin
	 * templates directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_name The template name (without .php extension).
	 *
	 * @return string|false The full path to the template, or false if not found.
	 */
	public static function locate( string $template_name ): string|false {
		$template_name = sanitize_file_name( $template_name );
		$file_name     = $template_name . '.php';

		/**
		 * Filter the theme subdirectory path for template overrides.
		 *
		 * @since 1.0.0
		 *
		 * @param string $path The theme subdirectory name.
		 */
		$theme_path = apply_filters( 'gigmanager_template_path', 'gigmanager' );

		// Check theme/child-theme first.
		$template = locate_template( trailingslashit( $theme_path ) . $file_name );

		if ( ! $template ) {
			$plugin_template = Plugin::get()->plugin_path . 'templates/' . $file_name;

			if ( file_exists( $plugin_template ) ) {
				$template = $plugin_template;
			}
		}

		if ( ! $template ) {
			return false;
		}

		return $template;
	}
}
