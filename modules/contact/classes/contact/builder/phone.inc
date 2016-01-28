<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/phone.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Builder_Phone extends Contact_Builder_Base {
	protected $contact_class = "Contact_Phone";

	function process(Contact_Import $import, $key, $value) {
		$clean_phone = Control_Phone::clean($value);
		$data = array(
			'Value' => $value
		) + $this->data;
		$data = map($data, $this->data);
		$import->merge_item($this->contact_class, $clean_phone, $data);
		return true;
	}
}
