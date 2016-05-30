<?php
namespace WPCivi\Gravity\Jourcoop;

use WPCivi\Shared\BasePlugin;

/**
 * Class SignupFormPlugin
 * Handles Gravity Form submissions for the DeCooperatie.org Signup Form.
 * @package WPCivi\Gravity\Jourcoop
 */
class SignupFormHandler extends BasePlugin
{

    /**
     * Register Gravity Form submission hooks once Gravity Forms is loaded.
     */
    public function register()
    {
        if ($this->isPluginActive('gravityforms')) {

            // TODO IMPLEMENT FORM HANDLER
            
        }
    }
}