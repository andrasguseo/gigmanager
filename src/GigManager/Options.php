<?php

namespace AGU\GigManager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles reading and writing plugin options.
 *
 * All user-configurable settings are stored in a single serialized array
 * under the option key 'gigmanager_options'.
 *
 * @since 1.0.0
 */
class Options {

	/**
	 * The option key in wp_options.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const OPTION_KEY = 'gigmanager_options';

	/**
	 * Get a single plugin option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     The option key (without prefix).
	 * @param mixed  $default Optional. Default value if the key is not set.
	 *                        If not provided, falls back to the registered default.
	 *
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$options  = get_option( self::OPTION_KEY, [] );
		$defaults = self::get_defaults();

		if ( null === $default && isset( $defaults[ $key ] ) ) {
			$default = $defaults[ $key ];
		}

		return $options[ $key ] ?? $default;
	}

	/**
	 * Update a single plugin option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   The option key (without prefix).
	 * @param mixed  $value The value to store.
	 *
	 * @return bool
	 */
	public static function update( string $key, $value ): bool {
		$options         = get_option( self::OPTION_KEY, [] );
		$options[ $key ] = $value;

		return update_option( self::OPTION_KEY, $options );
	}

	/**
	 * Delete a single plugin option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The option key (without prefix).
	 *
	 * @return bool
	 */
	public static function delete( string $key ): bool {
		$options = get_option( self::OPTION_KEY, [] );

		if ( ! isset( $options[ $key ] ) ) {
			return false;
		}

		unset( $options[ $key ] );

		return update_option( self::OPTION_KEY, $options );
	}

	/**
	 * Get the default values for all plugin options.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		return [
			// General.
			'shows_page_id'          => 0,
			'display_country'        => 'no',
			'artist_link'            => 'yes',
			'venue_link'             => 'yes',
			'show_feed_links'        => 'yes',
			'sticky_defaults'        => 'yes',

			// Labels.
			'label_show_singular'    => 'Show',
			'label_show_plural'      => 'Shows',
			'label_artist_singular'  => 'Artist',
			'label_artist_plural'    => 'Artists',
			'label_tour_singular'    => 'Tour',
			'label_tour_plural'      => 'Tours',
			'label_venue_singular'   => 'Venue',
			'label_venue_plural'     => 'Venues',
			'label_ticket_button'    => 'Buy Tickets',
			'label_external_button'  => 'More Info',
			'label_no_upcoming'      => 'No upcoming shows.',
			'label_no_past'          => 'No past shows.',
		];
	}
}
