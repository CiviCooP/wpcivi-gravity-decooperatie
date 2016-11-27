<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Plugin;
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
     * Implements hook gform_pre_render, as well as gform_pre_validation and gform_admin_pre_render to have
     * the same data available on validation as in the admin.
     * Gets default values for this form from CiviCRM.
     * @param mixed $form Form
     * @param bool $ajax Is Ajax
     * @param mixed $field_values Field values(?)
     * @return mixed Form
     */
    public function preRender($form, $ajax = false, $field_values = [])
    {
        // Check if handler is enabled
        if (!$this->handlerIsEnabled($form)) {
            return $form;
        }

        // Get current CiviCRM contact
        $contact = new Contact;
        try {
            $contact->loadCurrentWPUser();
            $websites = Website::getWebsitesForContact($contact->id);
        } catch(\Exception $e) {
            Plugin::exitOnException($e);
        }

        // Walk fields and add data
        foreach($form['fields'] as &$field) {
            $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));
            switch($label) {

                case 'specialisme':
                    $field->defaultValue = $contact->job_title;
                    break;
                case 'expertise':
                    $expertise = $contact->getCustom('Expertise');
                    $field->defaultValue = (is_array($expertise) ? implode(',',$contact->getCustom('Expertise')) : '');
                    break;
                case 'functie':
                    $field->defaultValue = $contact->getCustom('Functie');
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

        // Get contact
        $contact = new Contact;
        $websites = [];
        try {
            $contact->loadCurrentWPUser();
            $websites = Website::getWebsitesForContact($contact->id, true);
        } catch(\Exception $e) {
            Plugin::exitOnException($e);
        }

        // Start submission to CiviCRM
        try {
            foreach ($data as $field => $value) {
                switch ($field) {

                    case 'specialisme':
                        $contact->setValue('job_title', $value);
                        break;
                    case 'expertise':
                        $contact->setCustom('Expertise', $value);
                        break;
                    case 'functie':
                        $contact->setCustom('Functie', $value);
                        break;
                    case 'werkervaring';
                        $contact->setCustom('Werkervaring', $value);
                        break;
                    case 'werkplekvoorkeur':
                        $contact->setCustom('Werkplekvoorkeur', $value);
                        break;

                    case 'website':
                        $value = $this->addhttp($value);
                        $websites['Work'] = $value;
                        break;
                    case 'linkedinprofiel':
                        $value = $this->addhttp($value);
                        $websites['LinkedIn'] = $value;
                        break;
                    case 'twitterprofiel':
                        $value = $this->addhttp($value);
                        $websites['Twitter'] = $value;
                        break;
                    case 'facebookprofiel':
                        $value = $this->addhttp($value);
                        $websites['Facebook'] = $value;
                        break;
                    case 'instagramprofiel':
                        $value = $this->addhttp($value);
                        $websites['Instagram'] = $value;
                        break;
                    case 'googleprofiel':
                        $value = $this->addhttp($value);
                        $websites['Google_'] = $value;
                        break;

                    default:
                        break;
                }
            }

            // Try to save form data
            $contact->save();
            Website::setWebsitesForContact($contact->getId(), $websites);

            // Add activity
            Activity::createActivity($contact->getId(), "WPCivi_MemberProfileForm_Result",
                "Member profile updated by MemberProfileFormHandler", "Gravity Forms Entry ID: {$entry['id']}");

            // Add status and contact id to gform meta data
            gform_update_meta($entry['id'], 'wpcivi_status', 'SUCCESS', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_entity', 'Contact', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_entityid', $contact->getId(), $form['id']);

        } catch (\Exception $e) {

            // Exception handling for development
            if (WP_DEBUG === true) {
                Plugin::exitOnException($e);
            }

            // Exception handling for production --> Add error status to gform meta data, but show nothing to user
            gform_update_meta($entry['id'], 'wpcivi_status', 'ERROR (' . $e->getMessage() . ')', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_entity', 'Contact', $form['id']);

            // If we were able to get and/or update a contact, add an activity that shows this error
            if (is_object($contact) && !empty($contact->getId())) {
                gform_update_meta($entry['id'], 'wpcivi_entityid', $contact->getId(), $form['id']);

                try {
                    Activity::createActivity($contact->getId(), "WPCivi_MemberProfileForm_Result",
                        "An error occurred while handling this form submission", "Error: " . $e->getMessage() . "<br>
                         Gravity Forms Entry ID: " . $entry['id'] . ".", "Cancelled");
                } catch (\Exception $e) {}
            }
        }

        // Return entry
        return $entry;
    }

    function addhttp($url) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }

}