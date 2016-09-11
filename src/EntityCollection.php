<?php
namespace WPCivi\Jourcoop;

use WPCivi\Shared\EntityCollection as DefaultEntityCollection;

/**
 * Class EntityCollection. This class is extended just to get a collection of the right entities when we extend them.
 * @see DefaultEntityCollection
 * @package WPCivi\Jourcoop
 */
class EntityCollection extends DefaultEntityCollection
{
    protected function getEntityClassName()
    {
        return __NAMESPACE__ . "\\Entity\\" . $this->entityType;
    }
}