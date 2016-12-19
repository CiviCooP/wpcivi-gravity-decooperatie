<?php
namespace WPCivi\Jourcoop\Gravity\Traits;

/**
 * Class Gravity\Trait\ContactDataTrait
 * Trait with functions that are used on many contact info related forms.
 * @package WPCivi\Jourcoop
 */
trait ContactDataTrait
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
        $label = $this->getBaseLabel($field->label);

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

}