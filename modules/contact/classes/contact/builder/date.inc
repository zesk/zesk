<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/date.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Contact_Builder_Date extends Contact_Builder_Base {
	protected $contact_class = "Contact_Date";

	function process(Contact_Import $import, $key, $value) {
		if (!is_date($value)) {
			throw new Exception_Syntax(__("Not a valid date."));
		}
		$empty_values = $import->empty_date_values();
		if (in_array($value, $empty_values)) {
			return;
		}

		$data = array(
			'Value' => $value
		) + $this->data;
		$map = array(
			'key' => $key
		);
		$data = map($data, $map);

		$import->merge_item($this->contact_class, $data['Value'], $data);
		return true;
	}
}

