<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/classes/mail/header.inc $
 * @package zesk
 * @subpackage mail
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * @see Class_Mail_Header
 * @property integer $id
 * @property hex $hash
 * @property Mail $mail
 * @property Mail_Header_type $type
 * @property string $value
 */
class Mail_Header extends Object {
	function dump() {
		return $this->name() . ": " . $this->value;
	}
	function store() {
		$this->hash = md5($this->value);
		return parent::store();
	}
	function value($set = null) {
		if ($set !== null) {
			$this->value = $set;
			return $this;
		}
		return $this->value;
	}
	function type($set = null) {
		if ($set !== null) {
			$this->type = $set;
			return $this;
		}
		return $this->type;
	}
	function name($set = null) {
		if ($set !== null) {
			$type = Object::factory('Mail_Header_Type', array(
				"code" => $set
			))->register();
			if ($type) {
				$this->type = $type;
			} else {
				throw new Exception_NotFound(__("Can not register header type {0}", array(
					$set
				)));
			}
			return $this;
		}
		return $this->type->name();
	}
}

