<?php
namespace WPCivi\Jourcoop\Gravity\Traits;

/**
 * Class Gravity\Trait\BackendLinkTrait
 * Trait for forms that save a single CiviCRM contact ID and link to that contact in the backend.
 * Assumes 'wpcivi_status' and 'wpcivi_contactid' are set in the form handling function.
 * @package WPCivi\Jourcoop
 */

trait BackendLinkTrait {

    /**
     * Implements hook gform_entry_meta.
     * Adds CiviCRM contact ID to form listings.
     * @param array $entry_meta Entry metadata
     * @param int $form_id Form ID
     * @return array Entry metadata
     */
    public function entryMeta($entry_meta, $form_id)
    {
        $entry_meta['wpcivi_contactid'] = [
            'label'             => 'CiviCRM ID',
            'is_numeric'        => true,
            'is_default_column' => true,
        ];
        return $entry_meta;
    }

    /**
     * Implements hook gform_entries_column.
     * Adds a link to the CiviCRM contact ID column (quick hack).
     * @param int $form_id Form ID
     * @param int $field_id Field ID
     * @param mixed $value Field value
     * @param array $entry Entry array
     * @param string $query_string Query string
     */
    public function entriesColumn($form_id, $field_id, $value, $entry, $query_string)
    {
        if (!empty($entry['wpcivi_contactid']) && $entry['wpcivi_contactid'] == $value) {
            echo '(<a href="/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&reset=1&cid=' . $value . '">View Contact</a>)';
        }
    }

    /**
     * Implements hook gform_entry_detail.
     * Show status and link to CiviCRM contact, or show the error that occurred.
     * wr_success contains 'SUCCESS' or 'ERROR: Error Message'.
     * @param mixed $form Form
     * @param mixed $entry Entry
     * @return null
     */
    public function entryDetail($form, $entry)
    {
        $status = gform_get_meta($entry['id'], 'wpcivi_status');
        $contact_id = gform_get_meta($entry['id'], 'wpcivi_contactid');

        if (!empty($status)) {

            echo "<div class='postbox'>\n<h3>CiviCRM Integration</h3>\n<div class='inside'>";
            if ($status == 'SUCCESS') {
                echo "This entry has been saved and can be deleted in Gravity Forms.<br>\n";
            } else {
                echo "This entry could <strong>NOT</strong> be correctly stored in CiviCRM.<br>\n<em>{$status}</em><br>\n";
            }

            if (!empty($contact_id)) {
                echo "<a href='/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&cid={$contact_id}&reset=1'><strong>View CiviCRM contact (id {$contact_id})</strong></a>\n";
            }
            echo "</div></div>";
        }
    }

}