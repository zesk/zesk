<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/other.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Contact_Builder_Other extends Contact_Builder_Base {
	/**
	 * 
	 * @var string
	 */
	protected $contact_class = "zesk\\Contact_Other";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Contact_Builder_Base::process()
	 */
	function process(Contact_Import $import, $key, $value) {
		$data = array(
			'value' => $value
		) + $this->data;
		$data = map($data, array(
			'key' => $key
		));
		$clean_value = trim($value) . "." . trim($data['label']);
		$import->merge_item($this->contact_class, $clean_value, $data);
		return true;
	}
}
