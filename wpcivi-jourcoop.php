<?php

/*
Plugin Name: WPCivi Jourcoop Forms
Plugin URI: https://github.com/civicoop/wpcivi-jourcoop
Description: Integration logic specific for De Cooperatie: handlers for custom Gravity Forms.
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

    // -----------------------------------------------------

    // Get autoloader and add plugin namespace (only available after plugins have been loaded...)
    $wpciviloader = \WPCivi\Shared\Autoloader::getInstance();
    $wpciviloader->addNamespace('WPCivi\Jourcoop', __DIR__ . '/src/');

    // -----------------------------------------------------

    // Add backend and frontend form handlers here:
    new \WPCivi\Shared\Gravity\BackendFormHandler;
    new \WPCivi\Jourcoop\Gravity\OldSignupFormHandler;
    new \WPCivi\Jourcoop\Gravity\SignupFormHandler;

});
