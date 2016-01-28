<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/other.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Builder_Other extends Contact_Builder_Base {
	protected $contact_class = "Contact_Other";

	function process(Contact_Import $import, $key, $value) {
		$data = array(
			'Value' => $value
		) + $this->data;
		$data = map($data, array(
			'key' => $key
		));
		$clean_value = trim($value) . "." . trim($data['Label']);
		$import->merge_item($this->contact_class, $clean_value, $data);
		return true;
	}
}
