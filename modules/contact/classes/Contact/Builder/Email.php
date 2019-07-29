<?php
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Builder_Email extends Contact_Builder_Base {
	/**
	 *
	 * @var string
	 */
	protected $contact_class = "zesk\\Contact_Email";

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Contact_Builder_Base::process()
	 */
	public function process(Contact_Import $import, $key, $value) {
		if (!is_email($value)) {
			throw new Exception_Syntax(__("Not a valid email address."));
		}
		$parts = Mail::parse_address($value);
		if (!$parts) {
			throw new Exception_Syntax(__("Unable to parse email address."));
		}
		$map = array(
			'key' => $key,
		);
		$data = array(
			"value" => $parts['email'],
		) + $this->data;
		$data = map($data, $map);

		$import->merge_item($this->contact_class, $data['value'], $data);

		return true;
	}
}
