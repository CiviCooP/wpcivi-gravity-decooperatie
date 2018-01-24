<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Plugin;
use WPCivi\Shared\Entity\Website;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\MemberPaymentFormHandler
 * Handles Gravity Form submissions for the 'Betaal je lidmaatschap' form.
 * @package WPCivi\Jourcoop
 */
class MemberPaymentFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     */
    protected $label = 'Member Payment Form';

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
                case 'voornaam':
                    $field->defaultValue = $contact->getValue('first_name');
                    break;
                case 'tussenvoegsel':
                    $field->defaultValue = $contact->getValue('middle_name');
                    break;
                case 'achternaam';
                    $field->defaultValue = $contact->getValue('last_name');
                    break;
                case 'straathuisnummer':
                    $field->defaultValue = $contact->getAddress()->getValue('street_address');
                    break;
                case 'postcode':
                    $field->defaultValue = $contact->getAddress()->getValue('postal_code');
                    break;
                case 'woonplaats':
                    $field->defaultValue = $contact->getAddress()->getValue('city');
                    break;
                case 'land':
                    $field->defaultValue = 'Nederland'; // TODO Parse country
                    break;
                case 'emailadres':
                    $field->defaultValue = $contact->getEmail()->getValue('email');
                    break;
                /* case 'lidmaatschap':
                    $field->defaultValue = ''; // TODO
                    break; */
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
                    case 'voornaam':
                        $contact->setValue('first_name', $value);
                        break;
                    case 'tussenvoegsel':
                        $contact->setValue('middle_name', $value);
                        break;
                    case 'achternaam';
                        $contact->setValue('last_name', $value);
                        break;
                    case 'straathuisnummer':
                        $contact->getAddress()->setValue('street_address', $value);
                        break;
                    case 'postcode':
                        $contact->getAddress()->setValue('postal_code', $value);
                        break;
                    case 'woonplaats':
                        $contact->getAddress()->setValue('city', $value);
                        break;
                    case 'land':
                        // TODO Process country $contact->getAddress()->setValue('country', $value);
                        break;
                    case 'emailadres':
                        $contact->getEmail()->setValue('email', $value);
                        break;
                    /* case 'lidmaatschap':
                        // TODO Process, hoe?
                        break; */
                    default:
                        break;
                }
            }

            // Try to save form data
            $contact->save();

            // Add activity
            Activity::createActivity($contact->getId(), "WPCivi_MemberPaymentForm_Result",
                "Contact updated by MemberPaymentFormHandler", "Gravity Forms Entry ID: {$entry['id']}");

            // Set status and save entity info to metadata if possible
            $this->setWPCiviStatus(self::WPCIVI_SUCCESS, $form, $entry, 'Contact', $contact->id);

        } catch (\Exception $e) {

            // If we were able to get/update a contact try to create an activity, set status in both cases
            if (is_object($contact) && !empty($contact->id)) {

                $this->setWPCiviStatus(self::WPCIVI_ERROR, $form, $entry, 'Contact', $contact->id, $e->getMessage(), $e);

                try {
                    Activity::createActivity($contact->getId(), "WPCivi_MemberPaymentForm_Result",
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
}
