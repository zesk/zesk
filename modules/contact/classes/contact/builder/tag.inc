<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/tag.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Builder_Tag extends Contact_Builder_Base {
	protected $contact_class = "Contact_Tag";
	
	function process(Contact_Import $import, $key, $value) {
		$value = to_list($value);
		if (is_array($value)) {
			foreach ($value as $val) {
				$import->merge_item($this->contact_class, $val, array(
					'Name' => $val, 
					"Account" => "{account}"
				));
			}
		}
		return true;
	}
}
