<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Gravity\Traits\ContactDataTrait;
use WPCivi\Jourcoop\Gravity\Traits\BackendLinkTrait;
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
     * Traits for contact data normalization and backend integration
     */
    use ContactDataTrait;
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
        $contact->loadFullAddressData();

        // Walk fields and add data
        foreach($form['fields'] as &$field) {
            $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));
            switch($label) {
                case 'voornaam':
                    $field->defaultValue = $contact->first_name;
                    break;
                case 'tussenvoegsel':
                    $field->defaultValue = $contact->middle_name;
                    break;
                case 'achternaam';
                    $field->defaultValue = $contact->last_name;
                    break;
                case 'straat':
                    $field->defaultValue = $contact->street_name;
                    break;
                case 'huisnummer':
                    $field->defaultValue = $contact->street_number;
                    break;
                case 'toevoeging':
                    $field->defaultValue = $contact->street_unit;
                    break;
                case 'postcode':
                    $field->defaultValue = $contact->postal_code;
                    break;
                case 'woonplaats':
                    $field->defaultValue = $contact->city;
                    break;
                case 'telefoon':
                    $field->defaultValue = $contact->phone_phone;
                    break;
                case 'mobiel':
                    $field->defaultValue = $contact->phone_mobile;
                    break;
                case 'emailadres':
                    $field->defaultValue = $contact->email;
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
            return $form;
        }

        // Get form data
        $data = $this->getDataKVArray($entry, $form);
        $contact = null;

        // Set local timezone! (WP uses UTC, Civi uses default timezone)
        date_default_timezone_set(ini_get('date.timezone'));

        // TODO Niet meer rare nachtelijke opmerkingen in willekeurige PHP-files zetten

        // Start submission to CiviCRM
        try {

            // TODO!

//            // Add contact including address / phone / email data
//            $contact = Contact::createContact($data);
//
//            // Add membership (with status Pending by default and without contributions)
//            $membershipType = ($data['benjelidvandenvj'] == true ? 'Lid (NVJ)' : 'Lid');
//            $membership = Membership::create([
//                'contact_id'         => $contact->id,
//                'membership_type_id' => $membershipType,
//            ]);
//
//            // Add activity
//            Activity::createActivity($contact->id, "WPCivi_SignupForm_Result",
//                "Contact and membership added by SignupFormHandler", "Gravity Forms Entry ID: {$entry['id']}");
//
//            // Add status and contact id to gform meta data
//            gform_update_meta($entry['id'], 'wpcivi_status', 'SUCCESS', $form['id']);
//            gform_update_meta($entry['id'], 'wpcivi_contactid', $contact->id, $form['id']);

        } catch (\Exception $e) {

            // Exception handling for development
            if (WP_DEBUG === true) {
                echo "<strong>Gravity Form Handler error, exiting because WP_DEBUG is enabled:</strong><br />\n";
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
                exit;
            }

            // Exception handling for production --> Add error status to gform meta data, but show nothing to user
            gform_update_meta($entry['id'], 'wpcivi_status', 'ERROR (' . $e->getMessage() . ')', $form['id']);

            // If we were able to create a contact...
            if (is_object($contact) && isset($contact->id)) {

                // Add contact id to gform meta data
                gform_update_meta($entry['id'], 'wpcivi_contactid', $contact->id, $form['id']);

                // Try to create an activity
                try {
                    Activity::createActivity($contact->id, "WPCivi_MemberContactForm_Result",
                        "An error occurred while handling this form submission", "Error: " . $e->getMessage() . "<br>
                         Gravity Forms Entry ID: " . $entry['id'] . ".", "Cancelled");
                } catch (\Exception $e) {
                    // We won't create an activity about failing to create an activity after failing to create a contact.
                }
            }
        }

        // Revert timezone to UTC for WP
        date_default_timezone_set('UTC');

        // Return entry
        return $entry;
    }

}