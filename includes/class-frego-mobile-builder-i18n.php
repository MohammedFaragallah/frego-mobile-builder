<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @see       https://rnlab.io
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @author     RNLAB <ngocdt@rnlab.io>
 */
class Mobile_Builder_i18n
{
    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'frego-mobile-builder',
            false,
            dirname(dirname(plugin_basename(__FILE__))).'/languages/'
        );
    }
}
