<?php
/**
 * @version $Id: tag.inc 2159 2014-02-13 20:14:21Z kent $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Contact_Tag extends Zesk_Contact_Tag {
	function store() {
		$result = parent::store();
		if ($result) {
			$this->grant_user($this->User);
		}
		return $result;
	}

	function grant_user(User $user) {
		Object::class_query_insert("User_Contact_Tag")->values(array(
			"User" => $user,
			"Contact_Tag" => $this->id()
		))->replace()->execute();
	}
}
