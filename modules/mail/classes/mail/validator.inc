<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/classes/mail/validator.inc $
 * @package zesk
 * @subpackage mail
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 *
 * @version $Id: validator.inc 3178 2015-05-06 18:57:29Z kent $
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

