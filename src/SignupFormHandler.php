<?php
namespace WPCivi\Gravity\Jourcoop;

use WPCivi\Shared\BasePlugin;
use WPCivi\Shared\WPCiviApi;

/**
 * Class SignupFormPlugin
 * Handles Gravity Form submissions for the DeCooperatie.org Signup Form.
 * @package WPCivi\Gravity\Jourcoop
 */
class SignupFormHandler extends BasePlugin
{

    /**
     * Register Gravity Form submission hooks if Gravity Forms is active.
     */
    public function register()
    {
        if (!$this->isPluginActive('gravityforms')) {
            return true;
        }

        $this->addFilter('gform_save_field_value', [$this, 'saveFieldValue'], 10, 4);
        $this->addAction('gform_after_submission', [$this, 'afterSubmission'], 10, 2);

        $this->addFilter('gform_entry_meta', [$this, 'entryMeta'], 10, 2);
        $this->addAction('gform_entries_column', [$this, 'entriesColumn'], 10, 5);
        $this->addAction('gform_entry_detail', [$this, 'entryDetail'], 10, 2);
    }

    /**
     * Check if this handler class is enabled for this form
     * @param mixed $form Form
     * @return bool Is enabled?
     */
    private function handlerIsEnabled($form)
    {
        if (!empty($form['wpcivi_form_handler'])) {
            $class = new \ReflectionClass($this);
            if ($class->getShortName() == $form['wpcivi_form_handler']) {
                return true;
            }
        }
        return false;
    }

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
            return $value;
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
            return true;
        }

        // Set local timezone! (WP uses UTC, Civi uses default timezone)
        date_default_timezone_set(ini_get('date.timezone'));

        // Make form field label / id mapping + readable entry data array - not sure if there's a better way...
        $fields = [];
        $data = [];
        foreach ($form['fields'] as $field) {

            $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));
            if (!$label) {
                $label = $field->id;
            }

            $fields[$label] = $field->id;
            $data[$label] = $entry[$field->id];

            // For checkboxes, add an entry for each option value (18.1 -> maakuwkeuze.nieuwsbrief)
            if (get_class($field) == 'GF_Field_Checkbox') {
                foreach ($field->choices as $key => $choice) {
                    $key_id = $field->inputs[$key]['id'];
                    $data[$label . '.' . $choice['value']] = $entry[$key_id];
                }
            }
        }

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
                'activity_type_id' => "WPCivi_SignupForm_Result",
                'status_id'        => "Completed",
                'target_id'        => $contact->id,
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

        // Revert timezone to UTC for WP
        date_default_timezone_set('UTC');

        // Return entry
        return $entry;
    }

    /**
     * Implements hook gform_entry_meta.
     * Adds CiviCRM contact ID to form listings.
     * @param array $entry_meta Entry metadata
     * @param int $form_id Form ID
     * @return array Entry metadata
     */
    public function entryMeta($entry_meta, $form_id)
    {
        $entry_meta['wpcivi_contactid'] = [
            'label'             => 'CiviCRM ID',
            'is_numeric'        => true,
            'is_default_column' => true,
        ];
        return $entry_meta;
    }

    /**
     * Implements hook gform_entries_column.
     * Adds a link to the CiviCRM contact ID column (quick hack).
     * @param int $form_id Form ID
     * @param int $field_id Field ID
     * @param mixed $value Field value
     * @param array $entry Entry array
     * @param string $query_string Query string
     */
    public function entriesColumn($form_id, $field_id, $value, $entry, $query_string)
    {
        if (!empty($entry['wpcivi_contactid']) && $entry['wpcivi_contactid'] == $value) {
            echo '(<a href="/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&reset=1&cid=' . $value . '">View Contact</a>)';
        }
    }

    /**
     * Implements hook gform_entry_detail.
     * Show status and link to CiviCRM contact, or show the error that occurred.
     * wr_success contains 'SUCCESS' or 'ERROR: Error Message'.
     * @param mixed $form Form
     * @param mixed $entry Entry
     * @return null
     */
    public function entryDetail($form, $entry)
    {
        $status = gform_get_meta($entry['id'], 'wpcivi_status');
        $contact_id = gform_get_meta($entry['id'], 'wpcivi_contactid');

        if (!empty($status)) {

            echo "<div class='postbox'>\n<h3>CiviCRM Integration</h3>\n<div class='inside'>";
            if ($status == 'SUCCESS') {
                echo "This entry has been saved and can be deleted in Gravity Forms.<br>\n";
            } else {
                echo "This entry could <strong>NOT</strong> be correctly stored in CiviCRM.<br>\n<em>{$status}</em><br>\n";
            }

            if (!empty($contact_id)) {
                echo "<a href='/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&cid={$contact_id}&reset=1'><strong>View CiviCRM contact (id {$contact_id})</strong></a>\n";
            }
            echo "</div></div>";
        }
    }
}