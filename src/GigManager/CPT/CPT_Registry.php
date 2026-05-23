<?php

namespace AGU\GigManager\CPT;

defined( 'ABSPATH' ) || exit;

/**
 * Registers all GigManager custom post types and the admin menu.
 *
 * @since 1.0.0
 */
class CPT_Registry {

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_action( 'init', [ $this, 'register_cpts' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'maybe_flush_rewrite_rules' ] );
		add_action( 'admin_init', [ $this, 'redirect_top_level_menu' ] );
		add_action( 'add_meta_boxes', [ $this, 'remove_default_meta_boxes' ] );
	}

	/**
	 * Register all CPTs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_cpts(): void {
		$this->register_show();
		$this->register_artist();
		$this->register_venue();
		$this->register_tour();
	}

	/**
	 * Register the gigmanager_show CPT.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function register_show(): void {
		$labels = [
			'name'               => _x( 'Shows', 'post type general name', 'gigmanager' ),
			'singular_name'      => _x( 'Show', 'post type singular name', 'gigmanager' ),
			'add_new'            => __( 'Add New', 'gigmanager' ),
			'add_new_item'       => __( 'Add New Show', 'gigmanager' ),
			'edit_item'          => __( 'Edit Show', 'gigmanager' ),
			'new_item'           => __( 'New Show', 'gigmanager' ),
			'view_item'          => __( 'View Show', 'gigmanager' ),
			'search_items'       => __( 'Search Shows', 'gigmanager' ),
			'not_found'          => __( 'No shows found.', 'gigmanager' ),
			'not_found_in_trash' => __( 'No shows found in Trash.', 'gigmanager' ),
			'all_items'          => __( 'Shows', 'gigmanager' ),
			'menu_name'          => __( 'Shows', 'gigmanager' ),
		];

		register_post_type(
			'gigmanager_show',
			[
				'labels'            => $labels,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => 'gigmanager',
				'show_in_nav_menus' => false,
				'show_in_rest'      => false,
				'supports'          => [ 'title' ],
				'rewrite'           => [
					'slug'       => 'shows',
					'with_front' => false,
				],
				'has_archive'       => false,
				'capability_type'   => 'post',
				'map_meta_cap'      => true,
			]
		);
	}

	/**
	 * Register the gigmanager_artist CPT.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function register_artist(): void {
		$labels = [
			'name'               => _x( 'Artists', 'post type general name', 'gigmanager' ),
			'singular_name'      => _x( 'Artist', 'post type singular name', 'gigmanager' ),
			'add_new'            => __( 'Add New', 'gigmanager' ),
			'add_new_item'       => __( 'Add New Artist', 'gigmanager' ),
			'edit_item'          => __( 'Edit Artist', 'gigmanager' ),
			'new_item'           => __( 'New Artist', 'gigmanager' ),
			'search_items'       => __( 'Search Artists', 'gigmanager' ),
			'not_found'          => __( 'No artists found.', 'gigmanager' ),
			'not_found_in_trash' => __( 'No artists found in Trash.', 'gigmanager' ),
			'all_items'          => __( 'Artists', 'gigmanager' ),
			'menu_name'          => __( 'Artists', 'gigmanager' ),
		];

		register_post_type(
			'gigmanager_artist',
			[
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'gigmanager',
				'show_in_rest'    => false,
				'supports'        => [ 'title' ],
				'rewrite'         => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			]
		);
	}

	/**
	 * Register the gigmanager_venue CPT.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function register_venue(): void {
		$labels = [
			'name'               => _x( 'Venues', 'post type general name', 'gigmanager' ),
			'singular_name'      => _x( 'Venue', 'post type singular name', 'gigmanager' ),
			'add_new'            => __( 'Add New', 'gigmanager' ),
			'add_new_item'       => __( 'Add New Venue', 'gigmanager' ),
			'edit_item'          => __( 'Edit Venue', 'gigmanager' ),
			'new_item'           => __( 'New Venue', 'gigmanager' ),
			'search_items'       => __( 'Search Venues', 'gigmanager' ),
			'not_found'          => __( 'No venues found.', 'gigmanager' ),
			'not_found_in_trash' => __( 'No venues found in Trash.', 'gigmanager' ),
			'all_items'          => __( 'Venues', 'gigmanager' ),
			'menu_name'          => __( 'Venues', 'gigmanager' ),
		];

		register_post_type(
			'gigmanager_venue',
			[
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'gigmanager',
				'show_in_rest'    => false,
				'supports'        => [ 'title' ],
				'rewrite'         => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			]
		);
	}

	/**
	 * Register the gigmanager_tour CPT.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function register_tour(): void {
		$labels = [
			'name'               => _x( 'Tours', 'post type general name', 'gigmanager' ),
			'singular_name'      => _x( 'Tour', 'post type singular name', 'gigmanager' ),
			'add_new'            => __( 'Add New', 'gigmanager' ),
			'add_new_item'       => __( 'Add New Tour', 'gigmanager' ),
			'edit_item'          => __( 'Edit Tour', 'gigmanager' ),
			'new_item'           => __( 'New Tour', 'gigmanager' ),
			'search_items'       => __( 'Search Tours', 'gigmanager' ),
			'not_found'          => __( 'No tours found.', 'gigmanager' ),
			'not_found_in_trash' => __( 'No tours found in Trash.', 'gigmanager' ),
			'all_items'          => __( 'Tours', 'gigmanager' ),
			'menu_name'          => __( 'Tours', 'gigmanager' ),
		];

		register_post_type(
			'gigmanager_tour',
			[
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'gigmanager',
				'show_in_rest'    => false,
				'supports'        => [ 'title' ],
				'rewrite'         => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			]
		);
	}

	/**
	 * Register the top-level GigManager admin menu.
	 *
	 * CPTs with show_in_menu => 'gigmanager' attach themselves as submenus
	 * automatically. The top-level item redirects to the Shows list.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'GigManager', 'gigmanager' ),
			__( 'GigManager', 'gigmanager' ),
			'edit_posts',
			'gigmanager',
			'__return_null',
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjI1IDI1IDE1MCAxNTAiIGZpbGw9ImN1cnJlbnRDb2xvciIgZmlsbC1ydWxlPSJldmVub2RkIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDAuMzI3NTI4LDAuNDk0ODgxLC0wLjQ5MDY0MiwwLjMyNDcyMywxNTUuMTIzMDA2LC0xNy44NjQwMDMpIj4KICAgICAgICA8cGF0aCBkPSJNNTAsMTAwQzQ5LjM4NSwxMDAuNDU2IDE1MCwxMDAgMTUwLDEwMEMxNTAsMTAwIDE0OS42NzYsNDkuNzk0IDk5Ljc5NCw0OS43OTRDNDkuOTEyLDQ5Ljc5NCA1MC42MTUsOTkuNTQ0IDUwLDEwMFoiIHN0cm9rZS13aWR0aD0iMS42OSIvPgogICAgPC9nPgogICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoLTAuMzI3NTI4LC0wLjQ5NDg4MSwwLjUwMjg2NCwtMC4zMzI4MTIsMTE2LjExODgzNCwxNTAuMjgwMjE0KSI+CiAgICAgICAgPHBhdGggZD0iTTUwLDEwMEM0OS4zODUsMTAwLjQ1NiAxNTAsMTAwIDE1MCwxMDBDMTUwLDEwMCAxNDkuODg4LDgyLjY4MyAxMzkuNjkxLDY4LjM4OUMxMzIuNTI2LDU4LjM0NSA2Ny4xMjgsNTguMzQ1IDYwLjEzOSw2OC4zODlDNTAuMjY4LDgyLjU3MiA1MC4zNiw5OS43MzMgNTAsMTAwWiIgc3Ryb2tlLXdpZHRoPSIxLjY3Ii8+CiAgICA8L2c+CiAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgwLjY1NTA1NywwLjk4OTc2MSwtMC45ODk3NjEsMC42NTUwNTcsMTQyLjUyMjQxOSwtODAuNjg4NTAzKSI+CiAgICAgICAgPHBhdGggZD0iTTgyLDEwMEM4MiwxMDAgODQuMzQyLDE1NS4yNDkgODQuODY0LDE2Ny41NzlDODQuOTIyLDE2OC45MzMgODYuMDM1LDE3MCA4Ny4zOSwxNzBDOTMuMDI0LDE3MCAxMDYuOTU2LDE3MCAxMTIuNTksMTcwQzExMy45NDQsMTcwIDExNS4wNTgsMTY4LjkzMyAxMTUuMTE1LDE2Ny41OEMxMTUuNjQyLDE1NS4yNTEgMTE4LDEwMCAxMTgsMTAwQzEwNiwxMDIuODM2IDk0LDEwMi44MTIgODIsMTAwWiIgc3Ryb2tlLXdpZHRoPSIwLjg0IiBzdHJva2UtbGluZWpvaW49Im1pdGVyIiBzdHJva2UtbWl0ZXJsaW1pdD0iMiIvPgogICAgPC9nPgogICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC4wMDM2NTQsMS4yNjAxODUsLTEuMTg2ODkzLDAuMDAzNDQyLDIxNi4yMTM2MjksLTUzLjgyNTE2NikiPgogICAgICAgIDxwYXRoIGQ9Ik0xNzQuMDg0LDk3LjEyOEwxNzQuMDg0LDEwMi41MDNDMTc0LjA4NCwxMDMuOTg3IDE3Mi45NDksMTA1LjE5MSAxNzEuNTUyLDEwNS4xOTFMMTI0LjMzNiwxMDUuMTkxQzEyMi45MzksMTA1LjE5MSAxMjEuODA0LDEwMy45ODcgMTIxLjgwNCwxMDIuNTAzTDEyMS44MDQsOTcuMTI4QzEyMS44MDQsOTUuNjQ0IDEyMi45MzksOTQuNDQgMTI0LjMzNiw5NC40NEwxNzEuNTUyLDk0LjQ0QzE3Mi45NDksOTQuNDQgMTc0LjA4NCw5NS42NDQgMTc0LjA4NCw5Ny4xMjhaIi8+CiAgICA8L2c+Cjwvc3ZnPgo=',
			null
		);
	}

	/**
	 * Redirect the bare top-level menu page to the Shows list.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function redirect_top_level_menu(): void {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gigmanager' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=gigmanager_show' ) );
		exit;
	}

	/**
	 * Flush rewrite rules once after activation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( ! get_option( 'gigmanager_flush_rewrites' ) ) {
			return;
		}

		delete_option( 'gigmanager_flush_rewrites' );
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- One-time flush after plugin activation, gated by an option flag.
	}

	/**
	 * Remove default meta boxes that are not relevant for GigManager CPTs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_default_meta_boxes(): void {
		$post_types = [
			'gigmanager_show',
			'gigmanager_artist',
			'gigmanager_venue',
			'gigmanager_tour',
		];

		foreach ( $post_types as $post_type ) {
			remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
			remove_meta_box( 'commentsdiv', $post_type, 'normal' );
			remove_meta_box( 'postcustom', $post_type, 'normal' );
			remove_meta_box( 'slugdiv', $post_type, 'normal' );
		}
	}
}
