<?php

namespace AGU\GigManager\Admin;

use AGU\GigManager\Meta\Artist_Meta;

defined( 'ABSPATH' ) || exit;

/**
 * Customises the Artists CPT admin list screen.
 *
 * @since 1.0.0
 */
class Artist_List {

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hook(): void {
		add_filter( 'manage_gigmanager_artist_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_gigmanager_artist_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
	}

	/**
	 * Define custom columns for the Artists list screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns The default columns.
	 *
	 * @return array
	 */
	public function columns( array $columns ): array {
		$new_columns = [];

		$new_columns['cb']                      = $columns['cb'];
		$new_columns['title']                   = $columns['title'];
		$new_columns['gigmanager_artist_website'] = __( 'Website', 'gigmanager' );

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  The column name.
	 * @param int    $post_id The post ID.
	 *
	 * @return void
	 */
	public function column_content( string $column, int $post_id ): void {
		if ( 'gigmanager_artist_website' !== $column ) {
			return;
		}

		$website_url = get_post_meta( $post_id, Artist_Meta::META_PREFIX . 'website_url', true );
		if ( $website_url ) {
			printf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $website_url ),
				esc_html( $website_url )
			);
		} else {
			echo '—';
		}
	}
}
