<?php
/**
 * @version $Id: validator.inc 4056 2016-09-22 20:19:02Z kent $
 * @package zesk
 * @subpackage mail
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
use zesk\Timestamp;

/**
 * @package zesk
 * @subpackage mail
 */
class Mail_Validator extends Object {
	public static function validated($email, User $user) {
		$where = $where = array(
			"address" => $email,
			"user" => $user
		);
		$object = Object::factory(__CLASS__)->find($where);
		if (!$object) {
			return false;
		}
		return !$object->member_is_empty("confirmed");
	}
	static function validate($hash, User $user = null) {
		$members = array(
			"hash" => $hash
		);
		if ($user && $user->authenticated()) {
			$members["user"] = $user;
		}
		$object = Object::factory(__CLASS__);
		if (!$object->find($members)) {
			return false;
		}
		$object->set_member("confirmed", Timestamp::now());
		return $object->store();
	}
}

