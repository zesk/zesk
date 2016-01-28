<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/url.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Builder_URL extends Contact_Builder_Base {
	protected $contact_class = "Contact_Phone";

	function process(Contact_Import $import, $key, $value) {
		if (!url::valid($value)) {
			throw new Exception_Syntax(__("Not a valid url: \"$value\""));
		}
		$clean_value = url::normalize($value);
		$data = array(
			'key' => $key,
			'value' => $value
		);
		$data = map($data, $this->data);
		$import->merge_item($this->contact_class, $clean_value, $data);
		return true;
	}
}
