<?php declare(strict_types=1);
/**
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
class Contact_Builder_Phone extends Contact_Builder_Base {
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
	public function process(Contact_Import $import, $key, $value) {
		$clean_phone = Control_Phone::clean($value);
		$data = [
			'value' => $value,
		] + $this->data;
		$data = map($data, $this->data);
		$import->merge_item($this->contact_class, $clean_phone, $data);
		return true;
	}
}
