<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              github.com/MohammedFaragallah
 * @since             1.0.0
 * @package           Frego_Mobile_Builder
 *
 * @wordpress-plugin
 * Plugin Name:       Frego Mobile Builder
 * Plugin URI:        github.com/MohammedFaragallah
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Mohammed Faragallah
 * Author URI:        github.com/MohammedFaragallah
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       frego-mobile-builder
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FREGO_MOBILE_BUILDER_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-frego-mobile-builder-activator.php
 */
function activate_frego_mobile_builder() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-frego-mobile-builder-activator.php';
	Frego_Mobile_Builder_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-frego-mobile-builder-deactivator.php
 */
function deactivate_frego_mobile_builder() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-frego-mobile-builder-deactivator.php';
	Frego_Mobile_Builder_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_frego_mobile_builder' );
register_deactivation_hook( __FILE__, 'deactivate_frego_mobile_builder' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-frego-mobile-builder.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_frego_mobile_builder() {

	$plugin = new Frego_Mobile_Builder();
	$plugin->run();

}
run_frego_mobile_builder();
