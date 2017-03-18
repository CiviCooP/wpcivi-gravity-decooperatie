<?php
namespace WPCivi\Jourcoop;

use WPCivi\Jourcoop\Gravity\JobReplyFormHandler;
use WPCivi\Jourcoop\Gravity\MemberContactFormHandler;
use WPCivi\Jourcoop\Gravity\MemberProfileFormHandler;
use WPCivi\Jourcoop\Gravity\NewJobFormHandler;
use WPCivi\Jourcoop\Gravity\SignupFormHandler;
use WPCivi\Jourcoop\Widget\ContactDetailWidget;
use WPCivi\Jourcoop\Widget\ContactListSprekersWidget;
use WPCivi\Jourcoop\Widget\ContactListWidget;
use WPCivi\Jourcoop\Widget\JobDetailWidget;
use WPCivi\Jourcoop\Widget\JobListWidget;
use WPCivi\Shared\BasePlugin;

/**
 * Class Plugin
 * Initialises all WP plugin functionality.
 * @package WPCivi\Jourcoop
 */
class Plugin extends BasePlugin
{
    /**
     * Load plugin classes and register hooks
     */
    public function register()
    {
        /* --- INIT GRAVITY FORM HANDLERS --- */
        // (Used the gform_loaded hook before, but that didn't always work)

        new SignupFormHandler;
        new MemberContactFormHandler;
        new MemberProfileFormHandler;
        new NewJobFormHandler;
        new JobReplyFormHandler;

        /* --- INIT CUSTOM ACF / WIDGET / ETC BLOCKS --- */

        new ContactListWidget;
        new ContactDetailWidget;
        new JobListWidget;
        new JobDetailWidget;
        new ContactListSprekersWidget;

        /* --- ADD CIVICRM TO ADMIN BAR --- */

        $this->addToAdminBar();
    }

    private function addToAdminBar()
    {
        $this->addAction('wp_before_admin_bar_render', function () {
            if(current_user_can('access_civicrm')) {
                /** @var \WP_Admin_Bar $wp_admin_bar */
                global $wp_admin_bar;

                $wp_admin_bar->add_node([
                    'parent' => '',
                    'id'     => 'jourcoop-admin-civicrm',
                    'title'  => 'CiviCRM',
                    'href'   => '/wp-admin/admin.php?page=CiviCRM',
                ]);
            }
        }, 991);
    }
}