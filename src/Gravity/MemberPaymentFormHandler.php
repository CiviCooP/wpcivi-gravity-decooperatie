<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Entity\Membership;
use WPCivi\Jourcoop\EntityCollection;
use WPCivi\Jourcoop\Plugin;
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
                case 'straat':
                    $field->defaultValue = $contact->getAddress()->getValue('street_name');
                    break;
                case 'huisnummer':
                    $field->defaultValue = $contact->getAddress()->getValue('street_number');
                    break;
                case 'toevoeging':
                    $field->defaultValue = $contact->getAddress()->getValue('street_unit');
                    break;
                case 'postcode':
                    $field->defaultValue = $contact->getAddress()->getValue('postal_code');
                    break;
                case 'woonplaats':
                    $field->defaultValue = $contact->getAddress()->getValue('city');
                    break;
                case 'land':
                    $field->defaultValue = $contact->getAddress()->getValue('country_id');
                    break;
                case 'emailadres':
                    $field->defaultValue = $contact->getEmail()->getValue('email');
                    break;
                case 'lidmaatschap':
                    $this->filterMembershipChoices($field, $contact->getActiveMemberships());
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
        try {
            $contact->loadCurrentWPUser();
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
                    case 'straat':
                        $contact->getAddress()->setValue('street_name', $value);
                        $contact->getAddress()->setValue('contact_id', $contact->id);
                        $contact->getAddress()->setValue('location_type_id', 'Work');
                        break;
                    case 'huisnummer':
                        $contact->getAddress()->setValue('street_number', $value);
                        break;
                    case 'toevoeging':
                        $contact->getAddress()->setValue('street_unit', $value);
                        break;
                    case 'postcode':
                        $contact->getAddress()->setValue('postal_code', $value);
                        break;
                    case 'woonplaats':
                        $contact->getAddress()->setValue('city', $value);
                        break;
                    case 'land':
                        $contact->getAddress()->setValue('country_id', $value);
                        break;
                    case 'emailadres':
                        $contact->getEmail()->setValue('email', $value);
                        break;
                    case 'lidmaatschap':
                        // Not handled, read only field
                        break;
                    default:
                        break;
                }
            }

            // Try to save form data
            if (!empty($contact->getAddress()->getId()) || !empty($contact->getAddress()->getValue('street_name'))) {
                $contact->getAddress()->save();
            }
            if (!empty($contact->getEmail()->getValue('email'))) {
                $contact->getEmail()->save();
            }
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

    /**
     * Filter membership choices so only currently active memberships can be selected.
     *
     * @param \GF_Field $field Form Field
     * @param EntityCollection|Membership[] $memberships Memberships
     */
    public function filterMembershipChoices(&$field, $memberships)
    {
        foreach($field->choices as $key => $choice) {
            foreach($memberships as $membership) {
                if($choice['value'] === $membership->membership_type_id || $choice['value'] === $membership->membership_name) {
                    continue 2;
                }
            }

            unset($field->choices[$key]);
        }

        if(count($field->choices) === 0) {
            $field->choices = [['text' => '- Geen actief lidmaatschap gevonden! -', 'value' => null]];
        }
    }
}
