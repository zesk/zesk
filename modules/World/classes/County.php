<?php declare(strict_types=1);

/**
 * @see Class_County
 * @author kent
 *
 */
namespace zesk\World;

use zesk\ORM\ORMBase;

/**
 * @see Class_County
 * @author kent
 *
 */
class County extends ORMBase {
	public function reassign($new): void {
		$old_id = intval($this->id);
		$new_id = empty($new) ? null : ORMBase::mixedToID($new);
		$this->application->ormRegistry(Contact_Address::class)
			->query_update()
			->ignore_constraints(true)
			->value('county', $new_id)
			->addWhere('county', $old_id)
			->execute();
	}

	public function usage_statistics() {
		return [
			'Contact_Address' => $this->application->ormRegistry(Contact_Address::class)
				->querySelect()
				->addWhere('county', $this->id)
				->addWhat('*total', 'COUNT(X.id)')
				->one_integer('total'),
		];
	}

	public static function permissions(Application $application) {
		return parent::default_permissions($application, __CLASS__);
	}
}
