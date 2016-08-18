<?php
namespace WPCivi\Jourcoop\Entity;

use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Entity\Address;
use WPCivi\Shared\Entity\Contact as DefaultContact;
use WPCivi\Shared\Entity\Email;
use WPCivi\Shared\Entity\Phone;
use WPCivi\Shared\EntityCollection;

/**
 * Class Entity\Contact.
 * @package WPCivi\Jourcoop
 */
class Contact extends DefaultContact
{

    /**
     * Get contacts
     * @param array $params API parameters
     * @return EntityCollection Collection of Contact entities
     */
    public static function getContacts($params = []) {

        $contacts = new EntityCollection('Contact');
        return $contacts->get($params);
    }

    /**
     * Get contacts that are active members
     * @param array $params API parameters
     * @return EntityCollection Collection of Contact entities
     */
    public static function getMembers($params = []) {
        // TODO Implement search (= getContacts + automatically add membership check).
    }

    /**
     * Custom function to Create a CiviCRM individual contact, address and other contact data in one go.
     * Dutch field names are used on the form, so they're reused here.
     * @param array $params Parameters
     * @return self Contact entity
     * @throws WPCiviException
     */
    public static function createContact($params = []) {

        // Add new contact
        $contact = self::create([
            'contact_type' => 'Individual',
            'first_name'   => $params['voornaam'],
            'middle_name'  => $params['tussenvoegsel'],
            'last_name'    => $params['achternaam'],
            'display_name' => ($params['voornaam'] . (!empty($params['tussenvoegsel']) ? ' ' . $params['tussenvoegsel'] : '') . $params['achternaam']),
            'source'       => 'Website (new)',
        ]);

        // Add address
        if (!empty($data['postcode']) && !empty($data['huisnummer'])) {
            Address::create('Address', [
                'contact_id'       => $contact->id,
                'street_name'      => $data['straat'],
                'street_number'    => $data['huisnummer'],
                'street_unit'      => $data['toevoeging'],
                'postal_code'      => $data['postcode'],
                'city'             => $data['woonplaats'],
            ]);
        }

        // Add phone numbers and email address
        if(!empty($params['telefoon'])) {
            Phone::createPhone($contact->id, $params['telefoon'], 'Phone');
        }
        if(!empty($params['mobiel'])) {
            Phone::createPhone($contact->id, $params['telefoon'], 'Mobile');
        }
        if (!empty($params['emailadres'])) {
            Email::createEmail($contact->id, $params['email']);
        }

        // Add custom data that the form likely supplied
        if (!empty($params['rekeningnummeriban'])) {
            $contact->setCustom('Bank_Account_IBAN', $params['rekeningnummeriban']);
        }
        if(!empty($params['benjelidvandenvj'])) {
            $contact->setCustom('NVJ_Member', $params['benjelidvandenvj']);
        }

        // Save contact and reload
        $contact->save();
        return $contact;
    }

}
