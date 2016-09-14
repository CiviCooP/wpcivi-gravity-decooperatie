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

        /* --- INIT CUSTOM ACF / WIDGET / ETC BLOCKS --- */

        new ContactListWidget;
        new JobListWidget;

        /* --- ADD CIVICRM TO ADMIN BAR --- */

        $this->addToAdminBar();
    }

    private function addToAdminBar()
    {
        $this->addAction('wp_before_admin_bar_render', function () {
            /** @var \WP_Admin_Bar $wp_admin_bar */
            global $wp_admin_bar;
            $wp_admin_bar->add_node([
                'parent' => '',
                'id'     => 'jourcoop-admin-civicrm',
                'title'  => 'CiviCRM',
                'href'   => '/wp-admin/admin.php?page=CiviCRM',
            ]);
        }, 1);
    }
}