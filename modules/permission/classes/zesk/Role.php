<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 4:58 PM
 */
namespace zesk;

/**
 * @see Class_Role
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property boolean $is_root
 * @property boolean $is_default
 * @property boolean $is_coordinator
 * @property boolean $root_only
 * @property string $description
 */
class Role extends ORM {
	/**
	 *
	 * @param Application $application
	 * @return integer
	 */
	public static function root_id(Application $application) {
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
	public static function default_id(Application $application) {
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
	public function is_root() {
		return $this->member_boolean("is_root");
	}

	public function is_default() {
		return $this->member_boolean("is_default");
	}
}
