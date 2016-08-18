<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Entity\Membership;
use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\SignupFormHandler
 * Handles Gravity Form submissions for the DeCooperatie.org Signup Form (version 07/2016).
 * @package WPCivi\Jourcoop
 */
class SignupFormHandler extends BaseFormHandler
{
    /**
     * Implements gform_save_field_value.
     * Filters field values (there may also be separate form field handlers in separate classes).
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

        // Get form data
        $data = $this->getDataKVArray($entry, $form);
        $contact = null;

        // Set local timezone! (WP uses UTC, Civi uses default timezone)
        date_default_timezone_set(ini_get('date.timezone'));

        // Start submission to CiviCRM
        try {

            // Add contact including address / phone / email data
            $contact = Contact::createContact($data);

            // Add membership (with status Pending by default and without contributions)
            $membershipType = ($data['benjelidvandenvj'] == true ? 'Lid (NVJ)' : 'Lid');
            $membership = Membership::create([
                'contact_id' => $contact->id,
                'membership_type_id' => $membershipType,
                ]);

            // Add activity
            Activity::createActivity($contact->id, "WPCivi_SignupForm_Result",
                "Contact and membership added by SignupFormHandler", "Gravity Forms Entry ID: {$entry['id']}");

            // Add status and contact id to gform meta data
            gform_update_meta($entry['id'], 'wpcivi_status', 'SUCCESS', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_contactid', $contact->id, $form['id']);

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
                    Activity::createActivity($contact->id, "WPCivi_SignupForm_Result",
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