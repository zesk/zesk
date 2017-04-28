<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/url.inc $
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
class Contact_Builder_URL extends Contact_Builder_Base {
	/**
	 * 
	 * @var string
	 */
	protected $contact_class = "zesk\\Contact_Phone";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Contact_Builder_Base::process()
	 */
	function process(Contact_Import $import, $key, $value) {
		if (!URL::valid($value)) {
			throw new Exception_Syntax(__("Not a valid url: \"$value\""));
		}
		$clean_value = URL::normalize($value);
		$data = array(
			'key' => $key,
			'value' => $value
		);
		$data = map($data, $this->data);
		$import->merge_item($this->contact_class, $clean_value, $data);
		return true;
	}
}
