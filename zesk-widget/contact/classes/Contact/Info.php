<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Contact_Info
 * @property \zesk\Contact $contact
 */
abstract class Contact_Info extends ORMBase {
	/**
	 * @see Contact_Label::LabelType_Foo
	 *
	 * @return string
	 */
	abstract public function label_type();

	/**
	 *
	 *
	 * @todo Why is this commented out?
	 */
	private function _update_contact(): void {
		// 		if ($this->class->contact_object_field !== null) {
		// 			$table = ORM::class_table_name('Contact');
		// 			$this->query_update()->values(array(
		// 				$this->class->contact_object_field => $this->id()
		// 			))->where(array(
		// 				'id' => $this->Contact,
		// 				$this->class->contact_object_field => null
		// 			));
		// 		}
	}

	/**
	 * Effective no-op
	 * @todo
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORMBase::store()
	 */
	public function store(): self {
		$result = parent::store();
		if ($result) {
			$this->_update_contact();
			return $result;
		}
		return $result;
	}

	/**
	 * Effective no-op
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORMBase::register()
	 */
	public function register($where = null) {
		$result = parent::register($where);
		if ($this->statusInsert()) {
			$this->_update_contact();
		}
		return $result;
	}
}
