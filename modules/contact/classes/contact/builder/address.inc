<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/address.inc $
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
class Contact_Builder_Address extends Contact_Builder_Base {
	/**
	 * 
	 * @var string
	 */
	protected $contact_class = "zesk\\Contact_Address";

	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Contact_Builder_Base::process()
	 */
	function process(Contact_Import $import, $key, $value) {
		$data = $this->data;
		$map = array(
			'key' => $key,
			'value' => $value
		);
		$data = map($data, $map);
		$low_label = avalue($data, 'Label', 'home');
		if (array_key_exists('Street_Line', $data)) {
			$item = $import->has_item($this->contact_class, $low_label);
			if (!is_array($item)) {
				$item = array();
			}
			if (arr::has($item, 'Street')) {
				$item .= "\n" . $data['Street_Line'];
			} else {
				$item['Street'] = $data['Street_Line'];
			}
			unset($data['Street_Line']);
			$item += $data;
			$import->set_item($this->contact_class, $low_label, $item);
		} else {
			$import->merge_item($this->contact_class, $low_label, $data);
		}
	}
}
