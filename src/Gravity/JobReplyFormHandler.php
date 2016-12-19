<?php
namespace WPCivi\Jourcoop\Gravity;

use WPCivi\Jourcoop\Entity\Activity;
use WPCivi\Jourcoop\Entity\Cases;
use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Shared\Gravity\BaseFormHandler;

/**
 * Class Gravity\JobReplyFormHandler
 * Handles Gravity Form submissions for the DeCooperatie.org New Job Form ('Nieuwe opdracht').
 * @package WPCivi\Jourcoop
 */
class JobReplyFormHandler extends BaseFormHandler
{

    /**
     * Set (pretty) class name
     * @var string $label Label
     */
    protected $label = 'Job Reply Form';

    /**
     * Keep job object between hooks if possible
     * @var Cases|null $job Job Object
     */
    protected $job = null;

    /**
     * Implements hook gform_validation.
     * Checks if a valid Job ID is specified in the form's hidden 'Opdracht-ID' field.
     * @param mixed $validation_result Validation result
     * @return mixed Validation result
     */
    public function validation($validation_result)
    {
        $form = &$validation_result['form'];
        foreach ($form['fields'] as &$field) {
            if ($this->getBaseLabel($field->label) == 'opdrachtid') {

                $jobId = rgpost('input_' . $field->id);

                if (empty($jobId) || !is_numeric($jobId)) {
                    $field->failed_validation = true;
                    $field->validation_message = __('Error: opdracht-ID niet meegegeven in formulier!', 'wpcivi-jourcoop');
                    continue;
                }

                $this->job = new Cases;
                $this->job->load($jobId);
                if (empty($this->job->id)) {
                    $field->failed_validation = true;
                    $field->validation_message = sprintf(__('Error: geen geldige opdracht gevonden met ID %1$s!', 'wpcivi-jourcoop'), $jobId);
                    continue;
                }
            }
        }

        return $validation_result;
    }

    /**
     * Implements hook gform_after_submission.
     * Saves entry to CiviCRM - and adds the result status and CiviCRM *Activity* ID to the entry meta data.
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

        // Start submission to CiviCRM
        try {

            // Find job!
            if (empty($this->job)) {
                $this->job = new Cases;
                $this->job->load($data['opdrachtid']);
            }

            // Load contact
            $contact = new Contact;
            $contact->loadCurrentWPUser();

            // Goeie veldnaam
            $body = (string)$data['geefaanwaaromjemeewiltdoenenjebeschikbaarheid'];

            // Create case activity (using temporary(?) custom API method 'jourcoopcreate')
            $activity = Activity::create([
                'activity_type_id'  => 'WPCivi_JobReplyForm_Result',
                'case_id'           => $this->job->id,
                'source_contact_id' => $contact->id,
                'subject'           => 'New Job Reply by ' . $contact->display_name,
                'details'           => $body . "<br><br>Gravity Forms Entry ID: " . $entry['id'] . ".",
                'status'            => 'Scheduled',
                'activity_date_time' => date('YmdHis'),
            ], 'true', 'jourcoopcreate');

            // Set submission status
            $this->setWPCiviStatus(self::WPCIVI_SUCCESS, $form, $entry, 'Case', $this->job->id);

        } catch (\Exception $e) {

            // Set error status
            $jobId = (!empty($this->job) && !empty($this->job->id) ? $this->job->id : null);
            $this->setWPCiviStatus(self::WPCIVI_ERROR, $form, $entry, 'Case', $jobId, $e->getMessage(), $e);
        }

        // Return entry
        return $entry;
    }

}