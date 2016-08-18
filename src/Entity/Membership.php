<?php
namespace WPCivi\Jourcoop\Civi\Entity;

use WPCivi\Shared\Civi\Entity;

/**
 * Class Entity\Membership.
 * @package WPCivi\Jourcoop
 */
class Membership extends Entity
{

    /**
     * Default parameters for new memberships at De Cooperatie (at minimum):
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