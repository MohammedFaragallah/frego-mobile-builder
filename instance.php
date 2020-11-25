<?php
/**
 * Instantiates the Frego Mobile Builder plugin
 *
 * @package FregoMobileBuilder
 */

namespace FregoMobileBuilder;

global $frego_mobile_builder_plugin;

require_once __DIR__ . '/php/class-plugin-base.php';
require_once __DIR__ . '/php/class-plugin.php';

$frego_mobile_builder_plugin = new Plugin();

/**
 * Frego Mobile Builder Plugin Instance
 *
 * @return Plugin
 */
function get_plugin_instance() {
	global $frego_mobile_builder_plugin;
	return $frego_mobile_builder_plugin;
}
