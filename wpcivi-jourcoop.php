<?php

/*
Plugin Name: WPCivi Jourcoop
Plugin URI: https://github.com/civicoop/wpcivi-jourcoop
Description: Integration logic specific for De Coöperatie: custom forms, widgets and other functions.
Version: 1.3.0
Author: CiviCooP / Kevin Levie
Author URI: https://levity.nl
License: AGPL 3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.txt
Text Domain: wpcivi
*/

/**
 * WPCivi Integration for decooperatie.org
 * @package WPCivi\Jourcoop
 */

add_action('plugins_loaded', function () {

    if (!class_exists('\WPCivi\Shared\Autoloader')) {
        throw new \Exception("Plugin error: 'wpcivi_jourcoop' requires 'wpcivi_shared'.");
    }

    // Register autoloader
    $loader = \WPCivi\Shared\Autoloader::getInstance();
    $loader->addNamespace('WPCivi\Jourcoop', __DIR__ . '/src/');

    // Register plugin (actions/filters are defined in the Plugin class)
    $plugin = new \WPCivi\Jourcoop\Plugin;
    $plugin->register();
});
