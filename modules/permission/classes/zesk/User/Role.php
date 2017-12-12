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
class User_Role extends ORM {
	public function store() {
		if ($this->member_is_empty("creator")) {
			$this->creator = $this->application->user();
		}
		return parent::store();
	}
}
