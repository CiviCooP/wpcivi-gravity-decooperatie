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
     * Saves entry to CiviCRM - and adds the result status and CiviCRM *Case* ID to the entry meta data.
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

            // Adding custom fields using Case.Create does not work - temporary workaround in setSingleCustomValue method!
            // TODO: Report / fix Case API class?

            $case->setSingleCustomValue('Description', $data['beschrijving']);
            $case->setSingleCustomValue('Service', $data['categorie']);

            if(isset($data['startdatum'])) {
                // Note: start date from form = custom field; core field start_date for case = date processing is started.
                $startDate = date('YmdHis', strtotime($data['startdatum']));
                $case->setSingleCustomValue('Start_Date', $startDate);
            }
            if(isset($data['tarief'])) {
                $case->setSingleCustomValue('Tariff', $data['tarief']);
            }

            // CiviCase creates activities automatically; but do set status and save entity info to metadata here if possible
            $this->setWPCiviStatus(self::WPCIVI_SUCCESS, $form, $entry, 'Case', $case->id);

        } catch (\Exception $e) {

            // Set error status
            $caseId = is_object($case) && !empty($case->id) ? $case->id : null;
            $this->setWPCiviStatus(self::WPCIVI_ERROR, $form, $entry, 'Case', $caseId, $e->getMessage(), $e);
        }

        // Return entry
        return $entry;
    }

}