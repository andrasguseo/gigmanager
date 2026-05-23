<?php

namespace AGU\GigManager\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Customises the Tours CPT admin list screen.
 *
 * Tours only have a name (post title), so this class removes the
 * default Date column to keep the list clean.
 *
 * @since 1.0.0
 */
class Tour_List {

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_filter( 'manage_gigmanager_tour_posts_columns', [ $this, 'columns' ] );
	}

	/**
	 * Define custom columns for the Tours list screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns The default columns.
	 *
	 * @return array
	 */
	public function columns( array $columns ): array {
		$new_columns = [];

		$new_columns['cb']    = $columns['cb'];
		$new_columns['title'] = $columns['title'];

		return $new_columns;
	}
}
