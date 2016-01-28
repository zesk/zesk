<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/address.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage contact
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * Zesk_Contact_Address
 *
 * @see Class_Zesk_Contact_Address
 */
class Zesk_Contact_Address extends Contact_Info {
	function store() {
		if ($this->changed('value')) {
			$address = Contact_Address_Parser::parse($this->value);
			if ($address) {
				$columns = array(
					"Street",
					"City",
					"Province",
					"PostalCode",
					"Country"
				);
				foreach ($columns as $col) {
					$this->$col = $address->$col;
				}
			}
		}
		return parent::store();
	}
	function label_type() {
		return Contact_Label::LabelType_Address;
	}
}
