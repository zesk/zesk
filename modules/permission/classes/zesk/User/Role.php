<?php declare(strict_types=1);
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
	public function store(): self {
		if ($this->member_is_empty("creator") && null !== ($request = $this->application->request())) {
			$this->creator = $this->application->user($request);
		}
		return parent::store();
	}
}
