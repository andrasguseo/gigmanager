<?php
/**
 * Plugin Name:         GigManager
 * Description:         Manage and display live music shows, artists, venues, and tours.
 * Version:             1.0.0
 * Author:              Andras Guseo
 * Author URI:          https://andrasguseo.com
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         gigmanager
 * Domain Path:         /languages
 * Requires at least:   6.7
 * Requires PHP:        8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'GIGMANAGER_FILE', __FILE__ );

require_once 'src/functions/load.php';

add_action( 'plugins_loaded', 'gigmanager_load' );

register_activation_hook(
	GIGMANAGER_FILE,
	function () {
		require_once __DIR__ . '/vendor/autoload.php';
		update_option( 'gigmanager_flush_rewrites', true );
		\AGU\GigManager\Admin\Onboarding::activate();
	}
);

register_deactivation_hook(
	GIGMANAGER_FILE,
	function () {
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- One-time flush on plugin deactivation.
	}
);
