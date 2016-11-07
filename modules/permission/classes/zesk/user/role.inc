<?php
/**
 * 
 */
namespace zesk;

/**
 * @see Class_User_Role
 * @author kent
 *
 */
class User_Role extends Object {
	public function store() {
		if ($this->member_is_empty("creator")) {
			$this->creator = User::instance_id();
		}
		return parent::store();
	}
}
