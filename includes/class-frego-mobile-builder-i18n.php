<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       github.com/MohammedFaragallah
 * @since      1.0.0
 *
 * @package    Frego_Mobile_Builder
 * @subpackage Frego_Mobile_Builder/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Frego_Mobile_Builder
 * @subpackage Frego_Mobile_Builder/includes
 * @author     Mohammed Faragallah <o0frego0o@hotmail.com>
 */
class Frego_Mobile_Builder_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'frego-mobile-builder',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
