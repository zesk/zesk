<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Builder_Tag extends Contact_Builder_Base {
	/**
	 *
	 * @var string
	 */
	protected $contact_class = 'zesk\\Contact_Tag';

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Contact_Builder_Base::process()
	 */
	public function process(Contact_Import $import, $key, $value) {
		$value = to_list($value);
		if (is_array($value)) {
			foreach ($value as $val) {
				$import->merge_item($this->contact_class, $val, [
					'Name' => $val,
					'Account' => '{account}',
				]);
			}
		}
		return true;
	}
}
