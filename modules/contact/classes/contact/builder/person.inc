<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/person.inc $
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
class Contact_Builder_Person extends Contact_Builder_Base {
	/**
	 * 
	 * @var string
	 */
	protected $contact_class = "zesk\\Contact_Person";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Contact_Builder_Base::process()
	 */
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
