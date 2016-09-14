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


/* --- REGISTER WITH WPCIVI_SHARED AUTOLOADER --- */

add_action('plugins_loaded', function () {

  if (!class_exists('\WPCivi\Shared\Autoloader')) {
    add_action('admin_notices', function () {
      echo '<div class="error"><p><strong>' . sprintf(_('Plugin error: <em>%1$s</em> not initialised because <em>%2$s</em> is not enabled.'), 'WPCivi Jourcoop', 'WPCivi Shared') . '</strong></p></div>';
    });
    return false;
  }

  $loader = \WPCivi\Shared\Autoloader::getInstance();
  $loader->addNamespace('WPCivi\\Jourcoop\\', __DIR__ . '/src/');
}, 52);


/* --- REGISTER PLUGIN ACTIONS / FILTERS (defined in the Plugin class) --- */
/* TODO: Check all custom CiviCRM integration code for efficiency/performance, it probably could be better... */

add_action('init', function () {
  $plugin = new \WPCivi\Jourcoop\Plugin;
  $plugin->register();
}, 52);
