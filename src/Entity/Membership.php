<?php
namespace WPCivi\Jourcoop\Entity;

use WPCivi\Shared\Entity\Membership as DefaultMembership;
use WPCivi\Jourcoop\EntityCollection;

/**
 * Class Entity\Membership.
 * @package WPCivi\Jourcoop
 */
class Membership extends DefaultMembership
{

    /**
     * Get active memberships (all memberships or for contact $contact_id)
     * @param null $contact_id Contact ID or null
     * @return EntityCollection Collection of memberships
     */
    public static function getActiveMemberships($contact_id = null)
    {
        return EntityCollection::get('Membership', [
            'membership_type_id'   => ['Lid', 'Lid (NVJ)'],
            'membership_status_id' => ['New', 'Current', 'Grace'],
            'contact_id'           => $contact_id,
        ]);
    }

    /**
     * Default (minimum) parameters for new memberships at De Cooperatie:
     */
    protected function setDefaults()
    {
        $this->setArray([
            'membership_type_id' => 'Lid',
            'status_id'          => 'Pending',
            'is_override'        => 1,
            'join_date'          => date('Ymdhis'),
        ]);
    }

}