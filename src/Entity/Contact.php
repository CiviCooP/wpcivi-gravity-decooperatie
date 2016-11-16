<?php
namespace WPCivi\Jourcoop\Entity;

use WPCivi\Jourcoop\EntityCollection;
use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Entity\Address;
use WPCivi\Shared\Entity\Contact as DefaultContact;
use WPCivi\Shared\Entity\Email;
use WPCivi\Shared\Entity\Phone;

/**
 * Class Entity\Contact.
 * @package WPCivi\Jourcoop
 */
class Contact extends DefaultContact
{

    /**
     * Get contacts that are active members.
     * Returns an array of Contact objects, that optionally have a 'membership' which contains
     * the _first_ active membership for a contact if $mparams['include_membership'] is set.
     * @param array $mparams Membership API parameters
     * @return EntityCollection|self[] Collection of Contact entities
     */
    public static function getMembers($mparams = [])
    {
        /*
         * NOTE: A chain API call wasn't very efficient here (n+1)?
         * Replaced by a Membership.get and a Contact.get with contact_id IN (?)
         * The CiviCRM 4.7 join API works well ('return' => 'contact_id.first_name') but doesn't seem to
         * include an option to return _all_ fields for the contact. -> TODO ask / feature request?
         */

        if (!isset($mparams['membership_type_id'])) {
            $mparams['membership_type_id'] = ['IN' => ['Lid', 'Lid (NVJ)', 'Lid (student)', 'Lid (associated)', 'Lead member']];
        }
        if (!isset($mparams['status_id'])) {
            $mparams['status_id'] = ['IN' => ['New', 'Current', 'Grace']];
        }
        if (!isset($mparams['include_membership'])) {
            $mparams['return'] = 'contact_id';
        }
        $mresults = EntityCollection::get('Membership', $mparams);

        $contact_ids = [];
        $memberships = [];
        foreach ($mresults as $r) {
            if ($mparams['include_membership']) {
                $memberships[$r->contact_id] = $r;
            }
            $contact_ids[] = $r->contact_id; // Replace with array_column on PHP >= 7
        }

        $contacts = EntityCollection::get('Contact', [
            'contact_id' => ['IN' => $contact_ids],
            'is_deleted' => 0,
            'options' => ['sort' => 'sort_name'],
        ]);
        if ($mparams['include_membership'] && count($contacts) > 0) {
            foreach ($contacts as $contact) {
                if (array_key_exists($contact->id, $memberships)) {
                    $contact->membership = $memberships[$contact->id];
                }
            }
        }

        return $contacts;
    }

    /**
     * Custom member search function.
     * TODO Not implemented yet! Members list search implemented in JS for now.
     * @ignore
     * @param array $mparams Membership API parameters
     * @return EntityCollection|self[] Collection of Contact entities
     */
    public static function searchMembers($mparams = [])
    {
       return [];
    }

    /**
     * Custom function to Create a CiviCRM individual contact, address and other contact data in one go.
     * Dutch field names are used on the form, so they're reused here.
     * @param array $params Parameters
     * @return self Contact entity
     * @throws WPCiviException
     */
    public static function createContact($params = [])
    {

        // Add new contact
        $contact = static::create([
            'contact_type' => 'Individual',
            'first_name'   => $params['voornaam'],
            'middle_name'  => $params['tussenvoegsel'],
            'last_name'    => $params['achternaam'],
            'display_name' => ($params['voornaam'] . (!empty($params['tussenvoegsel']) ? ' ' . $params['tussenvoegsel'] : '') . $params['achternaam']),
            'source'       => 'Website (new)',
        ]);

        // Add address
        // Fixed var name error + added country (always NL for now) to prevent PCDB issues
        if (!empty($params['postcode']) && !empty($params['huisnummer'])) {
            Address::create([
                'contact_id'    => $contact->id,
                'street_name'   => $params['straat'],
                'street_number' => $params['huisnummer'],
                'street_unit'   => $params['toevoeging'],
                'postal_code'   => $params['postcode'],
                'city'          => $params['woonplaats'],
                'country_id'    => 'NL',
            ]);
        }

        // Add phone numbers and email address
        if (!empty($params['telefoon'])) {
            Phone::createPhone($contact->id, $params['telefoon'], 'Phone');
        }
        if (!empty($params['mobiel'])) {
            Phone::createPhone($contact->id, $params['mobiel'], 'Mobile');
        }
        if (!empty($params['emailadres'])) {
            Email::createEmail($contact->id, $params['emailadres']);
        }

        // Add custom data that the form likely supplied
        if (!empty($params['rekeningnummeriban'])) {
            $contact->setCustom('Bank_Account_IBAN', $params['rekeningnummeriban']);
        }
        if (!empty($params['benjelidvandenvj'])) {
            $contact->setCustom('NVJ_Member', $params['benjelidvandenvj']);
        }

        // Save contact and reload
        $contact->save();
        return $contact;
    }

    /**
     * Get active memberships for the current contact
     * @return EntityCollection Collection of active memberships
     */
    public function getActiveMemberships()
    {
        if (!isset($this->memberships)) {
            $this->memberships = Membership::getActiveMemberships($this->id);
        }
        return $this->memberships;
    }

}
