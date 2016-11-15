<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Jourcoop\Entity\Membership;
use WPCivi\Jourcoop\Gravity\Traits\ContactDataTrait;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\SignupFormHandler
 * Handles Gravity Form submissions for the DeCooperatie.org Signup Form (version 07/2016).
 * @package WPCivi\Jourcoop
 */
class SignupFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     */
    protected $label = 'Signup Form';

    /**
     * Trait for contact data normalization
     */
    use ContactDataTrait;

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
        $contact = null;

        // Start submission to CiviCRM
        try {

            // Add contact including address / phone / email data
            $contact = Contact::createContact($data);

            // Add membership - membership type depends on 'Ik word' select box on form.
            // Membership is added with status Pending by default and without contributions.
            switch (strtolower($data['ikword'])) {
                case 'student':
                    $membershipType = 'Lid (student)';
                    break;
                case 'associated':
                    $membershipType = 'Lid (associated)';
                    break;
                case 'lid':
                default:
                    $membershipType = 'Lid';
                    break;
            }
            Membership::create([
                'contact_id'         => $contact->id,
                'membership_type_id' => $membershipType,
            ]);

            // Add activity
            Activity::createActivity($contact->id, "WPCivi_SignupForm_Result",
                "Contact and membership added by SignupFormHandler", "Gravity Forms Entry ID: " . (int)$entry['id']);

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
            if (is_object($contact) && !empty($contact->id)) {

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

        // Return entry
        return $entry;
    }

}