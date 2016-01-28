<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/email.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Builder_Email extends Contact_Builder_Base {
	protected $contact_class = "Contact_Email";

	function process(Contact_Import $import, $key, $value) {
		if (!is_email($value)) {
			throw new Exception_Syntax(__("Not a valid email address."));
		}
		$parts = mail::parse_address($value);
		if (!$parts) {
			throw new Exception_Syntax(__("Unable to parse email address."));
		}
		$map = array(
			'key' => $key
		);
		$data = array(
			"Value" => $parts['email']
		) + $this->data;
		$data = map($data, $map);

		$import->merge_item($this->contact_class, $data['Value'], $data);

		return true;
	}
}
