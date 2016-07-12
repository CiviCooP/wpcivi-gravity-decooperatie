<?php

/*
Plugin Name: WPCivi Integration for DeCooperatie.org
Plugin URI: https://github.com/civicoop/wpcivi-jourcoop
Description: Wordpress plugin with code specific for decooperatie.org (like handling Gravity Forms submissions)
Version: 1.2
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

    // Get autoloader and add plugin namespace (only available after plugins have been loaded...)
    $wpciviloader = \WPCivi\Shared\Autoloader::getInstance();
    $wpciviloader->addNamespace('WPCivi\Jourcoop', __DIR__ . '/src/');

    // Add BackendFormHandler (adds functionality to form settings)
    $backendFormHandler = new \WPCivi\Shared\Gravity\BackendFormHandler;

    // Add frontend form handlers (more can be added here)
    $oldSignupFormHandler = new \WPCivi\Jourcoop\Gravity\OldSignupFormHandler;
    $signupFormHandler = new \WPCivi\Jourcoop\Gravity\SignupFormHandler;

});
