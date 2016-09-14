<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\OldSignupFormHandler
 * First version (06/2016) of the signup form handler for De Cooperatie.
 * @package WPCivi\Jourcoop
 */
class OldSignupFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     */
    protected $label = '[OLD] Signup Form Handler';

    /**
     * Implements gform_save_field_value.
     * Filters field values. (There may also be separate form field handlers in separate classes)
     * @param mixed $value Current value
     * @param mixed $lead Lead
     * @param mixed $field Field
     * @param mixed $form Form
     * @return mixed Filtered value
     */
    public function saveFieldValue($value, $lead, $field, $form)
    {
        // Check if handler is enabled
        if (!$this->handlerIsEnabled($form)) {
            return $form;
        }

        // Check fields based on field label
        // (Anyone know a better way? Fields don't seem to have internal names)
        $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));

        // Capitalize postcode / huisnummer / toevoeging / woonplaats / rekeningnr
        if (in_array($label, ['postcode', 'huisnummer', 'toevoeging', 'woonplaats', 'rekeningnummeriban'])) {
            $value = strtoupper($value);
        }

        // Capitalize first letter of voornaam / achternaam (though com.cividesk.normalize should do this too)
        if (in_array($label, ['voornaam', 'achternaam'])) {
            $value = ucfirst($value);
        }

        // Add space to postcode if necessary
        if ($label == 'postcode' && strlen($value) == 6) {
            $value = preg_replace('/(?<=[a-z])(?=\d)|(?<=\d)(?=[a-z])/i', ' ', $value);
        }

        return $value;
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

        // Start submission to CiviCRM
        $wpcivi = WPCiviApi::getInstance();
        $contact = null;

        try {
            // Add contact (uses custom create method with short syntax + error handling)
            $contact = $wpcivi->create('Contact', [
                'contact_type' => 'Individual',
                'first_name'   => $data['voornaam'],
                'middle_name'  => $data['tussenvoegsel'],
                'last_name'    => $data['achternaam'],
                'display_name' => ($data['voornaam'] . (!empty($data['tussenvoegsel']) ? ' ' . $data['tussenvoegsel'] : '') . $data['achternaam']),
                'source'       => 'Website (nieuw)',
            ]);

            // Add address
            if (!empty($data['postcode']) && !empty($data['huisnummer'])) {
                $address = $wpcivi->create('Address', [
                    'contact_id'       => $contact->id,
                    'is_primary'       => 1,
                    'location_type_id' => WPCiviApi::LOCATION_TYPE_WORK,
                    'street_name'      => $data['straat'],
                    'street_number'    => $data['huisnummer'],
                    'street_unit'      => $data['toevoeging'],
                    'postal_code'      => $data['postcode'],
                    'city'             => $data['woonplaats'],
                    'country_id'       => WPCiviApi::COUNTRY_CODE_NL,
                ]);
            }

            // Add email address
            if (!empty($data['emailadres'])) {
                $email = $wpcivi->create('Email', [
                    'contact_id'       => $contact->id,
                    'is_primary'       => 1,
                    'location_type_id' => WPCiviApi::LOCATION_TYPE_WORK,
                    'email'            => $data['emailadres'],
                ]);
            }

            // Add phone number
            if (!empty($data['telefoon'])) {
                $phone = $wpcivi->create('Phone', [
                    'contact_id'       => $contact->id,
                    'is_primary'       => 1,
                    'location_type_id' => WPCiviApi::LOCATION_TYPE_WORK,
                    'phone_type_id'    => 'Phone',
                    'phone'            => $data['telefoon'],
                ]);
            }

            // Add mobile number
            if (!empty($data['mobiel'])) {
                $mobile = $wpcivi->create('Phone', [
                    'contact_id'       => $contact->id,
                    'is_primary'       => 1,
                    'location_type_id' => WPCiviApi::LOCATION_TYPE_WORK,
                    'phone_type_id'    => 'Mobile',
                    'phone'            => $data['mobiel'],
                ]);
            }

            // Custom fields data (uses custom method for shorter syntax)
            $wpcivi->addCustomData($contact->id, [
                ['Leden_Tijdelijk', 'Lid_Cooperatie', 'Y'],
                ['Leden_Tijdelijk', 'Rekeningnummer_IBAN', $data['rekeningnummeriban']],
                ['Leden_Tijdelijk', 'Akkoord_Incasso', 'Y'],
                ['Leden_Tijdelijk', 'Aanmelddatum', date('Ymdhis')],
                ['Leden_Tijdelijk', 'Lid_NVJ', $data['benjelidvandenvj']],
                ['Leden_Tijdelijk', 'Werkplekvoorkeur', $data['werkplekvoorkeur']],
            ]);

            // Add contact to group 'Nieuwe inschrijvingen website'
            $wpcivi->create('GroupContact', [
                'group_id'   => 'Nieuwe_inschrijvingen_website_3',
                'contact_id' => $contact->id,
            ]);

            // Add activity
            $wpcivi->create('Activity', [
                'activity_type_id' => "WPCivi_OldSignupForm_Result",
                'status_id'        => "Completed",
                'target_id'        => $contact->id,
                'source_contact_id' => 1, // No longer supported
                'subject'          => "Contact added by SignupFormHandler",
                'details'          => "Gravity Forms Entry ID: " . $entry['id'],
            ]);

            // Add status and contact id to gform meta data
            gform_update_meta($entry['id'], 'wpcivi_status', 'SUCCESS', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_contactid', (isset($contact->id) ? $contact->id : null), $form['id']);

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
                    $wpcivi->create('Activity', [
                        'activity_type_id' => "WPCivi_SignupForm_Result",
                        'status_id'        => "Cancelled",
                        'target_id'        => $contact->id,
                        'subject'          => "An error occurred while handling this form submission",
                        'details'          => "Error: " . $e->getMessage() . "<br>\nGravity Forms Entry ID: " . $entry['id'] . ".",
                    ]);
                } catch (\Exception $e) {
                }
            }
        }

        // Return entry
        return $entry;
    }
}