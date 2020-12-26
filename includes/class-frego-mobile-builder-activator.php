<?php

/**
 * Fired during plugin activation
 *
 * @link       github.com/MohammedFaragallah
 * @since      1.0.0
 *
 * @package    Frego_Mobile_Builder
 * @subpackage Frego_Mobile_Builder/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Frego_Mobile_Builder
 * @subpackage Frego_Mobile_Builder/includes
 * @author     Mohammed Faragallah <o0frego0o@hotmail.com>
 */
class Frego_Mobile_Builder_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Require parent plugin
		if ( ! is_plugin_active( 'jwt-auth/jwt-auth.php' ) and current_user_can( 'activate_plugins' ) ) {
			// Stop activation redirect and show error
			wp_die( 'Sorry, but this plugin requires the <a href="wordpress.org/plugins/jwt-auth">Jwt Auth Plugin</a> to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>' );
		}
	}
}
