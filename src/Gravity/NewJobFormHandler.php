<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Cases;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\NewJobFormHandler
 * Handles Gravity Form submissions for the DeCooperatie.org New Job Form ('Nieuwe opdracht').
 * TODO WORK IN PROGRESS!
 * @package WPCivi\Jourcoop
 */
class NewJobFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     */
    protected $label = 'New Job Form';


    /**
     * Implements hook gform_after_submission.
     * Saves entry to CiviCRM - and adds the result status and CiviCRM *case* ID to the entry meta data.
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

            // Add new case and set type/status
            $case = new Cases;
            $case->setValue('case_type_id', 'opdracht');
            $case->setValue('status_id', 'Submitted');
            // Auto set - $case->setValue('start_date', date('Ymdhis'));

            // Set basic and custom fields
            $case->setValue('subject', $data['titel']);

            // Set client (opdrachtgever) -> for now, search for existing organisation and create it if necessary
            try {
                $client = new Contact;
                $client->loadBy([
                    'contact_type'      => 'Organization',
                    'organization_name' => $data['opdrachtgever'],
                    'is_deleted'        => 0,
                ]);
            } catch (WPCiviException $e) {
                $client = new Contact;
                $client->setArray([
                    'contact_type'      => 'Organization',
                    'organization_name' => $data['opdrachtgever'],
                    'source'            => 'Website (job frontend)',
                ]);
                $client->save();
            }

            // Set case client and case manager
            // Auto set - $case->setValue('creator_id', 'user_contact_id');
            $case->setValue('contact_id', $client->id);
            $case->save();

            // TODO Temporary workaround! Adding custom fields using Case.Create does not work...?
            $fields = $case->getFields('create');
            WPCiviApi::call('Case', 'setvalue', ['field' => $fields['Description']->api_field_name, 'id' => $case->getId(), 'value' => $data['beschrijving']]);
            WPCiviApi::call('Case', 'setvalue', ['field' => $fields['Service']->api_field_name, 'id' => $case->getId(), 'value' => $data['categorie']]);

            if(isset($data['startdatum'])) {
                // Start date from form -> custom field, case start_date -> date we start processing this job
                WPCiviApi::call('Case', 'setvalue', ['field' => $fields['Start_Date']->api_field_name, 'id' => $case->getId(), 'value' => date('Ymd', strtotime($data['startdatum']))]);
            }

            // Not creating activities since that is handled within CiviCase anyway
            // TODO Rewrite form handler exception handling + creating activities, similar code in most handlers

            // Add status and case id to gform meta data (now supports entity/entityid
            gform_update_meta($entry['id'], 'wpcivi_status', 'SUCCESS', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_entity', 'Case', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_entityid', $case->getId(), $form['id']);

        } catch (\Exception $e) {

            // Exception handling for development
            if (WP_DEBUG === true) {
                echo "<strong>Gravity Form Handler error, exiting because WP_DEBUG is enabled:</strong><br />\n";
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            // Exception handling for production --> Add error status to gform meta data, but show nothing to user
            gform_update_meta($entry['id'], 'wpcivi_status', 'ERROR (' . $e->getMessage() . ')', $form['id']);
            gform_update_meta($entry['id'], 'wpcivi_entity', 'Case', $form['id']);
        }

        // Return entry
        return $entry;
    }

}