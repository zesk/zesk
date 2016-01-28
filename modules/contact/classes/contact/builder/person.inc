<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/person.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Builder_Person extends Contact_Builder_Base {
	protected $contact_class = "Contact_Person";

	function process(Contact_Import $import, $key, $value) {
		$map = array(
			'key' => $key,
			'value' => $value
		);
		$data = map($data, $map);
		$import->merge_item($this->contact_class, 0, $data);

		return true;
	}
}
