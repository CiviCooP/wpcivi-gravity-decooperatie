<?php
namespace WPCivi\Jourcoop;

use WPCivi\Jourcoop\Gravity\MemberContactFormHandler;
use WPCivi\Jourcoop\Gravity\MemberProfileFormHandler;
use WPCivi\Jourcoop\Gravity\SignupFormHandler;
use WPCivi\Jourcoop\Widget\ContactListWidget;
use WPCivi\Jourcoop\Widget\JobListWidget;
use WPCivi\Shared\BasePlugin;

/**
 * Class Plugin
 * Initialises all WP plugin functionality.
 * @package WPCivi\Jourcoop
 */
class Plugin extends BasePlugin
{

    public function register()
    {

        /* ----- GRAVITY FORM HANDLERS (werkt niet met gform_loaded?) ----- */

        $this->addAction('init', function () {

            new SignupFormHandler;
            // new OldSignupFormHandler;

            new MemberContactFormHandler;
             new MemberProfileFormHandler;
        });

        /* ----- CUSTOM ACF / WIDGET / SHORTCODE / ETC BLOCKS ----- */

        $this->addAction('init', function () {

            new ContactListWidget;
            new JobListWidget;
        });
    }
}