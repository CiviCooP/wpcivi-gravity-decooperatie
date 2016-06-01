<?php

/*
Plugin Name: WPCivi Gravity Integration
Plugin URI: https://github.com/civicoop/wpcivi-gravity-jourcoop
Description: Wordpress plugin that handles Gravity Form submissions, specific for decooperatie.org.
Version: 1.0
Author: CiviCooP / Kevin Levie
Author URI: https://levity.nl
License: AGPL 3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.txt
Text Domain: wpcivi
*/

/**
 * WPCivi Gravity Integration for decooperatie.org
 * @package WPCivi\Gravity\Jourcoop
 */

add_action('plugins_loaded', function () {

    // Get autoloader and add plugin namespace (only available after plugins have been loaded...)
    $wpciviloader = \WPCivi\Shared\Autoloader::getInstance();
    $wpciviloader->addNamespace('WPCivi\Gravity\Jourcoop', __DIR__ . '/src/');

    // Add GeneralFormHandler (adds options to backend form settings)
    $generalHandler = new \WPCivi\Gravity\Jourcoop\GeneralFormHandler;
    $generalHandler->register();

    // Add SignupFormHandler  (more form handlers can be added here in the future)
    $signupHandler = new \WPCivi\Gravity\Jourcoop\SignupFormHandler;
    $signupHandler->register();

});
