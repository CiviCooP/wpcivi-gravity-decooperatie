<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Gravity\Traits\ContactDataTrait;
use WPCivi\Jourcoop\Plugin;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\MemberContactFormHandler
 * Handles Gravity Form submissions for the 'Mijn gegevens' form.
 * @package WPCivi\Jourcoop
 */
class MemberContactFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     */
    protected $label = 'Member Contact Form';

    /**
     * Trait for contact data normalization
     */
    use ContactDataTrait;

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
        } catch (\Exception $e) {
            Plugin::exitOnException($e);
        }

        // Walk fields and add data
        foreach ($form['fields'] as &$field) {
            $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));
            switch ($label) {
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
                case 'telefoon':
                    $field->defaultValue = $contact->getPhone()->getValue('phone');
                    break;
                case 'mobiel':
                    $field->defaultValue = $contact->getMobile()->getValue('phone');
                    break;
                case 'emailadres':
                    $field->defaultValue = $contact->getEmail()->getValue('email');
                    break;
                case 'rekeningnummeriban':
                    $field->defaultValue = $contact->getCustom('Bank_Account_IBAN');
                    break;
                case 'kvknummer':
                    $field->defaultValue = $contact->getCustom('KvK_No');
                    break;
                case 'btwnummer':
                    $field->defaultValue = $contact->getCustom('BTW_No');
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
            return $entry;
        }

        // Get form data
        $data = $this->getDataKVArray($entry, $form);

        // Get current CiviCRM contact
        $contact = new Contact;
        try {
            $contact->loadCurrentWPUser();
        } catch (\Exception $e) {
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
                        // Mooier zou hier natuurlijk zijn: $contact->getAddress()->setStreetName('');
                        $contact->getAddress()->setValue('street_name', $value);
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
                    case 'telefoon':
                        $contact->getPhone()->setValue('phone', $value);
                        break;
                    case 'mobiel':
                        $contact->getMobile()->setValue('phone', $value);
                        break;
                    case 'emailadres':
                        $contact->getEmail()->setValue('email', $value);
                        break;
                    case 'rekeningnummeriban':
                        $contact->setCustom('Bank_Account_IBAN', $value);
                        break;
                    case 'kvknummer':
                        $contact->setCustom('KvK_No', $value);
                        break;
                    case 'btwnummer':
                        $contact->setCustom('BTW_No', $value);
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
            if (!empty($contact->getPhone()->getValue('phone'))) {
                $contact->getPhone()->save();
            }
            if (!empty($contact->getMobile()->getValue('phone'))) {
                $contact->getMobile()->save();
            }
            $contact->save();

            // Add activity
            Activity::createActivity($contact->getId(), "WPCivi_MemberContactForm_Result",
                "Contact info updated by MemberContactFormHandler", "Gravity Forms Entry ID: {$entry['id']}");

            // Add status and contact id to gform meta data
            gform_update_meta($entry['id'], 'wpcivi_status', 'SUCCESS', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_contactid', $contact->getId(), $form['id']);

        } catch (\Exception $e) {

            // Exception handling for development
            if (WP_DEBUG === true) {
                Plugin::exitOnException($e);
            }

            // Exception handling for production --> Add error status to gform meta data, but show nothing to user
            gform_update_meta($entry['id'], 'wpcivi_status', 'ERROR (' . $e->getMessage() . ')', $form['id']);

            // If we were able to create a contact, add contact id and try to create another activity
            if (is_object($contact) && !empty($contact->getId())) {
                gform_update_meta($entry['id'], 'wpcivi_contactid', $contact->getId(), $form['id']);

                try {
                    Activity::createActivity($contact->getId(), "WPCivi_MemberContactForm_Result",
                        "An error occurred while handling this form submission", "Error: " . $e->getMessage() . "<br>
                         Gravity Forms Entry ID: " . $entry['id'] . ".", "Cancelled");
                } catch (\Exception $e) {}
            }
        }

        // Return entry
        return $entry;
    }

}