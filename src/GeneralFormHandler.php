<?php
namespace WPCivi\Gravity\Jourcoop;

use WPCivi\Shared\BasePlugin;

/**
 * Class GeneralFormHandler
 * General configuration and form handling for Gravity Forms. Adds an option to the form settings.
 * @package WPCivi\Gravity\Jourcoop
 */
class GeneralFormHandler extends BasePlugin
{

    /**
     * @var array Array of available form handlers
     */
    private $handlers = [
        'SignupFormHandler',
    ];

    /**
     * Register Gravity Form submission hooks if Gravity Forms is active
     */
    public function register()
    {
        if (!$this->isPluginActive('gravityforms')) {
            return true;
        }

        $this->addAction('gform_form_settings', [$this, 'formSettings'], 10, 2);
        $this->addAction('gform_pre_form_settings_save', [$this, 'formSettingsSubmit'], 10, 1);
        $this->addFilter('gform_tooltips', [$this, 'formSettingsTooltips']);
    }

    /**
     * Add custom options to the form admin
     * @param array $settings Settings
     * @param mixed $form Form
     * @return mixed Settings
     */
    public function formSettings($settings, $form)
    {
        $settings['CiviCRM Integration'] = [];
        $wpcivi_form_handler = rgar($form, 'wpcivi_form_handler');

        $fhtml = "<tr>
                  <th><label for='wpcivi_form_handler'>WPCivi Custom Form Handler</label> " .
                  gform_tooltip('wpcivi_form_handler', '', true) . "
                  </th><th>
                  <select name='wpcivi_form_handler' id='wpcivi_form_handler'>
                  <option value=''>-none-</option>
                 ";

        foreach ($this->handlers as $handler) {
            $fhtml .= "<option value='{$handler}'" . ($handler == $wpcivi_form_handler ? " selected" : "") . ">{$handler}</option>\n";
        }

        $fhtml .= "</select>
                   </th></tr>";

        $settings['CiviCRM Integration']['wpcivi_form_handler'] = $fhtml;
        return $settings;
    }

    /**
     * Save custom options in the form admin
     * @param mixed $form Form
     * @return mixed Settings
     */
    public function formSettingsSubmit($form)
    {
        $form['wpcivi_form_handler'] = rgpost('wpcivi_form_handler');
        return $form;
    }

    /**
     * Register form admin tooltips
     * @param array $tooltips Tooltips
     * @return array Tooltips
     */
    public function formSettingsTooltips($tooltips = [])
    {
        $tooltips['wpcivi_form_handler'] = '<h6>WPCivi Form Handler</h6> Select a custom form handler class to integrate this form with CiviCRM. The available classes are defined in the WPCivi Gravity Integration plugin.';
        return $tooltips;
    }

}