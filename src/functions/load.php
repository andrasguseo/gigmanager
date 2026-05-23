<?php
defined( 'ABSPATH' ) || exit;

function gigmanager_load() {
	// Last file that needs to be loaded manually.
	require_once dirname( __DIR__ ) . '/GigManager/Plugin.php';

	// Load the plugin, autoloading happens here.
	AGU\GigManager\Plugin::boot( GIGMANAGER_FILE );
}
