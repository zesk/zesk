<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/permission/classes/zesk/Role.php $
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 4:58 PM
 */
namespace zesk;

/**
 *
 * @see Class_Role
 * @author kent
 */
class Role extends ORM {
	/**
	 *
	 * @param Application $application
	 * @return integer
	 */
	static function root_id(Application $application) {
		return $application->orm_registry(__CLASS__)
			->query_select()
			->what('id')
			->where('is_root', true)
			->integer('id', null);
	}
	/**
	 *
	 * @param Application $application
	 * @return integer
	 */
	static function default_id(Application $application) {
		return $application->orm_registry(__CLASS__)
			->query_select()
			->what('id')
			->where('is_default', true)
			->integer('id', null);
	}
	
	/**
	 *
	 * @return boolean
	 */
	function is_root() {
		return $this->member_boolean("is_root");
	}
	function is_default() {
		return $this->member_boolean("is_default");
	}
}

