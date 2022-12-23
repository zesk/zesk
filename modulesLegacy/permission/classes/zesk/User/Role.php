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
class User_Role extends ORMBase {
	public function store(): self {
		if ($this->memberIsEmpty('creator') && null !== ($request = $this->application->request())) {
			$this->creator = $this->application->user($request);
		}
		return parent::store();
	}
}
