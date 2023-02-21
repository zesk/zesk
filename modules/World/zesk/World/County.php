<?php
declare(strict_types=1);


/**
 * @see Class_County
 * @author kent
 *
 */
namespace zesk\World;

use zesk\Application;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\KeyNotFound;
use zesk\ORM\ORMEmpty;
use zesk\ORM\ORMBase;

/**
 * @see Class_County
 * @author kent
 * @property int $id
 * @property string $name
 * @property Province $province
 */
class County extends ORMBase {
	public const MEMBER_ID = 'id';

	public const MEMBER_NAME = 'name';

	public const MEMBER_PROVINCE = 'province';

	public function reassign(string $targetClass, int|County $new, string $targetColumn = 'county'): void {
		$old_id = $this->id;
		$new_id = empty($new) ? null : ORMBase::mixedToID($new);
		$this->application->ormRegistry($targetClass)
			->queryUpdate()
			->setIgnoreConstraints(true)
			->setValues([$targetColumn => $new_id])
			->appendWhere([$targetColumn => $old_id])
			->execute();
	}

	/**
	 * @param string $targetClass
	 * @param string $targetColumn
	 * @return int
	 * @throws Database\Exception\Duplicate
	 * @throws Database\Exception\NoResults
	 * @throws Database\Exception\TableNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 */
	public function usageStatistics(string $targetClass, string $targetColumn = 'county'): int {
		$target = $this->application->ormRegistry($targetClass);
		return $target->querySelect()
			->addWhere($targetColumn, $this->id())
			->addWhat('*total', 'COUNT(X.' . $target->idColumn() . ')')
			->integer('total');
	}

	/**
	 * @param Application $application
	 * @return array[]
	 */
	public static function permissions(Application $application): array {
		return parent::default_permissions($application, __CLASS__);
	}
}
