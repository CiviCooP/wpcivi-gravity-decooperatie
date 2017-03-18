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
            $label = $this->getBaseLabel($field->label);
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
                case 'beschikbaaralsspreker':
                    $field->defaultValue = $contact->getCustom('Beschikbaar_als_spreker');
                    break;

                case 'website':
                    $field->defaultValue = ((!empty($websites['Work']) && $websites['Work'] != 'http://') ? $websites['Work'] : '');
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
                  case 'beschikbaaralsspreker':
                        $contact->setCustom('Beschikbaar_als_spreker', $value);
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

            // Set status and save entity info to metadata if possible
            $this->setWPCiviStatus(self::WPCIVI_SUCCESS, $form, $entry, 'Contact', $contact->id);

        } catch (\Exception $e) {

            // If we were able to get/update a contact try to create an activity, set status in both cases
            if (is_object($contact) && !empty($contact->id)) {

                $this->setWPCiviStatus(self::WPCIVI_ERROR, $form, $entry, 'Contact', $contact->id, $e->getMessage(), $e);

                try {
                    Activity::createActivity($contact->getId(), "WPCivi_MemberProfileForm_Result",
                        "An error occurred while handling this form submission", "Error: " . $e->getMessage() . "<br>
                         Gravity Forms Entry ID: " . $entry['id'] . ".", "Cancelled");
                } catch (\Exception $e) {
                    // We won't create an activity about failing to create an activity
                }
            } else {
                $this->setWPCiviStatus(self::WPCIVI_ERROR, $form, $entry, 'Contact', null, $e->getMessage(), $e);
            }
        }

        // Return entry
        return $entry;
    }

    /**
     * Adds HTTP to a URL if necessary
     * @param string $url URL
     * @return string URL with http://
     */
    private function addhttp($url) {
        if(empty($url) || $url == 'http://') {
            return '';
        }
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }

}