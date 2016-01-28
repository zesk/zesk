<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/info.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

/**
 * Contact_Info
 */
abstract class Contact_Info extends Object {

	abstract function label_type();

	private function _update_contact() {
// 		if ($this->class->contact_object_field !== null) {
// 			$table = Object::class_table_name('Contact');
// 			$this->query_update()->values(array(
// 				$this->class->contact_object_field => $this->id()
// 			))->where(array(
// 				'id' => $this->Contact,
// 				$this->class->contact_object_field => null
// 			));
// 		}
	}

	public function store() {
		$result = parent::store();
		if ($result) {
			$this->_update_contact();
			return $result;
		}
		return $result;
	}

	public function register($where = null) {
		$result = parent::register($where);
		if ($this->register_result() === self::REGISTER_Insert) {
			$this->_update_contact();
		}
		return $result;
	}
}
