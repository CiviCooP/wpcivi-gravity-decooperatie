<?php
namespace WPCivi\Jourcoop\Entity;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Entity\Activity as DefaultActivity;

/**
 * Class Entity\Activity.
 * @package WPCivi\Jourcoop
 */
class Activity extends DefaultActivity
{
    /**
     * Helper function to quickly add a (system) activity with default options.
     * @param $target_contact_id
     * @param $activity_type_id
     * @param string $subject
     * @param string|null $details
     * @param string|null $status_id
     * @param int|null $source_contact_id
     * @param int|null $case_id
     * @return static Activity Entity
     * @throws WPCiviException
     */
    public static function createActivity($target_contact_id, $activity_type_id, $subject, $details = null,
                                          $status_id = 'Completed', $source_contact_id = null)
    {
        $activity = new static;
        $activity->setArray([
            'activity_type_id'  => $activity_type_id,
            'status_id'         => $status_id,
            'target_contact_id' => (int) $target_contact_id,
            'source_contact_id' => (int) (!empty($source_contact_id) ? $source_contact_id : $activity->getSystemContactId()),
            'subject'           => $subject,
            'details'           => $details,
        ]);
        $activity->save();
    }

    /**
     * Returns System User Contact ID, if there is one.
     * (We might as well use the default organisation. And could check for a logged in user. Oh well.
     * @return int
     */
    public function getSystemContactId()
    {
        try {
            return WPCiviApi::call('Contact', 'getvalue', ['email' => 'no-reply@decooperatie.org', 'return' => 'id']);
        } catch(\CiviCRM_API3_Exception $e) {
            return 1;
        }
    }
}
