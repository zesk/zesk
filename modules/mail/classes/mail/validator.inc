<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/classes/mail/validator.inc $
 * @package zesk
 * @subpackage mail
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

use zesk\Timestamp as Timestamp;

/**
 *
 * @version $Id: validator.inc 3919 2016-07-13 05:39:51Z kent $
 * @package zesk
 * @subpackage mail
 * @todo Convert to regular zesk
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

