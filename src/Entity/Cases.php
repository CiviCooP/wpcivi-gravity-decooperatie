<?php
namespace WPCivi\Jourcoop\Entity;

use WPCivi\Shared\Entity\Cases as DefaultCases;
use WPCivi\Jourcoop\EntityCollection;

/**
 * Class Entity\Cases (since 'Case' is a PHP reserved word)
 * @package WPCivi\Jourcoop
 */
class Cases extends DefaultCases
{

    /**
     * Get jobs/opdrachten
     * @param array $params API parameters
     * @return EntityCollection Collection of Case entities
     */
    public static function getJobs($params = [])
    {
        return EntityCollection::get('Cases', array_merge($params, [
            'case_type_id' => 'opdracht',
            'is_deleted' => 0,
        ]));
    }

    /**
     * Get case 'Service' field options
     * @return string[]|null Service options
     */
    public function getCaseServiceOptions()
    {
        $getFields = $this->getFields('get');
        if(isset($getFields['Service'])) {
            return static::getCaseOptions($getFields['Service']->api_field_name);
        }
        return null;
    }

    /**
     * Get case 'Service name (instead of id) for this case
     * @return string|null Case Service name
     */
    public function getCaseServiceName()
    {
        $service_id = $this->getCustom('Service');
        $services = static::getCaseServiceOptions();
        if(!empty($services) && array_key_exists($service_id, $services)) {
            return $services[$service_id];
        }
        return null;
    }

}
