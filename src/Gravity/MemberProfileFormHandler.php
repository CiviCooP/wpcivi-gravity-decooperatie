<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Gravity\Traits\BackendLinkTrait;
use WPCivi\Shared\Entity\Website;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\MemberProfileFormHandler
 * Handles Gravity Form submissions for the 'Mijn profiel' form.
 * @package WPCivi\Jourcoop
 */
class MemberProfileFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     */
    protected $label = 'Member Profile Form';

    /**
     * Trait for backend integration
     */
    use BackendLinkTrait;

    /**
     * Implements hook gform_pre_render, as well as gform_pre_validation and gform_admin_pre_render to have
     * the same data available on validation as in the admin.
     * Gets default values for this form from CiviCRM.
     * @param mixed $form Form
     * @param bool $ajax Is Ajax
     * @param mixed $field_values Field values(?)
     * @return bool
     */
    public function preRender($form, $ajax = false, $field_values = [])
    {
        // Check if handler is enabled
        if (!$this->handlerIsEnabled($form)) {
            return $form;
        }

        // Get current CiviCRM contact
        $contact = new Contact;
        $contact->loadCurrentWPUser();
        $websites = Website::getWebsitesForContact($contact->id);
//        $websites = [];

        // Walk fields and add data
        foreach($form['fields'] as &$field) {
            $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));
            switch($label) {
                case 'functie':
                    $field->defaultValue = $contact->job_title;
                    break;
                case 'expertise':
                    $expertise = $contact->getCustom('Expertise');
                    $field->defaultValue = (is_array($expertise) ? implode(',',$contact->getCustom('Expertise')) : '');
                    break;
                case 'werkervaring';
                    $field->defaultValue = $contact->getCustom('Werkervaring');
                    break;
                case 'werkplekvoorkeur':
                    $field->defaultValue = $contact->getCustom('Werkplekvoorkeur');
                    break;
                case 'website':
                    $field->defaultValue = (!empty($websites['Work']) ? $websites['Work'] : '');
                    break;
                case 'linkedinprofiel':
                    $field->defaultValue = (!empty($websites['LinkedIn']) ? $websites['LinkedIn'] : '');
                    break;
                case 'twitterprofiel':
                    $field->defaultValue = (!empty($websites['Twitter']) ? $websites['Twitter'] : '');
                    break;
                case 'facebookprofiel':
                    $field->defaultValue = (!empty($websites['Facebook']) ? $websites['Facebook'] : '');
                    break;
                case 'instagramprofiel':
                    $field->defaultValue = (!empty($websites['Instagram']) ? $websites['Instagram'] : '');
                    break;
                case 'googleprofiel':
                    $field->defaultValue = (!empty($websites['Google+']) ? $websites['Google+'] : '');
                    break;
                default:
                    break;
            }
        }

        return $form;
    }

    /**
     * Implements hook gform_after_submission.
     * Saves entry to CiviCRM - and adds the result status and CiviCRM contact ID to the entry meta data.
     * @param mixed $entry Entry
     * @param mixed $form Form
     * @return mixed Entry
     * @throws \Exception If an error occurs while debugging - otherwise, continues silently
     */
    public function afterSubmission($entry, $form)
    {
        // Check if handler is enabled
        if (!$this->handlerIsEnabled($form)) {
            return $form;
        }

        // Get form data
        $data = $this->getDataKVArray($entry, $form);
        $contact = null;

        // Set local timezone! (WP uses UTC, Civi uses default timezone)
        date_default_timezone_set(ini_get('date.timezone'));

        // Start submission to CiviCRM
        try {

            // TODO!

        } catch (\Exception $e) {

            // TODO!
        }

        // Revert timezone to UTC for WP
        date_default_timezone_set('UTC');

        // Return entry
        return $entry;
    }

}