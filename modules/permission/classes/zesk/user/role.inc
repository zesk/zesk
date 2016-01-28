<?php

/**
 * @see Class_Zesk_User_Role
 * @author kent
 *
 */
class Zesk_User_Role extends Object {
	public function store() {
		if ($this->member_is_empty("creator")) {
			$this->creator = User::instance_id();
		}
		return parent::store();
	}
}