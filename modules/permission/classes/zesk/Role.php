<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
 * @property string $description
 */
class Role extends ORM {
	/**
	 *
	 * @param Application $application
	 * @return int
	 */
	public static function root_id(Application $application): int {
		return $application->ormRegistry(__CLASS__)->query_select()->addWhat('id', 'id')->addWhere('is_root', true)->integer('id', 0);
	}

	/**
	 *
	 * @param Application $application
	 * @return int
	 */
	public static function default_id(Application $application): int {
		return $application->ormRegistry(__CLASS__)->query_select()->addWhat('id', 'id')->addWhere('is_default', true)->integer('id', 0);
	}

	/**
	 *
	 * @return boolean
	 */
	public function is_root(): bool {
		return $this->member_boolean('is_root');
	}

	public function is_default(): bool {
		return $this->member_boolean('is_default');
	}
}
